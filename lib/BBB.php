<?php

class BBB
{
    const BASE = '/var/bigbluebutton';
    const STATUS = '/var/bigbluebutton/recording/status';

    const RAW_AUDIO_SOURCE = '/var/freeswitch/meetings';
    const RAW_VIDEO_SOURCE = '/usr/share/red5/webapps/video/streams';
    const RAW_SHARE_SOURCE = '/var/bigbluebutton/deskshare';
    const RAW_SLIDE_SOURCE = '/var/bigbluebutton'; // :(
    const RAW_ARCHIVE_PATH = '/var/bigbluebutton/recording/raw';

    const PUBLISHED_PATH = '/var/bigbluebutton/published';
    // const RECORDING_STATUS = '/var/bigbluebutton/recording/status';

    const REC_PROCESS = '/var/bigbluebutton/recording/process';
    const REC_PUBLISH = '/var/bigbluebutton/recording/publish';

    // /var/bigbluebutton/recording/status
    const LOG_PATH = '/var/log/bigbluebutton';

    const CONF_BBB = '/usr/local/bigbluebutton/core/scripts/bigbluebutton.yml';
    const CONF_TOMCAT = '/var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties';

    const CONF_FS_ESL = '/opt/freeswitch/conf/autoload_configs/event_socket.conf.xml';

    // ${SERVLET_DIR}/lti/WEB-INF/classes/lti.properties
    //const CONF_RED5 = '/usr/share/red5/webapps/bigbluebutton/WEB-INF/red5-web.xml';
                       // /usr/share/red5/webapps/bigbluebutton/WEB-INF/bigbluebutton.properties
                       // /usr/share/red5/webapps/sip/WEB-INF/bigbluebutton-sip.properties

    public static $_api_host = null;
    public static $_api_salt = null;


    /**
        @param $id Meeting ID
        @param $name Friendly Name
        @param $mpw Moderator Password
        @param $apw Attendee PW
        @param $rec True
    */
    static function openMeeting($id,$name,$mpw,$apw,$rec=true)
    {
        $fn = 'create';
        $qs = http_build_query(array(
            'meetingID' => $id,
            'name' => $name,
            'moderatorPW' => $mpw,
            'attendeePW' => $apw,
            'record' => (!empty($rec) ? 'true' : ''),
            // 'logouURL' => $_ENV['app']['uri_logout'],
        ));

        $buf = self::_api($fn,$qs);
        $xml = simplexml_load_string($buf);

        return $xml;
    }

    /**
        Generates a Link to Connect to the Meeting
        @param $id Meeting ID
        @param $name Your Display Name
        @param $pass Password for Joining
    */
    static function joinMeeting($id,$name,$pass)
    {
        $fn = 'join';
        $qs = http_build_query(array(
            'meetingID' => $id,
            'fullName' => $name,
            'password' => $pass,
        ));
        $uri = self::_api_uri($fn,$qs);
        return $uri;
    }

    /**
        @param $mid MeetingId
        @param $pw Password
    */
    function shutMeeting($mid,$pw)
    {
        $fn = 'end';
        $qs = http_build_query(array(
            'meetingID' => $mid,
            'password'  => $pw,
        ));
        $ret = $this->_api($fn,$qs);
        return $ret;
    }

    /**
		List the Known Paths of BBB
    */
	static function listPaths()
	{
	   $mirror = new ReflectionClass(__CLASS__);
	   $list = $mirror->getConstants();

	   return $list;

	}

	/**
        List types of ??? Processsing?
    */
    static function listTypes()
    {
        // cd /usr/local/bigbluebutton/core/scripts/process; ls *.rb
        return array('presentation');
    }

    static function listMeetings($live=false)
    {
        if ($live) {
            $ret = self::_api('getMeetings',null);
            $ret = simplexml_load_string($ret);
            return $ret;
        }

        $ret = array();
        $ml = self::_ls_meeting(self::BASE);
        $ret = array_merge($ret,$ml);

        $ml = self::_ls_meeting(self::RAW_AUDIO_SOURCE);
        $ret = array_merge($ret,$ml);

        $ml = self::_ls_meeting(self::RAW_VIDEO_SOURCE);
        $ret = array_merge($ret,$ml);

        $ml = self::_ls_meeting(self::RAW_ARCHIVE_PATH);
        $ret = array_merge($ret,$ml);

        # ls -t /var/bigbluebutton | grep "[0-9]\{13\}$" | head -n $HEAD > $tmp_file
        # ls -t /var/bigbluebutton/recording/raw | grep "[0-9]\{13\}$" | head -n $HEAD >> $tmp_file
        $ret = array_unique($ret,SORT_STRING);
        // radix::dump($ret);

        // Sort Newest on Top
        usort($ret,function($a,$b) {
            $a = intval(substr($a,-13));
            $b = intval(substr($b,-13));
            if ($a == $b) return 0;
            return ($a > $b) ? -1 : 1;
        });

        return $ret;
    }

    /**
		List Processes of BigBlueButton
    */
    static function listProcesses()
    {
		$ret = array();

		$god_pid = trim(file_get_contents('/var/run/god.pid'));
		$red_pid = trim(file_get_contents('/var/run/red5.pid'));
		$rdb_pid = trim(file_get_contents('/var/run/redis.pid'));
		$tom_pid = trim(file_get_contents('/var/run/tomcat6.pid'));

		$buf = shell_exec('/bin/ps -e -opid,pcpu,rss,vsz,pmem,time,args');
		$pat = '/(\d+)\s+([\d\.]+)\s+(\d+)\s+(\d+)\s+(\d+\.\d+)\s+([\d\-\:]+)\s+(.*(ffmpeg|freeswitch|java|libreoffice|nginx|php|redis|ruby).*)$/m';
		if (!preg_match_all($pat, $buf, $m)) {
			throw new Exception("Unable to parse the process table");
		}

		$c = count($m[0]);
		// Build List
		for ($i=0;$i<$c;$i++) {
			$p = array(
				'pid' => $m[1][$i],
				'cpu' => $m[2][$i],
				'cpu-time' => $m[6][$i],
				'ram' => $m[5][$i],
				'ram-rss' => $m[3][$i],
				'ram-vsz' => $m[4][$i],
				'cmd' => $m[7][$i],
			);

			// Friendly Name Map
			if ($p['pid'] == $god_pid) $p['name'] = 'GOD';
			elseif ($p['pid'] == $tom_pid) $p['name'] = 'Tomcat';
			elseif ($p['pid'] == $rdb_pid) $p['name'] = 'Redis';
			elseif ($p['pid'] == $red_pid) $p['name'] = 'Red5';

			$ret[ $p['pid'] ] = $p;
		}

		// Sort It
		usort($ret,function($a,$b) {

			if ($a['cpu'] > $b['cpu']) return -1;
			if ($a['cpu'] == $b['cpu']) {
				if ($a['ram'] > $b['ram']) return -1;
				if ($a['ram'] == $b['ram']) {
					if ($a['ram-rss'] > $b['ram-rss']) return -1;
				}
			}
			return 1;
		});

		return $ret;
    }

    /**
        @param $mid Meeting ID like 'dio1234'
        @return false|BBB Recording XML Element
    */
    static function listRecordings()
    {
        $buf = self::_api('getRecordings',null);
        // radix::dump($buf);
        $xml = simplexml_load_string($buf);
        // print_r($xml->recordings);
        foreach ($xml->recordings->recording as $r) {
            // print_r($r);
            $chk = strval($r->meetingID);
            // echo "Check1: $mid == $chk\n";
            if ($mid == $chk) {
                return $r;
            }
        }

    }

    /**
        Internal Meeting List from Directory
    */
    private static function _ls_meeting($dir)
    {
        $ls = array();
        $dh = opendir($dir);
        while ($de = readdir($dh)) {
            if (preg_match('/^([0-9a-f]+\-[0-9]+)/',$de,$m)) {
                $ls[] = $m[1];
            }
        }
        closedir($dh);
        return $ls;
    }

    /**
        Run and API Request
    */
    private static function _api($fn,$qs)
    {
        $ch = curl_init(self::_api_uri($fn,$qs));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        // print_r(curl_getinfo($ch));
        curl_close($ch);
        return $ret;
    }

    /**
        Resolve the API URI
    */
    private static function _api_uri($fn,$qs)
    {
        $ck = sha1($fn . $qs . self::$_api_salt);
        $ret = self::$_api_host . $fn . '?' . $qs .'&checksum=' . $ck;
        return $ret;
    }

}
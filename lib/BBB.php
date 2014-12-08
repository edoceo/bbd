<?php
/**
	Interaction with BBB
*/

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
	const PUB = '/var/bigbluebutton/published/presentation';

	const REC_PROCESS = '/var/bigbluebutton/recording/process';
	const REC_PUBLISH = '/var/bigbluebutton/recording/publish';

	// /var/bigbluebutton/recording/status
	const LOG_PATH = '/var/log/bigbluebutton';

	const CONF_BBB = '/usr/local/bigbluebutton/core/scripts/bigbluebutton.yml';
	const CONF_TOMCAT = '/var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties';

	const CONF_FS = '/opt/freeswitch/conf';
	const CONF_FS_ESL = '/opt/freeswitch/conf/autoload_configs/event_socket.conf.xml';

	// ${SERVLET_DIR}/lti/WEB-INF/classes/lti.properties
	//const CONF_RED5 = '/usr/share/red5/webapps/bigbluebutton/WEB-INF/red5-web.xml';
	// /usr/share/red5/webapps/bigbluebutton/WEB-INF/bigbluebutton.properties
	// /usr/share/red5/webapps/sip/WEB-INF/bigbluebutton-sip.properties

	public static $_api_uri = null;
	public static $_api_key = null;


	/**
		@param $id Meeting ID
		@param $name Friendly Name
		@param $mpw Moderator Password
		@param $apw Attendee PW
		@param $rec True

		@see https://code.google.com/p/bigbluebutton/wiki/API#create
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
			'voiceBridge' => '000',
			'redirectClient' => 'true', // true|false
			'clientURL' => $_ENV['app']['uri_client'],
			'logoutURL' => $_ENV['app']['uri_logout'],
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
	static function joinMeeting($id,$name,$pass,$args=null)
	{
		$fn = 'join';
		$qs = array(
			'fullName' => $name,
			'meetingID' => $id,
			'password' => $pass,
		);
		if (is_array($args)) {
			$qs = array_merge($qs, $args);
		}
		ksort($qs);
		$qs = http_build_query($qs);
		$uri = self::_api_uri($fn,$qs);
		return $uri;
	}

	/**
		Call BBB Meeting Info
	*/
	static function statMeeting($id, $pw)
	{
		$fn = 'getMeetingInfo';
		$qs = http_build_query(array(
			'meetingID' => $id,
			'password'  => $pw,
		));
		$res = $this->_api($fn,$qs);
		$res = simplexml_load_string($res);

		$ret = array(
			'name' => '',
			'code' => '',
		);
		if ('SUCCESS' == strval($res->returncode)) {
			$ret['name'] = strval($res->meetingName);
			$ret['stat'] = 'live';
			$ret['time_alpha'] = floor(intval($res->startTime) / 1000);
			if (empty($ret['time_alpha'])) {
				$ret['time_alpha'] = floor(intval($res->createTime) / 1000);
			}
			foreach ($res->attendees->attendee as $a) {
				$ret['attendees'][ strval($a->userID) ] = array(
					'userID' => strval($a->userID),
					'name' => strval($a->fullName),
					'role' => strval($a->role),
				);
			}
		} else {
			// Ignore
			// print_r($res);
		}

		return $ret;
	}

	/**
		@param $mid MeetingId
		@param $pw Password
	*/
	static function shutMeeting($mid,$pw)
	{
		$fn = 'end';
		$qs = http_build_query(array(
			'meetingID' => $mid,
			'password'  => $pw,
		));
		$ret = self::_api($fn,$qs);
		return $ret;
	}

	/**
		Get XML Configuration
	*/
	static function getConfig()
	{
		$fn = 'getDefaultConfigXML';
		$ret = self::_api($fn);
		return $ret;
		$ret = simplexml_load_string($ret);
		return $ret;
	}

	/**
		Set XML Configuration

		setConfigXML is odd, funny name, post w/o content

		@see https://github.com/bigbluebutton/bigbluebutton/blob/master/bbb-api-demo/src/main/webapp/bbb_api.jsp#L290
		@see https://groups.google.com/forum/#!searchin/bigbluebutton-dev/setConfigXML%7Csort:relevance%7Cspell:false/bigbluebutton-dev/A9-QaNLhbZ4/MKMGv7ztHLsJ
	*/
	static function setConfig($mid, $xml)
	{
		$xml = preg_replace('/\s+/ms', ' ', $xml);

		// Proper Order
		// $post = 'configXML=' . rawurlencode($xml); // fails
		$qs = 'configXML=' . urlencode($xml); // works
		$qs.= '&meetingID=' . $mid;
		$qs.= '&checksum=' . sha1('setConfigXML' . $qs . self::$_api_key);

		$ch = curl_init(self::$_api_uri . 'setConfigXML.xml?'. $qs);
		curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		// curl_setopt($ch, CURLOPT_HEADER, array(
		//	  'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		// ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		// $inf = curl_getinfo($ch);
		curl_close($ch);

		$xml = simplexml_load_string($ret);
		return $xml;
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
	private static function _api($fn, $qs=null, $verb='GET')
	{
		$ch = null;
		switch ($verb) {
		case 'POST':
			$ch = curl_init(self::_api_uri($fn, $qs));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
			curl_setopt($ch, CURLOPT_HEADER, array(
				'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			));
			break;
		case 'GET':
			$ch = curl_init(self::_api_uri($fn,$qs));
			break;
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

	/**
		Resolve the API URI
	*/
	private static function _api_uri($fn,$qs)
	{
		$ret = self::$_api_uri . $fn . '?' . $qs;

		if ('setConfigXML.xml' == $fn) $fn = 'setConfigXML';

		$ck = sha1($fn . $qs . self::$_api_key);
		$ret.= '&checksum=' . $ck;

		return $ret;
	}

}

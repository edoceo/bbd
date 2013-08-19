<?php

class BBB
{
    const BASE = '/var/bigbluebutton';
    const STATUS = '/var/bigbluebutton/recording/status';

    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const VIDEO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    const REC_PATH = '/var/bigbluebutton/recording';
    const PUB_PATH = '/var/bigbluebutton/published';
    
    // /var/bigbluebutton/recording/status

    const BBB_PROPS = '/var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties';

    const FS_ESL_CONFIG = '/opt/freeswitch/conf/autoload_configs/event_socket.conf.xml';

    // ${SERVLET_DIR}/lti/WEB-INF/classes/lti.properties
    // /opt/freeswitch/conf/autoload_configs/event_socket.conf.xml
    // $RED5_DIR/webapps/sip/WEB-INF/bigbluebutton-sip.properties"
    // 	CONFIG_FILES="$RED5_DIR/webapps/bigbluebutton/WEB-INF/bigbluebutton.properties \
    // ${SERVLET_DIR}/bigbluebutton/WEB-INF/classes/bigbluebutton.properties \

    const RED5_CONFIG = '/usr/share/red5/webapps/bigbluebutton/WEB-INF/red5-web.xml';

    public static $_api_host = null;
    public static $_api_salt = null;

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

        $ml = array();
        $ml+= self::_ls_meeting(self::BASE);
        $ml+= self::_ls_meeting(self::REC_PATH . '/raw');

        # ls -t /var/bigbluebutton | grep "[0-9]\{13\}$" | head -n $HEAD > $tmp_file
        # ls -t /var/bigbluebutton/recording/raw | grep "[0-9]\{13\}$" | head -n $HEAD >> $tmp_file
        $ml = array_unique($ml,SORT_STRING);

        // Sort Newest on Top
        usort($ml,function($a,$b) {
            $a = substr($a,-13);
            $b = substr($b,-13);
            if ($a == $b) return 0;
            return ($a < $b) ? 1 : -1;
        });

        return $ml;
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
            if (preg_match('/^[0-9a-f]+\-[0-9]+$/',$de)) {
                $ls[] = $de;
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
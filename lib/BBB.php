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

    static function meetingList()
    {
        $ml = array();
        $ml+= self::_meeting_ls(self::BASE);
        $ml+= self::_meeting_ls(self::REC_PATH . '/raw');

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


    static function _meeting_ls($dir)
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


}
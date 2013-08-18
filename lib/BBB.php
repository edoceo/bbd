<?php

class BBB
{
    const BASE = '/var/bigbluebutton';

    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const VIDEO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    // const AUDIO_REC = '/var/bigbluebutton/recording/raw/audio';
    const REC_PATH = '/var/bigbluebutton/recording';
    const PUB_PATH = '/var/bigbluebutton/published';

    static function meetingList()
    {
        $ml = array();
        $ml+= self::_meeting_ls(self::BASE);
        $ml+= self::_meeting_ls(self::BASE . '/recording/raw');

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
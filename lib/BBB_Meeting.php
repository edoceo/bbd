<?php
/**
	@file
	@brief A Meeting Wrapper Object
*/

class BBB_Meeting
{
    private $_id;
    private $_external_name;

    public $id;
    public $date;
    public $code;
    public $name;

    /**
        Meeting
        @param $mid Meeting Identifier
    */
    function __construct($mid)
    {
        $this->_id = $mid;
        $this->_init();
        $x = array();
        $x[] = $this->code;
        $x[] = $this->_external_name;
        $x = implode('/',$x);
        if (strlen($x) > 1) $this->name = $x;

        // Date
        if (preg_match('/\-(\d+)$/',$this->_id,$m)) {
            $this->date = strftime('%Y-%m-%d %H:%M', intval($m[1]) / 1000);
        }
    }

    /**
		Return Array of Events from Meeting
    */
	function getEvents($m=null)
    {
		$ret = array();
		$file = BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/events.xml";

		if (!is_file($file)) {
			return $ret;
		}

		$xml = simplexml_load_file($file);

		$time_alpha = null;
		foreach ($xml->event as $e) {

			if (null == $time_alpha) {
				$time_alpha = $e['timestamp'];
			}

			// Filter to Specific Module
			if ( (!empty($m)) && ($m != strval($e['module'])) ) {
				continue;
			}

			// Skip List
			switch ($e['module'] . '/' . $e['eventname']) {
			case 'VOICE/ParticipantTalkingEvent':
			case 'PRESENTATION/CursorMoveEvent':
			case 'PRESENTATION/ResizeAndMoveSlideEvent':
			   continue 2;
			}

			$rec = array();
			$rec['module'] = strval($e['module']);
			$rec['event'] = strval($e['eventname']);
			$rec['source'] = $e;

			$rec['time_ms'] = intval($e['timestamp']);
			$rec['time_s'] = floor($rec['time_ms'] / 1000);
			$rec['time_offset_ms'] = $rec['time_ms'] - $time_alpha;

			$ret[] = $rec;
		}

		return $ret;
    }

    function playURI()
    {
        return '/playback/presentation/playback.html?meetingId=' . $this->_id;
    }

    /**
        Trigger the Meeting for Rebuild
        @see https://groups.google.com/forum/#!topic/bigbluebutton-dev/2dzq6NIcEdg
    */
    function rebuild()
    {
        $type_list = BBB::listTypes();
        $wipe_list = array();

		foreach ($type_list as $type) {
			$wipe_list[] = sprintf('%s/recording/process/%s/%s',BBB::BASE,$type,$this->_id);
			$wipe_list[] = sprintf('%s/recording/publish/%s/%s',BBB::BASE,$type,$this->_id);
			$wipe_list[] = sprintf('%s/recording/status/processed/%s-%s.done',BBB::BASE,$this->_id,$type);
			$wipe_list[] = sprintf('%s/processed/%s/%s',BBB::STATUS,$this->_id,$type);
			$wipe_list[] = sprintf('%s/published/%s/%s',BBB::BASE,$type,$this->_id);
			$wipe_list[] = sprintf('%s/unpublished/%s/%s',BBB::BASE,$type,$this->_id);
		}
        // $buf = null;
        // foreach ($wipe_list as $path) {
        //     $buf.= shell_exec("rm -frv $path 2>&1");
        // }

        // Re-Create File at Beginning of Process
        // $rec_done = sprintf('%s/recording/status/recorded/%s.done', BBB::BASE, $this->_id);
        // if (!is_file($rec_done)) {
		// 	file_put_contents($rec_done, 'BBD Requested Rebuild');
		// }

		$ret = null;
		foreach ($wipe_list as $path) {
			$ret.= wipe_path($path);
		}

        return $ret;

    }

    #	A -- Audio
    #	P -- Presentation
    #	V -- Video
    #	D -- Desktop
    #	E -- Events
    function archiveStat()
    {
        $ret = array(
            'audio' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/audio/*"),
            'video' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/video/*"),
            'slide' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/presentation/*/*"),
            'share' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/deskshare/*"),
            'event' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/events.xml"),
        );
        return $ret;
    }

    /**
		Stats the Done Files
    */
    function processStat()
    {
        $ret = array();

        $list = array('recorded','archived','sanity');
        foreach ($list as $k) {

            $file = sprintf('%s/%s/%s.done',BBB::STATUS,$k,$this->_id);
            if (!is_file($file)) continue;

            $ret[$k] = array('file' => $file);

            if (is_file($file)) {
                $ret[$k]['time_alpha'] = filemtime($ret[$k]['file']);
                $ret[$k]['time_omega'] = filectime($ret[$k]['file']);
            }

        }

        return $ret;
    }

    /**
        Returns Information on the Sources
    */
    function sourceStat()
    {
        // echo '<pre>' . print_r(glob("/var/freeswitch/meetings/{$this->_id}-*"),true) . '</pre>';
        //
        // echo '<h3><i class="icon-facetime-video"></i> Raw Video</h3>';
        // echo '<pre>' . print_r(glob("/usr/share/red5/webapps/video/streams/{$this->_id}"),true) . '</pre>';
        //
        // echo '<h3> Raw Presentation Slides</h3>';
        // echo '<pre>' . print_r(glob("/var/bigbluebutton/{$this->_id}/{$this->_id}/*"),true) . '</pre>';

        // echo '<h3> Desk Share</h3>';
        // echo '<pre>' . print_r(glob("var/bigbluebutton/deskshare/{$this->_id}"),true) . '</pre>';

        $ret = array(
            'audio' => glob(BBB::RAW_AUDIO_SOURCE . "/{$this->_id}-*"),
            'video' => glob(BBB::RAW_VIDEO_SOURCE . "/{$this->_id}/*"),
            'slide' => glob(BBB::RAW_SLIDE_SOURCE . "/{$this->_id}/{$this->_id}/*/*"),
            'share' => glob(BBB::RAW_SHARE_SOURCE . "/{$this->_id}"),
        );
        return $ret;
    }

    public function stat()
    {
    	$ret = array(
    		'source' => $this->sourceStat(),
    		'archive' => $this->archiveStat(),
    		'process' => $this->processStat(),
		);
    	return $ret;
    }

    /**
		Removes all the Files and Directories related to the Meeting
		@see http://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
		@return empty on success, bufffer of errors on fail
    */
	public function wipe()
	{
		$mid = $this->_id;
		if (empty($mid)) {
			throw new Exception("Invalid Meeting ID");
		}

		// Raw Sources
		$wipe_list = array(
			BBB::RAW_AUDIO_SOURCE . '/' . $mid . '*.wav', // /var/freeswitch/meetings/$MEETING_ID*.wav
			BBB::RAW_VIDEO_SOURCE . '/' . $mid, // /usr/share/red5/webapps/video/streams/$MEETING_ID
			BBB::RAW_SLIDE_SOURCE . '/' . $mid, // /var/bigbluebutton/$MEETING_ID
			BBB::RAW_SHARE_SOURCE . '/' . $mid, // /var/bigbluebutton/deskshare/$MEETING_ID*.flv
			BBB::RAW_ARCHIVE_PATH . '/' . $mid, // /var/bigbluebutton/recording/raw/$MEETING_ID*
			BBB::LOG_PATH . '/*' . $mid . '*', // var/log/bigbluebutton/*$MEETING_ID*
		);
		// Statuses
		foreach (array('archived','processed','recorded','sanity') as $k) {
			$wipe_list[] = BBB::STATUS . '/' . $k . '/' . $mid . '*';
		}
		// Published Stuff
		$type_list = BBB::listTypes();
		foreach ($type_list as $type) {
			$wipe_list[] = BBB::PUBLISHED_PATH . '/' . $type . '/' . $mid; // /var/bigbluebutton/published/$type/$MEETING_ID*
			// $wipe_list[] = BBB::UNPUBLISHED_PATH . '/' . $type . '/' . $mid; // /var/bigbluebutton/unpublished/$type/$MEETING_ID*
			$wipe_list[] = BBB::REC_PROCESS . '/' . $type . '/' . $mid; // /var/bigbluebutton/recording/process/$type/$MEETING_ID*
			$wipe_list[] = BBB::REC_PUBLISH . '/' . $type . '/' . $mid; // /var/bigbluebutton/recording/publish/$type/$MEETING_ID*
			$wipe_list[] = BBB::LOG_PATH . '/' . $type . '/*' . $mid . '.log'; // /var/log/bigbluebutton/$type/*$MEETING_ID*
		}

		$ret = null;
		foreach ($wipe_list as $path) {
			$ret.= wipe_path($path);
		}

		return $ret;
	}

    /**
		Initialise this Object
    */
    private function _init()
    {
        // Look for My Cached Data
        $file = "/var/bigbluebutton/{$this->_id}/bbd-cache.bin";
        if (is_file($file)) {
            $data = unserialize(file_get_contents($file));
            $this->code = $data['id'];
            $this->_external_name = $data['name'];
        }

        $file = BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/events.xml";
        if (is_file($file)) {
			$name = array();
			$fh = fopen($file,'r');
			$buf = fread($fh,2048);

			if(preg_match('/meetingId="(.+?)"/',$buf,$m)) {
				$this->code = $m[1];
			}

			if(preg_match('/meetingName="(.+?)"/',$buf,$m)) {
				$this->_external_name = $m[1];
			}
			fclose($fh);
        } else {
			// throw new Exception("Events Not Found");
			$this->code = '## LOST ##';
			$this->_external_name = '## LOST ##';
		}
    }
}
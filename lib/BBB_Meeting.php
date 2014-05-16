<?php
/**
	@file
	@brief A Meeting Wrapper Object
*/

class BBB_Meeting
{
    private $_evt_list;
    private $_usr_list;
    private $_vid_list;
    private $_map_call_user = array();

    public $id;
    public $code;
    public $date;
    public $name;
    public $stat = 'lost';
    public $time_alpha;
    public $time_omega;

    public $data_redis = false;
    public $data_event = false;

    /**
        Meeting
        @param $mid Meeting Identifier
    */
    function __construct($arg)
    {
    	$this->_evt_list = array();
    	$this->_usr_list = array();
    	$this->_vid_list = array();

    	$arg = trim($arg);

    	if (preg_match('/^[0-9a-f]+\-\d+$/', $arg)) {

			$this->id = $arg;

			// Load Redis
			// Load from Redis & File by ID
			$rec = bbd::$r->hGetAll("meeting:info:{$this->id}");
			if (!empty($rec) && (count($rec)>0)) {
				$this->data_redis = 'byId';
				$this->code = $rec['meetingId'];
				$this->name = $rec['meetingName'];
				$this->stat = 'live';
			}

			$file = BBB::RAW_ARCHIVE_PATH . "/{$this->id}/events.xml";
			if ($rec = self::infoFromFile($file)) {
				$this->data_event = 'byId';
				$this->id = $rec['id'];
				$this->code = $rec['code'];
				$this->name = $rec['name'];
				$this->stat = 'done';
			}
		}

		if (empty($this->id)) {

			// Search Redis
			$pre = sha1($arg);
			$res = bbd::$r->keys("meeting:info:$pre*");
			foreach ($res as $key) {
				$rec = bbd::$r->hGetAll($key);
				// echo "Key:$x $key\n" . print_r($rec);
				if (($arg == $rec['meetingId']) || ($arg == $rec['meetingName'])) {
					if (preg_match('/([0-9a-f]+\-\d+)/',$key,$m)) {
						$this->data_redis = 'bySearch';
						$this->id = $m[1];
						$this->code = $rec['meetingId'];
						$this->name = $rec['meetingName'];
						$this->stat = 'live';
					}
					break;
				}
			}

			// Search Filesystem
			$path = BBB::RAW_ARCHIVE_PATH . "/$pre*/events.xml";
			$list = glob($path);
			// radix::dump($list);
			foreach ($list as $file) {
				$rec = self::infoFromFile($file);
				// echo "if (($arg == {$rec['code']}) || ($arg == {$rec['name']})) {\n";
				if (($arg == $rec['code']) || ($arg == $rec['name'])) {
					$this->data_event = 'bySearch';
					$this->id = $rec['id'];
					$this->code = $rec['code'];
					$this->name = $rec['name'];
					$this->stat = 'done';
					break;
				}
			}
		}

		// Determine Time from ID
		if (preg_match('/\-(\d+)/', $this->id, $m)) {
			$this->time_alpha = $m[1];
			$this->date = strftime('%Y-%m-%d %H:%M', intval($m[1]) / 1000);
		}

		// $rec = bbd::$r->hGetAll("meeting:{$this->id}:recordings");
		// print_r($rec);

        // Look for My Cached Data
        // $file = "/var/bigbluebutton/{$this->id}/bbd-cache.bin";
        // if (is_file($file)) {
		// 	$data = unserialize(file_get_contents($file));
		// 	$this->code = $data['id'];
        // }

		$this->time_omega = floor(microtime(true) * 1000);

    }

    /**
		Return Array of Events from Meeting
    */
	function getEvents($m=null)
    {
		if (empty($this->_evt_list)) {
			$this->_loadEventsFromRedis();
			$this->_loadEventsFromFile();
		}

		return $this->_evt_list;
	}

	/**
		Loads Events from the Events file made by BBB
		@return void
	*/
	private function _loadEventsFromFile()
	{
		$file = BBB::RAW_ARCHIVE_PATH . "/{$this->id}/events.xml";

		if (!is_file($file)) {
			return false;
		}

		$xml = simplexml_load_file($file);

		$this->time_alpha = null;
		foreach ($xml->event as $e) {

			$this->_procEvent($e);

			if (null == $this->time_alpha) {
				$this->time_alpha = intval($e['timestamp']);
			}

			// Filter to Specific Module
			if ( (!empty($m)) && ($m != strval($e['module'])) ) {
				continue;
			}
		}
		// return $ret;
    }

    /**
    	Loads Events from the Redis Datasource
    	@return void
	*/
    private function _loadEventsFromRedis()
    {
		// Load from Redis
		$pat = 'recording:' . $this->id . ':*';
		$res = bbd::$r->keys($pat);
		if (0 == count($res)) {
			return false;
		}

		// $ret['status'] = 'live';
		foreach ($res as $key) {
			$e = bbd::$r->hGetAll($key);
			$this->_procEvent($e);
		}
    }

    /**
		Process the event into this meeting
    */
    function _procEvent($x)
    {
		$bbe = new BBB_Event($x);
		$bbe['time_offset_ms'] = $bbe['time'] - $this->time_alpha;

		// Parse Interesting Data
    	switch ($bbe['module'] . '/' . $bbe['event']) {
		case 'PARTICIPANT/EndAndKickAllEvent':

			$this->stat = 'done';

			// Update audio/video exit times where needed
			$keys = array_keys($this->_usr_list);
			foreach ($keys as $uid) {
				if (empty($this->_usr_list[$uid]['audio_omega'])) $this->_usr_list[$uid]['audio_omega'] = $bbe['time_offset_ms'];
				if (empty($this->_usr_list[$uid]['video_omega'])) $this->_usr_list[$uid]['video_omega'] = $bbe['time_offset_ms'];
			}

			// set video exit times?
			$keys = array_keys($this->_vid_list);
			foreach ($keys as $vid) {
			// 	// foreach ($this->_vid_list[$vid] as $i=>$data) {
			// 	// 	if (empty($data['video_omega'])) {
			// 	// 		$this->_vid_list[$vid][$i]['video_omega'] = $bbe['time_offset_ms'];
			// 	// 		$this->_vid_list[$vid][$i]['delta'] = $this->_vid_list[$vid][$i]['video_omega'] - $this->_vid_list[$vid][$i]['video_alpha'];
			// 	// 	}
			// 	// }
			// 	// if (empty($this->_vid_list[$vid]['video_omega'])) $this->_vid_list[$vid]['video_omega'] = $e['time_offset_ms'];
			}
			break;
		case 'PARTICIPANT/ParticipantJoinEvent':
			$uid = $bbe['user_id'];
			$this->_usr_list[ $uid ] = array(
				   'uid' => $uid,
				   'mode' => 'live',
				   'role' => $bbe['role'],
				   'name' => $bbe['name'],
				   'time_init' => $bbe['time_offset_ms'],
			);
			break;
		case 'PARTICIPANT/ParticipantLeftEvent':
			$uid = $bbe['user_id'];
			$this->_usr_list[ $uid ]['mode'] = 'exit';
			$this->_usr_list[ $uid ]['time_exit'] = $bbe['time_offset_ms'];

			if (!empty($this->_usr_list[ $uid ]['name'])) {
				$bbe['name'] = $this->_usr_list[ $uid ]['name'];
			}

			// // $user_list[$uid]['span'] = $user_list[$uid]['time_exit'] - $user_list[$uid]['time_join'];
			// // if (empty($user_list[$uid]['time_audio_omega']) && !empty($user_list[$uid]['time_audio_omega'])) {
			// 	$user_list[$uid]['time_audio_omega'] = $e['time_offset_ms'];
			// 	$user_list[$uid]['span_audio'] = $user_list[$uid]['time_audio_omega'] - $user_list[$uid]['time_audio_alpha'];
			// // }
			// if (empty($user_list[ $uid ]['time_video_omega']) && !empty($user_list[$uid]['time_video_alpha'])) {
			// 	$user_list[$uid]['time_video_omega'] = $e['time_offset_ms'];
			// 	$user_list[$uid]['span_video'] = $user_list[$uid]['time_video_omega'] - $user_list[$uid]['time_video_alpha'];
			// }
			break;
		case 'PARTICIPANT/ParticipantStatusChangeEvent':
			// print_r($this->_vid_list);
			// print_r($bbe);
			// exit;
			// $vid = $bbe['user_id'];
			// if (preg_match('/true,stream=(.+)/', $bbe['value'], $m)) {
			// 	$this->_vid_list[$vid]['file'] = $m[1] . '.flv';
			// 	if (!is_file($this->_vid_list[$vid]['file'])) {
			// 		$b = basename($this->_vid_list[$vid]['file']);
			// 		$this->_vid_list[$vid]['file'] = '/var/bigbluebutton/recording/raw/' . $this->id . '/video/' . $this->id . '/' . $b;
			// 	}
			// 	$this->_vid_list[$vid]['time_join'] = $bbe['time_offset_ms'];
			// }
			break;
		case 'VOICE/ParticipantJoinedEvent':

			if (preg_match('/^(\w+)\-/', $bbe['callername'], $m)) {
				$uid = $m[1];
				$cid = $bbe['participant'];
				$this->_usr_list[$uid]['call_id'] = $cid;
				$this->_usr_list[$uid]['conf_id'] = $bbe['bridge'];

				if (empty($this->_usr_list[$uid]['audio_alpha'])) {
					$this->_usr_list[$uid]['audio_alpha'] = $bbe['time_offset_ms'];
					// $this->_usr_list[$uid]['call_id'] = strval($e['participant']); // @todo call_id?
				}

				$this->_map_call_user[$cid] = $uid;

			}

			// This can happen more than once per user, we only want the first one
			// if (preg_match('/^(\w+)\-/', $x, $m)) {
			// 	$uid = $m[1];
			// } elseif (preg_match('/^(\+|%2B)/', $x)) {
			// 	// It's a Dial In, ?
			// } elseif (preg_match('/^Outbound/', $x)) {
			// 	// Ignore Special Case
			// } else {
			// 	echo "Cannot Parse VOICE/ParticipantJoinedEvent\n";
			// 	die(print_r($e));
			// }

			break;
		case 'VOICE/ParticipantLeftEvent':

			// Save Audio End by Finding Original Participant
			$call_id = $bbe['participant'];
			foreach ($this->_usr_list as $uid=>$x) {
				if (!empty($x['call_id']) && ($call_id == $x['call_id'])) {
					$this->_usr_list[$uid]['audio_omega'] = $bbe['time_offset_ms'];
				}
			}

			break;

		case 'VOICE/ParticipantTalkingEvent':
			if (!empty($this->_map_call_user[ $bbe['participant'] ])) {
				$bbe['user_id'] = $this->_map_call_user[ $bbe['participant'] ];
				$bbe['user_name'] = $this->_usr_list[ $bbe['user_id'] ]['name'];
			}
			break;
		case 'WEBCAM/StartWebcamShareEvent': // @todo Hook

			// echo "WebCam Alpha: {$bbe['stream']} at {$bbe['time_offset_ms']}\n";
			if (!preg_match('/(\d+x\d+\-((\w+)\-\d+))$/', $bbe['stream'], $m)) {
				echo "Cannot Parse WEBCAM/StartWebcamShareEvent\n";
				die(print_r($bbe));
			}

			$flv = "{$m[1]}.flv";
			$vid = $m[2];
			$uid = $m[3];

			$this->_usr_list[$uid]['video_list'][$vid] = true;
			if (empty($this->_usr_list[$uid]['video_alpha'])) {
				$this->_usr_list[$uid]['video_alpha'] = $bbe['time_offset_ms'];
			}

			// Detect Video File
			$f = '/var/bigbluebutton/recording/raw/' . $this->id . '/video/' . $this->id . '/' . $flv;
			if (!is_file($f)) {
				$f = '/usr/share/red5/webapps/video/streams/' . $this->id . '/' . $flv;
			}
			if (!is_file($f)) {
				$f = null;
			}

			$this->_vidSet($vid, array(
				'uid' => $uid,
				'file' => $f,
				'video_alpha' => $bbe['time_offset_ms'],
			));

			// $this->_vid_list[$vid][] = ;
			// $key = sprintf('%s.%d', $vid, count($video_list[$vid]));
			// 'video_alpha' => $e['time_offset_ms'],
			// 	);
			// } else {
			//
			// }
			// $user_list[$uid]['video_list'][$sid] = array(
			// 	'time_video_alpha' => $e['time_offset_ms'],
			// );

			// echo "Webcam Alpha: $vid at " . formatMMSS($e['time_offset_ms']/1000) . "\n";
			// if (empty($video_list[$vid]['webcam_alpha'])) {
			// 	$video_list[$vid]['webcam_alpha'] = $e['time_offset_ms'];
			// 	$video_list[$vid]['webcam_alpha_f'] = formatMMSS($e['time_offset_ms']/1000);
			// }
			break;
		case 'WEBCAM/StopWebcamShareEvent': // @todo Hook

			// New
			// echo "WebCam Omega: $x at {$e['time_offset_ms']}\n";

			if (!preg_match('/\-((\w+)\-\d+)$/', $bbe['stream'], $m)) {
				echo "Cannot Parse WEBCAM/StopWebcamShareEvent\n";
				die(print_r($bbe));
			}

			$vid = $m[1];
			$uid = $m[2];

			$arg = array(
				'uid' => $uid,
				'video_omega' => $bbe['time_offset_ms'],
			);
			if (!empty($this->_vid_list[$vid]['video_alpha'])) {
				$arg['delta'] = $bbe['time_offset_ms'] - $this->_vid_list[$vid]['video_alpha'];
			}

			$this->_vidSet($vid, $arg);
			// $this->_vid_list[$vid][$key]['video_omega'] = $bbe['time_offset_ms'];
			// $this->_vid_list[$vid][$key]['delta'] = $bbe['time_offset_ms'] - $this->_vid_list[$vid][$key]['video_alpha'];

			// $vid = count($user_list[$uid]['this->_vid_list']) - 1;
			// // $user_list[ $uid ]['time_video_omega'] = $e['time_offset_ms'];
			// // // $user_list[ $uid ]['span_video'] = intval($e['source']->duration);
			// // $user_list[ $uid ]['span_video'] = $user_list[ $uid ]['time_video_omega'] - $user_list[ $uid ]['time_video_alpha'];
			// die(print_r($e));

			break;
		case 'CHAT/PublicChatEvent':
		case 'PARTICIPANT/AssignPresenterEvent':
		case 'PRESENTATION/ConversionCompletedEvent':
		case 'PRESENTATION/CursorMoveEvent':
		case 'PRESENTATION/GenerateSlideEvent':
		case 'PRESENTATION/GotoSlideEvent':
		case 'PRESENTATION/RemovePresentationEvent':
		case 'PRESENTATION/ResizeAndMoveSlideEvent':
		case 'PRESENTATION/SharePresentationEvent':
		case 'VOICE/ParticipantLockedEvent':
		case 'VOICE/ParticipantMutedEvent':
		case 'VOICE/ParticipantTalkingEvent': // @todo cleanup
		case 'VOICE/StartRecordingEvent': // @todo Hook
		case 'VOICE/StopRecordingEvent': // @todo Hook
		case 'WHITEBOARD/AddShapeEvent':
		case 'WHITEBOARD/ClearPageEvent':
		case 'WHITEBOARD/ModifyTextEvent':
			// Ignore These?
			break;
		default:
			radix::dump($bbe);
		 	die("Cannot Handle Event in BBB_Meeting");
		}

		$key = $bbe->getKey();
		$this->_evt_list[ $key ] = $bbe;

    }

    /**
		Returns the List of Users
    */
    function getUsers()
    {
    	if (empty($this->_usr_list)) {
			$this->getEvents();
		}
		return $this->_usr_list;
    }

    function getVideos()
    {
    	if (empty($this->_vid_list)) {
    		$this->getEvents();
    	}

		return $this->_vid_list;

    }

    /**
		@param $vid Video ID
		@param $arg Array of Keys => Values to Set on this Video
    */
    private function _vidSet($vid, $arg)
    {
		if (empty($this->_vid_list[$vid])) {
			$this->_vid_list[$vid] = array();
		}

		$tmp = array_merge($this->_vid_list[$vid], $arg);
		ksort($tmp);
		$this->_vid_list[$vid] = $tmp;

    }

    /**
		Read ID and Name from File
    */
    static function infoFromFile($f)
    {
    	$ret = array();
    	if (!is_file($f)) return false;

		if ($fh = fopen($f, 'r')) {
			$buf = fread($fh, 256);
			if (preg_match('/meeting_id="([^"]+)"/', $buf, $m)) {
				$ret['id'] = $m[1];
			}
			if (preg_match('/meetingId="([^"]+)"/', $buf, $m)) {
				$ret['code'] = $m[1];
			}
			if (preg_match('/meetingName="([^"]+)"/', $buf, $m)) {
				$ret['name'] = $m[1];
			}
			fclose($fh);
		}

		if (empty($ret['id']) && empty($ret['code'])) $ret = false;

		return $ret;
    }

    function playURI()
    {
        return '/playback/presentation/playback.html?meetingId=' . $this->id;
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
			$wipe_list[] = sprintf('%s/recording/process/%s/%s',BBB::BASE,$type,$this->id);
			$wipe_list[] = sprintf('%s/recording/publish/%s/%s',BBB::BASE,$type,$this->id);
			$wipe_list[] = sprintf('%s/processed/%s/%s',BBB::STATUS,$this->id,$type);
			$wipe_list[] = sprintf('%s/unpublished/%s/%s',BBB::BASE,$type,$this->id);
			$wipe_list[] = sprintf('%s/published/%s/%s',BBB::BASE,$type,$this->id);
			$wipe_list[] = sprintf('%s/recording/status/processed/%s-%s.done',BBB::BASE,$this->id,$type);
		}

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
            'audio' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->id}/audio/*"),
            'video' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->id}/video/*"),
            'slide' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->id}/presentation/*/*"),
            'share' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->id}/deskshare/*"),
            'event' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->id}/events.xml"),
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

            $file = sprintf('%s/%s/%s.done',BBB::STATUS,$k,$this->id);
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
        // echo '<pre>' . print_r(glob("/var/freeswitch/meetings/{$this->id}-*"),true) . '</pre>';
        //
        // echo '<h3><i class="icon-facetime-video"></i> Raw Video</h3>';
        // echo '<pre>' . print_r(glob("/usr/share/red5/webapps/video/streams/{$this->id}"),true) . '</pre>';
        //
        // echo '<h3> Raw Presentation Slides</h3>';
        // echo '<pre>' . print_r(glob("/var/bigbluebutton/{$this->id}/{$this->id}/*"),true) . '</pre>';

        // echo '<h3> Desk Share</h3>';
        // echo '<pre>' . print_r(glob("var/bigbluebutton/deskshare/{$this->id}"),true) . '</pre>';

        $ret = array(
            'audio' => glob(BBB::RAW_AUDIO_SOURCE . "/{$this->id}-*"),
            'video' => glob(BBB::RAW_VIDEO_SOURCE . "/{$this->id}/*"),
            'slide' => glob(BBB::RAW_SLIDE_SOURCE . "/{$this->id}/{$this->id}/*/*"),
            'share' => glob(BBB::RAW_SHARE_SOURCE . "/{$this->id}"),
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
		$mid = $this->id;
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

		// Redis
		$pre = substr($mid,0, 40);
		$res = bbd::$r->keys("*{$pre}*");
		foreach ($res as $key) {
			$ret.= "redis::delete($key)\n";
			bbd::$r->del($key);
		}
		$res = bbd::$r->keys("meeting:info:{$pre}*");
		foreach ($res as $key) {
			$ret.= "redis::delete($key)\n";
			// $rec = bbd::$r->del($key);
		}
		$res = bbd::$r->keys("recording:{$pre}*");
		foreach ($res as $key) {
			$ret.= "redis::delete($key)\n";
			// $rec = bbd::$r->del($key);
		}
		// $keys = bbd::$r->keys("*{$m}*");

		return $ret;
	}
}
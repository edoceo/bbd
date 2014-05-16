<?php
/**
	@file
	@brief Represents an Event in BBB

*/

class BBB_Event implements ArrayAccess
{
	private $_d; // Data

	/**
		Parse and Load the Event
	*/
	public function __construct($x)
	{
		if (is_array($x)) {

			if (empty($x['event']) && !empty($x['eventName'])) {
				$x['event'] = $x['eventName'];
				unset($x['eventName']);
			}
			if (empty($x['time']) && !empty($x['timestamp'])) {
				$x['time'] = $x['timestamp'];
				unset($x['timestamp']);
			}

			$this->_d = array(
				'time'    => $x['time'], // New
				'timestamp' => $x['time'], // Old
				'module'  => $x['module'],
				'event'   => $x['event'],
			);
			$esr = $x['module'] . '/' . $x['event'];
			unset($x['time']);
			unset($x['module']);
			unset($x['event']);


			// Event Specific Reader
			switch ($esr) {
			case 'PARTICIPANT/AssignPresenterEvent':
				// Re-Map this field
				$x['userId'] = $x['userid'];
				unset($x['userid']);
				break;
			case 'WEBCAM/StartWebcamShareEvent':
				// $this->_d['stream'] = $x['stream'];
				if (preg_match('/(\w+)\-(\d+)$/', $x['stream'], $m)) {
					$x['userId'] = $m[1];
				}
				break;
			case 'WEBCAM/StopWebcamShareEvent':
				// $this->_d['stream'] = $x['stream'];
				// $this->_d['duration'] = $x['duration'];
				if (preg_match('/(\w+)\-(\d+)$/', $x['stream'], $m)) {
					$x['userId'] = $m[1];
				}
				// @todo expand map as below
				// 'name' => strval($x->name),
				// 'status' => strval($x->status),
				// 'filename' => strval($x->filename),
				// 'stream' => strval($x->stream), // WEBCAM/StartWebcamShareEvent+StopWebcamShareEvent
				// 'bridge' => strval($x->bridge), // Voice/StartRecordingEvent+StopRecordingEvent
				// 'participant' => strval($x->participant),
				// 'callername' => strval($x->callername),
				// 'callernumber' => strval($x->callernumber),
				// 'muted' => strval($x->muted),
				// 'talking' => strval($x->talking),
				// 'locked' => strval($x->locked),
				break;
			}

			// Copy the Rest
			foreach ($x as $k=>$v) {
				$this->_d[$k] = $v;
			}

		} elseif (is_object($x)) {
			// Map from XML Object from Events table

			// $x = (array)$x;
			// die(print_r($x));

			$this->_d = array(
				'time' => intval($x['timestamp']),
				'timestamp' => intval($x['timestamp']),
				'module' => strval($x['module']),
				'event' => strval($x['eventname']),
				'userId' => strval($x->userId),
				'role' => strval($x->role),
				'name' => strval($x->name),
				'status' => strval($x->status),
				'filename' => strval($x->filename),
				'stream' => strval($x->stream), // WEBCAM/StartWebcamShareEvent+StopWebcamShareEvent
				'bridge' => strval($x->bridge), // Voice/StartRecordingEvent+StopRecordingEvent
				'participant' => strval($x->participant),
				'callername' => strval($x->callername),
				'callernumber' => strval($x->callernumber),
				'muted' => strval($x->muted),
				'talking' => strval($x->talking),
				'locked' => strval($x->locked),
			);
		}

		// Promote Name
		if (!empty($this->_d['userId'])) {
			$this->_d['user_id'] = $this->_d['userId'];
		}


		$this->_d['source'] = print_r($x, true);
	}

	/**
		Outputs a Fixed Field String
	*/
	public function __toString()
	{
		return $this->toString(false);
	}

	public function getKey()
	{
		$key = md5(serialize(array(
			'm' => $this->_d['module'],
			'e' => $this->_d['event'],
			't' => $this->_d['time'],
		)));

		return $key;
	}

	public function toArray()
	{
		return $this->_d;
	}

	public function toString($full=false)
	{

		$ret = '';

		$ret.= sprintf('%-16s',$this->_d['module']);
		$ret.= sprintf('%-32s',$this->_d['event']);

		switch ($this->_d['module'] . '/' . $this->_d['event']) {
		case 'CHAT/PublicChatEvent':
			$full = true;
			break;
        case 'PARTICIPANT/AssignPresenterEvent':
        	$ret.= $this->_d['name'] . '/' . $this->_d['user_id'];
        	$full = true;
            // echo 'Now: ' . strval($e->status) . '=' . strval($e->value);
            break;
        case 'PARTICIPANT/ParticipantJoinEvent':
        	// $full = true;
            $ret.= $this->_d['name'] . '/' . $this->_d['user_id'] . ' as ' . $this->_d['role'] . '; Status:' . strval($this->_d['status']);
            // self::$user_list[ strval($e->userId) ] = array(
            //     'name' => strval($e->name),
            // );
            break;
        case 'PARTICIPANT/ParticipantStatusChangeEvent':
            $ret.= 'Now: ' . strval($this->_d['status']) . '=' . strval($this->_d['value']);
            break;
        case 'PARTICIPANT/ParticipantLeftEvent':
        	$ret.= $this->_d['name'] . '/' . $this->_d['user_id'];
        	// $ret.= preg_replace('/\s+/', ' ', print_r($this->_d, true));
            break;
        case 'PARTICIPANT/EndAndKickAllEvent':
            // Ignore
            $ret.= preg_replace('/\s+/', ' ', print_r($this->_d['source'], true));
            break;
        case 'PRESENTATION/ConversionCompletedEvent':
        	$ret.= preg_replace('/\s+/', ' ', print_r($this->_d['source'], true));
        	break;
        case 'PRESENTATION/GenerateSlideEvent':
        	$ret.= preg_replace('/\s+/', ' ', print_r($this->_d['source'], true));
        	break;
        case 'PRESENTATION/GotoSlideEvent':
			$ret.= sprintf('Slide: #%d', $this->_d['slide']);
        	break;
        case 'PRESENTATION/ResizeAndMoveSlideEvent':
        	$ret.= sprintf('X:%0.1f, Y:%0.1f', $this->_d['xOffset'], $this->_d['yOffset']);
        	$ret.= sprintf('H:%0.1f%%, W:%0.1f%%', $this->_d['heightRatio'], $this->_d['widthRatio']);
        	break;
        case 'PRESENTATION/SharePresentationEvent':
        	// $full = true;
        	$ret.= 'Presentation: ' . $this->_d['presentationName'] . '; Sharing:' . $this->_d['share'];
        	break;
        case 'VOICE/ParticipantJoinedEvent':
        	$full = true;
            $ret.= strval($this->_d['bridge']) . '/' . strval($this->_d['participant']) . '/' . strval($this->_d['callername']) . '; Muted: ' . strval($e->muted);
            // $uid = substr($e->callername,0,12);
            // self::$user_list[$uid]['call'] = intval($e->participant);
            break;
        case 'VOICE/ParticipantTalkingEvent':
        	$ret.= $this->_d['user_name'] . '/' . $this->_d['user_id'];
        	$ret.= '; ';
            $ret.= $this->_d['bridge'] . '/' . $this->_d['participant'];
            break;
        case 'VOICE/ParticipantLeftEvent':
            $ret.= strval($this->_d['bridge']) . '/' . strval($this->_d['participant']);
            // echo self::$user_list[ strval($e->userId) ]['name'];
            // break;
            // foreach (self::$user_list as $k=>$v) {
            //     if (intval($v['call']) == intval($e->participant)) {
            //         $ret.= "; User: {$v['name']}";
            //     }
            // }
			//        echo strval($e->bridge) . '/' . strval($e->participant);
			//        foreach (self::$user_list as $k=>$v) {
			//            if (intval($v['call']) == intval($e->participant)) {
			//                echo "; User: {$v['name']}";
			//            }
			//        }
            break;
        case 'VOICE/ParticipantMutedEvent':
			// $full = true;
			$ret.= $this->_d['name'] . '/' . $this->_d['user_id'] . '/' . $this->_d['participant'] . '; ';
			$ret.= 'Muted:' . $this->_d['muted'];
        	// print_r($this->_d);
        	// $x = $this->_d['detail'];
        	// print_r($x['detail']);
            // $ret.= strval($this->_d['detail']['bridge']) . '/' . $this->_d['detail']['participant'] . '; Muted: ' . $this->_d['detail']['muted'];
            // // echo strval(['bridge']);
            // die("ret:$ret");
            // die(print_r($this->_d['detail']));
            // foreach (self::$user_list as $k=>$v) {
            //     if (intval($v['call']) == intval($e->participant)) {
            //         $ret.= "; User: {$v['name']}";
            //     }
            // }
            break;
        case 'VOICE/StartRecordingEvent':
        case 'VOICE/StopRecordingEvent':
            $ret.= strval($this->_d['bridge']) . '; File: ' . strval($this->_d['filename']);
            break;
        case 'WEBCAM/StartWebcamShareEvent':
        case 'WEBCAM/StopWebcamShareEvent':
			$ret.= 'Stream: ' . strval($this->_d['stream']);
			break;
		case 'WHITEBOARD/AddShapeEvent':
		case 'WHITEBOARD/ClearPageEvent':
		case 'WHITEBOARD/ModifyTextEvent':
			$full = true;
			break;
        default:
            echo "Event Not Handled: {$this->_d['event']}\n";
            radix::dump($this);
        }

        // $ret.= preg_replace('/\s+/', ' ', print_r($this->_d['detail'], true));
        if ($full) {
        	$x = $this->_d;
        	unset($x['time']);
        	unset($x['timestamp']);
        	unset($x['time_offset_ms']);
        	unset($x['module']);
        	unset($x['event']);
        	ksort($x);
			$ret.= ' - Full:' . preg_replace('/\s+/', ' ', print_r($x, true));
        }

		return $ret;
	}

	/**
		ArrayAccess Interface
	*/
	public function offsetExists($o) { return isset($this->_d[$o]); }
	public function offsetGet($o) { return($this->_d[$o]); }
	public function offsetSet($o, $v) { $this->_d[$o] = $v; }
	public function offsetUnset($o) { unset($this->_d[$o]); }

}

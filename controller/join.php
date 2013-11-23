<?php
/**

*/

$mid = trim($_GET['m']);

$res = BBB::listMeetings(true);
if (!empty($res->meetings)) {
    foreach ($res->meetings as $m) {

    	if ('false' == strval($m->meeting->running)) {
    		continue;
    	}

    	// By ID
    	if ($mid == $m->meeting->meetingID) {
    		$apw = strval($m->meeting->attendeePW);
			$res = BBB::joinMeeting($mid, 'mTurk Worker', $apw);
			radix::redirect($res);
    	}

    	// By Name
    	if ($mid == $m->meeting->meetingName) {
    		$mid = strval($m->meeting->meetingID);
    		$apw = strval($m->meeting->attendeePW);
			$res = BBB::joinMeeting($mid, 'mTurk Worker', $apw);
			radix::redirect($res);
		}
	}
}

return 404;
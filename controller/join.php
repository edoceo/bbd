<?php
/**

*/

$mid = trim($_GET['m']);

switch ($_POST['a']) {
case 'join':

	$name = trim($_POST['name']);

	// List of Live Meetings
	$res = BBB::listMeetings(true);
	if (empty($res->meetings)) {
		die("No Meetings");
	}

	foreach ($res->meetings as $m) {

		if ('false' == strval($m->meeting->running)) {
			continue;
		}

		// By ID
		$apw = null;
		if ($mid == $m->meeting->meetingID) {
			$apw = strval($m->meeting->attendeePW);
		}

		// By Name
		if ($mid == $m->meeting->meetingName) {
			$mid = strval($m->meeting->meetingID);
			$apw = strval($m->meeting->attendeePW);
		}

		if (!empty($apw)) {
			$res = BBB::joinMeeting($mid, $name, $apw);
			radix::redirect($res);
		}

	}

	return 404;
}


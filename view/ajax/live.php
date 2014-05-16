<?php
/**
	@file
	@brief Show Info about Live Meetings
*/

if (!acl::has_access($_SESSION['uid'], 'ajax-live')) {
       radix::redirect('/');
}

// Live Meetings
$res = BBB::listMeetings(true);
switch (strval($res->messageKey)) {
case 'noMeetings':
	exit(0);
	break;
default:
	$msg = strval($res->message);
	if (!empty($msg)) {
		echo '<p class="info">BBB Message: ' . $msg . '</p>';
		radix::dump($res);
	}
	break;
}

// Show Live Meetings (if any)
if (!empty($res->meetings)) {
    echo '<h2>Live Meetings</h2>';
    echo '<table>';
    echo '<tr>';
    echo '<th>Live</th>';
    echo '<th>Meeting</th>';
    echo '</tr>';
    foreach ($res->meetings->meeting as $m) {

    	$bbm = new BBB_Meeting($m->meetingID);
    	// radix::dump($bbm);

        echo '<tr>';
        $x = 'color:#f90';
        if ('true' == strval($m->running)) $x = 'color:#0c0;';
		echo '<td style="' . $x . '" title="Running: ' . strval($m->running) . '"><i class="fa fa-users"></i></td>';

        echo '<td><a href="' . radix::link('/monitor?m=' . strval($bbm->id)) . '">' . strval($m->meetingID) . '/' . strval($m->meetingName) . '</a></td>';
        echo '<td class="time-nice">' . strftime('%Y-%m-%d %H:%M:%S',intval($m->createTime)/1000) . '</td>';
        // date_default_timezone_set($_ENV['TZ']);
        // echo '<td>' . strftime('%Y-%m-%d %H:%M:%S',intval($m->meeting->createTime)/1000) . ' ' . $_ENV['TZ'] . '</td>';
        if ('true' == strval($m->running)) {
			echo '<td><button class="exec"><a href="' . radix::link('/join?m=' . $m->meetingID) . '"><i class="fa fa-sign-in"></i> Join</a></button></td>';
			echo '<td><button class="fail"><a href="' . radix::link('/meeting/stop?m=' . $m->meetingID) . '"><i class="fa fa-eject"></i> Stop</a></button></td>';
		}
/*
radix::dump($m);
SimpleXMLElement Object
(
	[meetingID] => m339
	[meetingName] => Meeting 339
	[createTime] => 1376947162767
	[attendeePW] => 123456
	[moderatorPW] => 654321
	[hasBeenForciblyEnded] => false
	[running] => true
)
*/
        echo '</tr>';
    }
    echo '</table>';
}

// $res = bbd::$r->keys("meeting:info:*");
// foreach ($res as $key) {
// 	if (preg_match('/:([0-9a-f\-0]+)$/', $key, $m)) {
// 		$mid = $m[1];
// 		$bbm = bbd::$r->hGetAll($key);
// 		// print_r($bbm);
// 		$evt_list = bbd::$r->keys("recording:$mid:*");
// 		// print_r($evt);
// 		foreach ($evt_list as $k1) {
// 			$evt = bbd::$r->hGetAll($k1);
// 			radix::dump($evt);
// 		}
// 	}
// }

// radix::dump($res);

exit(0);
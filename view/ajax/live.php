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
        echo '<tr>';
        $x = 'color:#f90';
        if ('true' == strval($m->running)) $x = 'color:#0c0;';
		echo '<td style="' . $x . '" title="Running: ' . strval($m->running) . '"><i class="fa fa-users"></i></td>';

        echo '<td>' . strval($m->meetingID) . '/' . strval($m->meetingName) . '</td>';
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

exit(0);
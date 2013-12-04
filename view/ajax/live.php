<?php

// Live Meetings
$res = BBB::listMeetings(true);
$msg = strval($res->message);
if (!empty($msg)) {
    echo '<p class="info">BBB Message: ' . $msg . '</p>';
    radix::dump($res);
}

// Show Live Meetings (if any)
if (!empty($res->meetings)) {
    echo '<h2>Live Meetings</h2>';
    echo '<table>';
    echo '<tr>';
    echo '<th>Live</th>';
    echo '<th>Meeting</th>';
    echo '</tr>';
    foreach ($res->meetings as $m) {
        echo '<tr>';
        echo '<td>' . strval($m->meeting->running) . '</td>';
        echo '<td>' . strval($m->meeting->meetingID) . '/' . strval($m->meeting->meetingName) . '</td>';
        echo '<td>' . strftime('%a %Y-%m-%d %H:%M:%S',intval($m->meeting->createTime)/1000) . ' UTC</td>';
        date_default_timezone_set($_ENV['TZ']);
        echo '<td>' . strftime('%a %Y-%m-%d %H:%M:%S',intval($m->meeting->createTime)/1000) . ' ' . $_ENV['TZ'] . '</td>';
        echo '<td><button class="exec"><a href="' . radix::link('/join?m=' . $m->meeting->meetingID . '&r=p') . '">Participate</a></button></td>';
        echo '<td><button class="warn"><a href="' . radix::link('/join?m=' . $m->meeting->meetingID . '&r=m') . '">Moderator</a></button></td>';
/*
radix::dump($m->meeting);
SimpleXMLElement Object
(
    [meeting] => SimpleXMLElement Object
        (
            [meetingID] => m339
            [meetingName] => Meeting 339
            [createTime] => 1376947162767
            [attendeePW] => 123456
            [moderatorPW] => 654321
            [hasBeenForciblyEnded] => false
            [running] => true
        )

)
*/
        echo '</tr>';
    }
    echo '</table>';

}

exit(0);
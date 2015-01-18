<?php
/**
	Draws a nice PRE of Events
*/

// $time_alpha = $time_omega = null;
// $file = BBB::RAW_ARCHIVE_PATH . "/{$mid}/events.xml";
// $xml = simplexml_load_file($file);
// foreach ($xml->event as $e) {

if (!acl::has_access($_SESSION['uid'], 'view-meeting')) {
       radix::redirect('/');
}

$mid = $_GET['m'];

try {
	$bbm = new BBB_Meeting($mid);
} catch (Exception $e) {
	echo '<p class="fail">Unable to load meeting: ' . $mid . '</p>';
	echo '<p class="info">' . $e->getMessage() . '</p>';
	exit(0);
}

// User Data
$usr_list = $bbm->getUsers();
echo '<div>';
foreach ($usr_list as $k=>$u) {
	echo '<button class="user-pick" data-id="' . $k . '">' . $u['name'] . '</button>';
}
echo '</div>';

// Event Data
$evt_list = $bbm->getEvents();

echo '<pre>';
foreach ($evt_list as $e) {

    $time = floor($e['timestamp'] / 1000);
    $time_omega = $e['timestamp'];

    // Skip List
    switch ($e['module'] . '/' . $e['event']) {
    case 'VOICE/ParticipantTalkingEvent':
    case 'PRESENTATION/CursorMoveEvent':
    case 'PRESENTATION/ResizeAndMoveSlideEvent':
        continue 2;
    }

    $x = array('event-line');
    if (!empty($e['user_id'])) $x[] = 'user-' . $e['user_id'];
    if (!empty($e['module'])) $x[] = 'module-' . $e['module'];
    if (!empty($e['event'])) $x[] = 'event-' . $e['event'];
    echo '<span class="' . implode(' ',$x) . '">';

    // Friendly Format of Time
    if (null == $time_alpha) {
        $time_alpha = $e['timestamp'];
        echo strftime('%H:%M:%S',$time) . '.' . sprintf('%03d',$e['timestamp'] - ($time * 1000));
    } else {
        $s = ($e['timestamp'] - $time_alpha) / 1000;
        $m = floor($s / 60);
        $s = $s - ($m * 60);
        // echo '+' . sprintf('% 4d:%06.3f',$m,$s);
        echo '<span class="time-hint" data-ts="' . intval((($e['timestamp'] - $time_alpha) / 1000)) . '" title="' . (($e['timestamp'] - $time_alpha) / 1000) . '">+' . sprintf('% 4d:%06.3f',$m,$s) . '</span>';
        // echo '<span class="time-hint" data-ts="' . intval((($e['timestamp'] - $time_alpha) / 1000)) . '" title="' . (($e['timestamp'] - $time_alpha) / 1000) . '">+' . sprintf('% 9.3f', ($e['timestamp'] - $time_alpha) / 1000) . '</span>';
    }

    echo ' ';

    echo $e->toString();

    echo "</span>\n";
}

echo '</pre>';

exit(0);
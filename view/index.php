<?php
/**
	@file
	@brief BigBlueDashboard Dashboard, Show Meetings, Start Meetings, Browse Meetings
*/

echo radix::block('start-form');

// Live Meetings
$res = BBB::listMeetings(true);
$msg = strval($res->message);
if (!empty($msg)) {
    echo '<p class="info">BBB Message: ' . $msg . '</p>';
    // radix::dump($res);
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
        echo '<td><button class="exec" name="join">Join</button></td>';
        // radix::dump($m->meeting);
/*
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

// @todo sort by time, the tail didgits of the name
$ml = BBB::listMeetings();

echo '<h2>' . count($ml) . ' Meetings</h2>';

echo '<table>';
echo '<tr>';
echo '<th colspan="2">Meeting</th>';
echo '<th>Date</th>';
echo '<th>Source</th>';
echo '<th>Archive</th>';
echo '<th>Published</th>';
echo '<th>Internal ID</th>';
echo '</tr>';

foreach ($ml as $mid) {

    $bbm = new BBB_Meeting($mid);
    if (empty($bbm->name)) $bbm->name = '&mdash;';

    echo '<tr>';
    echo '<td><a href="' . radix::link('/play?m=' . $mid) . '" target="_blank">' . ICON_WATCH . '</a></td>';
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $bbm->name . '</a></td>';
    echo '<td>' . $bbm->date . '</td>';

    // Sources
    $stat = $bbm->sourceStat();
    echo '<td>';
    foreach (array('audio','video','slide','share') as $k) {
        if (empty($stat[$k])) continue;
        if (!is_array($stat[$k])) continue;
        if (count($stat[$k])<=0) continue;
        switch ($k) {
        case 'audio':
            echo ICON_AUDIO . ' ';
            break;
        case 'video':
            echo ICON_VIDEO . ' ';
            break;
        case 'slide':
            echo ICON_SLIDE . ' ';
            break;
        case 'share':
            echo ICON_SHARE . ' ';
            break;
        }
    }
    echo '</td>';

    // Post Processing Data
    $x = $bbm->archiveStat();
    echo '<td>';
    foreach ($x as $k=>$v) {
        if (!is_array($v)) continue;
        if (count($v)==0) continue;
        switch ($k) {
        case 'audio':
            echo ICON_AUDIO . ' ';
            break;
        case 'video':
            echo ICON_VIDEO . ' ';
            break;
        case 'slide':
            echo ICON_SLIDE . ' ';
            break;
        case 'share':
            echo ICON_SHARE . ' ';
            break;
        case 'event':
            echo ICON_EVENT . ' ';
        }
    }
    echo '</td>';
    
    // Sanity Files
	// STATUS_DIR=$RAW_PRESENTATION_SRC/recording/status
	// DIRS="recorded archived sanity"
	// for dir in $DIRS; do
	// 	if [ -f $STATUS_DIR/$dir/$meeting.done ]; then
	// 		echo -n "X"
	// 	else 
	// 		echo -n " "
	// 	fi
	// done

	// Slide Count

	// Publishing Status
	echo '<td>';
	$type_list = BBB::listTypes();
	foreach ($type_list as $type) {
		// $stat = $bbm->processStat();

		// Processing
		$file = sprintf('%s/processed/%s-%s.done',BBB::STATUS,$mid,$type);
		if (is_file($file)) {
			echo '<i class="icon-smile" title="Processing ' . $type . ' is done"></i> ';
		} else {
			echo '<i class="icon-frown"></i> ';
		}

		// Published
		$file = sprintf('%s/published/%s/%s/metadata.xml',BBB::BASE,$type,$mid);
		if (is_file($file)) {
			echo '<i class="icon-smile" title="Publishing ' . $type . ' is done"></i> ';
		} else {
			echo '<i class="icon-frown"></i> ';
		}
	}
	echo '</td>';

    // Internal ID
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $mid . '</a></td>';

    echo '</tr>';
}
echo '</table>';

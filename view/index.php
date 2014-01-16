<?php
/**
	@file
	@brief BigBlueDashboard Dashboard, Show Meetings, Start Meetings, Browse Meetings
*/

if (!acl::has_access($_SESSION['uid'], 'index')) {
       radix::redirect('/');
}

echo radix::block('start-form');

echo '<div id="meeting-live-stat"></div>';

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
// echo '<th>Internal ID</th>';
echo '</tr>';

foreach ($ml as $mid) {

    $bbm = new BBB_Meeting($mid);

	echo '<tr>';
	echo '<td><a href="' . radix::link('/play?m=' . $mid) . '" target="_blank">' . ICON_WATCH . '</a></td>';
	echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $bbm->name . '</a></td>';
	echo '<td class="time-nice">' . strftime('%Y-%m-%d %H:%M', strtotime($bbm->date)) . '</td>';

    $icon_stat = array(
		'audio' => array('icon' => ICON_AUDIO, 'stat' => 'color:#FF0000;'),
		'video' => array('icon' => ICON_VIDEO, 'stat' => 'color:#FF0000;'),
		'slide' => array('icon' => ICON_SLIDE, 'stat' => 'color:#FF0000;'),
		'share' => array('icon' => ICON_SHARE, 'stat' => 'color:#FF0000;'),
		'event' => array('icon' => ICON_EVENT, 'stat' => 'color:#FF0000;'),
	);

    // Sources
    $stat = $bbm->sourceStat();
    foreach ($stat as $k=>$v) {
        if (empty($stat[$k])) continue;
        if (!is_array($stat[$k])) continue;
        if (count($stat[$k])<=0) continue;
        switch ($k) {
		case 'audio':
			$icon_stat['audio']['stat'] = 'color:#FF7400;';
			break;
		case 'video':
			$icon_stat['video']['stat'] = 'color:#FF7400;';
			break;
		case 'slide':
			$icon_stat['slide']['stat'] = 'color:#FF7400;';
			break;
		case 'share':
			$icon_stat['share']['stat'] = 'color:#FF7400;';
			break;
        }
    }

    // Post Processing Data
    $stat = $bbm->archiveStat();
    foreach ($stat as $k=>$v) {
        if (!is_array($v)) continue;
        if (count($v)==0) continue;
        switch ($k) {
        case 'audio':
        	$icon_stat['audio']['stat'] = 'color:#5CB85C';
            break;
        case 'video':
        	$icon_stat['video']['stat'] = 'color:#5CB85C';
            break;
        case 'slide':
        	$icon_stat['slide']['stat'] = 'color:#5CB85C';
            break;
        case 'share':
        	$icon_stat['share']['stat'] = 'color:#5CB85C';
            break;
        case 'event':
        	$icon_stat['event']['stat'] = 'color:#5CB85C';
        	break;
        }
    }
    echo '<td>';
    foreach ($icon_stat as $k=>$v) {
    	echo '<span style="' . $v['stat'] . '; margin:0px 2px 0px 2px;">' . $v['icon'] . '</span>';
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
			echo '<i class="fa fa-smile-o" title="Processing ' . $type . ' is done"></i> ';
		} else {
			echo '<i class="fa fa-frown-o" style="color:#FF7400;" title="Processing Incomplete"></i> ';
		}

		// Published
		$file = sprintf('%s/published/%s/%s/metadata.xml',BBB::BASE,$type,$mid);
		if (is_file($file)) {
			echo '<i class="fa fa-smile-o" title="Publishing ' . $type . ' is done"></i> ';
		} else {
			echo '<i class="fa fa-frown-o" style="color:#f00" title="Unpublished"></i> ';
		}
	}
	echo '</td>';

    // Internal ID
    // echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $mid . '</a></td>';

    echo '</tr>';
}
echo '</table>';

?>
<script>
$(function() {
	window.setInterval(statLive, 32768);
	statLive();
});
</script>

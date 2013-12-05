<?php
/**
	@file
	@brief BigBlueDashboard Dashboard, Show Meetings, Start Meetings, Browse Meetings
*/

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
echo '<th>Internal ID</th>';
echo '</tr>';

foreach ($ml as $mid) {

    $bbm = new BBB_Meeting($mid);
    if (empty($bbm->name)) $bbm->name = '&mdash;';

    echo '<tr>';
    echo '<td><a href="' . radix::link('/play?m=' . $mid) . '" target="_blank">' . ICON_WATCH . '</a></td>';
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $bbm->name . '</a></td>';
    echo '<td class="time-nice">' . strtotime('%Y-%m-%d %H:%M', strtotime($bbm->date)) . '</td>';

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
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $mid . '</a></td>';

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

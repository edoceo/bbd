<?php

// @todo sort by time, the tail didgits of the name
$ml = BBB::meetingList();

// Internal MeetingID                                               Time                APVD APVDE RAS Slides Processed            Published           External MeetingID

echo '<h2>' . count($ml) . ' Meetings</h2>';

echo '<table>';
echo '<tr>';
echo '<th colspan="2">Meeting</th>';
echo '<th>Date</th>';
echo '<th>Source</th>';
echo '<th>Archive</th>';
echo '<th>Internal ID</th>';

echo '</tr>';
foreach ($ml as $mid) {

    $bbm = new BBB_Meeting($mid);
    if (empty($bbm->name)) $bbm->name = '&mdash;';

    echo '<tr>';
    echo '<td><a href="' . $bbm->playURI() . '" target="_blank"><i class="icon-youtube-play"></i></a></td>';
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $bbm->name . '</a></td>';
    echo '<td>' . $bbm->date . '</td>';

    // Sources
    $x = $bbm->sourceStat();
    echo '<td>';
    foreach (array('audio','video','slide','share') as $k) {
        if (!empty($x[$k])) {
            if (is_array($x[$k])) {
                if (count($x[$k])>0) {
                    switch ($k) {
                    case 'audio':
                        echo '<i class="icon-bullhorn" title="Audio Files"></i> ';
                        break;
                    case 'video':
                        echo '<i class="icon-film" title="Video Files"></i> ';
                        break;
                    case 'slide':
                        echo '<i class="icon-picture" title="Slides"></i> ';
                        break;
                    case 'share':
                        echo '<i class="icon-desktop" title="Desktop Sharing"></i> ';
                        break;
                    }
                }
            }
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
            echo '<i class="icon-bullhorn" title="Audio Files"></i> ';
            break;
        case 'video':
            echo '<i class="icon-film" title="Video Files"></i> ';
            break;
        case 'slide':
            echo '<i class="icon-picture" title="Slides"></i> ';
            break;
        case 'share':
            echo '<i class="icon-desktop" title="Desktop Sharing"></i> ';
            break;
        case 'event':
            echo '<i class="icon-rocket" title="Event Details"></i> ';
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
	
// Processed
//	for type in $TYPES; do
//		if [ -f $STATUS/processed/$recording-$type.done ]; then
//			if [ ! -z "$processed" ]; then
//				processed="$processed,"
//			fi
//			processed="$processed$type"
//		fi	
//	done
//  printf "%-21s" $processed

	// Published
//	published=""
//	for type in $TYPES; do
//		if [ -f /var/bigbluebutton/published/$type/$recording/metadata.xml ]; then
//			if [ ! -z "$published" ]; then
//				published="$published,"
//			fi
//			published="$published$type"
//		fi
//	done
//    printf "%-17s" $published


    // Internal ID
    echo '<td><a href="' . radix::link('/meeting?m=' . $mid) . '">' . $mid . '</a></td>';

    echo '</tr>';
}
echo '</table>';


// 	if [ -f /var/bigbluebutton/recording/raw/$recording/events.xml ]; then
// 		echo -n "   "
// 		echo -n $(head -n 5 /var/bigbluebutton/recording/raw/$recording/events.xml | grep meetingId | sed s/.*meetingId=\"//g | sed s/\".*//g) | sed -e 's/<[^>]*>//g' -e 's/&lt;/</g' -e 's/&gt;/>/g' -e 's/&amp;/\&/g' -e 's/ \{1,\}/ /g' | tr -d '\n'
// 		if [ $WITHDESC ]; then
// 			echo -n "         "
// 			echo -n $(head -n 5 /var/bigbluebutton/recording/raw/$recording/events.xml | grep description | sed s/.*description=\"//g | sed s/\".*//g) | sed -e 's/<[^>]*>//g' -e 's/&lt;/</g' -e 's/&gt;/>/g' -e 's/&amp;/\&/g' -e 's/ \{1,\}/ /g' | tr -d '\n'
// 		fi
// 	fi

// Get Process List of tomcat User, It will be Background Commands
// ps fU tomcat6 -o "%c%a" | grep -v COMMAND | grep -v logging.properties


// if tail -n 20 /var/log/bigbluebutton/bbb-web.log | grep -q "is recorded. Process it."; then
//     echo -n "Last meeting processed (bbb-web.log): "
//     tail -n 20 /var/log/bigbluebutton/bbb-web.log | grep "is recorded. Process it." | sed "s/.*\[//g" | sed "s/\].*//g"
// fi

echo '<pre>';
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-web.log");
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-rap-worker.log");
echo '</pre>';

/**
root      7664     1  0 38815 16820   0 00:06 ?        00:00:42 /usr/bin/ruby1.9.2 /usr/bin/god -c /etc/bigbluebutton/god/god.rb -P /var/run/god.pid -l /var/log/god.log
tomcat6  23988     1  0 21474 21680   0 06:21 ?        00:00:25 ruby rap-worker.rb
tomcat6  31127 23988 14 38249 22356   0 19:11 ?        00:01:45 ruby /usr/local/bigbluebutton/core/scripts/process/presentation.rb -m c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801
*/

/*
ffmpeg -y -i /var/bigbluebutton/recording/process/presentation/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/temp/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/concatenated.mpg -loglevel fatal -v -10 -sameq /var/bigbluebutton/recording/process/presentation/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/temp/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/output.flv
*/
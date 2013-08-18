<?php

$mid = $_GET['m'];

$bbm = new BBB_Meeting($mid);

// radix::dump($mid);

echo '<h2>Playback</h2>';
echo '<p><a href="/playback/presentation/playback.html?meetingId=' . $mid . '"><i class="icon-youtube-play"></i></a></p>';


// Raw Audio:
echo '<h2>Meeting Sources</h2>';
echo '<h3><i class="icon-volume-up"></i> Raw Audio</h3>';
echo '<pre>' . print_r(glob("/var/freeswitch/meetings/$mid-*"),true) . '</pre>';

echo '<h3><i class="icon-facetime-video"></i> Raw Video</h3>';
echo '<pre>' . print_r(glob("/usr/share/red5/webapps/video/streams/$mid"),true) . '</pre>';

echo '<h3><i class="icon-file-text"></i> Raw Presentation Slides</h3>';
echo '<pre>' . print_r(glob("/var/bigbluebutton/$mid/$mid/*"),true) . '</pre>';


echo '<h3><i class="icon-desktop"></i> Desk Share</h3>';
echo '<pre>' . print_r(glob("var/bigbluebutton/deskshare/$mid"),true) . '</pre>';

$base = "/var/bigbluebutton/recording/raw/$mid";
echo '<h2>Meeting Archive</h2>';
foreach (array('audio','video','presentation','deskshare') as $chk) {
    echo '<h3>' . ucfirst($chk) . '</h3>';
    echo '<pre>' . print_r(glob("$base/$chk"),true) . '</pre>';
}
echo '<h3>Events</h3>';
echo '<pre>' . print_r(glob("$base/*.xml"),true) . '</pre>';

echo '<h2>Source Stat</h2>';
radix::dump($bbm->sourceStat());

echo '<h2>Archive Stat</h2>';
radix::dump($bbm->archiveStat());

echo '<h2>Process Stat</h2>';
radix::dump($bbm->processStat());



// 	RAW_DIR=
// 	echo -n " "
// 
// 	# Check if there area uploaded presentations 
// 	#echo "$RAW/audio"
// 	DIRS="audio presentation video deskshare"
// 	for dir in $DIRS; do
// 		if [ -d $RAW_DIR/$dir ]; then
// 			if [ "$(ls -A $RAW_DIR/$dir)" ]; then 
// 				echo -n "X"
// 			else
// 				echo -n " "
// 			fi
// 		else 
// 			echo -n " "
// 		fi
// 	done
//
//	if [ -f $RAW_DIR/events.xml ]; then
//		echo -n "X"
//	else
//		echo -n " "
//	fi
//

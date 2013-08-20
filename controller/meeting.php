<?php
/**
    Meeting Controller / Post Handler
*/

switch (strtolower($_POST['a'])) {
case 'delete':
    $mid = $_GET['m'];

    // Raw Sources
    $path_list = array(
        BBB::RAW_AUDIO_SOURCE . '/' . $mid . '*.wav', // /var/freeswitch/meetings/$MEETING_ID*.wav
        BBB::RAW_VIDEO_SOURCE . '/' . $mid, // /usr/share/red5/webapps/video/streams/$MEETING_ID
        BBB::RAW_SLIDE_SOURCE . '/' . $mid, // /var/bigbluebutton/$MEETING_ID
        BBB::RAW_SHARE_SOURCE . '/' . $mid, // /var/bigbluebutton/deskshare/$MEETING_ID*.flv
        BBB::RAW_ARCHIVE_PATH . '/' . $mid, // /var/bigbluebutton/recording/raw/$MEETING_ID*
        BBB::LOG_PATH . '/*' . $mid . '*', // var/log/bigbluebutton/*$MEETING_ID*
    );
    // Statuses
    foreach (array('archived','processed','recorded','sanity') as $k) {
        $path_list[] = BBB::STATUS . '/' . $k . '/' . $mid . '*';
    }
    // Published Stuff
    $type_list = BBB::listTypes();
    foreach ($type_list as $type) {
        $path_list[] = BBB::PUBLISHED_PATH . '/' . $type . '/' . $mid; // /var/bigbluebutton/published/$type/$MEETING_ID*
        // $path_list[] = BBB::UNPUBLISHED_PATH . '/' . $type . '/' . $mid; // /var/bigbluebutton/unpublished/$type/$MEETING_ID*
        $path_list[] = BBB::REC_PROCESS . '/' . $type . '/' . $mid; // /var/bigbluebutton/recording/process/$type/$MEETING_ID*
        $path_list[] = BBB::REC_PUBLISH . '/' . $type . '/' . $mid; // /var/bigbluebutton/recording/publish/$type/$MEETING_ID*
        $path_list[] = BBB::LOG_PATH . '/' . $type . '/*' . $mid . '.log'; // /var/log/bigbluebutton/$type/*$MEETING_ID*
    }

    echo '<pre>';
    foreach ($path_list as $path) {
        $cmd = "rm -frv $path 2>&1";
        // echo "$cmd\n";
        echo shell_exec($cmd);
    }
    echo '</pre>';
    echo '<div class="flash"><div class="warn">Meeting: ' . $mid . ' has been purged</div></div>';

    break;
case 'rebuild':
    $bbm = new BBB_Meeting($_GET['m']);
    $buf = $bbm->rebuild();
    radix::trace($buf);
    break;
}

/*
TYPES=$(cd /usr/local/bigbluebutton/core/scripts/process; ls *.rb | sed s/.rb//g)
$type_list = BBB::listTypes();

mark_for_rebuild() {
	MEETING_ID=$1
	#set -x
	for type in $TYPES; do
                if [ -d $BASE/process/$type/$MEETING_ID ]; then
                        rm -rf $BASE/process/$type/$MEETING_ID
#                else
#                        echo "Warn: Didn't find $BASE/process/$type/$MEETING_ID"
#                        exit 1
                fi

                if [ -f $STATUS/processed/$MEETING_ID-$type.done ]; then
                        rm $STATUS/processed/$MEETING_ID-$type.done
#                else
#                        echo "Warn: Didn't find $STATUS/processed/$MEETING_ID-$type.done"
#                        exit 1
                fi

                if [ -d $BASE/publish/$type/$MEETING_ID ]; then
			rm -rf $BASE/publish/$type/$MEETING_ID
		fi

                if [ -d /var/bigbluebutton/processed/$type/$MEETING_ID ]; then
                        rm -rf /var/bigbluebutton/published/$type/$MEETING_ID
                fi

                if [ -d /var/bigbluebutton/published/$type/$MEETING_ID ]; then
                        rm -rf /var/bigbluebutton/published/$type/$MEETING_ID
                fi

                if [ -d /var/bigbluebutton/unpublished/$type/$MEETING_ID ]; then
                        rm -rf /var/bigbluebutton/unpublished/$type/$MEETING_ID
                fi
	done
}

mark_for_republish() {
	MEETING_ID=$1
	#set -x
	for type in $TYPES; do
                if [ -d $BASE/publish/$type/$MEETING_ID ]; then
			rm -rf $BASE/publish/$type/$MEETING_ID
		fi

                if [ -d /var/bigbluebutton/published/$type/$MEETING_ID ]; then
                        rm -rf /var/bigbluebutton/published/$type/$MEETING_ID
                fi

                if [ -d /var/bigbluebutton/unpublished/$type/$MEETING_ID ]; then
                        rm -rf /var/bigbluebutton/unpublished/$type/$MEETING_ID
                fi
	done
}

*/
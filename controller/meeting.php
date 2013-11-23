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

case 'download':
	$mid = $_GET['m'];
	radix::redirect('/download', array('m' => $mid, 'f'=> 'tgz'));
	break;

// Instruct the Ruby Processor to Rebuild
case 'rebuild':
    $bbm = new BBB_Meeting($_GET['m']);
    $buf = $bbm->rebuild();
    if (!empty($buf)) {
		die("Error Deleting Meeting:<pre>$buf</pre>");
    }
    break;

// Start a Meeting
case 'start':
	$name = trim($_POST['m']);

	$mpw = trim($_POST['mpw']);
	if (empty($mpw)) {
		$mpw = 'mm1234';
		//radix_session::flash('warn',"A default Moderator password of '$mpw' was assigned");
	}

	$apw = trim($_POST['apw']);
	if (empty($apw)) {
		$apw = '';
		//radix_session::flash('warn',"No Attendee password was assigned");
	}

	$rec = ('on' == $_POST['rec']);

	$id = sprintf('%08x',crc32($name.$mpw.$apw.$rec));

	$res = BBB::openMeeting($id,$name,$mpw,$apw,$rec);
	if ('SUCCESS' == strval($res->returncode)) {
		$res = BBB::joinMeeting($id,'Administrator',$mpw);
		radix::redirect($res);
	} else {
		// <response><returncode>FAILED</returncode><messageKey>idNotUnique</messageKey><message>A meeting already exists with that meeting ID.  Please use a different meeting ID.</message></response>
		if ('idNotUnique' == strval($res->messageKey)) {
			$res = BBB::joinMeeting($id,'Administrator',$mpw);
			radix::redirect($res);
		}
	}
	radix::dump($res);
	radix::trace($_POST);

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
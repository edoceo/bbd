<?php
/**

*/

switch (strtolower($_POST['a'])) {
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
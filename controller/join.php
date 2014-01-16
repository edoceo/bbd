<?php
/**

*/

$mid = trim($_GET['m']);

switch ($_POST['a']) {
case 'init':

	radix::dump($_POST);

	$name = trim($_POST['meeting_name']);

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
	// $res = BBB::openMeeting($id,$name, array(
	// 	'moderatorPW' => $mpw,
	// 	'attendeePW' => $apw,
	// 	'record' => $rec,
	// ));
	// radix::dump($res);

	$good = false;
	if ('SUCCESS' == strval($res->returncode)) {
		$good = true;
	} else {
		// <response><returncode>FAILED</returncode><messageKey>idNotUnique</messageKey><message>A meeting already exists with that meeting ID.  Please use a different meeting ID.</message></response>
		if ('idNotUnique' == strval($res->messageKey)) {
			// $res = BBB::joinMeeting($id,'Administrator',$mpw);
			$good = true;
		}
	}

	if ($good == true) {

		$res = BBB::setConfig($id, $_POST['config_xml']);
		// radix::dump($res);

		// &configToken='.urlencode($configToken)
		$uri = BBB::joinMeeting($id, $_POST['user_name'], $mpw, array('configToken' => $res->configToken));
		// radix::redirect($uri);
	}

	// Could be clever and Ping this URI before redircting, if there is an XML response it's closed

	die("Your Meeting is available at <a href='$uri'>$uri</a>");

	break;
case 'join':

	// radix::dump($_POST);
	// exit;

	$name = trim($_POST['name']);

	// List of Live Meetings
	$res = BBB::listMeetings(true);
	if (empty($res->meetings)) {
		die("No Meetings");
	}

	foreach ($res->meetings as $m) {

		if ('false' == strval($m->meeting->running)) {
			continue;
		}

		// By ID
		$apw = null;
		if ($mid == $m->meeting->meetingID) {
			$apw = strval($m->meeting->attendeePW);
		}

		// By Name
		if ($mid == $m->meeting->meetingName) {
			$mid = strval($m->meeting->meetingID);
			$apw = strval($m->meeting->attendeePW);
		}

		if (!empty($apw)) {
			$res = BBB::joinMeeting($mid, $name, $apw);
			radix::redirect($res);
		}

	}

	return 404;
}

$this->config_xml = BBB::getConfig();
$this->layout_xml = file_get_contents('/var/www/bigbluebutton/client/conf/layout.xml');


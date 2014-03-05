<?php
/**
	@file
	@brief API for Meeting

	@param $_GET['id'] find a meeting with that name or ID

*/

if (empty($_GET['id'])) $_GET['id'] = $_GET['q'];

header('Content-Type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
case 'DELETE':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-delete')) {
		exit_403();
	}

	$bbm = new BBB_Meeting($_GET['id']);
	$res = $bbm->wipe();

	header('HTTP/1.1 200 OK', true, 200);
	die(json_encode(array(
		'status' => 'success',
		'detail' => $res,
	)));
	break;
case 'POST':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-create')) {
		exit_403();
	}

	$m = array(
		'meetingID' => $_POST['id'],
		'name' => $_POST['name'],
		'moderatorPW' => null, /* @todo make if empty, return */
		'attendeePW' => null, /* @todo make if empty, return */
		'welcome' => null,
		'dialNumber' => $_POST['phone'],
		'voiceBridge' => null, /* Pass 7\d{4} pattern */
		'webVoice' => null, /* Code for Web Participants */
		'logoutURL' => null, /* */
		'record' => 'false', /* or 'true' */
		'duration' => 0,
		'meta_XXX' => null,
	);

	// Read Parameters as JSON
	// @see https://code.google.com/p/bigbluebutton/wiki/API#create
	// BBB::openMeeting($m);

	// Create a Meeting with a Name
	header('HTTP/1.1 201 Created', true, 201);
	die(json_encode(array(
		'status' => 'success',
		'detail' => $m,
	)));

	break;
case 'GET':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-select')) {
		exit_403();
	}

	if ('live' == $_GET['status']) {
		_list_meetings_exit();
	}

	$res = big_meeting_list();

	// Find Specific One
	if (!empty($_GET['id'])) {
		_info_meeting_exit($res);
		header('HTTP/1.1 404 Not Found');
		die(json_encode(array(
			'status' => 'failure',
			'detail' => 'Could not find meeting with ID or Code: ' . $_GET['id'],
		)));
	}

	die(json_encode($res));

	break;
}

header('HTTP/1.1 400 Bad Reqeust');

exit(0);

function big_meeting_list()
{
	// List of Meetings

	$ret = array();
	$res = BBB::listMeetings();
	foreach ($res as $rec) {
		$ret[ $rec ] = array(
			'id' => $rec,
		);
		$f = "/var/bigbluebutton/recording/raw/$rec/events.xml";
		if (is_file($f)) {
			// 	$xml = simplexml_load_file($f);
			// 	$ret[ $rec ]['code'] = strval($xml->metadata['meetingId']);
			// 	$ret[ $rec ]['name'] = strval($xml->metadata['meetingName']);
			if ($fh = fopen($f, 'r')) {
				$buf = fread($fh, 256);
				if (preg_match('/meetingId="([^"]+)"/', $buf, $m)) {
					$ret[$rec]['code'] = $m[1];
				}
				if (preg_match('/meetingName="([^"]+)"/', $buf, $m)) {
					$ret[$rec]['name'] = $m[1];
				}
				fclose($fh);
			}
		}
	}

	return $ret;
}

/**
	The Denied
*/
function exit_403()
{
	header('HTTP/1.1 403 Forbidden', true, 403);
	die(json_encode(array(
		'status' => 'failure',
		'detail' => 'Forbidden',
	)));
}

function _info_meeting_exit($res)
{
	foreach ($res as $rec) {
		// If they passed as ID this wll match
		if ($rec['id'] == $_GET['id']) {
			$bbm = new BBB_Meeting($rec['id']);
			break;
			//die(json_encode($rec));
		}
		// If they passed the short name it will work
		if ($rec['code'] == $_GET['id']) {
			$bbm = new BBB_Meeting($rec['id']);
			break;
			// die(json_encode($rec));
		}
	}

	$ret = array(
		'id' => $rec['id'],
		'play' => '/playback/presentation/playback.html?meetingId=' . $rec['id'],
		'code' => $rec['code'],
		'name' => $rec['name'],
		'stat' => $bbm->stat(),
		// 'event' => $bbm->getEvents(),
	);
	// print_r($ret);
	die(json_encode($ret));
	exit(0);
}

function _list_meetings_exit()
{
	$res = BBB::listMeetings(true);
	switch (strval($res->messageKey)) {
	case 'noMeetings':
		header('HTTP/1.1 404 Not Found', true, 404);
		die(json_encode(array(
			'status' => 'failure',
			'detail' => 'No Live Meetings Found',
		)));
		break;
	default:
		$msg = strval($res->message);
		// if (!empty($msg)) {
		// 	// echo '<p class="info">BBB Message: ' . $msg . '</p>';
		// 	// radix::dump($res);
		// }
		break;
	}

	// Show Live Meetings (if any)
	$ret = array(
		'status' => 'success',
		'detail' => array(
			'info' => strval($res->message),
			'list' => array(),
		),
	);

	if (!empty($res->meetings)) {
		foreach ($res->meetings as $x) {
			$m = array(
				'id' => strval($m->meeting->meetingID),
				'live' => strval($m->meeting->running),
				'name' => strval($m->meeting->meetingName),
				'time' => intval($m->meeting->createTime) / 1000,
			);
			$ret['detail']['list'][] = $m;
		}
	}
	/*
	[meetingID] => m339
	[meetingName] => Meeting 339
	[createTime] => 1376947162767
	[attendeePW] => 123456
	[moderatorPW] => 654321
	[hasBeenForciblyEnded] => false
	[running] => true
	*/

	die(json_encode($ret));
}

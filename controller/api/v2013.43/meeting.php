<?php
/**
	@file
	@brief API for Meeting

	@param $_GET['id'] find a meeting with that name or ID

*/

header('Content-Type: application/json');

if (empty($_GET['id'])) $_GET['id'] = $_GET['q'];

switch ($_SERVER['REQUEST_METHOD']) {
case 'DELETE':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-delete')) {
		api_exit_403();
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

	if (empty($_POST['action'])) {
		$_POST['action'] = 'create';
	}

	// Creates a Meeting
	switch (strtolower($_POST['action'])) {
	case 'create':
		if (!acl::has_access($_SESSION['uid'], 'api-meeting-create')) {
			api_exit_403();
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
		// BBB::openMeeting($id,$name,$mpw,$apw,$rec=true)

		// Create a Meeting with a Name
		header('HTTP/1.1 201 Created', true, 201);
		die(json_encode(array(
			'status' => 'success',
			'detail' => $m,
		)));

		break;

	case 'rebuild':
		//
		if (!acl::has_access($_SESSION['uid'], 'api-meeting-rebuild')) {
			api_exit_403();
		}

		// Trigger Rebuild?
	}

	break;
case 'GET':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-select')) {
		api_exit_403();
	}

	if ('live' == $_GET['status']) {
		_list_meetings_exit();
	}

	// Find Specific One
	if (!empty($_GET['id'])) {

		// $bbm = new BBB_Meeting($_GET['id']);
		// print_r($bbm);

		_info_meeting_exit($_GET['id']);
		// header('HTTP/1.1 404 Not Found');
		// die(json_encode(array(
		// 	'status' => 'failure',
		// 	'detail' => 'Could not find meeting with ID or Code: ' . $_GET['id'],
		// )));
		die('want id');
	}

	$res = big_meeting_list();
	die(json_encode($res));

	break;
}

header('HTTP/1.1 400 Bad Reqeust');

exit(0);

function big_meeting_list()
{
	// List of Meetings
	$ret = array();

	// Live Meetings
	$res = BBB::listMeetings(true);
	// print_r($res);
	if (!empty($res->meetings)) {
		foreach ($res->meetings as $x) {
			// print_r($x);
			$k = strval($x->meeting->meetingID);
			$m = array(
				'id' => sha1($k) . '-' . intval($x->meeting->createTime),
				'code' => $k,
				'name' => strval($x->meeting->meetingName),
				'stat' => 'live',
				'time_alpha' => floor(intval($x->meeting->createTime) / 1000),
			);
			$ret[ $k ] = $m;
		}
	}
	// print_r($ret);

	// Past Meetings
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


function _info_meeting_exit($id)
{
	// $res = big_meeting_list();
	// foreach ($res as $rec) {
	// 	// If they passed as ID this wll match
	// 	if ($rec['id'] == $id) {
	// 		$bbm = new BBB_Meeting($rec['id']);
	// 		break;
	// 		//die(json_encode($rec));
	// 	}
	// 	// If they passed the short name it will work
	// 	if ($rec['code'] == $id) {
	// 		$bbm = new BBB_Meeting($rec['id']);
	// 		break;
	// 		// die(json_encode($rec));
	// 	}
	// }
	$bbm = new BBB_Meeting($id);

	if (empty($bbm->id)) {
		api_exit_404();
	}

	$ret = array(
		'id' => $bbm->id,
		'play' => '/playback/presentation/playback.html?meetingId=' . $bbm->id,
		'code' => $bbm->code,
		'name' => $bbm->name,
		'status' => $bbm->stat,
		'attendees' => array(),
		'time_alpha' => $bbm->time_alpha,
		'time_omega' => $bbm->time_omega,
	);

	$ret['attendees'] = $bbm->getUsers();

	// Load attendees from Events List
	if (is_file(BBB::RAW_ARCHIVE_PATH . '/' . $bbm->id .'/events.xml')) {

		$ret['status'] = 'proc';

		$event_list = $bbm->getEvents();
		foreach ($event_list as $e) {
			if (empty($ret['time_alpha'])) $ret['time_alpha'] = $e['time_s'];
			$ret['time_omega'] = $e['time_s'];
		}
	}

	// If Video File then Done
	if (is_file(BBB::PUBLISHED_PATH . '/presentation/' . $bbm->id . '/video/webcams.webm')) {
		$ret['status'] = 'done';
	}

	// Not Done? Check if process is running
	if ('done' != $ret['status']) {
		$proc = false;
		$list = BBB::listProcesses();
		foreach ($list as $proc) {
			$pat = '/' . preg_quote($bbm->id) . '/';
			if (preg_match($pat, $proc['cmd'])) {
				$proc = true;
				$ret['status'] = 'proc';
			}
		}
	}

	// print_r($ret);
	$ret['stat'] = $ret['status'];
	die(json_encode($ret));
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

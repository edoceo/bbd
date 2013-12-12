<?php
/**
	@file
	@brief API for Meeting

	@param $_GET['q'] find a meeting with that name

*/

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

	// Create a Meeting with a Name
	header('HTTP/1.1 401 Created', true, 201);
	die(json_encode(array(
		'uri' => '',
	)));
	break;
case 'GET':

	if (!acl::has_access($_SESSION['uid'], 'api-meeting-select')) {
		exit_403();
	}

	$res = big_meeting_list();

	// Find Specific One
	if (!empty($_GET['q'])) {
		foreach ($res as $rec) {
			if ($rec['code'] == $_GET['q']) {
				die(json_encode($rec));
			}
		}

		header('HTTP/1.1 404 Not Found');
		exit(0);
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
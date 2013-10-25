<?php
/**
	@file
	@brief API for Meeting

	@param $_GET['q'] find a meeting with that name

*/

header('Content-Type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
case 'POST':
	// @todo 201
	header('HTTP/1.1 401 Created', 201);
	// Create a Meeting with a Name
	die(json_encode(array(
		'uri' => '',
	)));

	break;
case 'GET':
	if (!empty($_GET['q'])) {
		$res = BBB::listMeetings();
		foreach ($res as $rec) {
			// $xml = simplexml_load_file("/var/bigbluebutton/recording/raw/$rec/events.xml");
			// radix::dump($xml);
			// exit;
		}
		die(json_encode(array(
			'uri' => '',
		)));
	}

	// List of Meetings
	$ret = array();
	$res = BBB::listMeetings();
	foreach ($res as $rec) {
		$ret[ $rec ] = array(
			'id' => $rec,
		);
		$f = "/var/bigbluebutton/recording/raw/$rec/events.xml";
		if (is_file($f)) {
			$xml = simplexml_load_file($f);
			$ret[ $rec ]['code'] = strval($xml->metadata['meetingId']);
			$ret[ $rec ]['name'] = strval($xml->metadata['meetingName']);
		}
	}
	die(json_encode($ret));

	break;
}

header('HTTP/1.1 400 Bad Reqeust');

exit(0);


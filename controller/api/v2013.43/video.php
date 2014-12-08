<?php
/**
	@file
	@brief API for Meeting Video

	@param $_GET['id'] Meeting ID
	@param $_GET['uid'] Participant Name or Code

*/

$mid = $_GET['id'];
$mid = preg_match('/^([0-9a-f]+\-\d+)$/', $mid, $m) ? $m[1] : null;

header('Content-Type: application/json');

if (!is_dir("/var/bigbluebutton/published/presentation/$mid/video")) {
	header('HTTP/1.1 404 Not Found');
	header('X-DIO-Status: failure');
	header('X-DIO-Detail: Meeting Not Found');
	exit(0);
};

$want_uid = $_GET['uid'];

// Load Meeting
$bbm = new BBB_Meeting($mid);

// GET, HEAD, POST/PUT
switch ($_SERVER['REQUEST_METHOD']) {
case 'GET':

	if (empty($want_uid)) {
		$vid_file = "/var/bigbluebutton/published/presentation/$mid/video/webcams.webm";
		if (is_file($vid_file) && (filesize($vid_file) > 64)) {
			send_download($vid_file);
		}
		api_exit_404('Meeting Video Not Found');
	}

	$vid_file = "/var/bigbluebutton/published/presentation/$mid/video/$want_uid.webm";
	if (is_file($vid_file) && (filesize($vid_file) > 64)) {
		send_download($vid_file);
	}

	die(json_encode(array(
		'status' => 'success',
		'detail' => "Job is Running: $pid",
	)));

	// if (is_writable("/var/bigbluebutton/published/presentation/$mid/video")) {
	// 	rename($out, $vid_file);
	// 	$out = $vid_file;
	// }
	// echo "file:$out\n";

	break;
case 'HEAD':

	header('Content-Length: 0');

	if (empty($want_uid)) {
		$vid_file = BBB::PUB . "/$mid/video/webcams.webm";
	} else {

		// Check for Specific user
		$user_list = $bbm->getUsers();
		foreach ($user_list as $uid=>$user) {
			if (($want_uid == $user['uid']) || ($want_uid == $user['name'])) {
				$good = true;
				break;
			}
		}

		if (!$good) {
			header('HTTP/1.1 404 Not Found');
			header('X-DIO-Status: failure');
			header('X-DIO-Detail: Meeting User Not Found');
			header('X-DIO-Result: nouser');
			// header('X-DIO-Video: ' . $vid_file);
			exit(0);
		}

		if (!$bbm->hasVideo($want_uid)) {
			header('HTTP/1.1 404 Not Found');
			header('X-DIO-Status: failure');
			header('X-DIO-Detail: Video for User Not Found');
			header('X-DIO-Result: novideo');
			exit(0);
		}

		$vid_file = BBB::PUB . "/$mid/video/$want_uid.webm";
	}

	if (!is_file($vid_file)) {
		header('HTTP/1.1 206 Partial Content');
		header('X-DIO-Status: failure');
		header('X-DIO-Detail: Video Not Processed Yet');
		header('X-DIO-Result: nobuild');
		exit(0);
	}

	// 	|| (filesize($vid_file) < 128)) {
	// 	exit(0);
	// }

	// Return Data in Headers?
	header('X-DIO-Date: ' . strftime('%Y-%m-%d %H:%I:%S', filemtime($vid_file)));
	header('X-DIO-Size: ' . filesize($vid_file));
	header('X-DIO-Status: success');

	// file_put_contents($vid_file, '');

	// $event_list = $bbm->getEvents();
	// $cmd = APP_ROOT . '/bin/video-clip.php';
	// $cmd.= ' >>/tmp/' . $mid . '-video.log';
	// $cmd.= ' 2>&1';
	// $buf = shell_exec("$cmd & echo $?");
	// die(json_encode(array(
	// 	'status' => 'success',
	// 	'detail' => "Started Processing Job: $buf",
	// )));

	exit(0);

	break;
case 'OPTIONS':
	// Show Details in JSON Data?
	break;
case 'POST':
case 'PUT':

	if (empty($want_uid)) {
		die("Need to hvae the Panelist ID");
	}

	if (!$bbm->hasVideo($want_uid)) {
		// 404?
		die(json_encode(array(
			'status' => 'failure',
			'detail' => "No Video",
		)));
	}


	// Assume Command is to Create
	$good = false;
	// $video_list = $bbm->getVideos();
	// foreach ($video_list as $vid => $vxx) {
	// 	if ($uid == $vxx['name']) {
	// 		$good = true;
	// 		break;
	// 	}
	// 	if ($uid == $vxx['user']) {
	// 		$good = true;
	// 		break;
	// 	}
	// }
	$user_list = $bbm->getUsers();
	foreach ($user_list as $uid=>$user) {
		if (($want_uid == $user['uid']) || ($want_uid == $user['name'])) {
			$good = true;
			break;
		}
	}
	if (!$good) {
		// print_r($video_list);
		api_exit_404('User not found in Meeting');
	}

	$job_file = '/tmp/' . md5($mid . $want_uid) . '.lock';
	if (is_file($job_file)) {
		$age = $_SERVER['REQUEST_TIME'] - filemtime($job_file);
		if ($age >= 3600) {
			unlink($job_file);
		}
	}

	if (is_file($job_file)) {
		$pid = trim(file_get_contents($job_file));
		die(json_encode(array(
			'status' => 'success',
			'detail' => "Job Running: $pid",
		)));
	}

	// Check for and Start Processor (@warning race conditions)
	$cmd = APP_ROOT . '/bin/video-clip.php';
	$cmd.= ' ' . escapeshellarg($mid);
	$cmd.= ' ' . escapeshellarg($want_uid);
	$cmd.= ' >/tmp/' . $mid . '-video.log';
	$cmd.= ' 2>&1';
	$cmd.= ' & echo $!';

	$pid = trim(shell_exec($cmd));

	file_put_contents($job_file, $pid);

	die(json_encode(array(
		'status' => 'success',
		'detail' => "Job Started: $pid",
		// 'command' => $cmd,
	)));

}

// header('HTTP/1.1 400 Bad Request', true, 400);
die(json_encode(array(
	'status' => 'failure',
	'detail' => 'Invalid Request',
)));

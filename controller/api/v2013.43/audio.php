<?php
/**
	@file
	@brief API for Meeting

	@param $_GET['q'] find a meeting with that ID

*/

header('Content-Type: application/json');

$tmp = trim(shell_exec('mktemp -d'));
if (!is_dir($tmp)) {
	header('HTTP/1.1 500 Server Error', true, 500);
	die(json_encode(array(
		'status' => 'failure',
		'detail' => 'Unable to create working location',
	)));
}

define('TMPDIR', $tmp);
chdir(TMPDIR);
register_shutdown_function(function() {
	chdir('/');
	shell_exec('rm -fr ' . escapeshellarg(TMPDIR));
});

switch ($_SERVER['REQUEST_METHOD']) {
case 'GET':

	$mid = $_GET['id'];

	$f = "/var/bigbluebutton/recording/raw/$mid/events.xml";
	if (!is_file($f)) {
		header('HTTP/1.1 404 Not Found', true, 404);
		die(json_encode(array(
			'status' => 'failure',
			'detail' => 'Meeting not found',
		)));
	}

	$bbm = new BBB_Meeting($mid);
	$audio = array();
	$event_list = $bbm->getEvents();
	$event_alpha = $event_omega = 0;
	foreach ($event_list as $e) {
		if (empty($event_alpha)) $event_alpha = $e['time_u'];
		switch ($e['module']) {
		case 'VOICE':
			switch ($e['event']) {
			case 'StartRecordingEvent':
				$f = strval($e['source']->filename);
				if (!is_file($f)) {
					$b = basename($f);
					$f = '/var/bigbluebutton/recording/raw/' . $mid . '/audio/' . $b;
				}
				$audio['file'] = $f;
				$audio['file_basename'] = basename($f);
				$audio['time_alpha'] = $e['time_u'] - $event_alpha; // Time in ms

				// Get Length from SOX
				$buf = shell_exec('sox ' . escapeshellarg($audio['file']) . ' -n stat 2>&1');
				if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
					$audio['length_file'] = floatval($m[1]) * 1000;
				} else {
					print_r($buf);
					die("Cannot Find Length\n");
				}
				break;
			case 'StopRecordingEvent':
				$audio['time_omega'] = $e['time_u'] - $event_alpha;
			}
		}
		$event_omega = $e['time_u'];
	}
	$event_span = $event_omega - $event_alpha;

	// Generate the Audio File
	// echo "Meeting Master Time: $meeting_duration ($meeting_omega - $meeting_alpha)\n";

	$audio['length_calc'] = $audio['time_omega'] - $audio['time_alpha'];

	// echo "Audio: {$audio['file']} at +{$audio['time_alpha']} for $audio_time\n";
	// Prepare Audio File with Lead Time Silence "
	// $cmd = "sox -i {$audio['file']}";
	// shell_exec("$cmd 2>&1");
	$audio['speed'] = $audio['length_file'] / $audio['length_calc'];

	// echo 'Speed: ' .  $audio['speed'] . " file:{$audio['length_file']} / calc:{$audio['length_calc']}\n";

	// Make Leading Silence
	$cmd = 'sox -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav head.wav trim 0.000 ' . floatval($audio['time_alpha'] / 1000);
	syslog(LOG_DEBUG, $cmd);
	shell_exec("$cmd 2>&1");

	// Adjust Audio File Time and Length
	$cmd = "sox -q -m -b 16 -c 1 -e signed -r 16000 -L -n {$audio['file']} -b 16 -c 1 -e signed -r 16000 -L -t wav body.wav speed {$audio['speed']} rate -h 16000 trim 0.000 " . floatval($audio['length_calc'] / 1000);
	syslog(LOG_DEBUG, $cmd);
	shell_exec("$cmd 2>&1");

	// Duration is Audio Stop Event to End of Meeting
	$cmd = "sox -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav tail.wav trim 0.000 " . floatval(($event_span - $audio['time_omega']) / 1000);
	syslog(LOG_DEBUG, $cmd);
	shell_exec("$cmd 2>&1");

	// Info on Resulting Audio File
	// $buf = shell_exec('sox trim.wav -n stat 2>&1');
	// if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
	// 	echo "Adjusted Audio to: " . (floatval($m[1]) * 1000) . "\n";;
	// }

	// Concat
	$cmd = "sox -q head.wav body.wav tail.wav -b 16 -c 1 -e signed -r 16000 -L -t wav work.wav";
	syslog(LOG_DEBUG, $cmd);
	shell_exec("$cmd 2>&1");

	// $buf = shell_exec('sox work.wav -n stat 2>&1');
	// if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
	// 	echo "Final Audio is: " . (floatval($m[1]) * 1000) . "\n";;
	// }

	// Cache our work?
	// rename('work.wav', "/var/bigbluebutton/published/presentation/$mid/audio.wav");

	switch ($_GET['f']) {
	case 'wav':
		break;
	case 'mp3':
		// Convert
		break;
	}

	while (ob_get_level()) ob_end_clean();

	header('Content-Disposition: attachment; filename="Meeting_' . $bbm->code . '.wav"');
	header('Content-Length: ' . filesize('work.wav'));
	header('Content-Transfer-Encoding: binary');
	header('Content-Type: audio/vnd.wav');

	// Prefer senfile over
	readfile('work.wav');

	exit(0);

	break;
case 'HEAD':
	// Return Data in Headers?
	break;
case 'OPTIONS':
	// Show Details in JSON Data?
	break;
}

header('HTTP/1.1 400 Bad Request', true, 400);
die(json_encode(array(
	'status' => 'failure',
	'detail' => 'Invalid Request',
)));

<?php
/**
	@file
	@brief API for Meeting Audio

	@param $_GET['id'] find a meeting with that ID

*/

header('Content-Type: application/json');

$tmp = trim(shell_exec('mktemp -d'));
if (!is_dir($tmp)) {
	exit_500('Unable to create working location');
}

// clean up handler
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
				$b = basename($e['source']->filename);
				$f = '/var/bigbluebutton/recording/raw/' . $mid . '/audio/' . $b;
				if (!is_file($f)) {
					// The FreeSWITCH Source
					$f = strval($e['source']->filename);
				}
				$audio['file'] = $f;
				$audio['file_basename'] = basename($f);
				$audio['time_alpha'] = $e['time_u'] - $event_alpha; // Time in ms

				// Get Length from SOX
				$cmd = 'sox ' . escapeshellarg($audio['file']) . ' -n stat 2>&1';
				syslog(LOG_DEBUG, $cmd);
				$buf = shell_exec($cmd);
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
	if (empty($audio['time_omega'])) {
		$audio['time_omega'] = ($event_omega - $event_alpha);
	}

	// Generate the Audio File
	// echo "\$audio['length_calc'] = {$audio['time_omega']} - {$audio['time_alpha']}\n";
	$audio['length_calc'] = $audio['time_omega'] - $audio['time_alpha'];

	// echo "Audio: {$audio['file']} at +{$audio['time_alpha']} for $audio_time\n";
	// Prepare Audio File with Lead Time Silence "
	// $cmd = "sox -i {$audio['file']}";
	// shell_exec("$cmd 2>&1");
	$audio['speed'] = $audio['length_file'] / $audio['length_calc']; //  / $audio['length_calc'];

	// Make Leading Silence
	sox_empty(floatval($audio['time_alpha'] / 1000), 'head.wav');

	// Adjust Audio File Time and Length
	$cmd = 'sox ';
	$cmd.= ' --buffer 131072';
	// $cmd.= ' --multi-threaded';
	$cmd.= ' --no-clobber';
	$cmd.= ' --no-show-progress';
	// $cmd.= '-m'; // --combine mix
	// Input Options
	$cmd.= ' --ignore-length';
	// $cmd.= ' --bits 16'; // -b 16
	// $cmd.= ' --channels 1'; // -c 1
	// $cmd.= ' --encoding signed'; // -e signed
	// $cmd.= ' --rate 16000'; // -r 16000
	// $cmd.= ' --endian little'; // -L
	// $cmd.= ' -n'; // What is this?
	$cmd.= ' ' . escapeshellarg($audio['file']); // Input File
	// Output Options
	$cmd.= ' --bits 16';
	$cmd.= ' --channels 1';
	$cmd.= ' --encoding signed';
	$cmd.= ' --rate 16000';
	$cmd.= ' --endian little';
	$cmd.= ' --type wav';
	$cmd.= ' body.wav'; // Output File
	// Effect Options
	$cmd.= ' speed ' . sprintf('%0.16f', $audio['speed']); // See Also Stretch
	$cmd.= ' rate -h 16000'; // high, 16k
	$cmd.= ' trim 0.000 ' . floatval($audio['length_calc'] / 1000);
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");
	if (!is_file('body.wav')) {
		exit_500("Command:$cmd\nOutput:\n$buf");
	}

	// Duration is Audio Stop Event to End of Meeting
	sox_empty(floatval(($event_span - $audio['time_omega']) / 1000), 'tail.wav');

	// Info on Resulting Audio File
	// $buf = shell_exec('sox trim.wav -n stat 2>&1');
	// if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
	// 	echo "Adjusted Audio to: " . (floatval($m[1]) * 1000) . "\n";;
	// }

	// Concat
	$cmd = "sox -q head.wav body.wav tail.wav -b 16 -c 1 -e signed -r 16000 -L -t wav work.wav";
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");

	if (!is_file('work.wav')) {
		exit_500("Command:$cmd\nOutput:\n$buf");
	}

	$buf = shell_exec('sox work.wav -n stat 2>&1');
	if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
		if (floatval($m[1]) <= 0) {
			die(json_encode(array(
				'status' => 'failure',
				'detail' => 'No Audio',
			)));
		}
	}

	// Cache our work?
	$file = 'work.wav';
	if (is_writable("/var/bigbluebutton/published/presentation/$mid")) {
		$file = "/var/bigbluebutton/published/presentation/$mid/audio.wav";
		rename('work.wav', $file);
	}

	switch ($_GET['f']) {
	case 'mp3':
		// Convert
		$cmd = 'ffmpeg -i ' . escapeshellarg($file) . ' -y work.mp3';
		// $cmd.= ' ' . escapeshellarg($wav_file);
		// $cmd.= ' -metadata title="Discuss.IO Meeting ' . $id . '" '; // TIT2
		// $cmd.= ' -metadata artist="Discuss.IO" ';
		// $cmd.= ' -metadata encoder="dioenc" ';
		// $cmd.= ' ' . escapeshellarg($mp3_file);

		syslog(LOG_DEBUG, $cmd);
		$buf = shell_exec("$cmd 2>&1");
		if (!is_file('work.mp3')) {
			exit_500("Command:$cmd\nOutput:\n$buf");
		}

		if (is_writable("/var/bigbluebutton/published/presentation/$mid")) {
			$file = "/var/bigbluebutton/published/presentation/$mid/audio.mp3";
			rename('work.mp3', $file);
		}
		send_download($file);
		break;
	case 'wav':
	default:
		send_download($file);
		break;
	}

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

function exit_500($msg)
{
	header('HTTP/1.1 500 Sever Error', true, 500);
	die(json_encode(array(
		'status' => 'failure',
		'detail' => $msg,
	)));
}
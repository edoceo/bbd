<?php
/**
	@file
	@brief API for Meeting Audio

	@param $_GET['id'] find a meeting with that ID

*/

// Stretches Each File to match it's length recorded in BBB Events
$_ENV['skew-audio'] = true;

// Skews the Events Time to Match the Recorded WAV file.
$_ENV['skew-event'] = false;

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
	$mp3_file = "/var/bigbluebutton/published/presentation/$mid/audio.mp3";
	$wav_file = "/var/bigbluebutton/published/presentation/$mid/audio.wav";

	// Check my Cache
	switch ($_GET['f']) {
	case 'mp3':
		if (is_file($mp3_file) && (filesize($mp3_file) > 0)) {
			send_download($mp3_file);
		}
		break;
	case 'txt':
		break;
	case 'wav':
	default:
		if (is_file($wav_file) && (filesize($wav_file) > 0)) {
			send_download($wav_file);
		}
		break;
	}

	$f = "/var/bigbluebutton/recording/raw/$mid/events.xml";
	if (!is_file($f)) {
		header('HTTP/1.1 404 Not Found', true, 404);
		die(json_encode(array(
			'status' => 'failure',
			'detail' => 'Meeting not found',
		)));
	}

	$bbm = new BBB_Meeting($mid);
	$audio_info = array();
	$audio_list = array();
	$event_list = $bbm->getEvents();
	$event_alpha = $event_omega = 0;

	foreach ($event_list as $e) {
		if (empty($event_alpha)) $event_alpha = $e['time_offset_ms'];
		switch ($e['module'] . '/' . $e['event']) {
		case 'VOICE/StartRecordingEvent':
			// Either in:
			// /var/freeswitch/meetings/ccf55f66dbe05f5491a67fba48e215cc96df9263-1397070154614-1397070163682.wav
			// /var/bigbluebutton/recording/raw/ccf55f66dbe05f5491a67fba48e215cc96df9263-1397070154614/audio/ccf55f66dbe05f5491a67fba48e215cc96df9263-1397070154614-1397070163682.wav
			// $b = basename($e['source']->filename);
			// $f = '/var/bigbluebutton/recording/raw/' . $mid . '/audio/' . $b;
			// if (!is_file($f)) {
			// 	// The FreeSWITCH Source
			// 	$f = strval($e['source']->filename);
			// }
			// $audio_info['file'] = $f;
			// $audio_info['file_basename'] = basename($f);
			// $audio_info['time_alpha'] = $e['time_offset_ms'];
			// $audio_info['alpha'] = $e;
			$wav = strval($e['source']->filename);
			// $audio_list[$key]['file'] = strval($e['source']->filename);
			// $key = basename($e['source']->filename);
			if (preg_match('/([0-9a-f]+\-\d+)\-(\d+)\.wav$/', $wav, $m)) {
				$mid = $m[1];
				$key = $m[2];
				if (!is_file($wav)) {
					$wav = "/var/bigbluebutton/recording/raw/$mid/audio/" . basename($wav);
				}
				$audio_list[$key] = array(
					'file' => $wav,
					'time_alpha' => $e['time_offset_ms'],
				);
			}
			break;
		case 'VOICE/StopRecordingEvent':
			// $audio_info['time_omega'] = $e['time_offset_ms']; // $e['time_ms'] - $event_alpha;

			$key = basename($e['source']->filename);
			if (preg_match('/\-(\d+)\.wav$/', $key, $m)) $key = $m[1];

			$audio_list[$key]['time_omega'] = $e['time_offset_ms'];
		}

		$event_omega = $e['time_offset_ms'];

	}

	// Add End Time Where Empty
	$key_list = array_keys($audio_list);
	foreach ($key_list as $key) {
		if (empty($audio_list[$key]['time_omega'])) {
			$audio_list[$key]['time_omega'] = $event_omega;
		}
	}

	// $audio_info['span_calc'] = $event_omega;
	// $event_span = $event_omega - $event_alpha;
	// if (empty($audio_info['time_omega'])) {
	// 	$audio_info['time_omega'] = ($event_omega - $event_alpha);
	// }
	// $audio_info['span_file'] = sox_length($audio_info['file']);
	// $audio_info['meet'] = array(
	// 	'alpha' => $event_alpha,
	// 	'omega' => $event_omega,
	// 	'span' => $event_span,
	// );
	// print_r($audio_list);

	// Process Audio File Speed/Stretch/Skew
	$key_list = array_keys($audio_list);
	foreach ($key_list as $key) {

		$audio_list[$key]['span_calc'] = $audio_list[$key]['time_omega'] - $audio_list[$key]['time_alpha'];
		$audio_list[$key]['span_file'] = sox_length($audio_list[$key]['file']);
		$audio_list[$key]['skew'] = $audio_list[$key]['span_file'] / $audio_list[$key]['span_calc'];

		if ($audio_list[$key]['skew'] < 1) {
			if ($_ENV['skew-audio']) {
				// This Block Stretches the Audio of the File to Match the Event Times
				// $out = "$key.wav";
				$cmd = 'sox';
				$cmd.= ' ' . escapeshellarg($audio_list[$key]['file']);
				$cmd.= " $key.wav";
				$cmd.= ' speed ' . sprintf('%0.16f', $audio_list[$key]['skew']); // See Also Stretch
				syslog(LOG_DEBUG, $cmd);
				$buf = shell_exec($cmd);
				$audio_list[$key]['file'] = "$key.wav";
				$audio_list[$key]['span_file_2'] = sox_length($audio_list[$key]['file']);
				$audio_list[$key]['skew_2'] = $audio_list[$key]['span_file_2'] / $audio_list[$key]['span_calc'];

			} elseif ($_ENV['skew-event']) {
				// This Block Modifies the Event Data to Match the Length of the File
				$audio_list[$key]['time_omega_x'] = $audio_list[$key]['time_omega'];
				$audio_list[$key]['time_omega'] = $audio_list[$key]['time_alpha'] + $audio_list[$key]['span_file'];
			}
		}

	}

	// Sort our Audio Streams
	uasort($audio_list, function($a, $b) {
		return ($a['time_alpha'] > $b['time_alpha']);
	});

	// Generate the Audio File
	// echo "\$audio_info['span_calc'] = {$audio_info['time_omega']} - {$audio_info['time_alpha']}\n";
	// $audio_info['length_calc'] = $audio_info['time_omega'] - $audio_info['time_alpha'];
	// $audio_info['span_calc'] = $audio_info['time_omega'] - $audio_info['time_alpha'];
	// $audio_info['span_diff'] = $audio_info['span_calc'] - $audio_info['span_file'];

	// echo "Audio: {$audio_info['file']} at +{$audio_info['time_alpha']} for $audio_time\n";
	// Prepare Audio File with Lead Time Silence "
	// $cmd = "sox -i {$audio_info['file']}";
	// shell_exec("$cmd 2>&1");
	// $audio_info['span_skew'] = $audio_info['span_file'] / $audio_info['span_calc']; //  / $audio['span_calc'];

	// Make Leading Silence
	// sox_empty(floatval($audio_info['time_alpha'] / 1000), 'head.wav');

	// Attach Audio
	$cat_list = array();
	$cur_time = 0;
	foreach ($audio_list as $key=>$audio) {

		if ($audio['time_alpha'] > $cur_time) {
			$pre_time = $audio['time_alpha'] - $cur_time;
		}
		if (!empty($pre_time)) {
			$out = "pre-$key.wav";
			sox_empty($pre_time / 1000, $out);
			$cat_list[] = $out;
		}

		// Get our file
		$cat_list[] = $audio['file'];
		$cur_time = $audio['time_omega'];
	}
	if ($cur_time < $event_omega) {
		sox_empty(($event_omega - $cur_time) / 1000, 'tail.wav');
		$cat_list[] = 'tail.wav';
	}
	// $audio_info['source_list'] = $cat_list;

	// Concat
	$arg = implode(' ', $cat_list);
	$cmd = "sox -q $arg -b 16 -c 1 -e signed -r 16000 -L -t wav work.wav";
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd >>/tmp/bbd-audio.log 2>&1");

	// Check Status
	if (!is_file('work.wav')) {
		exit_500("Command:$cmd\nOutput:\n$buf");
	}
	if (filesize('work.wav') <= 4096) {
		exit_500("Command:$cmd\nOutput:\n$buf");
	}

	$audio_info['span_file'] = sox_length('work.wav');

	// $buf = shell_exec('sox work.wav -n stat 2>&1');
	// if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
	// 	if (floatval($m[1]) <= 0) {
	// 		die(json_encode(array(
	// 			'status' => 'failure',
	// 			'detail' => 'No Audio',
	// 		)));
	// 	}
	// }

	// Cache our work?
	switch ($_GET['f']) {
	case 'txt':
		// $audio_info['temp_data'] = shell_exec('ls -alh');
		if (is_file($mp3_file)) {
			$audio_info['file_mp3'] = basename($mp3_file);
		}
		if (is_file($wav_file)) {
			$audio_info['file_wav'] = basename($wav_file);
		}

		ksort($audio_info);
		print_r($audio_info);
		uasort($audio_list, function($a, $b) {
			return ($a['time_alpha'] > $b['time_alpha']);
		});
		print_r($audio_list);
		exit(0);
		break;
	case 'mp3':
		$out_file = 'work.mp3';
		// Convert
		$cmd = '/usr/local/bin/ffmpeg -i work.wav -y ' . $out_file;
		// $cmd.= ' ' . escapeshellarg($wav_file);
		// $cmd.= ' -metadata title="BBB Meeting" '; // TIT2
		// $cmd.= ' -metadata artist="BBB" ';
		// $cmd.= ' -metadata encoder="bbbenc" ';
		// $cmd.= ' ' . escapeshellarg($file);

		syslog(LOG_DEBUG, $cmd);
		$buf = shell_exec("$cmd 2>&1");
		if (!is_file($out_file)) {
			exit_500("Command:$cmd\nOutput:\n$buf");
		}

		if (is_writable("/var/bigbluebutton/published/presentation/$mid")) {
			syslog(LOG_DEBUG, "Caching Audio: $mp3_file");
			rename($out_file, $mp3_file);
			$out_file = $mp3_file;
		}
		send_download($out_file);
		break;
	case 'wav':
	default:
		$out_file = 'work.wav';
		if (is_writable("/var/bigbluebutton/published/presentation/$mid")) {
			syslog(LOG_DEBUG, "Caching Audio: $mp3_file");
			rename($out_file, $wav_file);
			$out_file = $wav_file;
		}
		send_download($out_file);
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

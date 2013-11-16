#!/usr/bin/php
<?php
/**
	We Identify Each FLV in the Stream and Overlay it Over the Audio

	@see https://code.google.com/p/bigbluebutton/issues/detail?id=900
	@see https://code.google.com/p/bigbluebutton/issues/detail?id=1516
*/

require_once(dirname(dirname(__FILE__)) . '/boot.php');

$mid = $argv[1];
$uid = $argv[2];

$bbm = new BBB_Meeting($mid);

$event_list = $bbm->getEvents();
$event_last = null;

$audio = array();
$audio_file = null;
$audio_time = null;
$video_list = array();
$want_next_webcam = false;

$tmp = trim(shell_exec('mktemp -d'));
if (!is_dir($tmp)) {
	die("Failed to create working directory ($tmp)\n");
}
define('TMP_WORK', $tmp);
chdir(TMP_WORK);

register_shutdown_function(function() {
	chdir('/');
	shell_exec('rm -fr ' . escapeshellarg(TMP_WORK));
});

foreach ($event_list as $e) {
	switch ($e['module']) {
	case 'VOICE':
		switch ($e['event']) {
		case 'StartRecordingEvent':
			if (!empty($audio_file)) {
				// die("Duplicate Start Recording Event?!?\n");
				break;
			}
			$audio_file = strval($e['source']->filename);
			if (!is_file($audio_file)) {
				$b = basename($audio_file);
				$audio_file = '/var/bigbluebutton/recording/raw/' . $mid . '/audio/' . $b;
			}
			$audio['file'] = $audio_file;
			$audio['file_basename'] = basename($audio_file);
			$audio['time_alpha'] = $e['time_o']; // Time in ms
			$audio['time_alpha_s'] = $audio['time_alpha'] / 1000;
			$buf = shell_exec('sox ' . escapeshellarg($audio['file']) . ' -n stat 2>&1');
			if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
				$audio['length_file'] = floatval($m[1]) * 1000;
			} else {
				print_r($buf);
				die("Cannot Find Length\n");
			}
			break;
		case 'StopRecordingEvent':
			$audio['time_omega'] = $e['time_o'];
		}
		break;
	case 'PARTICIPANT':
		switch ($e['event']) {
		case 'ParticipantJoinEvent':
			$video_list[ strval($e['source']->userId) ]['user'] = strval($e['source']->userId);
			$video_list[ strval($e['source']->userId) ]['name'] = strval($e['source']->name);
			break;
		case 'ParticipantStatusChangeEvent':
			$vid = strval($e['source']->userId);
			$x = strval($e['source']->value);
			if (preg_match('/true,stream=(.+)/',$x,$m)) {
				$video_list[$vid]['file'] = $m[1] . '.flv';
				if (!is_file($video_list[$vid]['file'])) {
					$b = basename($video_list[$vid]['file']);
					$video_list[$vid]['file'] = '/var/bigbluebutton/recording/raw/' . $mid . '/video/' . $mid . '/' . $b;
				}
				$video_list[$vid]['time_join'] = $e['time_o'];
				// $want_next_webcam = $uid;
				//} else {
				//	$video_list[$uid]['append'] = array(
				//		'file' => $m[1] . '.flv',
				//		'time_join' => $e['time_o'],
				//	);
				//}
			}
			// print_r($e);
			// if ('hasStream=true,stream' == $e['status']) {
			// 	echo "New Video Stream at {$e['time_u']} is {$e['value']}\n";
			// }
		}

		if ('ParticipantLeftEvent' == $e['event']) {
			// $want_next_webcam = strval($e['source']->userId);
		}
		break;

	case 'WEBCAM':
		if ('StartWebcamShareEvent' == $e['event']) {
			// print_r($e);exit;
			if (preg_match('/\w+\-(\w+)\-\d+/',strval($e['source']->stream),$m)) {
				// $vid = strval($e['source']->stream);
				$vid = $m[1];
				// echo "Webcam Alpha: $vid at {$e['time_o']}\n";
				$video_list[$vid]['webcam_alpha'] = $e['time_o'];
			}
			// print_r($e);
			// if (!empty($want_next_webcam)) {
			// 	$video_list[$want_next_webcam]['time_webcam_alpha'] = $e['time_o'];
			// 	// print_r($video_list[$want_next_webcam]);
			// 	$want_next_webcam = null;
			// }
		}
		// May Happen BEFORE the PARTICIPANT / ParticipantLeftEvent
		if ('StopWebcamShareEvent' == $e['event']) {
			if (preg_match('/\w+\-(\w+)\-\d+/',strval($e['source']->stream),$m)) {
				// $vid = strval($e['source']->stream);
				$vid = $m[1];
				// echo "Webcam Omega: $vid\n";
				$video_list[$vid]['webcam_omega'] = $e['time_o'];
			}
		}
	}

	$event_last = $e['time_o'];

}
$audio['length_calc'] = $audio['time_omega'] - $audio['time_alpha'];
$audio['length_calc_s'] = $audio['length_calc'] / 1000; 

if (empty($audio_file)) die("Cannot Find Audio File\n");

// print_r($audio);

// echo "Audio: {$audio['file']} at +{$audio['time_alpha']} for $audio_time\n";
// Prepare Audio File with Lead Time Silence "
// $cmd = "sox -i {$audio['file']}";
// shell_exec("$cmd 2>&1");

echo 'Speed: ' . ( $audio['length_file'] / $audio['length_calc']) . "\n";

$speed = $audio['length_file'] / $audio['length_calc'];

// Make Leading Silence
$cmd = "sox -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav head.wav trim 0.000 {$audio['time_alpha_s']}";
echo "cmd:$cmd\n";
echo shell_exec("$cmd 2>&1");

// Adjust Audio File Time and Length
$cmd = "sox -q -m -b 16 -c 1 -e signed -r 16000 -L -n {$audio['file']} -b 16 -c 1 -e signed -r 16000 -L -t wav trim.wav speed $speed rate -h 16000 trim 0.000 {$audio['length_calc_s']}";
echo "cmd:$cmd\n";
echo shell_exec("$cmd 2>&1");

// /tmp/tail.wav is 78ms of slience, how is that factored?
$cmd = "sox -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav tail.wav trim 0.000 0.078";
echo "cmd:$cmd\n";
echo shell_exec("$cmd 2>&1");

// Concat
$cmd = "sox -q head.wav trim.wav tail.wav -b 16 -c 1 -e signed -r 16000 -L -t wav work.wav";
echo "cmd:$cmd\n";
echo shell_exec("$cmd 2>&1");

$list = array_keys($video_list);
foreach ($list as $x) {
	if (empty($video_list[$x]['file'])) {
		unset($video_list[$x]);
	}
}

foreach ($video_list as $vid=>$video) {

	// print_r($video);
	if (!empty($uid)) {
		if ($uid != $video['name']) {
			// echo "Skipping: {$video['file']}\n";
			continue;
		}
	}
	if (empty($video['file'])) {
		echo "Missing Video FIle\n";
		continue;
	}
	if (empty($video['webcam_alpha'])) {
		print_r($video);
		echo "Missing WEbCam STart FIle\n";
		continue;
	}
	if (empty($video['webcam_omega'])) {
		$video['webcam_omega'] = $event_last;
	}

	// Make Head Padding to Time of Event in File
	// @see https://trac.ffmpeg.org/wiki/FancyFilteringExamples
	$cmd = 'ffmpeg -to ' . ($video['webcam_alpha'] / 1000) . ' -filter_complex color=c=#D6DDE4:s=640x480:r=24 -an -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24 -f mpegts -y head.ts';
	echo "cmd:$cmd\n";
	echo shell_exec("$cmd 2>&1");

	// Use Direct Time
	$cmd = 'ffmpeg '; // -y -v warning -nostats
	$cmd.= ' -i ' . $video['file'];
	// $cmd.= ' -ss ' . (($video['webcam_alpha']) / 1000); // Skew Audio
	// $cmd.= ' -i ' . $audio_file;
	// $cmd.= ' -an -q:v 2 -pix_fmt yuv420p -r 24 ';
	// $cmd.= ' -vcodec libx264 -acodec libmp3lame ';
	// $cmd.= ' -c:a libvorbis -c:v libvpx ';
	$cmd.= ' -f mpegts ';
	$cmd.= ' -y main.ts';
	echo "cmd:$cmd\n";
	echo shell_exec("$cmd 2>&1");

	// Now Concat These two Video Files
	$cmd = 'ffmpeg -i \'concat:head.ts|main.ts\' -q:a 0 -q:v 0 -y work.ts';
	echo "cmd:$cmd\n";
	echo shell_exec("$cmd 2>&1");

	// Merge Audio and Video Here to WebM format
	$cmd = 'ffmpeg ';
	$cmd.= ' -i work.ts ';
	$cmd.= ' -i work.wav ';
	$cmd.= ' -c:v libvpx -crf 34 -b:v 60M -threads 2 -deadline good -cpu-used 3 -c:a libvorbis -b:a 32K -f webm ';
	$cmd.= ' -y ' . escapeshellarg("/tmp/video-$vid.webm");
	echo "cmd:$cmd\n";
	echo shell_exec("$cmd 2>&1");

	if (is_file("/tmp/video-$vid.webm")) {
		echo "file:/tmp/video-$vid.webm\n";
	} else {
		echo "fail:$cmd\n";
	}

}

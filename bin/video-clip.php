#!/usr/bin/php
<?php
/**
	We Identify Each FLV in the Stream and Overlay it Over the Audio

	@see https://code.google.com/p/bigbluebutton/issues/detail?id=900
	@see https://code.google.com/p/bigbluebutton/issues/detail?id=1516
	@see http://www.ezs3.com/public/What_bitrate_should_I_use_when_encoding_my_video_How_do_I_optimize_my_video_for_the_web.cfm
*/

require_once(dirname(dirname(__FILE__)) . '/boot.php');

$mid = $argv[1];
$uid = null;
$uname = $argv[2];

$bbm = new BBB_Meeting($mid);

$event_list = $bbm->getEvents();
$event_last = null;

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

$meeting_alpha = $meeting_omega = 0;

foreach ($event_list as $e) {
	if (empty($meeting_alpha)) $meeting_alpha = $e['time_u'];
	switch ($e['module']) {
	case 'VOICE':
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
	$meeting_omega = $e['time_u'];
}

$meeting_duration = $meeting_omega - $meeting_alpha;

$list = array_keys($video_list);
// Throw out Bad Ones
foreach ($list as $x) {
	if (empty($video_list[$x]['file'])) {
		unset($video_list[$x]);
		continue;
	}
	if (empty($video_list[$x]['webcam_omega'])) {
		$video_list[$x]['webcam_omega'] = $meeting_duration;
	}
}

// Map to specific username
if (!empty($uname)) {

	// Filter Out Ones I don't care about
	$list = array_keys($video_list);
	foreach ($list as $x) {
		// Assign UID when Unknown
		if (empty($uid)) {
			if ($video_list[$x]['name'] == $uname) {
				$uid = $video_list[$x]['user'];
				break;
			}
		}
	}
	// Spin and remove non-matching UID
	foreach ($list as $x) {
		if ($video_list[$x]['user'] != $uid) {
			unset($video_list[$x]);
		}
	}
	// print_r($video_list);

	// @todo Sort by webcam_alpha?
	$vid_alpha = $meeting_duration;
	$vid_omega = 0;
	foreach ($video_list as $vid=>$video) {
		$vid_alpha = min($vid_alpha, $video['webcam_alpha']);
		$vid_omega = max($vid_omega, $video['webcam_omega']);
	}
	// Leader Video to Here
	// echo "First at: $vid_alpha, Last at: $vid_omega\n";

	$cat_list = array();
	$pre_time = 0;
	// ffmpeg_blank($vid_alpha / 1000, 'head.ts'); //($video['webcam_alpha'] / 1000)
	// $cat_list[] = 'head.ts';
	foreach ($video_list as $vid=>$video) {
		// print_r($video);
		ffmpeg_empty(($video['webcam_alpha'] - $pre_time) / 1000, "pre-$vid.ts");
		$cat_list[] = "pre-$vid.ts";

		ffmpeg_convert2ts($video['file'], "vid-$vid.ts");
		$cat_list[] = "vid-$vid.ts";

		$pre_time = $video['webcam_omega'];
	}
	// echo "pre:$pre_time; vom: $vid_omega\n";

	// Tail
	if ($vid_omega < $meeting_duration) {
		ffmpeg_empty(($meeting_duration - $vid_omega) / 1000, 'tail.ts');
		$cat_list[] = 'tail.ts';
	}

	// Concat
	ffmpeg_concat($cat_list, 'work.ts');

	// Get Audio
	$uri = sprintf('http://%s@%s/bbd/api/v2013.43/audio?id=%s', $_ENV['bbb']['api_key'], $_ENV['app']['host'], $mid);
	syslog(LOG_DEBUG, "audio URI:$uri");
	_curl_get($uri, 'work.wav');

	// Merge Audio and Video Here to WebM format
	// @see https://www.virag.si/2012/01/webm-web-video-encoding-tutorial-with-ffmpeg-0-9/
	// @see http://superuser.com/questions/556463/converting-video-to-webm-with-ffmpeg-avconv
	$out = "/tmp/$mid.webm";
	$cmd = 'ffmpeg';
	$cmd.= ' -i work.ts';
	$cmd.= ' -i work.wav';
	// $cmd.= ' -codec:v libvpx -b:v 512k '; // -crf 16 -qmin 10 -qmax 42 '; // -crf 34 -deadline good ';
	// $cmd.= ' -codec:a libvorbis -b:a 32K ';
	// $cmd.= ' -threads 6 ';
	$cmd.= ' -c:v libvpx -crf 34 -b:v 60M -threads 2 -deadline good -cpu-used 3 ';
	$cmd.= ' -c:a libvorbis -b:a 32K';
	$cmd.= ' -f webm -y ' . escapeshellarg($out);
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");

	if (is_file($out)) {
		echo "file:$out\n";
	} else {
		echo "fail:$cmd\n$buf\n";
	}
}

function _curl_get($uri, $out)
{
	$ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FILE, fopen($out,'w'));
	curl_exec($ch);
	curl_close($ch);
}

<?php
/**
	Provides information for the Slides
	@param $_GET['id'] find a meeting with that name or ID
*/

header('Content-Type: application/json');

if (!acl::has_access($_SESSION['uid'], 'api-meeting-select')) {
	exit_403();
}

try {
	$bbm = new BBB_Meeting($_GET['id']);
} catch (Exception $e) {

}

$ret = array(
	'status' => 'success',
	'detail' => array(),
);

$uri_base = ('on' == $_SERVER['HTTPS'] ? 'https://' : 'http://');
$uri_base.= radix::$host;

// $stat = $bbm->stat();
// print_r($stat);
$media_list = array();

$event_list = $bbm->getEvents('PRESENTATION');
// print_r($event_list);

$cur_p = null;
foreach ($event_list as $e) {
	// print_r($e);
	// echo "{$e['event']}\n";
	switch ($e['event']) {
	case 'SharePresentationEvent':
		// print_r($e);
		$cur_p = strval($e['source']->presentationName);
		$k = sprintf('%s/slide-%d', $cur_p, 1);
		if (empty($media_list[$k])) {
			$media_list[$k] = array(
				'time_s' => $e['time_s'],
				'time_ms' => $e['time_ms'],
				'time_offset_ms' => $e['time_offset_ms'],
			);
		}
		break;
	case 'GotoSlideEvent':
		// print_r($e);
		// Indexed in Events from 0, in filesystem from 1 :(
		$k = sprintf('%s/slide-%d', $cur_p, intval($e['source']->slide)+1 );
		if (empty($media_list[$k])) {
			$media_list[$k] = array(
				'time_s' => $e['time_s'],
				'time_ms' => $e['time_ms'],
				'time_offset_ms' => $e['time_offset_ms'],
			);
		} else {
			// Showing Same Slide Again, What to do?
		}
		break;
	case 'ConversionCompletedEvent':
		// print_r($e['source']->slidesInfo->uploadedpresentation);
		// foreach ($e['source']->slidesInfo->uploadedpresentation->conference->presentation->slides as $s) {
		// 	print_r($s);
		// }
		break;
	case 'GenerateSlideEvent':
	default:
		break;
	}
}
// print_r($media_list);

// $path = '/var/bigbluebutton/published/presentation/' . $_GET['id'] . '/presentation/*';
// print_r($path);
// //
// $list = glob($path);
// print_r($list);

// foreach ($list as $e) {
// 	if (is_dir($e)) {
// 		print_r(glob("$e/*"));
// 	}
//
// }
$list = array_keys($media_list);
foreach ($list as $x) {

	// The Slide itself
	$file = '/var/bigbluebutton/published/presentation/' . $_GET['id'] . '/presentation/' . $x . '.png';
	if (is_file($file)) {
		// $media_list[$x]['file'] = $file;
		$media_list[$x]['image'] = str_replace('/var/bigbluebutton/published', $uri_base, $file);
	}

	// Detect Text
	$file = '/var/bigbluebutton/published/presentation/' . $_GET['id'] . '/presentation/' . str_replace('/', '/textfiles/', $x) . '.txt';
	if (is_file($file)) {
		$buf = file_get_contents($file);
		$buf = iconv('ISO-8859-1','UTF-8//IGNORE',$buf);
		$media_list[$x]['text'] = $buf;
	}

	$pub_file = '/var/bigbluebutton/published/presentation/' . $_GET['id'] . '/presentation/' . str_replace('slide-', 'thumb-', $x) . '.png';
	$raw_file = '/var/bigbluebutton/recording/raw/' . $_GET['id'] . '/presentation/' . str_replace('slide-', 'thumbnails/thumb-', $x) . '.png';
	if (!is_file($pub_file)) {
		if (is_file($raw_file)) {
			copy($raw_file, $pub_file);
		}
		// $buf = file_get_contents($file);
		// $buf = iconv('ISO-8859-1','UTF-8//IGNORE',$buf);
		// $media_list[$x]['text'] = $buf;
	}
	if (is_file($pub_file)) {
		$media_list[$x]['thumb'] = str_replace('/var/bigbluebutton/published', $uri_base, $pub_file);
	}
}

$ret['detail'] = $media_list;

die(json_encode($ret));

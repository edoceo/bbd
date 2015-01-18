<?php
/**
	@file
	@brief File Information about Meetings
*/

if (!acl::has_access($_SESSION['uid'], 'ajax-live')) {
	radix::redirect('/');
}

$mid = trim($_GET['m']);

try {
	$bbm = new BBB_Meeting($mid);
} catch (Exception $e) {
	echo '<p class="fail">Unable to load meeting: ' . $mid . '</p>';
	echo '<p class="info">' . $e->getMessage() . '</p>';
	exit(0);
}

$stat = $bbm->stat();
$size = 0;
$size_sum = 0;


switch ($_GET['k']) {
case 'all':
	$buf = shell_exec("locate $mid | sort 2>&1");
	$pat = array('/var/bigbluebutton', '/usr/share/red5/webapps/video/streams');
	$rep = null;
	$buf = str_replace($pat, $rep, $buf);
	die('<pre>' . htmlspecialchars($buf, ENT_QUOTES, 'utf-8', true) . '</pre>');
	break;
case 'arc':
case 'archive':

	echo '<table>';

	// Archive File Details
	$size_sum += _draw_file_list($stat['archive']['audio'],ICON_AUDIO);
	unset($stat['archive']['audio']);
	
	$size_sum += _draw_file_list($stat['archive']['video'],ICON_VIDEO);
	unset($stat['archive']['video']);
	
	$size_sum += _draw_file_list($stat['archive']['slide'],ICON_SLIDE);
	unset($stat['archive']['slide']);
	
	$size_sum += _draw_file_list($stat['archive']['share'],ICON_SHARE);
	unset($stat['archive']['share']);
	
	$size_sum += _draw_file_list($stat['archive']['event'],ICON_EVENT);
	unset($stat['archive']['event']);

	echo '</table>';

	exit(0);

	break;
case 'raw':
case 'source':

	echo '<table>';

	// Raw Audio
	$size_sum += _draw_file_list($stat['source']['audio'],ICON_AUDIO);
	// unset($stat['source']['audio']);

	// Source Videos
	$size_sum += _draw_file_list($stat['source']['video'],ICON_VIDEO);
	// unset($stat['source']['video']);

	// SourceSlide
	$size_sum += _draw_file_list($stat['source']['slide'],ICON_SLIDE);
	// unset($stat['source']['slide']);

	$size_sum += _draw_file_list($stat['source']['share'],ICON_SHARE);
	// unset($stat['source']['share']);

	echo '</table>';

	exit(0);

	break;
}

die("<p><strong>No data was found for {$_GET['src']}</strong></p>");


/**
	Draws Rows of Files, Returns Size of Files
*/
function _draw_file_list($list,$icon)
{
	if (empty($list)) return;
	if (!is_array($list)) return;
	if (0 == count($list)) return;

	$size = 0;
	foreach ($list as $f) {

		if (is_dir($f)) {
			continue;
		}

		$s = filesize($f);
		echo '<tr>';
		echo '<td>' . $icon . '</td>';
		echo '<td title="' . $f . '"><a href="' . radix::link('/download?f=' . $f) . '">' . basename($f) . '</a></td>';
		echo '<td>' . number_format(ceil($s / 1024), 0) . 'KB</td>';
		// echo '<td>' . md5_file($f) . '</td>';
		echo '</tr>';
		$size += $s;
	}
	echo '<tr>';
	echo '<td>'. $icon . '</td>';
	echo '<td>' . number_format(ceil($size / 1024 / 1024), 0) . 'MB</td>';
	echo '<td>' . count($list) . ' Files</td>';
	echo '</tr>';
	return $size;
}

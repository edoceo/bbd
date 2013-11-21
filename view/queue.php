<?php
/**
	Just shows Queue States
*/

$type_list = BBB::listTypes();

echo '<h2>Recording Archival Queue</h2>';
$i = 0;
$list = glob(BBB::BASE . '/recording/status/recorded/*.done', GLOB_NOSORT);
foreach ($list as $f) {
	$m = basename($f, '.done');
	if (!is_dir(BBB::BASE . "/recording/raw/$m")) {
		echo '<p>Meeting: ' . $m . '</p>';
		$i++;
	}
}
echo "<p>$i to process</p>";

echo '<h2>Archive Sanity Checks</h2>';
$i = 0;
$list = glob(BBB::BASE . '/recording/status/archived/*.done', GLOB_NOSORT);
foreach ($list as $f) {
	$m = basename($f, '.done');
	$x = array();
	if (is_file(BBB::BASE . "/recording/status/sanity/$f.fail")) {
		$x[] = "Failed";
	}
	if (!is_file(BBB::BASE . "/recording/status/sanity/$m.done")) {
		$x[] = "Sanity Check";
	}
	if (count($x)) {
		echo '<p>Meeting: ' . $m . ' because of ' . implode(', ', $x) . '</p>';
		$i++;
	}
}
echo "<p>$i to process</p>";

echo '<h2>Archive Processing</h2>';
$i = 0;
$list = glob(BBB::BASE . '/recording/status/sanity/*.done', GLOB_NOSORT);
foreach ($list as $f) {
	$m = basename($f, '.done');
	foreach ($type_list as $t) {
		if (!is_dir(BBB::BASE . '/recording/process/' . $t . '/' . $m)) {
			echo '<p>Meeting: ' . $m . ' needs ' . $t . '</p>';
			$i++;
		}
	}
}
echo "<p>$i to process</p>";

echo '<h2>Archive Publishing</h2>';
$i = 0;
$list = glob(BBB::BASE . '/recording/status/processed/*.done', GLOB_NOSORT);
// $type_list = glob(
foreach ($list as $f) {
	$m = basename($f, '.done');
	$m = str_replace('-presentation', null, $m);
	foreach ($type_list as $t) {
		if (!is_dir(BBB::BASE . '/recording/publish/' . $t . '/' . $m)) {
			echo '<p>Meeting: ' . $m . ' needs ' . $t . '</p>';
			$i++;
		}
	}
}
echo "<p>$i to process</p>";

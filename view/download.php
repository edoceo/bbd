<?php
/**
	@file
	@breif Provides a Direct Download for Files in the BBB world
*/

$file = null;
$name = null;

// A Specific File
if (!empty($_GET['f'])) {

	radix::dump($_GET);

	$list = BBB::listPaths();
	radix::dump($list);

	exit(0);
}

// A Meeting
if (!empty($_GET['m'])) {

	// Tarball a Meeting?  I don't like this thing
	$mid = $_GET['m'];
	$bbm = new BBB_Meeting($mid);

	$path = BBB::RAW_ARCHIVE_PATH . "/$mid";
	$file = "$path.tgz";

	if (!is_file($file)) {
		   # $cmd = "/bin/tar --create --directory $path . |/bin/gzip >$file 2>/tmp/err.out";
		   $cmd = "tar -zcf $file --directory $path . 2>&1";
		   # echo $cmd;
		   shell_exec($cmd);
	}

	$name = 'Meeting_' . $bbm->code . '.tgz';
}

// Clean Buffer
while (ob_get_level()) ob_end_clean();

header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file));
header('Content-Transfer-Encoding: binary');
header('Content-Type: application/octet-stream');

readfile($file);

exit;
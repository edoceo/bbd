<?php
/**
	@file
	@breif Provides a Direct Download for Files in the BBB world
*/

$file = null;
$name = null;

// A Specific File
if (!empty($_GET['f'])) {

	$file = $_GET['f'];
	$name = basename($file);

	// Check in Trusted Path
	$good = false;
	$list = BBB::listPaths();
	foreach ($list as $path) {
		if ($path == substr($file,0,strlen($path))) {
			send_download($file);
		}
	}
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

	send_download($file, 'Meeting_' . $bbm->code . '.tgz');
}

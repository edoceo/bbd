<?php
/**
	@file
	@brief File Information about Meetings
*/

if (!acl::has_access($_SESSION['uid'], 'ajax-live')) {
	radix::redirect('/');
}

$mid = trim($_GET['id']);

switch ($_GET['src']) {
case 'all':
	$buf = shell_exec("locate $mid | sort 2>&1");
	$pat = array('/var/bigbluebutton', '/usr/share/red5/webapps/video/streams');
	$rep = null;
	$buf = str_replace($pat, $rep, $buf);
	die('<pre>' . htmlspecialchars($buf, ENT_QUOTES, 'utf-8', true) . '</pre>');
	break;
}


die("<p><strong>No data was found</strong></p>");
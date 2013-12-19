<?php
/**


*/

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)) . '/boot.php');

session_start();

radix::init();

if (preg_match('|^/api|', radix::$path)) {

	$good = false;
	if ($_ENV['bbb']['api_key'] == $_SERVER['PHP_AUTH_USER']) {
		// BBB Salt
		$good = true;
	} elseif ( ($_ENV['app']['user'] == $_SERVER['PHP_AUTH_USER']) && ($_ENV['app']['pass'] == $_SERVER['PHP_AUTH_PW']) ) {
		// Username & Password
		$good = true;
	}

	if (!$good) {
		header('HTTP/1.1 403 Forbidden', true, 403);
		die(json_encode(array(
			'status' => 'fail',
			'detail' => 'Access Denied',
		)));
	}

	// OK
	$_SESSION['uid'] = 'api';
	acl::set_access('api', 'api-meeting-delete');
	acl::set_access('api', 'api-meeting-create');
	acl::set_access('api', 'api-meeting-select');
}

// Allow Anonymous to Join
if (empty($_SESSION['uid'])) {
	switch (radix::$path) {
	case '/auth':
		break;
	case '/join':
		$_SESSION['uid'] = 'join';
		acl::set_access('join','join');
		break;
	default:
		radix::redirect('/auth');
	}
}

// Fancy Routes for API
radix::route('/api/meeting', '/api/v2013.43/meeting');
radix::route('/api/meeting/(?P<id>[0-9a-f]{24})', '/api/v2013.43/meeting');
radix::route('/api/meeting/(?P<id>[0-9a-f]{24})/audio', '/api/v2013.43/audio');
radix::route('/api/meeting/(?P<id>[0-9a-f]{24})/video', '/api/v2013.43/video');
radix::route('/api/meeting/(?P<id>[0-9a-f]{24})/media', '/api/v2013.43/media');

radix::exec();
radix::view();
radix::send();

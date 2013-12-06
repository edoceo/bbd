<?php
/**


*/

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)) . '/boot.php');

session_start();

radix::init();

if (preg_match('|^/api/v2013\.43|', radix::$path)) {

	if ($_ENV['bbb']['api_key'] == $_SERVER['PHP_AUTH_USER']) {
		// OK 
	} else {
		// Look for HTTP Auth
		if ( ($_ENV['app']['user'] != $_SERVER['PHP_AUTH_USER']) || ($_ENV['app']['pass'] != $_SERVER['PHP_AUTH_PW']) ) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			die(json_encode(array(
				'status' => 'fail',
				'detail' => 'Access Denied',
			)));
		}
	}
	$_SESSION['uid'] = 'api';
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
radix::route('/api/meeting/(?P<id>[0-9a-f]{24})/media', '/api/v2013.43/media');

radix::exec();
radix::view();
radix::send();

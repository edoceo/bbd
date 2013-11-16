<?php
/**


*/

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)) . '/boot.php');

session_start();

radix::init();
if (preg_match('|^/api/v2013\.43|', radix::$path)) {
	// Look for HTTP Auth
	if ( ($_ENV['app']['user'] != $_SERVER['PHP_AUTH_USER']) || ($_ENV['app']['pass'] != $_SERVER['PHP_AUTH_PW']) ) {
		header('HTTP/1.1 403 Forbidden', true, 403);
		die(json_encode(array(
			'status' => 'fail',
			'detail' => 'Access Denied',
		)));
	}
	$_SESSION['uid'] = 'api';
}

if (empty($_SESSION['uid'])) {
	if (radix::$path != '/auth') {
		radix::redirect('/auth');
	}
}
radix::exec();
radix::view();
radix::send();

<?php
/**


*/

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)) . '/boot.php');

radix::init();
if (radix::$path != '/auth') {
	if (empty($_SESSION['uid'])) {
		radix::redirect('/auth');
	}
}
radix::exec();
radix::view();
radix::send();

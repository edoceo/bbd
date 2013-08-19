<?php
/**


*/

define('APP_ROOT',dirname(dirname(__FILE__)));
define('APP_NAME','BigBlueDashboard');

require_once(APP_ROOT . '/lib/radix.php');
require_once(APP_ROOT . '/lib/BBB.php');
require_once(APP_ROOT . '/lib/BBB_Meeting.php');

$_ENV['title'] = APP_NAME;

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);

radix::init();
radix::exec();
radix::view();
radix::send();



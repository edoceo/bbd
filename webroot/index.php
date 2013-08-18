<?php
/**


*/

define('APP_ROOT',dirname(dirname(__FILE__)));

require_once(APP_ROOT . '/lib/radix.php');
require_once(APP_ROOT . '/lib/BBB.php');
require_once(APP_ROOT . '/lib/BBB_Meeting.php');

radix::init();
radix::exec();
radix::view();
radix::send();



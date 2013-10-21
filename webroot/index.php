<?php
/**


*/

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)) . '/boot.php');

radix::init();
radix::exec();
radix::view();
radix::send();

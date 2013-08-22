<?php
/**


*/

define('APP_ROOT',dirname(dirname(__FILE__)));
define('APP_NAME','BigBlueDashboard');

define('ICON_AUDIO','<i class="icon-bullhorn" title="Audio"></i>');
define('ICON_VIDEO','<i class="icon-film" title="Video"></i>'); // <i class="icon-facetime-video"></i>
define('ICON_SLIDE','<i class="icon-picture" title="Slides"></i>'); // <i class="icon-file-text"></i>
define('ICON_SHARE','<i class="icon-desktop" title="Desktop Sharing"></i>');
define('ICON_WATCH','<i class="icon-youtube-play" title="Watch Meeting"></i>');
define('ICON_EVENT','<i class="icon-rocket" title="Event Details"></i>');

require_once(APP_ROOT . '/lib/radix.php');
require_once(APP_ROOT . '/lib/BBB.php');
require_once(APP_ROOT . '/lib/BBB_Meeting.php');

$cfg = parse_ini_file(APP_ROOT . '/etc/boot.ini',true);
BBB::$_api_host = $cfg['bbb']['host'];
BBB::$_api_salt = $cfg['bbb']['salt'];

$_ENV['TZ'] = $cfg['app']['timezone'];
$_ENV['title'] = APP_NAME;

error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);

radix::init();
radix::exec();
radix::view();
radix::send();

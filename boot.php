<?php
/**
	@file
	@brief Bootstrapper for BBD
*/

define('APP_ROOT',dirname(__FILE__));
define('APP_NAME','BigBlueDashboard');

define('ICON_AUDIO','<i class="icon-bullhorn" title="Audio"></i>');
define('ICON_VIDEO','<i class="icon-film" title="Video"></i>'); // <i class="icon-facetime-video"></i>
define('ICON_SLIDE','<i class="icon-picture" title="Slides"></i>'); // <i class="icon-file-text"></i>
define('ICON_SHARE','<i class="icon-desktop" title="Desktop Sharing"></i>');
define('ICON_WATCH','<i class="icon-youtube-play" title="Watch Meeting"></i>');
define('ICON_EVENT','<i class="icon-rocket" title="Event Details"></i>');

openlog('bbd', LOG_CONS, LOG_LOCAL0);

require_once(APP_ROOT . '/lib/radix.php');
require_once(APP_ROOT . '/lib/BBB.php');
require_once(APP_ROOT . '/lib/BBB_Meeting.php');
require_once(APP_ROOT . '/lib/bbd.php');

$_ENV = parse_ini_file(APP_ROOT . '/etc/boot.ini',true);
BBB::$_api_uri = $_ENV['bbb']['api_uri'];
BBB::$_api_key = $_ENV['bbb']['api_key'];

$_ENV['TZ'] = $_ENV['app']['timezone'];
$_ENV['title'] = APP_NAME;


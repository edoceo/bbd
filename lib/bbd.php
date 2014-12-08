<?php
/**
	Misc tools for ffmpeg, sox
*/

class bbd
{
	public static $r;

	public static function init()
	{
		$_ENV = parse_ini_file(APP_ROOT . '/etc/boot.ini',true);

		$_ENV['TZ'] = $_ENV['app']['timezone'];
		$_ENV['title'] = APP_NAME;

		BBB::$_api_uri = $_ENV['bbb']['api_uri'];
		BBB::$_api_key = $_ENV['bbb']['api_key'];

		self::$r = new Redis();
		self::$r->connect('127.0.0.1');
	}
}

function formatMMSS($x)
{
	$m = floor($x / 60);
	$s = floor($x - ($m * 60));
	$x = floor(($x - $s - ($m*60)) * 1000);
	return sprintf('%02d:%02d.%03d', $m, $s, $x);
}

/**
	Recursively Remove a Glob
*/
function wipe_path($glob)
{
	$ret = "wipe_path($glob)\n";
	$list = glob($glob);
	foreach ($list as $path) {
		if (is_dir($path)) {
			$ret.= wipe_path("$path/*");
			$ret.= "rmdir($path);\n";
			rmdir($path);
		}
			if (is_file($path)) {
			$ret.= "unlink($path);\n";
			unlink($path);
		}
	}
	return $ret;
}

function sox_empty($time, $file)
{
	$time = max(0, $time);

	$cmd = 'nice sox '; //  -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav';
	$cmd.= ' -n -r 16000 ';
	$cmd.= ' ' . escapeshellarg($file);
	$cmd.= ' trim';
	$cmd.= ' 0.0'; // Start
	$cmd.= ' ' . $time; // End
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");
}

function sox_length($file)
{
	// Get Length from SOX
	$cmd = 'nice sox ' . escapeshellarg($file) . ' -n stat 2>&1';
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec($cmd);
	if (preg_match('/Length.+:\s+([\d\.]+)/',$buf,$m)) {
		return floatval($m[1]) * 1000;
	}
	// else {
	// 	print_r($buf);
	// 	die("Cannot Find Length\n");
	// }
	return -1;

}

/**
	@param $src_file Source File
	@param $out_file Output File
	@param $t0 Absolute Starting Time, MS
	@param $t1 Absolute Ending Time, MS
*/
function sox_slice($src_file, $out_file, $t0, $t1=0)
{
	$span = $t1 - $t0;
	echo "sox_slice($src_file, $out_file, $t0, $t1, $span)\n";

	$cmd = 'nice sox'; //  -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav';
	$cmd.= ' ' . escapeshellarg($src_file);
	$cmd.= ' ' . escapeshellarg($out_file);
	$cmd.= ' trim';
	$cmd.= ' ' . sprintf('%0.4f', $t0); // Start
	if ($t1 > 0) $cmd.= ' ' . sprintf('%0.4f', $t1 - $t0); // Stop
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");
	echo "sox:$buf\n";

}

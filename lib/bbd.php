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

/**
	Generate Silence for Time to File
	@param $time float milliseconds
	@param $file string filename
*/
function ffmpeg_empty($time, $file)
{
	syslog(LOG_DEBUG, "ffmpeg_empty($time, $file)");

	$time = ($time / 1000);
	if ($time <= 0) $time = 0.500;

	$cmd = 'ffmpeg -to ' . sprintf('%0.3f', $time) . ' -filter_complex color=c=#101010:s=640x480:r=24 -an -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24 -f mpegts -y ' . escapeshellarg($file);
	syslog(LOG_DEBUG, "Video Blank: $cmd");
	shell_exec("$cmd 2>&1");
}

/**
	Converts Input to TS
*/
function ffmpeg_convert2ts($src, $dst)
{
	// Use Direct Time
	$cmd = 'ffmpeg'; // -y -v warning -nostats
	$cmd.= ' -i ' . escapeshellarg($src);
	$cmd.= ' -an';
	$cmd.= ' -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24';
	$cmd.= ' -f mpegts ';
	$cmd.= ' -y ' . escapeshellarg($dst);
	syslog(LOG_DEBUG, "Video Convert: $cmd");
	shell_exec("$cmd 2>&1");
}

/**
	@param $src array|string files to join
	@param $dst output file
*/
function ffmpeg_concat($src, $dst)
{
	if (is_array($src)) $src = implode('|', $src);

	$cmd = "ffmpeg -i 'concat:$src' -q:a 0 -q:v 0 -y " . escapeshellarg($dst);
	syslog(LOG_DEBUG, "Video Concat: $cmd");
	$buf = shell_exec("$cmd 2>&1");

	// echo "=$buf\n\n";

}

/**
	@param $file string file to read
	@return floating point seconds
*/
function ffmpeg_info($file)
{
	if (!is_file($file)) {
		return false;
	}

	if (0 == filesize($file)) {
		return false;
	}

	$ret = array(
		'size' => filesize($file),
		'streams' => array(),
	);
	$cmd = 'ffmpeg '; // -y -v warning -nostats
	$cmd.= ' -i ' . escapeshellarg($file);
	$buf = shell_exec("$cmd 2>&1");

	if (preg_match_all('/Input \#(\d+)/', $buf, $m)) {
		$ret['streams'] = count($m[0]);
	}

	if (preg_match('/ Duration: (\d+):(\d+):(\d+).(\d+)/', $buf, $m)) {
		$ret['length'] = floatval(sprintf('%d.%02d', ($m[1] * 3600) + ($m[2] * 60) + ($m[3]), $m[4]));
	}
	// else {
	// 	die($buf);
	//}

	return $ret;
}

/**
	@param $file string file to read
	@return floating point seconds
*/
function ffmpeg_length($file)
{
	if (!is_file($file)) {
		return false;
	}

	if (0 == filesize($file)) {
		return false;
	}

	$cmd = 'ffmpeg '; // -y -v warning -nostats
	$cmd.= ' -i ' . escapeshellarg($file);
	$buf = shell_exec("$cmd 2>&1");
	if (preg_match('/Duration: (\d+):(\d+):(\d+).(\d+)/', $buf, $m)) {
		// echo "Duration: {$m[0]}\n";
		$ret = floatval(sprintf('%d.%02d', ($m[1] * 3600) + ($m[2] * 60) + ($m[3]), $m[4]));
		return ($ret * 1000);
	} else {
		die($buf);
	}

	return -1;
}

function ffmpeg_slice($src_file, $out_file, $t0, $t1=0)
{
	$t0 = ($t0 / 1000);
	$t1 = ($t1 / 1000);

	$cmd = 'ffmpeg';
	$cmd.= ' -i ' . escapeshellarg($src_file);
	$cmd.= ' -ss ' . $t0;
	$cmd.= ' -t ' . $t1;
	$cmd.= ' -y ' . escapeshellarg($out_file);
	syslog(LOG_DEBUG, "Video Slice: $cmd");
	$buf = shell_exec("$cmd 2>&1");
	return $buf;
}

function sox_empty($time, $file)
{
	$time = max(0, $time);

	$cmd = 'sox '; //  -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav';
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
	$cmd = 'sox ' . escapeshellarg($file) . ' -n stat 2>&1';
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

function sox_slice($src_file, $out_file, $t0, $t1=0)
{
	$span = $t1 - $t0;
	echo "sox_slice($src_file, $out_file, $t0, $t1, $span)\n";

	$cmd = 'sox '; //  -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav';
	$cmd.= ' ' . escapeshellarg($src_file);
	$cmd.= ' ' . escapeshellarg($out_file);
	$cmd.= ' trim';
	$cmd.= ' ' . sprintf('%0.4f', $t0); // Start
	if ($t1 > 0) $cmd.= ' ' . sprintf('%0.4f', $t1 - $t0); // Stop
	syslog(LOG_DEBUG, $cmd);
	$buf = shell_exec("$cmd 2>&1");
}

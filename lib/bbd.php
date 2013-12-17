<?php
/**
	Misc tools for ffmpeg, sox
*/

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
	@param $time float seconds
	@param $file string filename
*/
function ffmpeg_empty($time, $file)
{
	syslog(LOG_DEBUG, "ffmpeg_empty($time, $file)");

	if ($time < 0) $time = 0.500;

	$cmd = 'ffmpeg -to ' . sprintf('%0.3f', $time) . ' -filter_complex color=c=#D6DDE4:s=640x480:r=24 -an -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24 -f mpegts -y ' . escapeshellarg($file);
	syslog(LOG_DEBUG, $cmd);
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
	syslog(LOG_DEBUG, $cmd);
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
	syslog(LOG_DEBUG, $cmd);
	echo shell_exec("$cmd 2>&1");

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
		echo "{$m[0]}\n";
		$ret = floatval(sprintf('%d.%02d', ($m[1] * 3600) + ($m[2] * 60) + ($m[3]), $m[4]));
		return $ret;
	} else {
		die($buf);
	}

	return 0;
}

function sox_empty($time, $file)
{
	$cmd = 'sox -q -b 16 -c 1 -e signed -r 16000 -L -n -b 16 -c 1 -e signed -r 16000 -L -t wav';
	$cmd.= ' ' . escapeshellarg($file);
	$cmd.= ' trim 0.000';
	$cmd.= ' ' . $time;
	syslog(LOG_DEBUG, $cmd);
	shell_exec("$cmd 2>&1");
}
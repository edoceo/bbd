<?php
/**

*/

class avconv
{
	const CMD = 'nice /opt/edoceo/bbd/ffmpeg-2.3.3-64bit-static/ffmpeg';

	/**
		Generate Silence for Time to File
		@param $time float milliseconds
		@param $file string filename
	*/
	static function blank($time, $file)
	{
		// syslog(LOG_DEBUG, "ffmpeg_empty($time, $file)");

		$time = ($time / 1000);
		if ($time <= 0) $time = 0.500;

		$cmd = self::CMD . ' -to ' . sprintf('%0.3f', $time) . ' -filter_complex color=c=#101010:s=640x480:r=24 -an -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24 -f mpegts -y ' . escapeshellarg($file);
		// syslog(LOG_DEBUG, "Video Blank: $cmd");
		shell_exec("$cmd 2>&1");
	}

	/**
		Converts Input to TS
	*/
	function ffmpeg_convert2ts($src, $dst)
	{
		// Use Direct Time
		$cmd = self::CMD; // -y -v warning -nostats
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
	static function concat($src, $dst)
	{
		if (is_array($src)) $src = implode('|', $src);
	
		$cmd = self::CMD . " -i 'concat:$src'";
		$cmd.= ' -c copy';
		// $cmd.= ' -q:a 0 -q:v 0';
		$cmd.= ' -y ' . escapeshellarg($dst);
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
		$cmd = self::CMD; // -y -v warning -nostats
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
	static function length($file)
	{
		if (!is_file($file)) {
			return false;
		}
	
		if (0 == filesize($file)) {
			return false;
		}
	
		$cmd = self::CMD; // -y -v warning -nostats
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
	
	/**
		@param $video Video File
		@param $audio Audio File
		@param $merge Merged Output
		@todo Detect Type from Extension?
	
	*/
	static function merge2webm($video, $audio, $merge=null)
	{
		$cmd = self::CMD;
		$cmd.= ' -i ' . escapeshellarg($video);
		$cmd.= ' -i ' . escapeshellarg($audio);
		// $cmd.= ' -c:v libvpx -crf 34 -b:v 60M -threads 2 -deadline good -cpu-used 3'; // BBB Default
		$cmd.= ' -c:v libvpx -crf 8 -qmin 0 -qmax 50 -b:v 8M -threads 2 -deadline good -cpu-used 2'; // Slightly Increased Quality
		// $cmd.= ' -codec:v libvpx -quality good -cpu-used 1 -b:v 500k -qmin 10 -qmax 42 -maxrate 500k -bufsize 1000k -threads 4 -vf scale=-1:480'; // Slow
		$cmd.= ' -c:a libvorbis -b:a 32K'; // BBB Default
		$cmd.= ' -f webm -y ' . escapeshellarg($merge);
		syslog(LOG_DEBUG, "merge2webm($cmd)");
		$buf = shell_exec("$cmd 2>&1");

		return $buf;
	}

	/**
	
	*/
	static function slice($src_file, $out_file, $t0, $t1=0)
	{
		$t0 = ($t0 / 1000);
		$t1 = ($t1 / 1000);
	
		$cmd = self::CMD;
		$cmd.= ' -ss ' . $t0;
		$cmd.= ' -t ' . $t1;
		$cmd.= ' -i ' . escapeshellarg($src_file);
		$cmd.= ' -y ' . escapeshellarg($out_file);
		syslog(LOG_DEBUG, "Video Slice: $cmd");
		$buf = shell_exec("$cmd 2>&1");
		return $buf;
	}
	
	static function stretch($src, $pts, $out)
	{
		$cmd = self::CMD;
		$cmd.= ' -i ' . escapeshellarg($src);
		// $cmd.= ' -codec mpeg2video -q:v 2 -pix_fmt yuv420p -r 24';
		$cmd.= sprintf(' -filter:v "setpts=%0.9f*PTS"', $pts);
		$cmd.= ' -f mpegts';
		$cmd.= ' -y ' . escapeshellarg($out);
		// syslog(LOG_DEBUG, "stretch($cmd)");
		$buf = shell_exec("$cmd 2>&1");
		return $buf;
	}
	
}

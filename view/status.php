<?php

$_ENV['title'] = 'BBB System Status';

echo '<h2>Meetings</h2>';

$list = glob(sprintf('%s/archived/*.done',BBB::STATUS));
echo '<h3>' . count($list) . ' Archived</h3>';

$list = glob(sprintf('%s/processed/*.done',BBB::STATUS));
echo '<h3>' . count($list) . ' Processed</h3>';

$list = glob(sprintf('%s/recorded/*.done',BBB::STATUS));
echo '<h3>' . count($list) . ' Recorded</h3>';

$list = glob(sprintf('%s/sanity/*.done',BBB::STATUS));
echo '<h2>' . count($list) . ' Sane</h2>';
// foreach ($list as $file) {
//     if (!preg_match('|/([0-9a-f]+)\-(\d+)\.done|',$file,$m)) continue;
// //
// //     $mid = $m[1];
// //     $mts = $m[2];
// //
// //     // Archived?
// //
// }
// // "#{recording_dir}/status/recorded/*.done"
// radix::dump($list);

$god_pid = trim(file_get_contents('/var/run/god.pid'));
$red_pid = trim(file_get_contents('/var/run/red5.pid'));
$rdb_pid = trim(file_get_contents('/var/run/redis.pid'));
$tom_pid = trim(file_get_contents('/var/run/tomcat6.pid'));
?>
<h2>Post Processor</h2>
<h3>God Script</h3>
<p>pid:<?php echo $pid; ?></p>
<?php
$buf = shell_exec('/bin/ps -e -opid,pcpu,rss,vsz,pmem,time,args'); // |/bin/grep -e ruby 2>&1
if (preg_match_all('/(\d+)\s+([\d\.]+)\s+(\d+)\s+(\d+)\s+(\d+\.\d+)\s+([\d\-\:]+)\s+(.*(ffmpeg|freeswitch|java|libreoffice|nginx|php|redis|ruby).*)$/m',$buf,$m)) {

	$p_list = array();
	$c = count($m[0]);
	for ($i=0;$i<$c;$i++) {
		$p_list[] = array(
			'pid' => $m[1][$i],
			'cpu' => $m[2][$i],
			'cpu-time' => $m[6][$i],
			'ram' => $m[5][$i],
			'ram-rss' => $m[3][$i],
			'ram-vsz' => $m[4][$i],
			'cmd' => $m[7][$i],
		);
	}
	usort($p_list,function($a,$b) {

		if ($a['cpu'] > $b['cpu']) return -1;
		if ($a['cpu'] == $b['cpu']) {
			if ($a['ram'] > $b['ram']) return -1;
			if ($a['ram'] == $b['ram']) {
				if ($a['ram-rss'] > $b['ram-rss']) return -1;
			}
		}
		return 1;
	});
	
	echo '<table>';
	foreach ($p_list as $p) {
		echo '<tr>';
		echo '<td>';
		if ($p['pid'] == $god_pid) echo 'GOD';
		elseif ($p['pid'] == $tom_pid) echo 'Tomcat';
		elseif ($p['pid'] == $rdb_pid) echo 'Redis';
		elseif ($p['pid'] == $red_pid) echo 'Red5';
		else echo $p['pid'];
		echo '</td>';
		echo '<td>' . $p['cpu'] . '%</td>'; // CPU
		echo '<td>' . $p['cpu-time'] . '</td>'; // CPU Time
		echo '<td>' . $p['ram'] . '%</td>'; // RAM
		echo '<td>' . $p['ram-rss'] . 'k</td>'; // RSS
		echo '<td>' . $p['ram-vsz'] . 'k</td>'; // VSZ
		echo '<td>' . $p['cmd'] . '</td>';
		echo '</td>';

	}
	echo '</table>';
    // radix::dump($m);
}
?>

<h2>God Logs</h2>
<pre>
<?php
echo shell_exec("tail /var/log/god.log 2>&1");
?>
</pre>

<h2>Web Server/Tomcat Information</h2>
<?php
echo '<pre>';
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-web.log");
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-rap-worker.log");
echo '</pre>';

/*
ffmpeg -y -i /var/bigbluebutton/recording/process/presentation/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/temp/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/concatenated.mpg -loglevel fatal -v -10 -sameq /var/bigbluebutton/recording/process/presentation/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/temp/c36a5f8f3fc3c9112e766aa1e34a6c3ee814a561-1375648042801/output.flv
*/

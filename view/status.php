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

$pid = trim(file_get_contents('/var/run/god.pid'));
?>
<h2>Post Processor</h2>
<h3>God Script</h3>
<p>pid:<?php echo $pid; ?></p>
<?php
$buf = shell_exec("/bin/ps -e -opid,pcpu,rss,vsz,pmem,time,args"); // |/bin/grep -e ruby 2>&1
if (preg_match_all('/(\d+)\s+(\d+\.\d+)\s+(\d+)\s+(\d+)\s+(\d+\.\d+)\s+([\d\-\:]+)\s+(.*(ffmpeg|freeswitch|java|libreoffice|nginx|php|redis|ruby).*)$/m',$buf,$m)) {

	echo '<table>';

	$c = count($m[0]);
	for ($i=0;$i<$c;$i++) {

		echo '<tr>';
		echo '<td>' . $m[1][$i] . '</td>';
		echo '<td>' . $m[2][$i] . '%</td>'; // CPU
		echo '<td>' . $m[6][$i] . '</td>'; // CPU Time
		echo '<td>' . $m[5][$i] . '%</td>'; // RAM
		echo '<td>' . $m[3][$i] . 'k</td>'; // RSS
		echo '<td>' . $m[4][$i] . 'k</td>'; // VSZ
		echo '<td>' . $m[7][$i] . '</td>';
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

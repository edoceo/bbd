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


$list = BBB::listProcesses();

echo '<h2>Processes</h2>';
echo '<table>';
foreach ($list as $p) {
	echo '<tr>';
	echo '<td>';
	echo $p['pid'];
	if (!empty($p['name'])) echo '/' . $p['name'];
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

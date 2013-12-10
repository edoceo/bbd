<?php
/**

*/

$list = glob(sprintf('%s/archived/*.done', BBB::STATUS));
$c_archived = count($list);

$list = glob(sprintf('%s/processed/*.done',BBB::STATUS));
$c_processed = count($list);

$list = glob(sprintf('%s/recorded/*.done',BBB::STATUS));
$c_recorded = count($list);

$list = glob(sprintf('%s/sanity/*.done',BBB::STATUS));
$c_sane = count($list);

$_ENV['title'] = 'BBB System Status';

echo '<h2>Meeting Status</h2>';
echo "<p>$c_archived Archived, $c_recorded Recorded, $c_processed Processed, $c_sane Sane</p>";

// Process Label
$list = BBB::listProcesses();
echo '<h2>Processes</h2>';
echo '<table>';
foreach ($list as $p) {
	echo '<tr>';
	echo '<td>';
	echo $p['pid'];
	if (!empty($p['name'])) echo ' (' . $p['name'] . ')';
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
<?php
echo '<pre>';
echo shell_exec("tail /var/log/god.log 2>&1");
echo '</pre>';

echo '<pre>';
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-rap-worker.log");
echo '</pre>';
?>

<h2>Web Server/Tomcat Information</h2>
<?php
echo '<pre>';
echo shell_exec("tail -n20 /var/log/bigbluebutton/bbb-web.log");
echo '</pre>';

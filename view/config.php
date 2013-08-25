<?php
/**
	@file
	@brief Show the Config of BBB and BBD
*/

// 

echo '<h2>BBB: ' . BBB::CONF_BBB . '</h2>';

// Configuration
$buf = trim(file_get_contents(BBB::CONF_BBB));
if (preg_match_all('/^(\w+): (.+)$/m',$buf,$m)) {
	// radix::dump($m);
	$map = array_combine($m[1],$m[2]);
	ksort($map);
	radix::dump($map);

	// Was trying to mimic what's below
	// echo '<pre>';
	// echo 'BASE=' . BBB::BASE . "\n";
	// echo 'STATUS=' . BBB::STATUS . "\n";
	// echo trim(file_get_contents('/etc/bigbluebutton/bigbluebutton-release')) . "\n";
	// echo '</pre>';
} else {
	echo '<p class="fail">Could not parse the BBB Configuration</p>';
	radix::dump($buf);
}

// Tomcat Information
echo '<h2>Tomcat: ' . BBB::CONF_TOMCAT . '</h2>';
$buf = trim(file_get_contents(BBB::CONF_TOMCAT));

if (preg_match_all('/^(\w+)=(.+)$/m',$buf,$m)) {
	$map = array_combine($m[1],$m[2]);
	ksort($map);
	radix::dump($map);
}
// radix::dump();

echo '<h2>BBD Application</h2>';
$res = get_defined_constants(true);
radix::dump($res['user']);

echo '<h2>BBD Configuration</h2>';
$res = new ReflectionClass('BBB');
radix::dump($res->getConstants());

$cfg = parse_ini_file(APP_ROOT . '/etc/boot.ini',true);
radix::dump($cfg);

radix::dump($_ENV);

echo '<h2>Server Environment</h2>';
ksort($_SERVER);
radix::dump($_SERVER);

echo '<h2>Radix Environment</h2>';
echo radix::info();

?>
<pre>
BASE=/var/bigbluebutton/recording
STATUS=$BASE/status
source /etc/bigbluebutton/bigbluebutton-release

SERVLET_DIR=/var/lib/tomcat6/webapps
BBB_WEB=$(cat ${SERVLET_DIR}/bigbluebutton/WEB-INF/classes/bigbluebutton.properties | sed -n '/^bigbluebutton.web.serverURL/{s/.*\///;p}')

RECORDING_DIR=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(recording_dir\)/{s/.*recording_dir:[ ]*//;s/;//;p}')
PUBLISHED_DIR=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(published_dir\)/{s/.*published_dir:[ ]*//;s/;//;p}')
RAW_AUDIO_SRC=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(raw_audio_src\)/{s/.*raw_audio_src:[ ]*//;s/;//;p}')
RAW_VIDEO_SRC=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(raw_video_src\)/{s/.*raw_video_src:[ ]*//;s/;//;p}')
RAW_DESKSHARE_SRC=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(raw_deskshare_src\)/{s/.*raw_deskshare_src:[ ]*//;s/;//;p}')
RAW_PRESENTATION_SRC=$(cat /usr/local/bigbluebutton/core/scripts/bigbluebutton.yml | sed -n '/\(raw_presentation_src\)/{s/.*raw_presentation_src:[ ]*//;s/;//;p}')
*/

</pre>
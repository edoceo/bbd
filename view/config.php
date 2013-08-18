<?php

ksort($_SERVER);

// echo '<pre>';
// echo htmlspecialchars(print_r($_SERVER,true));
// echo '</pre>';

echo radix::info();



echo '<pre>';
echo htmlspecialchars(file_get_contents('/usr/local/bigbluebutton/core/scripts/bigbluebutton.yml'));
echo '</pre>';

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

</pre>

<?php
$mirror = new ReflectionClass('BBB');
radix::dump($mirror->getConstants());
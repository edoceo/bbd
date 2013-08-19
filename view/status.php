<?php

$_ENV['title'] = 'BBB System Status';

$list = glob(sprintf('%s/status/recorded/*.done',BBB::REC_PATH));
foreach ($list as $file) {
    if (!preg_match('|/([0-9a-f]+)\-(\d+)\.done|',$file,$m)) continue;
// 
//     $mid = $m[1];
//     $mts = $m[2];
// 
//     // Archived?
//     
}
// "#{recording_dir}/status/recorded/*.done"
// radix::dump($list);

echo count($list);

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

<h2>Recordings</h2>
<pre>
<?php
// BBB::listRecordings();
?>
</pre>

# // REC_PATH
<?php
$pid = trim(file_get_contents('/var/run/god.pid'));
?>
<h2>Post Processor</h2>
<h3>God Script</h3>
<p>pid:<?php echo $pid; ?></p>
<pre>
<?php
echo shell_exec("/bin/ps -e -opid,ppid,cmd|/bin/grep -e ruby 2>&1");;
/**
 7664     1 /usr/bin/ruby1.9.2 /usr/bin/god -c /etc/bigbluebutton/god/god.rb -P /var/run/god.pid -l /var/log/god.log
17277     1 ruby rap-worker.rb
17305 17277 ruby /usr/local/bigbluebutton/core/scripts/process/presentation.rb -m 7a26340d0ea2bf82cf80f012992f288a50257fc7-1376887175209
17647 16767 sh -c /bin/ps -e -opid,ppid,cmd|/bin/grep -e ruby 2>&1
17649 17647 /bin/grep -e ruby
*/
?>
</pre>
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
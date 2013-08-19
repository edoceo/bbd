<?php

$bbm = new BBB_Meeting($_GET['m']);


?>

<h2>Video</h2>
<video controls src="/presentation/<?php echo $_GET['m']; ?>/video/webcams.webm" type="video/webm" class="webcam" id="video" data-timeline-sources="/presentation/<?php echo $_GET['m']; ?>/slides_new.xml" data-width="402" data-height="300" style="width: 800px; height: 600px;"></video>

<h2>Slides</h2>

<h2>Slide Slider</h2>

<h2>Chat Detail</h2>







<h4>BBB Player</h4>
<p><a href="<?php echo $bbm->playURI(); ?>"><?php echo str_replace('http://',null,$bbm->playURI()); ?></a></p>
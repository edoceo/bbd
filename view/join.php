<?php
/**
	@file
	@brief Join a Meeting
*/


?>


<form method="post">
<fieldset>
<div>
<label>Meeting Name</label>
<input name="meeting_name" placeholder="your name" type="text">
</div>

<div>
<label>Your Name</label>
<input name="user_name" placeholder="your name" type="text">
</div>

<div>
<label>Moderator Password</label>
<input name="mpw" placeholder="moderator password" type="text">
</div>

<div>
<label>Attendee Password</label>
<input name="apw" placeholder="attendee password" type="text">
</div>

<?php
echo '<label><input name="rec" type="checkbox"> Record</label>';
?>

<h3>Preload Slides</h3>
<input name="file0" type="file">
<input name="file1" type="file">


<h2>Config XML</h2>
<textarea name="config_xml" style="min-height:256px;"><?php echo $this->config_xml; ?></textarea>

<h2>Layout XML</h2>
<textarea name="layout_xml" style="min-height:256px;"><?php echo $this->layout_xml; ?></textarea>


<button class="exec" name="a" type="submit" value="init">Start</button>

</form>
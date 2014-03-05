<?php
/**
	@file
	@brief A form to start the meeting
*/

echo '<div id="start-form">';
echo '<h3>Quick Start</h3>';
echo '<form action="' . radix::link('/meeting') . '" method="post">';
echo '<input name="m" placeholder="meeting name" type="text"><button class="exec" name="a" type="submit" value="start">Start</button>';

// Additional Fields
echo '<div id="start-opts">';
echo '<input name="mpw" placeholder="moderator password" type="text"><br>';
echo '<input name="apw" placeholder="attendee password" type="text"><br>';
echo '<label><input name="rec" type="checkbox"> Record</label>';
echo '</div>';

echo '</form>';
echo '</div>';

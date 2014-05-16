<?php
/**
	@file
	@brief Display Information about a Meeting
*/

if (!acl::has_access($_SESSION['uid'], 'meeting-monitor')) {
       radix::redirect('/');
}

$mid = $_GET['m'];

try {
	$bbm = new BBB_Meeting($mid);
	$evt_list = $bbm->getEvents();
} catch (Exception $e) {
	echo '<p class="fail">Unable to load meeting: ' . $mid . '</p>';
	echo '<p class="info">' . $e->getMessage() . '</p>';
	return(0);
}

$_ENV['title'] = $bbm->name;
?>
<style>
.user-list button {
	margin:0px;
	min-width:64px;
	width:64px;
}
</style>
<?php

echo '<h2>Started ' . strftime('%Y-%m-%d %H:%M:%S', $bbm->time_alpha / 1000) . ' to ' . strftime('%Y-%m-%d %H:%M:%S', $bbm->time_omega / 1000) . '</h2>';

$usr_list = $bbm->getUsers();
if (count($usr_list)) {
	echo '<table class="user-list">';
	foreach ($usr_list as $usr) {
		echo '<tr>';
		echo '<td>' . $usr['name'] . '</td>';
		echo '<td>' . $usr['mode'] . '</td>';
		echo '<td><button class="good user-command" data-user="' . $usr['call_id'] . '" data-command="m+">Mic+</button></td>';
		echo '<td><button class="warn user-command" data-user="' . $usr['call_id'] . '" data-command="m-">Mic-</button></td>';
		echo '<td><button class="fail user-command" data-user="' . $usr['call_id'] . '" data-command="m0">Mute</button></td>';
		echo '<td><button class="good user-command" data-user="' . $usr['call_id'] . '" data-command="v+">Vol+</button></td>';
		echo '<td><button class="warn user-command" data-user="' . $usr['call_id'] . '" data-command="v-">Vol-</button></td>';
		echo '<td><button class="fail user-command" data-user="' . $usr['call_id'] . '" data-command="v0">Deaf</button></td>';
		echo '<td><button class="fail user-command" data-user="' . $usr['call_id'] . '" data-command="ko">Kick</button></td>';
		echo '</tr>';
		// radix::dump($usr);
	}
	echo '</table>';
}

echo '<h3>Events</h3>';
echo '<pre style="font-size:12px;">';

// New => Old
uasort($evt_list, function($a,$b) {
	return ($a['time'] < $b['time']);
});

foreach ($evt_list as $e) {

    // Skip List
    switch ($e['module'] . '/' . $e['event']) {
    case 'VOICE/ParticipantTalkingEvent':
    case 'PRESENTATION/CursorMoveEvent':
    // case 'PRESENTATION/GotoSlideEvent':
    case 'PRESENTATION/ResizeAndMoveSlideEvent':
        continue 2;
    }

    $time = floor($e['time'] / 1000);

    $x = array('event-line');
    if (!empty($e['userId'])) $x[] = 'user-' . strval($e['userId']);
    if (!empty($e['module'])) $x[] = 'module-' . strval($e['module']);
    if (!empty($e['event'])) $x[] = 'event-' . strval($e['event']);
    echo '<span class="' . implode(' ',$x) . '">';

    //if (null == $time_alpha) {
    //    $time_alpha = $e['time'];
    //    echo strftime('%H:%M:%S',$time) . '.' . sprintf('%03d',$e['time'] - ($time * 1000));
    //} else {
         $s = ($e['time'] - $bbm->time_alpha) / 1000;
         $m = floor($s / 60);
         $s = $s - ($m * 60);
    //     // echo '+' . sprintf('% 4d:%06.3f',$m,$s);
         echo '<span class="time-hint" data-ts="' . intval((($e['time'] - $bbm->time_alpha) / 1000)) . '" title="' . (($e['time'] - $bbm->time_alpha) / 1000) . '">+' . sprintf('% 4d:%06.3f',$m,$s) . '</span>';
    //     // echo '<span class="time-hint" data-ts="' . intval((($e['timestamp'] - $time_alpha) / 1000)) . '" title="' . (($e['timestamp'] - $time_alpha) / 1000) . '">+' . sprintf('% 9.3f', ($e['timestamp'] - $time_alpha) / 1000) . '</span>';
    //}
    echo ' ';

	echo $e->toString();

    echo "</span>\n";
}
echo '</pre>';

?>

<script>
$(function() {
	// Highlight this Users Row
	$('.user-pick').on('click', function(e) {
		var want = 'user-' + $(this).data('id');
		$('.event-line').each(function(i, node) {
			$(node).css({color:'#333'});
			if ($(node).hasClass(want)) {
				$(node).css({color:'#c00'});
			}
			// var node_s = $(node).data('ts');
			// if (node_s < s) {
			//   $(node).css('color', '#999');
			// } else if (node_s == s) {
			//   $(node).css('color', '#f00');
			// } else if (node_s > s) {
			//   $(node).css('color', 'default');
			// }
		});
	});

	$('.user-command').on('click', function(e) {
		var uid = $(this).data('user');
		var cmd = $(this).data('command');
		alert(uid + ' :: ' + cmd);
	});

});
</script>

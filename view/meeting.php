<?php
/**
	@file
	@brief Display Information about a Meeting
*/

if (!acl::has_access($_SESSION['uid'], 'view-meeting')) {
       radix::redirect('/');
}

$mid = $_GET['m'];

try {
	$bbm = new BBB_Meeting($mid);
} catch (Exception $e) {
	echo '<p class="fail">Unable to load meeting: ' . $mid . '</p>';
	echo '<p class="info">' . $e->getMessage() . '</p>';
	return(0);
}


$_ENV['title'] = $bbm->name;

// radix::dump($mid);

// Video Player
echo '<div id="video-wrap">';
echo '<h1><span id="meeting-code">' . $bbm->code . '</span>/<span id="meeting-name">' . $bbm->name . '</span> <span class="video-size"></span></h1>';
echo '<div class="video-show">';
//  class="webcam" id="video" data-timeline-sources="/presentation/' . $mid . '/slides_new.xml" data-width="402" data-height="300"
echo '<video autobuffer controls id="video-play" src="/presentation/' . $mid . '/video/webcams.webm" type="video/webm"></video>';
echo '<p><a href="/playback/presentation/playback.html?meetingId=' . $mid . '">' . ICON_WATCH . '</a></p>';
echo '</div>';
echo '</div>';

// Buttons
echo '<form method="post">';
echo '<button class="exec" name="a" value="download">Download <i class="fa fa-archive" title="Download Archive"></i></button>';
echo '<button class="warn" name="a" value="rebuild"><i class="fa fa-refresh"></i> Rebuild</button>';
// echo '<button name="a" value="republish">Republish</button>';
echo '<button class="fail" name="a" value="delete"><i class="fa fa-trash-o"></i> Delete</button>';
echo '</form>';


// $base = "/var/bigbluebutton/recording/raw/$mid";
// radix::dump(glob("$base/*"));

// foreach (array('audio','video','presentation','deskshare') as $chk) {
//     echo '<h3>' . ucfirst($chk) . '</h3>';
//     echo '<pre>' . print_r(glob("$base/$chk"),true) . '</pre>';
// }

ob_start();

// $time_alpha = $time_omega = null;
// $file = BBB::RAW_ARCHIVE_PATH . "/{$mid}/events.xml";
// $xml = simplexml_load_file($file);
// foreach ($xml->event as $e) {
$evt_list = $bbm->getEvents();
foreach ($evt_list as $e) {

    $time = floor($e['timestamp'] / 1000);
    $time_omega = $e['timestamp'];

    // Skip List
    switch ($e['module'] . '/' . $e['event']) {
    case 'VOICE/ParticipantTalkingEvent':
    case 'PRESENTATION/CursorMoveEvent':
    case 'PRESENTATION/ResizeAndMoveSlideEvent':
        continue 2;
    }

    $x = array('event-line');
    if (!empty($e['user_id'])) $x[] = 'user-' . $e['user_id'];
    if (!empty($e['module'])) $x[] = 'module-' . $e['module'];
    if (!empty($e['event'])) $x[] = 'event-' . $e['event'];
    echo '<span class="' . implode(' ',$x) . '">';

    // Friendly Format of Time
    if (null == $time_alpha) {
        $time_alpha = $e['timestamp'];
        echo strftime('%H:%M:%S',$time) . '.' . sprintf('%03d',$e['timestamp'] - ($time * 1000));
    } else {
        $s = ($e['timestamp'] - $time_alpha) / 1000;
        $m = floor($s / 60);
        $s = $s - ($m * 60);
        // echo '+' . sprintf('% 4d:%06.3f',$m,$s);
        echo '<span class="time-hint" data-ts="' . intval((($e['timestamp'] - $time_alpha) / 1000)) . '" title="' . (($e['timestamp'] - $time_alpha) / 1000) . '">+' . sprintf('% 4d:%06.3f',$m,$s) . '</span>';
        // echo '<span class="time-hint" data-ts="' . intval((($e['timestamp'] - $time_alpha) / 1000)) . '" title="' . (($e['timestamp'] - $time_alpha) / 1000) . '">+' . sprintf('% 9.3f', ($e['timestamp'] - $time_alpha) / 1000) . '</span>';
    }

    echo ' ';

    echo $e->toString();

    echo "</span>\n";
}

$buf = ob_get_contents();
ob_end_clean();

echo '<h3>Events</h3>';
echo '<p>Started ' . strftime('%Y-%m-%d %H:%M:%S', $time_alpha/1000) . ' to ' . strftime('%Y-%m-%d %H:%M:%S', $time_omega/1000) . '</p>';

$user_list = $bbm->getUsers();
foreach ($user_list as $k=>$u) {
	echo '<button class="user-pick" data-id="' . $k . '">' . $u['name'] . '</button>';
}

echo '<pre style="font-size:12px;">';
echo $buf;
echo '</pre>';

// radix::dump(draw::$user_list);

// echo '<h2>Source Stat</h2>';
// radix::dump($bbm->sourceStat());
//
// echo '<h2>Archive Stat</h2>';
// radix::dump($bbm->archiveStat());
//
// echo '<h2>Process Stat</h2>';
// radix::dump($bbm->processStat());

$stat = $bbm->stat();
$size = 0;
$size_sum = 0;

// Sources:
echo '<h2>Meeting Sources</h2>';
echo '<h3><i class="fa fa-plus-square-o" id="file-raw-exec"></i> Raw Files</h3><div id="file-raw-list"></div>';
echo '<h3><i class="fa fa-plus-square-o" id="file-arc-exec"></i> Archive Files</h3><div id="file-arc-list"></div>';
echo '<h3><i class="fa fa-plus-square-o" id="file-all-exec"></i> All Files</h3><div id="file-all-list"></div>';

echo '<table>';

// Raw Audio
$size_sum += _draw_file_list($stat['source']['audio'],ICON_AUDIO);
unset($stat['source']['audio']);

// Source Videos
$size_sum += _draw_file_list($stat['source']['video'],ICON_VIDEO);
unset($stat['source']['video']);

// SourceSlide
$size_sum += _draw_file_list($stat['source']['slide'],ICON_SLIDE);
unset($stat['source']['slide']);

$size_sum += _draw_file_list($stat['source']['share'],ICON_SHARE);
unset($stat['source']['share']);

echo '<tr><td colspan="4"><h3>Archive Files</h3></td></tr>';

// Archive File Details
$size_sum += _draw_file_list($stat['archive']['audio'],ICON_AUDIO);
unset($stat['archive']['audio']);

$size_sum += _draw_file_list($stat['archive']['video'],ICON_VIDEO);
unset($stat['archive']['video']);

$size_sum += _draw_file_list($stat['archive']['slide'],ICON_SLIDE);
unset($stat['archive']['slide']);

$size_sum += _draw_file_list($stat['archive']['share'],ICON_SHARE);
unset($stat['archive']['share']);

$size_sum += _draw_file_list($stat['archive']['event'],ICON_EVENT);
unset($stat['archive']['event']);

foreach ($stat['process'] as $k=>$v) {
	// radix::dump($v);
	echo '<tr>';
	echo '<td>' . $k . '</td>';
	echo '<td colspan="2">' . $v['file'] . '</td>';
	echo '</tr>';
}

echo '<tr><td>&nbsp;</td><td>' . $size_sum . 'b</td>';

echo '</table>';

echo '<h2>Logs</h2>';
$file = '/var/log/bigbluebutton/presentation/process-' . $mid . '.log';
if (is_file($file)) {
	radix::dump(file_get_contents($file));
}

// radix::dump($stat);

/**
	Draws Rows of Files, Returns Size of Files
*/
function _draw_file_list($list,$icon)
{
	if (empty($list)) return;
	if (!is_array($list)) return;
	if (0 == count($list)) return;

	$size = 0;
	foreach ($list as $f) {
		$x = filesize($f);
		echo '<tr>';
		echo '<td>' . $icon . '</td>';
		echo '<td>' . $x . '</td>';
		echo '<td title="' . $f . '"><a href="' . radix::link('/download?f=' . $f) . '">' . basename($f) . '</a></td>';
		echo '<td>' . md5_file($f) . '</td>';
		echo '</tr>';
		$size += $x;
	}
	echo '<tr>';
	echo '<td>'. $icon . '</td>';
	echo '<td>' . $size . '</td>';
	echo '<td>' . count($list) . ' Files</td>';
	echo '</tr>';
	return $size;
}

// class draw
// {
//     public static $user_list;
//     // private static $call_list;
// 
//     static function participant($e)
//     {
// 
//         switch ($e['eventname']) {
//         case 'ParticipantJoinEvent':
//             self::$user_list[ strval($e->userId) ] = array(
//                 'name' => strval($e->name),
//             );
//             echo strval($e->role) . '/' . strval($e->name) . ' (' . strval($e->status) . ')';
//             break;
//         case 'ParticipantStatusChangeEvent':
//             echo 'Now: ' . strval($e->status) . '=' . strval($e->value);
//             break;
//         case 'EndAndKickAllEvent':
//             // Ignore
//             break;
//         default:
//             echo "Not Handled: {$e['eventname']}\n";
//             radix::dump($e);
//         }
//     }
// 
//     //static function voice($e)
//     //{
//     //
//     //    switch ($e['eventname']) {
//     //    case 'ParticipantJoinedEvent':
//     //        echo strval($e->bridge) . '/' . strval($e->participant) . '/' . strval($e->callername) . '; Muted: ' . strval($e->muted);
//     //        $uid = substr($e->callername,0,12);
//     //        self::$user_list[$uid]['call'] = intval($e->participant);
//     //        break;
//     //    default:
//     //        echo "Not Handled: {$e['eventname']}";
//     //        radix::dump($e);
//     //    }
//     //}
// }

?>

<script>
var mid = '<?php echo $mid ?>';
var vid = document.getElementById('video-play');
$('#video-play').on('click', function(e) {
	var self = $(this);
	switch (self.data('mode')) {
	case 'play':
		// Stop It
		self[0].pause();
		break;
	case 'stop':
	default:
		// Play it
		self[0].play();
	}
});
$('#video-play').on('pause', function(e) {
	$(this).data('mode', 'stop');
});
$('#video-play').on('play', function(e) {
	$(this).data('mode', 'play');
});
$('#video-play').on('canplay', function(e) {
	$('.video-size').css({'color':'#00cc00'});
});

// vid.addEventListener('click', function(e) {
vid.addEventListener('durationchange', function(e) {
	// debugger;
	$('.video-size').html(e.target.duration);
});
vid.addEventListener('timeupdate', function(e) {
       $('.time-hint').each(function(i, node) {
		   $(node).css('color', 'default');
       });

       // debugger;
       console.log('Offset: ' + e.currentTarget.currentTime);
       var s = parseInt(e.currentTarget.currentTime);
       if (s < 1) return;

       var once = false;
       $('.time-hint').each(function(i, node) {
               var node_s = $(node).data('ts');
               if (node_s < s) {
                       $(node).css('color', '#999');
               } else if (node_s == s) {
                       $(node).css('color', '#f00');
               } else if (node_s > s) {
                       $(node).css('color', 'default');
               }
       });

       // Advance the Scrolling of the Events Window
}, false);

$(function() {

	$('#meeting-name').on('click', function() {
		var mn = $(this);
		switch (mn.data('mode')) {
		case 'edit':
			// Editing, Do Nothing
			// $(this).data('mode', 'view');
			// $(this).html( $('#meeting-name-text').val() );
			break;
		default:
			var mne = $('<input id="meeting-name-text">');
			mne.val(mn.html());

			mn.data('mode', 'edit');
			mn.html(mne);

			mne.on('keypress', function(e) {
				switch (e.keyCode) {
				case 13:
					$('#meeting-name').data('mode', 'view').html( $('#meeting-name-text').val() );
					// @todo POST/Save
					break;
				}
			});
			mne.focus();
			mne.select();
		}
	});

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

	$('#file-all-exec').on('click', function(e) {
		var self = this;
		switch ($(self).data('view-state')) {
		case 'open':
			$('#file-all-list').empty();
			$(self).addClass('fa-plus-square-o');
			$(self).removeClass('fa-minus-square-o');
			$(self).data('view-state', 'shut');
			break;
		case 'shut':
		default:
			var data = {
				id:mid,
				src:'all'
			};
			$('#file-all-list').load(bbd.base + '/ajax/file', data, function() {
				$(self).removeClass('fa-plus-square-o');
				$(self).addClass('fa-minus-square-o');
				$(self).data('view-state', 'open');
			});
		}
	});

	$('#file-raw-exec').on('click', function(e) {
		var self = this;
		var data = {
			id:mid,
			src:'raw'
		};
		$('#file-raw-list').load(bbd.base + '/ajax/file', data, function() {
			$(self).removeClass('fa-plus-square-o');
			$(self).addClass('fa-minus-square-o');
		});
	});

});
</script>

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

// Video Player
echo '<div id="video-wrap">';
echo '<h1><span id="meeting-code">' . $bbm->code . '</span>/<span id="meeting-name">' . $bbm->name . '</span> <span class="video-size"></span></h1>';
// echo '<p>Started ' . strftime('%Y-%m-%d %H:%M:%S', $bbm->time_alpha / 1000) . ' to ' . strftime('%Y-%m-%d %H:%M:%S', $bbm->time_omega / 1000) . '</p>';
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
//	 echo '<h3>' . ucfirst($chk) . '</h3>';
//	 echo '<pre>' . print_r(glob("$base/$chk"),true) . '</pre>';
// }

echo '<h3><i class="fa fa-plus-square-o" id="meeting-event-show"></i> Events</h3>';
echo '<div id="meeting-event-list"></div>';

// radix::dump(draw::$user_list);

// echo '<h2>Source Stat</h2>';
// radix::dump($bbm->sourceStat());
//
// echo '<h2>Archive Stat</h2>';
// radix::dump($bbm->archiveStat());
//
// echo '<h2>Process Stat</h2>';
// radix::dump($bbm->processStat());

// Sources:
echo '<h2>Meeting Sources</h2>';
echo '<h3><i class="fa fa-plus-square-o" id="file-src-exec"></i> Source Files</h3><div id="file-src-list" style="display:none;"></div>';
echo '<h3><i class="fa fa-plus-square-o" id="file-arc-exec"></i> Archive Files</h3><div id="file-arc-list" style="display:none;"></div>';
echo '<h3><i class="fa fa-plus-square-o" id="file-all-exec"></i> All Files</h3><div id="file-all-list" style="display:none;"></div>';

if (false) {
	
	foreach ($stat['process'] as $k=>$v) {
		// radix::dump($v);
		echo '<tr>';
		echo '<td>' . $k . '</td>';
		echo '<td colspan="2">' . $v['file'] . '</td>';
		echo '</tr>';
	}
	
	echo '<tr><td>&nbsp;</td><td>' . $size_sum . 'b</td>';
	
	echo '</table>';
}

// Log Details
$file = '/var/log/bigbluebutton/presentation/process-' . $mid . '.log';
if (is_file($file)) {
	echo '<h2>Logs <small><a href="' . Radix::link('/download?f=' . $file) . '">' . basename($file) . '</a></small></h2>';
	echo '<pre style="font-size:12px; max-height: 20em; overflow:auto;">';
	echo htmlspecialchars(file_get_contents($file), ENT_QUOTES, 'utf-8', true);
	echo '</pre>';
} else {
	echo '<h2>Logs: Not Found</h2>';
}

// class draw
// {
//	 public static $user_list;
//	 // private static $call_list;
// 
//	 static function participant($e)
//	 {
// 
//		 switch ($e['eventname']) {
//		 case 'ParticipantJoinEvent':
//			 self::$user_list[ strval($e->userId) ] = array(
//				 'name' => strval($e->name),
//			 );
//			 echo strval($e->role) . '/' . strval($e->name) . ' (' . strval($e->status) . ')';
//			 break;
//		 case 'ParticipantStatusChangeEvent':
//			 echo 'Now: ' . strval($e->status) . '=' . strval($e->value);
//			 break;
//		 case 'EndAndKickAllEvent':
//			 // Ignore
//			 break;
//		 default:
//			 echo "Not Handled: {$e['eventname']}\n";
//			 radix::dump($e);
//		 }
//	 }
// 
//	 //static function voice($e)
//	 //{
//	 //
//	 //	switch ($e['eventname']) {
//	 //	case 'ParticipantJoinedEvent':
//	 //		echo strval($e->bridge) . '/' . strval($e->participant) . '/' . strval($e->callername) . '; Muted: ' . strval($e->muted);
//	 //		$uid = substr($e->callername,0,12);
//	 //		self::$user_list[$uid]['call'] = intval($e->participant);
//	 //		break;
//	 //	default:
//	 //		echo "Not Handled: {$e['eventname']}";
//	 //		radix::dump($e);
//	 //	}
//	 //}
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

function mark_open(node)
{
	$(node)
		.addClass('fa-minus-square-o')
		.removeClass('fa-plus-square-o')
		.data('view-state', 'open');
}

function mark_shut(node)
{
	$(node)
		.addClass('fa-plus-square-o')
		.removeClass('fa-minus-square-o')
		.data('view-state', 'shut');
}


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
	
	$('#meeting-event-show').on('click', function(e) {
		var self = this;
		switch ($(self).data('view-state')) {
		case 'open':
			$('#meeting-event-list').empty();
			mark_shut(self);
			break;
		case 'shut':
		default:
			var data = {
				m: mid,
			};
			$('#meeting-event-list').load(bbd.base + '/ajax/events', data, function() {
				$('#meeting-event-list').show();
				mark_open(self);
			});
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

	$('#file-src-exec').on('click', function(e) {

		var self = this;

		switch ($(self).data('view-state')) {
		case 'open':
			$('#file-src-list').hide();
			mark_shut(self);
			break;
		default:
			var data = {
				m: mid,
				k: 'source'
			};
			$('#file-src-list').load(bbd.base + '/ajax/file', data, function() {
				$('#file-src-list').show();
				mark_open(self);
			});
			break;
		}

	});

	$('#file-arc-exec').on('click', function(e) {

		var self = this;

		switch ($(self).data('view-state')) {
		case 'open':
			$('#file-arc-list').hide();
			mark_shut(self);
			break;
		default:
			var data = {
				m: mid,
				k: 'archive'
			};
			$('#file-arc-list').load(bbd.base + '/ajax/file', data, function() {
				$('#file-arc-list').show();
				mark_open(self);
			});
			break;
		}
	});

	$('#file-all-exec').on('click', function(e) {
		var self = this;
		switch ($(self).data('view-state')) {
		case 'open':
			$('#file-all-list').empty();
			mark_shut(self);
			break;
		case 'shut':
		default:
			var data = {
				id:mid,
				src:'all'
			};
			$('#file-all-list').load(bbd.base + '/ajax/file', data, function() {
				$('#file-all-list').show();
				mark_open(self);
			});
		}
	});
});
</script>

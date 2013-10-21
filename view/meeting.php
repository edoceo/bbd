<?php

$mid = $_GET['m'];

$bbm = new BBB_Meeting($mid);
$_ENV['title'] = $bbm->name;

// radix::dump($mid);
echo '<h1>' . $_ENV['title'] . '</h1>';
echo '<form method="post">';
echo '<button name="a" value="rebuild">Rebuild</button>';
echo '<button name="a" value="republish">Republish</button>';
echo '<button name="a" value="delete">Delete</button>';
echo '<button name="a" value="download">Download <i class="icon-archive" title="Download Archive"></i></button>';
// echo '<button name="a" value="rebuild">Rebuild</button>';
echo '</form>';

echo '<h2>Video</h2>';
echo '<div style="margin:0 auto;width:800px;">';
echo '<video autobuffer controls src="/presentation/' . $mid . '/video/webcams.webm" type="video/webm" class="webcam" id="video" data-timeline-sources="/presentation/' . $mid . '/slides_new.xml" data-width="402" data-height="300" style="width: 800px; height: 600px;">';
echo '</video>';
echo '<p><a href="/playback/presentation/playback.html?meetingId=' . $mid . '">' . ICON_WATCH . '</a></p>';
echo '</div>';

// $base = "/var/bigbluebutton/recording/raw/$mid";
// radix::dump(glob("$base/*"));

// foreach (array('audio','video','presentation','deskshare') as $chk) {
//     echo '<h3>' . ucfirst($chk) . '</h3>';
//     echo '<pre>' . print_r(glob("$base/$chk"),true) . '</pre>';
// }

echo '<h3>Events</h3>';
echo '<pre>';
// echo '<pre>' . print_r(glob("$base/*.xml"),true) . '</pre>';
$user_list = array();
$time_alpha = null;
$file = BBB::RAW_ARCHIVE_PATH . "/{$mid}/events.xml";
$xml = simplexml_load_file($file);
foreach ($xml->event as $e) {

    // Skip List
    switch ($e['module'] . '/' . $e['eventname']) {
    case 'VOICE/ParticipantTalkingEvent':
    case 'PRESENTATION/CursorMoveEvent':
    case 'PRESENTATION/ResizeAndMoveSlideEvent':
        continue 2;
    }

    $time = floor($e['timestamp'] / 1000);

    if (null == $time_alpha) {
        $time_alpha = $e['timestamp'];
        echo strftime('%H:%M:%S',$time) . '.' . sprintf('%03d',$e['timestamp'] - ($time * 1000));
    } else {
        $s = ($e['timestamp'] - $time_alpha) / 1000;
        $m = floor($s / 60);
        $s = $s - ($m * 60);
        echo '+' . sprintf('% 4d:%06.3f',$m,$s);
    }
    echo ' ';

    echo sprintf('%-16s',$e['module']);
    echo sprintf('%-32s',$e['eventname']);

    switch ($e['module']) {
    case 'PARTICIPANT':
        draw::participant($e);
        break;
    case 'PRESENTATION':
        draw::presentation($e);
        break;
    case 'VOICE':
        draw::voice($e);
        break;
    case 'WEBCAM':
        draw::webcam($e);
        break;
    case 'CHAT':
        draw::chat($e);
        break;
    default:
        echo 'Not Handled';
    }

    echo "\n";
}
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
		echo '<td title="' . $f . '">' . basename($f) . '</td>';
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

class draw
{
    public static $user_list;
    // private static $call_list;

    static function participant($e)
    {

        switch ($e['eventname']) {
        case 'ParticipantJoinEvent':
            self::$user_list[ strval($e->userId) ] = array(
                'name' => strval($e->name),
            );
            echo strval($e->role) . '/' . strval($e->name) . ' (' . strval($e->status) . ')';
            break;
        case 'ParticipantStatusChangeEvent':
            echo 'Now: ' . strval($e->status) . '=' . strval($e->value);
            break;
        case 'AssignPresenterEvent':
            // echo 'Now: ' . strval($e->status) . '=' . strval($e->value);
            break;
        case 'ParticipantLeftEvent':
            echo self::$user_list[ strval($e->userId) ]['name'];
            break;
        case 'EndAndKickAllEvent':
            // Ignore
            break;
        default:
            echo "Not Handled: {$e['eventname']}\n";
            radix::dump($e);
        }
    }

    static function presentation($e)
    {
        switch ($e['eventname']) {
        case 'ResizeAndMoveSlideEvent':
        case 'SharePresentationEvent':
        case 'GotoSlideEvent':
        case 'CursorMoveEvent':
            break;
        default:
            echo "Not Handled: {$e['eventname']}";
        }
    }

    static function voice($e)
    {

        switch ($e['eventname']) {
        case 'ParticipantJoinedEvent':
            echo strval($e->bridge) . '/' . strval($e->participant) . '/' . strval($e->callername) . '; Muted: ' . strval($e->muted);
            $uid = substr($e->callername,0,12);
            self::$user_list[$uid]['call'] = intval($e->participant);
            break;
        case 'ParticipantTalkingEvent':
            foreach (self::$user_list as $k=>$v) {
                if (intval($v['call']) == intval($e->participant)) {
                    echo "User: {$v['name']}; ";
                }
            }
            echo strval($e->bridge) . '/' . strval($e->participant);
            break;
        case 'ParticipantLeftEvent':
            echo strval($e->bridge) . '/' . strval($e->participant);
            foreach (self::$user_list as $k=>$v) {
                if (intval($v['call']) == intval($e->participant)) {
                    echo "; User: {$v['name']}";
                }
            }
            break;
        case 'ParticipantMutedEvent':
            echo strval($e->bridge) . '/' . strval($e->participant);
            foreach (self::$user_list as $k=>$v) {
                if (intval($v['call']) == intval($e->participant)) {
                    echo "; User: {$v['name']}";
                }
            }
            break;
        case 'StartRecordingEvent':
        case 'StopRecordingEvent':
            echo strval($e->bridge) . '; File: ' . strval($e->filename);
            break;
        default:
            echo "Not Handled: {$e['eventname']}";
            radix::dump($e);
        }
    }

    static function webcam($e)
    {
        switch ($e['eventname']) {
        case 'StartWebcamShareEvent':
        case 'StopWebcamShareEvent':

            break;
        default:
            echo "Not Handled: {$e['eventname']}";
        }
    }

    static function chat($e)
    {
        switch ($e['eventname']) {
        case 'PublicChatEvent':
            break;
        default:
            echo "Not Handled: {$e['eventname']}";
        }
    }
}




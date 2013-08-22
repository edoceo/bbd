<?php
/**

*/

class BBB_Meeting
{
    private $_id;
    private $_external_name;

    public $date;
    public $code;
    public $name;

    /**
        Meeting
        @param $mid Meeting Identifier
    */
    function __construct($mid)
    {
        $this->_id = $mid;
        $this->_init();
        $x = array();
        $x[] = $this->code;
        $x[] = $this->_external_name;
        $x = implode('/',$x);
        if (strlen($x) > 1) $this->name = $x;

        // Date
        if (preg_match('/\-(\d+)$/',$this->_id,$m)) {
            $this->date = strftime('%Y-%m-%d %H:%M', intval($m[1]) / 1000);
        }
    }

    function playURI()
    {
        return '/playback/presentation/playback.html?meetingId=' . $this->_id;
    }

    /**
        Trigger the Meeting for Rebuild
    */
    function rebuild()
    {
        $type = 'presentation';

        $list = array();
        $list[] = sprintf('%s/process/%s/%s',BBB::BASE,$type,$this->_id);
        $list[] = sprintf('%s/publish/%s/%s',BBB::BASE,$type,$this->_id);
        $list[] = sprintf('%s/processed/%s/%s',BBB::BASE,$type,$this->_id);
        $list[] = sprintf('%s/published/%s/%s',BBB::BASE,$type,$this->_id);
        $list[] = sprintf('%s/unpublished/%s/%s',BBB::BASE,$type,$this->_id);
        $list[] = sprintf('%s/processed/%s-%s.done',BBB::STATUS,$this->_id,$type);

        $buf = null;
        foreach ($list as $path) {
            $buf.= shell_exec("rm -fr $path 2>&1");
        }

        return $buf;

    }

    #	A -- Audio
    #	P -- Presentation
    #	V -- Video
    #	D -- Desktop
    #	E -- Events
    function archiveStat()
    {
        $ret = array(
            'audio' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/audio/*"),
            'video' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/video/*"),
            'slide' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/presentation/*/*"),
            'share' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/deskshare/*"),
            'event' => glob(BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/events.xml"),
        );
        return $ret;
    }
	
    /**
		Stats the Done Files
    */
    function processStat()
    {
        $ret = array();

        $list = array('recorded','archived','sanity');
        foreach ($list as $k) {

            $file = sprintf('%s/%s/%s.done',BBB::STATUS,$k,$this->_id);
            if (!is_file($file)) continue;

            $ret[$k] = array('file' => $file);

            if (is_file($file)) {
                $ret[$k]['time_alpha'] = filemtime($ret[$k]['file']);
                $ret[$k]['time_omega'] = filectime($ret[$k]['file']);
            }

        }

        return $ret;
    }

    /**
        Returns Information on the Sources
    */
    function sourceStat()
    {
        // echo '<pre>' . print_r(glob("/var/freeswitch/meetings/{$this->_id}-*"),true) . '</pre>';
        //
        // echo '<h3><i class="icon-facetime-video"></i> Raw Video</h3>';
        // echo '<pre>' . print_r(glob("/usr/share/red5/webapps/video/streams/{$this->_id}"),true) . '</pre>';
        //
        // echo '<h3> Raw Presentation Slides</h3>';
        // echo '<pre>' . print_r(glob("/var/bigbluebutton/{$this->_id}/{$this->_id}/*"),true) . '</pre>';

        // echo '<h3> Desk Share</h3>';
        // echo '<pre>' . print_r(glob("var/bigbluebutton/deskshare/{$this->_id}"),true) . '</pre>';

        $ret = array(
            'audio' => glob(BBB::RAW_AUDIO_SOURCE . "/{$this->_id}-*"),
            'video' => glob(BBB::RAW_VIDEO_SOURCE . "/{$this->_id}/*"),
            'slide' => glob(BBB::RAW_SLIDE_SOURCE . "/{$this->_id}/{$this->_id}/*/*"),
            'share' => glob(BBB::RAW_SHARE_SOURCE . "/{$this->_id}"),
        );
        return $ret;
    }

    public function stat()
    {
    	$ret = array(
    		'source' => $this->sourceStat(),
    		'archive' => $this->archiveStat(),
    		'process' => $this->processStat(),
		);
    	return $ret;
    }

    /**

    */
    private function _init()
    {
        // Look for My Cached Data
        $file = "/var/bigbluebutton/{$this->_id}/bbd-cache.bin";
        if (is_file($file)) {
            $data = unserialize(file_get_contents($file));
            $this->code = $data['id'];
            $this->_external_name = $data['name'];
        }

        $file = BBB::RAW_ARCHIVE_PATH . "/{$this->_id}/events.xml";
        if (is_file($file)) {
            $name = array();
            $fh = fopen($file,'r');
            $buf = fread($fh,2048);

            if(preg_match('/meetingId="(.+?)"/',$buf,$m)) {
                $this->code = $m[1];
            }

            if(preg_match('/meetingName="(.+?)"/',$buf,$m)) {
                $this->_external_name = $m[1];
            }
            fclose($fh);
        }

    }
}
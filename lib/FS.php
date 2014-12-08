<?php
/**
    @file
    @brief Interact with FreeSWTICH via PHP w/o ESL

    @see http://wiki.freeswitch.org/wiki/PHP_Event_Socket

*/

// class Radix_Service_Freeswitch
class FS
{
    protected $_s; // Socket
    protected $_ms = 32; // ms for timeout

    /**
        Construct an FS thing
    */
    function __construct($host='localhost:8021',$pass='ClueCon')
    {
        $this->_s = stream_socket_client($host,$en,$es);
        stream_set_blocking($this->_s,true);
        // stream_set_read_buffer($this->_s,0);
        // stream_set_write_buffer($this->_s,0);
        stream_set_timeout($this->_s,0,$this->_ms*1000);
        $this->recv();
        $this->_send("auth $pass");
        $this->recv();
    }

    /**
        This is the one where we wait for events
    */
    public function recv()
    {
        $buf = $this->_recv();
        // echo "< $buf\n";
        // $ret = array_combine(array('head','body'),explode("\n\n",$buf));
        $ret = array();
        // print_r($ret);
        if (preg_match_all('/^([\w\-]+): (.+)$/m',$buf,$m)) {
            $ret['head'] = array_combine($m[1],$m[2]);
        }
        if ($ret['head']['Content-Type']=='auth/request') {
            // Anything?
        }
        if (!empty($ret['head']['Content-Length'])) {
            $ret['body'] = substr($buf,intval($ret['head']['Content-Length'])*-1);
			if (preg_match('/^\+OK ([0-9a-z\-]{36})/',$ret['body'],$m)) {
				$ret['uuid'] = $m[1];
			}
        }
        // print_r($ret);
        return $ret;
    }
    /**
        Send, and waits for reply
    */
    public function send($buf)
    {
        // echo "> $buf\n";
        $this->_send($buf);
        return $this->recv();
    }
    /**
        Internal Reciever, buffers for full response
    */
    protected function _recv()
    {
        if (feof($this->_s)) {
            fclose($this->_s);
            $this->_s = null;
        }
        $ret = null;
        //while (!feof($this->_s)) {
          while ($buf = fgets($this->_s,4096)) {
              // echo "buf:$buf";
              $ret.= $buf;
          }
        //}
        return $ret;
    }
    /**
        Internal Sender
    */
    protected function _send($buf)
    {
        $buf = trim($buf) . "\n\n";
        fwrite($this->_s,$buf);
    }
    /**
        xml2array
        @note this is crap
    */
    public static function x2a($xml)
    {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }
        $ret = array();
        foreach ($xml as $e) {
            $n = $e->getName();
            foreach ($e->children() as $c) {
                $ret[$n] = self::x2a($e);
            }
            $ret[$n][] = strval($e);
            if (count($ret[$n]) == 1) {
                $ret[$n] = trim($ret[$n][0]);
            }
        }
        return $ret;
    }
}

<?php
/**
    @file
    @brief Radix PHP Development Toolkit
    @see http://radix.edoceo.com/

    @copyright 2001-2012 Edoceo, Inc.
    @package Radix

    @mainpage Radix PHP Toolkit Core Library
    @section intro_sec Introduction

    Radix PHP is a collection of PHP libraries which can be used for rapid application development.
    These libraries are light-weight, relatively fast and simple to understand.

    The assumptions is that a few simple implementations of these classes can bring the system to life quickly.
*/

/**
    @brief Radix Base Class, Core MVC Utilities
           Radix is also the class instantiated for the View object
*/

class Radix
{
    const OK = 200;
    const NOT_FOUND = 404;

    private static $_module_list;
    private static $_file_list = array();
    private static $_route_list = array();
    private static $_exec_res; // Result of exec()
    private static $_view_res; // Result of view()

    protected static $_m; // Module
    protected static $_c; // Controller
    protected static $_a = 'index'; // Action

    public static $theme_name = 'html';
    public static $theme_bail = 'bail';

    public static $root; // Filesystem root of Application
    public static $host; // Hostname
    public static $base; // Web-Base of Application ( "/" or "/something" )
    public static $path; // Path of Request in Application "/" is main page

    // Other's can set more Public Stuff on this Object
    public static $view; // The View Object

    public $body; // Body of the Request

    private function __construct() { /*  Only I may dance */ }

    /**
        Initialize the Radix System

        @param array $opts specfiy: root, theme, theme_bail
        @return void
    */
    public static function init($opts=null)
    {
        // My Hostname
        self::$host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
                       ( isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ($_SERVER['SERVER_ADDR']) ) );

        // Root of Application
        if (isset($opts['root'])) {
            self::$root = $opts['root'];
        } else {
            // We are Generally Rooted one-up from the Handler
            self::$root = dirname(dirname($_SERVER['SCRIPT_FILENAME'])); // Should be webroot/index.php
        }

        // Base of Application
        self::$base = Radix::base();
        // Path of Request
        self::$path = Radix::path();
        // Possible Module Name? /(\w+)/.+
        if (preg_match('|^/(\w+)/.+|',self::$path,$m)) {
            self::$_m = $m[1];
        }

        if (!empty($opts['theme'])) {
            self::$theme_name = $opts['theme'];
        }
        if (!empty($opts['theme_bail'])) {
            self::$theme_bail = $opts['theme_bail'];
        }

        if (empty(self::$view)) {
            self::$view = new Radix();
        }
    }

    /**
        Create a new Route, routes are only process once (that is, no cascade/recurse)

        @param $src = the source path, regular expression (with capture)
        @param $dst = the destination path
        @param $arg = the by-ref argument of where to put the matched stuff
    */
    public static function route($src=null,$dst=null,&$arg=null)
    {
        // Accept the $src as RE, but escape only the '/'
        $src = str_replace('/','\/',$src);
        self::$_route_list[] = array(
            'src' => $src,
            'dst' => $dst,
            'arg' => $arg,
        );

        // @deprecated - over-rides existing pages/scripts cause it's in front
        //               we want routes to trigger if no Controller/View Found
        if (preg_match("/$src/i",$_SERVER['REQUEST_URI'],$m)) {
            Radix::$path = $dst;
            if ($arg !== null) {
                $arg = array_merge($arg,$m);
            } else {
                $_GET = array_merge($_GET,$m);
            }
        }
        // die( $src );
        // if (preg_match(
    }
    
    /**
    
    */
    public static function stat()
    {
        // Check Controller
        $path = self::$path;
        while ( (!empty($path)) && ('/' != $path) ) {
            $file = sprintf('%s/controller/%s.php',self::$root,trim($path,'/'));
            if (is_file($file)) return true;
            $path = dirname($path);
        }

        // Check Views
        $list = array();
        // Module View
        if (!empty(self::$m)) {
            $list[sprintf('%s/view/%s.php',self::$m,self::$path)] = -1;
        }
        if (self::$_a == 'index') {
            $list[] = sprintf('%s/view/%s/index.php',self::$root,self::$path);
        }
        // Theme Specific View
        $list[] = sprintf('%s/theme/%s/view/%s.php',self::$root,self::$theme_name,self::$path);
        // Standard View
        $list[] = sprintf('%s/view/%s.php',self::$root,self::$path);
        foreach ($list as $file) {
            if (is_file($file)) return true;
        }
        
        return false;
    }

    /**
        Execute the Controller for the Request
        Searches /controller from most specific to least specific path
        @return name of controller on success, array of attempted files on failure
    */
    public static function exec()
    {
        $path = self::$path;
        while ( (!empty($path)) && ('/' != $path) ) {
            $list[] = sprintf('%s/controller/%s.php',self::$root,trim($path,'/'));
            $path = dirname($path);
        }
        ob_start();
        $res = self::$view->_include($list);
        self::$view->body.= ob_get_clean();
        self::$_exec_res = $res;
        return self::$_exec_res;
    }

    /**
        render the view body
    */
    public static function view()
    {
        $list = array();
        // Module View
        if (!empty(self::$m)) {
            $list[sprintf('%s/view/%s.php',self::$m,self::$path)] = -1;
        }
        // Standard View
        $list[] = sprintf('%s/view/%s.php',self::$root,self::$path);
        // Or By Default Action
        if (self::$_a == 'index') {
            $list[] = sprintf('%s/view/%s/index.php',self::$root,self::$path);
        }
        // Theme Specific View
        $list[] = sprintf('%s/theme/%s/view/%s.php',self::$root,self::$theme_name,self::$path);

        ob_start();
        $res = self::$view->_include($list);
        self::$view->body.= ob_get_clean();
        self::$_view_res = $res;
        return self::$_view_res;
    }

    /**
        Sends the Results from the exec() and view() as page
        @return 200|404
    */
    static function send()
    {
        ob_start();

        // Bail on Error?
        // die('self::$_view_res ' . self::$_view_res . "\n");
        if (self::$_view_res !== self::OK) {
            $v = self::$_view_res;
            self::$_view_res = self::OK;
            radix::bail($v);
            return(0);
        }

        $list = array(
            sprintf('%s/theme/%s.php',self::$root,self::$theme_name),
            // sprintf('%s/theme/%s/layout.php',self::$root,self::$theme_name),
        );

        if (self::$view->_include($list)) {
            ob_end_flush();
            return(0);
        }

        // No Layout? Use This Built-In
        if (empty($_ENV['title'])) $_ENV['title'] = 'radix';
        echo "<!doctype html>\n";
        echo "<html>\n";
        echo '<head><meta http-equiv="content-type" content="text/html;charset=utf-8" />';
        echo '<title>' . $_ENV['title'] .'</title>';
        echo '</head><body>';
        echo '<div>' . self::$view->body . '</div>';
        echo '</body></html>';

        ob_end_flush();
    }

    /**
        Respond with an HTTP 400 or 500 level Error Message
        @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
        @param $opt int or text for error message or array('body'=>null), default 500
        @return never
    */
    static function bail($opt=null)
    {
        if (empty(self::$view)) {
            self::$view = new Radix();
        }
        self::$view->body = ob_get_clean();
        self::$_view_res = self::OK;
        // ob_end_flush();

        if ($opt === null) $opt = 500;

        if (is_int($opt)) {
            $opt = array('code' => $opt);
        } elseif (is_string($opt)) {
            $opt = array('code' => 500,'text' => $opt);
        } elseif (is_array($opt)) {
            if (!empty($opt['theme'])) {
                self::$theme_name = $opt['theme'];
            }
        }

        // Change Text
        switch ($opt['code']) {
        case 400:
            $opt['text'] = 'Bad Request';
            break;
        case 401:
            $opt['text'] = 'Not Authorized';
            break;
        case 402:
            $opt['text'] = 'Payment Required';
            break;
        case 403:
            $opt['text'] = 'Forbidden';
            break;
        case 404:
            $opt['text'] = 'Not Found';
            break;
        case 405:
            $opt['text'] = 'Method Not Allowed';
            break;
        case 406:
            $opt['text'] = 'Not Acceptable';
            break;
        case 409:
            $opt['text'] = 'Conflict';
            break;
        case 410:
            $opt['text'] = 'Gone';
            break;
        case 500:
        default:
            if (empty($opt['text'])) {
                $opt['text'] = 'Unexpected System Error';
            }
            break;
        }
        header(sprintf('HTTP/1.1 %d %s',$opt['code'],$opt['text']));
        if (empty(self::$view->body)) {
            self::$view->body = $opt['text'];
        }
        self::send();
        exit(0);
    }

    /**
        Engage trap handler for error and exceptions
        Also the routine that is used to trap errors and exceptions
    */
    static function trap()
    {

        // Engage myself as the error handler
        if ( ($e_code === true) && ($e_text === null) ) {
            //set_error_handler(array(self,'trap'));
            //set_exception_handler(array(self,'trap'));
            return(true);
        }

        //if ( (is_numeric($e_code) && (is_string($e_text)) ) {
        syslog(LOG_ERR,"I have had a fatal error");
        file_put_contents('/tmp/custos.err',print_r(debug_backtrace(),true));
        die(__LINE__);
        //}

        //set_error_handler('Radix::error_handler',  int $error_types = E_ALL | E_STRICT  ] )
        //set_exception_handler('Radix::hook_exception');

        ob_end_clean();
        set_exception_handler(null);

        try {
            // @todo reset view to 'error'
            // @todo insert 'error' layout as option
            // @todo insert 'error' theme as option
            // @todo push E to error View
            // Radix::dump($e);
            self::$view = new Radix( 'error' );
            self::$view->path = 'error';
            //self::view();
            //self::send();
        } catch (Exception $e) {
            die('Really fatal error here');
        }
        die(sprintf('%s#%d',__FILE__,__LINE__));
    }

    /**
        @return uri base of the application
        @note site's at the root are '/' otherwise '/path/'
    */
    public static function base($full=false)
    {
        $base = null;
        if ($full) {
            // Find Hostname
            $host = @$_SERVER['HTTP_HOST'];
            if (empty($host)) $host = $_SERVER['SERVER_NAME'];
            if (empty($host)) $host = $_SERVER['SERVER_ADDR'];

            // Scheme, Hostname & Port
            $base = 'http://' . $host;
            if ($_SERVER['SERVER_PORT'] != 80) {
                $base = 'http://' . $host . ':' . $_SERVER['SERVER_PORT'];
            }
            if (isset($_SERVER['HTTPS'])) {
                $base = 'https://' . $host;
                if ($_SERVER['SERVER_PORT'] != 443) {
                    $base = 'http://' . $host . ':' . $_SERVER['SERVER_PORT'];
                }
            }
        }
        // Dirname of the Path of the SCRIPT_NAME which is the handler
        $base.= dirname(parse_url($_SERVER['SCRIPT_NAME'],PHP_URL_PATH));
        return rtrim($base,'/');;
    }

    /**
        @return path of current request, with leading /
    */
    public static function path()
    {
        // @todo Find First Best one and break loop
        // $list = array('PATH_INFO','SCRIPT_URI');
        $path = null;
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $path = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $q = strpos($_SERVER['REQUEST_URI'], '?');
            if ($q === false) {
                $path = $_SERVER['REQUEST_URI'];
            } else {
                $path = substr($_SERVER['REQUEST_URI'], 0, $q);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            // Ignored?
        } elseif (isset($_SERVER['SCRIPT_URL'])) {
            $path = $_SERVER['SCRIPT_URL'];
        } elseif (isset($_SERVER['REDIRECT_URL'])) {
            $path = $_SERVER['REDIRECT_URL'];
        } elseif (isset($_SERVER['PHP_SELF'])) {
            $path = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['SCRIPT_NAME'])) {
            $path = $_SERVER['SCRIPT_NAME'];
            if (isset($_SERVER['PATH_INFO'])) {
                $path.= $_SERVER['PATH_INFO'];
            }
        }
        $path = parse_url($path,PHP_URL_PATH);
        // If there is a Base value, remove it
        if (self::$base != '/') {
            $path = str_replace(self::$base,null,$path);
        }
        // Refine
        if (empty($path)) $path = '/';
        if ($path == '/') $path = '/index';

        return $path;
    }

    public static function isAjax($ua=null)
    {
        $chk = strtolower($ua == null ? $_SERVER['HTTP_X_REQUESTED_WITH'] : $ua);
        return ('xmlhttprequest' == $chk);
    }

    /**
        Info function returns text/html or text/plain about the radix system
        @return string html
    */
    public static function info()
    {
        $html = null;
        $html.= 'root:' . self::$root . '<br>'; // Root Path of Application
        $html.= 'host:' . self::$host . '<br>'; // Hostname
        $html.= 'base:' . self::$base . '<br>'; // Web-Base of Application ( "/" or "/something" )
        $html.= 'path:' . self::$path . '<br>'; // Path of Request in Application
        $html.= 'exec()==' . self::$_exec_res . '<br>';
        $html.= 'view()==' . self::$_view_res . '<br>';
        $html.= 'files:<br>';
        foreach (self::$_file_list as $k=>$v) {
            $html.= ('+' . $k . ' = ' . $v . '<br>');
        }
        $html.= 'routes:<br>';
        foreach (self::$_route_list as $k=>$v) {
            $html.= ('@' . htmlspecialchars($v['src']) . ' = ' . htmlspecialchars($v['dst']) . '<br>');
        }                                                                                                                                                      
        // $html.= 'module:self::$m"; // Module of Request?
        // $html.= "view:$view; // The View Object
        if (php_sapi_name() == 'cli') {
            $html = strip_tags(str_replace('<br>',"\n",$html));
        }
        return $html;
    }

    /**
        Dumps Var to output
        @param $data the stuff to dump
        @return void
    */
    static function dump($data)
    {
        if (php_sapi_name() != 'cli') {
            echo '<pre>' . htmlspecialchars(print_r($data,true)) . '</pre>';
        } else {
            echo ("\n" . print_r($data,true) . "\n");
        }
    }

    /**
        Dumps a var and traces how we got here
        @param $x the var to assert on
        @return exits if var, like assert
    */
    static function trace($x=null)
    {
        $buf = null;
        $dbt = debug_backtrace();
        $idx = 0;

        foreach ($dbt as $sf) {

            // if (empty($sf['file'])) $sf['file'] = '{main}';
            // if (empty($sf['line'])) $sf['line'] = 0;

            $buf.= sprintf('%d: ',$idx++);
            if (!empty($sf['file'])) {
                $buf.= sprintf('%s:%d',$sf['file'],$sf['line']);
                $buf.= "\n   ";
            }
            if (!empty($sf['class'])) {
                $buf.= sprintf('%s%s',$sf['class'],$sf['type']);
            }
            $buf.= sprintf('%s(',$sf['function']);
            // $buf.=
            // Skip This One
            // if ((isset($s['function'])) && ($s['function']=='local_error_handler')) continue;
            $arg = array();
            if (is_array($sf['args'])) foreach ($sf['args'] as $a) {
                switch (strtolower(gettype($a))) {
                case 'integer':
                case 'double':
                    $arg[] = $a;
                    break;
                case 'string':
                    $a = htmlspecialchars(substr($a, 0, 16)).((strlen($a) > 16) ? '...' : '');
                    $arg[] = "\"$a\"";
                    break;
                case 'array':
                    $arg[] = 'Array('.count($a).')';
                    break;
                case 'object':
                    $arg[] = 'Object('.get_class($a).')';
                    break;
                case 'resource':
                    $arg[] = 'Resource('.strstr($a, '#').')';
                    break;
                case 'boolean':
                    $arg[] = ($a ? 'TRUE' : 'FALSE');
                    break;
                case 'null':
                    $arg[] = 'null';
                    break;
                default:
                    $arg[] = strtolower(gettype($a)) . '?';
                }
            }
            $buf.= implode(', ',$arg);
            $buf.= ")\n";
        }
        // Radix::dump($sf);
        // Output (should do something with ob_?)
        self::dump($buf); // echo "<pre>$buf</pre>";
        if ($x) {
            self::dump($x);
            exit(0);
        }
    }

    /**
        @param string $uri to redirect to
        @param int $code HTTP code, default 302, or full HTTP status line
    */
    public static function redirect($uri=null,$code=302)
    {
        // Special Case of Missing
        if (empty($uri)) {
            $uri = $_SERVER['HTTP_REFERER'];
        }
        if (empty($uri)) {
            $uri = '/';
        }

        // Specific URL
        $location = null;
        if (substr($uri,0,4)=='http') {
            $location = $uri;
        } else {
            $location = self::base(true);
            // Special Trick, // starts at webserver root / starts at app root
            if (substr($uri,0,2) == '//') {
                $location .= '/' . ltrim($uri,'/');
            } elseif (substr($uri,0,1) == '/') {
                $location .= '/' . ltrim($uri,'/');
            }
        }

        // This tried to do some file magic, too ugly
        // $sn = $_SERVER['SCRIPT_NAME'];
        // $cp = dirname($sn);
        // $schema = $_SERVER['SERVER_PORT']=='443'?'https':'http';
        // $host = strlen($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
        // if (substr($to,0,1)=='/') $location = "$schema://$host$to";
        // elseif (substr($to,0,1)=='.') // Relative Path
        // {
        //   $location = "$schema://$host/";
        //   $pu = parse_url($to);
        //   $cd = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        //   $np = realpath($cd.$pu['path']);
        //   $np = str_replace($_SERVER['DOCUMENT_ROOT'],'',$np);
        //   $location.= $np;
        //   if ((isset($pu['query'])) && (strlen($pu['query'])>0)) $location.= '?'.$pu['query'];
        // }
        // }

        $hs = headers_sent();
        if ($hs === false) {
            switch ($code) {
            case 301:
                // Convert to GET
                header("301 Moved Permanently HTTP/1.1",true,$code);
                break;
            case 302:
                // Confirm re-POST
                header("302 Found HTTP/1.1",true,$code);
                break;
            case 303:
                // dont cache, always use GET
                header("303 See Other HTTP/1.1",true,$code);
                break;
            case 304:
                // use cache
                header("304 Not Modified HTTP/1.1",true,$code);
                break;
            case 305:
                header("305 Use Proxy HTTP/1.1",true,$code);
                break;
            // case 306:
            //     header("306 Not Used HTTP/1.1",true,$code);
            //     break;
            case 307:
                header("307 Temporary Redirect HTTP/1.1",true,$code);
                break;
            default:
                // Pass Directly
                if (preg_match('/^(\d{3}) .+ HTTP\/1\.[01]/',$code,$m)) {
                    header($code,true,$m[1]);
                }
                break;
            }
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header("Location: $location");
        }
        // Show the HTML?
        if (($hs==true) || ($code==302) || ($code==303)) {
            // todo: draw some javascript to redirect
            $cover_div_style = 'background-color: #ccc; height: 100%; left: 0px; position: absolute; top: 0px; width: 100%;';
            echo "<div style='$cover_div_style'>\n";
            $link_div_style = 'background-color: #fff; border: 2px solid #f00; left: 0px; margin: 5px; padding: 3px; ';
            $link_div_style.= 'position: absolute; text-align: center; top: 0px; width: 95%; z-index: 99;';
            echo "<div style='$link_div_style'>\n";
            echo "<p>Please See: <a href='$uri'>".htmlspecialchars($location)."</a></p>\n";
            echo "</div>\n</div>\n";
        }
        exit(0);
    }

    /**
        Output a file from the ./block directory
        Has access to self::$view (or $this->view) as the View object

        @param $name file name, extension added if missing
        @param $data to share
    */
    static function block($name,$data=null)
    {
        // Add Extension if Missing
        $x = pathinfo($name,PATHINFO_EXTENSION);
        if (empty($x)) {
            $name.= '.php';
        }

        $file = sprintf('%s/block/%s',self::$root,ltrim($name,'/'));
        if (is_file($file)) {
            ob_start();
            include($file);
            $html = ob_get_clean();
            return $html;
        }
    }
    
    /**
        Wrapper for htmlentities
    */
    static function h($x,$twice=false)
    {
        if (is_array($x)) $x = print_r($x,true);
        return htmlentities($x,ENT_QUOTES,'utf-8',$twice);
    }

    /**
        Relative Link to this Base
        @param $path is application path, starting with '/' or schema://
        @param $args query string parameters
    */
    static function link($path,$args=null)
    {
        // No Schema == Local
        if (!preg_match('/^\w+:\/\//',$path)) {
            $path = sprintf('%s/%s',rtrim(self::$base,'/'),ltrim($path,'/'));
        }
        // Add Args
        if ( (is_array($args)) && (count($args)>0) ) {
            $path.= '?' . http_build_query($args);
        }
        return $path;
    }

    /**
        Given a list of files, include the first
        @return from included file
    */
    private function _include($list,$once=true)
    {
        //syslog(LOG_DEBUG,'Radix::include_file()');
        // Promote to Array
        if (!is_array($list)) {
            $list = array($list);
        }

        // Loop
        foreach ($list as $file) {
            self::$_file_list[$file] = 'fail:404';
            if (is_file($file)) {
                $r = $this->_include_file($file);
                // 0 if included file says "return(0);"
                // 1 to promote include() success to HTTP OK
                if ( ($r === 0) || ($r === 1) ) $r = self::OK;
                self::$_file_list[$file] = sprintf('load:%d',$r);
                return($r);
            }
        }
        return self::NOT_FOUND;
    }

    /**
        Includes the requested file with an mostly empty var space
        @param $f the file
        @return the return value from include
    */
    private function _include_file($f)
    {
        return include($f);
    }
}
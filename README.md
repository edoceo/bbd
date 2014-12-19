# BigBlueDashboard

The BigBlueDashboard (BBD) is a PHP based front-end for BigBlueButton (http://www.bigbluebutton.org/)

## Dependencies

After BigBlueButton is installed you'll need to add these

    apt-get install php5-cgi php5-curl php5-dev php-pear
    pecl install redis

## Nginx Configuration

Append this to your existing /etc/nginx/sites-enabled/bigbluebutton.conf.
@APP_ROOT@ should be replaced with your actual installation directory.

	include @APP_ROOT@/etc/nginx.conf;

Your nginx configuration may also have a 500 error handler.
If that is enabled it can hide errors in BBD, comment this handler out while installing.

## BBD Configuration

Now copy the etc/boot.ini.example to etc/boot.ini and edit as necessary.

### Fast CGI Details

By default we pass to FastCGI server listening on 127.0.0.1:9000 using PHP version 5.3.2
If you are having troubles with the Nginx/FPM the answer may be found in these.

* http://wiki.nginx.org/HttpFastcgiModule
* http://wiki.nginx.org/PHPFcgiExampleOld
* http://www.rackspace.com/knowledge_center/article/installing-nginx-and-php-fpm-setup-for-nginx
* http://blog.martinfjordvald.com/2011/01/no-input-file-specified-with-php-and-nginx/
* http://community.activestate.com/faq/cgi-debugging-no-input-fi
* http://forum.slicehost.com/index.php?p=/discussion/1259/solvednginx-fastcgi-php-not-working/p1
* https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/
* https://www.google.com/search?q=php+cgi+No+input+file+specified&oq=php+cgi+No+input+file+specified&aqs=chrome.0.69i57j0l3j69i62l2.5408j0&sourceid=chrome&ie=UTF-8

### Using ffmpeg

* https://www.virag.si/2012/01/web-video-encoding-tutorial-with-ffmpeg-0-9/
* https://trac.ffmpeg.org/wiki/How%20to%20concatenate%20(join,%20merge)%20media%20files
* http://evorster.blogspot.com/2012/10/httpblog.html
* https://groups.google.com/a/webmproject.org/forum/#!topic/webm-discuss/1cLXjVq06Yc

### Using sox

* http://edoceo.com/cli/sox

### Change BBB Background Color

	sed -i 's/=white/=#101010/g' /usr/local/bigbluebutton/core/lib/recordandplayback/edl/video.rb

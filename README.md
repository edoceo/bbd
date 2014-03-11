# BigBlueDashboard

The BigBlueDashboard (BBD) is a PHP based front-end for BigBlueButton (http://www.bigbluebutton.org/)

## Dependencies

    apt-get install php5-cgi php5-curl php5-dev php-pear
    pecl install redis

## Nginx Configuration

pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
We have 5.3.2

* http://wiki.nginx.org/HttpFastcgiModule
* http://wiki.nginx.org/PHPFcgiExampleOld
* http://www.rackspace.com/knowledge_center/article/installing-nginx-and-php-fpm-setup-for-nginx
* http://blog.martinfjordvald.com/2011/01/no-input-file-specified-with-php-and-nginx/
* http://community.activestate.com/faq/cgi-debugging-no-input-fi
* http://forum.slicehost.com/index.php?p=/discussion/1259/solvednginx-fastcgi-php-not-working/p1
* https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/
* https://www.google.com/search?q=php+cgi+No+input+file+specified&oq=php+cgi+No+input+file+specified&aqs=chrome.0.69i57j0l3j69i62l2.5408j0&sourceid=chrome&ie=UTF-8

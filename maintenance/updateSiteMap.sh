#!/bin/sh

/usr/local/bin/php /var/www/html/wiki/maintenance/generateUrls.php > /var/www/html/wiki/maintenance/urllist.txt
/usr/bin/python /var/www/html/wiki/maintenance/shovel.py --config=/var/www/html/wiki/maintenance/config.xml  > /var/www/html/wiki/maintenance/shovel.log

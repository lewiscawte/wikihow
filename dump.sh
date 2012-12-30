#! /bin/sh
echo "`date` starting..." >> /var/www/html/wiki/dump.log
/usr/local/bin/php /var/www/html/wiki/maintenance/dumpBackup.php --current --quiet > wikidb.xml 
echo "`date` finishing..." >> /var/www/html/wiki/dump.log

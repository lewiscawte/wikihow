#!/bin/bash

# INTL: Languages for which to generate sitemaps
ARRAY=( 'de' 'es' 'pt' )
ELEMENTS=${#ARRAY[@]}

for (( i=0;i<$ELEMENTS;i++)); do
	cd /var/www/html/wiki-${ARRAY[$i]}/
	/usr/local/bin/php /var/www/html/wiki-${ARRAY[$i]}/maintenance/generateUrls.php > /var/www/html/wiki-${ARRAY[$i]}/maintenance/urllist.txt
	/usr/bin/python /var/www/html/wiki-${ARRAY[$i]}/maintenance/shovel.py --config=/var/www/html/wiki-${ARRAY[$i]}/maintenance/config-${ARRAY[$i]}.xml  > /var/www/html/wiki-${ARRAY[$i]}/maintenance/shovel.log
done 

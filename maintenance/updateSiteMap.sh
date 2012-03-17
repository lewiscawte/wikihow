#!/bin/bash

# INTL: Languages for which to generate sitemaps
#ARRAY=( 'de' 'es' 'pt' )
ARRAY=( 'en' )
ELEMENTS=${#ARRAY[@]}

for (( i=0;i<$ELEMENTS;i++)); do
	if [ ${ARRAY[${i}]} == 'en' ]; then
		cd /var/www/html/wiki/maintenance/
		/usr/local/bin/php /var/www/html/wiki/maintenance/generateUrls.php > /var/www/html/wiki/maintenance/urllist.txt
		/usr/bin/python /var/www/html/wiki/maintenance/shovel.py --config=/var/www/html/wiki/maintenance/config.xml  > /var/www/html/wiki/maintenance/shovel.log
	else
		cd /var/www/html/wiki-${ARRAY[$i]}/
		/usr/local/bin/php /var/www/html/wiki-${ARRAY[$i]}/maintenance/generateUrls.php > /var/www/html/wiki-${ARRAY[$i]}/maintenance/urllist.txt
		/usr/bin/python /var/www/html/wiki-${ARRAY[$i]}/maintenance/shovel.py --config=/var/www/html/wiki-${ARRAY[$i]}/maintenance/config-${ARRAY[$i]}.xml  > /var/www/html/wiki-${ARRAY[$i]}/maintenance/shovel.log
	fi
done 

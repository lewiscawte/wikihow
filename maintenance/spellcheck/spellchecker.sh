#!/bin/sh

cd /var/www/html/wiki/maintenance/spellcheck

/usr/local/bin/php spellchecker.php $1 >> log.txt

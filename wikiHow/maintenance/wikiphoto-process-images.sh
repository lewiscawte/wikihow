#!/bin/bash

this_dir="`dirname $0`"
log="/usr/local/wikihow/log/wikiphoto-processing.log"

# make sure this isn't already running
if [ "`ps auxww |grep wikiphotoProcess |grep -c -v grep`" = "0" ]; then
	sudo -u apache /usr/local/bin/php $this_dir/wikiphotoProcessImages.php 2>&1 | tee -a $log
fi

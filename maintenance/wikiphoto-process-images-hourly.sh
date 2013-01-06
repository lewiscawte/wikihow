#!/bin/bash

this_dir="`dirname $0`"
sudo -u www $this_dir/wikiphoto-copy-images-from-dev.sh
sudo -u apache /usr/local/bin/php $this_dir/wikiphotoProcessImages.php


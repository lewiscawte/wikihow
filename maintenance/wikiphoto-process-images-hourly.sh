#!/bin/bash

sudo -u www /var/www/html/wiki/maintenance/wikiphoto-copy-images-from-dev.sh
sudo -u apache /usr/local/bin/php /var/www/html/wiki/maintenance/wikiphotoProcessImages.php


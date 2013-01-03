#!/bin/bash
#
# This script is run exclusively by phodesk-process-images-hourly.sh
#

[ "$USER" != "www" ] && echo 'must be www user' && exit

DIR="/usr/local/pfn/images"
SCRIPT_DIR="`/usr/bin/dirname $0`"

/usr/bin/rsync -vtr --delete --rsh=ssh dev.wikidiy.com:$DIR/ $DIR/

"$SCRIPT_DIR/phodesk-process-zips.sh" "$DIR"


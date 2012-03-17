#!/bin/bash
#
# Pull the latest tweets from the InboxQ API via these web calls.  This
# script should be run once every 20 minutes with a crontab line similar
# to this:
# */20 * * * * sh /home/reuben/prod/maintenance/refresh-inboxq-tweets.sh
#

. /usr/local/wikihow/LocalKeys.sh

if [ "`hostname`" != "dev.wikidiy.com" ]; then
	curl --silent --output /dev/null 'http://www.wikihow.com/Special:TweetItForward?action=retrieve'
else
	curl --silent --output /dev/null --user $WH_WEBAUTH_USER:$WH_WEBAUTH_PASSWORD 'http://reuben.wikidiy.com/Special:TweetItForward?action=retrieve'
fi


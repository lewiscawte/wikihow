#!/bin/sh
start_url='http://wiki19.wikidiy.com/Special:OLPC?command=start'
base_dir='./tmp'
auth='--auth=diy:diy267'
server='http:\/\/wiki19.wikidiy.com';
for url in `lynx $auth --source=1  --dump $start_url`
do
	title=`echo $url | sed 's/.*title=\([^&]*\)/\1/'`
	title=`echo $title | sed 's/&.*//g'`
	if [ "$title" = "$url" ]
	then
		# not an article
		save_file=`echo $url | sed "s/$server//g"`
		path=${save_file%/*}
		mkdir -p "$base_dir$path" > /dev/null
#echo $base_dir$path $save_file
		`lynx $auth --dump $url > $base_dir$save_file`
	else
		echo "" > /dev/null
		`lynx $auth --source=1 --dump $url > $base_dir/$title.html`
	fi
done

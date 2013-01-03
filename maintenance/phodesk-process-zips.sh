#!/bin/bash
#
# To be run by process-phodesk-images-hourly.sh, or as a cron job on dev
#

DIR="/usr/local/pfn/images"

cd "$DIR"
[ "`pwd`" != "$DIR" ] && echo "dir must exist: $DIR" && exit

for i in */*.ZIP; do
	if [ -f "$i" ]; then
		newfile="${i%.ZIP}.zip"
		mv "$i" "$newfile"
	fi
done

for i in */*.zip; do
	if [ -f "$i" ]; then
		dir="${i%.zip}"
		do_unzip=0
		if [ -d "$dir" ]; then
			if [ "$i" -nt "$dir" ]; then
				rm -rf "$dir"
				do_unzip=1
			fi
		else
			do_unzip=1
		fi
		if [ "$do_unzip" = "1" ]; then
			mkdir "$dir"
			(cd "$dir"; unzip -o -j "../../$i")
		fi
	fi
done


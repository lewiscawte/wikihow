#!/bin/sh

echo "Doing dev 1.12"
for i in `seq 1 10`;
do 
	lynx --dump --auth=diy:diy267  --source "http://wiki112.wikidiy.com/Special:Random" | egrep 'Served in' | sed 's/<!-- Served in //g' | sed 's/ secs.*//g'
done

echo "Doing dev 1.9"
for i in `seq 1 10`;
do 
	lynx --dump --auth=diy:diy267 --source "http://wiki19.wikidiy.com/Special:Random" | egrep 'Served by.*in' | sed 's/<!-- Served by.*in //g' | sed 's/ secs.*//g'
	#lynx --dump --source "http://www.wikihow.com/Special:Random" | egrep 'Served by.*in' | sed 's/<!-- Served by.*in //g' | sed 's/ secs.*//g'
done

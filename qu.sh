#! /bin/sh

for i in `cat queries.txt`
do
	q=`echo $i | sed 's/-/ /g'`
	lynx --dump "http://127.0.0.1:8123/search/wikidb_112/$q?namespaces=0&offset=0&limit=20&case=ignore" | awk '{print $3}' | sed 's/_/ /g' > ./results/$i-mw.txt
done


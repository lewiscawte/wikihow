<?php

/*
 * Curls through all the article pages on the site
 * on spare1.
 */

require_once("commandLine.inc");

if ($argv[0] == null) {
	echo "Missing filename for file with titles.\n";
	return;
}

$fi = fopen($argv[0], 'r');

$pages = array();

while ( !feof( $fi ) ) { 
	$fcontent = fgets($fi);
	$tcontent = trim($fcontent);
	$tcontent = str_replace("http://www.", "http://spare1.", $tcontent);
	
	$pages[] = $tcontent;
}

fclose($fi);

echo "Getting ready to check " . count($pages) . " articles\n";

$count = 0;

foreach($pages as $page) {
	getResults($page);
	$count++;
	if($count % 200 == 0)
		echo "Done checking " . $count . " articles\n";
}

function getResults($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, "wikihow:wikihow2006");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $contents = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "curl error {$url}: " . curl_error($ch) . "\n";
        }
		
        curl_close($ch);
    }

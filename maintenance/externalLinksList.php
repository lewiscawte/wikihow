<?php
/* 
 *
 * Script that aggregates all external links
 *
 */

global $IP, $wgTitle;
require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);

$res = $dbr->select('externallinks', '*', '1');

$results = array();

while( $obj = $dbr->fetchObject($res) ){
	$results[] = $obj;
}

foreach ( $results as $result ) {
	if($sites[$result->el_to] == null)
		$sites[$result->el_to] = 1;
	else
		$sites[$result->el_to]++;
}

arsort($sites);

$fh = fopen('/usr/local/wikihow/log/external_links.txt', 'w');

foreach ( $sites as $site => $count ) {
	fwrite( $fh, $site . " " . $count . "\n" );
}

fclose($fh);

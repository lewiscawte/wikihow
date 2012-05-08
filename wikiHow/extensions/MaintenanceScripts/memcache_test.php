<?php

require_once('commandLine.inc');

function microtime_float() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}


if ( isset($argv[0]) ) {
	$wgMemCachedServers = array( $argv[0] );

	// 100 is default; can be specified through 2nd param
	$iterations = isset($argv[1]) ? $argv[1] : 100;
}

if (count($wgMemCachedServers) <= 0) {
	die("No servers to test!\n");
}

foreach ($wgMemCachedServers as $server) {
	print "$server ";
	$mcc = new MemCachedClientforWiki( array('persistant' => true) );
	$mcc->set_servers( array($server) );
	$set = 0;
	$incr = 0;
	$get = 0;

	$key = wfMemcKey( wfHostname() . "-test" );
	$time_start = microtime_float();

	for ($i = 1; $i <= $iterations; $i++) {
		if ( !is_null( $mcc->set("$key$i", $i) ) ) {
			$set++;
		}
	}

	for ($i = 1; $i <= $iterations; $i++) {
		if ( !is_null( $mcc->incr("$key$i", $i) ) ) {
			$incr++;
		}
	}

	for ($i = 1; $i <= $iterations; $i++) {
		$value = $mcc->get("$key$i");
		if ( $value == $i*2 ) {
			$get++;
		}
	}
	$exectime = microtime_float() - $time_start;

	print "set: $set  incr: $incr  get: $get  time: $exectime\n";
}


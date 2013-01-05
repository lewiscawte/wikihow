<?php
/* $Id: mctest.php,v 1.1.1.1 2009/11/04 00:04:43 vu Exp $ */

$optionsWithArgs = array( 'i' );

require_once('commandLine.inc');

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


#$wgDebugLogFile = '/dev/stdout';

if ( isset( $args[0] ) ) {
	$wgMemCachedServers = array( $args[0] );
} else {
	##$wgMemCachedServers[] = 'localhost';
}
if ( isset( $options['i'] ) ) {
	$iterations = $options['i'];
} else {
	$iterations = 100;
}

foreach ( $wgMemCachedServers as $server ) {
        print "$server ";
	$mcc = new MemCachedClientforWiki( array('persistant' => true) );
	$mcc->set_servers( array( $server ) );
	$set = 0;
	$incr = 0;
	$get = 0;
	$key = wfHostname() . "-test";
    $time_start=microtime_float();
	for ( $i=1; $i<=$iterations; $i++ ) {
		if ( !is_null( $mcc->set( "$key$i", $i ) ) ) {
			$set++;
		}
	}

	for ( $i=1; $i<=$iterations; $i++ ) {
		if ( !is_null( $mcc->incr( "$key$i", $i ) ) ) {
			$incr++;
		}
	}

	for ( $i=1; $i<=$iterations; $i++ ) {
		$value = $mcc->get( "$key$i" );
		if ( $value == $i*2 ) {
			$get++;
		}
	}
        $exectime=microtime_float()-$time_start;

	print "set: $set   incr: $incr   get: $get time: $exectime\n";
}


?>

<?php
/**
 * Script for periodic off-peak updating of the search index
 *
 * Usage: php updateSearchResultsSupplementGSA.php [-s START] [-e END] [-p POSFILE] [-l LOCKTIME] [-q]
 * Where START is the starting timestamp
 * END is the ending timestamp
 * POSFILE is a file to load timestamps from and save them to, searchUpdate.pos by default
 * LOCKTIME is how long the searchindex and cur tables will be locked for
 * -q means quiet
 *
 * @addtogroup Maintenance
 */

/** */
$optionsWithArgs = array( 's', 'e', 'p' );

require_once( 'commandLine.inc' );
require_once( 'updateSearchResultsSupplementGSA.inc' );

if ( isset( $options['p'] ) ) {
	$posFile = $options['p'];
} else {
	$posFile = 'searchResultsUpdate.pos';
}

if ( isset( $options['e'] ) ) {
	$end = $options['e'];
} else {
	$end = wfTimestampNow();
}

if ( isset( $options['s'] ) ) {
	$start = intval($options['s']);
} else {
	print "must provide start timestamp\n";
	exit;
	/*
	$start = @file_get_contents( $posFile );
	if ( !$start ) {
		$start = wfTimestamp( TS_MW, time() - 86400 );
	}
	*/
}

if ( isset( $options['l'] ) ) {
	$lockTime = $options['l'];
} else {
	$lockTime = 20;
}

$quiet = (bool)(@$options['q']);

updateSearchResultsSupplement( $start, $end, $lockTime, $quiet );

$file = fopen( $posFile, 'w' );
fwrite( $file, $end );
fclose( $file );



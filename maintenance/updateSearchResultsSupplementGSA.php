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
	$scriptDir = dirname( realpath(__FILE__) );
	$posFile = $scriptDir . '/searchResultsUpdate.pos';
}

if ( isset( $options['e'] ) ) {
	$end = $options['e'];
} else {
	$end = wfTimestampNow();
}

if ( isset( $options['s'] ) ) {
	$start = intval($options['s']);
} else {
	$start = @file_get_contents( $posFile );
	if ( !$start ) {
		// default to "1 day ago"
		//$start = wfTimestamp( TS_MW, time() - 86400 );
		// default to "start of time"
		$start = 0;
	}
}

//if ( isset( $options['l'] ) ) {
//	$lockTime = $options['l'];
//} else {
//	$lockTime = 20;
//}
$lockTime = 0;

$quiet = (bool)(@$options['q']);

updateSearchResultsSupplement( $start, $end, $lockTime, $quiet );

@file_put_contents($posFile, "$end");


<?php
/**
 * @addtogroup Maintenance
 */

/**
 */
function updateSphinxIndex( $start, $end, $maxLockTime, $quiet ) {
	global $wgQuiet;
	global $wgDisableSearchUpdate;

	$fname = "updateSphinxIndex";

	$wgQuiet = $quiet;
	$wgDisableSearchUpdate = false;

	$dbw = wfGetDB( DB_MASTER );

	output( "Updating searchindex between $start and $end\n" );

	# Select entries from recentchanges which are on top and between the specified times
	$startOfTime = $start === 0;
	$start = $dbw->strencode( $start );
	$end = $dbw->strencode( $end );

	$ns_main = NS_MAIN;
	$page = $dbw->tableName( 'page' );
	if ($startOfTime) {
		$sql = "
			SELECT page_id,page_title,page_counter,page_touched,
			  page_is_featured
			FROM $page
			WHERE page_is_redirect=0 AND page_namespace=$ns_main AND
			  page_touched BETWEEN '$start' AND '$end'
			";
	} else {
		$sql = "TODO query recentchanges here;";
	}
	$res = $dbw->query( $sql, $fname );

	# Lock searchindex
	if ( $maxLockTime ) {
		output( "   --- Waiting for lock ---" );
		lockSearchindex( $dbw );
		$lockTime = time();
		output( "\n" );
	}

	# Loop through the results and do a search update
	$i = 0;
	while ( $row = $dbw->fetchRow( $res ) ) {

		# Allow reads to be processed
		if ( $maxLockTime && time() > $lockTime + $maxLockTime ) {
			output( "    --- Relocking ---" );
			relockSearchindex( $dbw );
			$lockTime = time();
			output( "\n" );
		}

		$success = addSphinxArticle( $dbw, $row );
		$title = $row['page_title'];
		if ($success) {
			output( "$title\n" );
		} else {
			output( "$title (not found)\n" );
		}
	}

	# Unlock searchindex
	if ( $maxLockTime ) {
		output( "    --- Unlocking --" );
		unlockSearchindex( $dbw );
		output( "\n" );
	}
	output( "Done\n" );
}

/*
 *schema:
 *
CREATE TABLE wh_sphinx_text (
	wst_id int unsigned not null,
	wst_title varchar(255) not null,
	wst_url_title varchar(255) not null,
	wst_text text not null,
	wst_timestamp int unsigned not null,
	wst_popularity tinyint(1) unsigned not null,
	wst_is_featured tinyint(1) unsigned not null,
	wst_img varchar(255) not null,
	wst_img_thumb_100 varchar(255) not null,
	primary key(wst_id)
);
 *
 */

function getPopularityRank($counter) {
	// these numbers were determined with percentile data and may change
	// a bit as the site ages.
	$ranks = array(
		81,
		545,
		2606,
		11784
	);
	$myRank = 1;
	foreach ($ranks as $rank) {
		if ($counter <= $rank) break;
		$myRank++;
	}
	return $myRank;
}

function addSphinxArticle(&$dbw, &$row) {
	$title = $row['page_title'];
	$titleObj = Title::newFromDBkey($title);
	if ($titleObj) {
		// Get current revision
		$rev = Revision::loadFromTitle($dbw, $titleObj);
	} else {
		$rev = null;
	}
	if( $rev ) {
		$revTitleObj = $rev->getTitle();
		$revTitle = $revTitleObj->getText();
		$urlTitle = $revTitleObj->getPartialURL();
		$text = $rev->getText();
		$textEnc = $dbw->strencode($text);
		$titleEnc = $dbw->strencode($revTitle);
		$urlTitleEnc = $dbw->strencode($urlTitle);
		$timestamp = wfTimestamp(TS_UNIX, $row['page_touched']);
		$popularity = getPopularityRank($row['page_counter']);

		$intro = preg_replace('@==.*$@s', '', $text);
		$img = '';
		if (preg_match('@^.*(\[\[Image:([^\|\]]*)(\|[^\]]*)\]\]).*$@Us', $intro, $m)) {
			$img = trim($m[2]);
		}
		$imgEnc = !empty($img) ? $dbw->strencode($img) : '';

		$thumbUrl100 = '';
		if ($imgEnc) {
			$imgTitle = Title::newFromText($img, NS_IMAGE);
			if ($imgTitle) {
				$file = wfFindFile($imgTitle);
				if ($file) {
					$thumb = $file->getThumbnail(100, 100, true);
					if ($thumb) {
						$thumbUrl100 = $thumb->getUrl();
					}
				}
			}
		}
		$sql = "REPLACE INTO wh_sphinx_text SET wst_id='{$row['page_id']}',
			wst_title='{$titleEnc}', wst_url_title='{$urlTitleEnc}',
			wst_text='{$textEnc}', wst_popularity='{$popularity}', 
			wst_timestamp='{$timestamp}', 
			wst_is_featured='{$row['page_is_featured']}',
			wst_img='{$imgEnc}',
			wst_img_thumb_100='{$thumbUrl100}'
			";
		$dbw->query($sql);

		return true;
	} else {
		return false;
	}
}

function lockSearchindex( &$db ) {
	$write = array( 'wh_sphinx_text' );
	$read = array( 'page', 'revision', 'text', 'interwiki', 'image' );
	$items = array();
	
	foreach( $write as $table ) {
		$items[] = $db->tableName( $table ) . ' LOW_PRIORITY WRITE';
	}
	foreach( $read as $table ) {
		$items[] = $db->tableName( $table ) . ' READ';
	}
	$sql = "LOCK TABLES " . implode( ',', $items );
	$db->query( $sql, 'updateSphinxIndex.inc ' . __METHOD__ );
}

function unlockSearchindex( &$db ) {
	$db->query( "UNLOCK TABLES", 'updateSphinxIndex.inc ' . __METHOD__ );
}

# Unlock and lock again
# Since the lock is low-priority, queued reads will be able to complete
function relockSearchindex( &$db ) {
	unlockSearchindex( $db );
	lockSearchindex( $db );
}

function output( $text ) {
	global $wgQuiet;
	if ( !$wgQuiet ) {
		print $text;
	}
}

?>

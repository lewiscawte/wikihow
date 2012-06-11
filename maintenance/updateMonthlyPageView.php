<?php 

require_once('commandLine.inc');

/******
 * 
 * This script is run nightly (prior to titus), to update the 
 * monthly page view field in the pageview table. Grabs the current
 * value, subtractst the single day value from 30 days ago (obtained
 * through titus_historical db) and adds on the new day value (from
 * the titus db)
 * 
 ******/

$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

const CHUNKSIZE = 2000;

$startTime = microtime(true);

$articles = array();

$i = 0;

while(1) {
	//first grab all the pages
	$res = $dbr->select('pageview', array('pv_30day', 'pv_page'), '', __METHOD__, array("LIMIT" => CHUNKSIZE, "OFFSET" => $i*CHUNKSIZE));
	
	if($dbr->numRows($res) == 0)
		break;
	
	while($row = $dbr->fetchObject($res)) {
		$articles[] = $row;
	}
	
	usleep(500000);
	
	$i++;
}

//now we need to recalculate the pv data
$start = time();
$monthAgo 	= substr(wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30), 0, 8); // 30 days
var_dump($monthAgo);

$articleCount = 0;
foreach($articles as $articleData) {
	$count = $articleData->pv_30day;
	
	$res = $dbr->select('titus_historical', array('ti_datestamp', 'ti_daily_views'), array('ti_page_id' => $articleData->pv_page, 'ti_datestamp' => $monthAgo), __METHOD__);

	if($dbr->numRows($res) > 0) {
		$row = $dbr->fetchObject($res);
		$count -= $row->ti_daily_views;
	}
	
	$res = $dbr->select('titus', array('ti_daily_views', 'ti_datestamp'), array('ti_page_id' => $articleData->pv_page), __METHOD__);
	$row = $dbr->fetchObject($res);
	if($row) {
		$count += $row->ti_daily_views;
	}
	
	$success = $dbw->update('pageview', array('pv_30day' => $count), array('pv_page' => $articleData->pv_page), __METHOD__);
	
	if($success) {
		
		/*$title = Title::newFromID($articleData->pv_page);
		if($title)
			echo "Updated " . $title->getText() . " with " . $count . "\n";*/
	}
	else {
		echo "Unable to update article id# " . $articleData->pv_page . "\n";
	}
	
	$articleCount++;
	if($articleCount % 2000 == 0)
		usleep(500000);
}

$endTime = microtime(true);

echo "Finished " . __FILE__ . " in " . ($endTime - $startTime) . "\n";

/********
 * 
 * CREATE TABLE `wikidb_112`.`pageview` (
 * `pv_page`  INT(8) UNSIGNED NOT NULL,
 * `pv_30day` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
 * UNIQUE(
 * `pv_page`
 * )
 * ) ENGINE = InnoDB ;
 * 
 ********/
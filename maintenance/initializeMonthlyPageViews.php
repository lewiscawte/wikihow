<?php 

/******
 * 
 * One-time script used to pre-populate the 30-day pageview field
 * in the page table. After running this script, "updateMonthlyPageViews.php"
 * will grab this field, subtract the value for 30 days ago (gotten out
 * of the titus_historical table) and add on the new value (gotten out
 * of the titus table)
 * 
 ******/

require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

$articles = array();
//first grab all the pages
$res = $dbr->select('page', array('page_id', 'page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0), __METHOD__);

while($row = $dbr->fetchObject($res)) {
	$articles[] = $row;
}

//now we need to recalculate the pv data
$start = time();
$monthAgo 	= substr(wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30), 0, 8); // 30 days
var_dump($monthAgo);

foreach($articles as $articleData) {
	$count = 0;
	$res = $dbr->select('titus_historical', array('ti_datestamp', 'SUM(ti_daily_views) as ti_sum'), array('ti_page_id' => $articleData->page_id, "ti_datestamp >= {$monthAgo}"), __METHOD__);

	$row = $dbr->fetchObject($res);
	if($row->ti_sum !== NULL) {

		$count = $row->ti_sum;
	
		$success = $dbw->insert('pageview', array('pv_30day' => $count, 'pv_page' => $articleData->page_id), __METHOD__, "IGNORE");

		if($success) {
			if(class_exists('Pageview')) {
				Pageview::update30day($articleData->pv_page, $count);
			}
			
			/*$title = Title::newFromID($articleData->page_id);
			if($title)
				echo "Updated " . $title->getText() . " with " . $count . "\n";*/
		}
		else {
			echo "Unable to update article id# " . $articleData->page_id . "\n";
		}
	}
}
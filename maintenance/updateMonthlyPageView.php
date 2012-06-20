<?php 

require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

/******
 * 
 * This script is run nightly (prior to titus), to update the 
 * monthly page view field and the single day page view field 
 * in the pageview table. Grabs the current
 * value, subtractst the single day value from 30 days ago (obtained
 * through titus_historical db) and adds on the new day value (from
 * pv stu). If not in debug mode, it resets pv stu.
 *
 * Note: this script took about 9 minutes to run on June 13, 2012
 * -Reuben
 * 
 ******/

$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

DEFINE(BATCHSIZE, 1000);

//This always defaults to being in debug mode where the pv's don't get reset.
$debug = @$argv[0] != "live";
$tableName = $debug ? "pageview_tmp" : "pageview";

$startTime = microtime(true);

//grab all of the articless
$articles = DatabaseHelper::batchSelect('page', array('page_id', 'page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0));

//now we need to recalculate all the pv data
$start = time();
$monthAgo = substr(wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30), 0, 8); // 30 days
$now = wfTimestamp(TS_MW);

echo "Starting from " . $monthAgo . " and processing " . count($articles) . " in " . ceil(count($articles)/BATCHSIZE) . " batches. Using table {$tableName}.\n";

$articleCount = 0;
$sqlStart = "INSERT INTO {$tableName} (pv_page, pv_30day, pv_1day, pv_timestamp) VALUES ";
$sqlEnd = " ON DUPLICATE KEY UPDATE pv_30day = VALUES(pv_30day), pv_1day = VALUES(pv_1day), pv_timestamp = VALUES(pv_timestamp)";
$batches = array();
foreach($articles as $articleData) {
	$title = Title::newFromID($articleData->page_id);
	if(!$title)
		continue;
	
	//first grab new single day pv data
	$singleCount = getDaysPV($dbw, $title, $debug);
	
	$monthCount = $dbr->selectField($tableName, 'pv_30day', array('pv_page' => "{$articleData->page_id}"));
	//this article must be new, so set its monthly count to 0
	if($monthCount === false)
		$monthCount = 0;
	
	//grab the pv data from 30 days ago
	$row = $dbr->selectRow('titus_historical', array('ti_datestamp', 'ti_daily_views'), array('ti_page_id' => $articleData->pv_page, 'ti_datestamp' => $monthAgo), __METHOD__);

	//if there was data from 30 days ago, subtract it off
	if($row) {
		$monthCount -= $row->ti_daily_views;
	}
	
	//now add on the new count
	$monthCount += $singleCount;
	
	//add this page's data into the batch to be processed later
	$batches[] = "('{$articleData->page_id}', '{$monthCount}', '{$singleCount}', '{$now}')";
	
	$articleCount++;
	if($articleCount % BATCHSIZE  == 0){
		
		$sql = $sqlStart . join(",", $batches) . $sqlEnd;
		$success = $dbw->query($sql, __METHOD__);
		
		$batchNum = $articleCount/BATCHSIZE;
		if($success) {
			echo "Updated batch #" . $batchNum . ".\n";
		}
		else {
			echo "Unable to update batch #" . $batchNum . "\n";
		}
		
		$batches = array();
		
		usleep(500000);
	}
}

$endTime = microtime(true);

if(!$debug)
	echo "----RESET PV STU----\n";
echo "Finished " . __FILE__ . " in " . round($endTime - $startTime) . "s\n";

function getDaysPV(&$dbw, &$title, $debug = true) {
	$count = 0;

	$dbKey = $title->getDBkey();
	$articleId = $title->getArticleID();
	
	$query = array('select' => '*', 'from' => 'pv', 'pages' => array($dbKey));
	$ret = AdminBounceTests::doBounceQuery($query);
	if (!$ret['err'] && $ret['results']) {
		AdminBounceTests::cleanBounceData($ret['results']);
		foreach ($ret['results'] as $page => $datum) {
            if (isset($datum['__'])) {
                $count = $datum['__'];
            }
            break; // should only be one record
        }
	}
	
	if(!$debug) {
		$deleteQuery = array('delete' => '*', 'from' => 'pv', 'pages' => array($dbKey));
		AdminBounceTests::doBounceQuery($deleteQuery);
	}
	
	return $count;
	
}

/********
 * 
 * CREATE TABLE `wikidb_112`.`pageview` (
 * `pv_page`  INT(8) UNSIGNED NOT NULL,
 * `pv_30day` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
 * UNIQUE(
 * `pv_page`
 * )
 * ) ENGINE = InnoDB ;
 * ALTER TABLE `pageview` ADD `pv_1day` INT( 8 ) UNSIGNED NOT NULL DEFAULT '0'
 * ALTER TABLE `pageview` ADD `pv_timestamp` VARCHAR( 14 ) NOT NULL 
 * 
 * CREATE TABLE pageview_tmp (pv_page INT(8) primary key)  SELECT * FROM pageview;
 * 
 ********/

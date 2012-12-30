<?
require_once( "commandLine.inc" );

	$dbw = wfGetDB( DB_MASTER );

$reps = 5;
if (isset($argv[0]) && $argv[0] != "") $reps = $argv[0];

for ($i = 0; $i < $reps; $i++) {

	$batch = $dbw->selectField('urls', 'batch', array(), array('LIMIT' => 1));
	if ($batch == '') {
		echo "No batch to process!\n";
		return;
	}
	echo "Processing batch $batch\n";
	$res = $dbw->query("select url, count as C from urls where batch={$batch} group by url having C > 2 order by C desc;");
	$total =  $actual = 0;

	// use a bucket mapping counter increments to page ids
	// ex:
	// count_array[5] = array(5, 292, 449);		
	// mean the pages with ids 5, 292 and 449 should have their page counter incremented by 5
	$count_array = array();
	while ( $row = $dbw->fetchObject($res) ) {
		$url1 = str_replace("http://www.wikihow.com/", "", $row->url);
		$url = urldecode($url1);
		$title = Title::newFromURL( $url );

		# check for bad titles
		if (!$title) {
			$title = Title::newFromURL( $url1 );
		}
		if (!$title) {
			//echo "Couldn't build proper title for  {$url}\n";
			continue;
		}
		$id = $title == null ? 0 : $title->getArticleID();
		if (!$title || ($title->getNamespace() == NS_MAIN && $id == 0) || $id == 0) {
			//echo "ID is 0 for  {$url}\n";
			continue;
		}

		// update the bucket
		if (!isset($count_array[$row->C]))
			$count_array[$row->C] = array();
		$count_array[$row->C][] = $id;
		$actual += $row->C;
	}	


	# do the updates based on what's in the bucket
	foreach ($count_array as $count => $arg) {
		$dbw->query("update page set page_counter=page_counter+{$count} where page_id IN (" . implode(",", $arg) . ");");
	}

	# delete this batch from the list of urls to process
	$total = $dbw->selectField('urls', 'count(*)', array("batch=$batch"));

	# spit out some debugging just for fun
	$inc = $total;
	$total = number_format($total, 0, "", ",");
	$actual = number_format($actual, 0, "", ",");
	echo "Done processing $total ($actual actual) page views.\n";

	$dbw->query("update site_stats set ss_total_views = ss_total_views + $inc;");

	$mp1 = $dbw->selectField('page', 'page_counter', array('page_id=5'));
	$mp2 = $dbw->selectField('page', 'page_counter', array('page_id=5'));
	$mp3 = $dbw->selectField('urls', 'count(*)', array('url'=>'http://www.wikihow.com/Main-Page'));

	#echo "<h2>Main page</h2>current page_counter $mp1<br/>new page_counter2 $mp2 <br/># of urls in table that were added: $mp3<br/>\n";

	$dbw->query("delete from urls where batch=$batch;");
	# $u = new SiteStatsUpdate( $total, 0, 0 );
        #array_push( $wgDeferredUpdateList, $u );

	$dbw->freeResult($res);
}
?>

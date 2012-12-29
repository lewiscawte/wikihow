<?
	require_once("commandLine.inc");

$t1 = time();
$count = 0;
$dbr = wfGetDB(DB_SLAVE);
$debug = 0;
$limit = isset($argv[1]) ? $argv[1] : "500";
$sql= " select page_title, page_namespace from page
            where page_namespace=0 and page_is_redirect=0 
            and page_id not in (5, 5791) order by page_counter desc limit 100,{$limit}
	 ";


$res = $dbr->query($sql);
$source = $argv[0];

while ($row = $dbr->fetchObject($res)) {
	$t = Title::makeTitle($row->page_namespace, $row->page_title);
	if (!$t) continue;

	if (!$debug) {
		$r = new RelatedVideos($t, $source);
		if ($r->hasResults()) {
			#echo "{$t->getPrefixedURL()} has videos already for {$source}, skipping \n";
			#continue;
		}
	}
	switch ($source) {
		case 'youtube':
			$api = new YoutubeApi($t);
			break;
		case 'videojug':
			$api = new VideojugApi($t);
			break;
		case 'wonderhowto':
			$api = new WonderhowtoApi($t);
			break;
		case 'howcast':
		default:
			$api = new HowcastApi($t);
			break;
	}
	$api->execute($t);
	$api->storeResults(true, false);
	#echo "{$t->getPrefixedURL()} got {$api->mNumResults} results \n";
	$count++;
}

/*
for ($i = 0; $i < 100; $i++) {
	$v =  new Video();
	$rp = new RandomPage();
	$t = $rp->getRandomTitle();	
	if (!$t) continue;
	echo "Got {$t->getPrefixedURL()}\n";
	$count++;
	$v->execute($t->getText());
}
*/
$t2 = time() - $t1;;
echo "Took " . number_format($t2, 0, ".", ",") . " seconds for {$count} results...\n";


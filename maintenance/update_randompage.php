<?

require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);

$pages = array();

function getIds($sql) {
	global $pages, $dbr;
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchRow($res)) {
		$pages[] = $row[0];
	}
	echo "Found... " . sizeof($pages) . "\n";
}

getIds("SELECT page_id from templatelinks left join page on tl_from=page_id where page_namespace=0 and page_is_redirect=0 and tl_title='Fa'");
getIds("SELECT p2.page_id, p2.page_namespace, p2.page_title from categorylinks left join page p1 on cl_from=p1.page_id left join page p2 on p1.page_title = p2.page_title and p2.page_namespace=0 where cl_to='Rising-Stars'");
getIds("select page_id, count(*) as C 
		from imagelinks left join page on il_from = page_id 
		where page_namespace=0 and page_is_redirect = 0 and page_counter > 25000 group by page_id having C >= 0");
getIds("select rat_page, avg(rat_rating) as A, count(*) as C from rating group by rat_page having C >= 7 and A >= 0.70;");
getIds(" select page_id, count(*) as C from page left join revision on page_id=rev_page 
		where page_namespace=0 and page_is_redirect=0 group by page_id having C >= 50");
getIds("select page_id, count(*) as C 
		from imagelinks left join page on il_from = page_id 
		where page_namespace=0 and page_is_redirect = 0 group by page_id having C >= 3");
getIds("SELECT page_id from templatelinks left join page on tl_from=page_id where page_namespace=0 and page_is_redirect=0 and tl_namespace=24");

$dbw = wfGetDB(DB_MASTER);
$pages = array_unique($pages);
$pages = array_values($pages); // reset array keys to 0, 1, 2, ...
$dbw->query("delete from randompage");

echo "Inserting randompage rows.\n";

$PER_BATCH = 500;
$num_batches = ceil(1.0 * count($pages) / $PER_BATCH);
for ($i = 0; $i < $num_batches; $i++) {
	$batch = array_slice($pages, $i * $PER_BATCH, $PER_BATCH);
	foreach ($batch as &$pageid) {
		$pageid = array('rp_page' => $pageid);
	}
	$dbw->insert('randompage', $batch, 'WH update_randompage.php');
}

echo "Updating page table.\n";

$dbw->query('update page set page_randomizer=0');
$dbw->query('update page,randompage set page_randomizer=1 where page_id = rp_page and page_namespace = 0 and page_is_redirect = 0');

echo "Done.\n";


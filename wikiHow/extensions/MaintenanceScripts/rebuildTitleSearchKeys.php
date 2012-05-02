<?php
//
// Clear out and rebuild the title_search_key table. The title_search_key 
// table is used for searching wikiHow titles. It is used to check existing 
// titles in the CreatePage and TitleSearch special pages. 
//

require_once('commandLine.inc');

$dbw = wfGetDB(DB_MASTER);

function updateKey($row) {
	global $dbw;
	$t = Title::newFromDBKey($row->page_title);
	if ($t == null) {
		"Got null title for {$row->page_title}";
		return;
	}
	$skey = generateSearchKey($t->getText());
	$page_title = $dbw->addQuotes($row->page_title);
	$search_key = $dbw->addQuotes($skey);
	$featured = 0;
	if ($row->tl_from != null) $featured = 1;
	$sql = "INSERT INTO title_search_key
			(tsk_title, tsk_namespace, tsk_key, tsk_wasfeatured)
			VALUES ($page_title, 0, $search_key, $featured)
			ON DUPLICATE KEY UPDATE tsk_key=$search_key, tsk_wasfeatured=$featured";
	$dbw->query($sql, __METHOD__);
}

# CLEAR OUT TABLE
$dbw->query("DELETE FROM title_search_key", __METHOD__);

# GET DATA WITH WHICH TO REPOPULATE
$res = $dbw->query("SELECT p1.page_title, tl_from
	FROM page p1 
	LEFT JOIN page p2 ON p1.page_title = p2.page_title
		AND p2.page_namespace = 1
	LEFT JOIN templatelinks ON p2.page_id = tl_from
		AND tl_namespace = 10
		AND tl_title = 'Featured'
	WHERE p1.page_namespace = 0
		AND p1.page_is_redirect = 0
	ORDER BY p1.page_id DESC",
	__METHOD__);
$count = $res->numRows();

print "Found $count main namespace articles\n";
$rows = array();
while ( $row = $res->fetchObject() ) {
	$rows[] = $row;
}
$res->free();

# REPOPULATE TABLE
foreach ($rows as $row) {
	updateKey($row);
}

if ($count) {
	print "Done\n";
}


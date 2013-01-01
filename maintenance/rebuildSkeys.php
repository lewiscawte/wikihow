<?php

require_once( 'commandLine.inc' );

$dbw =& wfGetDB( DB_SLAVE );

function updateKey($row) {	
	global $dbw;
	$t = Title::newFromDBKey($row->page_title);
	if ($t == null) {
		"Got null title for {$row->page_title}";
		return;
	}
	$skey = generateSearchKey($t->getText());
//	echo "Title " . $t->getText() . " skey $skey \n";
	$p1 = $dbw->addQuotes($row->page_title);
	$p2 = $dbw->addQuotes($skey);
	$featured = 0;
	if ($row->tl_from != null) $featured = 1;
	$sql = "INSERT INTO skey (skey_title, skey_namespace, skey_key, skey_wasfeatured) VALUES ($p1, 0, $p2, $featured) ON DUPLICATE KEY UPDATE skey_key=$p2, skey_wasfeatured=$featured;";
	$dbw->query($sql, "rebuildSkeys::updateKey");
}

#global $wgUser;
#$wgUser = User::newFromName('Tderouin');

$dbw->query ("Delete from skey");

	$res = $dbw->query("SELECT p1.page_title , tl_from
			FROM page p1 
			LEFT JOIN page p2 on p1.page_title= p2.page_title and p2.page_namespace=1
			LEFT JOIN templatelinks ON p2.page_id = tl_from AND tl_namespace=10 and tl_title ='Featured'
			WHERE p1.page_namespace = 0 AND p1.page_is_redirect = 0 ORDER BY p1.page_id DESC; ");
	$count = $dbw->numRows( $res );
	print "Found $count main namespace articles \n";
	$rows = array();
	
	while ( $row = $dbw->fetchObject( $res ) ) {
		$rows[] = $row;
	}

	$dbw->freeResult($res);
	foreach ($rows as $row) {
		updateKey( $row);
	}
	if ( $count ) {
		print "Done\n";
	}


?>

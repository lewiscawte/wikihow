<?
require_once( "commandLine.inc" );
function iso8601_date($time) {
	$date = substr($time, 0, 4) . "-"
		. substr($time, 4, 2) . "-"
		. substr($time, 6, 2) . "T"
		. substr($time, 8, 2) . ":" 
		. substr($time, 10, 2) . ":"
		. substr($time, 12, 2) . "Z" ; 
	return $date; 
}

$dbr = &wfGetDB(DB_SLAVE);
			
$sql = "SELECT page_id, page_title, page_namespace, page_touched from page WHERE page_namespace = 0 AND page_is_redirect=0 order by page_touched desc;";
$res = $dbr->query($sql);
$now = time();
#echo "echo got this many: " . $dbr->numRows($res) . "\n"; exit;
while ( $row = $dbr->fetchObject($res ) ) {
	$t = Title::newFromDBKey($row->page_title);
	if ($t == null) {
		#echo "Warning: title is null for {$row->page_title}\n";
		continue;
	}
	$url= $t->getFullUrl() ;
	
	$priority = "priority=0.5";
	$date_str = iso8601_date($row->page_touched);  
	echo $url;
	echo " lastmod=" . $date_str ;
	echo " $priority";
	echo "\n";		
}	
$dbr->freeResult( $res );

?>

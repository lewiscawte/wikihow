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
function format_data($mysql_timestamp){
	preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $mysql_timestamp,$pieces);
	$unix_timestamp = mktime($pieces[4], $pieces[5], $pieces[6], $pieces[2], $pieces[3], $pieces[1]);
	return($unix_timestamp);
}
	$dbr = &wfGetDB(DB_SLAVE);
	// construct the array of new pages
       $sql = "SELECT 'Newpages' as type,
                                rc_namespace AS namespace,
                                rc_title AS title,
                                rc_cur_id AS value,
                                rc_user AS user,
                                rc_user_text AS user_text,
                                rc_comment as comment,
                                rc_timestamp AS timestamp,
                                rc_patrolled AS patrolled,
                                rc_id AS rcid
                        FROM recentchanges,page
                        WHERE rc_cur_id=page_id AND rc_new=1
                          AND rc_namespace=0 AND page_is_redirect=0";
	
	$res = $dbr->query( $sql);
	$new_pages  = array();
 	while ( $row = $dbr->fetchObject($res) ) {
		$t = Title::newFromDBKey($row->title);
		$new_pages[$t->getPartialURL()] = 1;
	}
	$dbr->freeResult($res );
                
	$sql = "SELECT page_title, page_namespace, page_touched from page WHERE page_namespace = 0 AND page_is_redirect=0 order by page_touched desc;";
	$res = $dbr->query($sql);
	$now = time();
 	while ( $row = $dbr->fetchObject($res ) ) {
		$t = Title::newFromDBKey($row->page_title);
		if ($t == null) {
			//echo "Warning: title is null for {$row->page_title}\n";
			continue;
		}
		$url= $t->getFullUrl() ;
		/*
		$t = format_data($row->page_touched);
		$diff = ($now - $t) / 60;
		echo "{$row->page_touched} was $diff minutes ago";
		*/
		$priority = "priority=0.5";
		if (isset($new_pages[$t->getPartialURL() ]) ) 
			continue; //

		$date_str = iso8601_date($row->page_touched);  
		echo $url;
		echo " lastmod=" . $date_str ;
		echo " $priority";
		echo "\n";		
	}	
	$dbr->freeResult( $res );

?>

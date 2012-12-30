<?php
class DailyStats extends SpecialPage {

 	function __construct() {
        SpecialPage::SpecialPage( 'DailyStats' );
    }

    function execute ($par) { 
	    global $wgOut, $wgServer, $wgStylePath;
	
	    $fname = "wfSpecialDailyStats";
	    $wgOut->disable();
	   	$dbr =& wfGetDB( DB_SLAVE);
	    header("Content-Type: text/xml");
		$displaydate = date("Y-m-d", time() - 60 * 60 * 24);
		
		// get the number of edits 
		$numedits = 0;     
		$yesterday 	= date("Ymd", time() - 60 * 60 * 24); 
		$today		= date("Ymd", time()); 
		//$tstampdate = '20060901'; //TODO remove

		$row1 = $dbr->selectRow('sitesnap', 
				array( 'ss_total_views', 'ss_total_edits', 'ss_good_articles', 'ss_links_emailed', 'ss_total_pages',
            			'ss_users', 'ss_admins', 'ss_images'
				), 
				array('ss_day' => $yesterday));
		$row2 = $dbr->selectRow('sitesnap', 
				array( 'ss_total_views', 'ss_total_edits', 'ss_good_articles', 'ss_links_emailed', 'ss_total_pages',
            			'ss_users', 'ss_admins', 'ss_images'
				), 
				array('ss_day' => $today));
		
		$stats = array();
		$stats['total_views'] 		= max(0, $row2->ss_total_views - $row1->ss_total_views);
		$stats['total_edits'] 		= max(0, $row2->ss_total_edits - $row1->ss_total_edits);
		$stats['good_articles'] 	= max(0, $row2->ss_good_articles -  $row1->ss_good_articles);
		$stats['links_emailed'] 	= max(0, $row2->ss_links_emailed - $row1->ss_links_emailed);
		$stats['total_pages'] 		= max(0, $row2->ss_total_pages - $row1->ss_total_pages);
		$stats['users'] 			= max(0, $row2->ss_users - $row1->ss_users);
		$stats['admins'] 			= max(0, $row2->ss_admins - $row1->ss_admins);
		$stats['images'] 			= max(0, $row2->ss_images - $row1->ss_images);	

	
	 	echo '<?xml-stylesheet type="text/xsl" href="' .
	            htmlspecialchars( "$wgServer/extensions/wikihow/stats.xsl" ) . '"?' . ">\n";
	
	echo "<root xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
	                <title>Daily Stats for " . $wgServer . "</title>
	                <description>Daily Statistics</description>
	                <lastBuildDate>$displaydate</lastBuildDate>
	";
	
	 
	
		echo "<stats>\n";
		foreach($stats as $key => $value) 
			echo "\t<$key>$value</$key>\n";
		echo "</stats>\n";
	
		echo "<pageviews>\n";
	        $res = $dbr->query("select (snap_counter1-snap_counter2) as diff, snap_page, snap_counter1, snap_counter2 from snap order by diff desc limit 50;");
	     	while ( $row = $dbr->fetchObject( $res ) ) {
	        	$t = Title::newFromID($row->snap_page);
	                if ($t == null) continue; 
			echo "<page>\n";
			echo "	<title>" . FeedItem::xmlEncode($t->getFullText()) . "</title>\n";
			echo "	<url>" . FeedItem::xmlEncode($t->getFullURL()) . "</url>\n";
			echo "	<hits>" . FeedItem::xmlEncode($row->diff) . "</hits>\n";
			echo "</page>\n";
	         }
	    	$dbr->freeResult( $res );
		echo "</pageviews>";
	
	
		echo "<editors>\n";
			$sql = "SELECT rev_user, rev_user_text, count(*) as numedits FROM revision, page 
					where page_id=rev_page and rev_timestamp > '{$yesterday}00000' and rev_timestamp < '{$today}000000'
					AND page_namespace NOT IN (2, 3, 18) group by rev_user order by numedits desc limit 50;";
	        $res = $dbr->query($sql);
	     	while ( $row = $dbr->fetchObject( $res ) ) {
	        	$t = Title::newFromText($row->rev_user_text, NS_USER);
	                if ($t == null) continue; 
			echo "<editor>\n";
			if ($row->rev_user == 0) {
				echo "	<username>" . wfMsg('anonymous') . "</username>\n";
				echo "	<url>{$wgServer}</url>\n";
			}  else {
				echo "	<username>" . FeedItem::xmlEncode($t->getFullText()) . "</username>\n";
				echo "	<url>" . FeedItem::xmlEncode($t->getFullURL()) . "</url>\n";
			}
			echo "	<edits>{$row->numedits}</edits>\n";
			$percent = "?";
			if ( $stats['total_edits'] > 0) 
				$percent =	round( ($row->numedits / $stats['total_edits']) * 100 , 2);
			echo "	<percent>{$percent}</percent>\n";
			echo "</editor>\n";
	         }
	    	$dbr->freeResult( $res );
		echo "</editors>";
	
	
		echo "</root>";
	
	}
	
}
	

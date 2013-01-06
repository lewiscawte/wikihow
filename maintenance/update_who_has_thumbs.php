<?
	require_once("commandLine.inc");
	$wgUser = User::newFromName("ThumbnailNotifier");
	$dbw = wfGetDB(DB_MASTER); 
	$dbw->query('delete from ythasthumbs;'); 

	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace'=>NS_VIDEO
		//, 'page_title'=>'Avoid-a-Speeding-Ticket'	
		),
		"getting vids for youtube"
		//,array("ORDER BY"=> "rand()"
			//, "LIMIT"=>$limit * 4)
	);

	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle(NS_MAIN, $row->page_title); 
		if (YTThumb::hasThumbnails($t)) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('ythasthumbs', array('yth_page'=>$t->getArticleID()));
		}
	}

<?
	require_once("commandLine.inc");
	$wgUser = User::newFromName("Tderouin");

	$dbr = wfGetDB(DB_SLAVE); 
	$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace'=>NS_VIDEO
		//, 'page_title'=>'Avoid-a-Speeding-Ticket'	
		),
		"getting vids for youtube", 
		array("ORDER BY"=> "rand()", "LIMIT"=>"1000")
	);

	while ($row = $dbr->fetchObject($res)) {
		$v = Title::makeTitle($row->page_namespace, $row->page_title); 
		if (!$v) {
			continue;
		}
		$r = Revision::newFromTitle($v); 
		if (!$r) {
			continue;
		}
		$text = $r->getText(); 
		$params = split('\|', $text); 
		if ($params[1] == "youtube") {
			$id = $params[2]; 
			$t = Title::makeTitle(NS_MAIN, $v->getText());
			if (!YTThumb::hasThumbnails($t)) {
				echo "{$t->getFullURL()}\t{$id}\n";
				$count++;
			}
			if ($count >= 500) {
				break;
			}
		}
	}

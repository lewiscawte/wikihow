<?
	require_once("commandLine.inc");
	$wgUser = User::newFromName("Tderouin");
	$id = null;
	$limit = isset($argv[0]) ? $argv[0] : 100; 

	function needsThumbs($v) {
		global $id;
		if (!$v) {
			return false;
		}
		$r = Revision::newFromTitle($v); 
		if (!$r) {
			return false;
		}
		$text = $r->getText(); 
		$params = split('\|', $text); 
		if ($params[1] == "youtube") {
			$id = $params[2]; 
			$t = Title::makeTitle(NS_MAIN, $v->getText());
			if (!YTThumb::hasThumbnails($t)) {
				return true;
			}
		}
		return false;
	}

	$dbr = wfGetDB(DB_SLAVE); 
	
	// get ones that users have requested in the past 24 hours
	// helps us from getting stuck on the same problematic video 
	$old = wfTimestamp(TS_MW, time() - 24 * 3600);
	$res = $dbr->select(array('ytnotify', 'page'), array('page_title', 'page_namespace'),
			array('ytn_page=page_id', 'ytn_published'=>0, "ytn_timestamp > '{$old}'"));
	while ($row = $dbr->fetchObject($res)) {
		$v = Title::makeTitle($row->page_namespace, $row->page_title); 
		if (needsThumbs($v)) {
			echo "{$v->getFullURL()}\t{$id}\n";
			$count++;
		}
		if ($count >= $limit) {
			exit;
		}
	}

	// we have enough
	if ($count >= $limit) {
		exit;
	}

	$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace'=>NS_VIDEO
		//, 'page_title'=>'Avoid-a-Speeding-Ticket'	
		),
		"getting vids for youtube", 
		array("ORDER BY"=> "rand()"
			//, "LIMIT"=>$limit * 4
		)
	);

	while ($row = $dbr->fetchObject($res) && $count <= $limit ) {
		$v = Title::makeTitle($row->page_namespace, $row->page_title); 
		if (needsThumbs($v)) {
			echo "{$v->getFullURL()}\t{$id}\n";
			$count++;
		}
		if ($count >= $limit) {
			break;
		}
	}

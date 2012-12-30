<?
	require_once('commandLine.inc');

	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('page',
			array('page_namespace', 'page_title'),
			array('page_namespace=' . NS_VIDEO)
		);

	$list = "";
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		$r = Revision::newFromTitle($t);
		if (!$r) continue;
		$text = $r->getText();
#echo $text . "\n";
		preg_match("@\|youtube\|[^\|]+\|@U", $text, $matches);
		if (sizeof($matches) > 0) {
			$id = $matches[0];
			$id = str_replace("|", "", $id);
			$id = str_replace("youtube", "", $id);
			$url = "http://gdata.youtube.com/feeds/api/videos/$id";
			$results = Importvideo::getResults($url);
			if (strpos($results, "<yt:noembed/>") !== false) {
				$sql = "select count(*) as C 		
						from pagelinks left join page on pl_from = page_id 
						where pl_namespace=24 and pl_title=" . $dbr->addQuotes($t->getText()) . " and page_namespace=0;";
				$res2 = $dbr->query($sql);
				if ($row2 = $dbr->fetchObject($res2)) {
					if ($row2->C == 0) {
						echo "{$t->getFullText()} has no links...skipping\n";
						continue;
					}
				}
				
				$list .= "# [[{$t->getFullText()}]]\n";
			}
		}
	}

	if ($list == "") $list = "There are no videos at this time.";
	$t = Title::makeTitle(NS_PROJECT, "Videos that can no longer be embedded");
	$a = new Article($t);
	$date = date("Y-m-d");
	$text = wfMsg('no_more_embed_video') . "\n\n{$list}\nThis page was last updated {$date}\n";
	if ($t->getArticleID() == 0) {
		$a->insertNewArticle($text, "list of videos that cannot be embedded", false, false);
	} else {
		$a->updateArticle($text, "list of videos that cannot be embedded", false, false);
	}


<?
	require_once('commandLine.inc');

	function escapeDuck($str) {
		return preg_replace("@[\n\t]@", " ", $str); 
	}

	$dbr = wfGetDB(DB_SLAVE); 

	$res = $dbr->select('page', array('page_namespace', 'page_title'), array('page_namespace'=>NS_MAIN, 'page_is_redirect'=>0
		//, 'page_title'=>'Get-Six-Pack-Abs'		
		),
			"duck-duck-go",
			array("LIMIT"=>1000, "ORDER BY"=>"page_counter desc")
		);

	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title); 
		if (!$t) {
			continue;
		}
		
		$r = Revision::newFromTitle($t); 
		if (!$r) {
			continue;
		}

		$text = $r->getText(); 

		$steps = preg_split("@^(#)@im", Article::getSection($text, 1), 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY); 
		while (sizeof($steps) > 0) {
			$s = array_shift($steps);
			if ($s == "#") {
				$s = array_shift($steps);
				$first_step = escapeDuck(strip_tags($wgOut->parse(($s))));
				break;
			}
		}

		$intro = Article::getSection($text, 0);
		$photo = "";
		preg_match_all("@\[\[Image:[^\]]*\]\]@", $text, $matches);
		if (sizeof($matches) > 0) {
			$img = preg_replace("@.*Image:@", "", $matches[0][0]);
			$img = ucfirst(preg_replace("@[\|].*\]\]@", "", $img));
			$img = Title::makeTitle(NS_IMAGE, $img);
			$file = wfFindFile($img);
		
			if ($file) {
				$photo = wfGetPad($file->getURL());
			}
		}

		/// cat info		
		$cat_str = "";
		$cats = $t->getTopLevelCategories(); 
		$cat_str_array = array();
		foreach ($cats as $c) {
			$cat_str_array[] = $c->getText();	
		}
		$cat_str = implode(",", $cat_str_array); 

		$related_arr = array();
		for ($i = 0; $i < 25; $i++) {
			$section = Article::getSection($text, $i); 
			if (preg_match("@==[ ]*" . wfMsg('relatedwikihows') . "[ ]*==@", $section)) {
				$related = split("\n", $section);
				foreach ($related as $r) {
					if (preg_match("@^\*@", $r)) {
						$r = preg_replace("@^\*[ ]*\[\[@", "", $r);
						$r = trim(preg_replace("@[\|].*\]\]@", "", $r));	
						$tx = Title::newFromText($r); 
						if ($tx) {
							$related_arr[] = $tx->getFullText(); 
							$related_arr[] = $tx->getFullURL();
						}

					}
				}
			}
		}

		$related_str = implode(",", $related_arr);
	/*
		$res2 = $dbr->select('pagelinks', array('pl_namespace', 'pl_title'), array('pl_from'=>$t->getArticleID()));
		$related_str = "";
		$related_arr = array();
		while ($row2 = $dbr->fetchObject($res2)) {
			$related = Title::makeTitle($row2->pl_namespace, $row2->pl_title);
			if (!$related) {
				continue;
			}
			$related_arr[] = $related->getFullURL();
			$related_arr[] = $related->getFullText();
		}
		$related_str = implode(",", $related_arr); 
*/

		echo "{$t->getText()}\t{$t->getFullURL()}\t$first_step\t$photo\t$cat_str\t$related_str\n";
	}

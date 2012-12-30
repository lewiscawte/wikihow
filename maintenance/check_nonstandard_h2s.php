<?
	require_once('commandLine.inc');

	$dbr = wfGetDB(DB_MASTER);
	$res = $dbr->select('page', array('page_namespace', 'page_title'),
		array('page_namespace' => NS_MAIN, 'page_is_redirect'=>0));
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$t) continue;
		$r = Revision::newFromTitle($t);
		if (!$r) continue;
		$text = $r->getText();
		preg_match_all("@^==[^=]*==$@im", $text, $matches); 
		$found = false;
		foreach ($matches[0] as $m) {
			if ($found) break;
			$m = trim(preg_replace("@=@", "", $m));
			switch ($m) {
				case "Steps":	
				case "Tips":	
				case "Warnings":	
				case "Video":	
				case "Ingredients":	
				case "Related wikiHows":	
				case "Sources and Citations":	
				case "Things You'll Need":	
					break;
				default:
					echo "{$t->getFullURL()}\t$m\n";
					$found = true;
					break;
			}
		}
	}

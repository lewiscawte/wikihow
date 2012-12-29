<?

	require_once('commandLine.inc');


	function processSteps(&$dom, &$sec, $beef) {
		$toks = preg_split("@(^#)@im", $beef, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$substeps = null;
		while (sizeof($toks) > 0) {
			$x = array_shift($toks);
			if (preg_match("@##", $x)) {
				if ($substeps == null) 
					$substeps = $dom->createElement("substeps");
			} else {
				if ($substeps)
					$sec->appendChild($substeps);
				$substeps = null;
			}
			$x = array_shift($toks);
			$s = $dom->createElement("step");
			$t = $dom->createElement("text");
			$t->appendChild($dom->createTextNode(cleanUpText($x)));
			$s->appendChild($t);
			if ($substeps)
				$substeps->appendChild($s);
			else
				$sec->appendChild($s);
		}
		if ($substeps)
			$sec->appendChild($substeps);
		return;
	}

	function cleanupText($text) {
		// strip templates
		$text= preg_replace("@{{[^}]*}}@", "", $text);
		$text= preg_replace("@\[\[Image:[^\]]*\]\]@", "", $text);
		$text= preg_replace("@\[\[Category:[^\]]*\]\]@", "", $text);
		preg_match_all ("@\[\[[^\|]*\|[^\]]*\]\]@", $text, $matches);
		foreach ($matches[0] as $m) {
			$n = preg_replace("@.*|@", "", $m);
			$n = str_replace("]]", "", $n);
			$text = str_replace($m, $n, $text);
		}
		$text = preg_replace("@#[#]*@", "", $text);
		$text = preg_replace("@__[^_]*__@", "", $text);
		return trim($text);
	}


	if (!isset($argv[0])) {
		echo "Usage: php maintenance/generate_xml.php urls.txt\n";
		return;
	}

	$dbr = wfGetDB(DB_SLAVE);	
	$urls = split("\n", file_get_contents($argv[0]));
	$valid_sections = array("steps", "tips", "warnings", "things");

	$dom = new DOMDocument("1.0");
	$root = $dom->createElement("wikihowmedia");
	$dom->appendChild($root);

	foreach ($urls as $url) {
		if (trim($url) == "")
			continue;
		$url = str_replace("http://www.wikihow.com/", "", $url);
		$t = Title::newFromURL(urldecode($url));
		if (!$t) {
			echo "Can't get title from {$url}\n";
			continue;
		}
		$r = Revision::newFromTitle($t);
		if (!$r) {
			echo "Can't get revision from {$url}\n";
			continue;
		}
		$text = $r->getText(); 


		$a = $dom->createElement("article");

		// title
		$x = $dom->createElement("title");
		$x->appendChild($dom->createTextNode($t->getText()));
		$a->appendChild($x);

		// intro
		$content = $dom->createElement("content");
		$intro = Article::getSection($text, 0);
		$intro = cleanupText($intro);
		$i = $dom->createElement("introduction");
		$n = $dom->createElement("text");
		$n->appendChild($dom->createTextNode($intro));
		$i->appendChild($n);
		$content->appendChild($i);

		$parts = preg_split("@(^==[^=]*==)@im", $text, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		array_shift($parts);
		while (sizeof($parts) > 0) {
			$x =trim(strtolower(str_replace('==', '', array_shift($parts)))); // title
			$x = preg_replace("@[^a-z]@", "", $x);
			if ($x == "thingsyoullneed") $x = "things";	
			if (!in_array($x, $valid_sections))
				continue;
			$section = $dom->createElement($x);


			// process subsections
			$beef = array_shift($parts);
			if ($x == "steps") {
				$subs = preg_split("@(^===[^=]*===)@im", $beef, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				while (sizeof($subs) == 0) {
					$y = array_shift($subs);
					if (!preg_match("@(^===[^=]*===)@", $y))
						continue;
					$sub = $dom->createElement("subsection");
					$x = str_replace("=", "", $y);
					$tnode = $dom->createElement("title");
					$tnode->appendChild($dom->createTextNode($x));
					$sub->appendChild($tnode);
					$body = array_shift($parts);
					processSteps($dom, $sub, $body);
					$section->appendChild($sub);
				}
			}

			// append the section
			$content->appendChild($section);
		}

		$a->appendChild($content);
	
		//attribution
		$attr = $dom->createElement("attribution");		
		$num = $dom->createElement("numeditors");
		$users = array();
		$res = $dbr->select("revision", array("distinct(rev_user_text)"), array("rev_page"=>$t->getArticleID(), "rev_user != 0"), "generate_xml.php", array("ORDER BY" => "rev_timestamp DESC"));
		$num->appendChild($dom->createTextNode($dbr->numRows($res)));
		$attr->appendChild($num);
		while ($row = $dbr->fetchObject($res)) {
			$u = User::newFromName($row->rev_user_text);
			$u->load();
			$name = $u->getRealName() != "" ? $u->getRealName() : $u->getName();
			$users[] = $name;
		}
		$names = $dom->createElement("names");
		$names_text = $dom->createElement("text");
		$names_text->appendChild($dom->createTextNode(implode(",", $users)));
		$names->appendChild($names_text);
		$attr->appendChild($names);
		$a->appendChild($attr);

		$root->appendChild($a);
			
	}

	echo $dom->saveXML();

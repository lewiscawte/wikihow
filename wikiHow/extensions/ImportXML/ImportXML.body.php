<?

class ImportXML extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ImportXML' );
	}

	function getNextTextTag(&$parts, $elem = "<text>")  {
		$skipped = false;
		while (sizeof($parts) > 0) {
			$x = trim(array_shift($parts));
			#echo "got  $x\n";
			if ($x == "" ) {
				#echo "skipping empty\n";
				continue;
			}
			if ($x == "<text>") {
				$skipped = true;
				continue;
			}
			if (!$skipped && strpos($x, $elem) !== 0) {
				#echo "skipping position\n";
				continue;
			}
			$close = str_replace("<", "</", $elem);
			$x = str_replace($elem, "", $x);
			$x = str_replace($close, "", $x);
			$x = preg_replace("@^<!\[CDATA\[@", "", $x);
			$x = preg_replace("@\]\]>$@", "", $x);
			#$x = preg_replace("@^<[^>]*>@", "", $x);
			#$x = preg_replace("@</[^>]*>$@", "", $x);
			preg_match_all("@<a[^>]href=['\"](.*)['\"][^>]*>(.*)</a>@", $x, $matches);
			if ($matches > 0) {
				for ($i = 0; $i < sizeof($matches[0]); $i++) {
					$x = str_replace($matches[0][$i], "[{$matches[1][$i]} {$matches[2][$i]}]", $x);
				}
			}
			preg_match_all("@http[^ ]*@", $x, $matches);
			if (sizeof($matches[0]) > 0) {
				foreach ($matches[0] as $m) {
					$m = trim($m);
					$x = str_replace($m, "[$m $m]", $x);
				}
			}
			if ($parts[0] == "</text>") array_shift($parts);
			return trim($x);
		}
	}

	function parseXML($text) {
		$articles = array();
		$parts = preg_split("@(<[/]?article>)@im", $text, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		while (sizeof($parts) > 0) {
			$x = array_shift($parts);
			if ($x == "<article>") {
				// we got a live one here!
				$a = array_shift($parts);
				$d = preg_split("@(<[/]?.*>)@im", $a, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	#print_r($d);
				$title = null;
				$text = "";
				$prefix = array();
				while (sizeof($d) > 0) {
					$t = trim(array_shift($d));
					if ($t == "") continue;
	#echo "got T +$t+\n";
					if ($t == "<introduction>") {
						$n = self::getNextTextTag($d);
						$text .= $n;
					} else if (strpos($t, "<title>") === 0 && !$title) {
						$title = strip_tags($t);
					} else if ($t == "<steps>") {
						$text .= "\n\n== Steps ==\n";
						array_push($prefix, "#");
					} else if ($t == "<tips>") {
						$text .= "\n== Tips ==\n";
						array_push($prefix, "*");
					} else if ($t == "<substeps>") {
						array_push($prefix, "*");
					} else if ($t == "</substeps>") {
						array_pop($prefix);
					} else if ($t == "<warnings>") {
						$text .= "\n== Warnings ==\n";
						array_push($prefix, "*");
					} else if ($t == "<sources>") {
						$text .= "\n== Sources and Citations ==\n";
						array_push($prefix, "*");
					} else if ($t == "<things>") {
						$text .= "\n== Things You'll Need ==\n";
						array_push($prefix, "*");
					} else if ($t == "<step>" || $t == "<tip>" || $t == "<warning>" || $t == "<thing>" || $t == "<source>") {
						$text .= implode("", $prefix) . " " . self::getNextTextTag($d) . "\n";
					} else if ($t == "<subsection>") {
						// hack fix for WRM
						$found = false;

						for ($i = 0; $i < sizeof($d); $i++) {
							$yy = trim($d[$i]);
							if ($yy == "") continue;
							if (preg_match("@^<title>@", $yy)) {
								$found = true;
								break;
							}
							break;
						}
						if ($found) {
							$subtitle = self::getNextTextTag($d, "<title>");
							$text .= "\n=== " . $subtitle  . " ===\n";
						}
						#array_push($prefix, "#");
					} else if ($t == "</subsection>") {
						#array_pop($prefix);
					} else if ($t == "</steps>" || $t == "</tips>" || $t=="</warnings>" || $t == "</things>") {
						array_pop($prefix);
					}
				}
				$title = trim(preg_replace('@^' . wfMsg('howto', '') . '@im', "", $title));
				$articles[$title] = $text;
			}
		}
	#print_r($articles); exit;
		return $articles;

	}

	function publishArticles() {
		global $wgUser, $wgRequest, $wgOut;

		$olduser = $wgUser;
		$wgUser = User::newFromName('WRM');
		$dbr = wfGetDB(DB_SLAVE);
		$dbw = wfGetDB(DB_MASTER);
		foreach ($wgRequest->getValues() as $key=>$val) {
			if (!preg_match("@publish_[0-9]+@", $key)) continue;
			$id = preg_replace("@publish_@", "", $key);
			$row = $dbr->selectRow('import_articles', array('ia_text', 'ia_title'), array('ia_id'=>$id));
			$title = Title::makeTitle(NS_MAIN, $row->ia_title);
			if (!$title) {
				$wgOut->addHTML("Couldn't make title out of {$row->ia_title} <br/>");
				continue;
			}
			$a = new Article($title);
			if ($title->getArticleID()) {
				$wgOut->addHTML("<a href='{$title->getFullURL()}' target='new'>{$title->getFullText()}</a> already exists, was NOT created.<br/>");
				continue;
			}
			if ($a->doEdit($row->ia_text, "Creating new article")) {
				$wgOut->addHTML("<a href='{$title->getFullURL()}' target='new'>{$title->getFullText()}</a> was created.<br/>");
			} else {
				$wgOut->addHTML("<a href='{$title->getFullURL()}' target='new'>{$title->getFullText()}</a> was NOT created.<br/>");
				continue;
			}
			$dbw->update('import_articles', array('ia_published' => 1, 'ia_published_timestamp' => wfTimestampNow(TS_MW)), array('ia_id'=>$id));
			$dbw->update('newarticlepatrol', array('nap_patrolled' => 1), array('nap_page'=>$title->getArticleID()));
			$dbw->update('recentchanges', array('rc_patrolled' => 1), array('rc_cur_id'=>$title->getArticleID()));
			wfRunHooks("WRMArticlePublished", array($title->getArticleID()));
		}
		$wgUser = $olduser;
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;

		if ( !in_array( 'importxml', $wgUser->getRights() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		// used through ajax
		if ($wgRequest->getVal('delete')) {
			$wgOut->disable();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete('import_articles', array('ia_id'=>$wgRequest->getVal('delete')));
			return;
		}
		if ($wgRequest->getVal('publishedcsv')) {
			$wgOut->disable(); 
			header("Content-type: text/plain;");
			$dbr = wfGetDB(DB_MASTER);
			if ($wgRequest->getVal('errs')) {
				$opts = array('ia_publish_err'=>1);
			} else {
				$opts = array('ia_published'=>1);
			}
			$res = $dbr->select('import_articles', array('ia_title', 'ia_published_timestamp'),  $opts,
					"ImportXML::execute", array("ORDER BY"=>"ia_published_timestamp"));
			while ($row = $dbr->fetchObject($res)) {
				$t = Title::newFromDBKey($row->ia_title);
				$ts = date("Y-m-d", wfTimestamp(TS_UNIX, $row->ia_published_timestamp));
				echo "{$t->getFullURL()}\t{$t->getText()}\t{$ts}\n";
			}
			return;
		}
		// used through ajax
		if ($wgRequest->getVal('view')) {
			$wgOut->disable();
			$dbr = wfGetDB(DB_MASTER);
			$text = $dbr->selectField('import_articles', array('ia_text'), array('ia_id'=>$wgRequest->getVal('view')));
			echo "<textarea class='xml_edit' id='xml_input'>$text</textarea><br/>"
				. '<a onclick="save_xml(' . $wgRequest->getVal('view') . ');" class="button button136" style="float: left;" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">Save</a>';
			return;
		}
		// used through ajax
		if ($wgRequest->getVal('update')) {
			$wgOut->disable();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('import_articles', array('ia_text'=>$wgRequest->getVal('text')), array('ia_id'=>$wgRequest->getVal('update')));
			return;
		}
		if ($wgRequest->getVal('preview')) {
			$dbr = wfGetDB(DB_SLAVE);
			$text = $dbr->selectField('import_articles', array('ia_text'), array('ia_id'=>$wgRequest->getVal('preview')));
			$title  = $dbr->selectField('import_articles', array('ia_title'), array('ia_id'=>$wgRequest->getVal('preview')));
			$t = Title::newFromText($title);
			# try this parse, this is for debugging only
			$popts = $wgOut->parserOptions();
			$popts->setTidy(true);
			$popts->enableLimitReport();
			$parserOutput = $wgParser->parse( $text, $t, $popts);
			$popts->setTidy(false);
			$popts->enableLimitReport( false );
			$wgOut->setPageTitle(wfMsg('howto', $t->getText()));
			$html = WikiHowTemplate::mungeSteps($parserOutput->getText());
			$wgOut->addHTML($html);
			return;
		}

		if ($wgRequest->wasPosted() && ($wgRequest->getVal('xml') || sizeof($_FILES) > 0)) {
			$dbw = wfGetDB(DB_MASTER);

			// input can either come from an XML file or copy+pasted input from the textarea
			// use the file if we have it.
			$input = $wgRequest->getVal('xml');
			foreach ($_FILES as $f) {
				if (trim($f['tmp_name']) == "") continue;
				$input = preg_replace('@\r@', "\n", file_get_contents($f['tmp_name']));
				break;
			}
			
			$articles = self::parseXML($input);
			foreach($articles as $t=>$text) {
				$title = Title::newFromText($t);
				if (!$title) {
					$wgOut->addHTML("cant make title out of $t<br/>");
					continue;
				}
				if ($dbw->selectField('import_articles', 'count(*)', array('ia_title'=>$title->getDBKey())) > 0) {
					$wgOut->addHTML("Article {$title->getText()} already exists, not adding.<br/>");
					continue;
				}
				$dbw->insert('import_articles',
					array('ia_title' => $title->getDBKey(),
						'ia_text' => $text,
						'ia_timestamp' => wfTimestampNow())
					);
				$wgOut->addHTML("Article {$title->getText()} saved.<br/>");
			}
		} else if ($wgRequest->wasPosted() && $wgRequest->getVal('publish_articles')) {
			self::publishArticles();
		}

		$wgOut->addScript('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/import_xml.css"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/import_xml.js"></script>');

		$wgOut->addHTML("
			<form method='post' action='/Special:ImportXML' enctype='multipart/form-data'>
			<input type='hidden' name='publish_articles' value='1'/>
			<a href='#' id='hide_btn' style='float: right;' class='button white_button_150' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' onclick='hidePublished();'>Show Published</a><br/>
			<table class='importxml'><tr class='toprow'><td>ID</td><td>Title</td>
			<td>Created</td><td>Preview</td>
			<td>Published?</td>
			<td>Edit</td><td>Delete?</td>
			<td>Publish</td>
			</tr>");
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('import_articles', array('ia_published', 'ia_title', 'ia_id', 'ia_timestamp'), array(), "ImportXML", array("ORDER BY"=>"ia_id"));
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromText($row->ia_title);
			$class = $row->ia_published == 1 ? "pub" : "";
			$safe_title = urlencode(htmlspecialchars($row->ia_title));
			$wgOut->addHTML("<tr id='row_{$row->ia_id}' class='{$class}'><td>{$row->ia_id}</td><td>{$t->getText()}</td><td>{$row->ia_timestamp}</td>
				<td><a href='/Special:ImportXML?preview={$row->ia_id}' target='new'>Preview</a></td>
				<td>" . ($row->ia_published == 1 ? "yes" : "no") . "</td>
				<td><a onclick=\"edit_xml({$row->ia_id});\">Edit</a></td>
				<td><input style='height: 24px; width: 24px;' type='image' src='/extensions/wikihow/rcwDelete.png' onclick='return delete_xml({$row->ia_id}, \"{$safe_title}\");'></td>
				<td><input type='checkbox' name='publish_{$row->ia_id}'></a></td>
			</tr>");
		}
		$wgOut->addHTML("</table>
			<input type='submit' value='Publish'/>
			</form>
		<br/>
		<a href='/Special:ImportXML?publishedcsv=1'>Published(CSV)</a> | 
		<a href='/Special:ImportXML?publishedcsv=1&errs=1'>Errors (CSV) </a>
		<br/><br/>Insert new articles:
		<form action='/Special:ImportXML' method='post' accept-charset='UTF-8' enctype='multipart/form-data'>

			<textarea class='xmlinput' name='xml'></textarea><br/>
			Or, use an input file in XML format: <input type='file' name='uploadFile'> <br/>
			<input type='submit'>
		</form>
		<div id='magicbox'></div>
		");

	}
}

class ExportXML extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ExportXML' );
	}

	function grabNextToken(&$tokens) {
		while (sizeof($tokens) > 0) {
			$x = trim(array_shift($tokens));
			if ($x != "")
				return $x;
		}
		return null;
	}

	function handleImages($x, &$dom, &$s) {
		// grab images
		global $wgOut;
		preg_match_all("@\[\[Image:[^\]]*\]\]@im", $x, $matches);
		$img = null;
		foreach($matches[0] as $m ) {
			if (!$img)
				$img = $dom->createElement("images");
			$url = $wgOut->parse($m);
			preg_match("@<img[^>]*class=\"mwimage101\"[^>]*>@im", $url, $mx);
			$url = preg_replace("@.*src=\"@", "", $mx[0]);
			$url = preg_replace("@\".*@", "", $url);
			$i = $dom->createElement("image");
			$i->appendChild($dom->createTextNode($url));
			$img->appendChild($i);
		}
		if ($img)
			$s->appendChild($img);
		return;
	}

	function convertLinks($x) {
		#echo "\ngot $x \n";
		if (preg_match("@^http://@", $x) && strpos($x, " ") === false) {
			$x = "<a href='{$x}'>{$x}</a>";
			return $x;
		}
		preg_match_all("@\[[^\]]*\]@", $x, $matches);
		foreach ($matches[0] as $m) {
			if (preg_match("@\[\[@", $m)) continue;
			$d = preg_replace("@\[([^ ]*)[ ]([^\]]*)]@", "<a href='$1'>$2</a>", $m);
			#echo "\treplacing $m with $d\n";
			$x = str_replace($m, $d, $x);
		}
		#echo "\treutrning $x" . print_r($matches, true ) . " \n";
		return $x;
	}

	function processListSection(&$dom, &$sec, $beef, $aresteps = true, $elem = "step") {
		global $wgOut;
		$toks = preg_split("@(^[#\*]+)@im", $beef, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$substeps = null;

		while (sizeof($toks) > 0) {
			$x = self::grabNextToken($toks);
			while (!preg_match("@(^[#\*]+)@im", $x) && sizeof($toks) > 0)  {
				$x = self::grabNextToken($toks);
			}
			if ($aresteps && preg_match("@^#[#\*]@", $x)) {
				if ($substeps == null)
					$substeps = $dom->createElement("substeps");
			} else {
				if ($substeps)
					$sec->appendChild($substeps);
				$substeps = null;
			}
			$x = self::grabNextToken($toks);
			$s = $dom->createElement($elem);
			self::handleImages($x, $dom, $s);
			$t = $dom->createElement("text");
			$x = self::cleanUpText($x);

			if ($x == "") continue;
			if ($elem == "source")
				$x = self::convertLinks($x);

			$t->appendChild($dom->createTextNode($x));
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

	function cleanupText($text, $source = false) {
		// strip templates
		$text= preg_replace("@{{[^}]*}}@", "", $text);
		$text= preg_replace("@\[\[Image:[^\]]*\]\]@", "", $text);
		$text= preg_replace("@\[\[Category:[^\]]*\]\]@", "", $text);
		$text= preg_replace("@<ref[^>]*>.*</ref>@U", "", $text);
		preg_match_all ("@\[\[.*\]\]@U", $text, $matches);
		foreach ($matches[0] as $m) {
			if (strpos($m, "|") !== false)
				$n = preg_replace("@.*\|@", "", $m);
			else
				$n = $m;
			$n = str_replace("]]", "", $n);
			$n = str_replace("[[", "", $n);
			$text = str_replace($m, $n, $text);
		}
		// kill external links
		preg_match_all ("@\[.*\]@U", $text, $matches);
		foreach ($matches[0] as $m) {
			if (strpos($m, " ") !== false)
				$n = preg_replace("@^\[[^ ]* @U", "", $m);
			else
				$n = $m;
			$n = str_replace("]", "", $n);
			$n = str_replace("[", "", $n);
			$text = str_replace($m, $n, $text);
		}
		$text = preg_replace("@''[']?@", "", $text); // rid of bold, itaics;
		$text = preg_replace("@#[#]*@", "", $text);
		$text = preg_replace("@__[^_]*__@", "", $text);
		if ($source)
			$text = strip_tags($text, "<a>");
		else
			$text = strip_tags($text);
		return trim($text);
	}

	function execute($par) {
		global $wgRequest, $wgUser, $wgOut, $wgEmbedVideoServiceList;

		/* disabled this check, per Eliz and Jack.  added noindex meta tag.
		if ( !in_array( 'importxml', $wgUser->getRights() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		*/

		if (!$wgRequest->wasPosted()) {
			$wgOut->addMeta('robots', 'noindex');
			$wgOut->addHTML(<<<END
			<form action='/Special:ExportXML' method='post' enctype="multipart/form-data" >
			URLS to export: <textarea name='xml'></textarea>
			WOI category mappings: <input type="file" name="uploadFile"> <br/>
			<input type='submit'>
			</form>
END
			);
			return;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$urls = split("\n", $wgRequest->getVal('xml'));
		$valid_sections = array("steps", "tips", "warnings", "things", "sources", "videos");

		$dom = new DOMDocument("1.0");
		$root = $dom->createElement("wikihowmedia");
		$dom->appendChild($root);

		// did we get a WOI category mapping sent to us?
		$woi_map = array();
		foreach ($_FILES as $f) {
			if (trim($f['tmp_name']) == "") continue;
			$text = preg_replace('@\r@', "\n", file_get_contents($f['tmp_name']));
			$lines = split("\n", $text);
			foreach ($lines as $l) {
				$tokens = split(",", $l);
				$url = array_shift($tokens);
				$key = urldecode(preg_replace("@http://www.wikihow.com/@im", "", $url));
				if (preg_match("@index.php?@", $url)) {
					$parts = parse_url($url);
					$query = $parts['query'];
					$params = array();
					$tx = split("&", $query); 
					foreach($tx as $v) {
						$xx = split("=", $v);
						if ($xx[0] == "title") {
							$key = urldecode($xx[1]);
							break;
						}
					}
					
				}
				if ($key == "") continue;
				$woi_map[$key] = $tokens;
				$urls[] = $url;
			}
		}

		foreach ($urls as $url) {
			$origUrl = $url;
			if (trim($url) == "")
				continue;
			$url = trim($url);
			$url = str_replace("http://www.wikihow.com/", "", $url);
			$url = preg_replace('@^\s*index\.php\?@', '', $url);
			$kv = split('&', $url);
			$urlParams = array();
			# decode URLs that look like this:
			#   http://www.wikihow.com/index.php?title=Sing&oldid=4956082
			foreach ($kv as $pair) {
				$a = split('=', $pair);
				if (count($a) < 2) {
					$urlParams['title'] = $a[0];
				} else {
					$urlParams[$a[0]] = $a[1];
				}
			}
			$t = Title::newFromDBKey(urldecode($urlParams['title']));
			if (!$t) {
				echo "Can't get title from {$origUrl}\n";
				continue;
			}
			$revid = !empty($urlParams['oldid']) ? $urlParams['oldid'] : '';
			$r = Revision::newFromTitle($t, $revid);
			if (!$r) {
				echo "Can't get revision from {$origUrl}\n";
				continue;
			}
			$text = $r->getText();


			$a = $dom->createElement("article");

			// title
			$x = $dom->createElement("title");
			// make sure the title is in the form "How to x y z"
			$title = $t->getText();
			if (!preg_match('@' . wfMsg('howto', '') . '@', $title))
				$title = wfMsg('howto', $title);
			$x->appendChild($dom->createTextNode($title));
			$a->appendChild($x);

			// intro
			$content = $dom->createElement("content");
			$intro = Article::getSection($text, 0);
			$i = $dom->createElement("introduction");
			self::handleImages($intro, $dom, $i);
			$intro = self::cleanupText($intro);
			$n = $dom->createElement("text");
			$n->appendChild($dom->createTextNode($intro));
			$i->appendChild($n);
			$content->appendChild($i);

			# woi tags and categories
			if (isset($woi_map[$t->getDBKey()])) {
				$params = $woi_map[$t->getDBKey()];
				//tags
				$tags = array();
				for($i = 2; $i < 4; $i++) {
					if ($params[$i] != "None")
						$tags[] = $params[$i];
				}

				if (sizeof($tags) > 0) {
					$xx = $dom->createElement("tags");
					$xx->appendChild($dom->createTextNode(implode(",", $tags)));
					$a->appendChild($xx);
				}

				$yy = $dom->createElement("categories");
				if ($params[0] != "None") {
					$zz = $dom->createElement("category");
					$zz->setAttribute("type", "mainmenu");
					$zz->appendChild($dom->createTextNode($params[0]));
					$yy->appendChild($zz);
				}
				if ($params[1] != "None") {
					$zz = $dom->createElement("category");
					$zz->setAttribute("type", "featured");
					$zz->appendChild($dom->createTextNode($params[1]));
					$yy->appendChild($zz);
				}
				$a->appendChild($yy);
			}


			$parts = preg_split("@(^==[^=]*==)@im", $text, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$sources_element = null;
			while (sizeof($parts) > 0) {
				$x = trim(strtolower(str_replace('==', '', array_shift($parts)))); // title
				$x = preg_replace("@[^a-z]@", "", $x);
				if ($x == "thingsyoullneed") $x = "things";
				if ($x == "sourcesandcitations") $x = "sources";
				if ($x == "video") $x = "videos";
				if (!in_array($x, $valid_sections))
					continue;
				$section = $dom->createElement($x);

				if ($x == "sources") {
					$sources_element = $section;
				}

				// process subsections
				$beef = array_shift($parts);
				if ($x == "steps") {
					if (preg_match("@(^===[^=]*===)@im", $beef))  {
						$subs = preg_split("@(^===[^=]*===)@im", $beef, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
						while (sizeof($subs) > 0) {
							$y = array_shift($subs);
							$sub = null;
							if (preg_match("@(^===[^=]*===)@", $y)) {
								// this is a subsection
								$sub = $dom->createElement("subsection");
								$x = str_replace("=", "", $y);
								$tnode = $dom->createElement("title");
								$ttext = self::cleanupText($x);
								$tnode->appendChild($dom->createTextNode($ttext));
								$sub->appendChild($tnode);
								$body = array_shift($subs);
								self::processListSection($dom, $sub, $body);
								$section->appendChild($sub);
							} else {
								// this is not a subsection, it could be a set of steps preceeding  a subsection
								$body = $y;
								self::processListSection($dom, $section, $y);
							}
						}
					} else {
						self::processListSection($dom, $section, $beef);
					}
				} else if ($x == "videos") {
					// {{Video:...}} embeds can point to other videos, so
					// we need a loop here
					$title = $t->getText();
					while (true) {
						$vid_t = Title::makeTitle(NS_VIDEO, $title);
						$vid_r = Revision::newFromTitle($vid_t);
						if ($vid_r) {
							$vid_text = $vid_r->getText();
							$tokens = split("\|", $vid_text);
							if (preg_match('@^{{video:([^|}]*)@i', $tokens[0], $m)) {
								$title = $m[1];
								if (!empty($title)) continue;
							} else {
								$provider = $tokens[1];
								$id = $tokens[2];
								// special hack for wonderhowto videos that
								// are actually youtube videos
								if ($provider == 'wonderhowto' && preg_match('@http://www.youtube.com/v/([^&/">]*)@', $vid_text, $m)) {
									$provider = 'youtube';
									$id = $m[1];
								}
								$found = false;
								foreach ($wgEmbedVideoServiceList as $service=>$params) {
									if ($provider == $service) {
										$url = str_replace("$1", $id, $params['url']);
										if ($url != "") {
											$vid = $dom->createElement("video");
											$vid->appendChild($dom->createTextNode($url));
											$section->appendChild($vid);
											$found = true;
											break;
										}
									}
								}
								if (!$found) {
									$text = htmlspecialchars_decode($vid_text);
									preg_match("@src&61;\"[^\"]*@", $text, $matches);
									if (sizeof($matches[0]) > 0) {
										$url = preg_replace("@.*\"@", "", $matches[0]);
										$vid = $dom->createElement("video");
										$vid->appendChild($dom->createTextNode($url));
										$section->appendChild($vid);
									}
								}
							}
						}
						break;
					}
				} else {
					self::processListSection($dom, $section, $beef, false, preg_replace("@s$@", "", $x));
				}

				// append the section
				$content->appendChild($section);
			}

			// process references
			preg_match_all("@<ref[^>]*>.*</ref>@imU", $text, $matches);
			foreach($matches[0] as $m) {
				if (!$sources_element)
					$sources_element = $dom->createElement("sources");
				$m = preg_replace("@<[/]*ref[^>]*>@", "", $m);
				$e = $dom->createElement("source");
				$tx = $dom->createElement("text");
				$m = self::convertLinks($m);
				$m = self::cleanUpText($m, true);
				$tx->appendChild($dom->createTextNode($m));
				$e->appendChild($tx);
				$sources_element->appendChild($e);
				$content->appendChild($sources_element);
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
			$names_text->appendChild($dom->createTextNode(implode(", ", $users)));
			$names->appendChild($names_text);
			$attr->appendChild($names);
			$a->appendChild($attr);

			$root->appendChild($a);

		}
		$wgOut->disable();
		header("Content-type: text/xml;");
		echo $dom->saveXML();
	}
}

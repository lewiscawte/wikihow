<?
//
// Generate XML for the Duck Duck Go search engine to show inline results
// for how-to queries.
//

require_once('commandLine.inc');

function escapeDuck($str) {
	return trim(preg_replace("@[\n\t]@", " ", $str));
}

function flatten($arg) {
	$result = array();
	if (is_array($arg)) {
		foreach ($arg as $k=>$v) {
			$result[] = $k;
			$result = array_merge($result, flatten($v));
		}
	}
	return $result;
}

$baseMemory = memory_get_usage();

$dbr = wfGetDB(DB_SLAVE);

$res = $dbr->select('page',
	array('page_namespace', 'page_title'),
	array(
		'page_namespace' => NS_MAIN,
		'page_is_redirect' => 0,
		//'page_title'=>'Get-Six-Pack-Abs',
		//'page_id=12813',
		'page_id NOT IN (5, 5791)',
	),
	__FILE__,
	//array("LIMIT"=>1000, "ORDER BY"=>"page_counter desc")
	array(
		'ORDER BY' => 'page_id',
		//'LIMIT' => 1,
	));

//echo "#" . date("r") . " - " . (memory_get_usage() - $baseMemory) . "\n";
$titles = array();
while ($row = $dbr->fetchObject($res)) {
	$t = Title::makeTitle($row->page_namespace, $row->page_title);
	if (!$t) {
		continue;
	}
	$titles[] = $t;
}

//echo "#" . date("r") . " - " . (memory_get_usage() - $baseMemory) . "\n";
$index = 0;
foreach ($titles as $t) {

	$r = Revision::newFromTitle($t);
	if (!$r) {
		continue;
	}

	$text = $r->getText();

	$sx = array();
	$index = 1;
	while ($section = Article::getSection($text, $index)) {
		$steps = preg_split("@^(#)@im", $section, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$found = false;
		while (sizeof($steps) > 0) {
			$s = array_shift($steps);
			if ($s == "#") {
				$s = array_shift($steps);
				$s = preg_replace("@\[\[Image:[^\]]*\]\]@", "", $s);
				$sx[] = escapeDuck(strip_tags($wgOut->parse(($s))));
				$found = true;
				if (sizeof($sx) > 1 || strlen($sx[0]) > 50) {
					break;
				}
			}
		}
		if ($found) {
			break;
		}
		$index++;
	}
	if (sizeof($sx) == 1) {
		$sx[] = ""; // keep # of columns consistent
	}

#print_r($sx);

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
	$tree = array_unique(flatten($t->getParentCategoryTree()));
	foreach ($tree as $x) {
		$x = preg_replace("@Category:@", "", $x);
		$ct = Title::newFromText($x);
		if ($ct) {
			$cat_str_array[] = str_replace(",", " ", $ct->getText());
		}
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

	$related_str = implode("\t", $related_arr);
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

	#$key = escapeDuck(generateSearchKey($t->getText()));
	echo "{$t->getArticleID()}\t{$t->getText()}\t{$t->getFullURL()}\t"
		. implode("\t", $sx)
		. "\t$photo\t$cat_str\t$related_str\n";
	$index++;
	if ($index % 1000 == 0) {
		//echo "#" . date("r") . " - " . (memory_get_usage() - $baseMemory) . "\n";
	}
}

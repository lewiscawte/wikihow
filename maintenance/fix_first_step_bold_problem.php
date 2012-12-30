<?
require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);

$res = $dbr->select("page", array("page_title"), array("page_namespace" => NS_MAIN, "page_is_redirect"=>0), "fix_bold", array("LIMIT" => 1000, "ORDER BY" => "rand()")); 

$wgUser = User::newFromName("BoldStepFixer");

$fixed = 0;
while ($row = $dbr->fetchObject($res)) {
	$t = Title::makeTitle(NS_MAIN, $row->page_title);
	if (!$t) {
		continue;
	}
	$r = Revision::newFromTitle($t);
	if (!$r) {
		continue;
	}
	$fb = "[^a-zA-Z0-9_|\[\]]";
	$text = $r->getText();
	$changed = false;
	for ($i = 0; $i < 4; $i++) {
		$section = Article::getSection($text, $i);
		if (preg_match("@^==[ ]*Steps@", $section)) {
			$lines = split("\n", $section);
			foreach ($lines as $line) {
				if (preg_match("@\[\[Image:@", $line))
					continue;
				if (!preg_match("@^#@", $line))
					continue;
				if (!preg_match("@'''@", $line))
					continue;
				$parts = preg_split("@('''|\.|\!)@", $line, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				$newline = "";
				$open = false;
				while ($x = array_shift($parts)) {
					if ($x === "'''") {
						if ($open) $open = false;
						else $open = true;
						continue;
					}
					$newline .= $x;
					if ($x == "." || $x == "!") break;
				}
				$tail = implode("", $parts);
				if ($open) $tail = preg_replace("@'''@", "", $tail, 1);
				$newline .= $tail;
				$text = str_replace($line, $newline, $text);	
				$changed = true;
			}
		}
	}
	if ($changed) {
		$a  = new Article($t);
		$a->doEdit($text, "fixing the bold issue");
		echo "{$t->getFullURL()}\n";
		$fixed++;
	}
	if ($fixed == 100)
		break;
}

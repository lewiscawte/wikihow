<?
	require_once('commandLine.inc');

	$dbw = wfGetDB(DB_MASTER);
	$res = $dbw->select ('page',
			array ('page_title', 'page_namespace'),
			array ('page_namespace'=>NS_MEDIAWIKI)
	);
	while ($row = $dbw->fetchObject($res)) {
		$t = Title::makeTitle(NS_MEDIAWIKI, $row->page_title);
		if (!$t) {
			echo "not t for {$row->page_title}\n";
			continue;
		}
		$r = Revision::newFromTitle($t);
		if (strpos($r->getText(), "clickshare") !== false) {
			echo $t->getFullURL() . "\n";
		}
	}

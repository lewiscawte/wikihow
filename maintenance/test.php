<?
	require_once('commandLine.inc');

	$dbw = wfGetDB(DB_MASTER);
	$res = $dbw->select ('page',
			array ('page_title', 'page_namespace'),
			array()
	);
	while ($row = $dbw->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$t) {
			#echo "not t for {$row->page_title}\n";
			continue;
		}
		$r = Revision::newFromTitle($t);
		if (!$r) {
			#echo "not r for {$row->page_namespace} {$row->page_title}\n";
			continue;
		}
		if (stripos($r->getText(), "You Can't Edit") !== false) {
			echo $t->getFullURL() . "\n";
		}
	}

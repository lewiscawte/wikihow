<?
require_once("commandLine.inc");

$wgUser = User::newFromName('WRM');
$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

$limit = preg_replace("@[^0-9]@", "", wfMsg("wrm_hourly_limit")); 

if ($limit == 0) {
	exit;
}

$res = $dbr->select('import_articles', array('ia_id', 'ia_text', 'ia_title'), array('ia_published'=>0), "publish_wrm_articles", array("ORDER BY"=>"rand()", "LIMIT"=>$limit));

echo "Starting : " . date("r") . ", doing $limit \n";
while ($row = $dbr->fetchObject($res)) {
	$id = $row->ia_id;
	$title = Title::makeTitle(NS_MAIN, $row->ia_title);
	if (!$title) {
		echo("Couldn't make title out of {$row->ia_title} \n");
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	$a = new Article($title);
	if ($title->getArticleID() && !$a->mIsRedirect) {
		echo "Can't overwrite non-redirect article {$title->getText()}\n";
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	if ($a->doEdit($row->ia_text, "Creating new article", EDIT_FORCE_BOT)) {
		// success
		echo "Published {$title->getText()}\n";
	} else {
		echo "Couldn't save {$title->getText()}\n";
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	$dbw->update('import_articles', array('ia_published' => 1, 'ia_published_timestamp' => wfTimestampNow(TS_MW)), array('ia_id'=>$id));
	$dbw->update('recentchanges', array('rc_patrolled' => 1), array('rc_user_text'=>'WRM'));
	wfRunHooks("WRMArticlePublished", array($title->getArticleID()));
}
echo "Finished: " . date("r") . "\n";

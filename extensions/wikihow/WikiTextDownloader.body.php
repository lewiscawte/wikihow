<?
/*
* 
*/
class WikiTextDownloader extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('WikiTextDownloader');
	}

	function execute($par) {
		global $wgOut, $wgRequest;

		if (!self::isAuthorized()) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_SLAVE);
		$r = Revision::loadFromPageId($dbr, $wgRequest->getVal('pageid'));
		if ($r) {
			$title = $r->getTitle()->getText();
			TitusQueryTool::outputFile("$title.txt", $r->getText(), "application/force-download");
		}
		return;
	}

	public static function isAuthorized() {
		global $wgUser, $isDevServer;
		return false !== array_search(strtolower($wgUser->getName()), array_map('strtolower', explode("\n", trim(wfMsg('wikitext_widget_users')))));
	}
}

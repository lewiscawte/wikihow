<?
class CheckJS extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'CheckJS' );
    }


    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser;
	
		$wgOut->setArticleBodyOnly(true);
		$dbw = &wfGetDB(DB_MASTER);
		if ($wgRequest->getVal('js') == 'yes')
			$dbw->query("insert LOW_PRIORITY into checkjs values (1, {$wgUser->getID()});");
		else if ($wgRequest->getVal('js') == 'no')
			$dbw->query("insert LOW_PRIORITY into checkjs values (0, {$wgUser->getID()});");
		else if ($wgRequest->getVal('selection', null) != null ) { 
			$dbw->query("insert LOW_PRIORITY into share_track (selection) values (" . $wgRequest->getVal('selection') . ");");
		}
		return;	
	}
}

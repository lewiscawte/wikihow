<?
class Toolbarhelper extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Toolbarhelper' );
    }
    function execute ($par) {
		global $wgOut, $wgUser, $wgRequest, $wgServer;
		$wgOut->setArticleBodyOnly(true);
		$go = $wgRequest->getVal('go', 'null');
		if ($go == 'talk') {
			$t = $wgUser->getTalkPage();
			$wgOut->redirect( $t->getFullURL() . "#post");
			return;
		}
		if( $wgUser->getNewtalk())
			$wgOut->addHTML("1");
		else
			$wgOut->addHTML("0");
		return;
	}
}
	

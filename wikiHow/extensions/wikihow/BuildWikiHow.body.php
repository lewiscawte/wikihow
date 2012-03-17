<?
class BuildWikiHow extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'BuildWikiHow' );
    }


    function execute ($par) {
		global $wgRequest, $wgOut;
		require_once('WikiHow.php');
		$wgOut->disable();
		$whow = WikiHow::loadFromRequest($wgRequest);
		if ($wgRequest->getVal('parse') == '1') {
			echo $wgOut->parse($whow->formatWikiText());
		} else {
			echo $whow->formatWikiText();
		}
		return;
	}
}


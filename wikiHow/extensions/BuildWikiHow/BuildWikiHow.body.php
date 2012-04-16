<?

class BuildWikiHow extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'BuildWikiHow' );
    }

    function execute ($par) {
		global $wgRequest, $wgOut;
		$wgOut->disable();
		$whow = WikiHow::newFromRequest($wgRequest);
		if ($wgRequest->getVal('parse') == '1') {
			echo $wgOut->parse($whow->formatWikiText());
		} else {
			echo $whow->formatWikiText();
		}
		return;
	}
}


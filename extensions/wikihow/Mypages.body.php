<?
class Mypages extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'Mypages' );
    }


    function execute ($par) {
		global $wgOut, $wgUser, $wgRequest; 
	    $fname = "wfMypages";
	
		$url = '';
		switch ($par) {
			case 'Contributions':
				$url = Title::makeTitle(NS_SPECIAL, "Contributions")->getFullURL() . "/" . $wgUser->getName();
				break;
			case 'Fanmail':
				$url = Title::makeTitle(NS_USER_KUDOS, $wgUser->getName())->getFullURL();
				break;
	
		}
		if ($url != '')
			$wgOut->redirect($url);
	}
}

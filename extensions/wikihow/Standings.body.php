<?
class Standings extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Standings' );
    }

    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$wgOut->disable(); 
		$result = array();
		if ($target) {
			$c = new $target();
			$result['html'] = $c->getStandingsTable();
		} else {
			$result['error'] = "No target specified.";
		}
		print_r(json_encode($result));
		return;
	}	
}	

<?
class Bunchpatrol extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'Bunchpatrol' );
    }

    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		$x = new LSearch(); 
		$x->query("poodles");

		print_r($x); exit;
	}
}	

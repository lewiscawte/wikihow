<?php

class MyPages extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'MyPages' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser;

		if ( $par == 'Fanmail' ) {
			$url = Title::makeTitle( NS_USER_KUDOS, $wgUser->getName() )->getFullURL();
		} else { // default to 'Contributions' instead of empty page
			$url = SpecialPage::getTitleFor( 'Contributions', $wgUser->getName() )->getFullURL();
		}

		$wgOut->redirect( $url );
	}

}
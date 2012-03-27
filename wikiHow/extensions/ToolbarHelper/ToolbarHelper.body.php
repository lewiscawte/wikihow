<?php

class ToolbarHelper extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ToolbarHelper' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wgRequest;

		$wgOut->setArticleBodyOnly( true );

		$go = $wgRequest->getVal( 'go', 'null' );

		if ( $go == 'talk' ) {
			$t = $wgUser->getTalkPage();
			$wgOut->redirect( $t->getFullURL() . '#post' );
			return;
		}

		if( $wgUser->getNewtalk() ) {
			$wgOut->addHTML( '1' );
		} else {
			$wgOut->addHTML( '0' );
		}

		return;
	}
}


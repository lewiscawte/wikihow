<?php

class Ads extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Ads' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgSquidMaxage, $wgMimeType;

		$params = explode( '/', $par );
		$ads = array_shift( $params );

		$wgOut->setSquidMaxAge( $wgSquidMaxage );
		$wgOut->setArticleBodyOnly( true );
		$wgMimeType = 'application/x-javascript';

		$wgOut->addHTML( wfMsg( $ads, $params[0] ) );

		return;
	}

}

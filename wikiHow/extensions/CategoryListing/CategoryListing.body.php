<?php

class CategoryListing extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'CategoryListing' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut;

		// Set the page title...
		$this->setHeaders();
		// ...and overide the default robot policy as we want search engines
		// to index this page
		$wgOut->setRobotPolicy( 'index,follow' );

		// Add the "learn how to categorize an article" message
		$wgOut->addWikiMsg( 'categorylisting-subheader' );
		// The actual content of the page; before displaying it, get rid of the
		// surrounding pre tags
		$wgOut->addHTML( preg_replace( '/\<[\/]?pre\>/', '',
			wfMsg( 'categorylisting-categorytable', /*wfGetPad()*/'' )
		) );

		return;
	}
}

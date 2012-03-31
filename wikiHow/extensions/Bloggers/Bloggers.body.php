<?php

class Bloggers extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Bloggers' );
	}

	/**
	 * The callback made to process and display the output of the
	 * Special:Bloggers page.
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgHooks, $wgOut;

		$wgHooks['ShowBreadCrumbs'][] = 'Bloggers::removeBreadCrumbsCallback';

		$key = wfMessage( 'bloggers-formkey' )->inContentLanguage()->plain();
		$wgOut->addHTML(
			'<iframe src="https://spreadsheets.google.com/embeddedform?formkey=' . $key .
			'" width="630" height="693" frameborder="0" marginheight="0" marginwidth="0">' .
			wfMessage( 'bloggers-loading' )->plain() . '</iframe>'
		);
	}

	public static function removeBreadCrumbsCallback( &$showBreadCrumb ) {
		$showBreadCrumb = false;
		return true;
	}

}
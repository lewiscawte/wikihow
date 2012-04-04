<?php
/**
 * @file
 */
class CheckMyLinks extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'CheckMyLinks' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wgParser, $wgTitle, $wgLang;

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Need to be logged in in order to use this feature
		if ( !$wgUser->isLoggedIn() ) {
			$wgOut->addWikiMsg( 'checkmylinks-not-logged-in' );
			return;
		}

		// Show a summary to the users about this feature so that they won't
		// get so confused
		$wgOut->addWikiMsg( 'checkmylinks-summary' );

		$t = Title::makeTitle( NS_USER, $wgUser->getName() . '/Mylinks' );
		// If the user has a customized "my links" page, start doing our magic;
		// otherwise show an error message
		if ( $t->getArticleID() > 0 ) {
			$r = Revision::newFromTitle( $t );
			$text = $r->getText();
			$ret = '';
			if ( $text != '' ) {
				$ret = '<h3>' . wfMsg( 'mylinks' ) . '</h3>';
				$options = new ParserOptions();
				$output = $wgParser->parse( $text, $wgTitle, $options );
				$ret .= $output->getText();
			}
			$size = strlen( $ret );
			$formattedSize = $wgLang->formatNum( $size );
			if ( $size > 3000 ) {
				$wgOut->addWikiMsg( 'checkmylinks-size-bad', $formattedSize );
			} else {
				$wgOut->addWikiMsg( 'checkmylinks-size-good', $formattedSize );
			}
		} else {
			$wgOut->addWikiMsg( 'checkmylinks-error' );
		}
	}
}

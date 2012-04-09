<?php

class Sitemap extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Sitemap' );
	}

	/**
	 * Get the top-level categories by parsing the page defined in
	 * MediaWiki:Sitemap-article and excluding the categories listed in
	 * MediaWiki:Sitemap-excluded-categories.
	 *
	 * @return Array: array of top-level categories
	 */
	function getTopLevelCategories() {
		$results = array();
		$categoryArticle = Title::newFromText( wfMsgForContent( 'sitemap-article' ) );
		$excludedCategories = explode( "\n", wfMsgForContent( 'sitemap-excluded-categories' ) );

		$revision = Revision::newFromTitle( $categoryArticle );
		if ( !$revision ) {
			return $results;
		}

		// INTL: If there is a redirect to a localized page name, follow it
		if( strpos( $revision->getText(), '#REDIRECT' ) !== false ) {
			$revision = Revision::newFromTitle( Title::newFromRedirect( $revision->getText() ) );
		}

		$lines = explode( "\n", $revision->getText() );
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\*[^\*]/', $line ) ) {
				$line = trim( substr( $line, 1 ) );
				// If $line is not an excluded category (as defined by the MW
				// message), add it to the $results array
				if ( !in_array( $line, $excludedCategories ) ) {
					$results[] = $line;
				}
			}
		}

		return $results;
	}

	/**
	 * Get the subcategories for a given Title object.
	 *
	 * @param $t Object: Title object (usually a page in the category NS)
	 * @return Array: array of subcategories (page_title of each subcategory)
	 */
	function getSubcategories( $t ) {
	 	$dbr = wfGetDB( DB_SLAVE );
		$subcats = array();
		$res = $dbr->select(
			array( 'categorylinks', 'page' ),
			array( 'page_title' ),
			array(
				'page_id = cl_from',
				'cl_to' => $t->getDBkey(),
				'page_namespace' => NS_CATEGORY
			),
			__METHOD__
		);
		foreach ( $res as $row ) {
			// @todo FIXME: not very internationally compatible, eh?
			if( strpos( $row->page_title, 'Requests' ) !== false ) {
				continue;
			}
			$subcats[] = $row->page_title;
		}
		return $subcats;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgOut;

		// Allow search robots to index this page, obviously
		$wgOut->setRobotPolicy( 'index,follow' );

		// Add CSS
		$wgOut->addModules( 'ext.sitemap' );

		$topcats = $this->getTopLevelCategories();
		if ( empty( $topcats ) ) {
			$wgOut->addWikiMsg( 'sitemap-not-defined' );
		}

		// Set the page title
		$wgOut->setPageTitle( wfMsg( 'sitemap' ) );

		$count = 0;
		$wgOut->addHTML(
			'<table align="center" class="cats" width="90%" cellspacing="10px">'
		);

		foreach ( $topcats as $cat ) {
			$t = Title::newFromText( $cat, NS_CATEGORY );
			$subcats = $this->getSubcategories( $t );
			if ( $count % 2 == 0 ) {
				$wgOut->addHTML( '<tr>' );
			}
			$wgOut->addHTML(
				'<td><h3>' . Linker::link($t, $t->getText() ) .
				'</h3><ul id="catentry">'
			);
			foreach ( $subcats as $sub ) {
				$t = Title::newFromText( $sub, NS_CATEGORY );
				$wgOut->addHTML( '<li>' . Linker::link( $t, $t->getText() ) . "</li>\n" );
			}
			$wgOut->addHTML( "</ul></td>\n" );
			if ( $count % 2 == 1 ) {
				$wgOut->addHTML( '</tr>' );
			}
			$count++;
		}

		$wgOut->addHTML( '</table>' );
	}

}

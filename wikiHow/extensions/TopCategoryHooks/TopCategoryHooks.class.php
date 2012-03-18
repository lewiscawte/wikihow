<?php

class TopCategoryHooks {

	public static function flattenTopLevelCats( $arg, &$results = array() ) {
		if ( is_array( $arg ) ) {
			foreach ( $arg as $a => $p ) {
				if ( is_array( $p ) && sizeof( $p ) > 0 ) {
					TopCategoryHooks::flattenTopLevelCats( $p, $results );
				} else {
					$results[] = $a;
				}
			}
		}
		return $results;
	}

	public static function getTopLevelCats() {
		global $wgMemc;

		$key = wfMemcKey( 'toplevelcats_categorylinkstop' );
		$val = $wgMemc->get( $key );
		if ( $val ) {
			return $val;
		}

		// initialize the top level array of categories;
		$x = CategoryHelper::getTopLevelCategoriesForDropDown();
		$top = array();

		foreach ( $x as $cat ) {
			$cat = trim( $cat );
			$ignored = trim( wfMsgForContent( 'topcategoryhooks-ignored' ) );
			// Ignore empty categories and categories given in the system message
			if ( $cat == '' || in_array( $cat, $ignored ) ) {
				continue;
			}
			$top[] = $cat;
		}

		// Cache for a day
		$wgMemc->set( $key, $top, 86400 );

		return $top;
	}

	public static function updateTopLevelCatTable( $linker ) {
		global $wgContLang;

		// LinksUpdate does not do a lazy update, so neither do we!
		$dbw = wfGetDB( DB_MASTER );
		$title = $linker->mTitle;
		$tree = $title->getParentCategoryTree();
		$mine = array_unique( TopCategoryHooks::flattenTopLevelCats( $tree ) );
		$dbw->delete(
			'categorylinkstop',
			array( 'cl_from' => intval( $title->getArticleID() ) ),
			__METHOD__
		);

		$top = TopCategoryHooks::getTopLevelCats();
		$localizedCatNS = $wgContLang->getNsText( NS_CATEGORY );

		foreach ( $mine as $m ) {
			$m = str_replace( $localizedCatNS . ':', '', $m );
			$y = Title::makeTitle( NS_CATEGORY, $m );
			if ( in_array( $y->getText(), $top ) ) {
				$dbw->insert(
					'categorylinkstop',
					array(
						'cl_from' => intval( $title->getArticleID() ),
						'cl_to' => $y->getDBkey(),
						'cl_sortkey' => $title->getText()
					),
					__METHOD__
				);
			}
		}

		return true;
	}

	/**
	 * Get the top-level categories for a given Title object.
	 *
	 * @param $title Object: Title object
	 * @return Array: array of top-level categories or an empty array if no
	 *                Title was given
	 */
	public static function getTopLevelCategories( $title ) {
		$result = array();
		if ( !$title ) {
			return $result;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'categorylinkstop',
			array( 'cl_to' ),
			array( 'cl_from' => intval( $title->getArticleID() ) ),
			__METHOD__
		);

		foreach( $res as $row ) {
			$results[] = Title::makeTitle( NS_CATEGORY, $row->cl_to );
		}

		return $results;
	}

	/**
	 * Handler for the MediaWiki update script, update.php; this code is
	 * responsible for creating the categorylinkstop table in the database when
	 * the user runs maintenance/update.php.
	 *
	 * @param $updater DatabaseUpdater
	 * @return Boolean: true
	 */
	public static function createTableInDB( $updater ) {
		$dir = dirname( __FILE__ );

		$updater->addExtensionUpdate( array(
			'addTable', 'categorylinkstop', "$dir/categorylinkstop.sql", true
		) );

		return true;
	}
}
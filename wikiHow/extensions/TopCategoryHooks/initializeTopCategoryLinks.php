<?php
/**
 * Maintenance script to populate the categorylinkstop database table.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/TopCategoryLinks/ and we don't need to move this file to $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class InitializeCategoryLinksTop extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Initialize data for the categorylinkstop database table.';
		$this->addArg( 'batch', 'Used to calculate the OFFSET in the first SQL query; set to 0 to delete all data from the table' );
	}

	function flatten( $arg, &$results = array() ) {
		if ( is_array( $arg ) ) {
			foreach ( $arg as $a => $p ) {
				if ( is_array( $p ) ) {
					$this->flatten( $p, $results );
				} else {
					$results[] = $a;
				}
			}
		}
		return $results;
	}

	public function execute() {
		global $wgContLang;

		$dbr = wfGetDB( DB_SLAVE );
		$dbw = wfGetDB( DB_MASTER );

		$localizedCategoryNamespace = $wgContLang->getNsText( NS_CATEGORY );

		$batch = $this->getArg( 0, 0 /* default value */ );
		$opts = array(
			'ORDER BY' => 'page_id',
			'LIMIT' => 10000,
			'OFFSET' => ( $batch * 10 * 1000 )
		);
		$res = $dbr->select(
			'page',
			array( 'page_namespace', 'page_title' ),
			array( 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ),
			__METHOD__,
			$opts
		);

		// initialize the top level array of categories
		$x = CategoryHelper::getTopLevelCategoriesForDropDown();
		$top = array();
		foreach ( $x as $cat ) {
			$cat = trim( $cat );
			$ignored = explode( "\n", wfMsgForContent( 'topcategoryhooks-ignored' ) );
			if ( $cat == '' || in_array( $cat, $ignored ) ) {
				continue;
			}
			$top[] = $cat;
		}

		if ( $batch == 0 ) {
			$dbw->query(
				"DELETE FROM {$dbw->tableName( 'categorylinkstop' )}",
				__METHOD__
			);
		}

		$count = 0;
		$updates = array();
		$titles = array();
		foreach ( $res as $row ) {
			$t = Title::makeTitle( $row->page_namespace, $row->page_title );
			if ( !$t ) {
				continue;
			}
			$titles[] = $t;
		}

		$this->output( "Got titles\n" );
		foreach ( $titles as $t ) {
			$tree = $t->getParentCategoryTree();
			if ( $count == 0 ) {
				$this->output( "Starting with page ID {$t->getArticleID()}\n" );
			}
			$mine = array_unique( $this->flatten( $t->getParentCategoryTree() ) );
			foreach ( $mine as $m ) {
				$y = Title::makeTitle( NS_CATEGORY, str_replace( $localizedCategoryNamespace . ':', '', $m ) );
				if ( in_array( $y->getText(), $top ) ) {
					$updates[] = array(
						'cl_from' => $t->getArticleID(),
						'cl_to' => $y->getDBKey()
					);
				}
			}
			$count++;
			if ( $count % 1000 == 0 ) {
				$this->output( "Done $count\n" );
			}
		}

		$this->output( 'doing ' . sizeof( $updates ) . "\n" );
		$count = 0;

		foreach ( $updates as $u ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert( 'categorylinkstop', $u, __METHOD__ );
			$count++;
			if ( $count % 1000 == 0 ) {
				$this->output( "Done $count\n" );
			}
		}
	}
}

$maintClass = 'InitializeCategoryLinksTop';
require_once( RUN_MAINTENANCE_IF_MAIN );
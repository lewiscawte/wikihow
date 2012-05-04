<?php
/**
 * List all articles within a top level category (which includes its sub-
 * categories).
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts/ and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class ListAllCategoryArticles extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'List all articles within a top level category (which includes its sub-categories).';
		$this->addArg( 'category', 'Category name' );
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );

		if ( !$this->getArg( 0 ) ) {
			$this->error( 'You must supply the category name as an argument to this script!', true );
		}

		$topLevel = $this->getArg( 0 );;
		$file = $topLevel . '.csv';

		// get the category and all sub-categories
		$cats = WikiPhoto::getAllSubcats( $dbr, $topLevel );
		$cats[] = $topLevel;
		sort( $cats );
		$cats = array_unique( $cats );

		// get all pages
		$pages = array();
		foreach ( $cats as $cat ) {
			$results = WikiPhoto::getPages( $dbr, $cat );
			// make results unique based on page_id
			foreach ( $results as $result ) {
				$this->output( WikiPhoto::BASE_URL . $result['key'] . "\n" );
			}
		}
	}
}

$maintClass = 'ListAllCategoryArticles';
require_once( RUN_MAINTENANCE_IF_MAIN );
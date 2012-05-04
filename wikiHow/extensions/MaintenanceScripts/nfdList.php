<?php
/**
 * Creates a list of articles that have been deleted via the NFD tool.
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

class NFDList extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Creates a list of articles that have been deleted via the NFD tool.';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'logging',
			'log_title',
			array( 'log_type' => 'nfd', 'log_action' => 'delete' ),
			__METHOD__
		);

		$articles = array();
		foreach ( $res as $row ) {
			$articles[] = $row->log_title;
		}

		foreach( $articles as $article ) {
			$title = Title::newFromText( $article );
			if( !$title->exists() ) {
				$this->output( $title->getFullURL() . "\n" );
			}
		}
	}
}

$maintClass = 'NFDList';
require_once( RUN_MAINTENANCE_IF_MAIN );
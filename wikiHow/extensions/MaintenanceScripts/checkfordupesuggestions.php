<?php
/**
 * This script checks if there is a page corresponding to a topic suggestion
 * and updates suggested_titles table accordingly.
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

class CheckForDupeSuggestions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'See if there is a page corresponding to a topic suggestion and update suggested_titles table accordingly';
	}

	public function execute() {
		# this basically does a case insensitive comparison of suggested
		# titles to existing pages and sets any suggested titles to used
		# if they match a page that exists
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			array( 'page_title' ),
			array( 'page_namespace' => 0, 'page_is_redirect' => 0 ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$titles[strtolower( $row->page_title )] = 1;
		}

		$res = $dbr->select(
			'suggested_titles',
			array( 'st_title', 'st_id', 'st_used' ),
			array( 'st_used' => 0 ),
			__METHOD__
		);
		$ids = array();
		foreach ( $res as $row ) {
			if ( isset( $titles[strtolower( $row->st_title )] ) ) {
				$ids[] = $row->st_id;
				$this->output( "{$row->st_title} has already been taken\n" );
			}
		}

		if ( sizeof( $ids ) > 0 ) {
			$dbw = wfGetDB( DB_MASTER );
			$this->output( 'Updating suggested_titles database table...' );
			$dbw->update(
				'suggested_titles',
				array( 'st_used' => 1 ),
				array( 'st_id' => $ids ),
				__METHOD__
			);
		} else {
			$this->output( "Nothing to update\n" );
		}
	}
}

$maintClass = 'CheckForDupeSuggestions';
require_once( RUN_MAINTENANCE_IF_MAIN );
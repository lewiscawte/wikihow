<?php
/**
 * A script to output (to stdout) all NS_MAIN, non-redirect articles on the
 * site, in full URL form.
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

class DisplayAllURLs extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Outputs all NS_MAIN, non-redirect articles on the site, in full URL form to stdout.';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			'page_title',
			array(
				'page_namespace' => NS_MAIN,
				'page_is_redirect' => 0
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromDBkey( $row->page_title );
			if ( !$title ) {
				continue;
			}
			$this->output( $title->getFullURL() . "\n" );
		}
	}
}

$maintClass = 'DisplayAllURLs';
require_once( RUN_MAINTENANCE_IF_MAIN );
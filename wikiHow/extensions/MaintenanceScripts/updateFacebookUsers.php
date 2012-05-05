<?php
/**
 * Removes duplicate facebook_connect table entries.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class UpdateFacebookUsers extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Removes duplicate facebook_connect table entries';
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'facebook_connect',
			array( 'COUNT(fb_user) AS cnt', 'fb_user', 'wh_user' ),
			array(),
			__METHOD__,
			array( 'GROUP BY' => 'fb_user', 'ORDER BY' => 'cnt DESC' )
		);
		foreach ( $res as $row ) {
			if( $row->cnt == 1 ) {
				break;
			}
			if ( $row->wh_user != 0 ) {
				$dbw->delete(
					'facebook_connect',
					array( 'fb_user' => $row->fb_user ),
					__METHOD__
				);
				$dbw->insert(
					'facebook_connect',
					array( 'fb_user' => $row->fb_user, 'wh_user' => $row->wh_user ),
					__METHOD__
				);
			}
		}

	}
}

$maintClass = 'UpdateFacebookUsers';
require_once( RUN_MAINTENANCE_IF_MAIN );
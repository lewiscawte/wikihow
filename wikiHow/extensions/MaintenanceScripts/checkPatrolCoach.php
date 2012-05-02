<?php
/**
 * Checks which users have the Patrol Coach (RCTest extension) enabled
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

class CheckPatrolCoach extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Checks which users have the Patrol Coach (RCTest extension) enabled';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'rctest_users',
			array( 'ru_user_id', 'ru_base_patrol_count' ),
			array( 'ru_next_test_patrol_count' => 2, 'ru_base_patrol_count > 0' ),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$u = User::newFromId( $row->ru_user_id );
			$u->load();
			$enabled = RCTest::isEnabled( $u->getId() ) ? 'on' : 'off';
			$base = $row->ru_base_patrol_count;
			$wgUser = $u;
			$total = $dbr->selectField(
				'logging',
				'COUNT(*)',
				RCPatrolStandingsIndividual::getOpts(),
				__METHOD__
			);
			$adjusted = $total - $base;
			if ( $adjusted > 4 ) {
				$this->output( 'User: ' . $u->getName() . ", preference: $enabled, adjusted: $adjusted\n" );
			}
		}
	}
}

$maintClass = 'CheckPatrolCoach';
require_once( RUN_MAINTENANCE_IF_MAIN );
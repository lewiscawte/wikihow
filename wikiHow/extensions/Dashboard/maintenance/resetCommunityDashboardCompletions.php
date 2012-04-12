<?php
/**
 * Reset the Community Dashboard daily task completion goal info.
 * Run at night as part of cron.
 *
 * Usage: php resetCommunityDashboardCompletions.php
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/Dashboard/maintenance and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../../maintenance' );

require_once( 'Maintenance.php' );

class ResetCommunityDashboardCompletions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Reset the Community Dashboard daily task completion goal info.';
	}

	public function execute() {
		$dashboardData = new DashboardData();
		$dashboardData->resetDailyCompletionAllUsers();
	}
}

$maintClass = 'ResetCommunityDashboardCompletions';
require_once( RUN_MAINTENANCE_IF_MAIN );
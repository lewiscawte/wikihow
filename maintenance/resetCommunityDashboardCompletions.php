<?php
/**
 * Reset the Community Dashboard daily task completion goal info.  Run at 
 * night as part of cron.
 *
 * Usage: php resetCommunityDashboardCompletions.php
 */

require_once( 'commandLine.inc' );

$dashboardData = new DashboardData();
$dashboardData->resetDailyCompletionAllUsers();


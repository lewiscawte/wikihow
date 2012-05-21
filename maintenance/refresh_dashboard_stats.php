<?
//
// Refresh the stats in the community dashboard page in memcache every
// REFRESH_SECONDS seconds
//

// use the spare DB for community dashboard stats since the process is expensive and
// not mission critical
define('WH_USE_BACKUP_DB', true);
require_once("commandLine.inc");

global $IP;
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.body.php");

global $wgDebugLogFile;
$wgDebugLogFile = '';

class RefreshDashboardStats {

	const BASE_DIR = '/usr/local/wikihow/dashboard/';
	const REFRESH_SECONDS = 5.0;
	const STOP_AFTER_ERRORS = 60; // stop

	const TOKEN_FILE = 'refresh-stats-token.txt';
	const LOG_FILE = 'log.txt';

	private static function getToken() {

		$token = @file_get_contents(self::BASE_DIR . self::TOKEN_FILE);
		$token = (int)trim($token);

		return $token;
	}

	private static function log($str) {
		$date = date('m/d/Y H:i:s');
		file_put_contents(self::BASE_DIR . self::LOG_FILE, $date . " " . $str . "\n", FILE_APPEND);
	}

	public static function dataCompileLoop($opts) {
		$origToken = self::getToken();

		$numErrors = 0;
		$stopMsg = '';

		$data = new DashboardData();

		// The dashboard is very susceptible to going down when we're doing
		// maintenance on our spare server. Using this flag is a way to hold 
		// the stats steady by reading them once from the master DB and not again
		// until the daemon is restarted.
		$fakeStats = isset($opts['f']) || isset($opts['fake-stats']);
		if ($fakeStats) {
			$data->fetchOnFirstCallOnly();
		}

		$staticData = $data->loadStaticGlobalOpts();
		$baselines = (array)json_decode($staticData['cdo_baselines_json']);
		DashboardWidget::setBaselines($baselines);

		// Run the data compilation repeatedly, until token changes
		while (1) {
			$start = microtime(true);

			$success = $data->compileStatsData();

			$end = microtime(true);
			$delta = $end - $start;
			$logMsg = sprintf('data refresh took %.3fs', $delta);

			if ($success) {
				$numErrors = 0;
			} else {
				$logMsg = sprintf('error was detected in data refresh (%.3fs)', $delta);
				$numErrors++;
				if ($numErrors >= self::STOP_AFTER_ERRORS) {
					$stopMsg = sprintf('there were %d errors in a row.  stopping daemon.', self::STOP_AFTER_ERRORS);
				}
			}

			self::log($logMsg);
			if (!empty($stopMsg)) break;

			$until_refresh_seconds = self::REFRESH_SECONDS - $delta;
			if ($until_refresh_seconds >= 0.0) {
				$secs = (int)ceil($until_refresh_seconds);
				sleep($secs);
			}

			$token = self::getToken();
			if ($token != $origToken) {
				$stopMsg = 'stop daemon requested through token change.';
				break;
			}
		}

		if ($stopMsg) {
			self::log($stopMsg);
		}
	}

}

$opts = getopt('f', array('fake-stats'));
RefreshDashboardStats::dataCompileLoop($opts);


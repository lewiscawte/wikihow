<?

require_once("commandLine.inc");

global $IP;
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.body.php");

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

	public static function dataCompileLoop() {
		$origToken = self::getToken();

		$numErrors = 0;
		$stopMsg = '';

		$data = new DashboardData();

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

RefreshDashboardStats::dataCompileLoop();


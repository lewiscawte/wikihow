<?

require_once("commandLine.inc");

global $IP;
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.body.php");

class RefreshDashboardStats {

	const BASE_DIR = '/usr/local/wikihow/dashboard/';
	const REFRESH_SECONDS = 5.0;

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

		$data = new DashboardData();

		$staticData = $data->loadStaticGlobalOpts();
		$baselines = (array)json_decode($staticData['cdo_baselines_json']);
		DashboardWidget::setBaselines($baselines);

		// Run the data compilation repeatedly, until token changes
		while (1) {
			$start = microtime(true);
			
			$data->compileStatsData();

			$end = microtime(true);
			$delta = $end - $start;
			self::log( sprintf("data refresh took %.3fs", $delta) );

			$until_five_seconds = self::REFRESH_SECONDS - $delta;
			if ($until_five_seconds >= 0.0) {
				$secs = (int)ceil($until_five_seconds);
				sleep($secs);
			}

			$token = self::getToken();
			if ($token != $origToken) break;
		}
	}

}

RefreshDashboardStats::dataCompileLoop();


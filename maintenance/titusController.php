<?
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$maintenance = new TitusMaintenance();
$maintenance->nightly();

// To repair - run this call instead of nightly. This will recalc daily edits for the number of days lookback
// that you specify.  NOTE: historical data for the lookback period will not be repaired, just the current day
//$lookBack = 1;
//$maintenance->repairTitus($lookBack);

class TitusMaintenance {

	/*
	* Run the nightly maintenance for the titus and titus_historical tables
	*/
	public function nightly() {
		$this->updateHistorical();
		$this->incrementTitusDatestamp();
		$this->updateTitus();
	}

	private function updateTitus() {
		$titus = new TitusDB(true);
		$dailyEditStats = TitusConfig::getDailyEditStats();
		$titus->calcLatestEdits($dailyEditStats);

		$nightlyStats = TitusConfig::getNightlyStats();
		$titus->calcStatsForAllPages($nightlyStats);
	}

	public function repairTitus($lookBack = 1) {
		$titus = new TitusDB(true);
		$dailyEditStats = TitusConfig::getDailyEditStats();
		$titus->calcLatestEdits($dailyEditStats, $lookBack);

		$nightlyStats = TitusConfig::getNightlyStats();
		$titus->calcStatsForAllPages($nightlyStats);
	}

	/*
	* Dumps the current state of the titus table into titus_historical.  At the time of the dump, this should be a full days
	* worth of titus page rows. The titus_historical table should maintain 30-60 days worth of titus table dumps
	*/
	private function updateHistorical() {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "INSERT INTO titus_historical SELECT * FROM titus";
		$dbw->query($sql);
	}

	private function incrementTitusDatestamp() {
		$dbw = wfGetDB(DB_MASTER);
		$today = wfTimestamp(TS_MW, strtotime(date('Ymd', time())));
		$sql = "UPDATE titus set ti_datestamp = '$today'";
		$dbw->query($sql);
	}
}

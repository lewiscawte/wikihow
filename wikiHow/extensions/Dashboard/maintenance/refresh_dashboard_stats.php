<?php
/**
 * Refresh the stats in the community dashboard page in memcache every
 * REFRESH_SECONDS seconds
 *
 * @file
 * @ingroup Maintenance
 * @version 2012-05-14
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/Dashboard/maintenance and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../../maintenance' );

require_once( 'Maintenance.php' );

class RefreshDashboardStats extends Maintenance {
	const BASE_DIR = '/usr/local/wikihow/dashboard/';
	const REFRESH_SECONDS = 5.0;
	const STOP_AFTER_ERRORS = 60; // stop

	const TOKEN_FILE = 'refresh-stats-token.txt';
	const LOG_FILE = 'log.txt';

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Refresh Dashboard statistics';
		$this->addOption(
			'fake-stats',
			'Hold stats steady by reading them once from the master DB and not again until the daemon is restarted',
			false, false, 'f'
		);
	}

	private static function getToken() {
		wfSuppressWarnings();
		$token = file_get_contents( self::BASE_DIR . self::TOKEN_FILE );
		wfRestoreWarnings();
		$token = (int)trim( $token );

		return $token;
	}

	private static function log( $str ) {
		$date = date( 'm/d/Y H:i:s' );
		file_put_contents(
			self::BASE_DIR . self::LOG_FILE, $date . ' ' . $str . "\n",
			FILE_APPEND
		);
	}

	public static function dataCompileLoop() {
		$origToken = self::getToken();

		$numErrors = 0;
		$stopMsg = '';

		$data = new DashboardData();

		// The dashboard is very susceptible to going down when we're doing
		// maintenance on our spare server. Using this flag is a way to hold
		// the stats steady by reading them once from the master DB and not
		// again until the daemon is restarted.
		$fakeStats = $this->getOption( 'fake-stats' );
		if ( $fakeStats ) {
			$data->fetchOnFirstCallOnly();
		}

		$staticData = $data->loadStaticGlobalOpts();
		$baselines = (array)json_decode( $staticData['cdo_baselines_json'] );
		DashboardWidget::setBaselines( $baselines );

		// Run the data compilation repeatedly, until token changes
		while ( 1 ) {
			$start = microtime( true );

			$success = $data->compileStatsData();

			$end = microtime( true );
			$delta = $end - $start;
			$logMsg = sprintf( 'data refresh took %.3fs', $delta );

			if ( $success ) {
				$numErrors = 0;
			} else {
				$logMsg = sprintf( 'error was detected in data refresh (%.3fs)', $delta );
				$numErrors++;
				if ( $numErrors >= self::STOP_AFTER_ERRORS ) {
					$stopMsg = sprintf(
						'there were %d errors in a row. stopping daemon.',
						self::STOP_AFTER_ERRORS
					);
				}
			}

			self::log( $logMsg );
			if ( !empty( $stopMsg ) ) {
				break;
			}

			$until_refresh_seconds = self::REFRESH_SECONDS - $delta;
			if ( $until_refresh_seconds >= 0.0 ) {
				$secs = (int)ceil( $until_refresh_seconds );
				sleep( $secs );
			}

			$token = self::getToken();
			if ( $token != $origToken ) {
				$stopMsg = 'stop daemon requested through token change.';
				break;
			}
		}

		if ( $stopMsg ) {
			self::log( $stopMsg );
		}
	}

	public function execute() {
		self::dataCompileLoop();
	}
}

$maintClass = 'RefreshDashboardStats';
require_once( RUN_MAINTENANCE_IF_MAIN );
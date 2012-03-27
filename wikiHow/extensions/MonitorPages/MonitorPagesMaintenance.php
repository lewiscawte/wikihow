<?php
/**
 * @file
 * @ingroup Maintenance
 * @version 20110418
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MonitorPages and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );
require_once( dirname( __FILE__ ) . '/Maintenance.php' );

class MonitorPagesMaintenace extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Check the Google ranking of monitored pages and update the data in the DB accordingly.';
		$this->addOption( 'servername',
			'Name of the server, without the protocol and the trailing slash, such as "wikihow.com"',
			true /* required */, true /* withArg */, 'sn' );
	}

	/**
	 * Sleep randomly for a few seconds.
	 */
	function throttle() {
		$x = rand( 0, 15 );
		if ( $x == 10 ) {
			$s = rand( 1, 30 );
			$this->output( "sleeping for $s seconds\n" );
			sleep( $s );
		}
	}

	/**
	 * @return Array: array of Title objects
	 */
	function getTitles() {
		$dbr = wfGetDB( DB_SLAVE );
		$titles = array();
		$res = $dbr->select(
			array( 'google_monitor', 'page' ),
			array( 'page_namespace', 'page_title' ),
			array( 'page_id = gm_page', 'gm_active' => 1 ),
			__METHOD__
		);
		foreach( $res as $row ) {
			$titles[] = Title::makeTitle(
				$row->page_namespace,
				$row->page_title
			);
		}
		return $titles;
	}

	/**
	 * Check the Google ranking of our server for a given search keyword and
	 * update our tracking data in google_monitor_results accordingly.
	 *
	 * @param $query String: page name whose Google ranking we're checking
	 * @param $pageId Integer: article ID of the page
	 */
	function checkGoogle( $query, $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$url = 'http://www.google.com/search?q=' . urlencode( $query ) .
			'&num=100';
		$contents = file_get_contents( $url );
		$matches = array();
		$preg = '/href=\"http:\/\/[^\"]*\" class=l */ ';
		preg_match_all( $preg, $contents, $matches );

		$this->output( "checking $query\n" );
		$count = 0;
		$results = array();
		$found = false;
		foreach ( $matches[0] as $url ) {
			$url = substr( $url, 6, strlen( $url ) - 7 );
			// check for cache article
			if (
				strpos( $url, '/search?q=cache' ) !== false ||
				strpos( $url, 'google.com/' ) !== false
			)
			{
				continue;
			}
			$count++;
			$domain = str_replace( 'http://', '', $url );
			$domain = substr( $domain, 0, strpos( $domain, '/' ) );
			if ( strpos( $domain, $this->getOption( 'servername' ) ) !== false ) {
				$dbw->insert(
					'google_monitor_results',
					array(
						'gmr_page' => $pageId,
						'gmr_position' => $count
					),
					__METHOD__
				);
				$found = true;
				break;
			}
		}

		// If nothing was found, insert an initial record to the table
		if ( !$found ) {
			$dbw->insert(
				'google_monitor_results',
				array(
					'gmr_page' => $pageId,
					'gmr_position' => 0
				),
				__METHOD__
			);
		}
	}

	public function execute() {
		// load queries from the database
		$titles = $this->getTitles();
		if ( empty( $titles ) ) {
			$this->error(
				"No pages are being monitored, so there's nothing to do here",
				true /* die? */
			);
		}

		$serverName = $this->getOption( 'servername' );
		if ( !$serverName ) {
			$this->error(
				'The server name is mandatory, otherwise we have no idea what URL we should be looking for in Google results!',
				true /* die? */
			);
		}

		foreach( $titles as $title ) {
			$this->checkGoogle( $title->getText(), $title->getArticleID() );
			$this->throttle();
		}
	}
}

$maintClass = 'MonitorPagesMaintenance';
require_once( RUN_MAINTENANCE_IF_MAIN );
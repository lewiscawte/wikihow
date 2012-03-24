<?php
/**
 * Maintenance script to update the data in the google_indexed DB table.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/CheckGoogle/ and we don't need to move this file to $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class CheckGoogleIndex extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Updates the data in the google_indexed database table.';
	}

	function getResults( $url ) {
		$ch = curl_init();
		$useragent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13';
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_USERAGENT, $useragent );
		$contents = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			$wgLastCurlError = curl_error( $ch );
			return null;
		}
		curl_close( $ch );
		return $contents;
	}

	function isIndexed( $t ) {
		global $wgServer;

		// This is like $wgServer, but without the protocol...I couldn't find
		// a way to get the server URL _without_ the protocol.
		// WebRequest::detectServer(), which is used to build the value of
		// $wgServer in DefaultSettings.php, always returns the protocol.
		$newServer = preg_replace( '~^(http|https)://~', '', $wgServer );

		$query = $t->getText() . ' site:' . $newServer;
		$url = 'http://www.google.com/search?q=' . urlencode( $query ) . '&num=100';
		$results = $this->getResults( $url );
		if ( $results == null ) {
			return null;
		}
		$doc = new DOMDocument( '1.0', 'utf-8' );
		$doc->formatOutput = true;
		$doc->strictErrorChecking = false;
		$doc->recover = true;
		wfSuppressWarnings();
		$doc->loadHTML( $results );
		wfRestoreWarnings();
		$xpath = new DOMXPath( $doc );
		$nodes = $xpath->query( '//a[contains(concat(" ", normalize-space(@class), " "), " l")]' );
		$index = 1;
		$turl = urldecode( $t->getFullURL() );
		foreach ( $nodes as $node ) {
			$href = $node->getAttribute( 'href' );
			if ( $href == $turl ) {
				$found[] = $title;
				return $index;
			}
			$index++;
		}
		return 0;
	}

	public function execute() {
		global $wgLastCurlError;

		$dbw = wfGetDB( DB_MASTER );

		$dbw->query(
			'INSERT IGNORE INTO google_indexed (gi_page, gi_page_created) ' .
			"SELECT fe_page, fe_timestamp FROM firstedit WHERE fe_user_text='WRM'",
			__METHOD__
		);

		$res = $dbw->select(
			array( 'google_indexed', 'page' ),
			array( 'page_title', 'page_id' ),
			array( 'page_id = gi_page' ),
			__METHOD__,
			array( 'ORDER BY' => 'gi_times_checked, rand()', 'LIMIT' => 500 )
		);

		$titles = array();
		foreach ( $res as $row ) {
			$titles[] = Title::newFromDBKey( $row->page_title );
		}

		foreach ( $titles as $t ) {
			if ( !$t ) {
				continue;
			}
			$ts = wfTimestampNow();
			$ret = $this->isIndexed( $t );

			$dbw = wfGetDB( DB_MASTER );
			$opts = array(
				'gl_page' => $t->getArticleID(),
				'gl_pos' => ( $ret == null ? 0 : 1 ),
				'gl_checked' => $ts
			);
			if ( $ret == null ) {
				$opts['gl_err_str'] = $wgLastCurlError;
				$opts['gl_err'] = 1;
			}

			$dbw->insert( 'google_indexed_log', $opts, __METHOD__ );
			if ( $ret ) {
				$indexed = $ret > 0 ? 1 : 0;
				$dbw->update(
					'google_indexed',
					array(
						'gi_lastcheck' => $ts,
						'gi_indexed' => $indexed,
						'gi_times_checked = gi_times_checked + 1'
					),
					array( 'gi_page' => $t->getArticleID() ),
					__METHOD__
				);
			}

			// throttle
			$x = rand( 1, 3 );
			sleep( $x );
		}

		$this->output( "\nDONE\n" );
	}
}

$maintClass = 'CheckGoogleIndex';
require_once( DO_MAINTENANCE );
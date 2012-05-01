<?php
/**
 * Maintenance script to
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

class NewCheckGoogleIndex extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Updates the data in the google_indexed database table.';
		$this->addOption( 'limit', 'LIMIT for the SQL query, defaults to 1000', false /* required? */, true /* withArg */, 'l' );
	}

	function formatDate( $mysql_timestamp ) {
		preg_match(
			'/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',
			$mysql_timestamp,
			$pieces
		);
		$unix_timestamp = mktime(
			$pieces[4],
			$pieces[5],
			$pieces[6],
			$pieces[2],
			$pieces[3],
			$pieces[1]
		);
		return $unix_timestamp;
	}

	function throttle() {
		$x = rand( 0, 15 );
		if ( $x == 10 ) {
			$s = rand( 1, 30 );
			$this->output( "sleeping for $s seconds\n" );
			sleep( $s );
		}
	}

	function checkGoogle( $query, $a_url ) {
		global $gTotalAPIRequests;

		$a_url = urldecode( $a_url );
		$start = 0;
		$position = 0;
		$this->output( "checking $query\n" );
		$found = false;
		while ( $start < 90 && !$found ) {
			//$this->output( "searching at $start\n" );
			$results = SearchEngineAPI::serp( $query, $start ); // @todo FIXME
			$gTotalAPIRequests++;
			$i = $start + 1;
			if ( $results == null ) {
				$this->output( "Error: null API results or $query ($gTotalAPIRequests)\n" );
				continue;
			}
			foreach ( $results[0] as $r ) {
				if ( $a_url == $r['URL'] ) {
					//$this->output( "found position at $i\n" );
					$position = $i;
					$found = true;
					break;
				}
				$i++;
			}

			$start += 10;
		}

		return $position;
	}

	public function execute() {
		global $gTotalAPIRequests;

		$gTotalAPIRequests = 0;

		$dbr = wfGetDB( DB_SLAVE );
		$titles = array();

		// Check pages at most once a week
		$excluded = $dbr->select(
			'google_indexed',
			'gi_page',
			array( 'DATEDIFF(NOW(), gi_timestamp) < 7' ),
			__METHOD__
		);
		foreach ( $excluded as $row ) {
			$excludedArray[] = $row->gi_page;
		}

		$limit = $this->getOption( 'limit', 1000 );
		$res = $dbr->select(
			'page',
			array( 'page_id' ),
			array(
				'page_is_redirect' => 0,
				'page_namespace' => NS_MAIN,
				'page_id NOT IN (' . $dbr->makeList( $excludedArray ) . ')'
			),
			__METHOD__,
			array( 'ORDER BY' => 'RAND()', 'LIMIT' => $limit )
		);

		foreach ( $res as $row ) {
			$titles[] = Title::newFromID( $row->page_id );
		}

		$dbw = wfGetDB( DB_SLAVE );

		foreach ( $titles as $title ) {
			if ( $title == null ) {
				$this->output( 'error title is null ' . print_r( $title, true ) );
				continue;
			}

			$url = $title->getFullURL();
			// If our title is 'Talk:Foo', getFullURL() returns the whole URL
			// for the page, whereas getPartialURL() returns only the 'Foo'
			// part
			//$url = 'http://www.wikihow.com/' . $title->getPartialURL();

			// Get age
			$findAge = true;
			$age = 0;
			if ( $findAge ) {
				$min = $dbr->selectField(
					'revision',
					'MIN(rev_timestamp)',
					array( 'rev_page' => $title->getArticleID() ),
					__METHOD__
				);
				$d = $this->formatDate( $min );
				$diff = time() - $d;
				$age = ceil( $diff / 60 / 60 / 24 );
				//$this->output( "$url " . $title->getArticleID() . " is $age days old..\n" );
				$age = " age $age days";
			}

			$position = $this->checkGoogle(
				wfMsg( 'howto', $title->getFullText() ),
				$title->getFullURL()
				//'http://www.wikihow.com/' . $title->getPrefixedURL()
			);

			$dbw->insert(
				'google_indexed',
				array(
					'gi_page' => $title->getArticleID(),
					'gi_is_indexed' => ( $position > 0 ? '1' : '0' ),
					'gi_position' => $position
				),
				__METHOD__
			);

			if ( $position > 0 ) {
				$this->output( "indexed: $url $age position $position \n" );
			} else {
				$this->output( "not indexed: $url $age\n" );
			}
			//$this->throttle();
		}

		$this->output( "total API requests $gTotalAPIRequests\n" );
	}
}

$maintClass = 'NewCheckGoogleIndex';
require_once( DO_MAINTENANCE );
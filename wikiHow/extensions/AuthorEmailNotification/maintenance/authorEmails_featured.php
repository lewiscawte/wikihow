<?php
/**
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/AuthorEmailNotification/maintenance and we don't need to move
 * this file to $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../../maintenance' );

require_once( 'Maintenance.php' );

class AuthorEmailsFeatured extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sends e-mails to the authors of new featured articles';
	}

	public function execute() {
		global $wgServer;

		$t1 = time();
		$this->output(
			'Starting AuthorEmailNotifications - Featured Processing: ' .
			date( 'm/d/Y H:i:s', time() ) . "\n"
		);

		$this->output( "Processing Featured Articles Notification\n" );

		if ( !class_exists( 'FeaturedArticles' ) ) {
			require_once( '../../FeaturedArticles/FeaturedArticles.php' );
		}

		$days = 1;
		date_default_timezone_set( 'UTC' );
		$feeds = FeaturedArticles::getFeaturedArticles( $days );

		$now = time();
		$tomorrow = strtotime( 'tomorrow' );
		$today = strtotime( 'today' );

		$this->output(
			'Tomorrow: ' . date( 'm/d/Y H:i:s', $tomorrow ) .
			"[$tomorrow] Today: " . date( 'm/d/Y H:i:s', $today ) .
			"[$today] NOW: " . date( 'm/d/Y H:i:s', $now ) . " \n"
		);

		foreach ( $feeds as $f ) {
			$url = $f[0];
			$d = $f[1];
			$this->output(
				"Processing URL: $url with epoch " .
				date( 'm/d/Y H:i:s', $d ) . "[$d]\n"
			);

			if ( ( $d > $tomorrow ) || ( $d < $today ) ) {
				continue;
			}

			$url = str_replace( 'http://www.wikihow.com/', '', $url );
			$url = str_replace( $wgServer . '/', '', $url );
			$title = Title::newFromURL( urldecode( $url ) );
			$title_text = $title->getText();
			if ( isset( $f[2] ) && $f[2] != null && trim( $f[2] ) != '' ) {
				$title_text = $f[2];
			} else {
				$title_text = wfMsg( 'howto', $title_text );
			}

			if ( isset( $title ) ) {
				$this->output(
					"Featured: $title_text [AID] " . $title->getArticleID() .
					" [URL] $url\n"
				);
				AuthorEmailNotification::notifyFeatured( $title );
			} else {
				$this->output(
					"Warning Featured: could not retrieve article ID for $url\n"
				);
			}
		}

		$t2 = time() - $t1;
		$this->output( 'Took ' . number_format( $t2, 0, '.', ',' ) . " seconds...\n" );
		$this->output(
			'Completed AuthorEmailNotifications - Featured Processing: ' .
			date( 'm/d/Y H:i:s', time() ) . "\n"
		);
	}
}

$maintClass = 'AuthorEmailsFeatured';
require_once( RUN_MAINTENANCE_IF_MAIN );
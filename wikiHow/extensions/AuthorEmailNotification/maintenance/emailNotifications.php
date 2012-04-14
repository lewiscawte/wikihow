<?php
/**
 * One maintenance script to rule, err, maintain them all!
 * I think this script replaces authorEmails_featured.php and
 * authorEmails_viewership.php, but I'm not totally sure.
 *
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

class EmailNotificationsMaintenance extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sends out e-mail to users about their articles becoming featured and/or in regards to viewership data';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$dbw = wfGetDB( DB_MASTER );

		// Process featured articles
		if ( !class_exists( 'FeaturedArticles' ) ) {
			require_once( '../../FeaturedArticles/FeaturedArticles.php' );
		}
		$fas = FeaturedArticles::getFeaturedArticles( 1 );
		foreach ( $fas as $fa ) {
			$url = urldecode( preg_replace( '@http://www.wikihow.com/@', '', $fa[0] ) );
			$t = Title::newFromURL( $url );
			if ( !$t ) {
				$this->output( "Can't make title" );
				print_r( $fa );
				continue;
			}
			$this->output( "sending notification for FA for {$t->getFullText()}\n" );
			AuthorEmailNotification::notifyFeatured( $t );
		}

		// Process viewership e-mails
		$ts = wfTimestamp( TS_MW, time() - 86400 );
		$res = $dbr->select(
			array( 'email_notifications', 'page' ),
			array(
				'page_namespace', 'page_title', 'page_counter',
				'en_viewership', 'en_user'
			),
			array( "en_viewership_email IS NULL OR en_viewership_email < '{$ts}'" ),
			__METHOD__,
			array(),
			array( 'page' => array( 'LEFT JOIN', 'en_page = page_id' ) )
		);

		$milestones = array( 10000, 5000, 1000, 500, 100 ); 
		foreach ( $res as $row ) {
			$send = false;
			if ( !$row->page_title ) {
				continue;
			}
			if ( $row->page_counter >= 10000 && $row->page_counter - $row->en_viewership >= 10000 ) {
				$milestone = floor( $row->page_counter / 10000 ) * 10000;
				$send = true;
			} else {
				foreach ( $milestones as $m ) {
					if ( $row->page_counter >= $m && $row->en_viewership < $m ) {
						$milestone = $m;
						$send = true;
						break;
					}
				}
			}

			if ( $send ) {
				$title = Title::makeTitle( $row->page_namespace, $row->page_title );
				$user = User::newFromID( $row->en_user );
				$user->load();
				AuthorEmailNotification::notifyViewership(
					$title,
					$user,
					$milestone,
					$milestone,
					null
				);
			} 
		}
	}
}

$maintClass = 'EmailNotificationsMaintenance';
require_once( RUN_MAINTENANCE_IF_MAIN );
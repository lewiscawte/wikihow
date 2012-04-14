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

class AuthorEmailsViewership extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sends e-mails to article authors about how many times their articles have been watched';
	}

	public function execute() {
		$t1 = time();
		$this->output(
			'Starting AuthorEmailNotifications - Viewership Processing: ' .
			date( 'm/d/Y H:i:s', time() ) . "\n"
		);

		$thresholds = array( 25, 100, 500, 1000, 5000 );
		$thresh2 = 10000;

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array( 'email_notifications' ),
			array( 'en_viewership_email', 'en_viewership', 'en_user', 'en_page' ),
			array( 'en_watch = 1' ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$sendflag = 0;
			$viewership = 0;
			$milestone = 0;

			$title = Title::newFromID( $row->en_page );
			$user = User::newFromID( $row->en_user );

			if ( isset( $title ) ) {
				$viewership = $dbr->selectField(
					'page',
					'page_counter',
					array( 'page_id' => $title->getArticleID() ),
					__METHOD__
				);

				$prev = $row->en_viewership;

				if ( $viewership > $thresh2 ) {
					$a = floor( $prev / $thresh2 );
					$b = floor( $viewership / $thresh2 );
					if ( $b > $a ) {
						$milestone = $b * $thresh2;
						$sendflag = 1;
					}
				} else {
					foreach ( $thresholds as $level ) {
						if ( ( $prev < $level ) && ( $level < $viewership ) ) {
							$milestone = $level;
							$sendflag = 1;
						}
					}
				}

				if ( $sendflag ) {
					$this->output(
						'Processing: [TITLE] ' . $title->getText() .
						'(' . $title->getArticleID() . ') [USER] ' .
						$user->getName() . ', [VIEWS]' . $row->en_viewership .
						' - ' . $viewership . " [MILESTONE] $milestone \n"
					);

					AuthorEmailNotification::notifyViewership(
						$title,
						$user,
						$milestone,
						$viewership,
						$row->en_viewership_email
					);
				} else {
					$this->output(
						'Skipping: [TITLE] ' . $title->getText() .
						'(' . $title->getArticleID() . ') [USER] ' .
						$user->getName() . ', [VIEWS]' . $row->en_viewership .
						' - ' . $viewership . " [MILESTONE] $milestone \n"
					);
				}

			} else {
				$this->output(
					'Article removed: [PAGE] ' . $row->en_page .
					' [USER] ' . $row->en_user . "\n"
				);
			}
		}

		$t2 = time() - $t1;
		$this->output( 'Took ' . number_format( $t2, 0, '.', ',' ) . " seconds...\n" );
		$this->output(
			'Completed AuthorEmailNotifications - Viewership Processing: ' .
			date( 'm/d/Y H:i:s', time() ) . "\n"
		);
	}
}

$maintClass = 'AuthorEmailsViewership';
require_once( RUN_MAINTENANCE_IF_MAIN );
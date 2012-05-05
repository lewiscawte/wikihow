<?php
/**
 * Script to remove empty ProfileBox-related user-specific pages.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class RemoveEmptyProfilePages extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Removes empty ProfileBox-related user-specific pages.';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'user',
			'user_id',
			array( 'user_registration >= 20110501000000' ),
			__METHOD__
		);

		$users = array();
		foreach ( $res as $result ) {
			$users[] = $result->user_id;
		}

		$articleTitles = array(
			'/profilebox-live',
			'/profilebox-aboutme',
			'/profilebox-occupation'
		);
		$totalDeleted = 0;
		foreach( $users as $user ) {
			$u = User::newFromId( $user );
			if( $u ) {
				$userPage = $u->getUserPage();
				foreach( $articleTitles as $title ) {
					$t = Title::newFromText( $userPage->getBaseText() . $title, NS_USER );
					$totalDeleted += $this->checkForEmpty( $t ) ? 1 : 0;
				}
			}
		}

		$this->output( 'Total number of pages deleted: ' . $totalDeleted . "\n" );
	}

	function checkForEmpty( $t ) {
		$deleteComment = 'Deleting unused profile box page';
		if( $t ) {
			$a = new Article( $t );
			if( $a->exists() ) {
				$content = $a->getContent();
				if( $content == '' ) {
					if( $a->doDeleteArticle( $deleteComment ) ) {
						$this->output( $deleteComment . " " . $t->getText() . "\n" );
						return true;
					}
				}
			}
		}
		return false;
	}
}

$maintClass = 'RemoveEmptyProfilePages';
require_once( RUN_MAINTENANCE_IF_MAIN );
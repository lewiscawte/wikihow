<?php
/**
 * Maintenance script that moves kudos messages from the user's User_kudos
 * page into an archive page.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts/ and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class ArchiveKudos extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Moves kudos messages from the user's User_kudos page into an archive page";
	}

	public function execute() {
		global $wgUser;

		$wgUser = User::newFromName( 'KudosArchiver' );

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'page', 'user' ),
			array( 'page_title', 'page_namespace', 'user_name' ),
			array(
				'page_namespace' => NS_USER_KUDOS,
				'page_len > 80000',
				'page_title = user_name',
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			try {
				$num_titles = 0;
				$ot = Title::makeTitle( $row->page_namespace, $row->page_title );
				$links = array();
				for ( $x = 1; ; $x++ ) {
					$t = Title::makeTitle( NS_USER_KUDOS, wfMsg( 'user_kudos_archive_url', $row->user_name, $x ) );
					if ( $t->getArticleID() == 0 ) {
						break;
					}
					$num_titles++;
					$links[] .= "[[{$t->getPrefixedText()}|$x]]";
				}
				$links[] .= "[[{$t->getPrefixedText()}|" . ( $num_titles + 1 ) . ']]';
				$nt = Title::makeTitle( NS_USER_KUDOS, wfMsg( 'user_kudos_archive_url', $row->user_name, $num_titles + 1 ) );
				$this->output( "Moving {$ot->getFullText()} to {$nt->getFullText()}\n" );
				$ot->moveNoAuth( $nt );

				$text = wfMsg( 'user_kudos_archive_title' ) . implode( ', ', $links );
				$a = new Article( &$ot );
				$a->doEdit( &$text, wfMsg( 'user_kudos_archive_summary' ) );
				$this->output( "Setting new text $text\n");
			} catch ( Exception $e ) {
			}
		}
	}
}

$maintClass = 'ArchiveKudos';
require_once( RUN_MAINTENANCE_IF_MAIN );
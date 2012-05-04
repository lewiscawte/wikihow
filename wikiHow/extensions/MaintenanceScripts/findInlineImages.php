<?php
/**
 * List articles that have hotlinked inline images (<img src=)
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

class FindInlineImages extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'List articles that have hotlinked inline images (<img src=)';
	}

	public function execute() {
		global $wgParser, $wgUser;

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			array( 'page_title', 'page_namespace' ),
			array( 'page_is_redirect' => 0 ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			try {
				$title = Title::makeTitle( $row->page_namespace, $row->page_title );
				$revision = Revision::newFromTitle( $title );
				$text = $revision->getText();
				$wgParser->mOptions = ParserOptions::newFromUser( $wgUser );
				$wgParser->mTitle = $title;
				$p = $wgParser->internalParse( $text );
				$text1 = $wgParser->replaceExternalLinks( $text );
				if ( preg_match( '/<img src=/', $text1 ) ) {
					$this->output( $title->getFullURL() . "\n" );
				}
			} catch ( Exception $e ) {
			}
		}
	}
}

$maintClass = 'FindInlineImages';
require_once( RUN_MAINTENANCE_IF_MAIN );
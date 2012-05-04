<?php
/**
 * List all articles on that site that have a short (or non-existent) intro
 * section. All wikitext is stripped, so images aren't included in this
 * output.
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

class ListArticlesShortIntro extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'List all articles on that site that have a short (or non-existent) intro section.';
	}

	public function execute() {
		global $wgParser;

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			array( 'page_title', 'page_id' ),
			array( 'page_is_redirect' => 0, 'page_namespace' => NS_MAIN ),
			__METHOD__
		);

		$fp = fopen( 'short-intros.csv', 'w' );
		if ( !$fp ) {
			$this->error( "Could not open file for write\n", true );
		}

		fputcsv( $fp, array( 'page_id', 'URL', 'has_template', 'intro_length', 'intro' ) );
		foreach ( $res as $row ) {
			$title = Title::newFromDBkey( $row->page_title );
			if ( !$title ) {
				$this->output( "Can't make title out of {$row->page_title}\n" );
				continue;
			}

			$rev = Revision::newFromTitle( $title );
			$wikitext = $rev->getText();
			$intro = $wgParser->getSection( $wikitext, 0 );
			$flat = Wikitext::flatten( $intro );
			$flat = trim( $flat );
			$len = mb_strlen( $flat );
			if ( $len < 50 ) {
				// check whether it has either the {{intro or {{introduction template
				$hasTemplate = strpos( strtolower( $intro ), '{{intro' ) !== false;
				$fields = array(
					$row->page_id,
					$title->getFullURL(),
					$hasTemplate ? 'y' : 'n',
					$len,
					$flat
				);
				fputcsv( $fp, $fields );
				if ( @++$i % 100 == 0 ) {
					$this->output( "article $i\n" );
				}
			}
		}

		fclose( $fp );
	}
}

$maintClass = 'ListArticlesShortIntro';
require_once( RUN_MAINTENANCE_IF_MAIN );
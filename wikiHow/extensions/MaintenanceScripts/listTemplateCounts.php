<?php
/**
 * List the articles that match a few different templates.
 * Note that we can't just do a SELECT COUNT(*) FROM templatelinks because
 * we're looking for 'sub'-templates of NFD.
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

class ListTemplateCounts extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'List the articles that match a few different templates.';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'page', 'templatelinks' ),
			array( 'page_id' ),
			array(
				'tl_from = page_id',
				'page_namespace' => NS_MAIN,
				'tl_title' => 'Nfd'
			),
			__METHOD__
		);
		$titles = array();
		while ( $row = $res->fetchObject() ) {
			$titles[] = Title::newFromID( $row->page_id );
		}

		$badTemplates = array( 'copyvio', 'copyviobot', 'copyedit', 'cleanup' );
		$counts = array_flip( $badTemplates );
		$counts = array_map( function( $i ) { return 0; }, $counts );

		foreach ( $titles as $title ) {
			$rev = Revision::newFromTitle( $title );
			$wikitext = $rev->getText();
			if ( preg_match( '@{{nfd\|([A-Za-z]+)@im', $wikitext, $m ) ) {
				$sub = $m[1];
				$this->output( "here:$sub\n" );
				if ( isset( $counts[$sub] ) ) {
					$counts[$sub]++;
				}
			}
		}

		print_r( $counts );
	}
}

$maintClass = 'ListTemplateCounts';
require_once( RUN_MAINTENANCE_IF_MAIN );
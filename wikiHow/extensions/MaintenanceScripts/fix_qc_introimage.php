<?php
/**
 * Updates QualityGuardian's qc table (somehow...I don't know exactly how).
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

class FixQCIntroImage extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Updates QualityGuardian's qc table.";
	}

	public function execute() {
		global $wgParser, $wgContLang;

		$fileNamespaceName = $wgContLang->getNsText( NS_FILE );

		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			array( 'qc', 'page' ),
			array( 'page_title', 'page_namespace', 'qc_id' ),
			array(
				'qc_page = page_id',
				'qc_key' => 'changedintroimage',
				'qc_patrolled' => 0
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$t = Title::makeTitle( $row->page_namespace, $row->page_title );
			if ( !$t ) {
				$this->output( "can't make a title out of {$row->page_title}\n" );
				// that's no good
				$this->mP( $row->qc_id );
			}
			$r = Revision::newFromTitle( $t );
			if ( !$r ) {
				$this->output( "{$t->getFullURL()} doesn't appear to have a revision\n" );
				// that's no good
				$this->mP( $row->qc_id );
				continue;
			}
			$text = $r->getText();
			$intro = $wgParser->getSection( $text, 0 );
			if ( !preg_match( "@\[\[$fileNamespaceName:@", $text ) ) {
				$this->output( "{$t->getFullURL()} doesn't appear to have an image\n" );
				$this->mP( $row->qc_id );
			}
		}
	}

	function mP( $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'qc',
			array( 'qc_patrolled' => 1 ),
			array( 'qc_id' => $id, 'qc_key' => 'changedintroimage' ),
			__METHOD__
		);
	}

}

$maintClass = 'FixQCIntroImage';
require_once( RUN_MAINTENANCE_IF_MAIN );
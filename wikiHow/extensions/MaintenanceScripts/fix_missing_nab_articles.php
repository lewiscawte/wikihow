<?php
/**
 * Adds missing articles to the NewArticleBoost tool.
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

class FixMissingNabArticles extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Adds missing articles to the NewArticleBoost tool.';
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			array( 'firstedit', 'page' ),
			array( 'fe_page', 'fe_timestamp', 'page_id', 'page_title' ),
			array(
				'page_namespace' => NS_MAIN,
				'page_is_redirect' => 0,
				"fe_timestamp > '20090101000000'"
			),
			__METHOD__,
			array(),
			array(
				'page' => array( 'LEFT JOIN', 'fe_page = page_id' )
			)
		);
		foreach ( $res as $row ) {
			$count = $dbw->selectField(
				'newarticlepatrol',
				'COUNT(*)',
				array( 'nap_page' => $row->page_id ),
				__METHOD__
			);
			if ( $count == 0 ) {
				$this->output( "{$row->page_id}\t{$row->page_title}\n" );
				$dbw->insert(
					'newarticlepatrol',
					array(
						'nap_page' => $row->page_id,
						'nap_timestamp' => $row->fe_timestamp,
						'nap_patrolled' => 0
					),
					__METHOD__
				);
			}
		}
	}
}

$maintClass = 'FixMissingNabArticles';
require_once( RUN_MAINTENANCE_IF_MAIN );
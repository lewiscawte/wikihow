<?php
/**
 * Script to check the database for pages which have had a lot more content in
 * the past than they currently do.
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

class CheckForVandals extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Finds recently vandalized pages';
	}

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'page', 'revision' ),
			array( 'page_id', 'page_title', 'page_len', 'MAX(rev_len) AS rev_len' ),
			array( 'page_namespace' => 0, 'page_is_redirect' => 0 ),
			__METHOD__,
			array( 'GROUP BY' => 'rev_page' ),
			array(
				'revision' => array( 'LEFT JOIN', 'page_id = rev_page' )
			)
		);
		$this->output( '<table width="80%" align="center">' );
		$count = 0;
		foreach ( $res as $row ) {
			if ( $row->rev_len == 0 ) {
				#echo "Warning! {$row->page_id} = 0 \n"; exit;
				continue; // it's an old article
			}
			if ( $row->page_len / $row->rev_len <= 0.7 ) {
				$t = Title::makeTitle( NS_MAIN, $row->page_title );
				if ( $t ) {
					$rev_id = $dbr->selectField(
						'revision',
						array( 'rev_id' ),
						array(
							'rev_page' => $row->page_id,
							'rev_len' => $row->rev_len
						),
						__METHOD__
					);
					$count++;
					$this->output( "<tr><td>{$count}.</td><td><a href=\"{$t->getFullURL()}\">{$t->getText()}</a></td>" .
						"<td><a href=\"{$t->getFullURL()}\">" . number_format( $row->page_len, 0, '', ',' ) . '</a></td>' .
						"<td><a href=\"{$t->getFullURL()}?oldid={$rev_id}\">" . number_format( $row->rev_len, 0, '', ',' ) . '</td>' .
						"<td><a href=\"{$t->getFullURL()}?action=history\">History</td>
					</tr>"
					);
				}
			}
		}
		$this->output( '</table>' );
	}
}

$maintClass = 'CheckForVandals';
require_once( RUN_MAINTENANCE_IF_MAIN );
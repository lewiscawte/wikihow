<?php
/**
 * Removes old entries from QualityGuardian-related database tables
 * (qc and qc_vote)
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

class ArchiveQC extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Cleans up QualityGuardian-related database tables (qc and qc_vote)';
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );

		// rcpatrol QC items enter into the QC queue faster than they can be
		// QC patrolled.
		// Automatically QC patrol QC rcpatrol items older than 2 weeks so they
		// can be archived below
		$timestamp = wfTimestamp( TS_MW, time() - 60 * 60 * 24 * 14 );

		$this->output( "==============================\n" );
		$this->output( "Mark patrolled qc and qc_vote items that are older than $timestamp \n" );
		// Clean up stale/old qc items.
		// This happens if the community can't keep up with the qc queue.
		// We clean these out to make sure QC queries perform well
		$dbw->update(
			'qc',
			array( 'qc_patrolled' => 1 ),
			array( "qc_timestamp < '$timestamp'", 'qc_patrolled' => 0 ),
			__METHOD__
		);

		$this->output( "Archive qc and qc_vote items that are marked patrolled\n" );
		// Grab all the qc items and associate qc votes, put them in the
		// respective archive tables and then delete items
		// Do it in batches of 1000 to not overload the database
		$count = 0;
		do {
			$res = $dbw->select(
				'qc',
				array( '*' ),
				array( 'qc_patrolled' => 1),
				__METHOD__,
				array( 'LIMIT' => 1000 )
			);
			$qcRows = array();
			$qcVoteRows = array();
			$qcIds = array();
			$moreRows = false;
			foreach ( $res as $row ) {
				$moreRows = true;
				$qcRows[] = get_object_vars( $row );
				$qcIds[] = $row->qc_id;
				$count++;
			}

			if ( sizeof( $qcIds ) ) {
				$res1 = $dbw->select(
					'qc_vote',
					array( '*' ),
					array( 'qcv_qcid' => $qcIds ),
					__METHOD__
				);
				foreach ( $res1 as $row ) {
					$qcVoteRows[] = get_object_vars( $row );
				}
			}

			if ( is_array( $qcRows ) && sizeof( $qcRows ) ) {
				$dbw->insert(
					'qc_archive',
					$qcRows,
					__METHOD__
				);
			}

			if ( is_array( $qcVoteRows ) && sizeof( $qcVoteRows ) ) {
				$dbw->insert(
					'qc_vote_archive',
					$qcVoteRows,
					__METHOD__
				);
			}

			if ( is_array( $qcIds ) && sizeof( $qcIds ) ) {
				$dbw->delete(
					'qc',
					array( 'qc_id' => $qcIds ),
					__METHOD__
				);
				$dbw->delete(
					'qc_vote',
					array( 'qcv_qcid' => $qcIds ),
					__METHOD__
				);
			}
		} while ( $moreRows );

		$this->output( 'FINISHED archiving: ' . $count . " qc items archived\n" );
	}
}

$maintClass = 'ArchiveQC';
require_once( RUN_MAINTENANCE_IF_MAIN );
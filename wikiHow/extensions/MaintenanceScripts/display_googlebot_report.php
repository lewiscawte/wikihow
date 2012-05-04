<?php
/**
 * Generates and e-mails a Googlebot report to the specified e-mail address.
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

class DisplayGooglebotReport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generates and e-mails a Googlebot report to the specified e-mail address.';
		$this->addOption( 'sender', "Sender's e-mail address", true, true, 's' );
		$this->addOption( 'recipient', "Recipient's e-mail address", true, true, 'r' );
	}

	public function execute() {
		$sender = $this->getOption( 'sender' );
		$recipient = $this->getOption( 'recipient' );
		if ( !$sender || !$recipient ) {
			$this->error(
				"You MUST supply both e-mail addresses, the sender's and the recipient's!",
				true
			);
		}

		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'googlebot',
			'*',
			array(),
			__METHOD__,
			array( 'ORDER BY' => 'gb_batch DESC', 'LIMIT' => 4 )
		);
		$rows = array();
		foreach ( $res as $row ) {
			$rows[] = $row;
		}

		$params = array(
			'Batch' => 'gb_batch',
			'Total' => 'gb_total',
			'404 errors' => 'gb_404',
			'301 redirects' => 'gb_301',
			'Main namespace' => 'gb_main',
			'::userloggedout requests' => 'gb_bad',
			'User namespace' => 'gb_user',
			'User talk namespace' => 'gb_usertalk',
			'Discussion namespace' => 'gb_discuss',
			'Special namespace' => 'gb_special',
			'# of unique main '  => 'gb_uniquemain',
		);

		$html = '<style>
	table td {
		font-style: Georgia;
	}
	</style>
	<div style="font-family:Georgia;">
	<h1>Googlebot Report</h1>
	<table width="100%">';

		$lastWeek = '';

		foreach ( $params as $label => $column ) {
			$html .= "<tr><td style='font-family: Georgia; font-weight: bold;'>$label</td>";
			foreach ( $rows as $row ) {
				$html .= '<td style="font-family: Georgia;">';
				switch( $column ) {
					case 'gb_total':
						$html .= number_format( $row->gb_total, 0, '.', ',' );
						break;
					case 'gb_batch':
						$week = substr( $row->gb_batch, 0, 4 ) . '-' . substr( $row->gb_batch, 4, 2 );
						$html .= $week;
						if ( !$lastWeek ) {
							$lastWeek = $week;
						}
						break;
					case 'gb_uniquemain':
						$html .= number_format( $row->gb_uniquemain, 0, '.', ',' );
						break;
					default:
						$html .= number_format( $row->$column, 0, '.', ',' ) .
							'(' . number_format( $row->$column / $row->gb_total * 100, 2 ) . '%)';
						break;
				}
				$html .= '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table></div>';

		if ( $lastWeek ) {
			$to = new MailAddress( $recipient );
			$from = new MailAddress( $sender );
			$subject = 'Googlebot report for week ' . $lastWeek;
			$contentType = 'text/html; charset=UTF-8';
			UserMailer::send( $to, $from, $subject, $html, null, $contentType );
		}
	}
}

$maintClass = 'DisplayGooglebotReport';
require_once( RUN_MAINTENANCE_IF_MAIN );
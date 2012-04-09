<?php

class StatsList extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'StatsList' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut;

		$startdate = '000000';
		$startdate31 = strtotime( '31 days ago' );
		$startdate7 = strtotime( '7 days ago' );
		$startdate24 = strtotime( '24 hours ago' );

		$starttimestamp31 = date( 'YmdG', $startdate31 ) . floor( date( 'i', $startdate31 ) / 10 ) . '00000';
		$starttimestamp7 = date( 'YmdG', $startdate7 ) . floor( date( 'i', $startdate7 ) / 10 ) . '00000';
		$starttimestamp24 = date( 'YmdG', $startdate24 ) . floor( date( 'i', $startdate24 ) / 10 ) . '00000';

		$wgOut->addHTML( '<table cellspacing="10">' );
		$wgOut->addHTML(
			'<tr><td>' . wfMessage( 'statslist-requests-answered-24h' )->text() . '</td><td>' .
			self::getNumRequestsAnswered( $starttimestamp24 ) .
			'</td></tr>'
		);
		$wgOut->addHTML(
			'<tr><td>' . wfMessage( 'statslist-requests-answered-7d' )->text() . '</td><td>' .
			self::getNumRequestsAnswered( $starttimestamp7 ) .
			"</td></tr>"
		);
		$wgOut->addHTML(
			'<tr><td>' . wfMessage( 'statslist-requests-answered-31d' )->text() . '</td><td>' .
			self::getNumRequestsAnswered( $starttimestamp31 ) .
			'</td></tr>'
		);
		$wgOut->addHTML( '</table>' );
	}

	function getNumRequestsAnswered( $startTimestamp ) {
		global $wgMemc;

		$cacheKey = wfMemcKey( 'StatsList_requests' . $startTimestamp );
		$result = $wgMemc->get( $cacheKey );
		if ( $result !== null ) {
			return $result;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'firstedit', 'page', 'suggested_titles' ),
			array( 'COUNT(page_title) AS count' ),
			array(
				"fe_timestamp >= '$startTimestamp'",
				'st_isrequest IS NOT NULL'
			),
			__METHOD__,
			array(),
			array(
				'page' => array( 'LEFT JOIN', 'fe_page = page_id' ),
				'suggested_titles' => array( 'LEFT JOIN', 'page_title = st_title' )
			)
		);

		foreach ( $res as $row ) {
			$wgMemc->set( $cacheKey, $row->count );
			return $row->count;
		}
	}

}

<?php

class Standings extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Standings' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$wgOut->disable();
		$result = array();

		if ( $target ) {
			$rc = new ReflectionClass( $target );
			$allowedParents = array( 'StandingsIndividual', 'StandingsGroup' );
			$parentClass = $rc->getParentClass();
			$parentClass = $parentClass->name;
			if ( in_array( $parentClass, $allowedParents ) ) {
				$c = new $target();
				$result['html'] = $c->getStandingsTable();
			}
		} else {
			$result['error'] = wfMessage( 'standings-no-target' )->text();
		}

		echo json_encode( $result );
	}
}
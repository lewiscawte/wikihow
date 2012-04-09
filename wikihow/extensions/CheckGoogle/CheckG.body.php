<?php

class CheckGoogle extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'CheckGoogle', 'checkgoogle' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgLang, $wgRequest, $wgOut, $wgUser, $wgServer;

		// Check for the correct permission
		if( !$wgUser->isAllowed( 'checkgoogle' ) ) {
			$this->displayRestrictionError();
			return false;
		}

		// Blocked through Special:Block? No access for you either!
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage( false );
			return false;
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$createdate = $wgRequest->getVal( 'createdate' );
		// This is like $wgServer, but without the protocol...I couldn't find
		// a way to get the server URL _without_ the protocol.
		// WebRequest::detectServer(), which is used to build the value of
		// $wgServer in DefaultSettings.php, always returns the protocol.
		$newServer = preg_replace( '~^(http|https)://~', '', $wgServer );

		// Get the averages
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow(
			'google_indexed',
			array( 'AVG(gi_indexed) AS A', 'COUNT(*) AS C' ),
			array( 'gi_times_checked > 0' ),
			__METHOD__
		);
		$wgOut->addHTML(
			wfMsg( 'checkgoogle-number-of-pages', $row->C ) . '<br />' .
			wfMsg( 'checkgoogle-average', $wgLang->formatNum( $row->A * 100 ) ) .
			'<br />'
		);

		$left = $dbr->selectField(
			'google_indexed',
			array( 'COUNT(*) AS C'),
			array( 'gi_times_checked' => 0 ),
			__METHOD__
		);
		$wgOut->addHTML( wfMsg(
			'checkgoogle-unchecked-pages', $wgLang->formatNum( $left ) ) .
			'<br /><br />'
		);

		// do we have a target?
		if ( $createdate && $target ) {
			// Due to the usage of SUBSTR function, we can't leave this to the
			// Database class (the select function below)...
			$safeCreateDate = $dbr->addQuotes( $createdate );
			$safeTarget = $dbr->addQuotes( $target );
			$res = $dbr->select(
				array( 'google_indexed_log', 'google_indexed', 'page' ),
				array(
					'page_title', 'gl_err', 'gl_page', 'gl_pos',
					'SUBSTR(gi_page_created, 1, 8) AS createdate'
				),
				array(
					"SUBSTR(gi_page_created, 1, 8) = {$safeCreateDate}",
					"SUBSTR(gl_checked, 1, 8) = {$safeTarget}"
				),
				__METHOD__,
				array(),
				array(
					'google_indexed' => array( 'LEFT JOIN', 'gi_page = gl_page' ),
					'page' => array( 'LEFT JOIN', 'page_id = gl_page' )
				)
			);
			$f = preg_replace( '@([0-9]{4})([0-9]{2})([0-9]{2})@', "$1-$2-$3", $target );
			$c = preg_replace( '@([0-9]{4})([0-9]{2})([0-9]{2})@', "$1-$2-$3", $createdate );
			$wgOut->addHTML(
				'<h2>' . wfMsg( 'checkgoogle-detailed-report', $f, $c ) . '</h2>
					<table width="80%" align="center">
						<tr>
							<td>' . wfMsg( 'checkgoogle-page' ) . '</td>
							<td>' . wfMsg( 'checkgoogle-indexed' ) . '</td>
							<td>' . wfMsg( 'checkgoogle-error' ) . '</td>
							<td>' . wfMsg( 'checkgoogle-check' ) . '</td>
						</tr>'
			);
			foreach ( $res as $row ) {
				$t = Title::newFromDBKey( $row->page_title );
			    $query = $t->getText() . ' site:' . $newServer;
   				$url = 'http://www.google.com/search?q=' . urlencode( $query ) . '&num=100';
				$wgOut->addHTML(
					"<tr>
						<td><a href=\"{$t->getFullURL()}\">{$t->getText()}</td>
						<td>{$row->gl_pos}</td>
						<td>{$row->gl_err}</td>
						<td><a href=\"{$url}\" target=\"new\">" .
							wfMsg( 'checkgoogle-link' ) .
						'</a></td>
					</tr>'
				);
			}
			$wgOut->addHTML( '</table>' );
		} elseif ( $target ) {
			$f = preg_replace( '@([0-9]{4})([0-9]{2})([0-9]{2})@', "$1-$2-$3", $target );
			$wgOut->addHTML(
				'<h2>' . wfMsg( 'checkgoogle-report', $f ) . '</h2>
					<table width="80%" align="center">
						<tr>
							<td>' . wfMsg( 'checkgoogle-creationdate' ) . '</td>
							<td>' . wfMsg( 'checkgoogle-checked-pages' ) . '</td>
							<td>' . wfMsg( 'checkgoogle-average-indexed' ) . '</td>
						</tr>'
			);
			$res = $dbr->select(
				array( 'google_indexed_log', 'google_indexed' ),
				array(
					'SUBSTR(gi_page_created, 1, 8) AS D',
					'COUNT(*) AS C',
					'AVG(gl_pos) AS A'
				),
				array( 'gl_err' => 0 ),
				__METHOD__,
				array( 'GROUP BY' => 'D', 'ORDER BY' => 'D DESC' ),
				array( 'google_indexed' => array( 'LEFT JOIN', 'gi_page = gl_page' ) )
			);
			foreach ( $res as $row ) {
				$avg = $wgLang->formatNum( $row->A * 100 );
				$count = $wgLang->formatNum( $row->C );
				$f = preg_replace( '@([0-9]{4})([0-9]{2})([0-9]{2})@', "$1-$2-$3", $row->D );
				$wgOut->addHTML(
					'<tr>
						<td>' . Linker::link( $this->getTitle( $target )->getFullURL(
							array( 'createdate' => $row->D ) ), $f ) . "</td>
						<td>$count</td>
						<td>$avg%</td>
					</tr>"
				);
			}
			$wgOut->addHTML( '</table>' );

			$likeString = $dbr->buildLike( $target, $dbr->anyString() );
			$errs = $dbr->selectField(
				'google_indexed_log',
				array( 'COUNT(*)' ),
				array( "gl_checked $likeString", 'gl_err' => 1 ),
				__METHOD__
			);
			$wgOut->addHTML(
				'<br /><br />' .
				wfMsg( 'checkgoogle-number-of-errors', $errs ) . '<br />'
			);
		}

		// list the individual reports we ran
		$wgOut->addHTML(
			'<br /><br /><h2>' . wfMsg( 'checkgoogle-individual-reports' ) .
			'</h2><ul>'
		);
		$res = $dbr->select(
			'google_indexed_log',
			array( 'SUBSTR(gl_checked, 1, 8) AS D' ),
			array(),
			__METHOD__,
			array( 'GROUP BY' => 'D', 'ORDER BY' => 'D DESC' )
		);
		foreach ( $res as $row ) {
			$f = preg_replace( '@([0-9]{4})([0-9]{2})([0-9]{2})@', "$1-$2-$3", $row->D );
			if ( $target == $row->D ) {
				$lookingAtItMsg = wfMsg( 'checkgoogle-looking-at-it' );
				$wgOut->addHTML( "<li>{$f} {$lookingAtItMsg}</li>\n" );
			} else {
				$wgOut->addHTML(
					'<li>' . Linker::link( $this->getTitle( $row->D ), $f ) .
					"</li>\n"
				);
			}
		}
		$wgOut->addHTML( '</ul>' );
	}

	/**
	 * Handler for the MediaWiki update script, update.php; this code is
	 * responsible for creating the required tables in the database when
	 * the user runs maintenance/update.php.
	 *
	 * @param $updater DatabaseUpdater
	 * @return Boolean: true
	 */
	public static function createTablesInDB( $updater ) {
		$dir = dirname( __FILE__ );

		$updater->addExtensionUpdate( array(
			'addTable', 'google_indexed', "$dir/google_tables.sql", true
		) );
		$updater->addExtensionUpdate( array(
			'addTable', 'google_indexed_log', "$dir/google_tables.sql", true
		) );

		return true;
 	}
}
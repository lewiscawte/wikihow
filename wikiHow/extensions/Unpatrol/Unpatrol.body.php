<?php

class Unpatrol extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Unpatrol', 'unpatrol' );
	}

	function padVar( $name ) {
		global $wgRequest;
		$val = $wgRequest->getVal( $name );
		if ( $val && strlen( $val ) < 2 ) {
			$val = '0' . $val;
		}
		return $val;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgUser;

		// Check permissions
		if ( !$wgUser->isAllowed( 'unpatrol' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Now, whose edits are we going to unpatrol?
		$username = $wgRequest->getVal( 'username', $par );

		$wgOut->addHTML(
			'<form action="' . $this->getTitle()->getFullURL() . '" method="post">' .
			wfMsg( 'unpatrol-username' ) . ' <input type="text" name="username" value="' . $username . '" / > <br /><br />' .
			wfMsg( 'unpatrol-startdate' ) . wfMsg( 'word-separator' ) . wfMsg( 'unpatrol-year' ) .
			date( 'Y' ) . ' ' . wfMsg( 'unpatrol-month' ) . ' <input type="text" name="month_1" size="2" value="' . date( 'm' ) . '" />' .
			wfMsg( 'unpatrol-day' ) . ' <input type="text" name="day_1" size="2" value="' . date( 'd' ) . '" />' .
			wfMsg( 'unpatrol-hour' ) . ' <input type="text" name="hour_1" size="2" value="00" /> <br /><br />' .
			wfMsg( 'unpatrol-enddate' ) . wfMsg( 'word-separator' ) . wfMsg( 'unpatrol-year' ) .
			date( 'Y' ) . ' <input type="text" name="month_2" size="2" />' .
			wfMsg( 'unpatrol-day' ) . ' <input type="text" name="day_2" size="2" />' .
			wfMsg( 'unpatrol-hour' ) . ' <input type="text" name="hour_2" size="2" /> <br /><br />
			<input type="submit" value="' . wfMsg( 'unpatrol-submit' ) . '" />
			</form>'
		);

		// If the request was POSTed, start doing stuff!
		if ( $wgRequest->wasPosted() ) {
			$user = $wgRequest->getVal( 'username' );

			$start = date( 'Y' ) . $this->padVar( 'month_1' ) .
				$this->padVar( 'day_1' ) . $this->padVar( 'hour_1' ) . '0000';

			$end = null;
			if ( $wgRequest->getVal( 'month_2' ) ) {
				$end = date( 'Y' ) . $this->padVar( 'month_1' ) .
					$this->padVar( 'day_2' ) . $this->padVar( 'hour_2' ) . '0000';
			}

			$cutoff = wfTimestamp( TS_MW, $start );
			$cutoff2 = null;
			if ( !$end ) {
				$wgOut->addHTML( wfMsg( 'unpatrol-reverting', $user, $cutoff ) . '<br />' );
			} else {
				$cutoff2 = wfTimestamp( TS_MW, $end );
				$wgOut->addHTML( wfMsg( 'unpatrol-reverting-between', $user, $cutoff, $cutoff2 ) . '<br />' );
			}

			$user = User::newFromName( $user );

			if ( $user->getID() == 0 ) {
				$wgOut->addHTML( wfMsg( 'unpatrol-nouser', $wgRequest->getVal( 'username', '' ) ) );
				return;
			}

			$dbw = wfGetDB( DB_MASTER );
			$options = array(
				'log_user' => $user->getID(),
				'log_type' => 'patrol',
				"log_timestamp > '{$cutoff}'"
			);

			if ( $cutoff2 ) {
				$options[] = "log_timestamp < '{$cutoff2}'";
			}

			$res = $dbw->select(
				'logging',
				array( 'log_title', 'log_params' ),
				$options,
				__METHOD__
			);

			$oldIds = array();
			foreach ( $res as $row ) {
				$oldIds[] = preg_replace( "@\n.*@", '', $row->log_params );
			}

			if ( sizeof( $oldIds ) > 0 ) {
				$count = $dbw->update(
					'recentchanges',
					array( 'rc_patrolled' => 0 ),
					array( 'rc_this_oldid IN (' . implode( ', ', $oldIds ) . ')' ),
					__METHOD__
				);
				wfRunHooks( 'Unpatrol', array( &$oldIds ) );
				$wgOut->addHTML( wfMsgExt( 'unpatrol-done', 'parsemag', sizeof( $oldIds ), $user->getName() ) . "\n" );

				$log = new LogPage( 'unpatrol', false );
				$msg = wfMsgHtml(
					'unpatrol-log-entry',
					sizeof( $oldIds ),
					'[[User:' . $user->getName() . ']]',
					$wgLang->date( $cutoff ),
					$cutoff2 == null ? $wgLang->date( wfTimestampNow() ) : $wgLang->date( $cutoff2 )
				);
				$log->addEntry( 'unpatrol', $this->getTitle(), $msg );
			} else {
				$wgOut->addHTML( wfMsg( 'unpatrol-noedits' ) . '<br />' );
			}
		}

		return;
	}
}

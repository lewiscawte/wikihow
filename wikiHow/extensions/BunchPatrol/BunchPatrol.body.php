<?php

class BunchPatrol extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'BunchPatrol' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		// No patrolling your own edits!
		if ( $target == $wgUser->getName() ) {
			$wgOut->addHTML( wfMsg( 'bunchpatrol-noselfpatrol' ) );
			return;
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add JavaScript
		$wgOut->addModules( 'ext.bunchPatrol' );

		$dbr = wfGetDB( DB_SLAVE );
		$me = $this->getTitle();

 		$unpatrolled = $dbr->selectField(
			'recentchanges',
			array( 'COUNT(*)' ),
			array( 'rc_patrolled' => 0 ),
			__METHOD__
		);

		if ( !strlen( $target ) ) {
			$restrict = '(rc_namespace = 2 OR rc_namespace = 3)';
			$res = $dbr->select(
				'recentchanges',
				array( 'rc_user', 'rc_user_text', 'COUNT(*) AS C' ),
				array( 'rc_patrolled' => 0, $restrict ),
				__METHOD__,
				array(
					'GROUP BY' => 'rc_user_text',
					'HAVING' => 'C > 2',
					'ORDER BY' => 'C DESC'
				),
			);
			$wgOut->addHTML( '<table width="85%" align="center">' );
			while ( ( $row = $dbr->fetchObject( $res ) ) != null ) {
				$u = User::newFromName( $row->rc_user_text );
				if ( $u ) {
					$bpLink = SpecialPage::getTitleFor( 'BunchPatrol', $u->getName() );
					$wgOut->addHTML(
						'<tr><td>' .
						Linker::link( $bpLink, $u->getName() ) .
						"</td><td>{$row->C}</td>"
					);
				}
			}
			$wgOut->addHTML( '</table>' );
			return;
		}

		if ( $wgRequest->wasPosted() && $wgUser->isAllowed( 'patrol' ) ) {
			$values = $wgRequest->getValues();
			$vals = array();
			foreach ( $values as $key => $value ) {
				if ( strpos( $key, 'rc_' ) === 0 && $value == 'on' ) {
					$vals[] = str_replace( 'rc_', '', $key );
				}
			}
			foreach ( $vals as $val ) {
				RecentChange::markPatrolled( $val );
				PatrolLog::record( $val, false );
			}
			$whereConds = array(
				'rc_patrolled = 0'
			);
			$whereConds[] = ' (rc_namespace 2 OR rc_namespace = 3) ';
			$res = $dbr->select(
				'recentchanges',
				array( 'rc_user', 'rc_user_text', 'COUNT(*) AS C' ),
				$whereConds,
				__METHOD__,
				array(
					'GROUP BY' => 'rc_user_text',
					'HAVING' => 'C > 2',
					'ORDER BY' => 'C DESC'
				)
			);
			$wgOut->addHTML( '<table width="85%" align="center">' );
			while ( ( $row = $dbr->fetchObject( $res ) ) != null ) {
				$u = User::newFromName( $row->rc_user_text );
				if ( $u ) {
					$wgOut->addHTML(
						'<tr><td>' .
							Linker::link(
								$me,
								$u->getName(),
								array( 'target' => $u->getName() )
							) . "</td><td>{$row->C}</td>"
					);
				}
			}
			$wgOut->addHTML( '</table>' );
			return;
		}

		// don't show main namespace edits if there are < 500 total unpatrolled edits
		// the following line is related to a bad development/design decision
		// made by wikiHow ages ago: they chose to replace spaces with hyphens
		// in URLs. So the following line is needed for wikiHow.com, but we are
		// trying to deprecate that unsupported URL scheme.
		//$target = str_replace( '-', ' ', $target );
		$opts = array(
			'rc_user_text' => $target,
			'rc_patrolled = 0'
		);
		$opts[] = ' (rc_namespace = 2 OR rc_namespace = 3) ';

		$res = $dbr->select(
			'recentchanges',
			array(
				'rc_id', 'rc_title', 'rc_namespace', 'rc_this_oldid',
				'rc_cur_id', 'rc_last_oldid'
			),
			$opts,
			__METHOD__,
			array( 'LIMIT' => 15 )
		);

		$count = 0;

		$wgOut->addHTML(
			"<form method=\"post\" name=\"checkform\" action=\"{$me->getFullURL()}\">
				<input type=\"hidden\" name=\"target\" value=\"{$target}\" />"
		);

		if ( $wgUser->isAllowed( 'bunchpatrol' ) ) {
			$wgOut->addHTML(
				wfMsg( 'bunchpatrol-select' ) .
					' <input type="button" id="check-all" value="' . wfMsg( 'bunchpatrol-all' ) . '" />
					<input type="button" id="check-none" value="' . wfMsg( 'bunchpatrol-none' ) . '" />'
			);
		}

		$wgOut->addHTML(
			'<table width="100%" align="center" class="bunchtable">
				<tr>
					<td><b>' . wfMsg( 'bunchpatrol-patrol' ) . '</b></td>
					<td align="center"><b>' . wfMsg( 'bunchpatrol-diff' ) . '</b></td>
				</tr>'
			);

		while ( ( $row = $dbr->fetchObject( $res ) ) != null ) {
			$t = Title::makeTitle( $row->rc_namespace, $row->rc_title );
			$diff = $row->rc_this_oldid;
			$rcid = $row->rc_id;
			$oldid = $row->rc_last_oldid;
			$de = new DifferenceEngine( $t, $oldid, $diff, $rcid );
			$wgOut->addHTML(
				"<tr>
					<td valign=\"middle\" style=\"padding-right: 24px; border-right: 1px solid #eee;\">
						<input type=\"checkbox\" name=\"rc_{$rcid}\" />
					</td>
					<td style=\"border-top: 1px solid #eee;\">"
			);
			$wgOut->addHTML( Linker::link( $t/*, $row->rc_title */ ) );
			$de->showDiffPage( true );
			$wgOut->addHTML( '</td></tr>' );
			$count++;
		}

		$wgOut->addHTML( '</table><br /><br />' );
		if ( $count > 0 ) {
			$wgOut->addHTML( '<input type="submit" value="' . wfMsg( 'submit' ) . '" />' );
		}
		$wgOut->addHTML( '</form>' );

		// Nothing to patrol...
		if ( $count == 0 ) {
			$wgOut->addWikiMsg( 'bunchpatrol-nounpatrollededits', $target );
		}
	}

}
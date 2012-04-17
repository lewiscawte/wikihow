<?php

class AuthorLeaderboard extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AuthorLeaderboard' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgLang;

		$dbr = wfGetDB( DB_SLAVE );

		// Add CSS
		$wgOut->addModules( 'ext.authorLeaderboard' );

		if ( date( 'w', time() ) == 1 ) {
			// Special case for the day it switches since strtotime is not consistent
			$startDate = strtotime( 'monday' );
			$nextDate = strtotime( 'next monday' );
		} else {
			$startDate = strtotime( 'last monday' );
			$nextDate = strtotime( 'next monday' );
		}
		$date1 = date( 'm/d/Y', $startDate );
		$date2 = date( 'm/d/Y', $nextDate );
		$startTimestamp = date( 'Ymd', $startDate ) . '000000';

		// DB query to get new articles
		$resfe = $dbr->select(
			'firstedit',
			'*',
			array( "fe_timestamp >= '$startTimestamp'" ),
			__METHOD__
		);

		// DB query to get rising star articles
		$editSummaryStr = 'Marking new article as a Rising Star from From'; // @todo FIXME: ...
		$res2 = $dbr->select(
			'recentchanges',
			'DISTINCT(rc_title)'
			array(
				"rc_timestamp >= '$startTimestamp'",
				'rc_comment ' . $dbr->buildLike( $editSummaryStr, $dbr->anyString() ),
				'rc_namespace' => NS_TALK
			),
			__METHOD__
		);

		$total_newarticles = $dbr->numRows( $resfe );
		$row = $dbr->fetchObject( $resfe );
		// Setup array for new articles
		while ( $row != null ) {
			$t = Title::newFromID( $row->fe_page );
			if ( isset( $t ) ) {
				if ( $t->getArticleID() > 0 ) {
					//if ( !preg_match( '/\d+\.\d+\.\d+\.\d+/', $row->fe_user_text ) )
						$leader_articles[$row->fe_user_text]++;
				}
			}
		}

		$total_risingstar = $dbr->numRows( $res2 );
		$leader_rs = array();
		$row = $dbr->fetchObject( $res2 );
		// Setup array for rising star articles
		while ( $row != null ) {
			$t = Title::newFromText( $row->rc_title );
			$r = Revision::newFromTitle( $t );
			//if ( preg_match( '/#REDIRECT \[\[(.*?)\]\].*?/', $r->getText(), $matches ) ) {
			if ( $t->isRedirect() ) {
				$a = new Article( $t, 0 );
				$t = Title::newFromText( $a->getRedirectTarget() );
			}
			$a = new Article( $t, 0 );
			$author = $a->getContributors();
			$user = $author[0];
			$username = $user[1];
			$leader_rs[$username]++;
		}

		/**
 		 * New Articles Table
 		 */
		$wgOut->addHTML( "\n<div id=\"Authorleaderboard\">\n" );
		$wgOut->addHTML(
			wfMessage( 'authorleaderboard-total',
				$wgLang->formatNum( $total_newarticles ),
				$date1, $date2 )->parse() .
			'<br /><br /><center>'
		);

		$wgOut->addHTML( '<br /><table width="500px" align="center" class="status">' );
		// display header
		$index = 1;
		$wgOut->addHTML(
			'<tr>
				<td></td>
				<td>' . wfMessage( 'authorleaderboard-user' )->text() . '</td>
				<td align="right">' .
					wfMessage( 'authorleaderboard-articleswritten-header' )->text() .
				'</td>
			</tr>'
		);

		// display difference in only new articles
		arsort( $leader_articles );
		foreach( $leader_articles as $key => $value ) {
			$u = new User();
			$u->setName( $key );
			if ( ( $value > 0 ) && ( $key != '' ) ) {
				$class = '';
				if ( $index % 2 == 1 ) {
					$class = ' class="odd"';
				}
				$wgOut->addHTML(
					"<tr$class>
						<td>$index</td>
						<td>" . Linker::link( $u->getUserPage(), $u->getName() ) . "</td>
						<td align=\"right\">$value</td>
					</tr>"
				);
				$leader_articles[$key] = $value * -1;
				$index++;
			}
			if ( $index > 20 ) {
				break;
			}
		}
		$wgOut->addHTML( '</table><br /><br />' );

		/**
 		 * Rising Star Table
 		 */
		$wgOut->addHTML(
			wfMessage( 'authorleaderboard-rs-total',
				$wgLang->formatNum( $total_risingstar ),
				$date1, $date2 )->parse() .
			'<br /><br /><center>'
		);

		$wgOut->addHTML( '<br /><table width="500px" align="center" class="status">' );
		// display header
		$index = 1;
		$wgOut->addHTML(
			'<tr>
				<td></td>
				<td>' . wfMessage( 'authorleaderboard-user' )->text() . '</td>
				<td align="right">' . wfMessage( 'authorleaderboard-risingstar-header' )->text() . '</td>
			</tr>'
		);

		arsort( $leader_rs );
		foreach ( $leader_rs as $key => $value ) {
			$u = new User();
			$u->setName( $key );
			$class = '';
			if ( $index % 2 == 1 ) {
				$class = ' class="odd"';
			}
			$wgOut->addHTML(
				"<tr$class>
					<td>$index</td>
					<td>" . Linker::link( $u->getUserPage(), $u->getName() ) . '</td>
					<td align="right">' . $leader_rs[$key] . '</td>
				</tr>
			');
			$leader_articles[$key] = -1;
			$index++;
			if ( $index > 20 ) {
				break;
			}
		}
		$wgOut->addHTML( '</table>' );

		$wgOut->addHTML( '</center>' );
		$wgOut->addHTML( "</div>\n" );
	}
}

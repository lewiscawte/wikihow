<?php

/**
 * Query page to display the list of low accuracy / low rating articles
 */
class ListAccuracyPatrol extends PageQueryPage {

	var $targets = array();

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AccuracyPatrol' );
	}

	function getName() {
		return 'AccuracyPatrol';
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false;
	}

	function getPageHeader() {
		global $wgOut;
		return $wgOut->parse( wfMsg( 'accuracypatrol-list-low-ratings-text' ) );
	}

	function getQueryInfo() {
		return array(
			'tables' => array( 'rating_low', 'page' ),
			'fields' => array(
				'page_namespace',
				'page_title',
				'rl_avg',
				'rl_count'
			),
			'conds' => array(
				'rl_page = page_id'
			),
			'options' => array( 'USE INDEX' => 'page_len' )
		);
	}

	function getOrderFields() {
		return array( 'rl_avg' );
	}

	function formatResult( $skin, $result ) {
		global $wgLang;
		$t = Title::makeTitle( $result->page_namespace, $result->page_title );
		if ( $t == null ) {
			return '';
		}
		$avg = $wgLang->formatNum( $result->rl_avg * 100 );
		$cl = SpecialPage::getTitleFor( 'ClearRatings', $t->getText() );
		return wfMessage( 'accuracypatrol-result-line' )->params(
			$t->getFullText(), $result->rl_count, $avg )->parse();
	}

	/**
	 * This function is used for de-indexing purposes.
	 * All articles that show up on the page Special:AccuracyPatrol are de-indexed.
	 */
	static function isInaccurate( $articleId, &$dbr ) {
		$row = $dbr->selectField(
			'rating_low',
			'rl_page',
			array( 'rl_page' => $articleId ),
			__METHOD__
		);

		return $row !== false;
	}
}

/**
 * AJAX call class to actually rate an article.
 */
class RateArticle extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'RateArticle' );
	}

	function ratingsMove( $a, $ot, $nt ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'rating',
			array( 'rat_page' => $ot->getArticleID() ),
			array( 'rat_page' => $nt->getArticleID(), 'rat_isdeleted' => 0 ),
			__METHOD__
		);
		return true;
	}

	function clearRatingsOnDelete( $article, $user, $reason ) {
		RateArticle::clearRatingForPage(
			$article->getID(),
			$article->getTitle(),
			$user,
			wfMessage( 'ratearticle-deletion-summary' )->inContentLanguage()->text()
		);
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		$rat_page = $wgRequest->getInt( 'page_id' );
		$rat_user = $wgUser->getID();
		$rat_user_text = $wgUser->getName();
		$rat_rating = $wgRequest->getInt( 'rating' );
		$wgOut->disable();

		// disable ratings more than 5, less than 1
		if ( $rat_rating > 5 || $rat_rating < 0 ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$ts = wfTimestampNow( TS_MW );
		$month = substr( $ts, 0, 4 ) . '-' . substr( $ts, 4, 2 );

		// Ugly, because the Database class doesn't support ON DUPLICATE KEY UPDATE :-(
		$dbw->query(
			'INSERT INTO rating (rat_page, rat_user, rat_user_text, rat_rating, rat_month)
			VALUES (' . $dbw->addQuotes( $rat_page ) . ",
				$rat_user, "
				. $dbw->addQuotes( $rat_user_text ) . ", "
				. $dbw->addQuotes( $rat_rating ) . ", '$month'
			)
			ON DUPLICATE KEY UPDATE rat_rating=" .  $dbw->addQuotes( $rat_rating ),
			__METHOD__
		);
	}

	function showForm() {
		global $wgOut, $wgArticle, $wgTitle, $wgRequest;

		if ( $wgArticle == null ) {
			return;
		}
		$page_id = $wgArticle->getID();
		if ( $page_id <= 0 ) {
			return;
		}
		$action = $wgRequest->getVal( 'action' );
		if ( $action != null &&  $action != 'view' ) {
			return;
		}
		if ( $wgRequest->getVal( 'diff', null ) != null ) {
			return;
		}

		/* use this only for (Main) namespace pages that are not the main page - feel free to remove this... */
		$mainPageObj = Title::newMainPage();
		if ( $wgTitle->getNamespace() != NS_MAIN
			|| $mainPageObj->getFullText() == $wgTitle->getFullText() )
		{
			return;
		}

		$target = $this->getTitle();
		$dt = $wgTitle->getTalkPage();

		$langKeys = array('ratearticle-rated', 'ratearticle-notrated', 'ratearticle-talkpage');
		$js = WikiHow_i18n::genJSMsgs($langKeys);

		$s .= "$js <p>" . wfMsg( 'ratearticle-question' ) . '</p>
			<table style="width:100%;">
				<tr>
					<td align="right"><a href="javascript:rateArticle(1);" id="gatAccuracyYes" class="button white_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">' .
						wfMsg( 'ratearticle-yes-button' ) . '</a>
					</td>
					<td align="left"><a href="javascript:rateArticle(0)" id="gatAccuracyNo" class="button white_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">' .
						wfMsg( 'ratearticle-no-button' ) . '</a>
					</td>
				</tr>
			</table>';
		return $s;
	}

	function clearRatingForPage( $id, $title, $user, $reason = null ) {
		global $wgRequest, $wgLanguageCode;

		$dbw = wfGetDB( DB_MASTER );

		$max = $dbw->selectField(
			'rating',
			'MAX(rat_id)',
			array( 'rat_page' => $id, 'rat_isdeleted' => 0 ),
			__METHOD__
		);
		$min = $dbw->selectField(
			'rating',
			'MIN(rat_id)',
			array( 'rat_page' => $id, 'rat_isdeleted' => 0 ),
			__METHOD__
		);
		$count = $dbw->selectField(
			'rating',
			'COUNT(*)',
			array( 'rat_page' => $id, 'rat_isdeleted' => 0 ),
			__METHOD__
		);

		$dbw->update(
			'rating',
			array(
				'rat_isdeleted' => 1,
				'rat_deleted_when' => wfTimestampNow(),
				'rat_user_deleted' => $user->getID()
			),
			array(
				'rat_page' => $id,
				'rat_isdeleted' => 0
			),
			__METHOD__
		);

		$dbw->delete( 'rating_low', array( 'rl_page' => $id ), __METHOD__ );

		if ( $reason == null ) {
			$reason = $wgRequest->getVal( 'reason' );
		}

		$params = array( $id, $min, $max );
		$log = new LogPage( 'accuracy', true );
		$log->addEntry(
			'accuracy',
			$title,
			wfMessage( 'clearratings-logsummary', $reason, $title->getFullText(), $count )->inContentLanguage()->parse(),
			$params
		);
	}
}

/**
 * Special page to clear the ratings of an article. Accessed via the list
 * of low ratings pages.
 */
class ClearRatings extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ClearRatings' );
	}

	function addClearForm( $target ) {
		global $wgOut;
		$blankme = $this->getTitle();
		$wgOut->addHTML(
			"<span style=\"color: red; font-weight: bold;\">$err</span>
				<hr size=\"1\"/><br /><form id=\"ratings\" method=\"get\" action=\"{$blankme->getFullURL()}\">
				" . wfMsg( 'clearratings-input-title' ) .
				' <input type="text" name="target" value="' .
					htmlspecialchars( $target ) . '" />' .
				'<input type="submit" value="' . wfMessage( 'clearratings-submit' )->text() . '" />
			</form>'
		);
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wgRequest, $wgLang;

		$err = '';
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$restore = $wgRequest->getVal( 'restore', null );

		$wgOut->setHTMLTitle( wfMessage( 'clearratings-title' )->text() );
		$t = Title::newFromText( $target );

		if ( $t == '' ) {
			$wgOut->addHTML( wfMsg( 'clearratings-no-title' ) );
			$this->addClearForm( $target );
			return;
		}
		$me = SpecialPage::getTitleFor( 'ClearRatings', $t->getText() );

		if ( $wgUser->getID() == 0 ) {
			return;
		}

		if ( $wgRequest->wasPosted() ) {
			// clearing ratings
			$clear = $wgRequest->getVal( 'clear', null );
			$confirm = $wgRequest->getVal( 'confirm', null );
			if ( $clear != null && $confirm == null && false ) {
				$id = $t->getArticleID();
				$wgOut->addHTML(
					wfMsg( 'clearratings-clear-confirm-prompt', Linker::link( $t, $t->getFullText() ) ) .
						'<br /><br />
						<form id="clear_ratings" method="post">
							<input type="hidden" value="' . $id . '" name="clear" />
							<input type="hidden" value="true" name="confirm" />
							<input type="hidden" value="' . htmlspecialchars( $target ) . '" name="target" />
							<input type="submit" value="' . wfMsg( 'clearratings-clear-confirm' ) . '" />
						</form>'
					);
				return;
			} elseif ( $clear != null ) {
				RateArticle::clearRatingForPage( $clear, $t, $wgUser );
				$wgOut->addHTML( wfMsg( 'clearratings-clear-finished' ) . '<br /><br />' );
			}
		}

		if ( $restore != null && $wgRequest->getVal( 'reason', null ) == null ) {
			$wgOut->addHTML( wfMsg( 'clearreating-reason-restore' ) . '<br /><br />' );
			$wgOut->addHTML( "<form id=\"clear_ratings\" method=\"post\" action=\"{$me->getFullURL()}\">" );
			$wgOut->addHTML(
				wfMsg( 'clearratings-reason' ) .
				' <input type="text" name="reason" size="40" /><br /><br />'
			);
			// @todo FIXME: o_O
			foreach ( $_GET as $k => $v ) {
				$wgOut->addHTML( Html::hidden( $k, $v ) );
			}
			$wgOut->addHTML( '<input type="submit" value="' . wfMsg( 'clearratings_submit' ) . '" />' );
			$wgOut->addHTML( '</form>' );
			return;
		} elseif ( $restore != null ) {
			$dbw = wfGetDB( DB_MASTER );
			$user = $wgRequest->getVal( 'user' );
			$page = $wgRequest->getVal( 'page' );
			$u = new User();
			$u->setID( $user );
			$up = $u->getUserPage();
			$hi = $wgRequest->getVal( 'hi' );
			$low = $wgRequest->getVal( 'low' );
			$count = $dbw->selectField(
				'rating',
				'COUNT(*)',
				array( 'rat_page' => $page, 'rat_isdeleted' => 1 )
				__METHOD__
			);

			$dbw->update(
				'rating',
				array( 'rat_isdeleted' => 0 ),
				array(
					'rat_user_deleted' => $user,
					'rat_page' => $page,
					"rat_id <= $hi",
					"rat_id >= $low"
				),
				__METHOD__
			);
			$wgOut->addHTML(
				'<br /><br />' .
				wfMsg( 'clearratings-clear-restored', Linker::link( $up, $u->getName() ), $when ) .
				'<br /><br />'
			);

			// add the log entry
			$t = Title::newFromId( $page );
			$params = array( $page, $min, $max );
			$log = new LogPage( 'accuracy', true );
			$reason = $wgRequest->getVal( 'reason' );
			$log->addEntry(
				'accuracy',
				$t,
				wfMessage( 'clearratings-logrestore', $reason, $t->getFullText(), $count )->inContentLanguage()->parse(),
				$params
			);
		}

		if ( $target != null ) {
			$t = Title::newFromText( $target );
			$id = $t->getArticleID();

			if ( $id == 0 ) {
				$err = wfMsg( 'clearratings-no-such-title', $wgRequest->getVal( 'target' ) );
			} elseif ( $t->getNamespace() != NS_MAIN ) {
				$err = wfMsg( 'clearratings_only_main', $wgRequest->getVal( 'target' ) );
			} else {
				// clearing info
				$dbr = wfGetDB( DB_MASTER );

				// get log
				$res = $dbr->select(
					array( 'logging' ),
					array( 'log_timestamp', 'log_user', 'log_comment', 'log_params' ),
					array( 'log_type' => 'accuracy', 'log_title' => $t->getDBKey() ),
					__METHOD__
				);
				$count = 0;
				$wgOut->addHTML( wfMsg( 'clearratings-previous-clearings' ) . '<ul>' );
				foreach ( $res as $row ) {
					$d = $wgLang->date( $row->log_timestamp );
					$u = new User();
					$u->setID( $row->log_user );
					$up = $u->getUserPage();
					$params = explode( "\n", $row->log_params );
					$wgOut->addHTML( '<li>' . Linker::link( $up, $u->getName() ) . " ($d): " );
					$wgOut->addHTML( preg_replace( '/<?p>/', '', $wgOut->parse( $row->log_comment ) ) );
					$wgOut->addHTML( '</i>' );
					if ( strpos( $row->log_comment, wfMsg( 'clearratings-restore' ) ) === false ) {
						$wgOut->addHTML(
							'(' . Linker::link(
								$me,
								wfMsg( 'clearratings-previous-clearings-restore' ),
								array(),
								array(
									'page' => $id,
									'hi' => $params[2],
									'low' => $params[1],
									'target' => $target,
									'user' => $row->log_user,
									'restore' => '1'
								)
							) . ')'
						);
					}
					$wgOut->addHTML( '</li>' );
					$count++;
				}
				$wgOut->addHTML( '</ul>' );
				if ( $count == 0 ) {
					$wgOut->addHTML( wfMsg( 'clearratings-previous-clearings-none' ) . '<br /><br />' );
				}

				$res= $dbr->select(
					array( 'rating' ),
					array( 'COUNT(*) AS C', 'AVG(rat_rating) AS R' ),
					array( 'rat_page' => $id, 'rat_isdeleted' => 0 ),
					__METHOD__
				);
				$row = $dbr->fetchObject( $res );
				if ( $row )  {
					$percent = $row->R * 100;
					$wgOut->addHTML(
						Linker::link( $t, $t->getFullText() ) . '<br /><br />'  .
						wfMessage( 'clearratings-number-votes', $wgLang->formatNum( $row->C ) )->parse() . "<br />" .
						wfMsg( 'clearratings-avg-rating' ) . " {$percent} %<br /><br />
						<form id=\"clear_ratings\" method=\"post\" action='{$me->getFullURL()}'>
							<input type=\"hidden\" value=\"$id\" name=\"clear\" />
							<input type=\"hidden\" value=\"" . htmlspecialchars( $target ) . "\" name=\"target\" />
							" . wfMsg( 'clearratings-reason' ) . " <input type=\"text\" name=\"reason\" size=\"40\" /><br /><br />
							<input type=\"submit\" value='" . wfMsg( 'clearratings-clear-submit' ) . "' />
						</form><br /><br/ >"
					);
				}

				$ap = SpecialPage::getTitleFor( 'AccuracyPatrol' );
				$wgOut->addHTML( Linker::link( $ap, wfMessage( 'accuracypatrol-return-to' )->text() ) );
			}
		}

		$this->addClearForm( $target );
	}

}

/**
 * List the ratings of some set of pages
 */
class ListRatings extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ListRatings' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut;

		$wgOut->setHTMLTitle( wfMessage( 'listratings-title' )->text() );
		$wgOut->addHTML( '<ol>' );

		// TODO add something for viewing ratings 51-100, 101-150, etc
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'rating',
			array( 'rat_page', 'AVG(rat_rating) AS R', 'COUNT(*) AS C' ),
			array(),
			__METHOD__,
			array(
				'GROUP BY' => 'rat_page',
				'ORDER BY' => 'R DESC',
				'LIMIT' => 50
			)
		);

		foreach ( $res as $row ) {
			$t = Title::newFromID( $row->rat_page );
			if ( $t == null ) {
				continue;
			}
			$wgOut->addHTML(
				'<li>' . Linker::link( $t, $t->getFullText() ) .
				" ({$row->C}, {$row->R})</li>"
			);
		}

		$wgOut->addHTML( '</ol>' );
	}

}

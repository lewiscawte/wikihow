<?php
/**
 * Experimental Points scoring class to determine whether an edit is a
 * significant edit.
 * Currently not included in LocalSettings.php but may be of use later.
 */
class Points extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Points' );
	}

	/**
	 * @param $t Title: Title object; if not supplied, a random page will be used
	 * @return Revision
	 */
	function getRandomEdit( $t = null ) {
		// get a random page
		if ( !$t ) {
			$rp = new RandomPage();
			$t = $rp->getRandomTitle();
		}

		// pick a random one
		$dbr = wfGetDB( DB_SLAVE );
		$revId = $dbr->selectField(
			'revision',
			array( 'rev_id' ),
			array( 'rev_page' => $t->getArticleID() ),
			__METHOD__,
			array( 'ORDER BY' => 'rand()', 'LIMIT' => 1 )
		);
		$r = Revision::newFromID( $revId );
		return $r;
	}

	function getDiffToMeasure( $r ) {
		$dbr = wfGetDB( DB_SLAVE );
		$result = array();
		// get the low, we compare this against the last edit
		// which was made by a different user
		$revlo = $dbr->selectField(
			'revision',
			'rev_id',
			array(
				'rev_page' => $r->mTitle->getArticleID(),
				'rev_user_text != ' . $dbr->addQuotes( $r->mUserText ),
				'rev_id < ' . $r->mId
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => 1 )
		);

		// get the highest edit in this sequence of edits by this user
		$not_hi_row = $dbr->selectRow(
			'revision',
			array( 'rev_id', 'rev_comment', 'rev_user_text' ),
			array(
				'rev_page' => $r->mTitle->getArticleID(),
				'rev_user_text != ' . $dbr->addQuotes( $r->mUserText ),
				'rev_id > ' . $r->mId
			),
			__METHOD__
		);
		$revhi = null;
		if ( !$not_hi_row ) {
			$revhi = $r->mId;
		} else {
			$revhi = $dbr->selectField(
				'revision',
				'rev_id',
				array(
					'rev_page' => $r->mTitle->getArticleID(),
					'rev_id < ' . $not_hi_row->rev_id
				),
				__METHOD__,
				array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => 1 )
			);
			$result['nextcomment'] = $not_hi_row->rev_comment;
			$result['nextuser'] = $not_hi_row->rev_user_text;
		}

		$hi = Revision::newFromID( $revhi );
		$hitext = $hi->getText();

		$lotext = '';
		if ( $revlo ) {
			$lo = Revision::newFromID( $revlo );
			$lotext = $lo->getText();
		}

		if ( $lotext == '' ) {
			$result['newpage'] = 1;
		} else {
			$result['newpage'] = 0;
		}
		$opts = array(
			'rev_page' => $r->mTitle->getArticleID(),
			'rev_id <= ' . $revhi
		);
		if ( $revlo ) {
			$opts[] = 'rev_id > ' . $revlo;
		}
		$result['numedits'] = $dbr->selectField(
			'revision',
			'COUNT(*)',
			$opts,
			__METHOD__
		);
		$result['diff'] = wfDiff( $lotext, $hitext );
		$result['revhi'] = $hi;
		$result['revlo'] = $lo;
		return $result;
	}

	function getPoints( $r, $d, $de, $showDetails = false ) {
		global $wgContLang, $wgOut;

		$points = 0;

		$oldText = '';
		if ( $d['revlo'] ) {
			$oldText = $d['revlo']->mText;
		}
		$newText = $d['revhi']->mText;

		$flatOldText = preg_replace( '@[^a-zA-z]@', '', WikiHow::textify( $oldText ) );

		// get the points based on number of new / changed words
		$diffhtml = $de->generateDiffBody( $d['revlo']->mText, $d['revhi']->mText );
		$addedwords = 0;
		preg_match_all( '@<span class="diffchange diffchange-inline">[^>]*</span>@m', $diffhtml, $matches );
		foreach ( $matches[0] as $m ) {
			$m = WikiHow::textify( $m );
			preg_match_all( '@\b\w+\b@', $m, $words );
			$addedwords += sizeof( $words[0] );
		}
		preg_match_all( '@<td class="diff-addedline">(.|\n)*</td>@Um', $diffhtml, $matches );

		foreach ( $matches[0] as $m ) {
			if ( preg_match( '@diffchange-inline@', $m ) ) {
				// already accounted for in change-inline
				continue;
			}
			$m = WikiHow::textify( $m );

			// account for changes in formatting and punctuation
			// by flattening out the change piece of text and comparing to the
			// flattened old version of the text
			$flatM = preg_replace( '@[^a-zA-z]@', '', $m );
			if ( !empty( $flatM ) && strpos( $flatOldText, $flatM ) !== false ) {
				continue;
			}
			preg_match_all( '@\b\w+\b@', $m, $words );
			$addedwords += sizeof( $words[0] );
		}

		if ( $showDetails ) {
			$wgOut->addHTML(
				'<h3>' . wfMessage( 'points-for-edit' )->plain() . '</h3><ul>'
			);
		}

		// @todo FIXME: not internationally compatible; need to compare
		// $r->mComment to 'revertpage' and/or 'revertpage-nouser' somehow...
		if ( preg_match( '@Reverted@', $r->mComment ) ) {
			if ( $showDetails ) {
				$wgOut->addHTML(
					'<li>' . wfMessage( 'points-no-reverted-edit' )->plain() .
					'</li></ul><hr />'
				);
			}
			return 0;
		}
		if ( preg_match( '@Reverted edits by.*' . $d['revhi']->mUserText . '@', $d['nextcomment'] ) ) {
			if ( $showDetails ) {
				$wgOut->addHTML(
					'<li>' .
					wfMessage( 'points-no-reverted-by', $d['nextuser'] )->text() .
					"\n</li></ul><hr />"
				);
			}
			return 0;
		}

		$wordpoints = min( floor( $addedwords / 100 ), 5 );
		if ( $showDetails ) {
			$wgOut->addHTML(
				'<li>' .
				wfMessage( 'points-new-words', $addedwords, $wordpoints )->parse() .
				'</li>'
			);
		}
		$points += $wordpoints;

		// new images
		$newImagePoints = array();
		$fileNamespaceName = $wgContLang->getNsText( NS_FILE );
		preg_match_all( "@\[\[$fileNamespaceName:[^\]|\|]*@", $newText, $images );
		$newimages = $newImagePoints = 0;
		foreach ( $images[0] as $i ) {
			if ( strpos( $oldText, $i ) === false ) {
				$newImagePoints++;
				$newimages++;
			}
		}
		$newImagePoints = min( $newImagePoints, 2 );
		$points += $newImagePoints;
		if ( $showDetails ) {
			$wgOut->addHTML(
				'<li>' . wfMessage( 'points-new-images', $newimages, $newImagePoints )->parse()
				'</li>'
			);
		}

		// new page points
		if ( $d['newpage'] ) {
			if ( $showDetails ) {
				$wgOut->addHTML( '<li>' . wfMessage( 'points-new-page' )->parse() . '</li>' );
			}
			$points += 1;
		}


		// template points
		$reflistTemplateName = wfMessage( 'points-reflist-template-name' )->inContentLanguage()->plain();
		preg_match_all( "@\{\{[^\}]*\}\}@", $newText, $templates );
		foreach ( $templates[0] as $t ) {
			if ( strpos( $oldText, $t ) === false && $t != '{{' . $reflistTemplateName . '}}' ) {
				if ( $showDetails ) {
					$wgOut->addHTML( '<li>' . wfMessage( 'points-template-added' )->parse() . '</li>' );
				}
				$points++;
				break;
			}
		}

		// category added points
		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		preg_match_all( "@\[\[$categoryNamespaceName:[^\]]*\]\]@", $newText, $cats );
		foreach ( $cats[0] as $c ) {
			if ( strpos( $oldText, $c ) === false ) {
				if ( $showDetails ) {
					$wgOut->addHTML( '<li>' . wfMessage( 'points-category-added' )->parse() . '</li>' );
				}
				$points++;
				break;
			}
		}

		$points = min( $points, 10 );
		if ( $showDetails ) {
			$wgOut->addHTML( '</ul>' );
		}
		if ( $showDetails ) {
			$wgOut->addHTML( wfMessage( 'points-total', $points )->parse() . '<hr />' );
		}

		return $points;
	}

	// Group the edits of the page together by user
	function getEditGroups( $title ) {
		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'revision',
			array( 'rev_id', 'rev_user_text', 'rev_timestamp', 'rev_user' ),
			array( 'rev_page' => $title->getArticleID() ),
			__METHOD__
		);

		$results = array();
		$last_user = null;
		$x = null;

		foreach ( $res as $row ) {
			if ( $last_user == $row->rev_user_text ) {
				$x['edits']++;
				$x['max_revid'] = $row->rev_id;
				$x['max_revtimestamp'] = $row->rev_timestamp;
			} else {
				if ( $x ) {
					$results[] = $x;
				}
				$x = array();
				$x['user_id'] = $row->rev_user;
				$x['user_text'] = $row->rev_user_text;
				$x['max_revid'] = $row->rev_id;
				$x['min_revid'] = $row->rev_id;
				$x['max_revtimestamp'] = $row->rev_timestamp;
				$x['edits'] = 1;
				$last_user = $row->rev_user_text;
			}
		}

		$results[] = $x;
		return array_reverse( $results );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->setArticleRelated( false );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $target ) {
			if ( preg_match( '@[^0-9]@', $target ) ) {
				$t = Title::newFromURL( $target );
			} else {
				$r = Revision::newFromID( $target );
				if ( $wgRequest->getVal( 'popup' ) ) {
					$wgOut->setArticleBodyOnly( true );
					$wgOut->addHTML(
						"<style type='text/css'>
						table.diff {
							margin-left: auto; margin-right: auto;
						}
						table.diff td {
							max-width: 400px;
						}
						</style>"
					);
				}
				$wgOut->addHTML( wfMessage( 'points-revid', $r->mId )->plain() . "\n" );
				$d = self::getDiffToMeasure( $r );
				$de = new DifferenceEngine( $r->mTitle, $d['revlo']->mId, $d['revhi']->mId );
				self::getPoints( $r, $d, $de, true );
				if ( !$d['revlo'] ) {
					$de->mOldRev = null;
					$de->mOldid = null;
				}
				$de->showDiffPage();
				return;
			}
		} else {
			$rp = new RandomPage();
			$t = $rp->getRandomTitle();
		}

		$wgOut->addHTML(
			"<script type='text/javascript'>
function getPoints( rev ) {
	$( '#img-box' ).load( mw.config.get( 'wgArticlePath ).replace( '$1', 'Special:Points/' + rev + '?popup=true' ), function() {
			$( '#img-box' ).dialog({
				width: 750,
				modal: true,
				title: '" . wfMessage( 'points' )->plain() . "',
				show: 'slide',
				closeOnEscape: true,
				position: 'center'
			});
	});
	return false;
}
</script>\n"
		);
		// get the groups of edits
		$group = self::getEditGroups( $t );
		$wgOut->addHTML(
			wfMsg( 'points-title', $t->getFullURL(), $t->getFullText() ) . '<br /><br />'
		);
		$wgOut->addHTML(
			'<table width="100%"><tr><td><u>' .
			wfMessage( 'points-user' )->plain() .
			'</u></td><td><u>' . wfMessage( 'points-edit-count' )->plain() .
			'</u></td>'
		);
		$wgOut->addHTML(
			'<td><u>' . wfMessage( 'points-date' )->plain() .
			'</u></td><td><u>' . wfMessage( 'points-table-header' )->plain() .
			'</u></td></tr>'
		);

		foreach ( $group as $g ) {
			$r = Revision::newFromID( $g['max_revid'] );
			$d = self::getDiffToMeasure( $r);
			$de = new DifferenceEngine( $r->mTitle, $d['revlo']->mId, $d['revhi']->mId );
			$points = self::getPoints( $r, $d, $de );
			$date = date( 'Y-m-d', wfTimestamp( TS_UNIX, $g['max_revtimestamp'] ) );
			$wgOut->addHTML( "<tr><td>{$g['user_text']}</td><td>{$g['edits']}</td><td>{$date}</td>" );
			$wgOut->addHTML( "<td><a href='#' onclick='return getPoints({$g['max_revid']});'>{$points}</a></td></tr>" );
		}

		$wgOut->addHTML( '</table>' );
	}
}


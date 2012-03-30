<?php

class EditFinder extends UnlistedSpecialPage {
	var $topicMode = false;

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'EditFinder' );
	}

	public static function getUnfinishedCount( &$dbr, $type ) {
		switch( $type ) {
			case 'Stub':
				$count = $dbr->selectField(
					array( 'page', 'templatelinks' ),
					'COUNT(*) AS count',
					array(
						'tl_title' => 'Stub',
						'tl_from = page_id',
						'page_namespace' => NS_MAIN
					),
					__METHOD__
				);
				return $count;

			case 'Format':
				$count = $dbr->selectField(
					array( 'page', 'templatelinks' ),
					'COUNT(*) AS count',
					array(
						'tl_title' => 'Format',
						'tl_from = page_id',
						'page_namespace' => NS_MAIN
					),
					__METHOD__
				);
				return $count;
			case 'Topic':
				// No real unfinished count for Greenhouse by Topic
				return 0;
		}

		return 0;
	}

	function getNextArticle() {
		global $wgRequest;

		// skipping something?
		$skip_article = $wgRequest->getVal( 'skip' );

		// flip through a few times in case we run into problem articles
		for ( $i = 0; $i < 10; $i++ ) {
			if ( $this->topicMode ) {
				$pageId = $this->getNextByInterest( $skip_article );
			} else {
				$pageId = $this->getNext( $skip_article );
			}
			if ( !empty( $pageId ) ) {
				return $this->returnNext( $pageId );
			}
		}
		return $this->returnNext( '' );
	}


	function getNextByInterest( $skip_article ) {
		global $wgRequest, $wgUser;

		wfProfileIn( __METHOD__ );

		$dbw = wfGetDB( DB_MASTER );

		// mark skipped
		if ( !empty( $skip_article ) ) {
			$t = Title::newFromText( $skip_article );
			$id = $t->getArticleID();

			// mark the DB for this user
			if ( !empty( $id ) ) {
				$dbw->insert(
					'editfinder_skip',
					array(
						'efs_page' => $id,
						'efs_user' => $wgUser->getID(),
						'efs_timestamp' => wfTimestampNow()
					),
					__METHOD__
				);
			}
		}

		$aid = $wgRequest->getInt( 'id' );

		if ( $aid ) {
			// get a specific article
			$res = $dbw->select(
				'page',
				array( 'page_id' ),
				array( 'page_id' => $aid ),
				__METHOD__,
				array( 'LIMIT' => 1 )
			);
		} else {
			$timediff = date( 'YmdHis', strtotime( '-1 day' ) );

			// @todo FIXME: rewrite to use the Database class
			$sql = "SELECT page_id FROM page p INNER JOIN categorylinks c ON c.cl_from = page_id WHERE page_namespace = 0 ";
			$sql .= $this->getSkippedArticles( 'page_id' );
			$sql .= $this->getUserInterests();

			// teen filter
			if ( $wgUser->getOption( 'contentfilter' ) != 0 ) {
				$filter = $wgUser->getOption( 'contentfilter' );
				if ( $filter == 1 ) {
					$sql .= ' AND p.page_catinfo & ' . CAT_TEEN . ' = ' . CAT_TEEN;
				}
				if ( $filter == 2 ) {
					$sql .= ' AND p.page_catinfo & ' . CAT_TEEN . ' = 0 ';
				}
			}

			$sql .= ' ORDER BY p.page_random LIMIT 1;';
			$res = $dbw->query( $sql, __METHOD__ );
		}

		foreach ( $res as $row ) {
			$pageId = $row->page_id;
		}

		if ( $pageId ) {
			// not a specified an article, right?
			if ( empty( $aid ) ) {
				// is the article {{in use}}?
				if ( $this->articleInUse( $pageId ) ) {
					// mark it as viewed
					$pageId = '';
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return $pageId;
	}

	function getNext( $skip_article ) {
		global $wgRequest, $wgUser;

		$dbw = wfGetDB( DB_MASTER );

		// mark skipped
		if ( !empty( $skip_article ) ) {
			$t = Title::newFromText( $skip_article );
			$id = $t->getArticleID();

			// mark the DB for this user
			if ( !empty( $id ) ) {
				$dbw->insert(
					'editfinder_skip',
					array(
						'efs_page' => $id,
						'efs_user' => $wgUser->getID(),
						'efs_timestamp' => wfTimestampNow()
					),
					__METHOD__
				);
			}
		}

		$aid = $wgRequest->getInt( 'id' );

		if ( $aid ) {
			// get a specific article
			$res = $dbw->select(
				'editfinder',
				array( 'ef_edittype', 'ef_page' ),
				array( 'ef_page' => $aid ),
				__METHOD__,
				array( 'LIMIT' => 1 )
			);
		} else {
			$edittype = strtolower( $wgRequest->getVal( 'edittype' ) );

			$timediff = date( 'YmdHis', strtotime( '-1 day' ) );
			// @todo FIXME: this is horrrrrrible, rewrite it to use the
			// Database class instead
			$sql = "SELECT ef_edittype, ef_page FROM editfinder
					INNER JOIN page p ON p.page_id = ef_page
					WHERE ef_last_viewed < " . $dbw->addQuotes( $timediff ) . "
					AND LOWER(ef_edittype) = " . $dbw->addQuotes( $edittype )
					. $this->getSkippedArticles();

			$sql .= $this->getUserCats() . ' ';

			// teen filter
			if ( $wgUser->getOption( 'contentfilter' ) != 0 ) {
				$filter = $wgUser->getOption( 'contentfilter' );
				if ( $filter == 1 ) {
					$sql .= ' AND p.page_catinfo & ' . CAT_TEEN . ' = ' . CAT_TEEN;
				}
				if ( $filter == 2 ) {
					$sql .= ' AND p.page_catinfo & ' . CAT_TEEN . ' = 0 ';
				}
			}

			$sql .= ' LIMIT 1;';
			$res = $dbw->query( $sql, __METHOD__ );
		}

		foreach ( $res as $row ) {
			$pageId = $row->ef_page;
		}

		if ( $pageId ) {
			// not a specified an article, right?
			if ( empty( $aid ) ) {
				// is the article {{in use}}?
				if ( $this->articleInUse( $pageId ) ) {
					// mark it as viewed
					$dbw->update(
						'editfinder',
						array( 'ef_last_viewed' => wfTimestampNow() ),
						array( 'ef_page' => $pageId ),
						__METHOD__
					);
					$pageId = '';
				}
			}
		}

		return $pageId;
	}

	function returnNext( $pageId ) {
		if ( empty( $pageId ) ) {
			// nothing? Ugh.
			$a['aid'] = '';
		} else {
			if ( !$this->topicMode ) {
				// touch DB
				$dbw = wfGetDB( DB_MASTER );
				$dbw->update(
					'editfinder',
					array(
						'ef_last_viewed' => wfTimestampNow()
					),
					array( 'ef_page' => $pageId ),
					__METHOD__
				);
			}

			$a = array();

			$t = Title::newFromID( $pageId );

			$a['aid'] = $pageId;
			$a['title'] = $t->getText();
			$a['url'] = $t->getLocalURL();
		}

		// return array
		return( $a );
	}

	function confirmationModal( $type, $id ) {
		global $wgOut;

		wfProfileIn( __METHOD__ );

		$t = Title::newFromID( $id );
		$content = '
		<div class="editfinder_modal">
			<p>' . wfMessage( 'editfinder-thanks', $t->getText() )->parse() . '</p>
			<p>' . wfMessage( 'editfinder-remove-template', strtoupper( $type ) )->parse() . '</p>
			<div style="clear:both"></div>
			<span style="float:right">
			<input class="button blue_button_100 submit_button" id="editfinder-confirmbutton-yes" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" type="button" value="' . wfMsg( 'editfinder-confirmation-yes' ) . '" />
			<input class="button white_button_100 submit_button" id="editfinder-confirmbutton-no" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" type="button" value="' . wfMsg( 'editfinder-confirmation-no' ) . '" />
			</span>
		</div>';
		$wgOut->addHTML( $content );
		wfProfileOut( __METHOD__ );
	}

	function cancelConfirmationModal( $id ) {
		global $wgOut;

		wfProfileIn( __METHOD__ );

		$t = Title::newFromID( $id );
		$content = '
		<div class="editfinder_modal">
			<p>' . wfMessage( 'editfinder-stop-editing', $t->getText() )->parse() . '</p>
			<div style="clear:both"></div>
			<p id="efcc_choices">
			<a href="#" id="efcc_yes">' . wfMsg( 'editfinder-cancel-yes' ) . '</a>
			<input class="button blue_button_100 submit_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" type="button" value="' . wfMsg( 'editfinder-confirmation-no' ) . '" id="efcc_no" />
			</p>
		</div>';
		$wgOut->addHTML( $content );
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Check to see if {{inuse}} or {{in use}} is in the article
	 *
	 * @param $aid Integer: article ID number
	 * @return boolean
	 */
	function articleInUse( $aid ) {
		$dbr = wfGetDB( DB_SLAVE );
		$r = Revision::loadFromPageId( $dbr, $aid );

		$templateName = wfMessage( 'editfinder-inuse-template-name' )->inContentLanguage()->text();
		if ( strpos( $r->getText(), '{{' . $templateName ) === false ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	function getUserInterests() {
		$interests = CategoryInterests::getCategoryInterests();
		$interests = array_merge( $interests, CategoryInterests::getSubCategoryInterests( $interests ) );
		$interests = array_values( array_unique( $interests ) );

		$fn = function( &$value ) {
			$dbr = wfGetDB( DB_SLAVE );
			$value = $dbr->strencode( $value );
		};
		array_walk( $interests, $fn );
		$sql = " AND c.cl_to IN ('" . implode( "','", $interests ) . "') ";
		return $sql;
	}

	/**
	 * Grab categories specified by the user
	 *
	 * @return String
	 */
	function getUserCats() {
		global $wgUser, $wgCategoryNames;

		$cats = array();
		$catsql = '';
		$bitcat = 0;

		$dbr = wfGetDB( DB_SLAVE );

		$row = $dbr->selectRow(
			'suggest_cats',
			array( '*' ),
			array( 'sc_user' => $wgUser->getID() ),
			__METHOD__
		);

		if ( $row ) {
			$field = $row->sc_cats;
			$cats = preg_split( '@,@', $field, 0, PREG_SPLIT_NO_EMPTY );
		}

		$topcats = array_flip( $wgCategoryNames );

		foreach ( $cats as $key => $cat ) {
			foreach ( $topcats as $keytop => $cattop ) {
				$cat = str_replace( '-', ' ', $cat );
				if ( strtolower( $keytop ) == $cat ) {
					$bitcat |= $cattop;
					break;
				}
			}
		}
		if ( $bitcat > 0 ) {
			$catsql = ' AND p.page_catinfo & '.$bitcat.' <> 0';
		}
		return $catsql;
	}

	/**
	 * Grab articles that were already "skipped" by the user
	 * @return String
	 */
	function getSkippedArticles( $column = 'ef_page' ) {
		global $wgUser;

		$skipped = '';
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'editfinder_skip',
			array( 'efs_page' ),
			array( 'efs_user' => $wgUser->getID() ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$skipped_ary[] = $row->efs_page;
		}
		if ( count( $skipped_ary ) > 0 ) {
			$skipped = ' AND ' . $column . ' NOT IN (' . implode( ',', $skipped_ary ) . ') ';
		}

		return $skipped;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser, $wgParser;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		// Anons can't use this special page, they need to log in first
		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage(
				'editfinder-no-login',
				'editfinder-no-login-text',
				array( $this->getTitle()->getPrefixedDBkey() )
			);
			return;
		}

		// Don't allow blocked users to use this tool
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		$this->topicMode = strtolower( $par ) == 'topic' || strtolower( $wgRequest->getVal( 'edittype' ) ) == 'topic';

		if ( $wgRequest->getVal( 'fetchArticle' ) ) {
			$wgOut->setArticleBodyOnly( true );
			echo json_encode( $this->getNextArticle() );
			return;
		} elseif ( $wgRequest->getVal( 'show-article' ) ) {
			$wgOut->setArticleBodyOnly( true );

			if ( $wgRequest->getVal( 'aid' ) == '' ) {
				if ( $this->topicMode ) {
					$messageKey = 'editfinder-more-interests';
				} else {
					$messageKey = 'editfinder-more-categories';
				}
				$wgOut->addHTML( wfMessage( $messageKey )->text() );
				return;
			}

			$t = Title::newFromID( $wgRequest->getVal( 'aid' ) );

			$articleTitleLink = $t->getLocalURL();
			$articleTitle = $t->getText();
			//$edittype = $a['edittype'];

			// get article
			$a = new Article( $t );

			$r = Revision::newFromTitle( $t );
			$popts = $wgOut->parserOptions();
			$popts->setTidy( true );
			$popts->enableLimitReport();
			$parserOutput = $wgParser->parse( $r->getText(), $t, $popts, true, true, $a->getRevIdFetched() );
			$popts->setTidy( false );
			$popts->enableLimitReport( false );
			$html = WikiHowTemplate::mungeSteps( $parserOutput->getText(), array( 'no-ads' ) );
			$wgOut->addHTML( $html );
			return;
		} elseif ( $wgRequest->getVal( 'edit-article' ) ) {
			// Show the edit form
			$wgOut->setArticleBodyOnly( true );
			$t = Title::newFromID( $wgRequest->getVal( 'aid' ) );
			$a = new Article( $t );
			$editor = new EditPage( $a );
			$editor->edit();
			return;
		} elseif ( $wgRequest->getVal( 'action' ) == 'submit' ) {
			$wgOut->setArticleBodyOnly( true );

			$efType = strtolower( $wgRequest->getVal( 'type' ) );

			$t = Title::newFromID( $wgRequest->getVal( 'aid' ) );
			$a = new Article( $t );

			// log it
			$params = array( $efType );
			$log = new LogPage( 'EF_' . $efType, false ); // false - don't show in RecentChanges

			$log->addEntry(
				'',
				$t,
				wfMessage( 'editfinder-log-entry', strtoupper( $efType ) )->inContentLanguage()->text(),
				$params
			);

			$text = $wgRequest->getVal( 'wpTextbox1' );
			$sum = $wgRequest->getVal( 'wpSummary' );

			// save the edit
			$a->doEdit( $text, $sum, EDIT_UPDATE );
			wfRunHooks( 'EditFinderArticleSaveComplete', array( $a, $text, $sum, $wgUser, $efType ) );
			return;
		} elseif ( $wgRequest->getVal( 'confirmation' ) ) {
			$wgOut->setArticleBodyOnly( true );
			echo $this->confirmationModal( $wgRequest->getVal( 'type' ), $wgRequest->getVal( 'aid' ) );
			wfProfileOut( __METHOD__ );
			return;
		} elseif ( $wgRequest->getVal( 'cancel-confirmation' ) ) {
			$wgOut->setArticleBodyOnly( true );
			echo $this->cancelConfirmationModal( $wgRequest->getVal( 'aid' ) );
			wfProfileOut( __METHOD__ );
			return;
		} else { // default view (same as most of the views)
			$wgOut->setArticleBodyOnly( false );

			// Add CSS & JS
			$wgOut->addModules( 'ext.editFinder' );

			$efType = strtolower( $target );
			if ( strpos( $efType, '/' ) !== false ) {
				$efType = substr( $efType, 0, strpos( $efType, '/' ) );
			}
			if ( $efType == '' ) {
				// no type specified? Send 'em to format...
				$wgOut->redirect( $this->getTitle( 'Format' )->getFullURL() );
			}
			$wgOut->addHTML( '<script>var g_eftype = "' . $target . '";</script>' );

			// add main article info
			include( 'editfinder_main.tmpl.php' );
			$template = new EditFinderMainTemplate;
			$template->set( 'topicMode', ( $this->topicMode ? 'interests' : 'categories' ) );
			$template->set( 'pagetitle', wfMsg( 'editfinder-app-name' ) . ': ' . wfMsg( 'editfinder-' . $efType ) );
			$template->set( 'helparticle', wfMsg( 'editfinder-help-' . $efType ) );
			$wgOut->addTemplate( $template );

			$wgOut->setHTMLTitle( wfMsg( 'editfinder-app-name' ) . ': ' . wfMsg( 'editfinder-' . $efType ) );
		}

		// These two classes are defined (and autoloaded) in the Standings extension
		$stats = new EditFinderStandingsIndividual( $efType );
		$stats->addStatsWidget();
		$standings = new EditFinderStandingsGroup( $efType );
		$standings->addStandingsWidget();
	}
}

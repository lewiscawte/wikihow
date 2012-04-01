<?php

class AddRelatedLinks extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AddRelatedLinks', 'addrelatedlinks' );
	}

	function addLinkToRandomArticleInSameCategory( $t, $summary = null, $linktext = null ) {
		global $wgLang, $wgOut;

		if ( $summary == null ) {
			$summary = wfMessage( 'addrelatedlinks-edit-summary' )->inContentLanguage()->text();
		}

		$localizedCategoryName = $wgLang->getNsText( NS_CATEGORY );
		$cats = array_keys( $t->getParentCategories() );
		$found = false;
		$categoriesToIgnore = explode( "\n",
			wfMessage( 'addrelatedlinks-categories-to-ignore' )->inContentLanguage()->text()
		);

		while ( sizeof( $cats ) > 0 ) {
			$cat = array_shift( $cats );
			$cat = preg_replace( "@^$localizedCategoryName:@", '', $cat );
			$cat = Title::newFromText( $cat );
			if ( in_array( $cat->getText(), $categoriesToIgnore ) ) {
				continue;
			}
			$dbr = wfGetDB( DB_SLAVE );
			$id = $dbr->selectField(
				array( 'categorylinks', 'page' ),
				array( 'cl_from' ),
				array(
					'cl_to' => $cat->getDBKey(),
					'page_id = cl_from',
					'page_namespace' => NS_MAIN,
					'page_is_redirect' => 0
				),
				__METHOD__,
				array( 'ORDER BY' => 'RAND()', 'LIMIT' => 1 )
			);

			if ( !$id ) {
				$errorMessage = wfMessage( 'addrelatedlinks-error', $t->getText() )->parse();
				$wgOut->addHTML( "<li>$errorMessage</li>\n" );
				continue;
			}

			$src = Title::newFromID( $id );
			MarkRelated::addRelated( $src, $t, $summary, true, $linktext );
			$found = true;
			return $src;
		}

		return null;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgUser, $wgOut, $wgServer;

		wfProfileIn( __METHOD__ );

		// Check permissions
		if ( !$wgUser->isAllowed( 'addrelatedlinks' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// If the user is blocked, they don't need to access this page
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Perform a few basic checks before anything else
		if ( !class_exists( 'LSearch' ) ) {
			$wgOut->addWikiMsg( 'addrelatedlinks-error-no-lsearch' );
			return;
		}

		if ( !class_exists( 'MarkRelated' ) ) {
			$wgOut->addWikiMsg( 'addrelatedlinks-error-no-nab' );
			return;
		}

		// Output the form
		$action = $this->getTitle()->getFullURL();
		$instructions = wfMsg( 'addrelatedlinks-instructions' );
		$submit = wfMsg( 'addrelatedlinks-submit' );
		$wgOut->addHTML(<<<END
			<form action="{$action}" method="post" enctype="multipart/form-data">
				{$instructions} <textarea name="xml"></textarea>
				<input type="submit" value="{$submit}" />
			</form>
END
		);

		if ( !$wgRequest->wasPosted() ) {
			wfProfileOut( __METHOD__ );
			return;
		}

		// We need a higher time limit than the default because this can be
		// a time-consuming operation
		set_time_limit( 3000 );

		$dbr = wfGetDB( DB_SLAVE );
		$urls = array_unique( explode( "\n", $wgRequest->getVal( 'xml' ) ) );

		// Switch the user
		$oldUser = $wgUser;
		$wgUser = User::newFromName( 'Wendy Weaver' );

		// Start doing stuff!
		$wgOut->addHTML( wfMsg( 'addrelatedlinks-started', date( 'r' ) ) . '<ul>' );
		foreach ( $urls as $url ) {
			$url = trim( $url );
			if ( $url == '' ) {
				continue;
			}
			$url = preg_replace( "@$wgServer/@im", '', $url );
			$t = Title::newFromURL( $url );
			if ( !$t ) {
				$wgOut->addHTML( '<li>' . wfMsg( 'addrelatedlinks-error-title', $url ) . "</li>\n" );
				continue;
			}
			$r = Revision::newFromTitle( $t );
			if ( !$r ) {
				$wgOut->addHTML( '<li>' . wfMsg( 'addrelatedlinks-error-revision', $url ) . "</li>\n" );
				continue;
			}
			$text = $r->getText();
			$search = new LSearch();
			$results = $search->googleSearchResultTitles( $t->getText(), 0, 30, 7 );
			$good = array();
			foreach ( $results as $r ) {
				if ( $r->getText() == $t->getText() ) {
					continue;
				}
				if ( $r->getNamespace() != NS_MAIN ) {
					continue;
				}
				if ( preg_match( "@\[\[{$t->getText()}@", $text ) ) {
					continue;
				}
				$good[] = $r;
				if ( sizeof( $good ) >= 4 ) {
					break;
				}
			}

			if ( sizeof( $good ) == 0 ) {
				$src = self::addLinkToRandomArticleInSameCategory( $t );
				if ( $src ) {
					$wgOut->addHTML(
						'<li>' . wfMsg(
							'addrelatedlinks-linked-random',
							$src->getFullURL(), $src->getText(),
							$t->getFullURL(), $t->getText()
						) . "</li>\n"
					);
				} else {
					$wgOut->addHTML(
						'<li>' . wfMsg(
							'addrelatedlinks-error-no-appropriate-links',
							$t->getFullURL(), $t->getText()
						) . "</li>\n"
					);
				}
			} else {
				$x = rand( 0, min( 4, sizeof( $good ) - 1 ) );
				$src = $good[$x];
				$wgOut->addHTML(
					'<li>' . wfMsg(
						'addrelatedlinks-linked-search',
						$src->getFullURL(), $src->getText(),
						$t->getFullURL(), $t->getText()
					) . "</li>\n"
				);
				$editSummary = wfMessage( 'addrelatedlinks-edit-summary' )->inContentLanguage()->text();
				MarkRelated::addRelated( $src, $t, $editSummary, true );
			}
		}

		// Restore the user
		$wgUser = $oldUser;

		$wgOut->addHTML( '</ul>' . wfMsg( 'addrelatedlinks-finished', date( 'r' ) ) );
		wfProfileOut( __METHOD__ );
		return;
	}
}

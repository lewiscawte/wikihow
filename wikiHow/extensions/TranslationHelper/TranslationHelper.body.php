<?php
/**
 * @file
 * @ingroup Extensions
 */
class TranslationHelper extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'TranslationHelper' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgDBname, $wgOut, $wgUser, $wgTranslationHelper;

		// @todo FIXME: this method (getExtensionMessagesFor) hasn't existed
		// since Tim Starling's i18n system refactor, which was in
		// r52503 (28 June 2009)
		$source = MessageCache::singleton()->getExtensionMessagesFor(
			$wgTranslationHelper['sourceLang']
		);
		$sk = $wgUser->getSkin();
		$local = array();

		foreach ( $source as $key => $value ) {
			if ( !isset( $local[$key] ) || $source[$key] == $local[$key] ) {
				$diff[$key] = $value;
			}
		}

		$wgOut->addHTML(
			'<h2>' . wfMsg( 'translationhelper-extension-messages' ) . '</h2>'
		);

		$wgOut->addHTML( '<ol>' );
		$diff = array_diff( $source, $local );

		foreach ( $diff as $key => $value ) {
			$t = Title::makeTitle( NS_MEDIAWIKI, $key );
			$wgOut->addHTML( $this->formatRow( $sk, $t ) );
		}
		$wgOut->addHTML( '</ol>' );

		$wgOut->addHTML(
			'<h2>' . wfMsg( 'translationhelper-mediawiki-messages' ) . '</h2>'
		);
		$wgOut->addHTML( '<ol>' );

		$sql = "SELECT p1.page_title AS page_title,
				p1.page_namespace AS page_namespace,
				p1.page_touched AS page_touched,
				p2.page_title AS local_page_title
			FROM {$wgTranslationHelper['sourceDBName']}.page p1
			LEFT OUTER JOIN $wgDBname.page p2
				ON p1.page_title = p2.page_title AND p1.page_namespace=p2.page_namespace
			WHERE p1.page_namespace = 8
			AND (p2.page_touched IS NULL OR p1.page_touched > p2.page_touched)
			ORDER BY p1.page_touched";
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->query( $sql, __METHOD__ );
		foreach( $res as $row ) {
			$t = Title::makeTitle(
				$row->page_namespace,
				$row->page_title
			);
			$wgOut->addHTML( $this->formatRow( $sk, $t, true ) );
		}
		$wgOut->addHTML( '</ol>' );
	}

	/**
	 * Format a result row.
	 *
	 * @param $sk Object: Skin object or descendant class
	 * @param $t Object: Title object pointing to the desired MediaWiki message
	 * @param $raw Boolean: append action=raw to the URL?
	 * @return HTML
	 */
	function formatRow( $sk, $t, $raw = false ) {
		global $wgTranslationHelper;
		$link = str_replace(
			$wgTranslationHelper['localDomain'],
			$wgTranslationHelper['sourceDomain'],
			$t->getFullURL()
		) . ( $raw ? '?action=raw' : '' );
		$longLink = Linker::linkKnown(
			$t,
			$t->getFullText(),
			array(),
			$sk->editUrlOptions(),
			array( 'target' => 'new' )
		);
		return "<li>{$longLink} - <a href=\"$link\" target=\"new\">" .
			wfMsg( 'translationhelper-view' ) . "</a></li>\n";
	}
}

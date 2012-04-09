<?php

class ArticleStats extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ArticleStats' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgLang, $wgScriptPath, $wgParser;

		$target = $par != '' ? $par : $wgRequest->getVal( 'target' );

		if( $target == '' ) {
			$wgOut->addHTML( wfMsg( 'articlestats-notitle' ) );
			return;
		}

		$t = Title::newFromText( $target );
		$id = $t->getArticleID();
		if( $id == 0 ) {
			$wgOut->addHTML( wfMsg( 'articlestats-nosucharticle' ) );
			return;
		}

		$dbr = wfGetDB( DB_SLAVE );

		$related = $dbr->selectField(
			'pagelinks',
			'COUNT(*)',
			array( 'pl_from' => $id ),
			__METHOD__
		);
		$inbound = $dbr->selectField(
			array( 'pagelinks', 'page' ),
			'COUNT(*)',
			array(
				'pl_namespace' => $t->getNamespace(),
				'pl_title' => $t->getDBkey(),
				'page_id = pl_from',
				'page_namespace' => 0
			),
			__METHOD__
		);

		$sources = $dbr->selectField(
			'externallinks',
			'COUNT(*)',
			array( 'el_from' => $t->getArticleID() ),
			__METHOD__
		);

		$langlinks = $dbr->selectField(
			'langlinks',
			'COUNT(*)',
			array( 'll_from' => $t->getArticleID() ),
			__METHOD__
		);

		// talk page
		$f = Title::newFromText( 'Featured', NS_TEMPLATE );

		$tp = $t->getTalkPage();
		$featured = $dbr->selectField(
			'templatelinks',
			'COUNT(*)',
			array(
				'tl_from' => $tp->getArticleID(),
				'tl_namespace' => 10,
				'tl_title' => 'Featured',
			),
			__METHOD__
		);
		$fadate = '';
		if( $featured > 0 ) {
			$rev = Revision::newFromTitle( $tp );
			$text = $rev->getText();
			$matches = array();
			preg_match( '/{{Featured.*}}/', $text, $matches );
			$fadate = $matches[0];
			$fadate = str_replace( '{{Featured|', '', $fadate );
			$fadate = str_replace( '}}', '', $fadate );
			$fadate = "($fadate)";
			$featured = wfMsg( 'articlestats-yes' );
		} else {
			$featured = wfMsg( 'articlestats-no' );
		}

		$rev = Revision::newFromTitle( $t );
		$section = $wgParser->getSection( $rev->getText(), 0 );
		$fileNamespaceName = $wgLang->getNsText( NS_FILE );
		if( preg_match( '/\[\[' . $fileNamespaceName . ':/', $section ) == 1 ) {
			$intro_photo = wfMsg( 'articlestats-yes' );
		} else {
			$intro_photo = wfMsg( 'articlestats-no' );
		}

		$section = $wgParser->getSection( $rev->getText(), 1 );
		preg_match( "/==[ ]*" . wfMsg( 'steps' ) . '/', $section, $matches, PREG_OFFSET_CAPTURE );
		if ( sizeof( $matches ) == 0 || $matches[0][1] != 0 ) {
			$section = $wgParser->getSection( $rev->getText(), 2 );
		}

		$num_steps = preg_match_all( '/^#/im', $section, $matches );
		$num_step_photos = preg_match_all( '/\[\[' . $fileNamespaceName . ':/', $section, $matches );
		$has_stepbystep_photos = wfMsg( 'articlestats-no' );
		if ( $num_steps > 0 ) {
			$has_stepbystep_photos = ( $num_step_photos / $num_steps ) > 0.5 ? wfMsg( 'articlestats-yes' ) : wfMsg( 'articlestats-no' );
		}

		$linkshere = SpecialPage::getTitleFor( 'Whatlinkshere' );
		$linksherelink = Linker::link( $linkshere, $inbound, array( 'target' => $t->getPrefixedURL() ) );
		$articlelink = Linker::link( $t, wfMsg( 'howto', $t->getFullText() ) );

		$numVotes = $dbr->selectField(
			'rating',
			'COUNT(*)',
			array(
				'rat_page' => $t->getArticleID(),
				'rat_isdeleted = 0'
			),
			__METHOD__
		);
		$rating = $dbr->selectField(
			'rating',
			'AVG(rat_rating)',
			array(
				'rat_page' => $t->getArticleID(),
				'rat_isdeleted' => 0,
			),
			__METHOD__
		);
		$unique = $dbr->selectField(
			'rating',
			'COUNT(DISTINCT(rat_user_text))',
			array(
				'rat_page' => $t->getArticleID(),
				'rat_isdeleted = 0'
			),
			__METHOD__
		);
		$rating = $wgLang->formatNum( $rating * 100 );

		$a = new Article( $t, 0 /* oldid */ );
		$count = $a->getCount();
		$pageViews = $wgLang->formatNum( $count );

		/*
		$max = $dbr->selectField(
			'google_indexed',
			'MAX(gi_timestamp)',
			array(
				'gi_page' => $t->getArticleID(),
				'gi_timestamp > "2007-10-12 14:06:58"'
			),
			__METHOD__
		);
		$index = -1;
		if( $max != '' ) {
			$index = $dbr->selectField(
				'google_indexed',
				'gi_position',
				array(
					'gi_page' => $t->getArticleID(),
					'gi_timestamp' => $max
				),
				__METHOD__
			);
		}
		*/
		$imagePath = $wgScriptPath . '/extensions/ArticleStats/images';
		// Default for accuracy is grey ball = not enough votes to determine
		// the accuracy of the article
		$accuracy = '<img src="' . $imagePath . '/grey_ball.png">&nbsp; &nbsp;' .
			wfMsg( 'articlestats-notenoughvotes' );
		if ( $numVotes >= 5 ) {
			if( $rating > 70 ) {
				$accuracy = '<img src="' . $imagePath . '/green_ball.png" alt="" />';
			} elseif( $rating > 40 ) {
				$accuracy = '<img src="' . $imagePath . '/yellow_ball.png" alt="" />';
			} else {
				$accuracy = '<img src="' . $imagePath . '/red_ball.png" alt="" />';
			}
			$accuracy .= '&nbsp; &nbsp;' . wfMsg( 'articlestats-rating', $rating, $numVotes, $unique );
		}

		if( $index > 10 || $index == 0 ) {
			$index = wfMsg( 'articlestats-notintopten', wfMsg( 'howto', urlencode( $t->getText() ) ) );
			$index .= '<br />' . wfMsg( 'articlestats-lastchecked', substr( $max, 0, 10 ) );
		} elseif( $index < 0 ) {
			$index = wfMsg( 'articlestats-notcheckedyet', wfMsg( 'howto', urlencode( $t->getText() ) ) );
		} else {
			$index = wfMsg( 'articlestats-indexrank', wfMsg( 'howto', urlencode( $t->getText() ) ), $index );
			$index .= wfMsg( 'articlestats-lastchecked', substr( $max, 0, 10 ) );
		}

		$cl = SpecialPage::getTitleFor( 'ClearRatings', $t->getText() );

		$wgOut->addHTML('
		<p>' . $articlelink . '<br />
		<table border="0" cellpadding="5">
			<tr>
				<td width="350px" valign="middle">'
				. wfMsgExt( 'articlestats-accuracy', 'parseinline', $cl->getFullText() ) . ' </td>
				<td valign="middle">' . $accuracy . '<br /></td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-hasphotoinintro', 'parseinline' ) . '</td>
				<td>' . $intro_photo . ' </td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-stepbystepphotos', 'parseinline' ) .'</td>
				<td>' . $has_stepbystep_photos . ' </td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-isfeatured', 'parseinline' ) . '</td>
				<td>' . $featured . $fadate . '</td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-numinboundlinks', 'parseinline' ) . '</td>
				<td>' .  $linksherelink . '</td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-outboundlinks', 'parseinline' ) . '</td>
				<td>' . $related . '</td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-sources', 'parseinline' ) . '</td>
				<td>' . $sources . '</td>
			</tr>
			<tr>
				<td>' . wfMsgExt( 'articlestats-langlinks', 'parseinline' ) . '</td>
				<td>' . $langlinks . '</td>
			</tr>
	 	</table>
		</p> ' . wfMsgExt( 'articlestats-footer', 'parseinline' )
		);
	}
}

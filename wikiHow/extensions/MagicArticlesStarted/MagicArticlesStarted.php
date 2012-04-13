<?php
/**
 * Adds five new magic words:
 * -{{ARTICLESSTARTED}}, to show how many articles the user has created
 * -{{PATROLCOUNT}}, to show how many edits the user has patrolled
 * -{{NABCOUNT}}, to show how many articles the user has boosted via the
 * New Article Boost extension
 * -{{VIEWERSHIP}}, to show how many times articles created by the user have
 * been viewed; and finally,
 * -{{NUMBEROFARTICLESSTARTED}}
 *
 * These magic words won't work outside the user namespace.
 *
 * Also note that this extension depends on the "firstedit" database table.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This requires the MediaWiki environment.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'MagicArticlesStartedMagicWords',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Adds <nowiki>{{ARTICLESSTARTED}}</nowiki>, ' .
		'<nowiki>{{PATROLCOUNT}}</nowiki> and <nowiki>{{NABCOUNT}}</nowiki> ' .
		'magic words for showing articles created by user, articles patrolled ' .
		'by user and articles boosted by user, respectively',
);

// Internationalization messages
$wgExtensionMessagesFiles['MagicArticlesStarted'] = dirname( __FILE__ ) . '/MagicArticlesStarted.i18n.php';

$wgHooks['ParserFirstCallInit'][] = 'wfWikiHowParserFunction_Setup';

$wgHooks['MagicWordMagicWords'][] = 'MagicArticlesStartedMagicWords';
$wgHooks['MagicWordwgVariableIDs'][] = 'MagicArticlesStartedwgVariableIDs';
$wgHooks['LanguageGetMagic'][] = 'MagicArticlesStartedLanguageGetMagic';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfWikiHowMagicAssignAValue';
#$wgHooks['ParserGetVariableValueSwitch'][] = 'MagicArticlesStartedGetVariableValue';

function wfWikiHowParserFunction_Setup( &$parser ) {
	$parser->setFunctionHook( 'ARTICLESSTARTED', 'articlesstarted' );
	$parser->setFunctionHook( 'PATROLCOUNT', 'patrolcount' );
	$parser->setFunctionHook( 'NABCOUNT', 'nabcount' );
	return true;
}

function MagicArticlesStartedMagicWords( &$magicWords ) {
	$magicWords[] = 'ARTICLESSTARTED';
	$magicWords[] = 'PATROLCOUNT';
	$magicWords[] = 'NABCOUNT';
	$magicWords[] = 'VIEWERSHIP';
	$magicWords[] = 'NUMBEROFARTICLESSTARTED';
	return true;
}

function MagicArticlesStartedwgVariableIDs( &$variableIDs ) {
	$wgVariableIDs[] = ARTICLESSTARTED;
	$wgVariableIDs[] = PATROLCOUNT;
	$wgVariableIDs[] = NABCOUNT;
	$wgVariableIDs[] = VIEWERSHIP;
	$wgVariableIDs[] = NUMBEROFARTICLESSTARTED;
	return true;
}

function MagicArticlesStartedLanguageGetMagic( &$magicWords, $langCode ) {
	switch( $langCode ) {
		default:
			#$magicWords[MAG_ARTICLESSTARTED] = array( 0, 'ARTICLESSTARTED' );
			$magicWords['ARTICLESSTARTED'] 	= array( 0, 'ARTICLESSTARTED' );
			$magicWords['PATROLCOUNT'] 		= array( 0, 'PATROLCOUNT' );
			$magicWords['NABCOUNT'] 		= array( 0, 'NABCOUNT' );
			$magicWords['VIEWERSHIP'] 		= array( 0, 'VIEWERSHIP' );
			$magicWords['NUMBEROFARTICLESSTARTED'] = array( 0, 'NUMBEROFARTICLESSTARTED' );
	}
	return true;
}

function articlesstarted( $parser, $part1 = '', $part2 = '', $part3 = 'time', $part4 = '', $part5 = 'width:200px;border: 1px solid #ccc; padding:10px;' ) {
	global $wgTitle;
	$ret = '';
	if ( $wgTitle->getNamespace() == NS_USER ) {
		$ret = '';
		$msg = '';
		if ( $part2 == 'box' ) {
			if ( $part1 == '0' ) {
				$msg = wfMsg( 'articlesstarted-by-me' );
			} else {
				switch ( $part3 ) {
					case 'popular':
						$msg = wfMsg( 'articlesstarted-by-me-most-popular', $part1 );
						break;
					case 'time_asc':
						$msg = wfMsg( 'articlesstarted-by-me-first', $part1 );
						break;
					default:
						$msg = wfMsg( 'articlesstarted-by-me-most-recent', $part1 );
				}
			}
			if ( $part4 != '' ) {
				$msg = $part4;
			}
			$ret = "<div style=\"$part5\">$msg<br />\n";
		}
		$dbr = wfGetDB( DB_SLAVE );
		$order = array();
		switch ( $part3 ) {
			case 'popular':
				$order['ORDER BY'] = 'page_counter DESC ';
				break;
			case 'time_asc':
				$order['ORDER BY'] = 'fe_timestamp ASC';
				break;
			default:
				$order['ORDER BY'] = 'fe_timestamp DESC';
		}
		if ( intval( $part1 ) || $part1 != '0' ) {
			if ( $part1 > PHP_INT_MAX ) {
				$part1 = PHP_INT_MAX;
			}
			$order['LIMIT'] = $part1;
		}
		$res = $dbr->select(
			array( 'firstedit', 'page' ),
			array( 'page_title', 'page_namespace', 'fe_timestamp' ),
			array( 'fe_page = page_id', 'fe_user_text' => $wgTitle->getText() ),
			__FUNCTION__,
			$order
		);
		foreach ( $res as $row ) {
			$t = Title::makeTitle( $row->page_namespace, $row->page_title );
			$ret .= '# [[' . $t->getFullText() . "]]\n";
		}

		if ( $part2 == 'box' ) {
			$ret .= '</div>';
		}
	}
	return $ret;
}

function patrolcount( $parser, $date1 = '', $date2 = '' ) {
	global $wgLang, $wgTitle;

	$ret = '';

	if ( $wgTitle->getNamespace() == NS_USER ) {
		$fdate1 = $fdate2 = '';
		$u = User::newFromName( $wgTitle->getText() );
		if ( !$u || $u->getID() == 0 ) {
			$ret = wfMsgHtml( 'articlesstarted-no-such-user', $wgTitle->getText() );
			return;
		}
		$options = array(
			'log_user = ' . $u->getID(),
			'log_type' => 'patrol'
		);

		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace( '-', '', $date1 );
		$date2 = str_replace( '-', '', $date2 );
		if ( $date1 != '' ) {
			$options[] = "log_timestamp > '{$date1}000000'";
		}
		if ( $date2 != '' ) {
			$options[] = "log_timestamp < '{$date2}235959'";
		}

		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField(
			'logging',
			'COUNT(*)',
			$options,
			__FUNCTION__
		);
		$count = $wgLang->formatNum( $count );

		$ret = $count;
	}

	return $ret;
}

function nabcount( $parser, $date1 = '', $date2 = '' ) {
	global $wgLang, $wgTitle;

	$ret = '';

	if ( $wgTitle->getNamespace() == NS_USER ) {
		$fdate1 = $fdate2 = '';
		$u = User::newFromName( $wgTitle->getText() );
		if ( !$u || $u->getID() == 0 ) {
			$ret = wfMsgHtml( 'articlesstarted-no-such-user', $wgTitle->getText() );
			return;
		}
		$options = array(
			'log_user = ' . $u->getID(),
			'log_type' => 'nap'
		);

		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace( '-', '', $date1 );
		$date2 = str_replace( '-', '', $date2 );
		if ( $date1 != '' ) {
			$options[] = "log_timestamp > '{$date1}000000'";
		}
		if ( $date2 != '' ) {
			$options[] = "log_timestamp < '{$date2}235959'";
		}

		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField(
			'logging',
			'COUNT(*)',
			$options,
			__FUNCTION__
		);
		$count = $wgLang->formatNum( $count );

		$ret = $count;
	}

	return $ret;
}

function wfWikiHowMagicAssignAValue( &$parser, &$cache, &$magicWordId, &$ret ) {
	global $wgLang, $wgTitle;

	if ( $magicWordId == VIEWERSHIP ) {
		if ( !$wgTitle ) {
			return true;
		}
		$dbr = wfGetDB( DB_SLAVE );
		$u = User::newFromName( $wgTitle->getText() );
		if ( !$u || $u->getID() == 0 ) {
			$ret = wfMsgHtml( 'articlesstarted-no-such-user', $wgTitle->getText() );
			return true;
		}
		$options = array(
			'fe_user = ' . $u->getID(),
			'page_id = fe_page'
		);
		$count = $dbr->selectField(
			array( 'page', 'firstedit' ),
			'SUM(page_counter)',
			$options,
			__FUNCTION__
		);
		$ret = $wgLang->formatNum( $count );
		return true;
	} elseif ( $magicWordId == NUMBEROFARTICLESSTARTED ) {
		if ( !$wgTitle ) {
			return true;
		}
		$dbr = wfGetDB( DB_SLAVE );
		$u = User::newFromName( $wgTitle->getText() );
		if ( !$u || $u->getID() == 0 ) {
			$ret = wfMsgHtml( 'articlesstarted-no-such-user', $wgTitle->getText() );
			return true;
		}
		$options = array(
			'fe_user = ' . $u->getID(),
			'page_id = fe_page'
		);
		$count = $dbr->selectField(
			array( 'page', 'firstedit' ),
			'COUNT(*)',
			$options,
			__FUNCTION__
		);
		$ret = $wgLang->formatNum( $count );
		return true;
	}

	return false;
}

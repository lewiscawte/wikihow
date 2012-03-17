<?php
if (!defined('MEDIAWIKI')) {
	die("This requires the MediaWiki enviroment.");
}

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'MagicArticlesStartedMagicWords',
	'author' => 'Travis Derouin',
	'description' =>  'Adds ARTICLESSTARTED magic word for showing articles created by user.',
);

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfWikiHowParserFunction_Setup';
} else {
	$wgExtensionFunctions[] = 'wfWikiHowParserFunction_Setup';
}

$wgHooks['MagicWordMagicWords'][] = 'MagicArticlesStartedMagicWords';
$wgHooks['MagicWordwgVariableIDs'][] = 'MagicArticlesStartedwgVariableIDs';
$wgHooks['LanguageGetMagic'][] = 'MagicArticlesStartedLanguageGetMagic';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfWikiHowMagicAssignAValue';
#$wgHooks['ParserGetVariableValueSwitch'][] = "MagicArticlesStartedGetVariableValue";

function wfWikiHowParserFunction_Setup() {
	global $wgParser;
	$wgParser->setFunctionHook( 'ARTICLESSTARTED', 'articlesstarted');
	$wgParser->setFunctionHook( 'PATROLCOUNT', 'patrolcount');
	$wgParser->setFunctionHook( 'NABCOUNT', 'nabcount');
	return true;
}

function MagicArticlesStartedMagicWords(&$magicWords) {
	$magicWords[] = 'ARTICLESSTARTED';
	$magicWords[] = 'PATROLCOUNT';
	$magicWords[] = 'NABCOUNT';
	$magicWords[] = 'VIEWERSHIP';
	$magicWords[] = 'NUMBEROFARTICLESSTARTED';
	return true;
}

function MagicArticlesStartedwgVariableIDs(&$wgVariableIDs) {
	$wgVariableIDs[] = ARTICLESSTARTED;
	$wgVariableIDs[] = PATROLCOUNT;
	$wgVariableIDs[] = NABCOUNT;
	$wgVariableIDs[] = VIEWERSHIP;
	$wgVariableIDs[] = NUMBEROFARTICLESSTARTED;
	return true;
}

function MagicArticlesStartedLanguageGetMagic(&$magicWords, $langCode) {
	switch($langCode) {
		default:
			#$magicWords[MAG_ARTICLESSTARTED] = array( 0, 'ARTICLESSTARTED' );
			$magicWords['ARTICLESSTARTED'] 	= array( 0, 'ARTICLESSTARTED' );
			$magicWords['PATROLCOUNT'] 		= array( 0, 'PATROLCOUNT' );
			$magicWords['NABCOUNT'] 		= array( 0, 'NABCOUNT' );
			$magicWords['VIEWERSHIP'] 		= array( 0, 'VIEWERSHIP' );
			$magicWords['NUMBEROFARTICLESSTARTED'] 		= array( 0, 'NUMBEROFARTICLESSTARTED' );
	}
	return true;
}

function articlesstarted($parser, $part1 = '', $part2 = '', $part3 = 'time', $part4 = '', $part5 = 'width:200px;border: 1px solid #ccc; padding:10px;') {
	global $wgTitle;
	$ret = "";
	if ($wgTitle->getNamespace() == NS_USER) {
		$ret = "";
		$msg = "";
		if ($part2 == 'box') {
			if ($part1 == '0') {
				$msg = wfMsg('articlesstarted_byme');
			} else {
				switch ($part3) {
					case 'popular': 
						$msg = wfMsg('articlesstarted_byme_mostpopular', $part1);
						break;
					case 'time_asc': 
						$msg = wfMsg('articlesstarted_byme_first', $part1);
						break;
					default:
						$msg = wfMsg('articlesstarted_byme_mostrecent', $part1);
				}
			}
			if ($part4 != '') $msg = $part4;
			$ret = "<div style='$part5'>$msg<br/>\n";
		}
		$dbr = wfGetDB(DB_SLAVE);
		$order = array();
		switch ($part3) {
			case 'popular':
				$order['ORDER BY'] = 'page_counter DESC ';
				break;
			case 'time_asc':
				$order['ORDER BY'] = 'fe_timestamp ASC ';
				break;
			default:
				$order['ORDER BY'] = 'fe_timestamp DESC ';
		}
		if ( intval( $part1 ) || $part1 != "0" ) {
			if ($part1 > PHP_INT_MAX) {
				$part1 = PHP_INT_MAX;
			}
			$order['LIMIT'] = $part1;
		}
		$res = $dbr->select(
			array('firstedit','page'),
			array ('page_title', 'page_namespace', 'fe_timestamp'),
			array ('fe_page=page_id', 'fe_user_text' => $wgTitle->getText()),
			__METHOD__,
			$order
		);
		while ($row=$dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			$ret .= "# [[" . $t->getFullText() . "]]\n";
		}
		$dbr->freeResult($res);
		if ($part2 == 'box') $ret .= "</div>";
	}
	return $ret;
}

function patrolcount($parser, $date1 = '', $date2  = '') {
	global $wgTitle;
	$ret = "";
	if ($wgTitle->getNamespace() == NS_USER) {
		$msg_key = $fdate1 = $fdate2 = '';
		$u = User::newFromName($wgTitle->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('patrolcount_error');
			return;
		}
		$options = array('log_user=' . $u->getID(), 'log_type' => 'patrol');
		
		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace("-", "", $date1);
		$date2 = str_replace("-", "", $date2);
		if ($date1 != "") $options[] = "log_timestamp > '{$date1}000000'";
		if ($date2 != "") $options[] = "log_timestamp < '{$date2}235959'";

		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField('logging', 'count(*)', $options, "patrolcount");
		$count = number_format($count, 0, "", ",");

		$ret = $count;
	}
	return $ret;
}

function nabcount($parser, $date1 = '', $date2  = '') {
	global $wgTitle;
	$ret = "";
	if ($wgTitle->getNamespace() == NS_USER) {
		$msg_key = $fdate1 = $fdate2 = '';
		$u = User::newFromName($wgTitle->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('nabcount_error');
			return;
		}
		$options = array('log_user=' . $u->getID(), 'log_type' => 'nap');
		
		$fdate1 = $date1;
		$fdate2 = $date2;
		$date1 = str_replace("-", "", $date1);
		$date2 = str_replace("-", "", $date2);
		if ($date1 != "") $options[] = "log_timestamp > '{$date1}000000'";
		if ($date2 != "") $options[] = "log_timestamp < '{$date2}235959'";

		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField('logging', 'count(*)', $options, "nabcount");
		$count = number_format($count, 0, "", ",");

		$ret = $count;
	}
	return $ret;
}

function wfWikiHowMagicAssignAValue(&$parser, &$cache, &$magicWordId, &$ret) {
	if (VIEWERSHIP == $magicWordId) {
		global $wgTitle;
		if (!$wgTitle) return true;
		$dbr = wfGetDB(DB_SLAVE);
		$u = User::newFromName($wgTitle->getText());
		if (!$u || $u->getID() == 0) {
			$ret = "No such user \"{$wgTitle->getText()}\"";
			return true;
		}
		$options = array('fe_user=' . $u->getID(), 'page_id=fe_page');
		$count = $dbr->selectField(array('page', 'firstedit'), 'sum(page_counter)', $options, "viewership");
		$ret = number_format($count, 0, "", ",");
		return true;
	} else  if (NUMBEROFARTICLESSTARTED == $magicWordId) {
		global $wgTitle;
		if (!$wgTitle) return true;
		$dbr = wfGetDB(DB_SLAVE);
		$u = User::newFromName($wgTitle->getText());
		if (!$u || $u->getID() == 0) {
			$ret = "No such user \"{$wgTitle->getText()}\"";
			return true;
		}
		$options = array('fe_user=' . $u->getID(), 'page_id=fe_page');
		$count = $dbr->selectField(array('page', 'firstedit'), 'count(*)', $options, "viewership");
		$ret = number_format($count, 0, "", ",");
		return true;
	}

	return false;
}


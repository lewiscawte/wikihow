<?php

/**
 * @addtogroup Maintenance
 */

/**
 * Updates the search index results meta data with entries that were
 * changed between the $start and $end timestamps.
 */
function updateSearchResultsSupplement($start, $end, $quiet) {
	global $wgQuiet, $wgDisableSearchUpdate, $wgBots;

	$wgQuiet = $quiet;
	$wgDisableSearchUpdate = false;

	$wgBots = User::getBotIDs();

	$dbw = wfGetDB(DB_MASTER);
	$dbr = wfGetDB(DB_SLAVE);

	output("Updating search index results between $start and $end\n");

	# Select entries from recentchanges which are on top and between the 
	# specified times
	$startOfTime = $start === 0;
	$start = $dbr->strencode($start);
	$end = $dbr->strencode($end);

	$ns_main = NS_MAIN;
	$ns_category = NS_CATEGORY;
	if ($startOfTime) {
		$sql = "
			SELECT page_id, page_namespace, page_title, page_counter,
			  page_touched, page_is_featured
			FROM page
			WHERE page_is_redirect = 0 AND
			  (page_namespace = $ns_main OR page_namespace = $ns_category) AND
			  page_touched BETWEEN '$start' AND '$end'
			";
	} else {
		$sql = "
			SELECT DISTINCT page_id, page_namespace, page_title, page_counter, 
			  page_touched, page_is_featured
			FROM recentchanges, page
			WHERE rc_timestamp BETWEEN '$start' AND '$end' AND
				rc_namespace = page_namespace AND
				rc_cur_id = page_id AND page_is_redirect = 0 AND 
				(page_namespace = $ns_main OR page_namespace = $ns_category)
			";
	}

	# Grab the results first
	$res = $dbr->query($sql, __METHOD__);
	$rows = array();
	while ( $row = $dbr->fetchRow($res) ) {
		$rows[] = $row;
	}

	$startProcessing = wfTimestampNow(TS_MW);

	# And do a search update
	foreach ($rows as $i => $row) {
		$success = addSearchResultsArticle($dbw, $dbr, $row, $startProcessing);
		$title = $row['page_title'];
		$id = $row['page_id'];
		$out = sprintf('%8d %s', $id, $title);
		if ($success) {
			output("$out\n");
		} else {
			output("$out (not found)\n");
		}
	}

	output("Done\n");
}

/*
 *schema:
 *
CREATE TABLE search_results (
	sr_id int unsigned not null,
	sr_namespace int unsigned not null,
	sr_title varchar(255) not null,
	sr_timestamp varchar(14) not null,
	sr_is_featured tinyint(1) unsigned not null,
	sr_has_video tinyint(1) unsigned not null,
	sr_steps tinyint(1) unsigned not null,
	sr_popularity int unsigned not null,
	sr_num_editors int unsigned not null,
	sr_first_editor varchar(255) not null,
	sr_last_editor varchar(255) not null,
	sr_img varchar(255) not null,
	sr_img_thumb_100 varchar(255) not null,
	sr_processed varchar(14) not null default '',
	primary key(sr_id),
	index(sr_title)
);
 *
 */

function addSearchResultsArticle(&$dbw, &$dbr, &$row, $startProcessing) {
	global $wgBots;

	$ns = $row['page_namespace'];
	$title = $row['page_title'];
	$titleObj = Title::newFromDBkey($title);
	if (!$titleObj) return false;

	if ($ns == NS_MAIN) {
		// Get current revision
		$rev = Revision::loadFromTitle($dbr, $titleObj);
		if (!$rev) return false;

		$revTitleObj = $rev->getTitle();
		$revTitle = $revTitleObj->getDBkey();
		$urlTitle = $revTitleObj->getPartialURL();
		$text = $rev->getText();
		$textEnc = $dbr->strencode($text);
		$titleEnc = $dbr->strencode($revTitle);
		//$urlTitleEnc = $dbr->strencode($urlTitle);
		$timestamp = wfTimestamp(TS_MW, $row['page_touched']);

		$sections = preg_split('@==\s*(\w+)\s*==@', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$intro = count($sections) > 0 ? $sections[0] : '';
		$stepsMsg = wfMsg('steps');
		while ($curr = next($sections)) {
			if ($curr == $stepsMsg) break;
		}
		$steps = next($sections);
		if (!$steps) $steps = '';

		if (preg_match('@^.*(\[\[Image:([^\|\]]*)(\|[^\]]*)\]\]).*$@Us', $intro, $m)) {
			$img = trim($m[2]);
		}
		$imgEnc = !empty($img) ? $dbr->strencode($img) : '';

		$thumbUrl100 = '';
		if ($imgEnc) {
			$imgTitle = Title::newFromText($img, NS_IMAGE);
			if ($imgTitle) {
				$file = wfFindFile($imgTitle);
				if ($file) {
					$thumb = $file->getThumbnail(100, 100, true);
					if ($thumb) {
						$thumbUrl100 = $thumb->getUrl();
					}
				}
			}
		}

		$stepsCount = preg_match_all('@^(\s*#\s*[^#*])@m', $steps, $m);
		$hasVideo = intval(preg_match('@{{video@i', $text) > 0);

		$conds = array();
		$conds[] = "rev_page = '{$row['page_id']}'";
		if (!empty($wgBots)) {
			$conds[] = "rev_user NOT IN (" . $dbr->makeList($wgBots) . ")";
		}
		$conds[] = 'rev_user > 0';

		$opts = array('ORDER BY' => 'rev_id');
		$firstEditor = $dbr->selectField('revision', 'rev_user_text', $conds, __METHOD__, $opts);

		$opts = array('ORDER BY' => 'rev_id DESC');
		$lastEditor = $dbr->selectField('revision', 'rev_user_text', $conds, __METHOD__, $opts);

		$opts['DISTINCT'] = true;
		unset($conds[ count($conds) - 1 ]); // remove last clause (anon users)
		$result = $dbr->select('revision', 'rev_user_text', $conds, __METHOD__, $opts);
		$numEditors = $result->numRows();

		$sql =
			"REPLACE INTO search_results SET sr_id='{$row['page_id']}',
				sr_namespace='{$row['page_namespace']}',
				sr_title='{$titleEnc}',
				sr_timestamp='{$timestamp}', 
				sr_processed='{$startProcessing}',
				sr_is_featured='{$row['page_is_featured']}',
				sr_has_video='{$hasVideo}',
				sr_steps='{$stepsCount}',
				sr_popularity='{$row['page_counter']}', 
				sr_num_editors='$numEditors',
				sr_first_editor=" . $dbw->addQuotes($firstEditor) . ",
				sr_last_editor=" . $dbw->addQuotes($lastEditor) . ",
				sr_img='{$imgEnc}',
				sr_img_thumb_100='{$thumbUrl100}'
			";
		$dbw->query($sql, __METHOD__);

		return true;
	} else { // NS_CATEGORY
		$titleEnc = $dbr->strencode( $titleObj->getDBkey() );
		$timestamp = wfTimestamp(TS_MW, $row['page_touched']);
		$sql =
			"REPLACE INTO search_results SET sr_id='{$row['page_id']}',
				sr_namespace='{$row['page_namespace']}',
				sr_title='{$titleEnc}',
				sr_timestamp='{$timestamp}', 
				sr_processed='{$startProcessing}',
				sr_is_featured='{$row['page_is_featured']}',
				sr_has_video=0,
				sr_steps=0,
				sr_popularity='{$row['page_counter']}', 
				sr_num_editors=0,
				sr_first_editor='',
				sr_last_editor='',
				sr_img='',
				sr_img_thumb_100=''
			";
		$dbw->query($sql, __METHOD__);

		return true;
	}
}

function output($text) {
	global $wgQuiet;
	if (!$wgQuiet) {
		print $text;
	}
}


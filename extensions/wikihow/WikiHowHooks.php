<?

function getSearchKeyStopWords() {
	global $wgMemc;

	$cacheKey = wfMemcKey('stop_words');
	$cacheResult = $wgMemc->get($cacheKey);
	if ($cacheResult) {
		return $cacheResult;
	}

	$sql = "SELECT stop_words FROM stop_words limit 1";
	$stop_words = null;
	$db = wfGetDB(DB_SLAVE);
	$res = $db->query($sql, __METHOD__);
	if ( $db->numRows($res) ) {
		while ( $row = $db->fetchObject($res) ) {
			$stop_words = split(", ", $row->stop_words);
		}
	}
	$db->freeResult( $res );

	$s_index = array();
	if (is_array($stop_words)) {
		foreach ($stop_words as $s) {
			$s_index[$s] = "1";
		}
	}

	$wgMemc->set($cacheKey, $s_index);

	return $s_index;
}

function generateSearchKey($text) {
	$stopWords = getSearchKeyStopWords();

	$text = strtolower($text);
	$tokens = split(' ', $text);
	$ok_words = array();
	foreach ($tokens as $t) {
		if ($t == '' || isset($stopWords[$t]) ) continue;
		$ok_words[] = $t;
	}
	sort($ok_words);
	$key = join(' ', $ok_words);
	$key = trim($key);

	return $key;
}

function updateSearchIndex($new, $old) {
	$dbw = wfGetDB(DB_MASTER);
	if ($new != null
		&& ($new->getNamespace() == 0
			|| $new->getNamespace() == 16) )
	{
		$dbw->delete( 'skey',
			array('skey_title' => $new->getDBKey(),
				  'skey_namespace' => $new->getNamespace()),
			__METHOD__ );
		$dbw->insert( 'skey',
			array('skey_title' => $new->getDBKey(),
				  'skey_namespace' => $new->getNamespace(),
				  'skey_key' => generateSearchKey($new->getText()) ),
			__METHOD__ );
	}

	if ($old != null) {
		$dbw->delete( 'skey',
			array('skey_title' => $old->getDBKey(),
				  'skey_namespace' => $old->getNamespace()),
			__METHOD__ );
	}
}

function wfMarkUndoneEditAsPatrolled() {
	global $wgRequest;
	if ($wgRequest->getVal('wpUndoEdit', null) != null) {
		$oldid = $wgRequest->getVal('wpUndoEdit');
		$dbr = wfGetDB(DB_MASTER);
		 $rcid = $dbr->selectField('recentchanges', 'rc_id', array("rc_this_oldid=$oldid") );
		RecentChange::markPatrolled($rcid);
		PatrolLog::record($rcid, false);
	}
	return true;
}

function wfTitleMoveComplete($title, &$newtitle, &$user, $oldid, $newid) {
	updateSearchIndex($title, $newtitle);
	return true;
}
$wgHooks['TitleMoveComplete'][] = array('wfTitleMoveComplete');

function wfArticleSaveComplete($article, $user, $p2, $p3, $p5, $p6, $p7) {
	global $wgMemc;

	if ($article) {
		updateSearchIndex($article->getTitle(), null);
	}
	wfMarkUndoneEditAsPatrolled();

	// In WikiHowSkin.php we cache the info for the author line. we want to
	// remove this if that article was edited so that old info isn't cached.
	if ($article && class_exists('SkinWikihowskin')) {
		$cachekey = SkinWikihowskin::getLoadAuthorsCachekey($article->getID());
		$wgMemc->delete($cachekey);
	}

	return true;
}
$wgHooks['ArticleSaveComplete'][] = array('wfArticleSaveComplete');

function wfImageConvert($cmd) {
	global $wgMemc;
	$key = wfMemcKey('imgconvert', md5($cmd));
	if ($wgMemc->get($key)) {
		return false;
	}
	$wgMemc->set($key, 1, 3600);
	return true;
}
$wgHooks['ImageConvert'][] = array('wfImageConvert');

function wfUpdateCatInfoMask(&$article, &$user) {
	if ($article) {
		$title = $article->getTitle();
		if ($title && $title->getNamespace() == NS_MAIN) {
			$mask = $title->getCategoryMask();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('page',
				array('page_catinfo' => $mask),
				array('page_id' => $article->getID()),
				__METHOD__);
		}
	}
	return true;
}
$wgHooks['ArticleSaveComplete'][] = array('wfUpdateCatInfoMask');

function wfUpdatePageFeaturedFurtherEditing($article, $user, $text, $summary, $flags) {
	if ($article) {
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
	}

	$templates = split("\n", wfMsgForContent('templates_further_editing'));
	$regexps = array();
	foreach ($templates as $template) {
		$template = trim($template);
		if ($template == "") continue;
		$regexps[] ='\{\{' . $template;
	}
	$re = "@" . implode("|", $regexps) . "@i";

	$updates = array();
	if (preg_match_all($re, $text, $matches)) {
		$updates['page_further_editing'] = 1;
	}
	else{
		$updates['page_further_editing'] = 0; //added this to remove the further_editing tag if its no longer needed
	}
	if (preg_match("@\{\{fa\}\}@i", $text)) {
		$updates['page_is_featured'] = 1;
	}
	if (sizeof($updates) > 0) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('page', $updates, array('page_id'=>$t->getArticleID()), __METHOD__);
	}
	return true;
}
$wgHooks['ArticleSaveComplete'][] = array('wfUpdatePageFeaturedFurtherEditing');

function wfSetPage404IfNotExists() {
	global $wgTitle, $wgOut;
	// Note: if namespace < 0, it's a virtual namespace like NS_SPECIAL
	if ($wgTitle && $wgTitle->getNamespace() >= 0 && !$wgTitle->exists()) {
		$wgOut->setStatusCode(404);
	}
	return true;
}
$wgHooks['OutputPageBeforeHTML'][] = array('wfSetPage404IfNotExists');

// implemented in ArticleMetaInfo.class.php
$wgHooks['ArticleSaveComplete'][] = array('ArticleMetaInfo::refreshMetaDataCallback');

function wfAddCacheControlHeaders() {
	global $wgTitle, $wgRequest;

	if ($wgRequest && $wgTitle && $wgTitle->getText() == wfMsg('mainpage')) {
		$wgRequest->response()->header("X-T:MP");
	}

	return true;
}
$wgHooks['AddCacheControlHeaders'][] = array('wfAddCacheControlHeaders');

// Add to the list of available JS vars on every page
function wfAddJSglobals(&$vars) {
	$vars['wgCDNbase'] = wfGetPad('');
	return true;
}
$wgHooks['MakeGlobalVariablesScript'][] = array('wfAddJSglobals');


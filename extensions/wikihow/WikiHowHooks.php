<?

function getSearchKeyStopWords() {
	global $wgMemc;

	$cacheKey = 'stop_words';
	$cacheResult = $wgMemc->get($cacheKey);
	if ($cacheResult) {
		return $cacheResult;
	}

	$sql = "SELECT stop_words FROM stop_words limit 1;";
	$stop_words = null;
	$db =& wfGetDB( DB_MASTER );
	$res = $db->query( $sql, 'WH generateSearchKey' );
	if ( $db->numRows( $res ) ) {
		while ( $row = $db->fetchObject( $res ) ) {
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
	#require_once("HttpClient.php");
	$dbw =& wfGetDB( DB_MASTER );
	if ($new != null && ($new->getNamespace() == 0 || $new->getNamespace() == 16) ) {
		$dbw->delete( 'skey', array( 'skey_title' => $new->getDBKey(), 'skey_namespace' => $new->getNamespace() ) );                
		$dbw->insert( 'skey', array( 'skey_title' => $new->getDBKey(), 'skey_namespace' => $new->getNamespace(), 
			'skey_key' => generateSearchKey($new->getText()) ) );
		#error_log("adding index for " . $new->getText() . ",ns:" . $new->getNamespace() . ", key:" . generateSearchKey($new->getText()) );
		#$pageContents = HttpClient::quickGet("http://dev-2.ehow.com:8080/update_index.jsp?url=" . urlencode($new->getPrefixedURL()) . "&action=u");

	}
	if ($old != null) {
		$dbw->delete( 'skey', array( 'skey_title' => $old->getDBKey(), 'skey_namespace' => $old->getNamespace() ) );                
		#error_log("removing index for " . $old->getText() . ",ns:" . $old->getNamespace() . ", key:" . generateSearchKey($new->getText()) );
		#$pageContents = HttpClient::quickGet("http://dev-2.ehow.com:8080/update_index.jsp?url=" . urlencode($old->getPrefixedURL()) . "&action=d");
	}
}
function wfTitleMoveComplete (&$title, &$newtitle, &$user, $oldid, $newid) {
	updateSearchIndex($title, $newtitle);
	return true;
}
function wfArticleSaveComplete ($p0, $p1, $p2, $p3, $p5, $p6, $p7) {
	updateSearchIndex($p1->mTitle, null);
	wfMarkUndoneEditAsPatrolled();
	return true;
}
function wfArticleDelete ($p0, $p1, $p2, $p3) {
	if ($p1)
		updateSearchIndex(null, $p1->mTitle);
	return true;
}
function wfMarkUndoneEditAsPatrolled() {
	global $wgRequest;
	if ($wgRequest->getVal('wpUndoEdit', null) != null) {
		 $oldid = $wgRequest->getVal('wpUndoEdit');
		$dbr = wfGetDB(DB_MASTER);
		 $rcid = $dbr->selectField('recentchanges', 'rc_id', array("rc_this_oldid=$oldid") );
		 RecentChange::markPatrolled( $rcid );
         PatrolLog::record( $rcid, false );
	}
	return true;
}


<?

class TitleSearch extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'TitleSearch' );
	}

	function matchKeyTitles($text, $limit = 10) {
		global $wgMemc;

		$gotit = array();
		$text = trim($text);

		if ($text == "") return $result;

		$key = generateSearchKey($text);

		$cacheKey = wfMemcKey('title_search:' . $limit . ':' . $key);
		$result = $wgMemc->get($cacheKey);
		if ($result !== null) {
			return $result;
		}

		$result = array();

		$base = "SELECT skey.skey_title, p1.page_counter, p1.page_len, p1.page_is_featured FROM skey  "
			. " LEFT JOIN page p1 ON skey_title = p1.page_title AND skey_namespace = p1.page_namespace WHERE "
			. " p1.page_is_redirect = 0 AND skey_namespace = 0  ";
		$sql = $base . " AND skey_key LIKE '%" . str_replace(" ", "%", $key) . "%' AND skey_namespace = 0  GROUP by p1.page_id ";
		$sql .= " LIMIT $limit;";
		$db =& wfGetDB( DB_MASTER );
		$res = $db->query( $sql, 'WH TitleSearch::matchKeyTitles1' );
		if ( $db->numRows( $res ) ) {
			while ( $row = $db->fetchObject( $res ) ) {
				$con = array();
				$con[0] = $row->skey_title;
				$con[1] = $row->page_counter;
				$con[2] = $row->page_len;
				$con[3] = $row->page_is_featured;
				$result[] = $con;
				$gotit[$row->skey_title] = 1;
			}
		}

		if (count($result) < $limit) {
			$sql = $base . " AND ( skey_key LIKE '%" . str_replace(" ", "%", $key) . "%' ";
			$ksplit = split(" ", $key);
			if (count($ksplit) > 1) {
				foreach ($ksplit as $k) {
					$sql .= " OR skey_key LIKE '%$k%'";
				}
			}
			$sql .= " ) ";
			$sql .= " LIMIT $limit;";
			$res = $db->query( $sql, 'WH TitleSearch::matchKeyTitles2' );
			while ( count($result) < $limit && $row = $db->fetchObject( $res ) ) {
				if (!isset($gotit[$row->skey_title]))  {
					$con = array();
					$con[0] = $row->skey_title;
					$con[1] = $row->page_counter;
					$con[2] = $row->page_len;
					$con[3] = $row->page_is_featured;
					$result[] = $con;
				}
			}
		}

		$wgMemc->set($cacheKey, $result);
		return $result;
	}

	function execute() {
		global $wgRequest, $wgOut, $wgLanguageCode;

		$t1 = time();
		$search = $wgRequest->getVal("qu");
		$limit = intval($wgRequest->getVal("lim", 10));

		if ($search == "") exit;

		$search = strtolower($search);
		$howto = strtolower(wfMsg('howto', ''));
		
		// hack for german site
		if ($wgLanguageCode != 'de') {
			if (strpos($search, $howto) === 0) {
				$search = substr($search, 6);
				$search = trim($search);
			}
		}

		$t = Title::newFromText($search, 0);
		if (!$t) {
			echo 'sendRPCDone(frameElement, "' . $search . '", new Array(""), new Array(""), new Array(""));';
			$wgOut->disable();
			return;
		}
		$dbkey = $t->getDBKey();

		// do a case insensitive search
		echo 'sendRPCDone(frameElement, "' . $search . '", new Array(';

		$array = "";
		$titles = $this->matchKeyTitles($search, $limit);
		foreach ($titles as $con) {
			$t = Title::newFromDBkey($con[0]);
			$array .= '"' . str_replace("\"", "\\\"", $t->getFullText()) . '", ' ;
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;

		echo '), new Array(';

		$array = "";
		foreach ($titles as $con) {
			$counter = number_format($con[1], 0, "", ",");
			$words = number_format( ceil($con[2]/5), 0, "", ",");
			$tl_from = $con[3];
			if ($tl_from)
				$array .=  "\"<img src='/skins/common/images/star.png' height='10' width='10'> $counter ". wfMsg('ts_views') . " $words " . wfMsg('ts_words') . "\", ";
			else
			$array .=  "\" $counter " . wfMsg('ts_views') . " $words " . wfMsg('ts_words') . "\", ";
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;
		echo '), new Array(""));';
		$wgOut->disable();
	}

}


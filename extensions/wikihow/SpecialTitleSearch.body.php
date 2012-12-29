<?
	
class TitleSearch extends UnlistedSpecialPage {
	
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'TitleSearch' );
	}
	
	function matchKeyTitles ($text, $limit = 10, $namspace=0) {
		$gotit = array();
		$result = array();
		$text = trim($text);
		if ($text == "") return $result;

		$key = generateSearchKey($text);
		$base = "SELECT skey.*, p1.page_counter, p1.page_len, p1.page_is_featured FROM skey  "
			. " LEFT JOIN page p1 ON skey_title = p1.page_title AND skey_namespace = p1.page_namespace WHERE "
			. " p1.page_is_redirect = 0 AND skey_namespace= 0  ";
		$sql = $base . " AND skey_key LIKE '%" . str_replace(" ", "%", $key) . "%' AND skey_namespace = 0  GROUP by p1.page_id ";
		$sql .= " LIMIT $limit;";
		$db =& wfGetDB( DB_MASTER );
		$res = $db->query( $sql, 'Title::matchKeyTitles' );
		if ( $db->numRows( $res ) ) {
			while ( $row = $db->fetchObject( $res ) ) {
				$con = array();
				$con[0] = Title::newFromDBkey($row->skey_title);
				$con[1] = $row->page_counter;
				$con[2] = $row->page_len;
				$con[3] = $row->page_is_featured;
				$result[] = $con;
				$gotit[$row->skey_title] = 1;
			}
		}
		
		$sql = $base . " and ( skey_key like '%" . str_replace(" ", "%", $key) . "%' ";
		$ksplit = split(" ", $key);
		foreach ($ksplit as $k) {
			$sql .= " OR skey_key LIKE '%$k%'"  ; // str_replace(" ", "%", $key) . "%' LIMIT $limit;";
		}
		$sql .= " ) ";
		//$sql .= " GROUP BY p1.page_id LIMIT $limit;";
		$sql .= " LIMIT $limit;";
        $res = $db->query( $sql, 'Title::matchKeyTitles' );
		while ( $row = $db->fetchObject( $res ) ) {
			if (!isset($gotit[$row->skey_title]))  {
				$con = array();
				$con[0] = Title::newFromDBkey($row->skey_title);
				$con[1] = $row->page_counter;
				$con[2] = $row->page_len;
				$con[3] = $row->page_is_featured;
				//$result[] = Title::newFromDBkey($row->skey_title);
				$result[] = $con;
			}
		}
		return $result;
	}
	
	function execute () {
		global $wgRequest, $wgOut;

		$t1 = time();    
		$search = $wgRequest->getVal("qu");
		$limit = intval($wgRequest->getVal("lim", 20));
		
		if ($search == "") exit;

		$search = strtolower($search);
		if (strpos($search, "how to") === 0) {
			$search = substr($search, 6);
			$search = trim($search);
		}
		$t = Title::newFromText($search, 0);
		if (!$t) {
			echo 'sendRPCDone(frameElement, "' . $search . '", new Array(""), new Array(""), new Array(""));';
			$wgOut->disable();
			return;
		}
		$dbkey = $t->getDBKey();

		// do a case insensitive search
		$sql = "SELECT cur_title FROM cur WHERE convert(cur_title using latin1) like '$dbkey%' AND cur_namespace = 0 AND cur_is_redirect = 0 LIMIT 10";
		
		echo 'sendRPCDone(frameElement, "' . $search . '", new Array(';
	  
		$array = "";
		/*
		$db =& wfGetDB( DB_SLAVE );
		$res = $db->query( $sql, 'wfSpecialTitleSearch' );
		if ( $db->numRows( $res ) ) {
			while ( $row = $db->fetchObject( $res ) ) {
				if ( $titleObj = Title::makeTitle( NS_MAIN, $row->cur_title ) ) {
					$array .= '"' . str_replace("\"", "\\\"", $titleObj->getFullText()) . '", ' ;                
				}
			}
		}
		$array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;
		$db->freeResult( $res );
		*/
		$titles = $this->matchKeyTitles($search, $limit / 2);
		foreach ($titles as $con ) {
			$t = $con[0];
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
				$array .=  "\"<img src='/skins/common/images/star.png' height='10' width='10'> $counter views $words words \", ";
			else 
			$array .=  "\" $counter views $words words\", ";
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;
		echo '), new Array(""));';    
		$wgOut->disable();
	}
	
} 

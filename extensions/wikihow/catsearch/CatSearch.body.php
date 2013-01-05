<?

class CatSearch extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'CatSearch' );
	}
	
	function execute($par) {
		global $wgOut, $wgRequest;

		$fname = 'CatSearch::execute';
		wfProfileIn( $fname );

		$retVal = false;
		if (intval($revOld) && intval($revNew) && intval($pageId)) {
			self::thumbMultiple($revOld, $revNew, $pageId);
			$retVal = true;
		}
		
		if ($q = $wgRequest->getVal('q')) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode($this->catSearch($q));
		}

		wfProfileOut( $fname );
	}

	function catSearch($q) {
		$dbr = wfGetDB(DB_SLAVE);
		$q = $dbr->strencode($q); 

        $l = new LSearch();
       	$results = $l->googleSearchResultTitles($search);
		var_dump($results);

/*
		$url = "http://www.wikihow.com/Special:LSearch?search=" . urlencode($title) . "&raw=true";
        $results = getResults($url);
 		$lines = split("\n", $results);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == "") continue;
            $t = Title::newFromURL(str_replace("http://www.wikihow.com/", "", urldecode($line)));
            if($t) {
                echo "Result: " . $t->getText() . "<br>";	
			}
		}
*/
	}

	function getResults($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $contents = curl_exec($ch);
        #var_dump(curl_getinfo($ch)); 
        #var_dump(curl_error($ch));
        if (curl_errno($ch)) {
            # error
            echo "curl error {$url}: " . curl_error($ch);
        } else {

        }
        curl_close($ch);
        return $contents;
    }
}

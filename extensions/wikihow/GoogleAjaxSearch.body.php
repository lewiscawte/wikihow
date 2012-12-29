<?

class GoogleAjaxSearch extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'GoogleAjaxSearch' );
    }

    function getGlobalWebResults($q, $limit = 8, $site='wikkihow.com') {
        global $wgGoogleAjaxKey, $wgGoogleAjaxSig;

        $q = urlencode($q);
        $results = array();
        $start = 0;
        while (sizeof($results) < $limit) {
            $url = "http://www.google.com/uds/GwebSearch?callback=google.search.WebSearch.RawCompletion&context=0&lstkp=0&rsz=large&"
                .  "hl=en&source=gsc&gss=.com&sig={$wgGoogleAjaxSig}&q={$q}";
            if ($site)
                $url .="%20site%3Awikihow.com";
            $url .= "&gl=www.google.com&qid=12521dc27f9815152&key={$wgGoogleAjaxKey}&v=1.0&start={$start}";
            #$contents = file_get_contents($url);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_REFERER, "http://www.wikihow.com/Special:LSearch");
			$body = curl_exec($ch);
			curl_close($ch);

			$body = str_replace("google.search.WebSearch.RawCompletion('0',", "", $body);
			$body = preg_replace("@, [0-9]*, [a-z]*, [0-9]*\)$@", "", $body);
			$rex = json_decode($body, true); 
			$results = array_merge($results, $rex['results']);
            if (sizeof($rex['results']) < 8)
                break;
            $start += sizeof($matches);
            if ($start >= $limit) break;
        }
        return $results;
    }

    function scrapeGoogle($q, $limit = 8, $site='wikkihow.com') {
		global $wgGoogleAjaxKey, $wgGoogleAjaxSig;

        $q = urlencode($q);
        #$url = "http://www.google.com/uds/GwebSearch?callback=google.search.WebSearch.RawCompletion&context=0&lstkp=0&rsz=large&hl=en&source=gsc&gss=.com&sig=c1cdabe026cbcfa0e7dc257594d6a01c&q={$q}%20site%3Awikihow.com&gl=www.google.com&qid=124c49541548cd45a&key=ABQIAAAAYdkMNf23adqRw4vVq1itihTad9mjNgCjlcUxzpdXoz7fpK-S6xTT265HnEaWJA-rzhdFvhMJanUKMA&v=1.0";

		$hash = array();
		$results = array();
		$start = 0;
		while (sizeof($results) < $limit) {
			$url = "http://www.google.com/uds/GwebSearch?callback=google.search.WebSearch.RawCompletion&context=0&lstkp=0&rsz=large&"
				.  "hl=en&source=gsc&gss=.com&sig={$wgGoogleAjaxSig}&q={$q}";
			if ($site)
				$url .="%20site%3Awikihow.com";
			$url .= "&gl=www.google.com&qid=12521dc27f9815152&key={$wgGoogleAjaxKey}&v=1.0&start={$start}";
	        $contents = file_get_contents($url);
#echo $contents; exit;
	        preg_match_all('@unescapedUrl":"([^"]*)"@u',$contents, $matches);
	        $ids = array();
	        foreach($matches[1] as $m) {
	            $m= str_replace('http://www.wikihow.com/', '', $m);
	            $r = Title::newFromURL($m);
	            if ($r =='') continue;
	            if (!$r) {
	                continue;
	            } else if ($r->getNamespace() != NS_MAIN) {
	                continue;
	            } else if ($r->getArticleID() > 0 && !isset($hash[$r->getArticleID()])) {
	                $results[] = $r;
					$hash[$r->getArticleID()] = 1; // include titles only once in results
	            }
        	}
			if (sizeof($matches[1]) < 8)
				break;
			$start += sizeof($matches);
			if ($start >= $limit) break;
		}
        return $results;
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;
			
		$q = $wgRequest->getVal('q');	
		$titles = $this->scrapeGoogle($q);
		if ($wgRequest->getVal('raw')) {
			$wgOut->disable();
			header("Content-type: text/plain;");
			foreach ($titles as $t) {
				echo $t->getFullURL() . "\n";
			}
			return;
		}
	}
}

<?
class CatSearch extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'CatSearch' );
	}
	
	function execute($par) {
		global $wgOut, $wgRequest, $wgUser;

		$fname = 'CatSearch::execute';
		wfProfileIn( $fname );

		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		if ($wgUser->getId() == 0) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
		}


		if ($q = $wgRequest->getVal('q')) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode(array("results" => $this->catSearch($q)));
		}

		wfProfileOut( $fname );
	}

	function catSearch($q) {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$prefix = "Category ";
		$query = $dbr->strencode($prefix . $q); 
		$suggestions = array();
		$count = 0;

		// Add an exact category match
		$t = Title::newFromText($q, NS_CATEGORY);
		if ($t && $t->exists() && !$this->ignoreCategory($t->getText())) {
			$suggestions[] = $t->getPartialUrl();
		}

        $l = new LSearch();
       	$results = $l->googleSearchResultTitles($query);
		foreach ($results as $t) {
			if (!$this->ignoreCategory($t->getText())) {
				if ($t->getNamespace() == NS_CATEGORY) {
					$suggestions[] = $t->getPartialUrl();
				}
				elseif ($t->getNameSpace() == NS_MAIN && $count < 3) {
					$count++;
					$suggestions = array_merge($suggestions, $this->getParentCats($t));
				}
			}
		}

		if(!sizeof($suggestions)) {
			$suggestions[] = "Sorry, nothing found. Try another search.";
		}

		$suggestions = array_values(array_unique($suggestions));
		// Return the top 15
		return array_slice($this->formatResults($suggestions), 0, 15);
	}

	function formatResults(&$suggestions) {
		$results = array();
		foreach ($suggestions as $suggestion) {
			$results[] = self::formatResult($suggestion);
		}
		return $results;
	}

	function formatResult($partialUrl) {
		// urldecode hack for Cars & Other Vehicles category
		$partialUrl = urldecode($partialUrl);

		$label = str_replace("-", " ", $partialUrl);
		$ret = array('label' => $label, 'url' => $partialUrl);
		return $ret;
	}

	function getParentCats(&$t) {
		$cats = str_replace("Category:", "", array_keys($t->getParentCategories()));
		foreach($cats as $key => $cat) {
			if (self::ignoreCategory($cat)) {
				unset($cats[$key]);
			}
		}
		return $cats;
	}

	function ignoreCategory($cat) {
		$cat = str_replace("-", " ", $cat);
		$ignoreCats = wfMsgForContent("categories_to_ignore");
		$ignoreCats = split("\n", $ignoreCats);
		$ignoreCats = str_replace("http://www.wikihow.com/Category:", "", $ignoreCats);
		$ignoreCats = str_replace("-", " ", $ignoreCats);
		return array_search($cat, $ignoreCats) !== false ? true : false || $cat == 'WikiHow' || $cat == 'Honors' || $cat == 'Answered Requests' || $cat == 'Patrolling';
	}
}

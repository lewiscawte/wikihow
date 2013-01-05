<?
class CatSearch extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'CatSearch' );
	}
	
	function execute($par) {
		global $wgOut, $wgRequest;

		$fname = 'CatSearch::execute';
		wfProfileIn( $fname );

		if ($q = $wgRequest->getVal('q')) {
			$wgOut->setArticleBodyOnly(true);
			//echo json_encode(array("results" => $this->catSearch($q)));
			echo $_GET['callback'] . '('.json_encode(array("results" => $this->catSearch($q))).')';
		}

		wfProfileOut( $fname );
	}

	function catSearch($q, $supplementalResults = false) {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$prefix = $supplementalResults ? "" : "Category ";
		$query = $dbr->strencode($prefix . $q); 
		$suggestions = array();
		$supplemental = array();
		$count = 0;

		// Add an exact category match
		$t = Title::newFromText($q, NS_CATEGORY);
		if ($t && $t->exists()) {
			$suggestions[] = $t->getPartialUrl();
		}

        $l = new LSearch();
       	$results = $l->googleSearchResultTitles($query);
		foreach ($results as $t) {
			if (!$this->ignoreCategory($t->getText())) {
				if ($t->getNamespace() == NS_CATEGORY) {
					$suggestions[] = $t->getPartialUrl();
				}
				elseif ($supplementalResults && $t->getNameSpace() == NS_MAIN && $count < 3) {
					$count++;
					$supplemental = array_merge($supplemental, $this->getSupplementalCats($t));
				}
			}
		}

		if (!sizeof($suggestions)) {
			if (!$supplementalResults) {
				$suggestions = $this->catSearch($q, true);
			} 
			else {
				$suggestions = $supplemental;
			}
			
			if(!sizeof($suggestions)) {
				$suggestions = array('label' => "Sorry, nothing found. Please try another interest", url => -1);
				return $suggestions;
			}
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

	function getSupplementalCats(&$t) {
		$cats = str_replace("Category:", "", array_keys($t->getParentCategories()));
		return $cats;
	}

	function ignoreCategory($cat) {
		$ignoreCats = wfMsgForContent("categories_to_ignore");
		$ignoreCats = split("\n", $ignoreCats);
		$ignoreCats = str_replace("http://www.wikihow.com/Category:", "", $ignoreCats);
		$ignoreCats = str_replace("-", " ", $ignoreCats);
		return array_search($cat, $ignoreCats) !== false ? true : false || $cat == 'WikiHow' || $cat == 'Honors';
	}
}

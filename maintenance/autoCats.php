<?
	require_once('commandLine.inc');
	$dbr = wfGetDB(DB_SLAVE);

	$res = $dbr->select('page', array('page_id'), array('page_namespace' => 0, 'page_is_redirect' => 0), 'maintenance/autoCats.php', array('LIMIT' => 10));
	while($row = $dbr->fetchObject($res)) {
		$t = Title::newFromId($row->page_id);
		catTest1($t->getText());
	}

	function catTest1($title) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		echo "Search: $title\n";
		//$ls = new LSearch();
		//$result = $ls->googleSearchResultTitles($title);

		$url = "http://www.wikihow.com/Special:LSearch?search=" . urlencode($title) . "&raw=true";
		$results = getResults($url); 
		$lines = split("\n", $results);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line == "") continue;

			$t = Title::newFromURL(str_replace("http://www.wikihow.com/", "", urldecode($line)));
			if($t && $t->getText() != $title) {
				echo "Result: " . $t->getText();
				$cats = $t->getParentCategoryTree();
				$cats = $sk->flattenCategoryTree($cats);
				$cats = $sk->cleanUpCategoryTree($cats);
				echo " == Categories: " . implode(",", $cats) . "\n";

				$suggestedCat = "";
				foreach ($cats as $cat) {
					if (!ignoreCategory($cat)) {
						$suggestedCat = $cat;
						break;
					}
				}
				if ($suggestedCat) {
					echo "suggested category: $cat\n";
					break;
				}
			}
		}
		echo "\n";
	}

	function ignoreCategory($cat) {
		$ignoreCats = wfMsgForContent("categories_to_ignore");
		$ignoreCats = split("\n", $ignoreCats);
		$ignoreCats = str_replace("http://www.wikihow.com/Category:", "", $ignoreCats);
		$ignoreCats = str_replace("-", " ", $ignoreCats);
		return array_search($cat, $ignoreCats) !== false ? true : false || $cat == 'WikiHow' || $cat == 'Honors';
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


<?
class OLPC extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'OLPC' );
    }



	function getCat($arr) {
		if (is_array($arr) && sizeof($arr) > 0) {
			foreach ($arr as $a => $b) {
				if (sizeof($b) == 0) 
					return $a;
				return OLPC::getCat($b);
			}
		}
		return $arr;
	}
	function loadOLPCategoryMap() {
		global $wgOLPCUrls, $wgListoftitles, $wgOLPCCategoryMap;
		if (!is_array($wgOLPCCategoryMap) || sizeof($wgOLPCCategoryMap) == 0) {
			$wgOLPCCategoryMap = array();
			foreach ($wgOLPCUrls as $dbkey => $val) {
				$t = Title::newFromDBKey($dbkey);
				$p = $t->getParentCategoryTree();
				//echo $t->getFullURL() . "," .  print_r($p, true);
				$topcat = OLPC::getCat($p);
				if (is_array($topcat)){
					//echo "warnining: bad cat for $dbkey\n";
					continue;
				}
				if (!isset($wgOLPCCategoryMap[$topcat]))
					$wgOLPCCategoryMap[$topcat] = array();
				$wgOLPCCategoryMap[$topcat][] = $t;
			}
		}
	}
	function loadOLPCUrls() {
			global $wgOLPCUrls, $wgListoftitles;
	        if (!is_array($wgOLPCUrls)) {
	            $wgOLPCUrls = array();
	            $urls = array();
	            $t = Title::newFromText($wgListoftitles);
	            $r = Revision::newFromTitle($t);
	            preg_match_all('/http[^ ]*/', $r->getText(), $matches);
	            foreach ($matches[0] as $url) {
	                if (strpos($url, $wgServer) !== 0) {
	                    $url = str_replace("http://www.wikihow.com", $wgServer, $url);
	                }
	                $url = str_replace('&action=history', '', $url);
	                $url = str_replace('/index.php?title=', '', $url);
	                $url = preg_replace('/&oldid=.*/', '', $url);
	                $t = Title::newFromURL(urldecode($url));
	                $wgOLPCUrls[$t->getDBKey()] = 1;
	            }
	        }
	
	}
	function wfOLPC() {
		global $wgMessageCache;
		SpecialPage::AddPage(new UnlistedSpecialPage('OLPC'));
		 $wgMessageCache->addMessages(
	        array(
			)
		);
	}
	
	
	function wfFindOLPCImages($urls) {
		global $wgParser, $wgServer, $wgOLPCUrls;
		$options = new ParserOptions();
		foreach ($urls as $url) {
			preg_match('/title=[^&]+/', $url, $matches);
			$title = urldecode(str_replace("title=", "", $matches[0]));
			$t = Title::newFromURL($title);
			preg_match('/oldid=[0-9]+/', $url, $matches);
			$oldid  = str_replace("oldid=", "", $matches[0]);
			if (!$t) {
				echo "error getting title for $url, $title\n";
				continue;
			}	
			$r = Revision::newFromTitle($t, $oldid);
	if (!$r) { //echo "cant' get r for $title, $oldid \n"; 
	continue; 
	}
			$output = $wgParser->parse($r->getText(), $t, $options);
			preg_match_all("/<img src=\"\/images\/thumb\/[^\"]+\"/", $output->getText(), $matches);
			foreach ($matches[0] as $img) {
					$img = str_replace("<img src=\"", "", $img);
					$img = substr($img, 0, strlen($img) - 1);
					echo "$wgServer$img \n";
			}	
			//echo "{$t->getFullURL()}, {$matches[0]}\n";
		}
	
	}
    function execute ($par) {
		global $wgRequest, $wgListoftitles, $wgOut, $wgServer, $wgOLPCCategoryMap, $wgUser;
	
	    $fname = "wfOLPC";
	
		$me = Title::makeTitle(NS_SPECIAL, "OLPC");
		
		if ($wgRequest->getVal('command') == 'start') {
			header("Content-Type: text/plain;");
	
			if ($wgRequest->getVal('listoftitles', null) != null) {
				$wgListoftitles = $wgRequest->getVal('listoftitles');
			}
	
			$wgOut->setArticleBodyOnly(true);
			$urls = array();
			$t = Title::newFromText($wgListoftitles);
			$r = Revision::newFromTitle($t);
			preg_match_all('/http[^ ]*/', $r->getText(), $matches);
			foreach ($matches[0] as $url) {
				if (strpos($url, $wgServer) !== 0) {
					$url = str_replace("http://www.wikihow.com", $wgServer, $url);
				}
				$url = str_replace('&action=history', '', $url);
				echo "$url&useskin=olpc\n";
			}
			wfFindOLPCImages($matches[0]);
			$skinDir = opendir("/var/www/html/wiki19/skins/Olpc/");
			while($entryName = readdir($skinDir)) {
				$skinFiles[] = $entryName;
			}
			// 	close directory
			closedir($skinDir);
			foreach ($skinFiles as $file) {
				if ($file != "." && $file != ".." && $file != "CVS" && strpos($file, ".#") !== 0)
					echo "$wgServer/skins/Olpc/$file\n";
			}
			echo $me->getFullURL() . "?clearcategory=1\n";
			$cats = wfMsg('OLPC_Category_Sidebar');
			$cats = preg_replace('/\|.*/', '', $cats);
			$cats = preg_replace('/\*\[\[:/', '', $cats);
			$cats = preg_replace('/===.*===/', '', $cats);
			$cat_array = split("\n", $cats);
			foreach ($cat_array as $cat) {
				$cat = trim($cat);
				if ($cat == "") continue;
				$t = Title::newFromText($cat);
				echo $me->getFullURL() . "?getcategory=" . $t->getDBKey() . "&useskin=olpc\n";
			}
			exit;
	
		} else if ($wgRequest->getVal('clearcategory', null) != null) {
			$wgOLPCCategoryMap = null;	
		} else if ($wgRequest->getVal('getcategory', null) != null) {
			$category = $wgRequest->getVal('getcategory');
			OLPC::loadOLPCUrls();
			wfLoadOLPCategoryMap();
	
			$x = Title::makeTitle(NS_CATEGORY, $category);
			$articles = $wgOLPCCategoryMap[$x->getPrefixedURL()];
			$wgOut->setPageTitle($x->getFullText());
			if (sizeof($articles) ==0) {
				$wgOut->addHTML("There are currently no articles in this category.");
				 return;
			}
			$wgOut->addHTML("<ol>\n");
			$sk = $wgUser->getSkin();
			foreach($articles as $article){
				$wgOut->addHTML("<li> " . $sk->makeLinkObj($article) . "</li>");
			}
			$wgOut->addHTML("</ol>\n");
	
		}
	
		return;
	}
}

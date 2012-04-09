<?
/*
* 
*/
class ArticleData extends UnlistedSpecialPage {

	var $action = null;
	var $slowQuery = false;
	var $introOnly = false;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('ArticleData');
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest, $wgServer, $isDevServer;

		$userGroups = $wgUser->getGroups();
		if (($wgServer != "http://spare1.wikihow.com" && !$isDevServer) || $wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($wgRequest->wasPosted()) {
			$this->action = $wgRequest->getVal('a');
			$this->slowQuery = $wgRequest->getVal('alts') == 'true';
			$this->introOnly = $wgRequest->getVal('intonly') == 'true';
			switch ($this->action) {
				case 'cats':
					$this->outputCategoryReport();
					break;
				case 'articles':
					$this->outputArticleReport();
					break;
			}
			return;
		}

		$this->action = empty($par) ? 'cats' : strtolower($par);
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('download.jQuery.js'), 'extensions/wikihow/common', false));
		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$vars = array();
		$this->setVars($vars);
		$html = EasyTemplate::html('ArticleData', $vars);
		$wgOut->setPageTitle('Article Stats');
		$wgOut->addHTML($html);
	}

	function setVars(&$vars) {
		$vars['ct_a'] = $this->action;
	}

	function outputCategoryReport() {
		global $wgRequest, $wgOut;

		$cat = str_replace("http://www.wikihow.com/Category:", "", trim(urldecode($wgRequest->getVal('data'))));
		$catArr = array($cat);
		$cats = CategoryInterests::getSubCategoryInterests($catArr);
		$cats[] = $cat;
		$cats = '"' . join('","', $cats) . '"';

		$sql = 'SELECT 
					page_id, page_title, page_counter 
				FROM page p 
				INNER JOIN categorylinks c ON c.cl_from = page_id 
				WHERE page_namespace = 0  and page_is_redirect = 0 AND c.cl_to IN (' . $cats . ')';
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query($sql);	

		//$slowColumns = $this->slowQuery ? "\timages\talt_methods\tbyte_size" : "";
		$slowColumns = $this->slowQuery ? "\talt_methods\tbyte_size" : "";
		$output = "page_id\tpage_title\tviews$slowColumns\n";
		while ($row = $dbr->fetchObject($res)) {
			$altsData = "";
			if ($this->slowQuery) {
				$r = Revision::loadFromPageId($dbr, $row->page_id);
				$wikitext = $r->getText();
				//$imgs = $this->hasImages($wikitext);
				$altsData = $this->hasAlternateMethods($wikitext) ? "Yes" : "No";
				$sizeData = $this->getArticleSize($r);
			}
			//$output .= join("\t", array_values(get_object_vars($row))) . "\t$imgs\t$altsData\t$sizeData\n";
			$output .= join("\t", array_values(get_object_vars($row))) . "\t$altsData\t$sizeData\n";
		}
		$this->sendFile($cat, $output);
	}

	function hasImages(&$wikitext) {
		if ($this->introOnly) {
			$text = WikiText::getIntro($wikitext);
			$firstImage = Wikitext::getFirstImageURL($text);
			$hasImages = !empty($firstImage) ? "Yes" : "No";
		}
		else {
			list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
			if ($stepsText) {
				// has steps section, so assume valid candidate for detailed title
				$num_steps = preg_match_all('/^#[^*]/im', $stepsText, $matches);
			}
			$num_photos = preg_match_all('/\[\[Image:/im', $wikitext, $matches);
			$hasImages = $num_photos > ($num_steps / 2) ? "Yes" : "No";
		}

		return $hasImages;
	}

	function hasAlternateMethods(&$wikitext) {
		return preg_match("@^===@m", $wikitext);
	}

	function getArticleSize(&$object) {
		$size = 0;
		if ($object instanceof Title) {
			if(!is_null($r = Revision::newFromId($object->getLatestRevID()))) {
				$size = $r->getSize();	
			}
		}

		if ($object instanceof Revision) {
			$size = $object->getSize();	
		}
		return $size;
	}

	function outputArticleReport() {
		global $wgRequest;

		$urls = split("\n", trim(urldecode($wgRequest->getVal('data'))));
		$dbr = wfGetDB(DB_SLAVE);
		$articles = array();
		foreach ($urls as $url) {
			$t = Title::newFromText(str_replace("http://www.wikihow.com/", "", $url));
			if ($t && $t->exists()) {
				$articles[$t->getArticleId()] = array ('url' => $url);
				if ($this->slowQuery) {
					$wikitext = Wikitext::getWikitext($dbr, $t);
					$articles[$t->getArticleId()]['alts'] = $this->hasAlternateMethods($wikitext) ? "Yes" : "No";
					$articles[$t->getArticleId()]['size'] = $this->getArticleSize($t);
					$articles[$t->getArticleId()]['imgs'] = $this->hasImages($wikitext);
				}
			}
		}
		$this->addPageCounts($articles);
		$this->sendFile("article_stats", $this->getArticleReport($articles));
	}

	function addPageCounts(&$articles) {
		$dbr = wfGetDB(DB_SLAVE);
		$aids = join(",", array_keys($articles));
		$res = $dbr->select('page', array('page_counter', 'page_id'), array("page_id IN ($aids)"));
		while ($row = $dbr->fetchObject($res)) {
			$articles[$row->page_id]['cnt'] = $row->page_counter;
		}
	}

	function getArticleReport(&$articles) {
		$slowColumns = $this->slowQuery ? "\timages\talt_methods\tbyte_size" : "";
		$output = "url\tviews$slowColumns\n";

		foreach ($articles as $aid => $data) {
			$slowData = $this->slowQuery ? "\t{$data['imgs']}\t{$data['alts']}\t{$data['size']}" : "";
			$output .= "{$data['url']}\t{$data['cnt']}$slowData\n";
		}
		return $output;
	}

	function sendFile($filename, &$output) {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		$wgRequest->response()->header('Content-type: text/plain');
		$wgRequest->response()->header('Content-Disposition: attachment; filename="' . addslashes($filename) . '.txt"');
		$wgOut->addHtml($output);
	}
}

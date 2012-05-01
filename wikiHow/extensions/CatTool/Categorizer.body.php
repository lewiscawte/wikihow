<?
class Categorizer extends UnlistedSpecialPage {

	var $pageIdsKey = null;
	var $inUseKey = null;
	var $skippedKey = null;
	var $editPage = false;
	var $noMoreArticlesKey = null;
	var $oneHour = 0;
	var $halfHour = 0;
	var $oneWeek = 0;

	function __construct() { 
		global $wgUser;
		UnlistedSpecialPage::UnlistedSpecialPage( 'Categorizer' );

		$userId = $wgUser->getId();
		$this->pageIdsKey = wfMemcKey("cattool_pageids");
		$this->inUseKey = wfMemcKey("cattool_inuse");
		$this->skippedKey = wfMemcKey("cattool_{$userId}_skipped");
		$this->noMoreArticlesKey = wfMemcKey("cattool_nomore");

		$this->halfHour = time() + 60 * 30;
		$this->oneHour = time() + 60 * 60;
		$this->oneWeek = time() + 60 * 60 * 24 * 7;
	}
	
	function execute($par) {
		global $wgOut, $wgRequest, $wgUser;
		wfLoadExtensionMessages('Categorizer');

		$fname = 'Categorizer::execute';
		wfProfileIn( $fname );

		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		if ($wgUser->getId() == 0) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$action = $wgRequest->getVal('a', 'default');
		$pageId = $wgRequest->getVal('id', -1);
		switch ($action) {
			case 'default':
				$t = $this->getNext();
				$this->display($t);
				break;
			case 'editpage':
				$this->editPage = true;
				$t = Title::newFromId($pageId);
				$this->display($t);
				break;
			case 'complete':
				$this->complete($pageId);
				$t = $this->getNext();
				$this->displayHead($t);
				break;
			case 'skip':
				$this->skip($pageId);
				$t = $this->getNext();
				$this->displayHead($t);
				break;
			case 'view':
				$t = Title::newFromId($pageId);
				$this->display($t);
				break;
		}

		wfProfileOut( $fname );
		return;
	}

	function getNext() {
		global $wgMemc;

		$t = null;
		if (!$wgMemc->get($this->noMoreArticlesKey)) {
			do {
				$pageId = $this->getNextArticleId();
				$t = Title::newFromId($pageId);
			} while ($pageId != -1 && (!$t || !$t->exists()));
		}

		return $t;
	}

	function getNextArticleId() {
		global $wgMemc;
		$key = $this->pageIdsKey;
		$pageIds = $wgMemc->get($key);
		if (empty($pageIds) || $this->fetchMoreArticleIds()) {
			$pageIds = $this->getUncategorizedPageIds();
			$wgMemc->set($key, $pageIds, $this->oneWeek);
			// Remove old inuse article ids
			$wgMemc->set($this->inUseKey, array(), $this->halfHour);
		}
		$pageId = -1;
		foreach ($pageIds as $page) {
			try {
				if (!$this->skipped($page) && !$this->inUse($page) && GoodRevision::patrolledGood(Title::newFromId($page))) {
					$this->markInUse($page);
					$pageId = $page;
					break;
				}
			} catch (Exception $e) {
				$this->skip($page);
				continue;
			}
		}
		return $pageId;
	}

	function fetchMoreArticleIds() {
		global $wgMemc;
		$ret = false;
		$pageIds = $wgMemc->get($this->pageIdsKey);
		$inUseIds = $wgMemc->get($this->inUseKey);
		$diff = array();
		if (is_array($pageIds) && is_array($inUseIds)) {
			$diff = array_diff($pageIds, $inUseIds);	
		}
		if (empty($diff)) {
			$ret = true;
		}
		return $ret;
	}

	function skip($pageId) {
		global $wgMemc;
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		if(is_array($val)) {
			$val[] = $pageId;	
		} else {
			$val = array($pageId);
		}
		$wgMemc->set($key, $val, $this->oneWeek);
		$this->unmarkInUse($pageId);
	}

	function skipped($pageId) {
		global $wgMemc;
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		return $val ? in_array($pageId, $val) : false;
	}

	function inUse($pageId) {
		global $wgMemc;
		$key = $this->inUseKey;
		$val = $wgMemc->get($key);
		return $val ? in_array($pageId, $val) : false;
	}
	
	function unmarkInUse($page) {
		global $wgMemc;
		$key = $this->inUseKey;
		// Remove from page ids
		$pageIds = $wgMemc->get($key);
		if ($pageIds) {
			foreach ($pageIds as $k => $pageId) {
				if ($page == $pageId) {
					unset($pageIds[$k]);
					$wgMemc->set($key, $pageIds, $this->halfHour);
					break;
				}
			}
		} 
	}

	function markInUse($pageId) {
		global $wgMemc;
		$key = $this->inUseKey;
		$val = $wgMemc->get($key);
		if ($val) {
			// Throw an exception if someone else has marked this in use
			if(in_array($pageId, $val)) {
				throw new Exception("pageId in use: $pageId");
			}
			$val[] = $pageId;
		} else {
			$val = array($pageId);
		}
		$wgMemc->set($key, $val, $this->halfHour);
	}

	function display(&$t) {
		global $wgOut, $wgRequest;

		$vars = array();
		$this->setVars($vars, $t);
		$wgOut->setArticleBodyOnly($this->editPage);
		EasyTemplate::set_path(dirname(__FILE__).'/');
		$html = $this->editPage ? EasyTemplate::html('Categorizer_editpage', $vars) : EasyTemplate::html('Categorizer', $vars);
		$wgOut->addHtml($html);
		$this->displayLeaderboards();
		$wgOut->setHTMLTitle(wfMsg('cat_app_name'));
	}

	function displayHead(&$t) {
		global $wgOut;
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHtml($this->getHeadHtml($t));
	}

	function getHeadHtml(&$t, &$vars = array()) {
		global $wgUser, $IP;

		if ($t && $t->exists()) {
			$vars['cats'] = $this->getCategoriesHtml($t);
			$vars['pageId'] = $t->getArticleId();
			$sk = $wgUser->getSkin();
			$vars['title'] = $t->getText();
			$vars['titleUrl'] = "/" . urlencode(htmlspecialchars_decode(urldecode($t->getPartialUrl())));
			$vars['intro'] = $this->getIntroText($t);
		} else {
			// No title to display. See Categorizer::getNext()
			$vars['pageId'] = -1;
			$vars['title'] = wfMsg('cat_no_articles');
			$vars['titleUrl'] = '#';
		}
		EasyTemplate::set_path(dirname(__FILE__).'/');
		return EasyTemplate::html('Categorizer_head', $vars);
	}

	function getIntroText(&$t) {
		$r = Revision::newFromTitle($t);
		$intro = Article::getSection($r->getText(), 0);
		return Wikitext::flatten($intro);
	}

	function setVars(&$vars, &$t) {
		global $wgUser, $wgRequest;
		$vars['cat_head'] = $this->getHeadHtml($t, $vars);
		$vars['cat_help_url'] = wfMsg('cat_help_url');
		$vars['js'] = HtmlSnips::makeUrlTags('js', array('categorizer.js'), '/extensions/wikihow/cattool', false);
		$css = array('categorizer.css');
		if ($this->editPage) {
			$css[] = 'categorizer_editpage.css';
		}
		$vars['css'] = HtmlSnips::makeUrlTags('css', $css, '/extensions/wikihow/cattool', false);
		$vars['tree'] = json_encode(CategoryInterests::getCategoryTreeArray());
		$vars['cat_search_label'] = wfMsg('cat_search_label');
		$vars['cat_subcats_label'] = wfMsg('cat_subcats_label');
	}

	function displayLeaderboards() {
		if (!$this->editpage) {
			$stats = new CategorizationStandingsIndividual();
			$stats->addStatsWidget();
			$standings = new CategorizationStandingsGroup();
			$standings->addStandingsWidget();
		}
	}

	function getCategoriesHtml(&$t) {
		$html = "";
		$cats = array_reverse($this->getCategories($t));
		foreach ($cats as $cat) {
			if (!CatSearch::ignoreCategory($cat)) {
				$html .= "<span class='ui-widget-content ui-corner-all cat_category  cat_category_initial'>$cat<span class='cat_close'></span></span>";
			}
		}
		return $html;
	}

	function getCategories(&$t) {
		global $wgContLang;

		$parentCats = array_keys($t->getParentCategories());
		$templates = split("\n", wfMsgForContent('templates_further_editing'));
		$cats = array();
		foreach ($parentCats as $parentCat) {
			$parentCat = str_replace("-", " ", $parentCat);
			$catNsText = $wgContLang->getNSText (NS_CATEGORY);
			$parentCat = str_replace("$catNsText:", "", $parentCat);
			// Trim category text in case someone manually entered a category and left some leading whitespace
			$parentCat = trim($parentCat);
			if (false === array_search($parentCat, $templates)) { 
				$cats[] = $parentCat;
			}
		}
		return $cats;
	}

	function getUncategorizedPageIds() {
		global $wgMemc;

		$templates = wfMsgForContent('templates_further_editing');
		$templates = split("\n", $templates);
		$notIn  = " AND cl_to NOT IN ('" . implode("','", $templates) . "')";
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "SELECT page_id FROM page LEFT JOIN categorylinks ON page_id = cl_from $notIn 
			WHERE cl_from IS NULL and page_id != 1548 AND page_namespace = 0 AND page_is_redirect = 0 ORDER BY page_random LIMIT 500";
		$res = $dbr->query($sql, __METHOD__);
		$pageIds = array();
		while ($row = $dbr->fetchObject($res)) {
			$pageIds[] = $row->page_id;
		}

		if (empty($pageIds)) {
			// No more articles to categorize. Let's hold off on checking for 30 min 
			// to give the DB a break
			$wgMemc->set($this->noMoreArticlesKey, true, $this->halfHour);
		}
		return $pageIds;
	}

	function complete($page) {
		global $wgMemc, $wgRequest;
		$key = $this->pageIdsKey;
		// Remove from page ids
		$pageIds = $wgMemc->get($key);
		if ($pageIds) {
			foreach ($pageIds as $k => $pageId) {
				if ($page == $pageId) {
					unset($pageIds[$k]);
					$wgMemc->set($key, $pageIds, $this->oneWeek);
					break;
				}
			}
		} 
		$this->categorize($page);
		$this->unmarkInUse($page);
	}

	function categorize($aid) {
		global $wgRequest;

		$t = Title::newFromId($aid);	
		if ($t && $t->exists()) {
			$dbr = wfGetDB(DB_MASTER);
			$wikitext = Wikitext::getWikitext($dbr, $t);

			$intro = Wikitext::getIntro($wikitext);
			$intro = $this->stripCats($intro);

			$cats = array_reverse($wgRequest->getArray('cats', array()));
			$intro .= $this->getCatsWikiText($cats);
			
			$wikitext = Wikitext::replaceIntro($wikitext, $intro);
			$result = Wikitext::saveWikitext($t, $wikitext, 'categorization');

			// Article saved successfully
			if ($result === '') {
				wfRunHooks("CategoryHelperSuccess", array());
			}
		}
	}

	function getCatsWikiText($cats) {
		global $wgContLang;
		$text = "";
		foreach ($cats as $cat) {
			$text .= "\n[[" . $wgContLang->getNSText(NS_CATEGORY) . ":$cat]]";	
		}
		return $text;
	}

	function stripCats($text) {
		global $wgContLang;
		return preg_replace("/\[\[" . $wgContLang->getNSText(NS_CATEGORY) . ":[^\]]*\]\]/im", "", $text);
	}
	
	function makeDBKey($cat) {
		//return str_replace(" ", "-", $cat);
		return $cat;
	}
}

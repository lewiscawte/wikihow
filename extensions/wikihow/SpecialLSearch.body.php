<?php

/**
 * Our Google Search Appliance special page.  Used to use Lucene search.
 */
class LSearch extends SpecialPage {

	const RESULTS_PER_PAGE = 30;

	var $mCurrentNode = array();
	var $mResults = array();
	var $mCurrentTag = array();
	var $mLast = 0;
	var $mQ = '';
	var $mStart = 0;

	public function __construct() {
		SpecialPage::SpecialPage('LSearch');
	}

	/**
	 * Used to log the search in the gmini table, just to keep this data.
	 */
	private function logSearch($q, $host_id, $cache, $error, $curl_err, $gm_tm_count, $gm_ts_count, $username, $userid, $rank, $num_results) {
		$dbw	= wfGetDB(DB_MASTER);
		$dbw->insert('gmini',
			array(
				'gm_query' 			=> strtolower($q),
				'gm_host_id' 		=> $host_id,
				'gm_cache' 			=> $cache,
				'gm_error' 			=> $error,
				'gm_curl_error'		=> $curl_err,
				'gm_ts_count' 		=> $gm_ts_count,
				'gm_tm_count' 		=> $gm_tm_count,
				'gm_user'			=> $userid,
				'gm_user_text' 		=> $username,
				'gm_num_results'	=> $num_results,
				'gm_rank'			=> $rank,
			),
			"GMini:LogSearch",
			array('DELAYED')
		);
	}

	/* no longer used:
	function wfSpecialGetLuceneSearchResults($q, $score_limit = 0) {
		global $wgLuceneHostname, $wgRequest, $wgOut, $wgServer, $wgBogusQueries;
		$lq = preg_replace('/[^a-zA-Z0-9-\s]/', '', $q);
		$pageContents = file_get_contents("http://" . $wgLuceneHostname. "/search_wiki2.jsp?q=" . urlencode($lq) );
		$hits = array(); // return title objects
		$urls = array(); // avoid dupes

		$q = trim($q);
		if (in_array(strtolower($q), $wgBogusQueries) ) {
			return $hits;
		}

		$t = Title::newFromText($q);
		if (!$t || $t->getArticleID() == 0)
			$t = Title::newFromText(EditPageWrapper::formatTitle($q));

		if ($t && $t->getArticleID() > 0) {
			$hit = array();
			$hit[0] = $t;
			$hit[1] = 1;
			$hits[] = $hit;
			$urls[$t->getFullURL()] = 1;
		}

		$lines = split("\n", $pageContents);
		foreach ($lines as $line) {
			$tokens = split("\t", $line);
			$line = $tokens[0];
			$score = $tokens[1] ;
			if ($score_limit > 0 && $score < $score_limit)
				continue;
			$line = trim($line);
			if ($line == "") continue;
			$line = str_replace ("http://wiki.ehow.com" . "/", "", $line);
			$line = str_replace ("http://www.wikihow.com" . "/", "", $line);
			$line = str_replace ($wgServer . "/", "", $line);

			$line = urldecode($line);
			$t = Title::newFromDBKey($line);
			if ($t && isset($urls[$t->getFullURL()])) continue;
			if ($t) {
				$hit = array();
				$hit[0] = $t;
				$hit[1] = $score;

				// chck if the q is an exact match on the ttiel
				$x = strtolower(preg_replace('/\s\s+/', ' ', $t->getFullText() ));
				$y = strtolower(preg_replace('/\s\s+/', ' ', $q));
				if ($x == $y && sizeof($hits) > 0) {
					$tmp = $hits[0];
					$hits[0]= $hit;
					$hit = $tmp;
				}
				$hits[] = $hit;
				$urls[$t->getFullURL()] = 1;
			}
			$count++;
		}
		if ($wgRequest->getVal('raw', null) == true) {
			$wgOut->disable();
			foreach($urls as $url => $val)
				echo $url . "\n";
			return;
		}
		return $hits;
	}
	*/

	/**
	 * A callback used to parse the output of the google search appliance.
	 */
	public function googleMiniXmlStartElement($parser, $name, $attrs) {
		if ($name == "RES") {
			$this->mLast = $attrs['EN'];
		}
		if ($name == "R") {
			$this->mCurrentNode = array();
			return;
		}
		switch ($name) {
			case 'U':
			case 'UE':
			case 'T':
			case 'RK':
			case 'CRAWLDATE':
			case 'FS':
			case 'S':
			case 'LANG':
			case 'HAS':
			case 'L':
			case 'C':
				$this->mCurrentTag = $name;
				break;
			default:
				$this->mCurrentTag = $name;

		}
	}

	/**
	 * A callback used in XML parsing of the GSA output
	 */
	public function googleMiniXmlEndElement($parser, $name) {
		if ($name == "R") {
			$this->mResults[] = $this->mCurrentNode;
			$this->mCurrentNode = null;
		}
	}

	/**
	 * A callback used in XML parsing of the GSA output
	 */
	public function googleMiniXmlDefaultHandler($parser, $data)  {
		if ($this->mCurrentTag) {
			if (isset($this->mCurrentNode[$this->mCurrentTag]))
				$this->mCurrentNode[$this->mCurrentTag].= $data;
			else
				$this->mCurrentNode[$this->mCurrentTag] = $data;
		}
	}

	/**
	 * The public interface into this class used to list a bunch of
	 * titles from the GSA index.
	 */
	public function googleSearchResultTitles($q, $first = 0, $limit = 30, $minrank = 0) {
		$this->googleSearchResults($q, $first, $limit);
		$results = array();
		if (!is_array($this->mResults)) return $results;
		foreach ($this->mResults as $r) {
			if ($minrank > 0 && $r['RK'] < $minrank)
				continue;
			$url = str_replace("http://www.wikihow.com/", "", $r['UE']);
			$t = Title::newFromURL(urldecode($url));
			if ($t) $results[] = $t;
		}
		return $results;
	}

	/**
	 * Query the GSA, return the results in XML.
	 */
	private function googleSearchResults($q, $start, $limit = 30) {
		global $wgGoogleMiniHost, $wgOut, $wgRequest, $wgUser,  $wgMemc, $wgBogusQueries, $IP;

		if ($wgRequest->getVal('rss') == 1) {
			require_once("$IP/extensions/wikihow/GoogleAjaxSearch.body.php");
			// use limit and start parameters to allow for paging
			if ($wgRequest->getVal('chrome') == '1') {
				$results = GoogleAjaxSearch::scrapeGoogle($q, $wgRequest->getVal('limit'), 'wikihow.com');
			} else {
				$results = GoogleAjaxSearch::scrapeGoogle($q, 16, 'wikihow.com');
			}
			$wgOut->disable();
			$pad = "           ";
			header("Content-type: text/xml;");
			echo '<GSP VER="3.2">
<TM>0.083190</TM>
<Q>' . htmlspecialchars($q) . '</Q>
<PARAM name="filter" value="0" original_value="0"/>
<PARAM name="num" value="16" original_value="30"/>
<PARAM name="access" value="p" original_value="p"/>
<PARAM name="entqr" value="0" original_value="0"/>
<PARAM name="start" value="0" original_value="0"/>
<PARAM name="output" value="xml" original_value="xml"/>
<PARAM name="sort" value="date:D:L:d1" original_value="date%3AD%3AL%3Ad1"/>
<PARAM name="site" value="main_en" original_value="main_en"/>
<PARAM name="ie" value="UTF-8" original_value="UTF-8"/>
<PARAM name="client" value="internal_frontend" original_value="internal_frontend"/>
<PARAM name="q" value="' . htmlspecialchars($q) . '" original_value="' . htmlspecialchars($q) . '"/>
<PARAM name="ip" value="192.168.100.100" original_value="192.168.100.100"/>
<RES SN="1" EN="' . sizeof($results) . '">
<M>' . sizeof($results) . '</M>
<XT/>';
			$count = 1;
			foreach ($results as $r) {
				echo "<R N=\"{$count}\">
					<U>{$r->getFullURL()}</U>
					<UE>{$r->getFullURL()}</UE>
					<T>How to " . htmlspecialchars($r->getFullText()) . "{$pad}</T>
					<RK>10</RK>
					<HAS></HAS>
					<LANG>en</LANG>
			</R>";
				$count++;
			}
echo "</RES>
</GSP>";

			return;
		}

		$key = wfMemcKey("GoogleMini:" . str_replace(" ", "-", $q)."-".$start."-".$limit);
		$dbw = wfGetDB(DB_MASTER);
		$set_cache = true;

		$q = trim($q);
		if (in_array(strtolower($q), $wgBogusQueries) ) {
			return "<root></root>";
		}

		$host_id = 0;
		$host = "";
		if (is_array($wgGoogleMiniHost)) {
			$host_id = rand(0, sizeof($wgGoogleMiniHost) - 1);
			$host = $wgGoogleMiniHost[$host_id];
			if (is_array($host)) {
				$host_bucket 	= $host;
				$host_id 		= $host_bucket[0];
				$host 			= $host_bucket[1];
			}
		} else {
			$host = $wgGoogleMiniHost;
		}
		$cache = 0;
		if ($wgMemc->get($key)) {
			$contents = $wgMemc->get($key);
			$set_cache = false;
			$cache = 1;
		} else {
			$url = "$host/search?q=" . urlencode($q) . "&output=xml&client=default_frontend&num={$limit}&start={$start}&filter=0";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);

			$contents = curl_exec($ch);

			$gm_tm_count = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
			if (curl_errno($ch)) {
				$wgOut->addHTML(
					"Sorry! An error was experienced while processing your search. Try refreshing the page or click <a href=\"{$wgRequest->getRequestURL()}\">here</a> to try again. " );

				self::logSearch($q, $host_id, 0, 1, curl_errno($ch), $gm_tm_count, 0, $wgUser->getName(), $wgUser->getID(), 0, 0);
				curl_close($ch);
				return null;
			} else {
				curl_close($ch);
			}
		}

		if ($wgRequest->getVal('rss') == 1) {
			$wgOut->disable();
			header("Content-type: text/xml");
			echo $contents;
			return;
		}
		preg_match("/<TM>.*<\/TM>/im",$contents, $matches);
		$tm = strip_tags($matches[0]);
		preg_match("/<RK>.*<\/RK>/im",$contents, $matches);
		$rank = sizeof($matches) > 0 ? strip_tags($matches[0]) : 0;
		$num_results = 0;
		if (sizeof($matches) > 0) {
			preg_match("/<M>.*<\/M>/im",$contents, $matches);
			$num_results = strip_tags($matches[0]);
		}
		self::logSearch($q, $host_id, $cache, 0, 0, $gm_tm_count, $tm, $wgUser->getName(), $wgUser->getId(), $rank, $num_results);

		if ($set_cache) {
			$wgMemc->set($key, $contents, time() + 3600);
		}
		preg_match_all("/<R N=[^>]*>.*<\/R>/", $contents, $matches);
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, "googleMiniXmlStartElement"), array($this, "googleMiniXmlEndElement"));
		xml_set_default_handler($xml_parser, array($this, "googleMiniXmlDefaultHandler"));
		xml_parse($xml_parser, $contents);
		xml_parser_free($xml_parser);
		return $contents;
	}

	/**
	 * Trim all the "- wikiHow" etc off the back of the titles from GSA.
	 * Make sure the titles can be turned into a MediaWiki Title object.
	 */
	public function makeTitlesUniform($gsaResults) {
		$results = array();
		foreach($gsaResults as $r) {
			$t = htmlspecialchars_decode($r['T']);

			// remove detailed title from search results
			$t = str_replace(" - wikiHow", "", $t);
			$t = str_replace(" - wikihow", "", $t);
			$t = str_replace(" (with pictures)", "", $t);
			$t = str_replace(" (with video)", "", $t);
			$t = preg_replace("/\:(.*?)steps/", "", $t);
			$t = str_replace(' - how to articles from wikiHow', '', $t);

			//$s = htmlspecialchars_decode($r['S']);
			$url = str_replace("http://www.wikihow.com/", "", $r['UE']);
			$tobj = Title::newFromURL(urldecode($url));
			if (!$tobj) continue;
			$url = $tobj->getFullURL();
			$key = $tobj->getDBkey();

			$results[] = array('title_match' => $t, 'url' => $url, 'key' => $key);
		}
		return $results;
	}

	/**
	 * Add our own meta data to the search results to make them more
	 * interesting and informative to look at.
	 */
	public function supplementResults($titles) {
		global $wgMemc;
		$enc_q = urlencode($this->mQ);
		$cachekey = wfMemcKey('supp' . $this->mStart . ':' . $enc_q);
		// tmp hack from reuben
		$results = $wgMemc->get($cachekey);
		//$results = null;
		if ($results === null) {
			$results = array();

			$keys = array();
			foreach ($titles as $title) {
				$keys[] = $title['key'];
			}

			if (count($keys) == 0) {
				return $results;
			}

			$dbr = wfGetDB(DB_SLAVE);
			$sql = 'SELECT * FROM search_results WHERE sr_title IN (' . $dbr->makeList($keys) . ')';
			$res = $dbr->query($sql);
			$rows = array();
			while ( $row = $dbr->fetchRow( $res ) ) {
				$rows[ $row['sr_title'] ] = $row;
			}

			foreach ($titles as $title) {
				$key = $title['key'];
				$hasSupplement = isset($rows[$key]);
				if ($hasSupplement) {
					foreach ($rows[$key] as $k => $v) {
						if (preg_match('@^sr_@', $k)) {
							$k = preg_replace('@^sr_@', '', $k);
							$title[$k] = $v;
						}
					}
				}
				$title['has_supplement'] = intval($hasSupplement);
				$title['is_category'] = intval(preg_match('@^(http://[^/]+)?/Category:@', $title['url']) > 0);
				$results[] = $title;
			}

			$wgMemc->set($cachekey, $results);
		}
		return $results;
	}

	/**
	 * Special:LSearch page entry point
	 */
	public function execute($par = '') {
		global $wgUser, $wgOut, $wgScriptPath, $wgRequest, $wgServer;
		global $wgLanguageCode, $wgUseLucene, $IP, $wgHooks;

		$wgHooks['ShowBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');
		$wgHooks['WrapBodyWithArticleInner'][] = array($this, 'wrapBodyWithArticleInner');

		if ($wgLanguageCode != 'en' || ! $wgUseLucene ) {
			require_once("$IP/includes/SpecialSearch.php");
			wfSpecialSearch();
			return;
		}

		//show the gray article image at the bottom
		if (class_exists('WikihowCSSDisplay'))
			WikihowCSSDisplay::setSpecialBackground(true);
		
		$fname = "wfSpecialSearch";
		$me = Title::makeTitle(NS_SPECIAL, "LSearch");
		$sk = $wgUser->getSkin();

		$this->mQ = $wgRequest->getVal('search');
		$enc_q = htmlspecialchars($this->mQ);
		$this->mStart = $wgRequest->getVal('start', 0);

		if ($wgRequest->getVal('rss')) {
			$this->googleSearchResults($this->mQ, $this->mStart);
			return;
		}
		#require_once("$IP/extensions/wikihow/GoogleAjaxSearch.body.php");
		#$contents = GoogleAjaxSearch::scrapeGoogle($this->mQ, $wgRequest->getVal('limit', 16), 'wikihow.com');

		if ($wgRequest->getVal('raw') == true) {
			$contents = $this->googleSearchResultTitles($this->mQ, $this->mStart);
			header("Content-type: text/plain");
			$wgOut->disable(true);
			foreach($contents as $t) {
				echo "{$t->getFullURL()}\n";
			}
			return;
		}

		$contents = $this->googleSearchResults($this->mQ, $this->mStart);
		if ($contents == null) return;

		wfLoadExtensionMessages('LSearch');

		$wgOut->setHTMLTitle(wfMsg('lsearch_title_q', $enc_q));
	
		$me = Title::makeTitle(NS_SPECIAL, "LSearch");
		$sk = $wgUser->getSkin();
		preg_match_all('@<Spelling>.*</Spelling>@im', $contents, $suggestions);
		if (sizeof($suggestions[0]) > 0) {
			$suggestion = strip_tags(htmlspecialchars_decode($suggestions[0][0]));
			$suggestionLink = $sk->makeLinkObj($me, $suggestion, "search=" . urlencode($suggestion));
		}

		$results = $this->makeTitlesUniform($this->mResults);
		$results = $this->supplementResults($results);

		$mw = Title::makeTitle(NS_SPECIAL, "Search");
		$specialPageURL = $mw->getFullURL();

		preg_match("/<M>.*<\/M>/im",$contents, $matches);
		$total = strip_tags($matches[0]);

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'q' => $this->mQ,
			'enc_q' => $enc_q,
			'sk' => $sk,
			'me' => $me,
			'max_results' => self::RESULTS_PER_PAGE,
			'start' => $this->mStart,
			'first' => $this->mStart + 1,
			'last' => $this->mLast,
			'suggestionLink' => $suggestionLink,
			'results' => $results,
			'specialPageURL' => $specialPageURL,
			'total' => $total,
		));
		$html = $tmpl->execute('search-results-new.tmpl.php');

 		$wgOut->addStyle(wfGetPad('/skins/WikiHow/searchresults-new.css?' . WH_SITEREV));
		$wgOut->addHTML($html);
	}

	/**
	 * A Mediawiki callback set in contructor of this class to stop the display
	 * of breadcrumbs at the top of the page.
	 */
	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

	/**
	 * Define a Mediawiki callback to make it so that the body doesn't 
	 * get wrapped with <div class="article_inner"></div> ...
	 */
	public static function wrapBodyWithArticleInner() {
		return false;
	}
}


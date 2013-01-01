<?php

/**
 * Our Google Search Appliance special page.  Used to use Lucene search.
 */
class LSearch extends SpecialPage {

	var $mCurrentNode = array();
	var $mResults = array();
	var $mCurrentTag = array();
	var $mEn = 0;

	public function __construct() {
		global $wgHooks;
		SpecialPage::SpecialPage('LSearch');
		$wgHooks['showBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');
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
			"GMini:LogSeearch",
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
			$this->mEn = $attrs['EN'];
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
	public function googleSearchResultTitles($q, $start = 0, $limit = 30, $minrank = 0) {
		$this->googleSearchResults($q, $start, $limit);
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
		if ( $wgMemc->get($key)) {
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
	 * Special:LSearch page entry point
	 */
	public function execute($par = '') {
		global $wgUser, $wgOut, $wgScriptPath, $wgRequest, $wgServer;
		global $wgLanguageCode, $wgUseLucene, $IP;

		if ($wgLanguageCode != 'en' || ! $wgUseLucene ) {
			require_once("$IP/includes/SpecialSearch.php");
			wfSpecialSearch();
			return;
		}
		$me = Title::makeTitle(NS_SPECIAL, "LSearch");

		$fname = "wfSpecialSearch";

		$q = $wgRequest->getVal('search');
		$start = $wgRequest->getVal('start', 0);
		//$contents = $this->googleSearchResults($q, $start);
		if ($wgRequest->getVal('rss')) {
			$this->googleSearchResults($q, $start);
			return;
		}
		require_once("$IP/extensions/wikihow/GoogleAjaxSearch.body.php");
		$contents = GoogleAjaxSearch::scrapeGoogle($q, $wgRequest->getVal('limit', 16), 'wikihow.com');

		if ($wgRequest->getVal('raw') == true) {
			header("Content-type: text/plain");
			$wgOut->disable(true);
			foreach($contents as $t) {
				echo "{$t->getFullURL()}\n";
			}
			return;
		}
		if ($contents == null) return;

		$wgOut->setHTMLTitle(wfMsg('lsearch_title_q', $q));

		$enc_q = htmlspecialchars($q);
		$wgOut->addHTML("<form id='search_site' action='{$me->getFullURL()}' method='get' >
			<p>
			<input type='hidden' name='fulltext' value='Search'/>
			<input type='text' id='keywords' name='search' size='40' maxlength='75' value=\"{$enc_q}\"/>
			<input type='submit' class='SearchMe' value='" . wfMsg('search') ."' />
			</p></form>");
		if ($q == null) {
			return;
		}

		$sk = $wgUser->getSkin();
		$x = $start + 1;

		preg_match_all('@<Spelling>.*</Spelling>@im', $contents, $suggestions);

		if (sizeof($suggestions[0]) > 0) {
			$suggestion = strip_tags(htmlspecialchars_decode($suggestions[0][0]));
			$link = $sk->makeLinkObj($me, $suggestion, "search=" . urlencode($suggestion) );
			$wgOut->addHTML(wfMsg('lsearch_suggestion', $link));
		}
		if (sizeof($this->mResults) == 0) {
			$wgOut->addHTML(wfMsg('lsearch_noresults', htmlspecialchars($q)));
			return;
		}
		$wgOut->addHTML('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/searchresults.css"; /*]]>*/</style>');
		$wgOut->addHTML("<div id='searchresults_list'>");
		$wgOut->addHTML("Search for '$q', showing results {$x} through {$this->mEn}<br/><br/>");
		$count = 0;
		foreach($this->mResults as $r) {
			$t = htmlspecialchars_decode($r['T']);
			$t = str_replace(" - wikiHow", "", $t);
			//ADDED TO REMOVE DETAILED TITLE FROM SEARCH RESULTS
			$t = str_replace(" (with pictures)", "", $t);
			$t = str_replace(" (with video)", "", $t);
			$t = preg_replace("/\:(.*?)steps/", "", $t);
			$s = htmlspecialchars_decode($r['S']);
			$url = str_replace("http://www.wikihow.com/", "", $r['UE']);
			$tobj = Title::newFromURL(urldecode($url));
			if (!$tobj) { continue; }
			$url = $tobj->getFullURL();
			if ($count % 2 == 1) {
				$wgOut->addHTML("<div class='searchresult_0'><a href=\"{$url}\">{$t}</a></div>");
			} else {
				$wgOut->addHTML("<div class='searchresult_1'><a href=\"{$url}\">{$t}</a></div>");
			}
			$count++;
		}
		$wgOut->addHTML("</div>");

		$mw = Title::makeTitle(NS_SPECIAL, "Search");
		$wgOut->addHTML(wfMsg('lsearch_mediawiki', $mw->getFullURL() . "?search=" . urlencode($q)));

		preg_match("/<M>.*<\/M>/im",$contents, $matches);
		$num = strip_tags($matches[0]);

		if (!($num > $start + 30 && $this->mEn == ($start + 30)) && !($start >= 30) )
			return;

		$wgOut->addHTML("<div style='width: 600px; padding: 10px; margin: 10px; font-size:120%; font-weight: bold; border: 1px solid #eee;'>");
		$wgOut->addHTML("<div style='float:left; width: 300px;'>");
		if ($start >= 30) {
			$x = $start - 30;
			$prev = $sk->makeLinkObj($me, wfMsg("lsearch_previous"), "search=" . urlencode($q) . "&start=$x");
			$wgOut->addHTML($prev);
		}
		$wgOut->addHTML("&nbsp;</div>");
		$wgOut->addHTML("<div style='float:right; width: 250px; text-align: right;'>");
		if ($num > $start + 30 && $this->mEn == ($start + 30)) {
			$x = $start + 30;
			$next = $sk->makeLinkObj($me, wfMsg("lsearch_next"), "search=" . urlencode($q) . "&start=$x");
			$wgOut->addHTML($next);
		}
		$wgOut->addHTML("</div><br/></div>");
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

}


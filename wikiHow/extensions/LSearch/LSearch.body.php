<?php

/**
 * Our Google Search Appliance special page. Used to use Lucene search.
 */
class LSearch extends SpecialPage {

	const RESULTS_PER_PAGE = 30;

	const SEARCH_OTHER = 0;
	const SEARCH_LOGGED_IN = 1;
	const SEARCH_MOBILE = 2;
	const SEARCH_APP = 3;
	const SEARCH_RSS = 4;
	const SEARCH_RAW = 5;
	const SEARCH_404 = 6;
	const SEARCH_CATSEARCH = 7;
	const SEARCH_LOGGED_OUT = 8;

	const MAXAGE_SECS = 21600; // 6 hours

	var $mResults = array();
	var $mSpelling = array();
	var $mLast = 0;
	var $mQ = '';
	var $mStart = 0;
	var $logSearch = true;

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'LSearch' );
	}

	/**
	 * Used to log the search in the gmini table, just to keep this data.
	 */
	private function logSearch( $q, $host_id, $cache, $error, $curl_err, $gm_tm_count, $gm_ts_count, $username, $userid, $rank, $num_results, $gm_type ) {
		if ( $this->logSearch ) {
			$dbw = wfGetDB( DB_MASTER );
			$vals = array(
				'gm_query' 			=> strtolower( $q ),
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
				'gm_type'			=> $gm_type
			);
			$res = $dbw->insert(
				'gmini',
				$vals,
				__METHOD__
			);
		}
	}

	/**
	 * The public interface into this class used to list a bunch of
	 * titles from the GSA index.
	 */
	public function googleSearchResultTitles( $q, $first = 0, $limit = 30, $minrank = 0, $searchType = self::SEARCH_OTHER ) {
		global $wgServer;

		$this->googleSearchResults( $q, $first, $limit, $searchType );
		$results = array();
		$searchResults = $this->mResults['results'];
		if ( !is_array( $searchResults ) ) {
			return $results;
		}

		foreach ( $searchResults as $r ) {
			$url = str_replace( $wgServer . '/', '', $r['url'] );
			$t = Title::newFromURL( urldecode( $url ) );
			if ( $t ) {
				$results[] = $t;
			}
		}

		return $results;
	}

	private function loggedOutSearch() {
		global $wgRequest;

		$this->logSearch = false;
		$q = $wgRequest->getVal( 'search', '' );
		$this->googleSearchResults( $q, $this->mStart, 10, self::SEARCH_LOGGED_OUT );

		$results = array();
		$searchResults = $this->mResults['results'];
		if ( !is_array( $searchResults ) ) {
			return $results;
		}
	}

	/**
	 * Query the GSA, return the results in XML.
	 */
	private function googleSearchResults( $q, $start, $limit = 30, $gm_type = self::SEARCH_OTHER ) {
		global $wgOut, $wgRequest, $wgUser, $wgMemc, $wgBogusQueries, $IP, $wgServer;

		$key = wfMemcKey( 'YahooBoss:' . str_replace( ' ', '-', $q ) . '-' . $start . '-' . $limit );
		$set_cache = true;

		$q = trim( $q );
		if ( in_array( strtolower( $q ), $wgBogusQueries ) ) {
			return null;
		}

		// All Yahoo boss searches have host_id of 100
		$host_id = 100;
		$cache = 0;
		$gm_tm_count = 0;
		$res = $wgMemc->get( $key );
		if ( $res ) {
			$contents = $res;
			$set_cache = false;
			$cache = 1;
		} else {
			$cc_key = WH_YAHOO_BOSS_API_KEY;
			$cc_secret = WH_YAHOO_BOSS_API_SECRET;
			$url = 'http://yboss.yahooapis.com/ysearch/web';
			// Request spelling results for logged in search
			if ( $gm_type == self::SEARCH_LOGGED_IN || $gm_type == self::SEARCH_LOGGED_OUT ) {
				$url .= ',spelling';
			}
			$args = array(
				'q' => $q,
				'format' => 'json',
				'sites' => str_replace( 'http://', '', $wgServer ),
				'start' => $start,
				'count' => $limit
			);

			// Yahoo boss required OAuth 1.0 authentication
			require_once( "$IP/extensions/common/oauth/OAuth.php" );

			$consumer = new OAuthConsumer( $cc_key, $cc_secret );
			$request = OAuthRequest::from_consumer_and_token( $consumer, null, 'GET', $url, $args );
			$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null );
			$url = sprintf( '%s?%s', $url, OAuthUtil::build_http_query( $args ) );
			$ch = curl_init();
			$headers = array( $request->to_header() );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
			$rsp = curl_exec( $ch );
			$contents = null;

			$gm_tm_count = curl_getinfo( $ch, CURLINFO_TOTAL_TIME );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			if ( $http_code != 200 || curl_errno( $ch ) ) {
				$wgOut->addHTML( wfMsg( 'lsearch-error', $wgRequest->getRequestURL() ) );

				self::logSearch(
					$q,
					$host_id,
					0,
					1,
					curl_errno( $ch ),
					$gm_tm_count,
					0,
					$wgUser->getName(),
					$wgUser->getID(),
					0,
					0,
					$gm_type
				);
				curl_close( $ch );
				return null;
			} else {
				$contents = json_decode( $rsp, true );
				curl_close( $ch );
			}
		}

		$num_results = $contents['totalresults'] ? $contents['totalresults'] : 0;
		self::logSearch(
			$q,
			$host_id,
			$cache,
			0,
			0,
			$gm_tm_count,
			0,
			$wgUser->getName(),
			$wgUser->getId(),
			0,
			$num_results,
			$gm_type
		);

		if ( $set_cache ) {
			$wgMemc->set( $key, $contents, 3600 ); // 1 hour
		}

		if ( $gm_type == self::SEARCH_LOGGED_IN || $gm_type == self::SEARCH_LOGGED_OUT ) {
			$this->mSpelling = $contents['bossresponse']['spelling'];
		}
		$this->mResults = $contents['bossresponse']['web'];
		$this->mLast = $this->mStart + $this->mResults['count'];

		return $contents;
	}

	private function cleanTitle( &$t ) {
		// remove detailed title from search results
		$t = str_replace( ' - wikiHow', '', $t );
		$t = str_replace( ' - wiki How', '', $t );
		$t = str_replace( ' - wikihow', '', $t );
		$t = preg_replace( "@ \(with[^\.]+[\.]*@", '', $t );
		$t = preg_replace( "/\:(.*?)steps/", '', $t );
		$t = str_replace( ' - how to articles from wikiHow', '', $t );
	}

	private function localizeUrl( &$url ) {
		return preg_replace( '@^http://([^/]+\.)?wikihow\.com/@', '', $url );
	}

	/**
	 * Trim all the "- wikiHow" etc off the back of the titles from GSA.
	 * Make sure the titles can be turned into a MediaWiki Title object.
	 */
	public function makeTitlesUniform( $gsaResults ) {
		$results = array();
		foreach( $gsaResults as $r ) {
			$t = htmlspecialchars_decode( $r['title'] );
			$this->cleanTitle( $t );

			$url = $this->localizeUrl( $r['url'] );
			$tobj = Title::newFromURL( urldecode( $url ) );
			if ( !$tobj ) {
				continue;
			}
			$key = $tobj->getDBkey();

			$results[] = array(
				'title_match' => $t,
				'url' => $url,
				'key' => $key
			);
		}
		return $results;
	}

	public function cleanLoggedOutResults( &$rawResults ) {
		global $wgContLang;

		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		$results = array();
		foreach ( $rawResults as $r ) {
			$t = htmlspecialchars_decode( $r['title'] );
			$this->cleanTitle( $t );
			if ( stripos( $r['url'], $categoryNamespaceName . ':' ) ) {
				$t = $categoryNamespaceName . ': ' . $t;
			}

			$url = $this->localizeUrl( $r['url'] );
			$abstract = preg_replace( "@ ([\.]+)$@", "$1", $r['abstract'] );
			$results[] = array(
				'title_match' => $t,
				'url' => $url,
				'abstract' => $abstract,
				'dispurl' => $r['dispurl']
			);
		}
		return $results;
	}

	/**
	 * Add our own meta data to the search results to make them more
	 * interesting and informative to look at.
	 */
	public function supplementResults( $titles ) {
		global $wgMemc;

		$enc_q = urlencode( $this->mQ );
		$cachekey = wfMemcKey( 'supp', $this->mStart, $enc_q );
		$results = $wgMemc->get( $cachekey );
		$results = null;

		if ( $results === null ) {
			$results = array();

			$keys = array();
			foreach ( $titles as $title ) {
				$keys[] = $title['key'];
			}

			if ( count( $keys ) == 0 ) {
				return $results;
			}

			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'search_results',
				'*',
				array( 'sr_title' => $keys ),
				__METHOD__
			);
			$rows = array();
			$row = $dbr->fetchRow( $res );
			while ( $row ) {
				$rows[$row['sr_title']] = $row;
			}

			foreach ( $titles as $title ) {
				$key = $title['key'];
				$hasSupplement = isset( $rows[$key] );
				if ( $hasSupplement ) {
					foreach ( $rows[$key] as $k => $v) {
						if ( preg_match( '@^sr_@', $k ) ) {
							$k = preg_replace( '@^sr_@', '', $k );
							$title[$k] = $v;
						}
					}
				}
				$title['has_supplement'] = intval( $hasSupplement );
				$isCategory = $title['namespace'] == NS_CATEGORY;
				$title['is_category'] = intval( $isCategory );
				$results[] = $title;
			}

			$wgMemc->set( $cachekey, $results );
		}

		return $results;
	}

	private function getLoggedOutSearchHtml() {
		global $wgRequest;

		$this->mQ = $wgRequest->getVal( 'search', '' );
		$enc_q = htmlspecialchars( $this->mQ );
		$me = '/wikiHowTo';
		$suggestionLink = $this->getSpellingSuggestion( $me );
		$results = $this->mResults['results'] ? $this->mResults['results'] : array();
		$results = $this->cleanLoggedOutResults( $results );

		$mw = SpecialPage::getTitleFor( 'Search' );
		$specialPageURL = $mw->getFullURL();

		$total = $this->mResults['totalresults'];

		include( 'search-results-lo.tmpl.php' );
		$template = new LSearchSearchResultsLoggedOutTemplate;
		$template->set( 'q', $this->mQ );
		$template->set( 'enc_q', $enc_q );
		$template->set( 'me', $me );
		$template->set( 'max_results', 10 );
		$template->set( 'start', $this->mStart );
		$template->set( 'first', $this->mStart + 1 );
		$template->set( 'last', $this->mLast );
		$template->set( 'suggestionLink', $suggestionLink );
		$template->set( 'results', $results );
		$template->set( 'specialPageURL', $specialPageURL );
		$template->set( 'total', $total );

		ob_start();
		$template->execute();
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	private function setMaxAgeHeaders( $maxAgeSecs = self::MAXAGE_SECS ) {
		global $wgOut, $wgRequest;

		$wgOut->setSquidMaxage( $maxAgeSecs );
		$wgRequest->response()->header(
			'Cache-Control: s-maxage=' . $maxAgeSecs .
			', must-revalidate, max-age=' . $maxAgeSecs
		);
		$future = time() + $maxAgeSecs;
		$wgRequest->response()->header( 'Expires: ' . gmdate( 'D, d M Y H:i:s T', $future ) );

		//$wgOut->setArticleBodyOnly( true );
		$wgOut->sendCacheControl();
	}

	/**
	 * Special:LSearch page entry point
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgRequest, $wgServer, $wgSitename;
		global $wgUseLSearch, $IP, $wgHooks;

		$wgHooks['ShowBreadCrumbs'][] = array( $this, 'removeBreadCrumbsCallback' );
		$wgHooks['WrapBodyWithArticleInner'][] = array( $this, 'wrapBodyWithArticleInner' );

		$this->mStart = $wgRequest->getVal( 'start', 0 );
		$this->mQ = $wgRequest->getVal( 'search', $par );

		// special case search term filtering
		if ( strtolower( $this->mQ ) == 'sat' ) { // searching for SAT, not sitting
			$this->mQ = '"SAT"';
		}

		$enc_q = htmlspecialchars( $this->mQ );

		// Logged out search test
		if ( $wgRequest->getVal( 'lo', 0 ) ) {
		 	$wgHooks['ShowSideBar'][] = array( $this, 'removeSideBarCallback' );
			// We want to cache results of searches for 6 hours at the Varnish level
			// since logged out search receives a high volume of queries
			$wgHooks['AllowMaxageHeaders'][] = array( $this, 'allowMaxageHeadersCallback' );
			$this->setMaxageHeaders();

			$this->loggedOutSearch();

			$wgOut->setHTMLTitle( wfMsg( 'lsearch-title-q', $enc_q, $wgSitename ) );
 			$wgOut->addModules( 'ext.LSearch' );
			$wgOut->addHTML( $this->getLoggedOutSearchHtml() );
			return;
		}

		// If we don't want to use this extension, redirect this special page
		// to the default MediaWiki search page.
		if ( !$wgUseLSearch ) {
			$wgOut->redirect( SpecialPage::getTitleFor( 'Search' )->getFullURL() );
			return;
		}

		if ( $wgRequest->getVal( 'rss' ) == 1 ) {
			$results = $this->googleSearchResultTitles(
				$wgRequest->getVal( 'search' ),
				$this->mStart,
				self::RESULTS_PER_PAGE,
				0,
				self::SEARCH_RSS
			);
			$wgOut->disable();
			$pad = '		   ';
			header( 'Content-type: text/xml;' );
			echo '<GSP VER="3.2">
<TM>0.083190</TM>
<Q>' . htmlspecialchars( $q ) . '</Q>
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
<PARAM name="q" value="' . htmlspecialchars( $q ) . '" original_value="' . htmlspecialchars( $q ) . '"/>
<PARAM name="ip" value="192.168.100.100" original_value="192.168.100.100"/>
<RES SN="1" EN="' . sizeof( $results ) . '">
<M>' . sizeof( $results ) . '</M>
<XT/>';
			$count = 1;
			foreach ( $results as $r ) {
				echo "<R N=\"{$count}\">
					<U>{$r->getFullURL()}</U>
					<UE>{$r->getFullURL()}</UE>
					<T>How to " . htmlspecialchars( $r->getFullText() ) . "{$pad}</T>
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

		// show the gray article image at the bottom
		if ( class_exists( 'WikihowCSSDisplay' ) ) {
			WikihowCSSDisplay::setSpecialBackground( true );
		}

		if ( $wgRequest->getVal( 'raw' ) == true ) {
			$contents = $this->googleSearchResultTitles(
				$this->mQ,
				$this->mStart,
				self::RESULTS_PER_PAGE,
				0,
				self::SEARCH_RAW
			);
			header( 'Content-type: text/plain' );
			$wgOut->disable( true );
			foreach( $contents as $t ) {
				echo "{$t->getFullURL()}\n";
			}
			return;
		}

		if ( $wgRequest->getVal( 'mobile' ) == true ) {
			$this->mobileSearch(
				$this->mQ,
				$this->mStart,
				$wgRequest->getVal( 'limit', 20 )
			);
			return;
		}

		// Logged in search is only for logged in users
		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'lsearch-nologin', 'lsearch-nologintext',
				array( $this->getTitle()->getPrefixedDBkey() ) );
			return;
		}

		$contents = $this->googleSearchResults(
			$this->mQ,
			$this->mStart,
			self::RESULTS_PER_PAGE,
			self::SEARCH_LOGGED_IN
		);
		if ( $contents == null ) {
			return;
		}

		$wgOut->setHTMLTitle( wfMsg( 'lsearch-title-q', $enc_q, $wgSitename ) );

		$me = $this->getTitle();
		$suggestionLink = $this->getSpellingSuggestion( $me->getPartialUrl() );
		$results = $this->mResults['results'] ? $this->mResults['results'] : array();
		$results = $this->makeTitlesUniform( $results );
		$results = $this->supplementResults( $results );

		$mw = SpecialPage::getTitleFor( 'Search' );
		$specialPageURL = $mw->getFullURL();

		$total = $this->mResults['totalresults'];

		include( 'search-results.tmpl.php' );
		$template = new LSearchSearchResultsTemplate;
		$template->set( 'q', $this->mQ );
		$template->set( 'enc_q', $enc_q );
		$template->set( 'me', $me );
		$template->set( 'max_results', self::RESULTS_PER_PAGE );
		$template->set( 'start', $this->mStart );
		$template->set( 'first', $this->mStart + 1 );
		$template->set( 'last', $this->mLast );
		$template->set( 'suggestionLink', $suggestionLink );
		$template->set( 'results', $results );
		$template->set( 'specialPageURL', $specialPageURL );
		$template->set( 'total', $total );
		$template->set( 'isBoss', false );

		// Add CSS
 		$wgOut->addModules( 'ext.LSearch' );

		// Output everything
		$wgOut->addTemplate( $template );
	}

	private function getSpellingSuggestion( $url ) {
		$spellingResults = $this->mSpelling;
		$suggestionLink = null;
		if ( $spellingResults['count'] > 0 ) {
			$suggestion = $spellingResults['results'][0]['suggestion'];
			$suggestionUrl = "$me?search=" . urlencode( $suggestion );
			// A hack for logged out search test
			if ( stripos( $url, 'wikiHowTo' ) ) {
				$suggestionUrl .= '&lo=1';
			}
			$suggestionLink = "<a href=\"$suggestionUrl\">$suggestion</a>";
		}
		return $suggestionLink;
	}

	/**
	 * Return a json array of articles that includes the title, full url and abbreviated intro text
	 */
	public function mobileSearch( $q, $start, $limit = 20 ) {
		global $wgOut, $wgMemc;

		// Don't return more than 50 search results at a time to prevent abuse
		if ( $limit > 50 ) {
			$limit = 50;
		}

		$key = wfMemcKey( 'MobileSearch:' . str_replace( ' ', '-', $q ) . '-' . $start . '-' . $limit );
		$val = $wgMemc->get( $key );
		if ( $val ) {
			return $val;
		}

		$contents = $this->googleSearchResultTitles( $q, $start, $limit, 0, self::SEARCH_MOBILE );
		$results = array();
		foreach ( $contents as $t ) {
			// Only return articles
			if( $t->getNamespace() != NS_MAIN ) {
				continue;
			}

			$result = array();
			$result['title'] = $t->getText();
			$result['url'] = $t->getFullURL();
			$result['imgurl'] = wfGetPad( SkinWikihowskin::getGalleryImage( $t, 103, 80 ) );
			$result['intro'] = null;
			$r = Revision::newFromId( $t->getLatestRevID() );
			if( $r ) {
				$intro = Wikitext::getIntro( $r->getText() );
				$intro = trim( Wikitext::flatten( $intro ) );
				$result['intro'] = substr( $intro, 0, 180 );
				// Put an ellipsis on the end
				$len = strlen( $result['intro'] );
				$result['intro'] .= substr( $result['intro'], $len - 1, $len ) == '.' ? '..' : '...';
			}
			if( !is_null( $result['intro'] ) ) {
				$results[] = array( 'article' => $result );
			}
		}

		$searchResults['results'] = $results;
		$json = json_encode( $searchResults );
		$wgMemc->set( $key, $json, 3600 ); // 1 hour

		header( 'Content-type: application/json' );
		$wgOut->disable( true );
		echo $json;
	}

	/**
	 * A MediaWiki callback set in contructor of this class to stop the display
	 * of breadcrumbs at the top of the page.
	 */
	public static function removeBreadCrumbsCallback( &$showBreadCrumb ) {
		$showBreadCrumb = false;
		return true;
	}

	/**
	 * Define a MediaWiki callback to make it so that the body doesn't
	 * get wrapped with <div class="article_inner"></div> ...
	 */
	public static function wrapBodyWithArticleInner() {
		return false;
	}

	public static function allowMaxageHeadersCallback() {
		return false;
	}

	public static function removeSideBarCallback( &$showSideBar ) {
		$showSideBar = false;
		return true;
	}
}
<?php

/**
 * @property TweetModel $TweetModel
 * @property TwitterReplierTemplate TwitterReplierView
 */
if ( !defined( 'MEDIAWIKI' ) )
	die();

global $IP;

define( 'CONSUMERKEY', 'AhAfJM7EZthn3tWL7gBQ6A' );
define( 'CONSUMERSECRET', 'KIEuCNnOth78zoKsjXqzXo2sZNBgUUsDos8gwb5PEs' );
define( 'TRCOOKIE', 'wiki_twitterReplier' );

require_once dirname( __FILE__ ) . '/Tweet.model.php';
require_once dirname( __FILE__ ) . '/TwitterAuth.model.php';
require_once dirname( __FILE__ ) . '/SearchCategories.model.php';
require_once dirname( __FILE__ ) . '/TwitterReplier.easytemplate.php';
require_once dirname( __FILE__ ) . '/twitter-async/EpiCurl.php';
require_once dirname( __FILE__ ) . '/twitter-async/EpiOAuth.php';
require_once dirname( __FILE__ ) . '/twitter-async/EpiTwitter.php';
require_once dirname( __FILE__ ) . '/RestRequest.class.php';

//TODO: pull this once we're done with development
require_once dirname( __FILE__ ) . '/marks.helpers.php';

class TwitterReplier extends UnlistedSpecialPage
{
	const NUM_WH_SUGGESTED = 7;
	const NUM_KEY_SEARCH_WORDS = 5;
	// we're actually counting spaces
	const MIN_WORDS = 0;
	const NUM_WH_SEARCH_RESULTS = 5;

	public function __construct()
	{
		UnlistedSpecialPage::UnlistedSpecialPage( 'TwitterReplier' );

		$this->TweetModel = new TweetModel();
		$this->TwitterAuthModel = new TwitterAuthModel();
		$this->SearchCategoryModel = new SearchCategoryModel();

		TwitterReplierTemplate::set_path( dirname( __FILE__ ) . '/templates' );
	}

	/**
	 * The callback made to process and display the output of the 
	 * Special:Bloggers page.
	 */
	public function execute( $par )
	{
		global $wgHooks, $wgOut;

		wfLoadExtensionMessages( 'TwitterReplier' );

		$wgHooks['ShowSideBar'][] = array( 'TwitterReplier::removeSideBarCallback' );

		$wgOut->setPageTitle( wfMsg( 'tool-title' ) );
		
		// TODO: find out if there is a better location to link to jquery UI
		$wgOut->addHTML( '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js"></script>' );
		$wgOut->addHTML( TwitterReplierTemplate::linkCss( 'TwitterReplier.css', true ) );
		$wgOut->addHTML( TwitterReplierTemplate::linkJs( 'TwitterReplier.js' ) );

		$html = $this->controller();
		$wgOut->addHTML( $html );

		$wgOut->getHTML();
	}

	public function controller()
	{
		global $wgRequest;

		$action = $wgRequest->getVal( 'action' );

		// couple of action overrides
		$oauthToken = $wgRequest->getVal( 'oauth_token' );

		if ( !empty( $oauthToken ) ) {
			$action = 'authenticated';
		}

		// another action override
		$denied = $wgRequest->getVal( 'denied' );

		if ( !empty( $denied ) ) {
			$action = 'denied';
		}

		$keywords = $wgRequest->getVal( 'keywords' );
		$numResults = $wgRequest->getVal( 'numResults' );

		switch ( $action ) {
			case "streamCompare": // TODO: temporary code
				$html = $this->streamCompareForm();
				break;
			case "searchInbox":
				$inboxType = $wgRequest->getVal( 'inboxType' );
				$settings = array( );
				$settings['inboxType'] = $inboxType;
				$settings['numResults'] = $numResults;

				$results = $this->searchInboxQ( $keywords, $settings );
				echo $this->displayInboxSearchResults( $results );
				exit;
				break;
			case "searchTwitter":
				$results = $this->searchTwitter( $keywords, array( 'numResults' => $numResults ) );
				echo $this->displayTwitterSearchResults( $results );
				exit;
				break;
			case "searchWikihow":
				$tweet = $wgRequest->getVal( 'tweet' );
				$results = $this->getSuggestedTitles( $tweet );
				echo $this->displaySuggestedTitles( $results );
				exit;
				break;
			case "retrieve":
				echo $this->retrieveNewTweets();
				exit;
				break;
			case "latest":
				$lastTwitterId = $wgRequest->getVal( 'lastTwitterId' );
				$returnType = $wgRequest->getVal( 'returnType' );
				$tweets = $wgRequest->getVal( 'tweets' );

				echo $this->displayTweets( $lastTwitterId, $returnType, $tweets );
				exit;
				break;
			case "lockTweet":
				$tweetId = $wgRequest->getVal( 'tweetId' );
				echo $this->lockTweet( $tweetId );
				exit;
				break;
			case "unlockTweet":
				$tweetId = $wgRequest->getVal( 'tweetId' );
				echo $this->unlockTweet( $tweetId );
				exit;
				break;
			case "authenticate":
				if ( !$this->isAuthenitcated() ) {
					echo $this->authenticateUser();
				}
				exit;
				break;
			case "authenticated":
				$this->saveUserTwitterToken( $oauthToken );
				header( "location: /Special:TwitterReplier" );
				exit;
				break;
			case "reply":
				$tweet = $wgRequest->getVal( 'tweet' );
				$replyStatusId = $wgRequest->getVal( 'replyStatusId' );
				echo $this->reply( $tweet, $replyStatusId );
				exit;
				break;
			case "displayMessage":
				$key = $wgRequest->getVal( 'key' );
				if ( strlen( $key ) > 0 ) {
					echo wfMsg( $key );
				}
				exit;
				break;
			case "getUserAvatar":
				echo $this->TwitterAuthModel->getUserAvatar();
				exit;
				break;
			case "getUserScreenName":
				echo $this->TwitterAuthModel->getUserScreenName();
				exit;
				break;
			case "displayTweet":
				$data['tweet'] = $wgRequest->getVal( 'replyTweet' );
				$data['profileImage'] = $wgRequest->getVal( 'profileImage' );
				$data['fromUser'] = $wgRequest->getVal( 'screenName' );

				echo TwitterReplierTemplate::html( 'tweet', $data );
				exit;
				break;
			case "denied":
				// do something if user has denied access
			default:
				$lastTwitterId = $wgRequest->getVal( 'lastTwitterId' );
				$html = $this->displayTweetStream();
				$html .= $this->displayTweetReply();
		}

		return $html;
	}

	private function displayTweetStream()
	{
		$data['tweets'] = $this->TweetModel->getTweets();
		$data['screenName'] = $this->TwitterAuthModel->getUserScreenName();
		$data['profileImage'] = $this->TwitterAuthModel->getUserAvatar();

		$html = TwitterReplierTemplate::html( 'stream', $data );

		return $html;
	}

	private function displayTweets( $lastTweetId = null )
	{
		$data = array( );

		$data['tweets'] = $this->TweetModel->getTweets( $lastTweetId );
		$data['html'] = TwitterReplierTemplate::html( 'tweets', $data );

		return json_encode( $data );
	}

	private function displayTweet( $tweet, $screenName, $profileImage )
	{
		$data['tweet'] = $tweet;
		$data['screenName'] = $screenName;
		$data['profileImage'] = $profileImage;
		$data['createdOn'] = date( "Y-m-d H:i:s" );

		$html = TwitterReplier::html( 'tweet', $data );

		return $html;
	}

	private function displayTweetReply()
	{
		$html = EasyTemplate::html( 'reply' );

		return $html;
	}

	private function lockTweet( $tweetId )
	{
		$userIdentifier = $this->getUserIdentifier();

		$locked = false;
		$locked = $this->TweetModel->lockTweet( $tweetId, $userIdentifier );

		$json = json_encode( array( 'lock' => $locked ) );

		return $json;
	}

	// TODO
	private function unlockTweet( $tweetId )
	{
		$userIdentifier = $this->getUserIdentifier();

		$unlocked = false;
		$unlocked = $this->TweetModel->unlockTweet( $tweetId, $userIdentifier );

		$json = json_encode( array( 'unlock' => $unlocked ) );

		return $json;
	}

	public static function removeSideBarCallback( &$showSideBar )
	{
		$showSideBar = false;
		return true;
	}

	private function searchTwitter( $keywords = null, $settings = null )
	{
		$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET );

		$params = array( );
		$params['q'] = !empty( $keywords ) ? $keywords : "#lazyweb";
		$params['rpp'] = !empty( $settings['numResults'] ) ? $settings['numResults'] : 15;

		$resp = $twitterObj->get( '/search.json', $params );

		return $resp->responseText;
	}

	private function saveSearchResults( $results, $type = 'twitter', $keywordId )
	{
		if ( is_string( $results ) ) {
			$results = json_decode( $results );
		}

		$savedCounter = 0;
		
		switch ( $type ) {
			case "twitter":
				$savedCounter = $this->processTwitterResponseObject( $results->results, $keywordId );
				break;
			case "inboxq":
				$savedCounter = $this->processInboxQResponseObject( $results, $keywordId );
				break;
			default:
				$resp = false;
				break;
		}

		return $savedCounter;
	}

	private function retrieveNewTweets()
	{
		//$defaultInboxQKeywords = array( 'how do i', 'anyone know how to', 'can anyone explain how to', 'i don’t know how to', 'best way to', 'lazyweb' );
		$defaultInboxQKeywords = $this->SearchCategoryModel->getSearchCategories( 'inboxq' );
		$defaultTwitterKeywords = $this->SearchCategoryModel->getSearchCategories( 'twitter' );

		if ( is_array( $defaultInboxQKeywords ) && count( $defaultInboxQKeywords ) > 0 ) {
			$savedTweets = 0;
			foreach ( $defaultInboxQKeywords as $keywords ) {
				$results = $this->searchInboxQ( $keywords['keywords'], array( 'inboxType' => 'qualityInboxQ' ) );
				$savedTweets += $this->saveSearchResults( $results, 'inboxq', $keywords['id'] );
			}
		}

		if ( is_array( $defaultTwitterKeywords ) && count( $defaultTwitterKeywords ) > 0 ) {
			$savedTweets = empty( $savedTweets ) ? 0 : $savedTweets;
			$keywords = '';
			foreach ( $defaultTwitterKeywords as $keywords ) {
				$results = $this->searchTwitter( $keywords );
				$savedTweets += $this->saveSearchResults( $results, 'twitter', $keywords['id'] );
			}
		}

		return $savedTweets;
	}

	private function processTwitterResponseObject( $results, $keywordId )
	{
		$savedCounter = 0;
		if ( is_object( $results ) && is_array( $results->results ) && count( $results->results ) > 0 ) {
			foreach ( $results->results as $tweet ) {
				$tweet->search_category_id = $keywordId;
				$resp = $this->TweetModel->insertTweet( $tweet );

				if ( $resp ) {
					$savedCounter++;
				}
			}
		}

		return $savedCounter;
	}

	private function processInboxQResponseObject( $results, $keywordId )
	{
		$savedCounter = 0;

		if ( is_array( $results ) && count( $results ) > 0 ) {
			foreach ( $results as $tweet ) {
				$tweet->search_category_id = $keywordId;
				$resp = $this->TweetModel->insertInboxQTweet( $tweet );

				$savedCounter = $resp ? $savedCounter + 1 : $savedCoutner;
			}
		}

		return $savedCounter;
	}

	private function authenticateUser()
	{
		$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET );
		$twitterObj->setCallback( 'http://mark.wikidiy.com/Special:TwitterReplier' );

		return json_encode( array( 'authorizationUrl' => $twitterObj->getAuthorizationUrl() ) );
	}

	private function saveUserTwitterToken( $oauthVerificationToken )
	{
		global $wgUser;

		$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET );

		$twitterObj->setToken( $oauthVerificationToken );
		$token = $twitterObj->getAccessToken();
		$twitterObj->setToken( $token->oauth_token, $token->oauth_token_secret );

		$twitterInfo = $twitterObj->get_accountVerify_credentials();
		$twitterInfo->response;

		$twitterUserId = $twitterInfo->response['id_str'];
		$whUserId = $wgUser->getId();

		$this->TwitterAuthModel->saveAccessToken( $token->oauth_token, $token->oauth_token_secret, $twitterUserId, $whUserId );

		$this->generateTwitterCookie( $twitterUserId );
	}

	private function generateTwitterCookie( $twitterUserId )
	{
		// TODO: is there someother unique id generator?
		$hash = sha1( $this->getUniqueId( 128 ) );

		if ( is_numeric( $twitterUserId ) && $twitterUserId > 0 ) {
			if ( !$this->TwitterAuthModel->isValidHash( $hash ) ) {
				// sets cookie to be valid for 7 days
				if ( !setcookie( TRCOOKIE, $hash, time() + 604800, '/', '.' . $_SERVER['HTTP_HOST'], false, true ) ) {
					//echo 'Unable to set cookie: ' . $_SERVER['HTTP_HOST'];
				}

				$this->TwitterAuthModel->saveHash( $hash, $twitterUserId );
				return true;
			}
			else {
				return $this->generateTwitterCookie( $twitterUserId );
			}
		}
		else {
			throw new InvalidArgumentException( 'twitterUserId must be numeric' );
		}
	}

	private function isAuthenitcated()
	{
		global $wgUser;

		$isAuthenitcated = false;

		if ( $this->hasCookie() ) {
			$hash = $this->TwitterAuthModel->getHash();
			$twitterUserId = $this->TwitterAuthModel->getUserTwitterIdByHash( $hash );

			$this->generateTwitterCookie( $twitterUserId );

			if ( $wgUser->getId() > 0 ) {
				$this->associateWHUserId( $twitterUserId, $wgUser->getId() );
			}
			$isAuthenitcated = $twitterUserId ? true : false;
		}
		else if ( $this->isLoggedIn() ) {
			if ( $this->isAuthorized( $wgUser->getId() ) ) {
				$twitterUserId = $this->TwitterAuthModel->getUserTwitterIdByWHUserId( $wgUser->getId() );
				
				$this->generateTwitterCookie( $twitterUserId );

				$isAuthenitcated = true;
			}
		}

		return $isAuthenitcated;
	}

	private function isAuthorized( $whUserId )
	{
		$token = $this->TwitterAuthModel->getUserToken( $whUserId, 'wikihow_user_id' );
		$secret = $this->TwitterAuthModel->getUserSecret( $whUserId, 'wikihow_user_id' );

		// TODO: should we check this against twitter
		if ( strlen( $token ) > 0 && strlen( $secret ) > 0 ) {
			return true;
		}
		else {
			return false;
		}
	}

	private function hasCookie()
	{
		if ( !empty( $_COOKIE[TRCOOKIE] ) && $this->TwitterAuthModel->isValidHash( $_COOKIE[TRCOOKIE] ) ) {
			return true;
		}
		else {
			return false;
		}
	}

	private function isLoggedIn()
	{
		global $wgUser;

		if ( $wgUser->getId() > 0 ) {
			return true;
		}
	}

	private function associateWHUserId( $twitterUserId, $whUserId )
	{
		if ( empty( $_SESSION['have_associated'] ) && $whUserId > 0 ) {
			$this->TwitterAuthModel->updateWHUserId( $twitterUserId, $whUserId );
			$_SESSION['have_associated'] = true;
		}
	}

	function reply( $tweet, $replyStatusId )
	{
		$twitterUserId = $this->TwitterAuthModel->getUserTwitterIdByHash( $_COOKIE[TRCOOKIE] );
		$token = $this->TwitterAuthModel->getUserToken( $twitterUserId );
		$secret = $this->TwitterAuthModel->getUserSecret( $twitterUserId );

		$this->generateTwitterCookie( $twitterUserId );
		if ( !empty( $secret ) && strlen( $secret ) > 0 && !empty( $token ) && strlen( $token ) > 0 ) {

			$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET, $token, $secret );

			$replyTweet = array( );
			$replyTweet['status'] = $tweet;
			$replyTweet['in_reply_to_status_id'] = $replyStatusId;
			try {
				// TODO: uncomment, this is just for testing
				$resp = $twitterObj->post_statusesUpdate( $replyTweet );
				$this->TweetModel->insertReplyTweet( $resp->response, $replyStatusId );
			}
			catch ( EpiTwitterException $e ) {
				echo $e->getMessage();
			}
			catch ( Exception $e ) {
				echo $e->getMessage();
			}
		}
	}

	function getSuggestedTitles( $tweet )
	{
		if ( strlen( $tweet ) > 0 ) {
			// clean up tweet
			$tweet = str_replace( "\n", "", $tweet );
			$tweet = trim( $tweet );
			
			// remove search word
			//$tweet = preg_replace('/<b>(.*?)<\/b> /is', '', $tweet);
			// $tweet = $this->removeStopWords( $tweet );
			
			$emoticons = array( ':)', '=O' );

			foreach ( $emoticons as $emoticon ) {
				$tweet = str_replace( $emoticon, "", $tweet );
			}
			
			// attmp to get search words + x number of words after
//		el( $eTweet, '1st explode' );
//		$eTweet = $this->removeStopWords( $eTweet );
//		el( $eTweet, 'Removed stop words' );

			$data = array( );
			$data['tweet'] = null;
			$data['results'] = array( );

			// parse the sentences into questions/senteces
			/*
			$eTweet = array( );
			$eTweet = preg_split( "/(?<!\..)([\?\!\.]+)\s(?!.\.)/", $tweet, -1, PREG_SPLIT_DELIM_CAPTURE );

			if ( is_array( $eTweet ) && count( $eTweet ) > 0 ) {
				$questions = array( );
				$sentences = array( );
				
				for ( $i = 0; $i < count( $eTweet ); $i++ ) {

					$piece = trim( $eTweet[$i] );

					if ( strlen( $piece ) > 0 ) {
						// remove non alpha characters
						$piece = preg_replace( "/[^a-zA-Z0-9\s]/", "", $piece );
						$nextKey = $i + 1;
						el( $piece . " - " . substr_count( $piece, ' ' ), __LINE__ );

						if ( $eTweet[$nextKey] == '?' && substr_count( $piece, ' ' ) >= self::MIN_WORDS ) {
							$questions[] = $piece;
						}
						else if ( substr_count( $piece, ' ' ) >= self::MIN_WORDS ) {
							$sentences[] = $piece;
						}
					}
					// skip over sentence ender
					$i++;
				}
			}

			// search for questions first
			$eTweet = array_merge( $questions, $sentences );
			
			if ( is_array( $eTweet ) && count( $eTweet ) > 0 ) {
				foreach ( $eTweet as $tweet ) {

					$search = new LSearch();
					$results = $search->googleSearchResultTitles( $tweet, 0, self::NUM_WH_SEARCH_RESULTS );

					$data['tweet'] = !empty( $data['tweet'] ) ? $data['tweet'] . '; ' . $tweet : $tweet;
					$data['results'] = array_merge( $data['results'], $results );
					el( $results, $tweet );
				}
			}
			 
			 */
			
			// single search string, ie user types in search string
			$search = new LSearch();
			$results = $search->googleSearchResultTitles( $tweet, 0, self::NUM_WH_SEARCH_RESULTS );
			
			$data['tweet'] = !empty( $data['tweet'] ) ? $data['tweet'] . '; ' . $tweet : $tweet;
			$data['results'] = array_merge( $data['results'], $results );
					
			return $data;
		}
		else {
			throw new InvalidArgumentException( 'tweet cannot be null' );
		}
	}

	function removeStopWords( $tweet )
	{
		if ( strlen( $tweet ) > 0 ) {
			$eTweet = explode( ' ', $tweet );
			
			$stopWords = getSearchKeyStopWords();
			$stopWords['#lazyweb'] = 1;

			array_walk( $eTweet, 'trim' );

			$eTweet = array_flip( $eTweet );

			$diff = array_diff_key( $eTweet, $stopWords );
			$eTweet = array_flip( $diff );
			
			$tweet = implode( ' ', $eTweet );
			
			return $tweet;
		}
		else {
			throw new InvalidArgumentException( 'tweet cannot be null' );
		}
	}

	function displaySuggestedTitles( $results )
	{
		global $wgServer;

		$data['tweet'] = $results['tweet'];
		$data['results'] = $results['results'];
		$data['wgServer'] = $wgServer;
		$html = TwitterReplierTemplate::html( 'suggestedTitles', $data );

		return $html;
	}

	/**
	 * Generates a unique random number
	 *
	 * @param int $length length of unique number
	 * @param string $pool additional characters to use in pool of characters
	 * @return string
	 */
	function getUniqueId( $length=20, $pool="" )
	{
		// set pool of possible char
		if ( $pool == "" ) {
			$pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$pool .= "abcdefghijklmnopqrstuvwxyz";
			// $pool .= "0123456789";
		}// end if

		mt_srand( ( double ) microtime() * 1000000 );

		$unique_id = "";

		for ( $index = 0; $index < $length; $index++ ) {
			$unique_id .= substr( $pool, (mt_rand() % (strlen( $pool )) ), 1 );
		}// end for
		return($unique_id);
	}

	// TODO: Delete
	function streamCompareForm()
	{
		$html = TwitterReplierTemplate::html( 'streamform', $data );

		return $html;
	}

	function searchInboxQ( $keywords = null, $settings = null )
	{
		if ( !empty( $keywords ) ) {
			$params = array( );
			//$params['terms'] = str_replace( " ", ",", $keywords );
			$params['terms'] = $keywords;
			$params['before'] = !empty( $settings['numDays'] ) ? strtotime( $settings['numDays'] ) : strtotime( 'now' );
			$params['type'] = !empty( $settings['inboxType'] ) ? $settings['inboxType'] : 'qualityInboxQ';

			$url = 'http://api.inboxq.com/v0.4/questions';

			$rr = new RestRequest( $url, 'GET', $params );

			$rr->execute();

			return $rr->displayResponse();
		}
		else {
			return false;
		}
	}

	function displayInboxSearchResults( $results )
	{
		$data['results'] = json_decode( $results );

		return TwitterReplierTemplate::html( 'streamTweetsInboxQ', $data );
	}

	function displayTwitterSearchResults( $results )
	{
		$results = json_decode( $results );

		$data['results'] = $results->results;

		return TwitterReplierTemplate::html( 'streamTweetsTwitter', $data );
	}
	
	private function getUserIdentifier()
	{
		global $wgUser;
		
		$whUserId = $wgUser->getId();
		$hash = $this->TwitterAuthModel->getHash();
		
		if( is_numeric( $whUserId ) ) {
			$userIdentifier = $this->TwitterAuthModel->getUserTwitterIdByWHUserId( $whUserId );
		}
		else if( $hash ) {
			$userIdentifier = $this->TwitterAuthModel->getUserTwitterIdByHash( $hash );
		}
		else {
			$userIdentifier = session_id();
		}
		
		return $userIdentifier;
	}

}
<?php

global $IP;
require_once("$IP/extensions/wikihow/nusoap/nusoap.php");

class gSearch {

	function suggest($terms) {
		global $wgGoogleAPIKey;

		// prepare an array of input parameters to be passed to the remote   procedure
		// doGoogleSearch()
		$params = array(
			'Googlekey' => $wgGoogleAPIKey, // Google license
			'queryStr' => $terms . ' site:www.wikihow.com',  // search term that was being typed
			'startFrom' => 0,               // start from result n
			'maxResults' => 10,             // show a total of 10 results
			'filter' => true,               // remove similar results
			'restrict' => '',               // restrict by topic
			'adultContent' => true,         // remove adult links from search result
			'language' => '',               // restrict by language
			'iencoding' => '',              // input encoding
			'oencoding' => ''               // output encoding
		);

		$skey = strtolower($terms);
		$dbw =& wfGetDB(DB_MASTER);

		// check the cache
		$sql = "SELECT * from sugg where sugg_query=" . $dbw->addQuotes($skey);
		$res = $dbw->query($sql);
		$suggest = null;
		$row = $dbw->fetchObject($res);
		if ($row) {

			// found an item
			$suggest = $row->sugg_suggest;
			$dbw->freeResult($res);
			$sql = "UPDATE sugg set sugg_hits=sugg_hits+1 where sugg_query= " . $dbw->addQuotes($skey)." LIMIT 1;";
			$dbw->query($sql);
		} else {
			return null; // Google SOAP API no longer available

			// not implemented yet, but use Yahoo's spelling suggestion
			// API -- allows 5000 queries per day
			// http://developer.yahoo.com/search/web/V1/spellingSuggestion.html
			//$url = 'http://search.yahooapis.com/WebSearchService/V1/spellingSuggestion?appid=' . WH_YAHOO_API_KEY . '&query=' . urlencode($terms) . '&output=json';
			//$ch = curl_init($url);

			// create entry, make the call to Google
			$soapclient = new soapclient("http://api.google.com/search/beta2");
			$params = array(
				'key' => $wgGoogleAPIKey,
				'phrase' => $terms,
			);
			$spell = $soapclient->call('doSpellingSuggestion', $params, "urn:GoogleSearch", "urn:GoogleSearch");
			$err = $soapclient->getError();

			if ($err) {
				error_log("GoogleAPI: An error occurred! $err {$soapclient->response}\n");
			}

			// cache the result
			if (!is_array($spell)) {
				$suggest = $spell;
			}
			$dbw->insert("sugg", array("sugg_query" => $skey, "sugg_suggest" => $suggest), "gSearch::suggest");
			log_query("SUGG:", $terms);

		}
		//echo "returning $suggest"; exit;
		return $suggest;
	}

	function query($terms, $queryType = 'QUERY:', $site = "site: www.wikihow.com") {
		global $wgGoogleAPIKey;
		global $wgUser;

		// prepare an array of input parameters to be passed to the remote   procedure
		// doGoogleSearch()
		$params = array(
			'Googlekey' => $wgGoogleAPIKey, // Google license
			// key
			'queryStr' => $terms . " $site" ,  // search term that was being typed
			'startFrom' => 0,               // start from result n
			'maxResults' => 10,              // show a total of 10 results
			'filter' => true,               // remove similar results
			'restrict' => '',               // restrict by topic
			'adultContent' => true,        // remove adult links from search result
			'language' => '',              // restrict by language
			'iencoding' => '',             // input encoding
			'oencoding' => ''             // output encoding
		);

		// create a instance of the SOAP client object
		$soapclient = new soapclient("http://api.google.com/search/beta2");

		// uncomment the next line to see debug messages
		// $soapclient->debug_flag = 1;

		$MyResult = null;
		if ($wgUser->getID() > 0)
			$MyResult = $soapclient->call("doGoogleSearch", $params, "urn:GoogleSearch", "urn:GoogleSearch");

		$err = $soapclient->getError();

		if ($err) {
			//error_log("GoogleAPI: An error occurred! $err {$soapclient->response}\n");
			return null;
		}

/* Uncomment next line, if you want to see the SOAP envelop, which is forwarded to Google server, It is important to understand the content of SOAP envelop*/

// echo '<xmp>'.$soapclient->request.'</xmp>';

/* Uncomment next line, if you want to see the SOAP envelop, which is received from Google server. It is important to understand the SOAP packet forwarded from Google Server */

		// echo '<xmp>'.$soapclient->response.'</xmp>';

		// Print the results of the search
		//if ($MyResult['faultstring'])
		// echo $MyResult['faultstring'];

		//echo "errors?" . $MyResult['faultstring'];
		//echo "query: " . $MyResult['searchQuery'];
		//echo "count: " . $MyResult['estimatedTotalResultsCount'];
		//is_array($MyResult['resultElements']))
		//foreach ($MyResult['resultElements'] as $r)
		//echo "<tr><td>[$i] <a href=" . $r['URL'] . ">" . $r['title'] . "</a>";
		//echo $r['snippet'] . "(" . $r['cachedSize'] . ")</td></tr>";


		$result = array();
		$result[] = $MyResult['resultElements'];
		//$result[]= gSearch::suggest($terms);
		//return $MyResult['resultElements'];
		log_query($queryType, $terms);

		return $result;
	}

	function serp($terms, $startFrom = 0) {
		global $wgGoogleAPIKey;
		global $wgUser;

		// prepare an array of input parameters to be passed to the remote   procedure
		// doGoogleSearch()
		$params = array(
			'Googlekey' => $wgGoogleAPIKey, // Google license
			// key
			'queryStr' => $terms ,
			'startFrom' => $startFrom,               // start from result n
			'maxResults' => 10,              // show a total of 10 results
			'filter' => true,               // remove similar results
			'restrict' => '',               // restrict by topic
			'adultContent' => true,        // remove adult links from search result
			'language' => '',              // restrict by language
			'iencoding' => '',             // input encoding
			'oencoding' => ''             // output encoding
		);

		// create a instance of the SOAP client object
		$soapclient = new soapclient("http://api.google.com/search/beta2");

		// uncomment the next line to see debug messages
		// $soapclient->debug_flag = 1;


		$MyResult = null;
		$MyResult = $soapclient->call("doGoogleSearch", $params, "urn:GoogleSearch", "urn:GoogleSearch");
		$err = $soapclient->getError();

		if ($err) {
			//error_log("GoogleAPI: An error occurred! $err {$soapclient->response}\n");
			return null;
		}

/* Uncomment next line, if you want to see the SOAP envelop, which is forwarded to Google server, It is important to understand the content of SOAP envelop*/

// echo '<xmp>'.$soapclient->request.'</xmp>';

/* Uncomment next line, if you want to see the SOAP envelop, which is received from Google server. It is important to understand the SOAP packet forwarded from Google Server */

		// echo '<xmp>'.$soapclient->response.'</xmp>';

		// Print the results of the search
		//if ($MyResult['faultstring'])
		// echo $MyResult['faultstring'];

		//echo "errors?" . $MyResult['faultstring'];
		//echo "query: " . $MyResult['searchQuery'];
		//echo "count: " . $MyResult['estimatedTotalResultsCount'];
		//is_array($MyResult['resultElements']))
		//foreach ($MyResult['resultElements'] as $r)
		//echo "<tr><td>[$i] <a href=" . $r['URL'] . ">" . $r['title'] . "</a>";
		//echo $r['snippet'] . "(" . $r['cachedSize'] . ")</td></tr>";



		$result = array();
		$result[] = $MyResult['resultElements'];
		//$result[]= gSearch::suggest($terms);
		//return $MyResult['resultElements'];

		return $result;
		}
	}

function log_query($type, $query) {
	$dbw =& wfGetDB( DB_MASTER );
	$sql = "INSERT INTO google_api_checker (request) VALUES (" . $dbw->addQuotes($type . " " . $query) . ");";
	$dbw->query($sql);
}


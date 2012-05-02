<?
//
// Grabs a bunch of SERPs.  This is meant to be run once per hour.
//
// Usage: php getSerpsData.php <domain-list-one-per-line> <limit>
//


/*
 * database schema:
CREATE TABLE `google_serps` (
  `gs_id` int(10) unsigned NOT NULL auto_increment,
  `gs_page` int(10) unsigned NOT NULL default '0',
  `gs_query` varchar(255) NOT NULL default '',
  `gs_position` tinyint(3) unsigned NOT NULL default '0',
  `gs_domain` varchar(32) NOT NULL default '',
  `gs_url` varchar(255) NOT NULL default '',
  `gs_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `gs_batch` varchar(6) NOT NULL default '',
  `gs_batch_hour` int(10) NOT NULL default 0,
  PRIMARY KEY  (`gs_id`),
  KEY `gs_page` (`gs_page`),
  KEY `gs_query` (`gs_query`),
  KEY `gs_domain` (`gs_domain`),
  KEY `gs_timestamp` (`gs_timestamp`),
  KEY `gs_batch_batch_hour` (`gs_batch`,`gs_batch_hour`)
);
*/

/**
 * Reads in list of domains we care about
 */
function getDomains($filename) {
	// get the list of domains that we are interested in checking 
	// with these results
	$file_contents = trim(file_get_contents($filename));
	$domains = split("\n", $file_contents);
	$domains = array_flip($domains);
	$domains['wikihow.com'] = 1;
	return $domains;
}

/**
 *  Easy way to turn off debug messages if we don't need them
 */
function debug_msg($msg) {
	global $debug;
	if ($debug) {
		echo date("r") . " - $msg\n";
	}
}

/**
 * Send error messages to stderr if $debug is on
 */
function debug_err($msg) {
	global $debug, $stderr;
	if ($debug) {
		fputs($stderr, date('r') . " - $msg\n");
	}
}

/**
 * Logs error count to database
 */
function logNoResultsError() {
	global $batch, $db;
	$sql = "INSERT INTO serps.errors VALUES ({$db->addQuotes($batch)}, 1) ON DUPLICATE KEY UPDATE errors = errors + 1";
	$db->query($sql, __METHOD__);
}

/**
 *  Probably the best way to get the URL's contents using curl
 */
function getResults($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	//$start = microtime(true);
	$contents = curl_exec($ch);
	//debug_msg("Took " . number_format(microtime(true) - $start, 2) . "s to get results");
	if (curl_errno($ch)) {
		$msg = "curl error {$url}: " . curl_error($ch);
		debug_msg($msg);
		debug_err($msg);
	}
	curl_close($ch);
	return $contents;
}

/**
 *  Throttles connection to Google to avoid being banned as a bot
 */
function throttle() {
	global $throttle;
	if (!$throttle) {
		return;
	}
	if (rand(0, 4) == 0) {
		$x =  rand(0, 10);
		debug_msg("throttle(): sleeping for $x seconds");
		sleep($x);
	}
}

/**
 *  Gets the set of suggestions we want to run the queries on 
 */
function getSuggestions() {
	global $batchhour, $db;

	// 0 .. 167 for 168 hours in the week
	if ($batchhour === '') {
		// calculate current batchhour
		$batchhour = date("w") * 24 + date("G"); 
	}

	// 168 for total hours in the week
	$totalbatches = 7 * 24; 

	if (USE_MEDIAWIKI) {
		// how many queries do divide up into batches? 
		$sql = 'SELECT COUNT(DISTINCT(result)) AS C FROM serps.suggestions WHERE LENGTH(query) < 5';
		$res = $db->query($sql, __METHOD__);
		$row = $res->fetchObject();
		$count = $row->C;
	} else {
		$lines = file(READ_QUERIES_FROM_FILE, FILE_IGNORE_NEW_LINES);
		$count = count($lines);
	}

	// how many do we need to process per batch? 
	$perbatch = ceil($count / $totalbatches); 

	// which batch is this one? 
	$offset = $perbatch * $batchhour; 

	debug_msg("Doing offset, rowcount $offset, $perbatch");  

	if (USE_MEDIAWIKI) {
		$sql = "SELECT DISTINCT(result) AS r FROM serps.suggestions WHERE LENGTH(query) < 5 LIMIT $offset, $perbatch";
		$res = $db->query($sql, __METHOD__);

		$results = array();
		while ($row = $res->fetchObject()) {
			$results[trim($row->r)] = trim($row->r);
		}
	} else {
		$results = array_splice($lines, $offset, $perbatch);
	}

	return $results;
}

/**
 * Extract all URLs from the HTML that look like:
 * <a href="..." class=l>...</a>
 */
function extractUrlsClassL($contents) {
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	$doc->strictErrorChecking = false;
	$doc->recover = true;
	@$doc->loadHTML($contents);
	$xpath = new DOMXPath($doc);
	$nodes = $xpath->query("//h3[@class='r']/a");

	$urls = array();
	foreach ($nodes as $node) {
		$href = $node->getAttribute("href");
		if (!$href || !preg_match('@^http://@', $href)) continue;
		$urls[] = $href;
	}

	return processURLs($urls);
}

// hack for callback + global because php 5.2 doesn't have closures / anon funcs
function urlCallback($m) {
	global $urls;
	$urls[] = $m[1];
}

/**
 * Extract URLs that start with /url?... This is google's way of
 * tracking clicks on results for their own data/testing.
 */
function extractUrlsTracked($contents) {
	global $urls;
	$urls = array();
	preg_replace_callback('@/url\?[^=]+=([^&]*)@m', 'urlCallback', $contents);

	// if there weren't any such URLs, it's not unexpected
	if (count($urls) == 0) {
		return array(null, 0);
	}

	foreach ($urls as $i=>$href) {
		if ($href && preg_match('@^https?://@', $href)) continue;
		unset($urls[$i]);
	}

	return processURLs($urls);
}

/**
 * Add meta data to each URL about the domains we're tracking and what
 * search position its in.
 */
function processURLs($urls) {
	global $domains;

	$results = array();
	$count = 0;
	foreach ($urls as $href) {
		foreach ($domains as $d => $x) {
			$d = trim($d); 
			if (!$d) continue;

			// add domain to results with first position it appears
			if (strpos($href, $d) !== false && !isset($results[$d])) {
				$results[$d] = array(
					'domain' => $d,
					'position' => $count + 1,
					'url' => $href,
				);
			}
		}
		$count++;
	}
	return array($results, $count);
}

/**
 * Given a query, grabs the search results from Google and looks for 
 * the results according to the domains we are interested in
 */
function checkGoogle($query, $page_id) {
	global $batch, $batchhour, $db;
	static $tries = 0; 
	$tries++;

	$url = "http://www.google.com/search?q=" . urlencode($query) . "&num=" . RESULTS_PER_PAGE;

	// pull the html from google.com
	$contents = getResults($url);

	if (preg_match("@<TITLE>302 Moved</TITLE>@i", $contents)) {
		// uh oh, we have worn out our welcome -- they want to captcha us
		logNoResultsError();
		$msg = "$tries: UH oh, we have been found out! Sleep for 60s.";
		debug_msg($msg);
		debug_err($msg);
		sleep(60);
		return;
	}

	if ($contents == null) {
		debug_err('got null contents from google');
		return null;
	}

	list($results, $nodeCount) = extractUrlsClassL($contents);

	debug_msg("$tries: checking $query, $nodeCount");
	if (!$nodeCount) {
		list($results, $nodeCount) = extractUrlsTracked($contents);
		debug_msg("(retried parsing) $tries: checking $query, $nodeCount");
		if (!$nodeCount) {
			$contents = preg_replace('@[\r\n]+@', ' ', $contents);
			debug_err('got no results in google html!  html=' . $contents);
			return null;
		}
	}

	foreach ($results as $r) {
		$sql = "INSERT INTO serps.google_serps 
				  (gs_page, gs_query, gs_position, gs_domain, 
					gs_url, gs_batch, gs_batch_hour) 
				VALUES ($page_id,
					{$db->addQuotes($query)},
					{$r['position']},
					'{$r['domain']}',
					{$db->addQuotes($r['url'])}, 
					{$db->addQuotes($batch)},
					{$db->addQuotes($batchhour)});";
		$db->query($sql, __METHOD__);
	}
}

/**
 * Clear all data from the given batch hour, so that it may be overwritten
 */
function clearBatchHourData() {
	global $batch, $batchhour, $db;
	$sql = "DELETE FROM serps.google_serps
			WHERE gs_batch={$db->addQuotes($batch)} AND
				gs_batch_hour={$db->addQuotes($batchhour)};";
	$db->query($sql, __METHOD__);
}

// fake DB handle when MW framework isn't loaded
class FileDB {
	var $fp;

	public function __construct($filename) {
		$this->fp = fopen($filename, 'a');
	}

	public function addQuotes($str) {
		return "'" . str_replace("'", "\\'", $str) . "'";
	}

	public function query($sql, $trace) {
		if (!preg_match("@^select@i", $sql)) {
			fputs($this->fp, $sql . "\n");
		}
	}
}

define('RESULTS_PER_PAGE', 100);

$opts = getopt('d:b:l:hm', array('domains:', 'limit:', 'batch-hour:', 'help', 'no-mediawiki'));
if (isset($opts['h']) || isset($opts['help'])) {
	print "usage: {$argv[0]} [--domains <file>] [--limit <num>] [--batch-hour <num>] [--no-mediawiki]\n";
	exit;
}

$use_mw = !isset($opts['m']) && !isset($opts['no-mediawiki']);
define('USE_MEDIAWIKI', $use_mw);

if (USE_MEDIAWIKI) {
	require_once("commandLine.inc");
} else {
	define('WRITE_RESULTS_TO_FILE', 'relay.txt');
	define('READ_QUERIES_FROM_FILE', dirname(__FILE__) . '/../x/serps-sugg.txt');
}

define('DEFAULT_DOMAINS_FILE', dirname(__FILE__) . '/../x/domains.txt');
if (isset($opts['d'])) $opts['domains'] = $opts['d'];
$domain_file = isset($opts['domains']) ? $opts['domains'] : DEFAULT_DOMAINS_FILE;

if (isset($opts['l'])) $opts['limit'] = $opts['l'];
$limit = isset($opts['limit']) ? $opts['limit'] : 0; 
if (isset($opts['b'])) $opts['batch-hour'] = $opts['b'];
$batchhour = isset($opts['batch-hour']) ? $opts['batch-hour'] : ''; 
$batch = date('YW');

$clearData = $batchhour !== '';
$debug = true; 
$throttle = false;

$stderr = null;
if (!$stderr) $stderr = fopen('php://stderr', 'w');

if (USE_MEDIAWIKI) {
	$db = wfGetDB(DB_MASTER);
} else {
	$db = new FileDB(WRITE_RESULTS_TO_FILE);
}

$domains = getDomains($domain_file);
$suggestions = getSuggestions();

if ($clearData) {
	clearBatchHourData();
}

debug_msg("Starting batch ($batch) hour ($batchhour)");
$count = 0; 
foreach ($suggestions as $s) {
	checkGoogle($s, 0);
	throttle();

	$count++;

	if ($count == $limit) {
		break;
	}
}

debug_msg("Done last ($count)");


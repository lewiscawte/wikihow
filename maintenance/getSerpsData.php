<?
require_once( "commandLine.inc" );

$dbw =& wfGetDB( DB_SLAVE );

// get the list of domains that we are interested in checking with these results
$dfile = isset($argv[0]) ? $argv[0] : "/usr/local/wikihow/domains";

$d = trim(file_get_contents($dfile));
$domains = split("\n", $d);
$domains = array_flip($domains);
$domains['wikihow.com'] = 1;

$limit = isset($argv[1]) ? $argv[1] : 0; 

$debug = true; 
$throttle = false;

/*********
 *  Easy way to turn off debug messages if we don't need them
 *
 */
function debug_msg($msg) {
	global $debug;
	if ($debug) {
		echo date("r") . " - $msg\n";
	}
}

function logError() {
	$dbw = wfGetDB(DB_MASTER);
	$dbw->selectDB('serps');
	$batch = $dbw->addQuotes(date("YW"));
	$sql = "INSERT INTO errors values ($batch, 1) ON DUPLICATE KEY update errors = errors + 1";
	$dbw->query($sql);
}

/*********
 *  Probably the best way to get the URL's contents using CURL
 *
 */
function getResults($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$x = microtime(true);
	$contents = curl_exec($ch);
	#debug_msg("Took " . number_format(microtime(true) - $x, 2) . "s to get results");
	if (curl_errno($ch)) {
		# error
		echo "curl error {$url}: " . curl_error($ch) . "\n";
	} else {

	}
	curl_close($ch);
	return $contents;
}

/*********
 *  Throttles connection to Google to avoid being banned as a bot
 *
 */
function throttle() {
	global $throttle;
	if (!$throttle) {
		return;
	}
	if (rand(0, 4) == 0) {
		$x =  rand(0, 10);
		debug_msg("sleeping for $x seconds");
		#echo("sleeping for $x seconds\n");
		sleep($x);
	}
}

/*********
 *  Gets the set of suggestions we want to run the queries on 
 *
 */
function getSuggestions() {
	$dbr =& wfGetDB( DB_SLAVE );

	// 0 .. 167 for 168 hours in the week
	$batchnum = date("w") * 24 + date("G"); 
	// 168 for total hours in the week
	$totalbatches = 7 * 24; 
	// how many queries do divide up into batches? 
	$res = $dbr->query('select count(distinct(result))  as C from serps.suggestions where length(query) < 5');
	$row = $dbr->fetchObject($res);
	$count = $row->C;
	// how many do we need to process per batch? 
	$perbatch = ceil($count / $totalbatches); 
	// which batch is this one? 
	$offset = $perbatch * $batchnum; 

	debug_msg("Doing offset, rowcount $offset, $perbatch \n");  

	$results = array();
	$sql = " select distinct(result) as r from serps.suggestions where length(query) < 5 LIMIT $offset, $perbatch;";
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchObject($res)) {
		$results[trim($row->r)] = trim($row->r);
	}
	$dbr->freeResult($res);
	return $results;
}

/*********
 *  Given a query, grabs the search results from Google and looks for the results according to the domains
 *  we are interested in
 *
 */
$tries = 0;
function checkGoogle($query, $page_id, $domains, $dbw) {
	global $tries; 
	$tries++;
	$url = "http://www.google.com/search?q=" . urlencode($query) . "&num=100";

	// get the contents
	$contents = getResults($url);

	if (preg_match("@<TITLE>302 Moved</TITLE>@i", $contents)) {
		// uh oh, we have worn out our welcome
		logError();
		debug_msg("$tries: UH oh, we have been found out! Sleep for 60s. "); 
		sleep(60);
		return;
	}
	$contents = getResults($url); 
	if ($contents == null) {
		return null;
	}
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	$doc->strictErrorChecking = false;
	$doc->recover = true;
	@$doc->loadHTML($contents);
	$xpath = new DOMXPath($doc);
	$nodes = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " l")]');
	$index = 1;
	$results = array();
	#echo $contents; 
	foreach ($nodes as $node) {
		$href = $node->getAttribute("href");
		#echo "HREF: $href $index\n"; 
		foreach($domains as $d => $x) {
			// thanks file_get_contents
			$d = trim($d); 
			if ($d == "") {
				continue;
			}
			if (strpos($href, $d) !== false && !isset($results[$d])) {
				$r = array();
				$r['domain'] = $d;
				$r['position'] = $index;
				$r['url'] = $href;
				$results[$d] = $r;
			}
		}
		$index++;
	}
	debug_msg("$tries: checking $query, " . $index);
	if ($index == 1) {
		echo $contents;
		exit;
	}
	foreach ($results as $r) {	
		$sql = "INSERT INTO serps.google_serps (gs_page, gs_query, gs_position, gs_domain, gs_url, gs_batch) 
			VALUES ($page_id,
					{$dbw->addQuotes($query)},
					{$r['position']},
					'{$r['domain']}',
					{$dbw->addQuotes($r['url'])}, 
					{$dbw->addQuotes(date("YW"))}
				);" ;
		$dbw->query($sql);
	}
	return $results;
}

echo date("r") . " - Starting \n";
$suggestions = getSuggestions();
$count = 0; 
foreach ($suggestions as $s) {
	checkGoogle($s, 0, $domains, $dbw);
	throttle();
	$count++;
	if ($count % 100 == 0) {
		echo date("r") . " - Done $count\n";
	}
	if ($count == $limit) {
		break;
	}
}

echo date("r") . " - Done\n";

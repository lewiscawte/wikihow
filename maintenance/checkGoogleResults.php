<?
function format_data($mysql_timestamp){    preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $mysql_timestamp,$pieces);    $unix_timestamp = mktime($pieces[4], $pieces[5], $pieces[6], $pieces[2], $pieces[3], $pieces[1]);
    return($unix_timestamp);
}

require_once( "commandLine.inc" );

$dbw =& wfGetDB( DB_SLAVE );
$d = trim(file_get_contents($argv[0]));
$domains = split("\n", $d);
$domains = array_flip($domains);
$domains['wikihow.com'] = 1;

function throttle() {
		$x =  rand(0, 15);
		if ($x == 10) {
			$s = rand(1,30);
			echo "sleeping for $s seconds\n";
			sleep($s);
		}
}

function getSuggestions() {

	$results = array();
	$sql = " select distinct(result) as r from serps.suggestions where length(query) < 5;";
	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchObject($res)) {
		$results[trim($row->r)] = trim($row->r);
	}
	$dbr->freeResult($res);
	return $results;
}

function getTitles($num = 1000) {
	$sql = "select page_id from page where page_is_redirect=0 and page_namespace=0 order by rand() limit $num;"; // default 500
	
	$dbr =& wfGetDB( DB_SLAVE );
	$titles = array();
	$res = $dbr->query($sql);
     while( $row = $dbr->fetchObject( $res ) ) {
            $titles[] = Title::newFromID($row->page_id); 
     }
	$dbr->freeResult( $res );
}

function checkGoogle($query, $page_id, $domains, $dbw) {
		$url = "http://www.google.com/search?q=" . urlencode($query) . "&num=100";
		$contents = file_get_contents($url);
		$matches = array();
		$preg = "/href=\"http:\/\/[^\"]*\"*/";
		$preg = "/href=\"http:\/\/[^\"]*\" class=l */ ";
		preg_match_all($preg, $contents, $matches);

//print_r($matches); exit;	
echo "checking $query\n";
		$count = 0;
		$results = array();
		foreach ($matches[0] as $url) {
			$url = substr($url, 6, strlen($url) - 7);
			$url = str_replace('" class=', '', $url);

				// check for cache article
			if (strpos($url, "/search?q=cache") !== false || strpos($url, "google.com/") !== false) 
				continue;
			$count++;		
			$domain = str_replace("http://", "", $url); 
			$domain = substr($domain, 0, strpos($domain, '/'));
			foreach($domains as $d=>$index) {
				if (strpos($domain, $d) !== false && !isset($results[$d])) {
					$r = array();
					$r['domain'] = $d;
					$r['position'] = $count;
					$r['url'] = $url;
					$results[$d] = $r;
				}
			}
		}		

		foreach ($results as $r) {	
			$sql = "INSERT INTO serps.google_serps (gs_page, gs_query, gs_position, gs_domain, gs_url) 
				VALUES ($page_id,
						{$dbw->addQuotes($query)},
						{$r['position']},
						'{$r['domain']}',
						{$dbw->addQuotes($r['url'])}
					);" ;
			$dbw->query($sql);
		}
echo "adding " . sizeof($results) . " for $query\n";

}
if (false && isset($args[0]) && file_exists($args[0])) {
	//load queries from file
	$contents = file_get_contents($args[0]);
	$lines = split("\n", $contents);
	foreach($lines as $line) {
		checkGoogle($line, 0, $domains, $dbw);
		throttle();
	}	
} else if (isset($argv[1]) && $argv[1] == "--use-suggestions") {
	$suggestions = getSuggestions();
	foreach ($suggestions as $s) {
		checkGoogle($s, 0, $domains, $dbw);
		throttle();
	}
} else {
	// load queries from the database
	$titles = getTitles($args[0]);	
	foreach($titles as $title) {
		checkGoogle ($title->getText(), $title->getArticleID(), $domains, $dbw);
		throttle();
	}
}

?>


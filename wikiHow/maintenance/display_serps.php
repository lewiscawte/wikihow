<?

require_once('commandLine.inc');


function oldBatch($batch) {
	$year = substr($batch, 0, 4); 
	$week = substr($batch, 4, 2);
	if ($week == "01") {
		$week = "12";
		$year = (int)($year) - 1;
	} else {
		$week = (int) $week - 1;
	}
	if (strlen($week) < 2) {
		$week = "0" . $week;
	}
	return $year . $week;
}

echo '<style type="text/css" media="all">/*<![CDATA[*/ @import "http://wikidiy.com/serps.css"; /*]]>*/</style>';

# what date are we running this report for anyway? 
if (isset($argv[0])) {
	$batch = $argv[0];
} else {
	echo "need a batch number";
	return;
}

$oldbatch = oldBatch($batch); 

echo "<div id='serps' style= 'font-family: Georgia;'><h1 style='background: #E7EDFF;'>wikiHow SERPs Report for $batch</h1>\n";

$dbr = wfGetDB(DB_SLAVE);

# get the distribution for given domain and range of results
# for example, can show the # of results that wikihow in the 5-10 range of search results
function getDomainRange($domain, $r, $batch) {
	global $dbr;
	$sql = "select count(*) from google_serps where gs_batch='{$batch}' and ";
	if (sizeof($r) == 1) 
		$sql .= " gs_position > {$r[0]} ";
	else
		$sql .= " gs_position between {$r[0]} and {$r[1]}";
	$sql .= " and gs_domain='{$domain}';";
	$res = $dbr->query($sql);
	$row = $dbr->fetchRow($res);

	$results = "";
	if (sizeof($r) == 1) 
		$results .= " > {$r[0]} ";
	else if ($r[0] == $r[1]) 
		$results .= "#{$r[0]}";
	else
		$results .= "#{$r[0]}-{$r[1]}";
	$results = "<td style='border-bottom: 1px solid #CCC;'>{$results}</td><td style='border-bottom: 1px solid #CCC;'>" . number_format($row[0], 0, "", ",") . "</td>\n";
	return $results;
}

# change the db and set up the overall # of hits
$dbr->selectDB('serps');
$res = $dbr->query("select count(*) from google_serps where gs_batch='{$batch}'");
echo "<center><b>Total hits</b>:  &nbsp;";
$row = $dbr->fetchRow($res);
echo number_format($row[0], 0, "", ",");;
echo "<br/>\n";

$oldhits = $dbr->selectField("google_serps", "count(*) as C", array('gs_batch' => $oldbatch));
echo "<center><b>Total hits last report</b>:  &nbsp;";
echo number_format($oldhits, 0, "", ",");;
echo "<br/>\n";


// get the domain averages for the last week of data
$sql = "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where gs_batch='{$oldbatch}' group by gs_domain order by AVG;";
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$x = array();
	$x['results'] = $row->results;
	$x['avg'] = $row->AVG;
	$old[$row->domain] = $x;
}

# the main table of the report
echo "<h2>Results by domain</h2>\n";
$sql= "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where gs_batch='{$batch}' group by gs_domain order by AVG;";
$res = $dbr->query($sql);
$s = "";
while ($row = $dbr->fetchObject($res)) {
	$domain = $row->domain;
	$results = $row->results;
	$avg = $row->AVG;
	$s .= "<tr><td style='border-bottom: 1px solid #CCC;'>{$domain}</td><td style='border-bottom: 1px solid #CCC;'>";
	
	$p1 = $p2 = $p3 = $p4= "-";
	if (isset($old[$domain])) {
		# the number of results
		$p1 = $old[$domain]['results'];
		$p2 = number_format( ($results - $p1) / $p1, 2);
		if ($p2 > 0) $p2 = "&uarr; " . ($p2 * 100) . "%";
		else $p2 = "&darr; " . ($p2 * 100) . "%";
	
		# the average of those results
		$p3 = $old[$domain]['avg'];
		$p4 = number_format( ($avg - $p3) / $p3, 2);
		if ($p4 > 0) $p4 = "&uarr; " . ($p4 * 100) . "%";
		else $p4 = "&darr; " . ($p4 * 100) . "%";
		$p1 = number_format($p1, 0, "", ",");
	}
	$results = number_format($results, 0, ".", ",");
	$s .= "{$results}</td><td style='border-bottom: 1px solid #CCC;'>{$p1}</td><td style='border-bottom: 1px solid #CCC;'>{$p2}</td><td style='border-bottom: 1px solid #CCC;'>{$avg}</td><td style='border-bottom: 1px solid #CCC;'>{$p3}</td><td style='border-bottom: 1px solid #CCC;'>{$p4}</td></tr>\n";	
}

echo "\n\n<br/><table style='margin-left:auto; margin-right:auto; width: 80%;'><tr style='font-weight: bold; background: #CCC;'><td style='border-bottom: 1px solid #CCC;'>Domain</td><td style='border-bottom: 1px solid #CCC;'># of results</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>%change</td><td style='border-bottom: 1px solid #CCC;'>Avg. position</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>% change</td></tr>$s</table><br/><br/>\n";


# get the distribution of wikihow's results for the past week
$ranges = array(
	array(1, 1),
	array(2, 2),
	array(3, 3),
	array(4, 4),
	array(5, 5),
	array(6, 10),
	array(11, 20),
	array(20)
	);


echo "<h2>WikiHow's distribution of results</h2><table style='margin-left:auto; margin-right:auto; width: 80%;'><tr class='top_row'><td style='border-bottom: 1px solid #CCC;'>Position</td><td style='border-bottom: 1px solid #CCC;'>Number</td></tr>\n\n";
foreach ($ranges as $r) {
	echo "<tr>" . getDomainRange("wikihow.com", $r, $batch) . "</tr>";
}
echo "</table>\n";

# #1 results by domain and the % of their change
echo "<h2>#1 Results by domain</h2>\n";
$old = array();
$sql = "select gs_domain as domain, count(*) as num from google_serps where gs_batch='{$oldbatch}' and gs_position=1 group by gs_domain order by num desc;";
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$old[$row->domain] = $row->num;
}

$sql = "select gs_domain as domain, count(*) as num from google_serps where gs_batch='{$batch}' and gs_position=1 group by gs_domain order by num desc;";
$res = $dbr->query($sql);
$s = "";
while ($row = $dbr->fetchObject($res)) {
	$s .= "<tr><td style='border-bottom: 1px solid #CCC;'>{$row->domain}</td><td style='border-bottom: 1px solid #CCC;'>" . number_format($row->num, 0, "", ",");
	$p1 = $p2 = "-";
	if (!isset($old[$row->domain]) || $old[$row->domain] == 0) {
		$p1 = "0";
		$p2 = "- &#8734;%";
	} else {
		$p1 = $old[$row->domain];
		$p2 = ($row->num - $p1) / $p1;
		if ($p2 > 0)
			$p2 = "&uarr; ".  number_format($p2 * 100, 2) . "%";
		else
			$p2 = "&darr; ".  number_format($p2 * 100, 2) . "%";
		$p1 = number_format($old[$row->domain], 0, "", ",");
	}
	$s .= "<td style='border-bottom: 1px solid #CCC;'>{$p1}</td><td style='border-bottom: 1px solid #CCC;'>{$p2}</td></tr>";
}

echo "<table style='margin-left:auto; margin-right:auto; width: 80%;'><tr style='font-weight: bold; background: #CCC;'><td style='border-bottom: 1px solid #CCC;'>Domain</td><td style='border-bottom: 1px solid #CCC;'>Number</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>% change</td></tr>\n$s</table>\n\n";

echo "</div>\n";


/******
	THOSE THAT WERE #1 LAST WEEK BUT ARE NO LONGER
*****/ 

$sql = "select gs_query from google_serps where gs_batch='{$oldbatch}' and gs_position=1 and gs_domain='wikihow.com';";
$res = $dbr->query($sql);
$numones_queries = array();
while ($row = $dbr->fetchObject($res)) {
	$numones_queries[] = $row->gs_query;	
}

$fallen = array();
foreach ($numones_queries as $q) {
	$sql = "select gs_position from google_serps where gs_batch='{$batch}' and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
	$res = $dbr->query($sql);
	if ($row = $dbr->fetchObject($res)) {
		if ($row->gs_position != 1) {
			$fallen[$q] = $row->gs_position;
		}
	} else{
		$fallen[$q] = 'No result found.';
	}	
}
echo "<h2>Results from wikiHow that have fallen from  #1 this past week</h2>
	<table style='margin-left:auto; margin-right:auto; width: 80%;'><tr><td><ul>";
foreach ($fallen as $q=>$n) {
	echo "<li>{$q} - Was 1, now is $n (<a target='new' href='http://www.google.com/search?q=" . urlencode($q) . "'>double check</a>)</li>\n";
}
echo "</td></tr></table>";



/******
	THOSE THAT WERE RANKED 1-10 LAST WEEK BUT ARE NO LONGER
*****/ 

$sql = "select gs_query, gs_position from google_serps where gs_batch='{$oldbatch}' and gs_position between 1 and 10  and gs_domain='wikihow.com';";
$res = $dbr->query($sql);
$numones_queries = array();
while ($row = $dbr->fetchObject($res)) {
    $numones_queries[$row->gs_query] = $row->gs_position;
}

$fallen = array();
foreach ($numones_queries as $q=>$pos) {
    $sql = "select gs_position from google_serps where gs_batch='{$batch}' and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
    $res = $dbr->query($sql);
    if ($row = $dbr->fetchObject($res)) {
        if ($row->gs_position > 10) {
            $fallen[$q] = "Was $n, now is $row->gs_position";
        }
    } else{
        $fallen[$q] = "Was $n, now no result found. ";
    }
}
echo "<h2>Results from wikiHow have fallen from the first page of results in the past week</h2>
    <table style='margin-left:auto; margin-right:auto; width: 80%;'><tr><td><ul>";
foreach ($fallen as $q=>$n) {
	echo "<li>{$q} - $n (<a target='new' href='http://www.google.com/search?q=" . urlencode($q) . "'>double check</a>)</li>\n";
}
echo "</td></tr></table>";



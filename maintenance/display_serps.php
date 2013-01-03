<?

require_once('commandLine.inc');


echo '<style type="text/css" media="all">/*<![CDATA[*/ @import "http://wikidiy.com/serps.css"; /*]]>*/</style>';

# what date are we running this report for anyway? 
if (isset($argv[1])) {
	$dt = $argv[1];
} else {
	$dt = date("Y-m-d");
}

echo "<div id='serps' style= 'font-family: Georgia;'><h1 style='background: #E7EDFF;'>wikiHow SERPs Report for $dt</h1>\n";

$dbr = wfGetDB(DB_SLAVE);

# get the distribution for given domain and range of results
# for example, can show the # of results that wikihow in the 5-10 range of search results
function getDomainRange($domain, $r, $dt) {
	global $dbr;
	$sql = "select count(*) from google_serps where datediff('{$dt}', gs_timestamp) <= 7 and ";
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
$res = $dbr->query("select count(*) from google_serps where datediff('$dt', gs_timestamp) <= 7;");
echo "<center><b>Total hits</b>:  &nbsp;";
$row = $dbr->fetchRow($res);
echo number_format($row[0], 0, "", ",");;
echo "<br/>\n";

// get the domain averages for the last week of data
$sql = "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where datediff('{$dt}', gs_timestamp) between 8 and 15 group by gs_domain order by AVG;";
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$x = array();
	$x['results'] = $row->results;
	$x['avg'] = $row->AVG;
	$old[$row->domain] = $x;
}

# the main table of the report
echo "<h2>Results by domain</h2>\n";
$sql= "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where datediff('{$dt}', gs_timestamp) <= 7 group by gs_domain order by AVG;";
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
	echo "<tr>" . getDomainRange("wikihow.com", $r, $dt) . "</tr>";
}
echo "</table>\n";

# #1 results by domain and the % of their change
echo "<h2>#1 Results by domain</h2>\n";
$old = array();
$sql = "select gs_domain as domain, count(*) as num from google_serps where datediff('{$dt}', gs_timestamp) between 8 and 15 and gs_position=1 group by gs_domain order by num desc;";
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$old[$row->domain] = $row->num;
}

$sql = "select gs_domain as domain, count(*) as num from google_serps where datediff('$dt', gs_timestamp) <= 7 and gs_position=1 group by gs_domain order by num desc;";
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
	TRACKING QUERIES
*****/ 
$track = array();
$sql = "select distinct(result) from suggestions where track=1";
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$track[] = $row->result;	
}

echo "<h2>Queries that are being tracked - last 5 weeks</h2><table width='100%' style=''><tr>";
$s = 'padding: 3px; border: 1px solid #000;';
$count = 0;
foreach ($track as $t) {
	$res = $dbr->query("select gs_position, substr(gs_timestamp, 1, 10) as gs_timestamp from google_serps where gs_query=" . $dbr->addQuotes($t) . " and gs_domain='wikihow.com' order by gs_timestamp desc limit 5");
	if ($count % 2 == 1) 	
		echo "<td style='$s background: #ccc;'><b>{$t}</b><ul>\n";
	else 
		echo "<td style='$s'><b>{$t}</b><ul>\n";
	while ($row = $dbr->fetchObject($res)) {
		echo "<li>{$row->gs_timestamp} - {$row->gs_position}</li>\n";	
	}
	echo "<ul></td>\n";
	$count++;
	if ($count % 4 == 0) 
		echo "</tr><tr>\n";
}
echo "</tr></table>\n";
	


/******
	THOSE THAT WERE #1 LAST WEEK BUT ARE NO LONGER
*****/ 

$sql = "select gs_query from google_serps where datediff('{$dt}', gs_timestamp) between 8 and 15 and gs_position=1 and gs_domain='wikihow.com';";
$res = $dbr->query($sql);
$numones_queries = array();
while ($row = $dbr->fetchObject($res)) {
	$numones_queries[] = $row->gs_query;	
}

$fallen = array();
foreach ($numones_queries as $q) {
	$sql = "select gs_position from google_serps where datediff('$dt', gs_timestamp) <= 7 and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
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

$sql = "select gs_query, gs_position from google_serps where datediff('{$dt}', gs_timestamp) between 8 and 15 and gs_position between 1 and 10  and gs_domain='wikihow.com';";
$res = $dbr->query($sql);
$numones_queries = array();
while ($row = $dbr->fetchObject($res)) {
    $numones_queries[$row->gs_query] = $row->gs_position;
}

$fallen = array();
foreach ($numones_queries as $q=>$pos) {
    $sql = "select gs_position from google_serps where datediff('{$dt}', gs_timestamp) <= 7 and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
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



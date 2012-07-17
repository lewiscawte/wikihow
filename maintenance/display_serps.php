<?php
//
// Output the SERP for the past week. This is called by a maintenance
// shell script called serps_report.sh and the output is both emailed
// and stored for later reference as an HTML file. The generated
// report shows how we've been doing in our SERP analysis.
//

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

// Get the distribution for given domain and range of results.
// For example, can show the # of results that wikihow in the
// 5-10 range of search results.
function getDomainRange($dbr, $domain, $r, $batch) {
	$sql = "select count(*) from google_serps where gs_batch='{$batch}' and ";
	if (sizeof($r) == 1)
		$sql .= " gs_position > {$r[0]} ";
	else
		$sql .= " gs_position between {$r[0]} and {$r[1]}";
	$sql .= " and gs_domain='{$domain}';";
	$res = $dbr->query($sql, __METHOD__);
	$row = $res->fetchRow();

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

function displaySerps($dbr, $oldbatch, $batch) {
	echo "<div id='serps' style= 'font-family: Georgia;'><h1 style='background: #E7EDFF;'>wikiHow SERPs Report for $batch</h1>\n";

	$sql = "select count(*) from google_serps where gs_batch='{$batch}'";
	$res = $dbr->query($sql, __FILE__);
	echo "<center>\n";
	echo "<b>Total hits</b>:  &nbsp;";
	$row = $res->fetchRow();
	echo number_format($row[0], 0, "", ",");;
	echo "<br/>\n";

	$oldhits = $dbr->selectField("google_serps", "count(*) as C", array('gs_batch' => $oldbatch));
	echo "<b>Total hits last report</b>:  &nbsp;";
	echo number_format($oldhits, 0, "", ",");;
	echo "<br/>\n";


	// get the domain averages for the last week of data
	$sql = "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where gs_batch='{$oldbatch}' group by gs_domain order by AVG;";
	$res = $dbr->query($sql, __FILE__);
	foreach ($res as $row) {
		$x = array();
		$x['results'] = $row->results;
		$x['avg'] = $row->AVG;
		$old[$row->domain] = $x;
	}

	$green_span = '<span style="color:#337700;">';
	$red_span = '<span style="color:#990000;">';
	$up_arrow = '&#8593;'; // was &uarr;
	$down_arrow = '&#8595;'; // was &darr;
	$infinity = '&#8734;';

	// the main table of the report
	echo "<h2>Results by domain</h2>\n";
	$sql= "select gs_domain as domain, count(*) as results, avg(gs_position) as AVG from google_serps where gs_batch='{$batch}' group by gs_domain order by AVG;";
	$res = $dbr->query($sql, __FILE__);
	$s = "";
	foreach ($res as $row) {
		$domain = $row->domain;
		$results = $row->results;
		$avg = $row->AVG;
		$s .= "<tr><td style='border-bottom: 1px solid #CCC;'>{$domain}</td><td style='border-bottom: 1px solid #CCC;'>";

		$p1 = $p2 = $p3 = $p4= "-";
		if (isset($old[$domain])) {
			// the number of results
			$p1 = $old[$domain]['results'];
			$percent = number_format( ($results - $p1) / $p1, 2);
			if ($percent > 0) $p2 = $green_span . $up_arrow . " ";
			elseif ($percent == 0) $p2 = '<span>';
			else $p2 = $red_span . $down_arrow . " ";
			$p2 .= ($percent * 100) . "%</span>";

			// the average of those results
			$p3 = $old[$domain]['avg'];
			$percent = number_format( ($avg - $p3) / $p3, 2);
			if ($percent > 0) $p4 = $red_span . $up_arrow . " ";
			elseif ($percent == 0) $p4 = '<span>';
			else $p4 = $green_span . $down_arrow . " ";
			$p4 .= ($percent * 100) . "%</span>";
			$p1 = number_format($p1, 0, "", ",");
		}
		$results = number_format($results, 0, ".", ",");
		$s .= "{$results}</td><td style='border-bottom: 1px solid #CCC;'>{$p1}</td><td style='border-bottom: 1px solid #CCC;'>{$p2}</td><td style='border-bottom: 1px solid #CCC;'>{$avg}</td><td style='border-bottom: 1px solid #CCC;'>{$p3}</td><td style='border-bottom: 1px solid #CCC;'>{$p4}</td></tr>\n";
	}

	echo "\n\n<br/><table style='margin-left:auto; margin-right:auto; width: 80%;'><tr style='font-weight: bold; background: #CCC;'><td style='border-bottom: 1px solid #CCC;'>Domain</td><td style='border-bottom: 1px solid #CCC;'># of results</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>%change</td><td style='border-bottom: 1px solid #CCC;'>Avg. position</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>% change</td></tr>$s</table><br/><br/>\n";


	// get the distribution of wikihow's results for the past week
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
		echo "<tr>" . getDomainRange($dbr, "wikihow.com", $r, $batch) . "</tr>";
	}
	echo "</table>\n";

	// #1 results by domain and the % of their change
	echo "<h2>#1 Results by domain</h2>\n";
	$old = array();
	$sql = "select gs_domain as domain, count(*) as num from google_serps where gs_batch='{$oldbatch}' and gs_position=1 group by gs_domain order by num desc;";
	$res = $dbr->query($sql, __FILE__);
	foreach ($res as $row) {
		$old[$row->domain] = $row->num;
	}

	$sql = "select gs_domain as domain, count(*) as num from google_serps where gs_batch='{$batch}' and gs_position=1 group by gs_domain order by num desc;";
	$res = $dbr->query($sql, __FILE__);
	$s = "";
	foreach ($res as $row) {
		$s .= "<tr><td style='border-bottom: 1px solid #CCC;'>{$row->domain}</td><td style='border-bottom: 1px solid #CCC;'>" . number_format($row->num, 0, "", ",");
		$p1 = $p2 = "-";
		if (!isset($old[$row->domain]) || $old[$row->domain] == 0) {
			$p1 = "0";
			$p2 = $infinity . "%";
		} else {
			$p1 = $old[$row->domain];
			$percent = ($row->num - $p1) / $p1;
			if ($percent > 0) $p2 = $green_span . $up_arrow . " ";
			elseif ($percent == 0) $p2 = '<span>';
			else $p2 = $red_span . $down_arrow . " ";
			$p2 .= number_format($percent * 100, 2) . "%</span>";
			$p1 = number_format($old[$row->domain], 0, "", ",");
		}
		$s .= "<td style='border-bottom: 1px solid #CCC;'>{$p1}</td><td style='border-bottom: 1px solid #CCC;'>{$p2}</td></tr>\n";
	}

	echo "<table style='margin-left:auto; margin-right:auto; width: 80%;'><tr style='font-weight: bold; background: #CCC;'><td style='border-bottom: 1px solid #CCC;'>Domain</td><td style='border-bottom: 1px solid #CCC;'>Number</td><td style='border-bottom: 1px solid #CCC;'>Last time</td><td style='border-bottom: 1px solid #CCC;'>% change</td></tr>\n$s</table>\n\n";

	echo "</div>\n";
	echo "</center>\n";
}

function displaySpot1Hits($dbr, $outputCSV, $oldbatch, $batch) {
	// Those that were #1 last week but are no longer
	$sql = "select gs_query from google_serps where gs_batch='{$oldbatch}' and gs_position=1 and gs_domain='wikihow.com'";
	$res = $dbr->query($sql, __FILE__);
	$numones_queries = array();
	foreach ($res as $row) {
		$numones_queries[] = $row->gs_query;
	}

	$fallen = array();
	foreach ($numones_queries as $q) {
		$sql = "select gs_position from google_serps where gs_batch='{$batch}' and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
		$res = $dbr->query($sql, __FILE__);
		if ($row = $res->fetchObject()) {
			if ($row->gs_position != 1) {
				$fallen[$q] = $row->gs_position;
			}
		} else {
			$fallen[$q] = 0;
		}
	}

	if ($outputCSV) {
		$stdout = fopen('php://stdout','w');
		fputcsv($stdout, array('Google search', 'New position'));
		foreach ($fallen as $q => $n) {
			$url = "http://www.google.com/search?q=" . urlencode($q);
			fputcsv($stdout, array($url, $n));
		}
		fclose($stdout);
	} else {
		echo "<h2>Results from wikiHow that have fallen from  #1 this past week</h2>";
		echo "<table style='margin-left:auto; margin-right:auto; width: 80%;'><tr><td><ul>";
		foreach ($fallen as $q => $n) {
			$url = "http://www.google.com/search?q=" . urlencode($q);
			if ($n === 0) $n = '(removed)';
			echo "<li>{$q} - Was 1, now is $n (<a target='new' href='$url'>double check</a>)</li>\n";
		}
		echo "</td></tr></table>\n";
	}
}

// Those that were ranked 1-10 last week but are no longer
function displayPage1Hits($dbr, $outputCSV, $oldbatch, $batch) {
	$sql = "select gs_query, gs_position from google_serps where gs_batch='{$oldbatch}' and gs_position between 1 and 10  and gs_domain='wikihow.com';";
	$res = $dbr->query($sql, __FILE__);
	$numones_queries = array();
	foreach ($res as $row) {
		$numones_queries[$row->gs_query] = $row->gs_position;
	}

	$fallen = array();
	foreach ($numones_queries as $q => $pos) {
		$sql = "select gs_position from google_serps where gs_batch='{$batch}' and gs_domain='wikihow.com' and gs_query=" . $dbr->addQuotes($q);
		$res = $dbr->query($sql, __FILE__);
		if ($row = $res->fetchObject()) {
			if ($row->gs_position > 10) {
				$fallen[$q] = array(
					'was' => $pos,
					'now' => $row->gs_position,
				);
			}
		} else {
			$fallen[$q] = array(
				'was' => $pos,
				'now' => 0,
			);
		}
	}

	if ($outputCSV) {
		$stdout = fopen('php://stdout','w');
		fputcsv($stdout, array('Google search', 'Old position', 'New position'));
		foreach ($fallen as $q => $n) {
			$url = "http://www.google.com/search?q=" . urlencode($q);
			fputcsv($stdout, array($url, $n['was'], $n['now']));
		}
		fclose($stdout);
	} else {
		echo "<h2>Results from wikiHow have fallen from the first page of results in the past week</h2>\n";
		echo "<table style='margin-left:auto; margin-right:auto; width: 80%;'><tr><td><ul>\n";
		foreach ($fallen as $q => $n) {
			$url = "http://www.google.com/search?q=" . urlencode($q);
			$posText = $n['now'] !== 0 ?
				"Was {$n['was']}, now is {$n['now']}" :
				"Was {$n['was']}, now no result found.";
			echo "<li>{$q} - $posText (<a target='new' href='$url'>double check</a>)</li>\n";
		}
		echo "</td></tr></table>";
	}
}

function main($batch, $losers_spot1_csv, $losers_page1_csv) {

	$oldbatch = oldBatch($batch);

	// change the db and set up the overall # of hits
	$dbr = wfGetDB(DB_SLAVE);
	$dbr->selectDB('serps');

	if (!$losers_spot1_csv && !$losers_page1_csv) {
		displaySerps($dbr, $oldbatch, $batch);

		displaySpot1Hits($dbr, false, $oldbatch, $batch);
		displayPage1Hits($dbr, false, $oldbatch, $batch);
	} elseif ($losers_spot1_csv) {
		displaySpot1Hits($dbr, true,  $oldbatch, $batch);
	} elseif ($losers_page1_csv) {
		displayPage1Hits($dbr, true,  $oldbatch, $batch);
	}
}

// for what date are we running this report
if (isset($argv[0])) {
	$batch = $argv[0];
} else {
	echo "need a batch number";
	return;
}

$losers_spot1_csv = $losers_page1_csv = false;
if (isset($argv[1])) {
	$losers_spot1_csv = $argv[1] == '--losers-spot1-csv';
	$losers_page1_csv = $argv[1] == '--losers-page1-csv';
}

main($batch, $losers_spot1_csv, $losers_page1_csv);


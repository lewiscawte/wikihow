<?
//
// A script to import the titles of a bunch of articles and to which test 
// cohort they belong.
//

require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/TitleTests.class.php");

$filename = '/home/reuben/expr3.csv';
$fp = fopen($filename, 'r');
if (!$fp) {
	die("error: cannot open $filename for reading\n");
}

$dbw = wfGetDB(DB_MASTER);

while (($data = fgetcsv($fp)) !== false) {
	$pageid = $data[0];
	$title = Title::newFromID($pageid);
	if (!$title) {
		print "bad title: $pageid\n";
		continue;
	}
	$pageid = $title->getArticleId();
	if (!$pageid) {
		print "not found: $pageid\n";
		continue;
	}

	$test = intval($data[1]) + 10;
	//print $title->getText()."\n";
	TitleTests::dbAddRecord($dbw, $title, $test);
}


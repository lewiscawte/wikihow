<?
//
// A script to import the titles of a bunch of articles and to which test 
// cohort they belong.
//

require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/TitleTests.class.php");

$filename = '/home/reuben/e2.csv';
$fp = fopen($filename, 'r');
if (!$fp) {
	die("error: cannot open $filename for reading\n");
}

$dbw = wfGetDB(DB_MASTER);

while (($data = fgetcsv($fp)) !== false) {
	$page = $data[0];
	//$page = iconv('ISO-8859-1', 'UTF-8', $page);
	$page = preg_replace('@^http://[^/]+/@', '', $page);
	$page = preg_replace('@""@', '"', $page);

	$title = Title::newFromURL($page);
	if (!$title) {
		print "bad title: $page\n";
		continue;
	}
	$pageid = $title->getArticleId();
	if (!$pageid) {
		print "not found: $page\n";
		continue;
	}

	$test = intval($data[1]);
	TitleTests::dbAddRecord($dbw, $title, $test);
}


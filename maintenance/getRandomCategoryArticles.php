<?php
/**
 * Grab a bunch of random articles from a category and its subcategories, as
 * long as the articles have no images in the Steps section.
 *
 * Usage: php getRandomCategoryArticles.php Category-Name
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/maintenance/Phodesk.class.php");

$numArticles = 5000;

if (count($argv) < 1) {
	print "usage: php getRandomCategoryArticles.php <category-name-encoded>\n";
	print "  example of category name: Personal-Care-and-Style\n";
	exit;
}

$dbr = wfGetDB(DB_SLAVE);

$topLevel = $argv[0];
$file = $topLevel . '.csv';

// get the category and all sub-categories
$cats = Phodesk::getAllSubcats($dbr, $topLevel);
$cats[] = $topLevel;
sort($cats);
$cats = array_unique($cats);

// get all pages
$pages = array();
foreach ($cats as $cat) {
	$results = Phodesk::getPages($dbr, $cat);
	// make results unique based on page_id
	foreach ($results as $result) {
		$pages[ $result['id'] ] = $result;
	}
}
$pages = array_values($pages);
shuffle($pages);

$lines = array();
foreach ($pages as $page) {
	if (Phodesk::articleBodyHasNoImages($dbr, $page['id'])) {
		$lines[] = array(Phodesk::BASE_URL . "{$page['key']}", $page['id']);
		if (count($lines) >= $numArticles) break;
	}
}

$fp = fopen($file, 'w');
if ($fp) {
	foreach ($lines as $line) {
		fputcsv($fp, $line);
	}
	fclose($fp);
	print "output is in $file\n";
} else {
	print "unable to open file $file\n";
}


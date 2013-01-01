<?php
/**
 * Grab a bunch of random articles from a category and its subcategories
 *
 * Usage: php getRandomCategoryArticles.php outfile.txt
 */


require_once( 'commandLine.inc' );
require_once( 'updateSearchResultsSupplementGSA.inc' );

$topLevel = 'Home-and-Garden';
//$topLevel = 'Hobbies-and-Crafts';
$numArticles = 200;

$dbr = wfGetDB(DB_SLAVE);
$stepsMsg = wfMsg('steps');

function getPages($cat) {
	global $dbr;
	$sql = "SELECT page_id, page_title, cl1.cl_sortkey FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 0 GROUP BY page_id ORDER BY cl1.cl_sortkey";
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchRow($res)) {
		$page = array('id' => $row['page_id'], 'key' => $row['page_title'], 'title' => $row['cl_sortkey']);
		$pages[] = $page;
	}
	return $pages;
}

function getAllSubcats($cat) {
	global $dbr;
	$sql = "SELECT page_title FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 14 GROUP BY page_id ORDER BY cl1.cl_sortkey";
	$cats = array();
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchRow($res)) {
		$cats[] = $row['page_title'];
	}
	$output = $cats;
	foreach ($cats as $cat) {
		$result = getAllSubcats($cat);
		$output = array_merge($output, $result);
	}
	return $output;
}

function getArticleSections($articleText) {
	global $stepsMsg;
	$out = array();

	$sections = preg_split('@==\s*((\w| )+)\s*==@', $articleText, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (count($sections) > 0 && $sections[0] != $stepsMsg) {
		$out['Intro'] = $sections[0];
		unset($sections[0]);
	} else {
		$out['Intro'] = '';
	}

	$sections = array_map(function ($elem) {
		return trim($elem);
	}, $sections);
	$sections = array_filter($sections, function ($elem) {
		return !empty($elem);
	});
	$sections = array_values($sections);

	$i = 0;
	while ($i < count($sections)) {
		$name = trim($sections[$i]);
		//if (preg_match('@^(\w| )+$@', $name))
		if ($i + 1 < count($sections)) {
			$body = trim($sections[$i + 1]);
		} else {
			$body = '';
		}
		$out[$name] = $body;
		$i += 2;
	}

	return $out;
}

function getStepsSection($articleText) {
	global $stepsMsg;
	$out = array();

	$sections = preg_split('@==\s*((\w| )+)\s*==@', $articleText, -1, PREG_SPLIT_DELIM_CAPTURE);

	$sections = array_map(function ($elem) {
		return trim($elem);
	}, $sections);
	$sections = array_filter($sections, function ($elem) {
		return !empty($elem);
	});
	$sections = array_values($sections);

	while ($i < count($sections)) {
		$name = trim($sections[$i]);
		if ($name == $stepsMsg && $i + 1 < count($sections)) {
			$body = trim($sections[$i + 1]);
			return $body;
		}
		$i++;
	}
}

function articleBodyHasNoImages($id) {
	global $dbr;
	$rev = Revision::loadFromPageId($dbr, $id);
	$text = $rev->getText();
	$steps = getStepsSection($text);
	$len = strlen($steps);
	$imgs = preg_match('@\[\[Image:@', $steps);
	return ($len > 10 && $imgs == 0);
}

$file = $topLevel . '.txt';

// get the category and all sub-categories
$cats = getAllSubcats($topLevel);
$cats[] = $topLevel;
sort($cats);
$cats = array_unique($cats);

// get all pages
$pages = array();
foreach ($cats as $cat) {
	$results = getPages($cat);
	// make results unique based on page_id
	foreach ($results as $result) {
		$pages[ $result['id'] ] = $result;
	}
}
$pages = array_values($pages);
shuffle($pages);

$lines = array();
foreach ($pages as $page) {
	if (articleBodyHasNoImages($page['id'])) {
		$lines[] = "http://www.wikihow.com/{$page['key']}";
		if (count($lines) >= $numArticles) break;
	}
}

file_put_contents($file, join("\n", $lines) . "\n");


<?php

/********
 * 
 * This script grabs all articles that meet the following conditions:
 * 1) Has less than 10,000 views
 * 2) Has {{accuracy}} template OR is included in Special:AccuracyPatrol
 * 
 * Then it adds {{Accuracy-bot}} template to the article.
 * 
 ********/

require_once("commandLine.inc");

//first get a list of articles that have
//the {{Accuracy}} template on them.

$dbr = wfGetDB(DB_SLAVE);

$wgUser = User::newFromName("Miscbot");

echo "Starting first query at " . microtime(true) . "\n";

$res = $dbr->select(array('page', 'templatelinks'), array('page_counter', 'page_id'), array('tl_from = page_id', 'tl_title' => "Accuracy", "page_namespace" => "0"), __FUNCTION__);

echo "Finished last query at " . microtime(true) . "\n";

$articles = array();
while($row = $dbr->fetchObject($res)) {
	if($row->page_counter < 10000)
		$articles[$row->page_id] = $row->page_id;
}

echo "Starting second query at " . microtime(true) . "\n";

$res = $dbr->select(array('page', 'rating_low'), array('page_counter', 'page_id'), array('rl_page = page_id', 'page_namespace' => 0));

echo "Finished second query at " . microtime(true) . "\n";

while($row = $dbr->fetchObject($res)) {
	if($row->page_counter < 10000)
		$articles[$row->page_id] = $row->page_id;
}

echo "Getting ready to add template to " . count($articles) . " articles\n";
echo "\n\n";

foreach($articles as $id) {
	$title = Title::newFromID($id);
	if($title){
		$revision = Revision::newFromTitle($title);
		$article = new Article($title);
		$text = $revision->getText();
		$text = "{{Accuracy-bot}} " . $text;
		$article->doEdit($text, "Marking article with Accuracy-bot template");
		
		echo "Added template to " . $title->getFullURL() . "\n";
	}
}
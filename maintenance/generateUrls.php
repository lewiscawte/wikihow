<?php
//
// Generate a list of all URLs for the sitemap
//

require_once('commandLine.inc');

function iso8601_date($time) {
	$date = substr($time, 0, 4)  . "-"
		  . substr($time, 4, 2)  . "-"
		  . substr($time, 6, 2)  . "T"
		  . substr($time, 8, 2)  . ":"
		  . substr($time, 10, 2) . ":"
		  . substr($time, 12, 2) . "Z" ;
	return $date;
}

function main() {
	$PAGE_SIZE = 2000;
	$dbr = wfGetDB(DB_SLAVE);

	$pages = array();
	for ($page = 0; ; $page++) {
		$offset = $PAGE_SIZE * $page;
		$sql = "SELECT page_id, page_title, page_namespace, page_touched FROM page WHERE page_namespace = 0 AND page_is_redirect = 0 ORDER BY page_touched DESC LIMIT $offset,$PAGE_SIZE";
		$res = $dbr->query($sql, __FILE__);
		if (!$res->numRows()) break;
		foreach ($res as $row) {
			$title = Title::newFromDBKey($row->page_title);
			if (!$title) {
				continue;
			}

			if (class_exists('RobotPolicy')) {
				$robotPolicy = RobotPolicy::newFromTitle($title);
				if (!$robotPolicy) {
					continue;
				}
				if ($robotPolicy->genRobotPolicy() != RobotPolicy::POLICY_INDEX_FOLLOW) {
					continue;
				}
			}

			$pages[] = array(
				'url' => $title->getFullUrl(),
				'date' => iso8601_date($row->page_touched),
			);
		}
		$res->free();
	}

	foreach ($pages as $page) {
		print "{$page['url']} lastmod={$page['date']}\n";
	}
}

main();

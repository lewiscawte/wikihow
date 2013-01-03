<?php

/**
 * A collection of functions used to process articles and find articles
 * for the phodesk project.
 */
class Phodesk {

	const BASE_URL = 'http://www.wikihow.com/';

	public static function getPages(&$dbr, $cat) {
		$sql = "SELECT page_id, page_title, cl1.cl_sortkey FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 0 GROUP BY page_id ORDER BY cl1.cl_sortkey";
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchRow($res)) {
			$page = array('id' => $row['page_id'], 'key' => $row['page_title'], 'title' => $row['cl_sortkey']);
			$pages[] = $page;
		}
		return $pages;
	}

	public static function getAllSubcats(&$dbr, $cat) {
		$sql = "SELECT page_title FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 14 GROUP BY page_id ORDER BY cl1.cl_sortkey";
		$cats = array();
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchRow($res)) {
			$cats[] = $row['page_title'];
		}
		$output = $cats;
		foreach ($cats as $cat) {
			$result = self::getAllSubcats($dbr, $cat);
			$output = array_merge($output, $result);
		}
		return $output;
	}

	public static function getArticleSections($articleText) {
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMsg('steps');

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

	public static function getStepsSection($articleText) {
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMsg('steps');

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

	public static function articleBodyHasNoImages(&$dbr, $id) {
		$rev = Revision::loadFromPageId($dbr, $id);
		$text = $rev->getText();
		$steps = self::getStepsSection($text);
		$len = strlen($steps);
		$imgs = preg_match('@\[\[Image:@', $steps);
		return ($len > 10 && $imgs == 0);
	}

	/**
	 * Given a URL at www.wikihow.com, look up the page ID.  If it couldn't
	 * be found, return the empty string.
	 */
	public static function getArticleID($url) {
		$count = preg_match('@^' . self::BASE_URL . '@', $url);
		if (!$count) return '';

		$partialUrl = preg_replace('@^' . self::BASE_URL . '@', '', $url);
		$title = Title::newFromURL($partialUrl);
		if ($title) {
			$id = $title->getArticleID();
			return $id ? $id : '';
		} else {
			return '';
		}
	}

}


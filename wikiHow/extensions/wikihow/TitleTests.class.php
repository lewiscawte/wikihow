<?
//
// Class used to manage title tests, to display the correct title and meta 
// description data based on which test is being run.
//

/*db schema:
CREATE TABLE title_tests(
	tt_pageid INT UNSIGNED NOT NULL,
	tt_page VARCHAR(255) NOT NULL,
	tt_test INT(2) UNSIGNED NOT NULL,
	tt_custom TEXT DEFAULT NULL,
	PRIMARY KEY (tt_pageid)
);
*/

class TitleTests {

	const TITLE_DEFAULT = -1;
	const TITLE_CUSTOM = 100;
	const TITLE_SITE_PREVIOUS = 101;

	const MAX_TITLE_LENGTH = 65;

	var $title;
	var $row;
	var $cachekey;

	// called by factory method
	protected function __construct($title, $row) {
		$this->title = $title;
		$this->row = $row;
	}

	// factory function to create a new object using pageid
	public static function newFromTitle($title) {
		global $wgMemc, $wgLanguageCode;

		if ($wgLanguageCode != 'en' || !$title || !$title->exists()) {
			// cannot create class
			return null;
		}

		$pageid = $title->getArticleId();
		$namespace = $title->getNamespace();
		if ($namespace != NS_MAIN || $pageid <= 0) {
			return null;
		}

		$cachekey = wfMemcKey('titletests-' . $pageid);
		$row = $wgMemc->get($cachekey);
		if ($row === null) {
			$dbr = wfGetDB(DB_SLAVE);
			$row = (array)$dbr->selectRow(
				'title_tests',
				array('tt_test', 'tt_custom'),
				array('tt_pageid' => $pageid));
			$wgMemc->set($cachekey, $row);
		}

		if (!$row) {
			// not a test
			return null;
		}

		$obj = new TitleTests($title, $row);
		return $obj;
	}

	public function getTitle() {
		return self::genTitle($this->title, $this->row['tt_test'], $this->row['tt_custom']);
	}

	public function getDefaultTitle() {
		$wasEdited = $this->row['tt_test'] == self::TITLE_CUSTOM;
		$defaultPageTitle = self::genTitle($this->title, self::TITLE_DEFAULT, '');
		return array($defaultPageTitle, $wasEdited);
	}

	public function getOldTitle() {
		$isCustom = $this->row['tt_test'] == self::TITLE_CUSTOM;
		$testNum = $isCustom ? self::TITLE_CUSTOM : self::TITLE_SITE_PREVIOUS;
		$oldPageTitle = self::genTitle($this->title, $testNum, $this->row['tt_custom']);
		return $oldPageTitle;
	}

	private static function genTitle($title, $test, $custom) {
		$titleTxt = $title->getText();
		$howto = wfMsg('howto', $titleTxt);
		$detailsFunc = array('WikiHowTemplate', 'getTitleExtraInfo');

		switch ($test) {
		case self::TITLE_CUSTOM: // Custom
			$title = $custom;
			break;
		case self::TITLE_SITE_PREVIOUS: // How to XXX: N steps (with pictures) - wikiHow
			list($details, $steps) = call_user_func($detailsFunc, $title);
			$inner = $howto . $details;
			$title = wfMsg('pagetitle', $inner);
			break;
		default:
		case 3: // How to XXX: N steps (with pictures) - wikiHow
			list($details, $steps) = call_user_func($detailsFunc, $title);
			$inner = $howto . $details;
			$title = wfMsg('pagetitle', $inner);
			// first, try articlename + metadata + wikihow
			if (strlen($title) > self::MAX_TITLE_LENGTH) {
				// next, try articlename + metadata
				$title = $inner;
				if (!empty($steps) && strlen($title) > self::MAX_TITLE_LENGTH) {
					// next, try articlename + steps
					$title = $howto . $steps;
				}
				if (strlen($title) > self::MAX_TITLE_LENGTH) {
					// next, try articlename + wikihow
					$title = wfMsg('pagetitle', $howto);
					if (strlen($title) > self::MAX_TITLE_LENGTH) {
						// lastly, set title just as articlename
						$title = $howto;
					}
				}
			}
			break;

/*
		case 1: // How to XXX - wikiHow (no change)
			$title = wfMsg('pagetitle', $howto);
			break;
		case 2: // How to XXX
			$title = $howto;
			break;
		case 4: // How to XXX: N steps (with pictures)
			list($details, ) = call_user_func($detailsFunc, $title);
			$title = $howto . $details;
			break;
		case 5: // How to XXX - wikiHow, the free how-to guide
			$title = $howto . ' - wikiHow, the free how-to guide';
			break;
*/
		}
		return $title;
	}

	public function getMetaDescription() {
		return self::genMetaDescription($this->title, $this->row['tt_test']);
	}

	private static function genMetaDescription($title, $test) {
		// no more tests -- always use site default for meta desription
		$ami = new ArticleMetaInfo($title);
		$desc = $ami->getDescription();
		return $desc;
	}

	/**
	 * Adds a new record to the title_tests db table.  Called by 
	 * importTitleTests.php.
	 */
	public static function dbAddRecord(&$dbw, $title, $test) {
		global $wgMemc;
		if (!$title || $title->getNamespace() != NS_MAIN) {
			throw new Exception('TitleTests: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace('title_tests', 'tt_pageid', 
			array('tt_pageid' => $pageid,
				'tt_page' => $title->getDBkey(),
				'tt_test' => $test),
			__METHOD__);
		$cachekey = wfMemcKey('titletests-' . $pageid);
		$wgMemc->delete($cachekey);
	}

	/**
	 * Adds or replaces the current title with a custom one specified by
	 * a string from the admin. Note: must be a main namespace title.
	 */
	public static function dbSetCustomTitle(&$dbw, $title, $custom) {
		global $wgMemc;
		if (!$title || $title->getNamespace() != NS_MAIN) {
			throw new Exception('TitleTests: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace('title_tests', 'tt_pageid',
			array('tt_pageid' => $pageid,
				'tt_page' => $title->getDBkey(),
				'tt_test' => self::TITLE_CUSTOM,
				'tt_custom' => $custom),
			__METHOD__);
		$cachekey = wfMemcKey('titletests-' . $pageid);
		$wgMemc->delete($cachekey);
	}

	/**
	 * List all "custom-edited" titles in one go
	 */
	public static function dbListCustomTitles(&$dbr) {
		$res = $dbr->select('title_tests',
			array('tt_page', 'tt_custom'), 
			array('tt_test = ' . self::TITLE_CUSTOM),
			__METHOD__);
		$pages = array();
		while (($row = $res->fetchObject())) {
			$pages[] = (array)$row;
		}
		return $pages;
	}

	/**
	 * Remove a title from the list of tests
	 */
	public static function dbRemoveTitle(&$dbw, $title) {
		global $wgMemc;
		$pageid = $title->getArticleId();
		$dbw->delete('title_tests',
			array('tt_pageid' => $pageid),
			__METHOD__);
		$cachekey = wfMemcKey('titletests-' . $pageid);
		$wgMemc->delete($cachekey);
	}

}


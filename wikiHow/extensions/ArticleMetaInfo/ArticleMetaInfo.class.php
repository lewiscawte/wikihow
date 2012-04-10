<?

if ( !defined('MEDIAWIKI') ) die();

/**
 * Controls the html meta descriptions that relate to Google and Facebook
 * in the head of all article pages.
 *
 * Follows something like the active record pattern.
 */
class ArticleMetaInfo {
	static $dbr = null,
		$dbw = null;

	var $title = null,
		$wikitext = '',
		$cachekey = '',
		$row = null;

	const MAX_DESC_LENGTH = 240;

	const DESC_STYLE_NOT_SPECIFIED = -1;
	const DESC_STYLE_ORIGINAL = 0;
	const DESC_STYLE_INTRO = 1;
	const DESC_STYLE_DEFAULT = 1; // SAME AS ABOVE
	const DESC_STYLE_STEP1 = 2;
	const DESC_STYLE_EDITED = 3;
	const DESC_STYLE_INTRO_NO_TITLE = 4;
	const DESC_STYLE_FACEBOOK_DEFAULT = 4; // SAME AS ABOVE

	public function __construct($title) {
		$this->title = $title;
		$articleID = $title->getArticleID();
		$namespace = $title->getNamespace();
		$this->cachekey = wfMemcKey('metadata', $namespace, $articleID);
	}

	/**
	 * After each edit of an article or when an article is started
	 */
	public static function refreshMetaDataCallback($article, $user, $wikitext) {
		$title = $article->getTitle();
		if ($title
			&& $title->exists()
			&& $title->getNamespace() == NS_MAIN
			&& $wikitext)
		{
			$meta = new ArticleMetaInfo($title);
			// optimization so that article revision doesn't need to be
			// looked up again
			$meta->wikitext = $wikitext;
			$meta->refreshMetaData();
		}
		return true;
	}

	/**
	 * Refresh all computed data about the meta description stuff
	 */
	public function refreshMetaData($style = self::DESC_STYLE_NOT_SPECIFIED) {
		$this->loadInfo();
		$this->updateImage();
		$this->populateDescription($style);
		$this->populateFacebookDescription();
		$this->saveInfo();
	}

	/**
	 * Return the image meta info for the article record
	 */
	public function getImage() {
		$this->loadInfo();
		// if ami_img == NULL, this field needs to be populated
		if ($this->row && $this->row['ami_img'] === null) {
			if ($this->updateImage()) {
				$this->saveInfo();
			}
		}
		return @$this->row['ami_img'];
	}

	/**
	 * Update the image meta info for the article record
	 */
	private function updateImage() {
		$wikitext = $this->getArticleWikiText();
		if (!$wikitext) return false;

		$url = Wikitext::getFirstImageURL($wikitext);
		$this->row['ami_img'] = $url;
		return true;
	}

	/**
	 * Grab the wikitext for the article record
	 */
	private function getArticleWikiText() {
		// cache this if it was already pulled
		if ($this->wikitext) {
			return $this->wikitext;
		}

		if (!$this->title || !$this->title->exists()) {
			//throw new Exception('ArticleMetaInfo: title not found');
			return '';
		}

		$dbr = $this->getDB();
		$rev = Revision::loadFromTitle($dbr, $this->title);
		if (!$rev) {
			//throw new Exception('ArticleMetaInfo: could not load revision');
			return '';
		}

		return $rev->getText();
	}

	/**
	 * Add meta descriptions for all the article URLs listed (in CSV format)
	 * in $filename.  The $style style of format will be created.
	 *
	 * Commenting out this function because it's dangerous.  It could delete
	 * all user-generated descriptions from the table.
	 *
	public static function processArticleDescriptionList($filename, $style) {
		$fp = fopen($filename, 'r');
		if (!$fp) {
			throw new Exception('unable to open file: ' . $filename);
		}
		
		while (($line = fgetcsv($fp)) !== false) {
			$url = $line[0];
			$partialURL = preg_replace('@^(http://[a-z]+\.wikihow\.com\/)?(.*)$@', '$2', $url);
			$title = Title::newFromURL($partialURL);
			if ($title) {
				$ami = new ArticleMetaInfo($title);
				if ($ami->populateDescription($style)) {
					$ami->saveInfo();
				}
				print "desc added: $title\n";
			} else {
				print "title not found: $partialURL\n";
			}
		}
		fclose($fp);
	}
	*/

	/**
	 * Add meta descriptions for all pages on site.  Convert all to the
	 * given style.
	 *
	 * Commenting out this function because it's dangerous.  It could delete
	 * all user-generated descriptions from the table.
	 *
	 public static function reprocessAllArticles($style) {
		// pull all pages from DB
		$dbw = wfGetDB(DB_SLAVE);
		$res = $dbw->select('page', 'page_title',
			array('page_is_redirect = 0',
				'page_namespace = ' . NS_MAIN),
			__METHOD__);
		$pages = array();
		while (($obj = $res->fetchObject()) != null) {
			$pages[] = $obj->page_title;
		}

		// delete all existing meta descriptions not of the chosen style
		//$dbw->update('article_meta_info', 
		//	array('ami_desc_style = ' . $style,
		//		"ami_desc = ''"),
		//	array('ami_desc_style <> ' . $style),
		//	__METHOD__);

		// process all pages, adding then chosen style description to them
		foreach ($pages as $page) {
			$title = Title::newFromDBkey($page);
			if ($title) {
				$ami = new ArticleMetaInfo($title);
				$ami->refreshMetaData($style);
				print "desc added: $title\n";
			} else {
				print "title not found: $page\n";
			}
		}
	}
	*/

	/**
	 * Populate Facebook meta description.
	 */
	private function populateFacebookDescription() {
		$fbstyle = self::DESC_STYLE_FACEBOOK_DEFAULT;
		return $this->populateDescription($fbstyle, true);
	}

	/**
	 * Add a meta description (in one of the styles specified by the row) if
	 * a description is needed.
	 */
	private function populateDescription($forceDesc = self::DESC_STYLE_NOT_SPECIFIED, $facebook = false) {
		$this->loadInfo();

		if (!$facebook && 
			(self::DESC_STYLE_NOT_SPECIFIED == $forceDesc
			 || self::DESC_STYLE_EDITED == $this->row['ami_desc_style']))
		{
			$style = $this->row['ami_desc_style'];
		} else {
			$style = $forceDesc;
		}

		if (!$facebook) {
			$this->row['ami_desc_style'] = $style;
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_desc'] = $desc;
		} else {
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_facebook_desc'] = $desc;
		}

		return $success;
	}

	/**
	 * Sets the meta description in the database to be part of the intro, part
	 * of the first step, or 'original' which is something like "wikiHow
	 * article on How to <title>".
	 */
	private function buildDescription($style) {
		if (self::DESC_STYLE_ORIGINAL == $style) {
			return array(true, '');
		}
		if (self::DESC_STYLE_EDITED == $style) {
			return array(true, $this->row['ami_desc']);
		}

		$wikitext = $this->getArticleWikiText();
		if (!$wikitext) return array(false, '');

		if (self::DESC_STYLE_INTRO == $style
			|| self::DESC_STYLE_INTRO_NO_TITLE == $style)
		{
			// grab intro
			$desc = Wikitext::getIntro($wikitext);
		} elseif (self::DESC_STYLE_STEP1 == $style) {
			// grab steps section
			list($desc, ) = Wikitext::getStepsSection($wikitext);

			// pull out just the first step
			if ($desc) {
				$desc = Wikitext::cutFirstStep($desc);
			} else {
				$desc = Wikitext::getIntro($wikitext);
			}
		} else {
			//throw new Exception('ArticleMetaInfo: unknown style');

			return array(false, '');
		}

		$desc = Wikitext::flatten($desc);

		$howto = wfMsg('howto', $this->title->getText());
		if ($desc) {
			if (self::DESC_STYLE_INTRO_NO_TITLE != $style) {
				$desc = $howto . '. ' . $desc;
			}
		} else {
			$desc = $howto;
		}

		$desc = self::trimDescription($desc);
		return array(true, $desc);
	}
	
	private static function trimDescription($desc) {
		// Chop desc length at MAX_DESC_LENGTH, and then last space in
		// description so that '...' is added at the end of a word.
		$desc = mb_substr($desc, 0, self::MAX_DESC_LENGTH);
		$len = mb_strlen($desc);
		// TODO: mb_strrpos method isn't available for some reason
		$pos = strrpos($desc, ' ');

		if ($len >= self::MAX_DESC_LENGTH && $pos !== false) {
			$toAppend = '...';
			if ($len - $pos > 20)  {
				$pos = $len - strlen($toAppend);
			}
			$desc = mb_substr($desc, 0, $pos) . $toAppend;
		}

		return $desc;
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_desc']) {
			return $this->row['ami_desc'];
		}

		$this->loadInfo();

		// needs description
		if ($this->row
			&& $this->row['ami_desc_style'] != self::DESC_STYLE_ORIGINAL
			&& !$this->row['ami_desc'])
		{
			if ($this->populateDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_desc'];
	}

	/**
	 * Return the description style used.  Can be compared against the
	 * self::DESC_STYLE_* constants.
	 */
	public function getStyle() {
		$this->loadInfo();
		return $this->row['ami_desc_style'];
	}

	/**
	 * Returns the description in the "intro" style.  Note that this function
	 * is not optimized for caching and should only be called within the
	 * admin console.
	 */
	public function getDescriptionDefaultStyle() {
		$this->loadInfo();
		list($success, $desc) = $this->buildDescription(self::DESC_STYLE_DEFAULT);
		return $desc;
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function setEditedDescription($desc) {
		$this->loadInfo();
		$this->row['ami_desc_style'] = self::DESC_STYLE_EDITED;
		$this->row['ami_desc'] = self::trimDescription($desc);
		$this->refreshMetaData();
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function resetMetaData() {
		$this->loadInfo();
		$this->row['ami_desc_style'] = self::DESC_STYLE_DEFAULT;
		$this->row['ami_desc'] = '';
		$this->refreshMetaData();
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getFacebookDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_facebook_desc']) {
			return $this->row['ami_facebook_desc'];
		}

		$this->loadInfo();

		// needs FB description
		if ($this->row && !$this->row['ami_facebook_desc']) {
			if ($this->populateFacebookDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_facebook_desc'];
	}

	/**
	 * Retrieve the meta info stored in the database.
	 */
	/*public function getInfo() {
		$this->loadInfo();
		return $this->row;
	}*/

	/* DB schema
	 *
	 CREATE TABLE article_meta_info (
	   ami_id int unsigned not null,
	   ami_namespace int unsigned not null default 0,
	   ami_title varchar(255) not null default '',
	   ami_updated varchar(14) not null default '',
	   ami_desc_style tinyint(1) not null default 0,
	   ami_desc varchar(255) not null default '',
	   ami_facebook_desc varchar(255) not null default '',
	   ami_img varchar(255) default null,
	   primary key (ami_id)
	 ) DEFAULT CHARSET=utf8;
	 *
	 alter table article_meta_info add column ami_facebook_desc varchar(255) not null default '' after ami_desc;
	 *
	 */

	/**
	 * Create a database handle.  $type can be 'read' or 'write'
	 */
	private function getDB($type = 'read') {
		if ($type == 'write') {
			if (self::$dbw == null) self::$dbw = wfGetDB(DB_MASTER);
			return self::$dbw;
		} elseif ($type == 'read') {
			if (self::$dbr == null) self::$dbr = wfGetDB(DB_SLAVE);
			return self::$dbr;
		} else {
			throw new Exception('unknown DB handle type');
		}
	}

	/**
	 * Load the meta info record from either DB or memcache
	 */
	private function loadInfo() {
		global $wgMemc;

		if ($this->row) return;

		$res = $wgMemc->get($this->cachekey);
		if ($res === null) {
			$articleID = $this->title->getArticleID();
			$namespace = MW_MAIN;
			$dbr = $this->getDB();
			$sql = 'SELECT * FROM article_meta_info WHERE ami_id=' . $dbr->addQuotes($articleID) . ' AND ami_namespace=' . intval($namespace);
			$res = $dbr->query($sql, __METHOD__);
			$this->row = $dbr->fetchRow($res);

			if (!$this->row) {
				$this->row = array(
					'ami_id' => $articleID,
					'ami_namespace' => intval($namespace),
					'ami_desc_style' => self::DESC_STYLE_INTRO,
					'ami_desc' => '',
					'ami_facebook_desc' => '',
				);
			} else {
				foreach ($this->row as $k => $v) {
					if (is_int($k)) {
						unset($this->row[$k]);
					}
				}
			}
			$wgMemc->set($this->cachekey, $this->row);
		} else {
			$this->row = $res;
		}
	}

	/**
	 * Save article meta info to both DB and memcache
	 */
	private function saveInfo() {
		global $wgMemc;

		if (empty($this->row)) {
			throw new Exception(__METHOD__ . ': nothing loaded');
		}

		$this->row['ami_updated'] = wfTimestampNow(TS_MW);

		if (!isset($this->row['ami_title'])) {
			$this->row['ami_title'] = $this->title->getText();
		}
		if (!isset($this->row['ami_id'])) {
			$articleID = $this->title->getArticleID();
			$this->row['ami_id'] = $articleID;
		}
		if (!isset($this->row['ami_namespace'])) {
			$namespace = $this->title->getNamespace();
			$this->row['ami_namespace'] = $namespace;
		}

		$dbw = $this->getDB('write');
		$sql = 'REPLACE INTO article_meta_info SET ' . $dbw->makeList($this->row, LIST_SET);
		$res = $dbw->query($sql, __METHOD__);
		$wgMemc->set($this->cachekey, $this->row);
	}

}


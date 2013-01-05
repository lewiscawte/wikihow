<?

if ( !defined('MEDIAWIKI') ) die();

global $IP;
require_once("$IP/extensions/wikihow/Wikitext.class.php");

/**
 * Follows something like the active record pattern.
 */
class ArticleMetaInfo {
	static $dbr = null,
		$dbw = null;

	var $namespace = -1,
		$articleID = 0,
		$dbkey = '',
		$title = '',
		$cachekey = '',
		$row = null,
		$desc = false;

	const MAX_DESC_LENGTH = 240;

	const DESC_STYLE_ORIGINAL = 0;
	const DESC_STYLE_INTRO = 1;
	const DESC_STYLE_STEP1 = 2;

	public function __construct($title) {
		$this->articleID = $title->getArticleID();
		$this->namespace = $title->getNamespace();
		$this->cachekey = wfMemcKey('md-' . $this->namespace . '-' . $this->articleID);
		$this->dbkey = $title->getDBkey();
		$this->title = $title->getText();
	}

	/**
	 * Return the image meta info for the article record
	 */
	public function getImage() {
		$this->loadInfo();
		// if ami_img == NULL, this field needs to be populated
		if ($this->row && $this->row['ami_img'] === null) {
			$this->updateImage();
		}
		return @$this->row['ami_img'];
	}

	/**
	 * Update the image meta info for the article record
	 */
	private function updateImage() {
		list($text, ) = $this->getArticleWikiText();
		if (!$text) return false;

		$url = Wikitext::getFirstImageURL($text);
		$this->row['ami_img'] = $url;
		$this->saveInfo();
		return true;
	}

	/**
	 * Grab the wikitext for the article record
	 */
	private function getArticleWikiText() {
		$title = null;
		if (!empty($this->dbkey)) {
			// try this first since it's most efficient
			$title = Title::newFromDBkey($this->dbkey);
		}

		if (!$title) {
			// try to load the title this way next since it's slower
			$title = Title::newFromID($this->articleID);
		}

		if (!$title) {
			//throw new Exception('ArticleMetaInfo::buildDescription: title not found');
			return array('', null);
		}

		if ($this->row && !@$this->row['ami_title']) {
			$this->row['ami_title'] = $title->getText();
		}

		$dbr = $this->getDB();
		$rev = Revision::loadFromTitle($dbr, $title);
		if (!$rev) {
			//throw new Exception('ArticleMetaInfo::buildDescription: could not load revision');
			return array('', null);
		}

		return array($rev->getText(), $title);
	}

	/**
	 * Add meta descriptions for all the article URLs listed (in CSV format)
	 * in $filename.  The $style style of format will be created.
	 */
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
				$ami->row['ami_desc_style'] = $style;

				$ami->populateDescription();
				//print "desc added: $title\n";
			} else {
				print "title not found: $title\n";
			}
		}
		fclose($fp);
	}

	/**
	 * Add a meta description (in one of the styles specified by the row) if
	 * a description is needed.
	 */
	private function populateDescription() {
		$this->loadInfo();
		$style = $this->row['ami_desc_style'];
		return $this->buildDescription($style);
	}

	/**
	 * Sets the meta description in the database to be part of the intro, part
	 * of the first step, or 'original' which is something like "wikiHow
	 * article on How to <title>".
	 */
	private function buildDescription($style) {
		global $wgParser;

		if ($style == self::DESC_STYLE_ORIGINAL) {
			return true;
		}

		list($text, $titleObj) = $this->getArticleWikiText();
		if (!$text) return false;

		if ($style == self::DESC_STYLE_INTRO) {
			// grab intro
			$desc = Wikitext::getIntro($text);
		} elseif ($style == self::DESC_STYLE_STEP1) {
			// grab steps section
			list($desc, ) = Wikitext::getStepsSection($text);

			// pull out just the first step
			if ($desc) {
				$desc = Wikitext::cutFirstStep($desc);
			} else {
				$desc = Wikitext::getIntro($text);
			}
		} else {
			//throw new Exception('ArticleMetaInfo::buildDescription: unknown style');

			return false;
		}

		$desc = Wikitext::flatten($desc);

		$howto = wfMsg('howto', $titleObj->getText());
		if ($desc) {
			$desc = $howto . '. ' . $desc;
		} else {
			$desc = $howto;
		}

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

		$this->row['ami_desc'] = $desc;
		$this->saveInfo();
		return true;
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getDescription() {
		// return copy of description already found
		if ($this->desc !== false) {
			return $this->desc;
		}

		$this->loadInfo();

		// needs description
		if ($this->row && !$this->row['ami_desc']) {
			$this->populateDescription();
		}

		$this->desc = @$this->row['ami_desc'];
		return $this->desc;
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
	   ami_img varchar(255) default null,
	   primary key (ami_id)
	 ) DEFAULT CHARSET=utf8;
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
		$fname = __METHOD__;

		if ($this->row) return;

		$res = $wgMemc->get($this->cachekey);
		if ($res === null) {
			$dbr = $this->getDB();
			$namespace = MW_MAIN;
			$sql = 'SELECT * FROM article_meta_info WHERE ami_id=' . $dbr->addQuotes($this->articleID) . ' AND ami_namespace=' . intval($namespace);
			$res = $dbr->query($sql, $fname);
			$this->row = $dbr->fetchRow($res);

			if (!$this->row) {
				$this->row = array(
					'ami_id' => $this->articleID,
					'ami_namespace' => intval($namespace),
					'ami_desc_style' => self::DESC_STYLE_ORIGINAL,
					'ami_desc' => '',
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

		$fname = __METHOD__;
		if (empty($this->row)) {
			throw new Exception(__METHOD__ . ': nothing loaded');
		}

		$this->row['ami_updated'] = wfTimestampNow(TS_MW);

		if (!isset($this->row['ami_title'])) {
			$this->row['ami_title'] = $this->title;
		}
		if (!isset($this->row['ami_id'])) {
			$this->row['ami_id'] = $this->articleID;
		}
		if (!isset($this->row['ami_namespace'])) {
			$this->row['ami_namespace'] = $this->namespace;
		}

		$dbw = $this->getDB('write');
		$sql = 'REPLACE INTO article_meta_info SET ' . $dbw->makeList($this->row, LIST_SET);
		$res = $dbw->query($sql, $fname);
		$wgMemc->set($this->cachekey, $this->row);
	}

}


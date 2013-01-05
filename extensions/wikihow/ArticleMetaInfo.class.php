<?

if ( !defined('MEDIAWIKI') ) die();

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

		$url = '';
		if (preg_match('@\[\[Image:([^\]|]*)(\|[^\]]*)?\]\]@s', $text, $m)) {
			$imgTitle = Title::newFromText($m[1], NS_IMAGE);
			if ($imgTitle) {
				$file = wfFindFile($imgTitle);
				if ($file && $file->exists()) {
					$url = $file->getUrl();
				}
			}
		}

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
			$desc = self::getIntro($text);
		} elseif ($style == self::DESC_STYLE_STEP1) {
			// grab steps section
			$desc = self::getStepsSection($text);

			// remove just the first step
			if ($desc) {
				// remove alternate method title
				$desc = preg_replace('@^===[^=]*===@', '', $desc);

				// cut just first step
				$desc = preg_replace('@^[#*\s]*([^#*]([^#]|\n)*)([#*](.|\n){0,1000})?$@', '$1', $desc);
				$desc = trim($desc);
			} else {
				$desc = $wgParser->getSection($text, 0);
			}
		} else {
			//throw new Exception('ArticleMetaInfo::buildDescription: unknown style');

			return false;
		}

		// change unicode quotes (from MS Word) to ASCII
		$desc = preg_replace('@[\x{93}\x{201C}\x{94}\x{201D}]@u', '"', $desc);
		$desc = preg_replace('@[\x{91}\x{2018}\x{92}\x{2019}]@u', '\'', $desc);

		// remove templates
		$desc = preg_replace('@{{[^}]+}}@', '', $desc);

		// remove [[Image:foo.jpg]] images and [[Link]] links
		$desc = preg_replace_callback(
			'@\[\[([^\]|]+(#[^\]|]*)?)((\|[^\]|]*)*\|([^\]|]*))?\]\]@', 
			function ($m) use($aid) {

				// if the link text has Image: or something at the start,
				// we don't want it to be in the description
				if (strpos($m[1], ':') !== false) {
					return '';
				} else {
					// if the link looks like [[Texas|The lone star state]],
					// we try to grab the stuff after the vertical bar
					if (isset($m[5]) && strpos($m[5], '|') === false) {
						return $m[5];
					} else {
						return $m[1];
					}
				}
			},
			$desc);

		// remove [http://link.com/ Link] links
		$desc = preg_replace_callback(
			'@\[http://[^\] ]+( ([^\]]*))?\]@', 
			function ($m) {
				// if the link looks like [http://link/ Link], we try to 
				// grab the stuff after the space
				if (isset($m[2])) {
					return $m[2];
				} else {
					return '';
				}
			},
			$desc);

		// remove multiple quotes since they're wikitext for bold or italics
		$desc = preg_replace('@[\']{2,}@', '', $desc);

		// remove other special wikitext stuff
		$desc = preg_replace('@(__FORCEADV__|__TOC__|#REDIRECT)@i', '', $desc);

		// convert special HTML characters into spaces
		$desc = preg_replace('@(<br[^>]*>|&nbsp;)+@i', ' ', $desc);

		// replace multiple spaces in a row with just one
		$desc = preg_replace('@[[:space:]]+@', ' ', $desc);

		// remove all HTML
		$desc = strip_tags($desc);

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

	/**
	 * Extract the intro from the wikitext of an article
	 */
	public static function getIntro($wikitext) {
		global $wgParser;
		$intro = $wgParser->getSection($wikitext, 0);
		return $intro;
	}

	/**
	 * Extract the Steps section from the wikitext of an article
	 */
	public static function getStepsSection($wikitext) {
		global $wgParser;
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMsg('steps');

		$steps = '';
		for ($i = 1; $i < 10; $i++) {
			$section = $wgParser->getSection($wikitext, $i);
			if (empty($section)) break;
			if (preg_match('@^\s*==\s*([^=\s]+)\s*==\s*$((.|\n){0,1000})@m', $section, $m)) {
				if ($m[1] == $stepsMsg) {
					$steps = trim($m[2]);
					break;
				}
			}
		}
		return $steps;
	}

}


<?

$wgHooks['BeforePageDisplay'][] = array("RobotPolicy::setRobotPolicy");

class RobotPolicy {

	const POLICY_INDEX_FOLLOW = 1;
	const POLICY_NOINDEX_FOLLOW = 2;
	const POLICY_NOINDEX_NOFOLLOW = 3;

	const POLICY_INDEX_FOLLOW_STR = 'index,follow';
	const POLICY_NOINDEX_FOLLOW_STR = 'noindex,follow';
	const POLICY_NOINDEX_NOFOLLOW_STR = 'noindex,nofollow';

	var $title, $article, $request;

	private function __construct($title, $article, $request = null) {
		$this->title = $title;
		$this->article = $article;
		$this->request = $request;
	}

	public static function setRobotPolicy() {
		global $wgOut, $wgTitle, $wgArticle, $wgRequest;
		if ($wgOut && $wgTitle) {
			$robotPolicy = new RobotPolicy($wgTitle, $wgArticle, $wgRequest);
			$policy = $robotPolicy->genRobotPolicy();

			switch ($policy) {
			case self::POLICY_NOINDEX_FOLLOW:
				$wgOut->setRobotPolicy(self::POLICY_NOINDEX_FOLLOW_STR);
				break;
			case self::POLICY_NOINDEX_NOFOLLOW:
				$wgOut->setRobotPolicy(self::POLICY_NOINDEX_NOFOLLOW_STR);
				break;
			}
		}
		return true;
	}

	public static function newFromTitle($title) {
		if (!$title) {
			return null;
		} else {
			$article = new Article($title);
			return new RobotPolicy($title, $article);
		}
	}

	public function genRobotPolicy() {

		if ($this->hasUserPageRestrictions()
			|| $this->hasBadTemplate() 
			|| $this->isShortUnNABbedArticle()
			|| $this->isAccuracyPatrolArticle()
			|| $this->isInaccurate())
		{
			return self::POLICY_NOINDEX_FOLLOW;
		} elseif ($this->isProdButNotWWWHost()
			|| $this->isPrintable()
			|| $this->isOriginCDN()
			|| $this->isNonExistentPage()
			|| $this->hasOldidParam())
		{
			return self::POLICY_NOINDEX_NOFOLLOW;
		} else {
			return self::POLICY_INDEX_FOLLOW;
		}

	}

	/**
	 * Don't allow indexing of user pages where the contributor has less
	 * than 20 edits.  Also, ignore pages with a '/' in them, such as
	 * User:Reuben/Sandbox
	 */
	private function hasUserPageRestrictions() {
		if ($this->title->getNamespace() == NS_USER
			&& ($this->numEdits() < 20
				|| strpos($this->title->getText(), '/') !== false))
		{
			return true;
		}
		return false;
	}

	/**
	 * Check if we're on the production/english server but that the http
	 * Host header isn't www.wikihow.com
	 */
	private function isProdButNotWWWHost() {
		global $wgServer;
		$serverName = @$_SERVER['SERVER_NAME'];
		if (IS_PROD_EN_SITE && $serverName) {
			$www = 'www.wikihow.com';
			if ($wgServer == 'http://' . $www && $serverName != $www) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Test whether page is being displayed in "printable" form
	 */
	private function isPrintable() {
		$isPrintable = $this->request && $this->request->getVal('printable', '') == 'yes';
		return $isPrintable;
	}

	/**
	 * Check whether the origin of the request is the CDN
	 */
	private function isOriginCDN() {
		$isCDNRequest = strpos(@$_SERVER['HTTP_X_INITIAL_URL'], 'http://pad') === 0;
        return IS_PROD_EN_SITE && $isCDNRequest;
	}

	/**
	 * Check whether page exists in DB or not
	 */
	private function isNonExistentPage() {
		if (!$this->title ||
			($this->title->getArticleID() == 0
			 && $this->title->getNamespace() != NS_SPECIAL))
		{
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Retrieve number of edits by a user
	 */
	private function numEdits() {
		if ($this->title->getNamespace() != NS_USER) {
			return 0;
		}
		$u = split("/", $this->title->getText());
		return User::getAuthorStats($u[0]);
	}

	/**
	 * Check to see whether certain templates are affixed to the article.
	 */
	private function hasBadTemplate() {
		global $wgMemc;
		$result = 0;
		$articleID = $this->title->getArticleID();
		if ($articleID) {
			$cachekey = wfMemcKey('badtmpl', $articleID);
			$result = $wgMemc->get($cachekey);
			if ($result === null) {
				$dbr = self::getDB();
				$sql = "SELECT COUNT(*) AS count FROM templatelinks 
						WHERE tl_from = '" . $articleID . "' AND 
						tl_title IN ('Speedy', 'Stub', 'Copyvio','Copyviobot','Copyedit','Cleanup')";
				$res = $dbr->query($sql, __METHOD__);
				if ($res && ($row = $res->fetchObject())) {
					$result = intval($row->count > 0);
				} else {
					$result = 0;
				}
				$wgMemc->set($cachekey, $result);
			}
		}
		return $result;
	}

	/**
	 * Check whether the URL has an &oldid=... param
	 */
	private function hasOldidParam() {
		return $this->request && (boolean)$this->request->getVal('oldid');
	}

	/**
	 * Get (and cache) the database handle.
	 */
	private static function getDB() {
		static $dbr = null;
		if (!$dbr) $dbr = wfGetDB(DB_SLAVE);
		return $dbr;
	}

	/**
	* Check whether the article is yet to be nabbed and is short in length.  Use byte size as a proxy for length
	* for better performance.
	*/
	private function isShortUnNABbedArticle() {
		$ret = false;
		if ($this->article
			&& $this->title->exists() 
			&& $this->title->getNamespace() == NS_MAIN) 
		{
			if (!Newarticleboost::isNABbed(self::getDB(), $this->title->getArticleID())) {
				$ret = $this->article->getContentLength() < 1500; // ~1500 bytes
			}
		}
		return $ret;
	}
	
	/**
	 *
	 * Checks to see if article exists in Special:AccuracyPatrol.
	 * If so, it should be de-indexed.
	 * 
	 */
	private function isAccuracyPatrolArticle() {
		global $wgMemc;
		
		$result = false;
		
		$articleID = $this->title->getArticleID();
		if ($articleID) {
			$cachekey = wfMemcKey('accpatrol', $articleID);
			$result = $wgMemc->get($cachekey);
			if ($result === null) {
				$result = AccuracyPatrol::isInaccurate($articleID, self::getDB());
				$wgMemc->set($cachekey, $result);
			}
		}
		
		return $result;
	}
	
	/**
	 *
	 * Checks to see if an article has the accuracy OR nfd templates
	 * AND has less than 10,000 page views. If so,
	 * it is de-indexed.
	 * 
	 */
	private function isInaccurate() {
		global $wgMemc;
		
		$result = false;
		
		$articleID = $this->title->getArticleID();
		if ($articleID) {
			$cachekey = wfMemcKey('inaccurate', $articleID);
			$result = $wgMemc->get($cachekey);
			if ($result === null) {
				$dbr = self::getDB();
				$page_counter = $dbr->selectField(array('templatelinks', 'page'), 'page_counter', array('page_id'=>$articleID, 'tl_title' => array('Accuracy', 'Nfd'), 'page_id=tl_from'), __METHOD__);
				$result = ($page_counter !== false && $page_counter < 10000);
				
				$wgMemc->set($cachekey, $result);
			}
		}
	
		return $result;
	}
	
	/**
	 *
	 * Not in use currently.
	 * 
	 */
	private function isInDeindexList() {
		global $wgMemc;
		
		$exceptionList = array();
		
		$result = false;
		$articleID = $this->title->getArticleID();
		if ($articleID) {
			$cachekey = wfMemcKey('deindex-exception', $articleID);
			$result = $wgMemc->get($cachekey);
			if ($result === null) {
				$key = $this->title->getDBkey();
				$result = in_array($key, $exceptionList);
				
				$wgMemc->set($cachekey, $result);
			}
		}
	
		return $result;
	}

}


<?

$wgHooks['BeforePageDisplay'][] = array("RobotPolicy::setRobotPolicy");

class RobotPolicy {

	const POLICY_NOINDEX_FOLLOW = 'noindex,follow';
	const POLICY_NOINDEX_NOFOLLOW = 'noindex,nofollow';

	public static function setRobotPolicy() {
		global $wgOut;
		if (self::hasUserPageRestrictions()
			|| self::hasBadTemplate() 
			|| self::isShortUnNABbedArticle()
			|| self::isAccuracyPatrolArticle()
			|| self::isInaccurate())
		{
			$wgOut->setRobotPolicy(self::POLICY_NOINDEX_FOLLOW);
		} elseif (self::isProdButNotWWWHost()
			|| self::isPrintable()
			|| self::isOriginCDN()
			|| self::isNonExistentPage()
			|| self::hasOldidParam())
		{
			$wgOut->setRobotPolicy(self::POLICY_NOINDEX_NOFOLLOW);
		}
		return true;
	}

	/**
	 * Don't allow indexing of user pages where the contributor has less
	 * than 20 edits.  Also, ignore pages with a '/' in them, such as
	 * User:Reuben/Sandbox
	 */
	private static function hasUserPageRestrictions() {
		global $wgTitle;
		if ($wgTitle
			&& $wgTitle->getNamespace() == NS_USER
			&& (self::numEdits() < 20
				|| strpos($wgTitle->getText(), '/') !== false))
		{
			return true;
		}
		return false;
	}

	/**
	 * Check if we're on the production/english server but that the http
	 * Host header isn't www.wikihow.com
	 */
	private static function isProdButNotWWWHost() {
		global $wgServer;
		if (IS_PROD_EN_SITE) {
			$serverName = @$_SERVER['SERVER_NAME'];
			$www = 'www.wikihow.com';
			if ($wgServer == 'http://' . $www && $serverName != $www) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Test whether page is being displayed in "printable" form
	 */
	private static function isPrintable() {
		global $wgRequest;
		$isPrintable = $wgRequest->getVal('printable', '') == 'yes';
		return $isPrintable;
	}

	/**
	 * Check whether the origin of the request is the CDN
	 */
	private static function isOriginCDN() {
		$isCDNRequest = strpos(@$_SERVER['HTTP_X_INITIAL_URL'], 'http://pad') === 0;
        return IS_PROD_EN_SITE && $isCDNRequest;
	}

	/**
	 * Check whether page exists in DB or not
	 */
	private static function isNonExistentPage() {
		global $wgTitle;
		if (!$wgTitle ||
			($wgTitle->getArticleID() == 0
			 && $wgTitle->getNamespace() != NS_SPECIAL))
		{
			return true;
		}
		return false;
	}

	/**
	 * Retrieve number of edits by a user
	 */
	private static function numEdits() {
		global $wgTitle;
		if ($wgTitle->getNamespace() != NS_USER) {
			return 0;
		}
		$u = split("/", $wgTitle->getText());
		return User::getAuthorStats($u[0]);
	}

	/**
	 * Check to see whether certain templates are affixed to the article.
	 */
	private static function hasBadTemplate() {
		global $wgTitle, $wgMemc;
		$result = 0;
		if ($wgTitle) {
			$articleID = $wgTitle->getArticleID();
			if ($articleID) {
				$cachekey = wfMemcKey('badtmpl', $articleID);
				$result = $wgMemc->get($cachekey);
				if ($result === null) {
					$dbr = self::getDB();
					$sql = "SELECT COUNT(*) AS count FROM templatelinks WHERE tl_from = '" . $articleID . "' AND tl_title IN ('Speedy', 'Stub', 'Copyvio','Copyviobot','Copyedit','Cleanup')";
					$res = $dbr->query($sql, __METHOD__);
					if ($res && ($row = $res->fetchObject())) {
						$result = intval($row->count > 0);
					} else {
						$result = 0;
					}
					$wgMemc->set($cachekey, $result);
				}
			}
		}
		return $result;
	}

	/**
	 * Check whether the URL has an &oldid=... param
	 */
	private static function hasOldidParam() {
		global $wgRequest;
		return $wgRequest && !!$wgRequest->getVal('oldid');
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
	private static function isShortUnNABbedArticle() {
		global $wgTitle, $wgArticle;

		$ret = false;
		if ($wgTitle
			&& $wgArticle
			&& $wgTitle->exists() 
			&& $wgTitle->getNamespace() == NS_MAIN) 
		{
			if (!Newarticleboost::isNABbed(self::getDB(), $wgTitle->getArticleID())) {
				$ret = strlen($wgArticle->getContent()) < 1500; // ~1500 bytes
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
	private static function isAccuracyPatrolArticle() {
		global $wgTitle, $wgMemc;
		
		$result = false;
		
		if ($wgTitle) {
			$articleID = $wgTitle->getArticleID();
			if ($articleID) {
				
				$cachekey = wfMemcKey('accpatrol', $articleID);
				$result = $wgMemc->get($cachekey);
				if ($result === null) {
					$result = AccuracyPatrol::isInaccurate($articleID, self::getDB());
					$wgMemc->set($cachekey, $result);
				}
			}
		}
		
		return $result;
	}
	
	/**
	 *
	 * Checks to see if an article has the accuracy template
	 * on it AND has less than 10,000 page views. If so,
	 * it is de-indexed.
	 * 
	 */
	private static function isInaccurate() {
		global $wgTitle, $wgMemc;
		
		$result = false;
		
		if ($wgTitle) {
			$articleID = $wgTitle->getArticleID();
			if ($articleID) {
				
				$cachekey = wfMemcKey('inaccurate', $articleID);
				$result = $wgMemc->get($cachekey);
				if ($result === null) {
					$dbr = self::getDB();
					$page_counter = $dbr->selectField(array('templatelinks', 'page'), 'page_counter', array('page_id'=>$articleID, 'tl_title' => 'Accuracy', 'page_id=tl_from'), __METHOD__);
					$result = ($page_counter !== false && $page_counter < 10000);
					
					$wgMemc->set($cachekey, $result);
				}
			}
		}
		
		return $result;
	}
	
	/**
	 *
	 * Not in use currently.
	 * 
	 */
	private static function isInDeindexList() {
		global $wgTitle, $wgMemc;
		
		$exceptionList = array();
		
		$result = false;
		
		if ($wgTitle) {
			$articleID = $wgTitle->getArticleID();
			if ($articleID) {
				
				$cachekey = wfMemcKey('deindex-exception', $articleID);
				$result = $wgMemc->get($cachekey);
				if ($result === null) {
					$key = $wgTitle->getDBkey();
					$result = in_array($key, $exceptionList);
					
					$wgMemc->set($cachekey, $result);
				}
			}
		}
		
		return $result;
	}
}

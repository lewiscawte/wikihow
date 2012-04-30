<?php

/** db schema:
CREATE TABLE good_revision (
  gr_page INT(8) UNSIGNED NOT NULL,
  gr_rev INT(8) UNSIGNED NOT NULL,
  gr_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(gr_page)
);
*/

// callbacks that need to be set up for this feature to work
$wgHooks['ArticleFromTitle'][] = array('GoodRevision::onArticleFromTitle');
$wgHooks['MarkPatrolledDB'][] = array('GoodRevision::onMarkPatrolled');
$wgHooks['EditURLOptions'][] = array('GoodRevision::onEditURLOptions');
$wgHooks['Unpatrol'][] = array('GoodRevision::onUnpatrol');

class GoodRevision {

	var $title,
		$cachekey,
		$articleID;

	static $usedRev = array();

	/**
	 * Factory method to instantiate an object that determines the latest
	 * good revision for an article.
	 *
	 * Note: uses $articleID if it's provided, for efficiency.
	 */
	public static function newFromTitle(&$title, $articleID = 0) {
		global $wgLanguageCode;

		if ('en' != $wgLanguageCode
			|| !$title
			|| $title->getNamespace() != NS_MAIN)
		{
			return null;
		}

		return new GoodRevision($title, $articleID);
	}

	private function __construct(&$title, $articleID) {
		$this->title = $title;
		$this->articleID = $articleID ? $articleID : $title->getArticleID();
		$this->cachekey = wfMemcKey('goodrev', $this->articleID);
	}

	/**
	 * Look up the latest good revision for an article.
	 */
	public function latestGood() {
		global $wgMemc;

		$res = $wgMemc->get($this->cachekey);
		if (!$res) {
			$dbr = self::getDB();
			$res = $dbr->selectField('good_revision', 'gr_rev',
				array('gr_page = ' . $this->articleID),
				__METHOD__);
			if ($res) {
				$wgMemc->set($this->cachekey, $res);
			}
		}
		return intval($res);
	}

	/**
	 * Compute the latest good revisions table for all articles.
	 */
	public static function computeLatestAll() {
		$dbw = self::getDB('write');
		$one_week_ago = time() - 1 * 7 * 24 * 60 * 60;

		$updateRevFunc = function ($row) {
			$title = Title::newFromDBkey($row['page_title']);
			$goodRev = GoodRevision::newFromTitle($title, $row['page_id']);
			if ($goodRev) {
				$goodRev->updateRev($row['rev_id']);
			}
		};

		// grab all articles patrolled over the last week
		$sql = 'SELECT page_title, page_id, MAX(rc_id) AS rc_id
				FROM page, recentchanges 
				WHERE page_id = rc_cur_id AND
					page_namespace = 0 AND
					page_is_redirect = 0 AND
					rc_patrolled = 1 AND
					rc_timestamp >= FROM_UNIXTIME(' . $one_week_ago . ')
				GROUP BY rc_cur_id';
		$patrolled = array();
		$res = $dbw->query($sql, __METHOD__);
		while ($obj = $res->fetchObject()) {
			$patrolled[ $obj->page_id ] = (array)$obj;
		}

		foreach ($patrolled as $row) {
			$row['rev_id'] = self::getRevFromRC($row['rc_id']);
			$updateRevFunc($row);
		}

		$sql = 'SELECT page_title, page_id, MAX(rev_id) AS rev_id
				FROM page, revision 
				WHERE page_id = rev_page AND
					page_namespace = 0 AND
					page_is_redirect = 0 AND
					rev_timestamp < FROM_UNIXTIME(' . $one_week_ago . ')
				GROUP BY rev_page';
		$rows = array();
		$res = $dbw->query($sql, __METHOD__);
		while ($obj = $res->fetchObject()) {
			$rows[] = (array)$obj;
		}

		foreach ($rows as $row) {
			if (!isset( $patrolled[ $row['page_id'] ] )) {
				$updateRevFunc($row);
			}
		}
	}

	/**
	 * Removes all good revisions older than 1 week, so that if a revision
	 * avoided auto-patrolling and RC patrol somehow, the old revision isn't
	 * "stuck" forever.  Acts as a fail-safe.
	 */
/* no longer used
	public static function removeOld() {
		$dbw = self::getDB('write');
		$one_week_ago = time() - 1 * 7 * 24 * 60 * 60;
		$sql = 'DELETE FROM good_revision 
				WHERE gr_updated < FROM_UNIXTIME(' . $one_week_ago . ')';
		$dbw->query($sql, __METHOD__);
	}
*/

	/**
	 * Event called when a recent change is patrolled in RCPatrol or 
	 * auto-patrolled
	 */
	public static function onMarkPatrolled($rcid, &$article) {
		$title = null;
		if ($article) {
			$title = $article->getTitle();
		}
		if ($title && $rcid) {
			$goodRev = self::newFromTitle($title);
			if ($goodRev) {
				$rev = self::getRevFromRC($rcid);
				$goodRev->updateRev($rev);
				$title->purgeSquid();
			}
		}
		return true;
	}

	/**
	 * Update good revision for a page ID.
	 *
	 * NOTE: this doesn't clear anything out of memcache.  A memcache 
	 *   reset / clear may be needed.
	 */
	public function updateRev($rev) {
		global $wgMemc;
		if ($rev && $rev > $this->latestGood()) {
			$dbw = self::getDB('write');
			$sql = 'REPLACE INTO good_revision
					SET gr_page=' . $dbw->addQuotes($this->articleID) . ',
						gr_rev=' . $dbw->addQuotes($rev);

			$dbw->query($sql, __METHOD__);
			$wgMemc->set($this->cachekey, $rev);
		}
	}

	/**
	 * Turn a RecentChange id into a revision ID.
	 */
	private static function getRevFromRC($rcid) {
		$rc = RecentChange::newFromId($rcid, true);
		return $rc ? $rc->getAttribute('rc_this_oldid') : 0;
	}
	
	/**
	 * Grab the last good patrol
	 * - return true if the last edit on the article was patrolled
	 */
	public static function patrolledGood($t) {		
		//get the last revision
		$a = new Article($t);
		$a->loadLastEdit();
		$last_rev = $a->mLastRevision;
		
		//get the last good revision
		$goodRev = self::newFromTitle($t);
		$last_good_rev = $goodRev->latestGood();
		
		return $last_rev->mId == $last_good_rev;
	}

	/**
	 * Check if there are any cookies that start with "wiki_shared".  If
	 * there are, we don't consider the user anonymous.
	 */
	private static function isAnonymous() {
		global $wgCookiePrefix, $wgRequest;
		// for testing
		if ($wgRequest->getVal('anon', 'no') != 'no') {
			return true;
		}
		foreach ($_COOKIE as $name => $val) {
			if (strpos($name, $wgCookiePrefix) === 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Event called when an article is about to be fetched from the database
	 */
	public static function onArticleFromTitle(&$title, &$article) {
		global $wgRequest;

		// check if user is anonymous / uncookied, if not, show current rev
		$isAnon = self::isAnonymous();
		if (!$isAnon) {
			return true;
		}

		// if "oldid" is a URL param, we don't want to override the displayed
		// revision.
		if ($wgRequest->getVal('oldid')) {
			return true;
		}

		// fetch correct revision, if there is one
		$goodRev = self::newFromTitle($title);
		if ($goodRev) {
			$revid = $goodRev->latestGood();
		} else {
			$revid = 0;
		}

		// if there's a last good revision for the article, use it
		if ($revid) {
			$pageid = $goodRev->articleID;
			self::$usedRev[$pageid] = $revid;
			$article = new Article($title, $revid);
		}
		return true;
	}

	/**
	 * Grab info on which older revisions have been used
	 */
	public static function getUsedRev($pageid) {
		return @self::$usedRev[$pageid];
	}

	/**
	 * Callback for edit URL options
	 */
	public static function onEditURLOptions(&$useDefault) {
		global $wgArticle;
		$usedRev = self::getUsedRev( $wgArticle->getID() );
		$useDefault = !empty($usedRev);
		return true;
	}

	/**
	 * Callback when a list of revisions are unpatrolled
	 */
	public static function onUnpatrol(&$oldids) {
		$dbw = self::getDB('write');
		$sql = 'DELETE FROM good_revision
				WHERE gr_rev IN (' . join(',', $oldids) . ')';
		$dbw->query($sql, __METHOD__);
		return true;
	}

	/**
	 * Get a DB handle
	 */
	private static function getDB($type = 'read') {
		static $dbr = null, $dbw = null;
		if ($type != 'read') {
			if (!$dbw) $dbw = wfGetDB(DB_MASTER);
			return $dbw;
		} else {
			if (!$dbr) $dbr = wfGetDB(DB_SLAVE);
			return $dbr;
		}
	}

}


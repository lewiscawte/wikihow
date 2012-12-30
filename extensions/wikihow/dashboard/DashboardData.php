<?

if (!defined('MEDIAWIKI')) die();

class DashboardData {

	private $widgets = array();
	private $userid = 0;
	private $dbh = null;
	private $userCachekey = null;

	const GLOBAL_STATS_CACHEKEY = 'cd-stats'; // this is updated constantly
	const GLOBAL_STATS_EXPIRES = 300; // 5 minutes ttl in case of bad updates
	const GLOBAL_OPTS_CACHEKEY = 'cd-opts';
	const GLOBAL_OPTS_EXPIRES = 600; // 10 minutes
	const USER_STATS_EXPIRES = 600; // 10 minutes

    public function __construct() {
		global $wgUser;

		// load the widget classes into the class variable $widgets
		$this->loadWidgets();

		// get user ID, which is 0 if not logged in
		$this->userid = $wgUser->getID();

		if ($this->userid) {
			$this->userCachekey = 'cd-user-' . $this->userid;
		}
    }

	/**
	 * Loads the list $wgWidgetList of widgets into the $widgets class 
	 * variable.  This variable is defined in CommunityDashboard.php.
	 */
	private function loadWidgets() {
		global $wgWidgetList; // defined in CommunityDashboard.php

		foreach ($wgWidgetList as $widgetName) {
			if (!preg_match('@^[A-Za-z0-9]+$@', $widgetName)) {
				die("error: widget name not valid: $widgetName");
			}

			include_once( dirname(__FILE__) . '/widgets/' . $widgetName . '.php' );
			if (!class_exists($widgetName)) {
				die("error: widget class didn't load correctly: $widgetName");
			}

			$this->widgets[$widgetName] = new $widgetName($widgetName);
		}
	}

	/**
	 * Return the list of instantiated widgets.
	 */
	public function getWidgets() {
		return $this->widgets;
	}

	/**
	 * Store and return a DB handle so that we don't need to re-create
	 * it for every method call.  Note: DB handle is read-only (to a
	 * replicated slave).
	 */
	private function dbHandle() {
		if (!$this->dbh) {
			$this->dbh = wfGetDB(DB_SLAVE);
		}
		return $this->dbh;
	}

	/**
	 * Compile all the "global" (ie, not user-specific) stats needed to
	 * display the community dashboard page.  This call is made every
	 */
	public function compileStatsData() {
		global $wgMemc;

		$dbr = $this->dbHandle();

		$widgetStats = array();
		$widgets = $this->widgets;
		foreach ($widgets as $name => $widget) {
			try {
				$widgetStats[$name] = $widget->compileStatsData($dbr);
			} catch(Exception $e) {
				$widgetStats[$name] = array('error' => "error getting $name widget data");
			}
		}
		$stats = array('widgets' => $widgetStats);
//print_r($stats);
		$wgMemc->set(self::GLOBAL_STATS_CACHEKEY, $stats, self::GLOBAL_STATS_EXPIRES);
	}

	/**
	 * Grab the latest update to the stats from Memcache.  Note that
	 * compileStatsData() should have been called seconds before this call.
	 * No database calls are done by this method since it could be called
	 * very often.
	 */
	public function getStatsData() {
		global $wgMemc;
		return $wgMemc->get(self::GLOBAL_STATS_CACHEKEY);
	}

	/*
	 * DB schema:
	 CREATE TABLE community_dashboard_users(
		cdu_userid INT UNSIGNED PRIMARY KEY, 
		cdu_prefs_json TEXT,
		cdu_completion_json TEXT
	 );
	 *
	 * Note: in community_dashboard_users, I wanted to separate 
	 *   cdu_completion_json from cdu_prefs_json because there's a query in
	 *   resetDailyCompletionAllUsers() that would have been very slow if
	 *   the data was one column
	 *
	 * initial values:
	 INSERT INTO community_dashboard_users SET 
	 	cdu_userid='', 
	 	cdu_prefs_json='{"ordering":["RecentChangesAppWidget"]}',
		cdu_completion_json='{"RecentChangesAppWidget":0}';
	 */

	/**
	 * Grab user-specific data from Memcache or database.  Includes widget
	 * placement info and goal/task completion data.
	 */
	public function loadUserData($cacheWrite = true) {
		global $wgMemc;

		if ($this->userid > 0) {
			$row = $wgMemc->get($this->userCachekey);
$row=null;
			if ($row) {
				return $row;
			}

			$dbr = $this->dbHandle();
			$row = (array)$dbr->selectRow(
				'community_dashboard_users',
				'cdu_prefs_json, cdu_completion_json',
				'cdu_userid = ' . $this->userid,
				__METHOD__);

			// Make sure the prefs and completion arrays are in a good state
			if (!$row) {
				$row = array(
					'prefs' => $wgWidgetList,
					'completion' => array(),
				);
			} else {
				if ($row['cdu_completion_json']) {
					$row['completion'] =
						(array)json_decode($row['cdu_completion_json']);
				}
				if (!is_array($row['completion'])) {
					$row['completion'] = array();
				}

				if ($row['cdu_prefs_json']) {
					$row['prefs'] = 
						(array)json_decode($row['cdu_prefs_json']);
				}
				if (!is_array($row['prefs'])) {
					$row['prefs'] = array();
				}
			}
			unset($row['cdu_completion_json']);
			unset($row['cdu_prefs_json']);

			if ($cacheWrite) {
				$wgMemc->set($this->userCachekey, $row, self::USER_STATS_EXPIRES);
			}

			return $row;
		} else {
			return array();
		}
	}

	// Save the user data row back to the database
	private function saveUserData($row) {
		global $wgWidgetList;

		if ($this->userid) {
			$dbw = wfGetDB(DB_MASTER);

			if (!is_array($row['completion'])) {
				$row['completion'] = array();
			}
			if (!is_array($row['prefs'])) {
				$row['prefs'] = $wgWidgetList;
			}
			$completion = json_encode($row['completion']);
			$prefs = json_encode($row['prefs']);
			$sql = 'REPLACE INTO community_dashboard_users ' .
				'SET cdu_completion_json=' . $dbw->addQuotes($completion) . ', ' .
				'  cdu_prefs_json=' . $dbw->addQuotes($prefs) . ' ' .
				'WHERE cdu_userid = ' . intval($this->userid);
			$dbw->query($sql, __METHOD__);

			$wgMemc->set($this->userCachekey, $row, self::USER_STATS_EXPIRES);
		}
	}

	/**
	 * Marks an app "completed" for that day.  These values are reset at
	 * midnight.
	 */
	public function setDailyCompletion($app) {
		global $wgWidgetList; // defined in CommunityDashboard.php
		global $wgMemc;

		if ($this->userid > 0 && isset($this->widgets[$app])) {
			$row = $this->loadUserData(false);

			// Already set in memcache or DB, so we don't need to save
			if ($row['completion'] && $row['completion'][$app])
			{
				return;
			}

			// Set this app as "completed" for today
			$row['completion'][$app] = 1;

			$this->saveUserData($row);
		}
	}

	/**
	 * Reset all app "completed" data for that day.  Called at midnight.
	 */
	public static function resetDailyCompletionAllUsers() {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "UPDATE community_dashboard_users SET cdu_completion_json=''";
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Set user-specific page preferences.
	 */
	public function saveUserPrefs($prefs) {
		if ($this->userid > 0) {
			$row = $this->loadUserData(false);
			$row['prefs'] = $prefs;
			$this->saveUserData($row);
		}
	}

	/*
	 * DB schema:
	 CREATE TABLE community_dashboard_opts(
		cdo_priorities_json TEXT, 
		cdo_thresholds_json TEXT
	 );
	 *
	 * initial values:
	 INSERT INTO community_dashboard_opts SET 
	 	cdo_priorities_json='["RecentChangesAppWidget"]', 
	 	cdo_thresholds_json='{"RecentChangesAppWidget":{"mid":250,"high":500}}';
	 */

	/**
	 * Grab global static data, such as widget thresholds and community
	 * priorities.
	 */
	public function loadStaticGlobalOpts() {
		global $wgMemc;

		$cachekey = self::GLOBAL_OPTS_CACHEKEY;

		$opts = $wgMemc->get($cachekey);
		if (!$opts) {
			$dbr = $this->dbHandle();

			// get any over-arching community priority widgets to display 
			// them first
			$opts = (array)$dbr->selectRow(
				'community_dashboard_opts', 
				'cdo_priorities_json, cdo_thresholds_json', 
				'', __METHOD__);

			$wgMemc->set($cachekey, $opts, self::GLOBAL_OPTS_EXPIRES);
		}

		return $opts;
	}

	/**
	 * Replace global opts, like community priorities and thresholds.
	 */
	public function saveStaticGlobalOpts($opts) {
		global $wgMemc;

		$cachekey = self::GLOBAL_OPTS_CACHEKEY;
		$wgMemc->delete($cachekey);

		print "not implemented yet\n";
	}

	/**
	 *
	 */
	public function getLeaderboardData($widgetName){
		$dbr = $this->dbHandle();

		$widgets = $this->widgets;
		foreach ($widgets as $name => $widget) {
			if($name == $widgetName){
				try {
					$leaderboardData = $widget->getLeaderboard($dbr);
				} catch(Exception $e) {
					//$widgetStats[$name] = array('error' => "error getting $name widget data");
				}
				return $leaderboardData;
			}
		}
	}

}

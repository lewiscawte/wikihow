<?

class RecentChangesAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/**
	 * Provides the visual title of the widget
	 */
	public function getTitle(){
		return wfMsg('cd-rc-title');
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$user = RCWidget::getLastPatroller($dbr);
		
		return $this->populateUserObject($user['id'], $user['date']);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$user = RCWidget::getTopPatroller($dbr);

		return $this->populateUserObject($user['id'], $user['date']);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow){
		$link = "<a href='/Special:RCPatrol' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('RecentChangesAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('RecentChangesAppWidget.css');
	}

	/*
	 * Returns the number of changes left to be patrolled.
	 */
	public function getCount(&$dbr){
		return RCWidget::getUnpatrolledEdits($dbr);
	}

	public function getUserCount(&$dbr){
		$standings = new RCPatrolStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(&$dbr){
		$standings = new RCPatrolStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	public function getCountDescription(){
		return wfMsg('cd-rc-countdescription');
	}

	public function  getUserCountDescription(){
		return wfMsg('cd-rc-usercount');
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = Leaderboard::getRCEdits($starttimestamp);

		//for some reason we shouldn't be sorting if its RC data
		//arsort($data);

		return $data;

	}

}

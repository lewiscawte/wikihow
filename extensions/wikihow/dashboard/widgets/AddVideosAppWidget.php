<?

class AddVideosAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus) {
		if($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:Videoadder' class='comdash-start'>Start";
		else if($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Login?returnto=Special:CommunityDashboard' class='comdash-start'>Login";
		else if($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-New-Article-Booster-on-wikiHow' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	/**
	 * Provides the visual title of the widget
	 */
	public function getTitle() {
		return wfMsg('cd-AddVideos-title');
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr) {
		global $wgUser;
		$sk = $wgUser->getSkin();

		$user = Videoadder::getLastVA($dbr);

		return $this->populateUserObject($user['id'], $user['date']);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr) {
		global $wgUser;
		$sk = $wgUser->getSkin();

		$user = Videoadder::getHighestVA($dbr);
		return $this->populateUserObject($user['id'], $user['date']);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('AddVideosAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('AddVideosAppWidget.css');
	}

	/*
	 * Returns the number of videos left to be added.
	 */
	public function getCount(&$dbr){
		return Videoadder::getArticleCount($dbr);
	}

	public function getCountDescription(){
		if($this->getBaseline() == 0)
			return wfMsg('cd-addVideos-countdescription');
		else
			return wfMsg('cd-addVideos-countdescription-adjusted');
	}

	public function  getUserCountDescription(){
		return wfMsg('cd-addVideos-usercount');
	}

	public function getUserCount(){
		$standings = new VideoStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new VideoStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = Leaderboard::getVideosReviewed($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/videos_reviewed?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if(!$isLoggedIn)
			return false;
		else if($isLoggedIn && $userId == 0)
			return false;
		else{
			$user = new User();
			$user->setID($userId);
			return in_array( 'videoadder', $user->getRights());
		}
	}

}

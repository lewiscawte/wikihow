<?

class NabAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:Newarticleboost' class='comdash-start'>Start";
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
		return wfMsg('cd-nab-title');
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr) {
		$user = Newarticleboost::getLastNAB($dbr);

		return $this->populateUserObject($user['id'], $user['date']);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$user = Newarticleboost::getHighestNAB($dbr);

		return $this->populateUserObject($user['id'], $user['date']);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('NabAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('NabAppWidget.css');
	}

	/*
	 * Returns the number of articles left to be NABed.
	 */
	public function getCount(&$dbr){
		return Newarticleboost::getNABCount($dbr);
	}

	public function getCountDescription(){
		if($this->getBaseline() == 0)
			return wfMsg('cd-nab-countdescription');
		else
			return wfMsg('cd-nab-countdescription-adjusted');
	}

	public function  getUserCountDescription(){
		return wfMsg('cd-nab-usercount');
	}

	public function getUserCount(&$dbr){
		global $wgUser;
		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		return Newarticleboost::getUserNABCount($dbr, $wgUser->getID(), $starttimestamp);
	}

	public function getAverageCount(){
		$standings = new NABStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	function getLeaderboardData(&$dbr, $starttimestamp){

		$data = Leaderboard::getArticlesNABed($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/articles_nabed?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if(!$isLoggedIn)
			return false;
		else if($isLoggedIn && $userId == 0)
			return false;
		else{
			$user = new User();
			$user->setID($userId);
			return in_array( 'newarticlepatrol', $user->getRights());
		}
	}

}

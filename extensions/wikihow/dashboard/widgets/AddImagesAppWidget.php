<?

class AddImagesAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:IntroImageAdder' class='comdash-start'>Start";
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
	public function getTitle(){
		return wfMsg('cd-addImages-title');
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('image', array('img_user', 'img_timestamp'), array('img_user_text != "WRM"'), 'AddImagesAppWidget::getLastImage', array("ORDER BY"=>"img_timestamp DESC", "LIMIT"=>1));

		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->img_user, $row->img_timestamp);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$res = $dbr->select('image', array('*', 'count(img_user) as img_count', 'MAX(img_timestamp) as recent_timestamp'), array("img_user_text != 'WRM'", 'img_timestamp > "' . $starttimestamp . '"'), 'StubAppWidget::getLastContributor', array("GROUP BY" => 'img_user', "ORDER BY"=>"img_count DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->img_user, $row->recent_timestamp);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('AddImagesAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('AddImagesAppWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr){
		return IntroImageAdder::getArticleCount($dbr);
	}

	public function getUserCount(){
		$standings = new IntroImageStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new IntroImageStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	public function getCountDescription(){
		if($this->getBaseline() == 0)
			return wfMsg('cd-addImages-countdescription');
		else
			return wfMsg('cd-addImages-countdescription-adjusted');
	}

	public function  getUserCountDescription(){
		return wfMsg('cd-addImages-usercount');
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){

		$data = Leaderboard::getImagesAdded($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/images_added?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if(!$isLoggedIn)
			return false;
		else
			return true;
	}

}

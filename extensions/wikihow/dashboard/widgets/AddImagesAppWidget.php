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
			$link = "<a href='/Special:Userlogin?returnto=Special:IntroImageAdder' class='comdash-login'>Login";
		else if($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-New-Article-Booster-on-wikiHow' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	public function getMWName(){
		return "addImages";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$sql = "";
		$bots = User::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = "rc_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if($sql != "")
			$res = $dbr->select('recentchanges', array('*'), array("rc_comment='Added Image using ImageAdder Tool'", $sql), 'AddImagesAppWidget::getLastImage', array("ORDER BY"=>"rc_id DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('recentchanges', array('*'), array("rc_comment='Added Image using ImageAdder Tool'"), 'AddImagesAppWidget::getLastImage', array("ORDER BY"=>"rc_id DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->rc_user, $row->rc_timestamp);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "";
		$bots = User::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = "img_user NOT IN (" . $dbr->makeList($bots) . ")";
		}
		
		if($sql != "")
			$res = $dbr->select('image', array('*', 'count(img_user) as img_count', 'MAX(img_timestamp) as recent_timestamp'), array("img_user_text != 'WRM'", 'img_timestamp > "' . $starttimestamp . '"', $sql), 'AddImagesAppWidget::getTopContributor', array("GROUP BY" => 'img_user', "ORDER BY"=>"img_count DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('image', array('*', 'count(img_user) as img_count', 'MAX(img_timestamp) as recent_timestamp'), array("img_user_text != 'WRM'", 'img_timestamp > "' . $starttimestamp . '"'), 'AddImagesAppWidget::getTopContributor', array("GROUP BY" => 'img_user', "ORDER BY"=>"img_count DESC", "LIMIT"=>1));
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

<?

class WriteAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:ListRequestedTopics' class='comdash-start'>Start";
		else if($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:ListRequestedTopics' class='comdash-login'>Login";
		else if($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-New-Article-Booster-on-wikiHow' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	public function getMWName(){
		return "Write";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('recentchanges', array('*'), array('rc_new' => '1', 'rc_namespace' => '0', 'rc_user_text != "WRM"'), 'WriteAppWidget::getLastContributor', array("ORDER BY"=>"rc_timestamp DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->rc_user, $row->rc_timestamp);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$res = $dbr->select(array('recentchanges', 'page'), array('*', 'count(*) as C', 'MAX(rc_timestamp) as recent_timestamp'), array('rc_cur_id = page_id', 'page_is_redirect' => 0, 'rc_new' => '1', 'rc_timestamp > "' . $starttimestamp . '"', 'rc_namespace' => '0', 'rc_user != "0"', 'rc_user_text != "WRM"'), 'WriteAppWidget::getTopContributor', array("GROUP BY" => 'rc_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->rc_user, $row->recent_timestamp);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('WriteAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('WriteAppWidget.css');
	}

	/*
	 * Returns the number of videos left to be added.
	 */
	public function getCount(&$dbr){
		return ListRequestedTopics::getUnwrittenTopics(true);
	}

	public function getUserCount(){
		$standings = new VideoStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new ArticleWrittenStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = Leaderboard::getArticlesWritten($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/articles_written?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		return true;
	}

}

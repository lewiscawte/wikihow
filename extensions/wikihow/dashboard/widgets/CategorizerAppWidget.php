<?

class CategorizerAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/**
	 * Provides the visual title of the widget
	 */
	public function getTitle(){
		return wfMsg('cd-cat-title');
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('recentchanges', array('*'), array('rc_comment like "categorization"', 'rc_user_text != "WRM"'), 'CategorizationAppWidget::getLastContributor', array("ORDER BY"=>"rc_timestamp DESC", "LIMIT"=>1));
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
		$res = $dbr->select('recentchanges', array('*', 'count(*) as C', 'MAX(rc_timestamp) as recent_timestamp'), array('rc_comment like "categorization"', 'rc_timestamp > "' . $starttimestamp . '"', 'rc_user_text != "WRM"'), 'CategorizationAppWidget::getLastContributor', array("GROUP BY" => 'rc_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		return $this->populateUserObject($row->rc_user, $row->recent_timestamp);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow){
		$link = "<a href='/Special:Uncategorizedpages' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('CategorizerAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('CategorizerAppWidget.css');
	}

	/*
	 * Returns the number of changes left to be patrolled.
	 */
	public function getCount(&$dbr){
		$templates = wfMsgForContent('templates_further_editing');
		$t_arr = split("\n", $templates);
		$not_in  = " AND cl_to NOT IN ('" . implode("','", $t_arr) . "')";

		$sql = "SELECT count(*) as C
			FROM page
			LEFT JOIN categorylinks ON page_id=cl_from $not_in
			WHERE cl_from IS NULL AND page_namespace='0' AND page_is_redirect='0'
			";
		$res = $dbr->query($sql);

		$row = $dbr->fetchRow($res);
		return $row['C'];
	}

	public function getUserCount(&$dbr){
		$standings = new CategorizationStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(&$dbr){
		$standings = new CategorizationStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	public function getCountDescription(){
		return wfMsg('cd-cat-countdescription');
	}

	public function  getUserCountDescription(){
		return wfMsg('cd-cat-usercount');
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = Leaderboard::getArticlesCategorized($starttimestamp);
		arsort($data);

		return $data;

	}

}

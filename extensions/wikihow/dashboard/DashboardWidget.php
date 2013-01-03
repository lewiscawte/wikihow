<?

/**
 * DashboardWidget should be subclassed by all widgets in the widgets/ dir
 */
abstract class DashboardWidget {

	protected $widgetName;
	static $thresholds = array();
	static $completion = array();
	static $baselines = array();
	static $max_username_length;

	const WIDGET_LOGIN = "login";
	const WIDGET_DISABLED = "disabled";
	const WIDGET_ENABLED = "";

	const GLOBAL_WIDGET_MEDIAN = 21; // the is the leaderboard position that's used to "calculate" the median

	/**
	 * The constructor should be called by every subclass as
	 * parent::__construct() in their constructor.
	 */
	protected function __construct($name) {
		$this->widgetName = $name;
	}

	/**
	 * Returns the name of the widget.
	 */
	public function getName() {
		return $this->widgetName;
	}

	/**
	 * Returns HTML internals of the widget box.
	 */
	public function getHTML($initialData) {
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'data' => $initialData,
			'completedToday' => $this->getCompletion(),
			'thresholds' => $this->getThresholds(),
			'header' => $this->getHeaderHTML(),
			'weather' => $this->getWeatherClass($initialData['ct']),
			'countDescription' => $this->getCountDescription(),
			'moreLink' => $this->getMoreLink(),
			'widgetName' => $this->getName(),
			'title' => $this->getLeaderboardTitle(),
			'getAvatarLink' => array($this, 'getAvatarLink'),
			'getUserLink' => array($this, 'getUserLink'),
			'status' => $this->getWidgetStatus(),
			'login' => $this->getLoginLink(),
			'widgetMWName' => $this->getMWName(),
		));
		$html = $tmpl->execute('widgets/dashboardWidget.tmpl.php');
		return $html;
	}

	/**
	 * Returns the HTML that contains a div that has widget-specific HTML and
	 * allows CSS to control how all containers are displayed.  This method
	 * is calls getHTML() and is called by the dashboard display.
	 */
	public function getContainerHTML($initialData) {
		return '<div class="comdash-widget-box comdash-widget-' . $this->getName() . ' ' . $this->getWidgetStatus() . '"><div class="status" id="status-' . $this->getName() . '"></div>' . $this->getHTML($initialData) . '</div>';
	}

	public function getLoginLink(){
		return "<a href='/Special:Userlogin?returnto=Special:CommunityDashboard'>log in</a>";
	}

	/*
	 * Needs to return the visual title for
	 * this widget.
	 */
	public function getTitle(){
		return wfMsg('cd-' . $this->getMWName() . '-title');
	}

	protected abstract function getLeaderboardTitle();

	protected abstract function getMWName();

	/**
	 *
	 * Returns the visual count for this widget after adjusting for the baseline.
	 * If the adjustment makes the count less than zero, zero is return. Number
	 * is formatted with comma.
	 */
	public function getAdjustedCount(&$dbr){
		//echo $this->getName();
		$count = $this->getCount($dbr) - $this->getBaseline();
		if($count < 0)
			$count = 0;

		return number_format($count, 0, "", ",");
	}

	/*
	 * Must be implemented by subclass. Needs to return the html for the last
	 * contributor shown at the bottom of the widget
	 */
	protected abstract function getLastContributor(&$dbr);

	/*
	 * Takes a user ID and a timestamp and creates an object with avatar,
	 * user link, and date to be used on the front end.
	 *
	 */
	public function populateUserObject($userId, $timestamp){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$imguser = array();
		$imguser['id'] = $userId;
		$imguser['date'] = wfTimeAgo($timestamp);
		if(strpos($imguser['date'], "Bad") !== false)
			$imguser['date'] = "Just now";
		$u = User::newFromId($imguser['id']);
		$img = $this->getUserPic($u);
		$data = array();

		$data['im'] = $img;
		if (strpos($img, 'df') === FALSE)
			$data['hp'] = Avatar::getHashPath("$userId.jpg");
		else
			$data['hp'] = "";

		if($userId == 0)
			$data['na'] = "Anonymous";
		else
			$data['na'] = $u->getName();
		$data['da'] = $imguser['date'];
		return $data;
	}

	/*
	 * Must be implemented by subclass. Needs to return the html for the top
	 * contributor shown at the bottom of the widget
	 */
	protected abstract function getTopContributor(&$dbr);

	/*
	 * Returns an array with the user data for this widget
	 */
	public function getUserStats(&$dbr){
		$data = array();
		$data['start'] = $this->getStartLink(false, $this->getWidgetStatus());
		$data['usercd'] = $this->getUserCountDescription();
		$data['usercount'] = $this->getUserCount($dbr);
		$data['averagecount'] = $this->getAverageCount($dbr);

		return $data;
	}

	/*
	 * Must be implemented by subclass. Returns the absolute count for this
	 * widget (NOT adjusted for the baseline).
	 */
	protected abstract function getCount(&$dbr);

	public function getLeaderboard(&$dbr){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$data = $this->getLeaderboardData($dbr, $starttimestamp);

		$count = 0;
		$leaderboardData = array();

		foreach($data as $key => $value) {
			$u = new User();
			$u->setName($key);
			if (($value > 0) && ($key != '') && ($u->getName() != "WRM")) {
				$leaderboardData[$count] = array();
				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}

				$leaderboardData[$count]['img'] = $img;
				$leaderboardData[$count]['user'] = $sk->makeLinkObj($u->getUserPage(), $u->getName());
				$leaderboardData[$count]['count'] = $value;

				$data[$key] = $value * -1;

				$count++;
				if($count >= 6)
					break;
			}
		}

		$html = "";

		$count = 0;
		foreach($leaderboardData as $leader) {
			if($count == 0)
				$html .= "<tr class='first'>";
			else
				$html .= "<tr>";

			$html .= "<td class='avatar'>" . $leader['img'] . "</td>";
			$html .= "<td class='user'>" . $leader['user'] . "</td>";
			$html .= "<td class='count'>" . $leader['count'] . "</td>";

			$html .= "</tr>";

			$count++;
		}

		$jsondata = array();
		$jsondata['leaderboard'] = $html;

		return json_encode($jsondata);
	}

	/**
	 * Returns json encoded leaderboard data for this widget
	 */
	protected abstract function getLeaderboardData(&$dbr, $starttimestamp);

	/**
	 *
	 * returns the html for the "more" link at the bottom of each widget
	 */
	public function getMoreLink(){
		return "<a href='#' class='comdash-more' id='comdash-more-" . $this->widgetName . "'></a>";
	}

	/*
	 * Returns the class for the weather icon
	 * on the widget.
	 */
	public function getWeatherClass($count){
		//for the case when there are errors
		if($count == "")
			return "stormy";

		$thresholds = self::getThresholds();
		$count = str_replace(",", "", $count); //need to remove commas to compare with thresholds
		if($count <= $thresholds['low']){
			return "sunny";
		}
		else if($count <= $thresholds['med']){
			return "cloudy";
		}
		else if($count <= $thresholds['high']){
			return "rainy";
		}
		else{
			return "stormy";
		}
	}

	/*
	 * Must be implemented by sublcass. Needs to return the start link that appears
	 * in the header of the widget.
	 */
	protected abstract function getStartLink($showArrow, $widgetStatus);

	/**
	 * Returns the HTML for just the top part of the widget
	 */
	public function getHeaderHTML(){
		return '<div class="comdash-widget-header">' . $this->getTitle() . $this->getStartLink(true, $this->getWidgetStatus()) . '</div>';
	}

	/**
	 * Returns a string to be displayed under the current count
	 * in the widget.
	 */
	public function getCountDescription(){
		if($this->getBaseline() == 0)
			return wfMsg('cd-' . $this->getMWName() . '-countdescription');
		else
			return wfMsg('cd-' . $this->getMWName() . '-countdescription-adjusted');
	}

	/**
	 * Returns a string to be displayed next to the users
	 * leaderboard count for this widget. 
	 */

	public function  getUserCountDescription(){
		return wfMsg('cd-' . $this->getMWName() . '-usercount');
	}

	/**
	 * Returns an array that lists the Javascript files used by the widget.
	 * These files are included by the container and minimized.
	 */
	public abstract function getJSFiles();

	/**
	 * Returns an array that lists the Javascript files used by the widget.
	 * These files are included by the container and minimized.
	 */
	public abstract function getCSSFiles();

	/**
	 * Returns the non-cached version of the stats used by the widget.  This
	 * method will repeatedly be called by a server-side script that updates
	 * stats.
	 */
	public function compileStatsData(&$dbr) {
		$data = array();
		$data['ct'] = $this->getAdjustedCount($dbr);
		$data['lt'] = $this->getLastContributor($dbr);
		$data['tp'] = $this->getTopContributor($dbr);
		return $data;
	}

	public static function setMaxUsernameLength($length){
		self::$max_username_length = $length;
	}

	/**
	 * Sets the thresholds for all apps.
	 * @param $thresholds an array like array('App1'=>array('mid'=>'1'),...)
	 */
	public static function setThresholds($thresholds) {
		self::$thresholds = $thresholds;
	}

	/**
	 * Gets the thresholds for a particular app.
	 */
	public function getThresholds() {
		$name = $this->getName();
		$thresholds = (array)@self::$thresholds[$name];
		return $thresholds;
	}

	/**
	 * Sets the baseline for all apps.
	 * @param $baseline an array like array('App1'=>200,...)
	 */
	public static function setBaselines($baselines) {
		self::$baselines = $baselines;
	}

	/**
	 * Gets the thresholds for a particular app.
	 */
	public function getBaseline() {
		$name = $this->getName();
		$baselines = self::$baselines[$name];
		return $baselines;
	}

	/**
	 * Sets the completion flag for all apps.
	 * @param $completion an array like array('App1'=>1,...)
	 */
	public static function setCompletion($completion) {
		self::$completion = $completion;
	}

	/**
	 * Gets the thresholds for a particular app.
	 */
	public function getCompletion() {
		$name = $this->getName();
		$completion = !!@self::$completion[$name];
		return $completion;
	}

	/**
	 * Return a new avatar img in a compact format for transmission
	 */
    protected function getUserPic(&$u) {
		$raw = Avatar::getAvatarRaw($u->getName());
		return $raw['type'] . ':' . $raw['url'];
	}

	/**
	 * Return a URL from the av:... compressed avatar image format.
	 */
	private function getAvatarURL($img) {
		if (preg_match('@^([^:]*)(:(.*))?$@', $img, $m)) {
			$type = $m[1];
			if ($type != 'df') $param = $m[3];
		} else {
			$type = 'df';
		}
		if ($type == 'df') {
			return wfGetPad('/skins/WikiHow/images/default_profile.png');
		} else if ($type == 'fb') {
			return $param;
		} else {
			$filename = explode("?", $param);
			return wfGetPad(Avatar::getAvatarOutPath($filename[0]) . $param);
		}
	}

	/**
	 * Return the html for a link to a user's page.  (TODO: it'd be
	 * better to use the makeLinkObj() function here.)
	 */
	public function getUserLink($username) {
		if($username == "Anonymous")
			return '<a href="/wikiHow:Anonymous" title="wikiHow:Anonymous">Anonymous</a>';
		if(strlen($username) > self::$max_username_length)
			$shortenedName = substr($username, 0, self::$max_username_length - 3) . "...";
		else
			$shortenedName = $username;
		return '<a href="/User:' . $username . '" title="User:' . $username . '">' . $shortenedName . '</a>';
	}

	/**
	 * Return the html for an img tag to the avatar pic.
	 */
	public function getAvatarLink($img) {
		return '<img src="' . self::getAvatarURL($img) . '" />';
	}

	public function getWidgetStatus(){
		global $wgUser;
		
		if($wgUser->getID() > 0){
			if($this->isAllowed(true, $wgUser->getID()))
				return DashboardWidget::WIDGET_ENABLED;
			else
				return DashboardWidget::WIDGET_DISABLED;
		}
		else{
			if($this->isAllowed(false))
				return DashboardWidget::WIDGET_ENABLED;
			else if($this->isAllowed(true))
				return DashboardWidget::WIDGET_LOGIN;
			else
				return DashboardWidget::WIDGET_DISABLED;
		}
	}

	public abstract function isAllowed($isLoggedIn, $userId=0);

}


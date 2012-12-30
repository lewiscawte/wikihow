<?

class AddVideosAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink(){
		return "<a href='/Special:Videoadder' class='comdash-start'>Start <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''></a>";
	}

	/**
	 * Provides the visual title of the widget
	 */
	public function getTitle(){
		return wfMsg('cd-AddVideos-title');
	}


	/**
	 *
	 * Gets the current weather class for this
	 * widget
	 */
	public function getWeatherClass(){
		return "rainy";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$username = "Bsteudel";
		$u = new User();
		$u->setName($username);
		$img = Avatar::getPicture($u->getName(), true);
		if ($img == '') {
			$img = Avatar::getDefaultPicture();
		}

		$data = array();
		$data['im'] = $img;
		$data['na'] = $sk->makeLinkObj($u->getUserPage(), $u->getName());
		$data['da'] = $user['date'];
		return $data;
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$username = "JackHerrick";
		$u = new User();
		$u->setName($username);
		$img = Avatar::getPicture($u->getName(), true);
		if ($img == '') {
			$img = Avatar::getDefaultPicture();
		}

		$data = array();
		$data['im'] = $img;
		$data['na'] = $sk->makeLinkObj($u->getUserPage(), $u->getName());
		$data['da'] = $user['date'];
		return $data;
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

	/**
	 * Called on the servers as part of a maintenance method.
	 */
	public function compileStatsData(&$dbr) {
		$data = array();
		$data['ct'] = number_format(Videoadder::getArticleCount($dbr), 0, "", ",");
		$data['lt'] = self::getLastContributor($dbr);
		$data['tp'] = self::getTopContributor($dbr);
		return $data;
	}

	public function getCountDescription(){
		return 'videos needed';
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = Leaderboard::getVideosReviewed($starttimestamp);

		return $data;

	}

}

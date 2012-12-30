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
	 * Gets the current weather class for this
	 * widget
	 */
	public function getWeatherClass(){
		return "sunny";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$user = RCWidget::getLastPatroller($dbr);
		$u = new User();
		$u->setID($user['id']);
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

		$user = RCWidget::getTopPatroller($dbr);
		$u = new User();
		$u->setID($user['id']);
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

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink(){
		return "<a href='#'>Start <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''></a>";
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

	/**
	 * Called on the servers as part of a maintenance method.
	 */
	public function compileStatsData(&$dbr) {
		$data = array();
		$data['ct'] = RCWidget::getUnpatrolledEdits($dbr);
		$data['lt'] = self::getLastContributor($dbr);
		$data['tp'] = self::getTopContributor($dbr);
		return $data;
	}

	public function getCountDescription(){
		return 'changes to go';
	}

}


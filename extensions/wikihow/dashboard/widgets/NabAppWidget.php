<?

class NabAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
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
			'footer' => $this->getFooterHTML(),
			'weather' => $this->getWeatherClass(),
		));
		$html = $tmpl->execute('nab.tmpl.php');
		return $html;
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink(){
		return "<a href='#' onclick='alert(\"not implemented yet\")'>Start <img src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' alt=''></a>";
	}

	/**
	 * Provides the visual title of the widget
	 */
	public function getTitle(){
		return wfMsg('cd-nab-title');
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
	public function getLastContributor(){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$username = "Bsteudel";
		$u = new User();
		$u->setName($username);
		$img = Avatar::getPicture($u->getName(), true);
		if ($img == '') {
			$img = Avatar::getDefaultPicture();
		}
		return $img . '<span>Last</span>' . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . '<span>14 minutes ago</span>';
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(){
		global $wgUser;
		$sk = $wgUser->getSkin();

		$username = "JackHerrick";
		$u = new User();
		$u->setName($username);
		$img = Avatar::getPicture($u->getName(), true);
		if ($img == '') {
			$img = Avatar::getDefaultPicture();
		}
		return $img . '<span>Leader</span>' . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . '<span>14 minutes ago</span>';
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

	/**
	 * Called on the servers as part of a maintenance method.
	 */
	public function compileStatsData(&$dbr) {
		$data = array();
		$data['unpatrolled'] = RCWidget::getUnpatrolledEdits($dbr);
		return $data;
	}

}


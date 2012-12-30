<?

class RecentChangesAppWidget extends DashboardWidget {

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
		));
		$html = $tmpl->execute('recent-changes.tmpl.php');
		return $html;
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
		$data['unpatrolled'] = RCWidget::getUnpatrolledEdits($dbr);
		return $data;
	}

}


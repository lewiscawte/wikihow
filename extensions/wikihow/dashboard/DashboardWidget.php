<?

/**
 * DashboardWidget should be subclassed by all widgets in the widgets/ dir
 */
abstract class DashboardWidget {
	
	protected $widgetName;
	static $thresholds = array();
	static $completion = array();

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
	 * Must be implemented by subclass.  Needs to return HTML that is
	 * contained in the widget box provided.
	 */
	protected abstract function getHTML($initialData);

	/**
	 * Returns the HTML that contains a div that has widget-specific HTML and
	 * allows CSS to control how all containers are displayed.  This method 
	 * is calls getHTML() and is called by the dashboard display.
	 */
	public function getContainerHTML($initialData) {
		return '<div class="comdash-widget-box comdash-widget-' . $this->getName() . '">' . $this->getHTML($initialData) . '</div>';
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
	public abstract function compileStatsData(&$dbr);

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

}


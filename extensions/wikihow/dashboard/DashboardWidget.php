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
	 * Returns HTML internals of the widget box.
	 */
	public function getHTML($initialData) {
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'data' => $initialData,
			'completedToday' => $this->getCompletion(),
			'thresholds' => $this->getThresholds(),
			'header' => $this->getHeaderHTML(),
			'weather' => $this->getWeatherClass(),
			'countDescription' => $this->getCountDescription(),
			'moreLink' => $this->getMoreLink(),
			'widgetName' => $this->getName(),
			'title' => $this->getTitle(),
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
		return '<div class="comdash-widget-box comdash-widget-' . $this->getName() . '">' . $this->getHTML($initialData) . '</div>';
	}

	/*
	 * Must be implmented by subclass. Needs to return the visual title for
	 * this widget.
	 */
	protected abstract function getTitle();

	/*
	 * Must be implemented by subclass. Needs to return the html for the last
	 * contributor shown at the bottom of the widget
	 */
	protected abstract function getLastContributor(&$dbr);

	/*
	 * Must be implemented by subclass. Needs to return the html for the top
	 * contributor shown at the bottom of the widget
	 */
	protected abstract function getTopContributor(&$dbr);

	/**
	 *
	 * returns the html for the "more" link at the bottom of each widget
	 */
	public function getMoreLink(){
		return "<a href='#' class='comdash-more' id='comdash-more-" . $this->widgetName . "'></a>";
	}

	/*
	 * Must be implemented by subclass. Needs to return the class for the weather icon
	 * on the widget.
	 */
	protected abstract function getWeatherClass();

	/*
	 * Must be implemented by sublcass. Needs to return the start link that appears
	 * in the header of the widget.
	 */
	protected abstract function getStartLink();

	/**
	 * Returns the HTML for just the top part of the widget
	 */
	public function getHeaderHTML(){
		return '<div class="comdash-widget-header">' . $this->getTitle() . $this->getStartLink() . '</div>';
	}

	/**
	 * Returns a string to be displayed under the current count
	 * in the widget. Needs to be implemented by subclass.
	 */
	public abstract function getCountDescription();

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


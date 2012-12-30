<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");

class CommunityDashboard extends SpecialPage {

	private $dashboardData = null;
	private $refreshData = null;

	// refresh stats from CDN every 5 seconds
	const GLOBAL_DATA_REFRESH_TIME_SECS = 5;
	const USER_DATA_REFRESH_TIME_SECS = 60;

    public function __construct() {
        SpecialPage::SpecialPage('CommunityDashboard');
    }

	/**
	 * The callback made to process and display the output of the 
	 * Special:CommunityDashboard page.
	 */
    public function execute($par) {
		global $wgOut, $wgRequest;

		wfLoadExtensionMessages('CommunityDashboard');

		$this->dashboardData = new DashboardData();

		$action = $wgRequest->getVal('action', '');
		if ($action == 'refresh') {
			$expiresSecs = self::GLOBAL_DATA_REFRESH_TIME_SECS;

			// get all commonly updating stats
			$refreshData = $this->dashboardData->getStatsData();

			$this->restResponse($expiresSecs, json_encode($refreshData));
		} else if ($action == 'userrefresh') {
			$expiresSecs = self::USER_DATA_REFRESH_TIME_SECS;

			// get user-specific stats
			$userData = $this->dashboardData->loadUserData();
			$completion = @$userData['completion'];

			$this->restResponse($expiresSecs, json_encode($completion));
		} else {
			$wgOut->setHTMLTitle(wfMsg('cd-html-title'));

			$html = $this->displayContainer();
			$wgOut->addHTML($html);
		}
	}

	/**
	 * Returns a relative URL by querying all the widgets for what 
	 * JS or CSS files they use.
	 *
	 * @param $type must be the string 'js' or 'css'
	 * @return a string like this: /extensions/min/?f=/foo/file1,/bar/file2
	 */
	private function makeUrl($type, $localFiles = array()) {
		$widgets = $this->dashboardData->getWidgets();
		$files = $localFiles;
		foreach ($widgets as $widget) {
			$moreFiles = $type == 'js' ? $widget->getJSFiles() : $widget->getCSSFiles();
			foreach ($moreFiles as &$file) $file = 'widgets/' . $file;
			$files = array_merge($files, $moreFiles);
		}
		$files = array_unique($files);
		$url = '/extensions/min/?b=extensions/wikihow/dashboard&f=' . join(',', $files);
		return $url;
	}

	/**
	 * Display the HTML for this special page with all the widgets in it
	 */
	private function displayContainer() {
		global $wgWidgetList;

		$containerJS = array(
			'community-dashboard.js',
			'dashboard-widget.js',
		);
		$containerCSS = array(
			'community-dashboard.css',
		);

		$jsUrl = $this->makeUrl('js', $containerJS);
		$cssUrl = $this->makeUrl('css', $containerCSS);

		// get all commonly updating stats, to see the initial widget
		// displays with
		$this->refreshData = $this->dashboardData->getStatsData();

		// get all data such as wikihow-defined structure goals, dynamic 
		// global data, and user-specific data
		$staticData = $this->dashboardData->loadStaticGlobalOpts();
		$priorities = (array)json_decode($staticData['cdo_priorities_json']);
		$thresholds = (array)json_decode($staticData['cdo_thresholds_json']);
		DashboardWidget::setThresholds($thresholds);

		// display the user-defined ordering of widgets inside an outside
		// container
		$userData = $this->dashboardData->loadUserData();
		$prefs = !empty($userData['prefs']) ? $userData['prefs'] : array();
		$userOrdering = isset($prefs['ordering']) ? $prefs['ordering'] : $wgWidgetList;
		$completion = !empty($userData['completion']) ? $userData['completion'] : array();
		DashboardWidget::setCompletion($completion);

		// remove any community priority widgets from the user-defined ordering
		// so that they're not displayed twice
		foreach ($priorities as $name1) {
			foreach ($userOrdering as $i => $name2) {
				if ($name1 == $name2) {
					unset($userOrdering[$i]);
				}
			}
		}
		$func = array($this, 'displayWidgets');
		$out = call_user_func($func, array('test'));

		$langKeys = array('howto','cd-pause-updates','cd-resume-updates');
		$langScript = WikiHow_i18n::genJSMsgs($langKeys);

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'jsUrl' => $jsUrl,
			'cssUrl' => $cssUrl,
			'thresholds' => $staticData['cdo_thresholds_json'],
			'GLOBAL_DATA_REFRESH_TIME_SECS' => self::GLOBAL_DATA_REFRESH_TIME_SECS,
			'USER_DATA_REFRESH_TIME_SECS' => self::USER_DATA_REFRESH_TIME_SECS,
			'priorityWidgets' => $priorities,
			'userWidgets' => $userOrdering,
			'displayWidgetsFunc' => array($this, 'displayWidgets'),
		));
		$html = $tmpl->execute('dashboard-container.tmpl.php');

		return $langScript . $html;
	}

	/**
	 * Called by the dashboard-container.tmpl.php template to generate the
	 * widget boxes for a list of widgets.
	 *
	 * @param $widgetList an array like array('RecentChangesAppWidget', ...)
	 */
	public function displayWidgets($widgetList) {
		$widgets = $this->dashboardData->getWidgets();

		foreach ($widgetList as $name) {
			$widget = $widgets[$name];
			if ($widget) {
				$initialData = @$this->refreshData['widgets'][$name];
				$html .= $widget->getContainerHTML($initialData);
			}
		}

		return $html;
	}

	/**
	 * Form a REST response (JSON encoded) using the data in $data.  Does a
	 * JSONP response if requested.  Expires in $expiresSecs seconds.
	 */
	private function restResponse($expiresSecs, $data) {
		global $wgOut, $wgRequest;

		$wgOut->disable();
		$this->controlFrontEndCache($expiresSecs);

		if (!$data) {
			$data = array('error' => 'data not refreshing on server');
		}

		$funcName = $wgRequest->getVal('function', '');
		if ($funcName) {
			print "$funcName($data)";
		} else {
			print $data;
		}
	}

	/**
	 * Add HTTP headers so that the front end caches for the right number of
	 * seconds.
	 */
	private function controlFrontEndCache($maxAgeSecs) {
		global $wgOut, $wgRequest;
        $wgOut->setSquidMaxage($maxAgeSecs);
		$wgRequest->response()->header( 'Cache-Control: s-maxage=' . $maxAgeSecs . ', must-revalidate, max-age=' . $maxAgeSecs );
		$wgOut->setArticleBodyOnly(true);
		$wgOut->sendCacheControl();
	}

}


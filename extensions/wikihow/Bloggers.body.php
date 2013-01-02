<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");

class Bloggers extends UnlistedSpecialPage {

    public function __construct() {
		global $wgHooks;
        UnlistedSpecialPage::UnlistedSpecialPage('Bloggers');
		$wgHooks['showBreadCrumbs'][] = array('Bloggers::removeBreadCrumbsCallback');

    }

	/**
	 * The callback made to process and display the output of the 
	 * Special:Bloggers page.
	 */
    public function execute($par) {
		global $wgOut, $wgRequest;

		$wgOut->addHTML('<iframe src="https://spreadsheets.google.com/embeddedform?formkey=dHdUMlZ0a0p1SXM2NURDQTRvb0F3QVE6MQ" width="630" height="693" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>');
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

}


<?

if (!defined('MEDIAWIKI')) die();
    
/**#@+
 * The wikiHow community dashboard.  It's a list of widgets that update in
 * close to real time.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:CommunityDashboard-Extension Documentation
 *
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");

$wgExtensionCredits['special'][] = array(
	'name' => 'CommunityDashboard',
	'author' => 'Bebeth Steudel and Reuben Smith',
	'description' => 'Shows the status of a bunch of different aspects of the wikiHow site',
	'url' => 'http://www.wikihow.com/WikiHow:CommunityDashboard-Extension',
);

$wgSpecialPages['CommunityDashboard'] = 'CommunityDashboard';
$wgAutoloadClasses['CommunityDashboard'] = dirname( __FILE__ ) . '/CommunityDashboard.body.php';
$wgExtensionMessagesFiles['CommunityDashboard'] = dirname(__FILE__) . '/CommunityDashboard.i18n.php';

/**
 * $wgWidgetList is a list of that can be displayed on the CommunityDashboard
 * special page.  Each widget listed should have a class named 
 * ClassNameWidget.php in the widget/ subdirectory.  The class loaded in 
 * this file should extend the WHDashboardWidget class.
 *
 * IMPORTANT NOTE: every widget defined in this array must also be 
 * defined in $wgWidgetShortCodes below.
 */
$wgWidgetList = array(
	'WriteAppWidget',
	'AddImagesAppWidget',
	'RecentChangesAppWidget',
	'CategorizerAppWidget',
	'FormatAppWidget',
	'StubAppWidget',
	'QcAppWidget',
	'AddVideosAppWidget',
	'NabAppWidget',
	'TopicAppWidget',
	'NfdAppWidget',
	'TweetItForwardWidget',
	'SpellcheckerAppWidget'
);

/**
 * Define some short codes for apps, so that the long names don't have to be
 * transmitted constantly.
 */
$wgWidgetShortCodes = array(
	'RecentChangesAppWidget' => 'rc',
	'NabAppWidget' => 'nab',
	'AddImagesAppWidget' => 'img',
	'AddVideosAppWidget' => 'vid',
	'WriteAppWidget' => 'wri',
	'FormatAppWidget' => 'for',
	'CategorizerAppWidget' => 'cat',
	'StubAppWidget' => 'stu',
	'QcAppWidget' => 'qc',
	'TopicAppWidget' => 'tpc',
	'NfdAppWidget' => 'nfd',
	'TweetItForwardWidget' => 'tif',
	'SpellcheckerAppWidget' => 'spl',
);

/**
 * Community Dashboard debug flag -- always check-in as false and make a
 * local edit.
 */
define('COMDASH_DEBUG', false);

/**
 * Hooks
 */

$wgHooks['MarkPatrolled'][] = array("wfMarkCompleted", "RecentChangesAppWidget"); //recent changes
$wgHooks['NABArticleFinished'][] = array("wfMarkCompleted", "NabAppWidget"); //nab
$wgHooks['ArticleSaveComplete'][] = array("wfMarkCompletedWrite"); //write articles
$wgHooks['EditFinderArticleSaveComplete'][] = array("wfMarkCompletedEF"); //stub, format
$wgHooks['CategoryHelperSuccess'][] = array("wfMarkCompleted", "CategorizerAppWidget"); //categorizer
$wgHooks['IntroImageAdderUploadComplete'][] = array("wfMarkCompleted", "AddImagesAppWidget"); //add images
$wgHooks['VAdone'][] = array("wfMarkCompleted", "AddVideosAppWidget"); //add videos
$wgHooks['QCVoted'][] = array("wfMarkCompleted", "QcAppWidget"); //qc
$wgHooks['NFDVoted'][] = array("wfMarkCompleted", "NfdAppWidget"); //nfd
$wgHooks['Spellchecked'][] = array("wfMarkCompleted", "SpellcheckerAppWidget"); //spellchecker

function wfMarkCompleted($appName) {
	$dashboardData = new DashboardData();
	$dashboardData->setDailyCompletion($appName);
	
	return true;
}

function wfMarkCompletedEF($article, $text, $summary, $user, $type) {
	switch (strtolower($type)) {
		case 'format':
			wfMarkCompleted("FormatAppWidget");
			break;
		case 'stub':
			wfMarkCompleted("StubAppWidget");
			break;
		case 'topic':
			wfMarkCompleted("TopicAppWidget");
			break;
		default:
			break;
	}
	return true;
}

function wfMarkCompletedWrite($article, $user, $text, $summary, $p5, $p6, $p7) {
	try {
		$dbr = wfGetDB(DB_MASTER);
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN)  {
			return true;
		}

		$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));

		if($num_revisions == 1)
			wfMarkCompleted("WriteAppWidget");
	} catch (Exception $e) {
		return true;
	}
	return true;
}

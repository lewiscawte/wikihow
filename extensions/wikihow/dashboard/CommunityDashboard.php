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

require_once("$IP/includes/EasyTemplate.php");
require_once("$IP/extensions/wikihow/WikiHow_i18n.class.php");

$wgExtensionCredits['special'][] = array(
	'name' => 'CommunityDashboard',
	'author' => 'Reuben Smith',
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
 */
$wgWidgetList = array(
	'RecentChangesAppWidget',
	'NabAppWidget',
);


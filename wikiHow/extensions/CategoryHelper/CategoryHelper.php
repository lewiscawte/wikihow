<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * Provides support for the category drop down menu
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Categoryhelper-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgMaxCategories = 2;

/**#@+
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Categoryhelper',
	'author' => 'Travis Derouin',
	'description' => 'Provides support for the category drop down menu',
);

$wgExtensionMessagesFiles['Categoryhelper'] = dirname(__FILE__) . '/Categoryhelper.i18n.php';

$wgSpecialPages['Categoryhelper'] = 'Categoryhelper';
$wgAutoloadClasses['Categoryhelper'] = dirname( __FILE__ ) . '/Categoryhelper.body.php';


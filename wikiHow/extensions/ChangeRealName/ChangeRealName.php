<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Changerealname-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgAvailableRights[] = 'changerealname';
$wgGroupPermissions['sysop']['changerealname'] = true;


/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfChangerealnameForm");

$wgExtensionCredits['other'][] = array(
	'name' => 'Changerealname',
	'author' => 'Travis Derouin',
	'description' => 'Changes the real name of a user',
);

$wgExtensionMessagesFiles['Changerealname'] = dirname(__FILE__) . '/Changerealname.i18n.php';
$wgSpecialPages['Changerealname'] = 'Changerealname';
$wgAutoloadClasses['Changerealname'] = dirname( __FILE__ ) . '/Changerealname.body.php';


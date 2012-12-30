<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Facebook-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfFacebookForm");

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Facebook',
	'author' => 'Travis Derouin',
	'description' => 'Back end support for Facebook app',
);

$wgSpecialPages['Facebook'] = 'Facebook';
$wgAutoloadClasses['Facebook'] = dirname( __FILE__ ) . '/Facebook.body.php';


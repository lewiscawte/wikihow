<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that lets you import thumbnails from youtube videos into the article
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:YTThumb-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'YTThumb',
	'author' => 'Travis Derouin',
	'description' => 'An extension that lets you import thumbnails from youtube videos into the article',
	'url' => 'http://www.wikihow.com/WikiHow:YTThumb-Extension',
);

#$wgExtensionMessagesFiles['YTThumb'] = dirname(__FILE__) . '/YTThumb.i18n.php';

$wgSpecialPages['YTThumb'] = 'YTThumb';
$wgAutoloadClasses['YTThumb'] = dirname( __FILE__ ) . '/YTThumb.body.php';

$wgHooks["MakeGlobalVariablesScript"][] = array("wfYTThumbSetupHooks");

function wfYTThumbSetupHooks(&$vars) {
	global $wgTitle, $wgUser;
	if ($wgTitle && $wgTitle->getNamespace() == NS_MAIN 
		&& in_array( 'newarticlepatrol', $wgUser->getRights() ) 
		&& YTThumb::hasThumbnails($wgTitle)) {
		$vars['wgYTThumbs'] = true;
	} else {
		$vars['wgYTThumbs'] = false;
	}
	return true;
}

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

$wgExtensionMessagesFiles['YTThumb'] = dirname(__FILE__) . '/YTThumb.i18n.php';

$wgSpecialPages['YTThumb'] = 'YTThumb';
$wgAutoloadClasses['YTThumb'] = dirname( __FILE__ ) . '/YTThumb.body.php';

$wgSpecialPages['YTThumbList'] = 'YTThumbList';
$wgAutoloadClasses['YTThumbList'] = dirname( __FILE__ ) . '/YTThumb.body.php';

// set up the hooks
$wgHooks["MakeGlobalVariablesScript"][] = array("wfYTThumbSetupHooks");
$wgHooks["ArticleSaveComplete"][] = array("wfYTSetNotificationCookie");
$wgHooks["OutputPageBeforeHTML"][] = array("wfYTThumbAddJS");
$wgHooks["OutputPageBeforeHTML"][] = array("wfYTPromptForNotifications");

// new user group/right for uploading thumbnails
$wgAvailableRights[] = 'imagecurator';
$wgGroupPermissions['imagecurator']['imagecurator'] = true;

// sets the javascript global variable wgYTThumbs which indicates whether or not 
// there are thumbnails available for uploading to the article
function wfYTThumbSetupHooks(&$vars) {
	global $wgTitle, $wgUser;
	if ($wgTitle && $wgTitle->getNamespace() == NS_MAIN 
		&& in_array( 'imagecurator', $wgUser->getRights() ) 
		&& YTThumb::hasThumbnails($wgTitle)) {
		$vars['wgYTThumbs'] = true;
	} else {
		$vars['wgYTThumbs'] = false;
	}
	return true;
}

// adds the necessary javascript file to the page if you can upload youtube thumbnails
function wfYTThumbAddJS(&$out, &$text) {
	global $wgTitle, $wgUser;
	if ($wgTitle && $wgTitle->getNamespace() == NS_MAIN 
		&& in_array( 'imagecurator', $wgUser->getRights() ) 
		&& YTThumb::hasThumbnails($wgTitle)) {
		$out->addScript("<script type='text/javascript' src='/extensions/wikihow/ytthumbs.js'></script>");
		$out->addScript('<script src="' . wfGetPad('/extensions/wikihow/common/ui/js/jquery-ui-1.8.custom.min.js') . '" type="text/javascript"></script> ');
	}
	return true;
	
}

// sets a cookie if a user has embedded a youtube video
// which we will use to notify them when thumbnails are available 
function wfYTSetNotificationCookie(&$article, &$user, $text) {
	global $wgCookieExpiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix, $wgUser;
	$t = $article->getTitle(); 
	if ($t->getNamespace() == NS_VIDEO
		&& in_array( 'imagecurator', $wgUser->getRights())) { 
		$parts = split("\|", $text); 
		if ($parts[1] == "youtube") {
			$exp = time() + $wgCookieExpiration;
			setcookie( $wgCookiePrefix.'CheckVideo', $article->getID(), $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		}
	}
	return true; 
}

function wfYTPromptForNotifications(&$out, &$text) {
	global $wgCookieExpiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix, $wgOut, $wgUser;
	if (isset( $_COOKIE["{$wgCookiePrefix}CheckVideo"])
		&& in_array( 'imagecurator', $wgUser->getRights())) { 
		$id = $_COOKIE["{$wgCookiePrefix}CheckVideo"];
		$out->addScript("<script type='text/javascript'>var wgVideoId = {$id};</script>");
		$out->addScript("<script type='text/javascript' src='/extensions/wikihow/ytnotify.js?" . WH_SITE_REV  . "'></script>");
		setcookie( $wgCookiePrefix.'CheckVideo', 0, time() - 3600, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}
	return true; 

}

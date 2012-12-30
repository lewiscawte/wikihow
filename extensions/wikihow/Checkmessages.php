<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Checkmessages-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfCheckmessages';

$wgExtensionCredits['other'][] = array(
	'name' => 'Checkmessages',
	'author' => 'Travis Derouin',
	//'description' => 'Provides a basic article ratings system',
	//'url' => 'http://www.wikihow.com/WikiHow:Checkmessages-Extension',
);

function wfCheckmessages() {
	SpecialPage::AddPage(new UnlistedSpecialPage('Checkmessages'));
}

function wfSpecialCheckmessages( $par )
{
	global $wgOut, $wgUser, $wgRequest;
        global $wgCookieExpiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix;
      
	$exp = time() + $wgCookieExpiration;
	$sk = $wgUser->getSkin();

	$announcement = "";
   	$usertalktitle = $wgUser->getTalkPage();
    $userkudostitle = $wgUser->getKudosPage();

	// set the logout variable if they have an expired session
	/*
	echo $wgUser->getId() . "<br/>\n";
	if ($_COOKIE[ $wgCookiePrefix . 'UserName'] != '') {
		echo "Nothing...";	
	} else {
		echo $_COOKIE[ $wgCookiePrefix . 'UserName'];
	}
	if ($wgUser->getId() == 0 && $_COOKIE[ $wgCookiePrefix . 'UserName'] != '') {
		setcookie( $wgCookiePrefix.'LoggedOut', wfTimestampNow(), time() + 86400, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}
	*/
	$wgOut->setArticleBodyOnly(true);

	if ($wgUser->getID() == 0) {
		if (isset($_COOKIE[$wgCookiePrefix.'UserName']) && !isset($_COOKIE[$wgCookiePrefix.'LoggedOut'])) {
			setcookie( $wgCookiePrefix.'LoggedOut', wfTimestampNow(), time() + 86400, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		}	
		return; 
	}
    
	if( $wgUser->getNewtalk() ) {
                $announcement = '<p id="newmsgs">' . wfMsg( 'newmessages', $wgUser->getName(), $sk->makeKnownLinkObj( $usertalktitle, wfMsg('newmessageslink') )) . '</p>';
    } 
        
    if( $wgUser->getNewkudos() && !$wgUser->getOption('ignorefanmail')) {
    	if ($announcement != '') $announcement .= "<br/>";
        $announcement .= wfMsg( 'newfanmail', $wgUser->getName(), $userkudostitle->getPrefixedURL());
	}
	if ($announcement == "")
		$exp = time() - 3600; // clear the cookie
	 setcookie( $wgCookiePrefix.'Announcement', $announcement, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	
	$source = $wgRequest->getVal('source', null);
	if ($source != null) {
		if ($source == $usertalktitle->getFullURL()) 			
			$wgUser->setNewTalk(false);
		else if ($source == $userkudostitle->getFullURL()) 			
			$wgUser->setNewkudos(false);
	}

}
?>

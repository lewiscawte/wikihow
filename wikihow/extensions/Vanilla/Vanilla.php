<?php
/**
 * Vanilla extension -- integration with Vanilla forums (http://vanillaforums.org/)
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is not a valid entry point.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'Vanilla',
	'version' => '1.0',
	'author' => array( 'Travis Derouin', 'Jack Phoenix' ),
	'description' => 'Integration with [http://vanillaforums.org/ Vanilla] forums',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Vanilla'
);

// Settings for the extension so that it knows what the Vanilla DB is called
// and so on.
/*
$wgVanillaDB = array(
	'host' => WH_VANILLA_HOST,
	'dbname' => WH_VANILLA_DBNAME, // name of the Vanilla DB
	'user' => WH_VANILLA_USER,
	'password' => WH_VANILLA_PASSWORD
);
*/
$wgVanillaDB = array();

// Set up the new special page and i18n
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['Vanilla'] = $dir . 'Vanilla.i18n.php';
$wgAutoloadClasses['Vanilla'] = $dir . 'Vanilla.body.php';
$wgSpecialPages['Vanilla'] = 'Vanilla';

// Hooked functions
$wgHooks['UserLogout'][] = 'Vanilla::destroyCookies';
$wgHooks['UserLoginComplete'][] = 'Vanilla::processVanillaRedirect';
$wgHooks['UserLoginComplete'][] = 'Vanilla::destroyCookies';
$wgHooks['BlockIpComplete'][] = 'Vanilla::blockVanillaUser';
$wgHooks['AvatarUpdated'][] = 'Vanilla::setAvatar';

// @todo FIXME/CHECKME: still needed?
$wgHooks['ArticleSaveComplete'][] = 'wfCheckIp';

$wgVanillaEmergencyContact = 'alerts@localhost';

/**
 * Make sure that no real user is using the IP 192.168.100 but if someone is,
 * send an alert e-mail to the server administrator.
 *
 * @param $article Object: Article object
 * @param $user Object: User object
 * @param $text
 * @return Boolean: true
 */
function wfCheckIp( $article, $user, $text ) {
	global $wgUser, $wgVanillaEmergencyContact;
	$ip = wfGetIP();
	if ( strpos( $ip, '192.168.100' ) !== false ) {
		$alerts = new MailAddress( $wgVanillaEmergencyContact );
		$subject = 'Bad IP connected to ' . wfHostname() . ' - ' . date( 'r' );
		$body = "UHOH: $ip User {$wgUser->getName()} "
				. "\n-------------------------------------\n"
				. print_r( getallheaders(), true )
				. "\n-------------------------------------\n"
				. print_r( $_POST, true )
				. "\n-------------------------------------\n"
				. print_r( $_SERVER, true )
				. "\n-------------------------------------\n"
				. print_r( $wgUser, true )
				. "\n-------------------------------------\n"
				. wfBacktrace()
				. "\n-------------------------------------\n"
				. print_r( $article )
				. "\n";
		UserMailer::send( $alerts, $alerts, $subject, $body, $alerts );
		error_log( $body );
		wfDebugLog( 'Vanilla', $body );
	}
	return true;
}
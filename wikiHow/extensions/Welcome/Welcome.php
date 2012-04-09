<?php
/**
 * Welcome extension -- sends a welcome e-mail message to new users upon their
 * initial registration.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Welcome',
	'version' => '1.0',
	'author' => 'Vu Nguyen',
	'description' => 'Sends a welcome e-mail message to new users upon their initial registration',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['Welcome'] = $dir . 'Welcome.i18n.php';
$wgAutoloadClasses['Welcome'] = $dir . 'Welcome.body.php';
$wgSpecialPages['Welcome'] = 'Welcome';

// Hook it up!
$wgHooks['ConfirmNewAccount'][] = 'Welcome::sendWelcomeUser';
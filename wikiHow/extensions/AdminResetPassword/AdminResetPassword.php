<?php
/**
 * AdminResetPassword extension -- allows privileged users to reset a user's
 * password
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminResetPassword',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => '[[Special:AdminResetPassword|Tool]] for support personnel to reset a user\'s password without an e-mail address attached to the account',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AdminResetPassword'] = $dir . 'AdminResetPassword.i18n.php';
$wgAutoloadClasses['AdminResetPassword'] = $dir . 'AdminResetPassword.body.php';
$wgSpecialPages['AdminResetPassword'] = 'AdminResetPassword';

// Register the JS with ResourceLoader
$wgResourceModules['ext.adminResetPassword'] = array(
	'scripts' => 'AdminResetPassword.js',
	'messages' => array( 'adminresetpassword-loading' ),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AdminResetPassword'
);

// New user right, required to use the special page
$wgAvailableRights[] = 'adminresetpassword';
$wgGroupPermissions['bureaucrat']['adminresetpassword'] = true;
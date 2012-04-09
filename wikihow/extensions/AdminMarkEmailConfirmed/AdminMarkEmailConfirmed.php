<?php
/**
 * AdminMarkEmailConfirmed extension -- allows privileged users to confirm a
 * user's e-mail address attached to the user account
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
	'name' => 'AdminMarkEmailConfirmed',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => '[[Special:AdminMarkEmailConfirmed|Tool]] for support personnel to confirm a user\'s e-mail address attached to the account',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.adminMarkEmailConfirmed'] = array(
	'scripts' => 'AdminMarkEmailConfirmed.js',
	'messages' => array( 'adminmarkemailconfirmed-loading' ),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AdminMarkEmailConfirmed'
);

// New user right, required to access the special page
$wgAvailableRights[] = 'adminmarkemailconfirmed';
$wgGroupPermissions['bureaucrat']['adminmarkemailconfirmed'] = true;

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AdminMarkEmailConfirmed'] = $dir . 'AdminMarkEmailConfirmed.i18n.php';
$wgAutoloadClasses['AdminMarkEmailConfirmed'] = $dir . 'AdminMarkEmailConfirmed.body.php';
$wgSpecialPages['AdminMarkEmailConfirmed'] = 'AdminMarkEmailConfirmed';
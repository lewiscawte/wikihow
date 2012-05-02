<?php
/**
 * AdminRemoveAvatar extension -- allows privileged users to remove a user's
 * avatar file
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
	'name' => 'AdminRemoveAvatar',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => '[[Special:AdminRemoveAvatar|Tool]] for support personnel to remove a user\'s avatar file',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AdminRemoveAvatar'] = $dir . 'AdminRemoveAvatar.i18n.php';
$wgAutoloadClasses['AdminRemoveAvatar'] = $dir . 'AdminRemoveAvatar.body.php';
$wgSpecialPages['AdminRemoveAvatar'] = 'AdminRemoveAvatar';

// Register the JS with ResourceLoader
$wgResourceModules['ext.adminRemoveAvatar'] = array(
	'scripts' => 'AdminRemoveAvatar.js',
	'messages' => array( 'adminremoveavatar-loading' ),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AdminRemoveAvatar'
);

// New user right, required to use the special page
$wgAvailableRights[] = 'adminremoveavatar';
$wgGroupPermissions['sysop']['adminremoveavatar'] = true;

// Set up logging
$wgLogTypes[]             = 'avatarrm';
$wgLogNames['avatarrm']   = 'avatarrm';
$wgLogHeaders['avatarrm'] = 'avatarrmtext';
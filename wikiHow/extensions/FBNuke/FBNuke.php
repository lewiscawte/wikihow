<?php
/**
 * FBNuke extension
 * Allows privileged users to remove connections between a wiki account and a
 * Facebook account
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Jordan Small
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'FBNuke',
	'version' => '1.0',
	'author' => 'Jordan Small',
	'description' => 'Allows privileged users to [[Special:FBNuke|remove]] connections between a wiki account and a Facebook account',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['FBNuke'] = $dir . 'FBNuke.i18n.php';
$wgAutoloadClasses['FBNuke'] = $dir . 'FBNuke.body.php';
$wgSpecialPages['FBNuke'] = 'FBNuke';

// New user right, required to access Special:FBNuke
$wgAvailableRights[] = 'fbnuke';
$wgGroupPermissions['bureaucrat']['fbnuke'] = true;
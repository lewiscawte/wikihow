<?php
/**
 * Facebook App Contact Creator -- stores Facebook ID and e-mail of a user that
 * authorizes the site to send them e-mail.
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
	'name' => 'Facebook App Contact Creator',
	'version' => '1.0',
	'author' => 'Jordan Small',
	'description' => 'Stores the Facebook ID and e-mail of a user that authorizes the site to send them e-mail',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['FBAppContact'] = $dir . 'FBAppContact.i18n.php';
$wgAutoloadClasses['FBAppContact'] = $dir . 'FBAppContact.body.php';
$wgSpecialPages['FBAppContact'] = 'FBAppContact';
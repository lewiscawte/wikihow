<?php
/**
 * An extension that users to check if their "my links" page is within size
 * limits
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CheckMyLinks',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => '[[Special:CheckMyLinks|Allows]] users to check if their "my links" page is within size limits',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['CheckMyLinks'] = $dir . 'CheckMyLinks.i18n.php';
$wgAutoloadClasses['CheckMyLinks'] = $dir . 'CheckMyLinks.body.php';
$wgSpecialPages['CheckMyLinks'] = 'CheckMyLinks';
<?php
/**
 * Determine whether an edit is a significant edit.
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
	'name' => 'Points',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Determine whether an edit is a significant edit',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['Points'] = $dir . 'Points.i18n.php';
$wgAutoloadClasses['Points'] = $dir . 'Points.body.php';
$wgSpecialPages['Points'] = 'Points';
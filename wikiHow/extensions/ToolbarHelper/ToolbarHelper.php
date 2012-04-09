<?php
/**
 * Server side helper for the Firefox toolbar
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
	'name' => 'ToolbarHelper',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Server-side helper for the toolbar, could be replaced by RCBuddy at some point',
);

// Set up the new special page
$wgAutoloadClasses['ToolbarHelper'] = dirname( __FILE__ ) . '/ToolbarHelper.body.php';
$wgSpecialPages['ToolbarHelper'] = 'ToolbarHelper';
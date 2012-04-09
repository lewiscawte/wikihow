<?php
/**
 * Adds a new special page showing a list of the topmost (root) categories.
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
	'name' => 'CategoryListing',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides [[Special:CategoryListing|a list]] of the top most categories',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['CategoryListing'] = $dir . 'CategoryListing.i18n.php';
$wgAutoloadClasses['CategoryListing'] = $dir . 'CategoryListing.body.php';
$wgSpecialPages['CategoryListing'] = 'CategoryListing';
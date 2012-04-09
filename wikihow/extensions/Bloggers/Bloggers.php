<?php
/**
 * Display a Google form/spreadsheet which people can use to sign up.
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

// Extension credits that will show up in Special:Version
$wgExtensionCredits['special'][] = array(
	'name' => 'Bloggers',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => 'Display a Google form for bloggers which can be used to sign up'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['Bloggers'] = $dir . 'Bloggers.i18n.php';
$wgAutoloadClasses['Bloggers'] = $dir . 'Bloggers.body.php';
$wgSpecialPages['Bloggers'] = 'Bloggers';
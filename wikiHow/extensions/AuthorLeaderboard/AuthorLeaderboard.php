<?php
/**
 * An extension that displays number of new articles and number of rising stars
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AuthorLeaderboard',
	'version' => '1.0',
	'author' => 'Vu Nguyen',
	'description' => '[[Special:AuthorLeaderboard|Shows]] author count',
);

// Register the CSS with ResourceLoader
$wgResourceModules['ext.authorLeaderboard'] = array(
	'styles' => 'AuthorLeaderboard.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AuthorLeaderboard',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AuthorLeaderboard'] = $dir . 'AuthorLeaderboard.i18n.php';
$wgAutoloadClasses['AuthorLeaderboard'] = $dir . 'AuthorLeaderboard.body.php';
$wgSpecialPages['AuthorLeaderboard'] = 'AuthorLeaderboard';
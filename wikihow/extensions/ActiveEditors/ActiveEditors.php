<?php
/**
 * An extension that shows the most active users in the main namespace.
 *
 * @file
 * @ingroup Extensions
 * @version 1.2
 * @author Travis Derouin <travis@wikihow.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link http://www.mediawiki.org/wiki/Extension:ActiveEditors Documentation
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point.\n" );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ActiveEditors',
	'version' => '1.2',
	'author' => array( 'Travis Derouin', 'Jack Phoenix' ),
	'description' => 'Shows the most active main namespace editors',
	'url' => 'http://www.mediawiki.org/wiki/Extension:ActiveEditors',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.activeEditors'] = array(
	'styles' => 'ActiveEditors.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'ActiveEditors',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['ActiveEditors'] = $dir . 'ActiveEditors.i18n.php';
$wgAutoloadClasses['ActiveEditors'] = $dir . 'ActiveEditors.body.php';
$wgSpecialPages['ActiveEditors'] = 'ActiveEditors';
// Special page group for MW 1.13+
$wgSpecialPageGroups['ActiveEditors'] = 'users';
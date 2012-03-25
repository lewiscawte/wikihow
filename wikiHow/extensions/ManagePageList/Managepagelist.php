<?php
/**
 * ManagePageList -- an extension to manage lists of pages, such as the rising
 * stars feed
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
	'name' => 'ManagePageList',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => '[[Special:ManagePageList|Manages]] a list of pages, such as the rising stars feed',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.managePageList'] = array(
	'styles' => 'pagelist.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'ManagePageList',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['ManagePageList'] = $dir . 'ManagePageList.i18n.php';
$wgAutoloadClasses['ManagePageList'] = $dir . 'ManagePageList.body.php';
$wgAutoloadClasses['ManagePageListHooks'] = $dir . 'ManagePageListHooks.php';
$wgSpecialPages['ManagePageList'] = 'ManagePageList';

// New user right, required to access the special page
$wgAvailableRights[] = 'managepagelist';
$wgGroupPermissions['sysop']['managepagelist'] = true;

// Hooked functions
$wgHooks['MarkTitleAsRisingStar'][] = 'ManagePageListHooks::updatePageListRisingStar';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'ManagePageListHooks::createPagelistTable'; // update.php handler
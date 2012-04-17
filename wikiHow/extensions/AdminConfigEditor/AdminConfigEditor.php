<?php
/**
 * AdminConfigEditor extension -- a special page for editing and storing
 * certain types of configuration variables
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

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminConfigEditor',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => '[[Special:AdminConfigEditor|Tool]] for support personnel to edit and store config blobs',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.adminConfigEditor'] = array(
	'scripts' => 'AdminConfigEditor.js',
	'messages' => array( 'adminconfigeditor-error', 'adminconfigeditor-loading', 'adminconfigeditor-saving' ),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AdminConfigEditor'
);

// New user right, required to access the special page
$wgAvailableRights[] = 'adminconfigeditor';
$wgGroupPermissions['sysop']['adminconfigeditor'] = true;

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AdminConfigEditor'] = $dir . 'AdminConfigEditor.i18n.php';
$wgAutoloadClasses['ConfigStorage'] = $dir . 'ConfigStorage.class.php';
$wgAutoloadClasses['AdminConfigEditor'] = $dir . 'AdminConfigEditor.body.php';
$wgSpecialPages['AdminConfigEditor'] = 'AdminConfigEditor';
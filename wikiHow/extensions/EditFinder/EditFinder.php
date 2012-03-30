<?php
/**
 * EditFinder extension -- tool for experienced users to edit pages that need
 * it.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Scott Cushman
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EditFinder',
	'version' => '1.0',
	'author' => 'Scott Cushman',
	'description' => 'Tool for experienced users to edit articles that need it',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.editFinder'] = array(
	'scripts' => 'editfinder.js', // TODO CHECKME: wH custom clientscript.js? It was originally included, but is it needed?
	'styles' => array( 'editfinder.css' ),//, 'suggestedtopics.css'
	'messages' => array(
		// UI template
		'editfinder-stub', 'editfinder-copyedit', 'editfinder-format',
		'editfinder-app-name',
		// JS file
		'editfinder-no-interests-selected', 'editfinder-dialog-title',
		'editfinder-edit-summary-js', 'editfinder-no-pages-found',
		'editfinder-categories', 'editfinder-interests'
	),
	'dependencies' => array( 'jquery.cookie', 'mediawiki.legacy.preview' ),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'EditFinder'
);

// Set up the new special pages
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['EditFinder'] = $dir . 'EditFinder.i18n.php';
$wgAutoloadClasses['EditFinder'] = $dir . 'EditFinder.body.php';
$wgSpecialPages['EditFinder'] = 'EditFinder';

// Set up logging for three different EditFinder actions
$wgLogTypes[] = 'ef_format';
$wgLogNames['ef_format'] = 'editfinder_format';
$wgLogHeaders['ef_format'] = 'editfindertext_format';

$wgLogTypes[] = 'ef_stub';
$wgLogNames['ef_stub'] = 'editfinder_stub';
$wgLogHeaders['ef_stub'] = 'editfindertext_stub';

$wgLogTypes[] = 'ef_topic';
$wgLogNames['ef_topic'] = 'editfinder_topic';
$wgLogHeaders['ef_topic'] = 'editfindertext_topic';

// update.php handler
$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfEditFinderCreateTables';

/**
 * Handler for the MediaWiki update script, update.php; this code is
 * responsible for creating the editfinder and editfinder_skip tables in the
 * database when the user runs maintenance/update.php.
 *
 * @param $updater DatabaseUpdater
 * @return Boolean: true
 */
function wfEditFinderCreateTables( $updater ) {
	$dir = dirname( __FILE__ );

	$updater->addExtensionUpdate( array(
		'addTable', 'editfinder', "$dir/editfinder_tables.sql", true
	) );

	$updater->addExtensionUpdate( array(
		'addTable', 'editfinder_skip', "$dir/editfinder_tables.sql", true
	) );

	return true;
}
<?php
/**
 * An extension that allows to monitor pages' Google ranking.
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
	'name' => 'MonitorPages',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => "[[Special:MonitorPages|Allows to monitor pages' Google ranking]]",
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.monitorPages'] = array(
	'styles' => 'MonitorPages.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'MonitorPages'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['MonitorPages'] = $dir . 'MonitorPages.i18n.php';
$wgSpecialPages['MonitorPages'] = 'MonitorPages';
$wgAutoloadClasses['MonitorPages'] = $dir . 'MonitorPages.body.php';

// New user right, required to add pages to the list
$wgAvailableRights[] = 'monitorpages-add';
$wgGroupPermissions['sysop']['monitorpages-add'] = true;

// update.php handler
$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfMonitorPagesCreateTables';

/**
 * Handler for the MediaWiki update script, update.php; this code is
 * responsible for creating the google_monitor and google_monitor_results
 * tables in the database when the user runs maintenance/update.php.
 *
 * @param $updater DatabaseUpdater
 * @return Boolean: true
 */
function wfMonitorPagesCreateTables( $updater ) {
	$dir = dirname( __FILE__ );

	$updater->addExtensionUpdate( array(
		'addTable', 'google_monitor', "$dir/google_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'google_monitor_results', "$dir/google_tables.sql", true
	) );

	return true;
}
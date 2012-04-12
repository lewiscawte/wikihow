<?php
/**
 * AdminCommunityDashboard extension -- administrator panel to the Community
 * Dashboard extension.
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
	'name' => 'AdminCommunityDashboard',
	'version' => '1.0',
	'author' => 'Reuben Smith',
	'description' => '[[Special:AdminCommunityDashboard|Tool]] for support personnel to change admin settings for the community dashboard',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.adminCommunityDashboard'] = array(
	'scripts' => array( 'AdminCommunityDashboard.js', 'jquery.json-2.2.js' ),
	'messages' => array(
		'admincommunitydashboard-js-choose-3',
		'admincommunitydashboard-js-network-error',
		'admincommunitydashboard-js-restarting-script',
		'admincommunitydashboard-js-saving-error',
		'admincommunitydashboard-js-error-occurred'
	),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AdminCommunityDashboard'
);

// Set up the new special page (i18n file is shared between this and the main ext.)
$wgAutoloadClasses['AdminCommunityDashboard'] = dirname( __FILE__ ) . '/AdminCommunityDashboard.body.php';
$wgSpecialPages['AdminCommunityDashboard'] = 'AdminCommunityDashboard';
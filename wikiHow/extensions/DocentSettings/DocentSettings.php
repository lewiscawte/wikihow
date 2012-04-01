<?php
/**
 * An extension that provides a way of administering docent settings.
 *
 * @file
 * @ingroup Extensions
 * @version 1.1
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'DocentSettings',
	'version' => '1.1',
	'author' => 'Travis Derouin',
	'description' => 'Provides [[Special:DocentSettings|a way]] of administering docent settings',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.docentSettings'] = array(
	'styles' => 'DocentSettings.css',
	'scripts' => 'DocentSettings.js',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'DocentSettings',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['DocentSettings'] = $dir . 'DocentSettings.i18n.php';
$wgAutoloadClasses['DocentSettings'] = $dir . 'DocentSettings.body.php';
$wgSpecialPages['DocentSettings'] = 'DocentSettings';

// Set up the new log for docent changes
$wgLogTypes[] = 'doc';
$wgLogNames['doc'] = 'docentadministration';
$wgLogHeaders['doc'] = 'docentadministrationtext';
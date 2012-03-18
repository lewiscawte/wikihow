<?php
/**
 * Generates a page of links to the top-level categories and their subcatgories
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
	'name' => 'Sitemap',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Generates [[Special:Sitemap|a page of links]] to the top-level categories and their subcatgories',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.sitemap'] = array(
	'styles' => 'sitemap.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'Sitemap',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['Sitemap'] = $dir . 'Sitemap.i18n.php';
$wgAutoloadClasses['Sitemap'] = $dir . 'Sitemap.body.php';
$wgSpecialPages['Sitemap'] = 'Sitemap';
<?php
/**
 * An extension that provides redirecting static URLs to dynamic user pages.
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
	'name' => 'MyPages',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides redirecting static URLs to dynamic user pages',
);

// Set up the new special page
$wgAutoloadClasses['MyPages'] = dirname( __FILE__ ) . '/Mypages.body.php';
$wgSpecialPages['MyPages'] = 'MyPages';
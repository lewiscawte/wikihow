<?php
/**
 * CheckGoogle extension -- allows checking the Google rank of pages
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author wikiHow
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CheckGoogle',
	'version' => '1.0',
	'author' => array( 'wikiHow', 'Jack Phoenix' ),
	'description' => 'Allows [[Special:CheckGoogle|checking]] the Google rank of pages',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['CheckGoogle'] = $dir . 'CheckGoogle.i18n.php';
$wgAutoloadClasses['CheckGoogle'] = $dir . 'CheckG.body.php';
$wgSpecialPages['CheckGoogle'] = 'CheckGoogle';

// New permission which is required to access Special:CheckGoogle
$wgAvailableRights[] = 'checkgoogle';
$wgGroupPermissions['bureaucrat']['checkgoogle'] = true;

// update.php handler which creates the DB tables when update.php is ran
$wgHooks['LoadExtensionSchemaUpdates'][] = 'CheckGoogle::createTablesInDB';
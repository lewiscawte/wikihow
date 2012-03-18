<?php
/**
 * Maintain a table of links (categorylinkstop) from top level categories to
 * articles through a hook.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link http://www.mediawiki.org/wiki/Extension:TopCategoryHooks Documentation
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'TopCategoryHooks',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Maintain a table of links from top level categories to articles through a hook',
	'url' => 'http://www.mediawiki.org/wiki/Extension:TopCategoryHooks',
);

$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['TopCategoryHooks'] = $dir . 'TopCategoryHooks.i18n.php';
$wgAutoloadClasses['TopCategoryHooks'] = $dir . 'TopCategoryHooks.class.php';

$wgHooks['LinksUpdate'][] = 'TopCategoryHooks::updateTopLevelCatTable';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TopCategoryHooks::createTableInDB'; // update.php handler
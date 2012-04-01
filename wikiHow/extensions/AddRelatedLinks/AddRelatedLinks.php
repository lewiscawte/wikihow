<?php
/**
 * Takes a set of URLs, finds related pages, and adds inbound links to the submitted pages
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
	'name' => 'AddRelatedLinks',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Takes a set of URLs, finds related pages, and adds inbound links to the submitted pages',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AddRelatedLinks'] = $dir . 'AddRelatedLinks.i18n.php';
$wgAutoloadClasses['AddRelatedLinks'] = $dir . 'AddRelatedLinks.body.php';
$wgSpecialPages['AddRelatedLinks'] = 'AddRelatedLinks';

// New user right, required to access and use the special page
$wgAvailableRights[] = 'addrelatedlinks';
$wgGroupPermissions['bureaucrat']['addrelatedlinks'] = true;
<?php
/**
 * An extension that allows users to leave fan mail to all the authors of an
 * article.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is not a valid entry point to MediaWiki.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ThankAuthors',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => "A way for users to leave fan mail on authors' user_kudos page",
);

// Set up the new special page and the new namespaces
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['ThankAuthors'] = $dir . 'ThankAuthors.i18n.php';
$wgExtensionMessagesFiles['ThankAuthorsNamespaces'] = $dir . 'ThankAuthors.namespaces.php';
$wgAutoloadClasses['ThankAuthors'] = $dir . 'ThankAuthors.body.php';
$wgSpecialPages['ThankAuthors'] = 'ThankAuthors';

// Hooked function
$wgHooks['CanonicalNamespaces'][] = 'wfThankAuthorsRegisterCanonicalNamespaces';

/**
 * Register the canonical names for our custom namespaces and their talkspaces.
 *
 * @param $list Array: array of namespace numbers with corresponding
 *                     canonical names
 * @return Boolean: true
 */
function wfThankAuthorsRegisterCanonicalNamespaces( &$list ) {
	$list[NS_USER_KUDOS] = 'User_kudos';
	$list[NS_USER_KUDOS_TALK] = 'User_kudos_talk';
	return true;
}
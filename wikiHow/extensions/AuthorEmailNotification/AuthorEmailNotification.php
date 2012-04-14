<?php
/**
 * An extension that notifies users on certain events by e-mail
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AuthorEmailNotification',
	'version' => '1.0',
	'author' => 'Vu Nguyen',
	'description' => 'Notifies users by e-mail on certain events',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.authorEmailNotification'] = array(
	'scripts' => 'authoremails.js',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'AuthorEmailNotification'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['AuthorEmailNotification'] = $dir . 'AuthorEmailNotification.i18n.php';
$wgAutoloadClasses['AuthorEmailNotification'] = $dir . 'AuthorEmailNotification.body.php';
$wgAutoloadClasses['AuthorEmailNotificationHooks'] = $dir . 'AuthorEmailNotificationHooks.body.php';
$wgSpecialPages['AuthorEmailNotification'] = 'AuthorEmailNotification';

$wgHooks['AddNewAccount'][] = 'AuthorEmailNotificationHooks::attributeAnon';
$wgHooks['AddNewAccount'][] = 'AuthorEmailNotificationHooks::setUserTalkOption';
#$wgHooks['ArticlePageDataBefore'][] = 'AuthorEmailNotificationHooks::addFirstEdit';
$wgHooks['MarkPatrolledDB'][] = 'AuthorEmailNotificationHooks::sendModNotification';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'AuthorEmailNotificationHooks::createTable'; // update.php handler
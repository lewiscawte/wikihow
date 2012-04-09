<?php
/**
 * RequestTopic extension -- Provides a basic way of suggesting new topics to
 * be added to the article base.
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

// Extension credist that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Request Topic',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of suggesting new topics to be added to the article base',
);

// Register the JS with ResourceLoader
$wgResourceModules['ext.requestTopic'] = array(
	'scripts' => 'RequestTopic.js',
	'messages' => array( 'requesttopic-choose-category' ),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'RequestTopic'
);

// Set up the new special pages
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['RequstTopic'] = $dir . 'RequestTopic.i18n.php';
$wgExtensionMessagesFiles['RequstTopicNamespaces'] = $dir . 'RequestTopic.namespaces.php';
$wgAutoloadClasses['Request'] = $dir . 'Request.php';
$wgAutoloadClasses['RequestTopic'] = $dir . 'RequestTopic.body.php';
$wgAutoloadClasses['ListRequestedTopics'] = $dir . 'RequestTopic.body.php';
$wgSpecialPages['RequestTopic'] = 'RequestTopic';
$wgSpecialPages['ListRequestedTopics'] = 'ListRequestedTopics';

// Hooked functions
$wgHooks['ArticleSaveComplete'][] = 'RequestTopic::notifyRequests';
$wgHooks['ArticleDelete'][] = 'RequestTopic::uncategorizeRequest';
$wgHooks['CanonicalNamespaces'][] = 'RequestTopic::registerCanonicalNamespaces';
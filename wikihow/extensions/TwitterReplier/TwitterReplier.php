<?php
/**
 * Tweets helpful articles
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Mark Steudel <msteudel@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['special'][] = array(
	'name' => 'TwitterReplier',
	'version' => '1.0',
	'author' => 'Mark Steudel',
	'description' => 'Tweet helpful articles',
);

// Register the CSS and the JS with ResourceLoader
$wgResourceModules['ext.twitterReplier'] = array(
	'scripts' => array( 'relative-time.js', 'TwitterReplier.js' ),
	'dependencies' => 'jquery.ui.dialog',
	'styles' => 'TwitterReplier.css',
	'messages' => array(
		'twitterreplier-default-search-title', 'howto',
		'twitterreplier-new-tweet', 'twitterreplier-new-tweets',
		'twitterreplier-ok', 'twitterreplier-random-message-1',
		'twitterreplier-random-message-2', 'twitterreplier-random-message-3',
		'twitterreplier-ie-warning-title', 'twitterreplier-ie-warning-message',
		'twitterreplier-searching', 'twitterreplier-success-title',
		'twitterreplier-success-message', 'twittereplier-error-title',
		'twittereplier-error-reply-too-long', 'twittereplier-error-reply-too-short',
		'twittereplier-loading', 'twittereplier-error-missing-keywords',
		'twittereplier-accounts-unlinked-title', 'twittereplier-accounts-unlinked-message',
		'twittereplier-error-already-replying',
		// relative-time.js
		'twitterreplier-relative-time-ago',
		'twitterreplier-relative-time-from-now',
		'twitterreplier-relative-time-recently',
		'twitterreplier-relative-time-seconds',
		'twitterreplier-relative-time-minute',
		'twitterreplier-relative-time-minutes',
		'twitterreplier-relative-time-hour',
		'twitterreplier-relative-time-hours',
		'twitterreplier-relative-time-day',
		'twitterreplier-relative-time-days',
		'twitterreplier-relative-time-month',
		'twitterreplier-relative-time-months',
		'twitterreplier-relative-time-year',
		'twitterreplier-relative-time-years'
	),
 	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'TwitterReplier',
	'position' => 'top'
);

// Set up the new special pages
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['TwitterReplier'] = $dir . 'TwitterReplier.i18n.php';

$wgAutoloadClasses['TweetModel'] = $dir . 'Tweet.model.php';
$wgAutoloadClasses['TwitterAuthModel'] = $dir . 'TwitterAuth.model.php';
$wgAutoloadClasses['SearchCategoryModel'] = $dir . 'SearchCategories.model.php';
$wgAutoloadClasses['TwitterReplierTemplate'] = $dir . 'TwitterReplierTemplate.class.php';
$wgAutoloadClasses['EpiCurl'] = $dir . 'twitter-async/EpiCurl.php';
$wgAutoloadClasses['EpiOAuth'] = $dir . 'twitter-async/EpiOAuth.php';
$wgAutoloadClasses['EpiOAuthResponse'] = $dir . 'twitter-async/EpiOAuth.php';
$wgAutoloadClasses['EpiOAuthException'] = $dir . 'twitter-async/EpiOAuth.php';
$wgAutoloadClasses['EpiTwitter'] = $dir . 'twitter-async/EpiTwitter.php';
$wgAutoloadClasses['RestRequest'] = $dir . 'RestRequest.class.php';

$wgAutoloadClasses['TwitterReplier'] = $dir . 'TwitterReplier.body.php';
$wgSpecialPages['TwitterReplier'] = 'TwitterReplier';
$wgSpecialPages['TweetItForward'] = 'TwitterReplier';

// $wgCookiePrefix isn't available yet here because it's defined in Setup.php
define( 'TRCOOKIE', 'wiki_sharedTwitterReplier' );

function wfTwitterReplierOnLogout() {
	global $wgCookiePath, $wgCookieDomain;
	setcookie( TRCOOKIE, '', time() - 604800, $wgCookiePath, $wgCookieDomain, false, true );
	if ( $_SESSION && $_SESSION['hash'] ) {
		unset( $_SESSION['hash'] );
	}
	return true;
}

$wgHooks['UserLogout'][] = 'wfTwitterReplierOnLogout';

// update.php handler
$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfTwitterReplierCreateTables';

/**
 * Handler for the MediaWiki update script, update.php; this code is
 * responsible for creating the six new tables in the database when the user
 * runs maintenance/update.php.
 *
 * @param $updater DatabaseUpdater
 * @return Boolean: true
 */
function wfTwitterReplierCreateTables( $updater ) {
	$dir = dirname( __FILE__ );

	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_cookie', "$dir/twitter_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_oauth', "$dir/twitter_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_reply_log', "$dir/twitter_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_reply_tweets', "$dir/twitter_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_search_categories', "$dir/twitter_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'twitterreplier_tweets', "$dir/twitter_tables.sql", true
	) );

	return true;
}
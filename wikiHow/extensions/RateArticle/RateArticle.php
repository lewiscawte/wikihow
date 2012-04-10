<?php
/**
 * An extension that allows users to rate articles.
 *
 * @file
 * @ingroup Extensions
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgShowRatings = false; // set this to false if you want your ratings hidden

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RateArticle',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic article ratings system',
);

// Set up the new log type
$wgLogTypes[] = 'accuracy';
$wgLogNames['accuracy'] = 'accuracylogpage';
$wgLogHeaders['accuracy'] = 'accuracylogtext';

// Hooked functions
$wgHooks['AfterArticleDisplayed'][] = 'RateArticle::showForm';
$wgHooks['ArticleDelete'][] = 'RateArticle::clearRatingsOnDelete';

// Set up all the new special pages
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['RateArticle'] = $dir . 'RateArticle.i18n.php';

$wgSpecialPages['RateArticle'] = 'RateArticle';
$wgAutoloadClasses['RateArticle'] = $dir . 'RateArticle.body.php';

$wgSpecialPages['ListRatings'] = 'ListRatings';
$wgAutoloadClasses['ListRatings'] = $dir . 'RateArticle.body.php';

$wgSpecialPages['ClearRatings'] = 'ClearRatings';
$wgAutoloadClasses['ClearRatings'] = $dir . 'RateArticle.body.php';

$wgAutoloadClasses['ListAccuracyPatrol'] = $dir . 'RateArticle.body.php';
$wgSpecialPages['AccuracyPatrol'] = 'ListAccuracyPatrol';

// update.php handler
$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfRateArticleCreateTables';

/**
 * Handler for the MediaWiki update script, update.php; this code is
 * responsible for creating the rating and rating_low tables in the database
 * when the user runs maintenance/update.php.
 *
 * @param $updater DatabaseUpdater
 * @return Boolean: true
 */
function wfRateArticleCreateTables( $updater ) {
	$dir = dirname( __FILE__ );

	$updater->addExtensionUpdate( array(
		'addTable', 'rating', "$dir/rating_tables.sql", true
	) );
	$updater->addExtensionUpdate( array(
		'addTable', 'rating_low', "$dir/rating_tables.sql", true
	) );

	return true;
}

function wfGetRatingForArticle( $id, $minVotes ) {
	global $wgMemc;

	$cacheKey = wfMemcKey( 'rating', $id, $minVotes );
	$ret = -1;
	$mres = $wgMemc->get( $cacheKey );
	if ( $mres === null ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'rating',
			array(
				'COUNT(*) AS C',
				'AVG(rat_rating) AS A'
			),
			array(
				'rat_isdeleted' => 0,
				'rat_page' => $id
			),
			__FUNCTION__
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			if ( $row->C > $minVotes ) {
				$ret = $row->A;
			}
		}
		$wgMemc->set( $cacheKey, $ret );
	} else {
		$ret = $mres;
	}

	return $ret;
}
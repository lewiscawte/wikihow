<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:RateArticle-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgShowRatings = false; // set this to false if you want your ratings hidden


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RateArticle',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Provides a basic article ratings system',
);

$wgExtensionMessagesFiles['RateArticle'] = dirname(__FILE__) . '/RateArticle.i18n.php';

$wgSpecialPages['RateArticle'] = 'RateArticle';
$wgAutoloadClasses['RateArticle'] = dirname( __FILE__ ) . '/RateArticle.body.php';

$wgSpecialPages['ListRatings'] = 'ListRatings';
$wgAutoloadClasses['ListRatings'] = dirname( __FILE__ ) . '/RateArticle.body.php';

$wgSpecialPages['Clearratings'] = 'Clearratings';
$wgAutoloadClasses['Clearratings'] = dirname( __FILE__ ) . '/RateArticle.body.php';

$wgSpecialPages['AccuracyPatrol'] = 'AccuracyPatrol';
$wgAutoloadClasses['AccuracyPatrol'] = dirname( __FILE__ ) . '/RateArticle.body.php';

function wfGetRatingForArticle($id, $minvotes) {
	$ret = -1;
	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('rating',
			array('count(*) as C',
				'avg(rat_rating) as A'
			),
			array('rat_isdeleted=0',
				"rat_page={$id}"
			)
		);
	if ($row = $dbr->fetchObject($res) ){
		if ($row->C > $minvotes) $ret = $row->A;
	}
	$dbr->freeResult($res);
	return $ret;
}

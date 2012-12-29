<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    /**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Newarticleboost-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


/**#@+
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Newarticleboost',
	'author' => 'Travis Derouin',
	'description' => 'Provides a separate way of patrolling new articles',
	'url' => 'http://www.wikihow.com/WikiHow:Newarticleboost-Extension',
);

$wgExtensionMessagesFiles['Newarticleboost'] = dirname(__FILE__) . '/Newarticleboost.i18n.php';
$wgSpecialPages['Newarticleboost'] = 'Newarticleboost';
$wgSpecialPages['NABStatus'] = 'NABStatus';
$wgSpecialPages['Copyrightchecker'] = 'Copyrightchecker';
$wgSpecialPages['Markrelated'] = 'Markrelated';
$wgAutoloadClasses['Newarticleboost'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABStatus'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['Copyrightchecker'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['Markrelated'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
		
$wgHooks['ArticleDelete'][] = array("wfNewArticlePatrolClearOnDelete");
$wgHooks['ArticleSaveComplete'][] = array("wfNewArticlePatrolAddOnCreation");

$wgAvailableRights[] = 'newarticlepatrol';
$wgGroupPermissions['newarticlepatrol']['newarticlepatrol'] = true;
$wgGroupPermissions['newarticlepatrol' ]['move']   = true;

$wgLogTypes[]                      = 'nap';
$wgLogNames['nap']    = 'newarticlepatrollogpage';
$wgLogHeaders['nap']          = 'newarticlepatrollogpagetext';
	
// Take the article out of the queue if it's been deleted
	function wfNewArticlePatrolClearOnDelete($article, $user, $reason) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query("delete from newarticlepatrol where nap_page={$article->getId()};");	
		return true;
	}
	
	function wfNewArticlePatrolAddOnCreation($article, $user, $text, $summary, $p5, $p6, $p7) {

		try {
			$dbr = wfGetDB(DB_MASTER);
			$t = $article->getTitle();
			if (!$t || $t->getNamespace() != NS_MAIN) 
				return true;
			$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
			$nap_count = $dbr->selectField('newarticlepatrol', 'count(*)', array('nap_page=' . $article->getId()));
			if ((!$num_revisions || $num_revisions == 1) && $nap_count == 0) {
				$dbw = wfGetDB(DB_MASTER);
				$min_ts = $dbr->selectField('revision', 'min(rev_timestamp)', array('rev_page=' . $article->getId()));
				$dbw->insert('newarticlepatrol', 
						array('nap_page' => $article->getId(), 'nap_timestamp' => $min_ts), "wfNewArticlePatrolAddOnCreation");
			}
		} catch (Exception $e) {
			return true;
		}	
		return true;
	}

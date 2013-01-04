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
$wgGroupPermissions['staff' ]['newbienap']   = true;

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
	global $wgUser;
	try {
		$dbr = wfGetDB(DB_MASTER);
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN)  {
			return true;
		}

		if (in_array("bot", $wgUser->getGroups())) {
			// ignore bots
			return true;
		}
		$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
		$min_rev = $dbr->selectField('revision', 'min(rev_id)', array('rev_page=' . $article->getId()));
		$ts = $dbr->selectField('revision', 'rev_timestamp', array('rev_id=' . $min_rev));
		$rev_id_fetched = $article->mRevIdFetched;
		$nap_count = $dbr->selectField('newarticlepatrol', 'count(*)', array('nap_page=' . $article->getId()));
	
		$userid = $dbr->selectField('revision', 'rev_user', array('rev_id=' . $min_rev));
		// filter for bots again
		if ($userid > 0) {
			$u = User::newFromID($userid); 
			if ($u && in_array("bot", $u->getGroups())) {
				return true;
			}	
		}

		if (($min_rev = $article->mRevIdFetched || !$num_revisions || $num_revisions < 5) 
				&& $nap_count == 0		// ignore articles already in there. 
				&& $ts > '20090101000000' // forget articles before 2009-01-01
			) {
			// check for newbie feature
			$newbie = array('anon'=>1, 'articles'=>5, 'edits'=>10);
			$msg = wfMsg('NSS');
			$lines = split("\n", $msg);
			foreach ($lines as $l) {
				$l = trim($l);
				$parts = split("=", $l);
				$k = $parts[0];
				$v = $parts[1];
				$newbie[$k] = $v;
			}

			// only do checks if we the anon flag is set, or the user is logged in
			if ($newbie['anon'] || $user->getID()) {
				// how many edits? 
				$nap_newbie = 0;
				if ($newbie['edits'] > 0) {
					$count = $dbr->selectField(array('revision', 'page'), 'count(*)', array('rev_page=page_id', 'page_namespace'=>NS_MAIN, 'rev_user_text'=>$user->getName()));
					if ($count < $newbie['edits']) 
						$nap_newbie = 1;
				}
				if ($nap_newbie == 0 && $newbie['articles'] > 0) {
					// how many articles created?
					$count = $dbr->selectField(array('firstedit'), 'count(*)', array('fe_user_text'=>$user->getName()));
					if ($count < $newbie['articles']) 
						$nap_newbie = 1;
				}
			}
			$dbw = wfGetDB(DB_MASTER);
			$min_ts = $dbr->selectField('revision', 'min(rev_timestamp)', array('rev_page=' . $article->getId()));
			$dbw->insert('newarticlepatrol', 
					array('nap_page' => $article->getId(), 'nap_timestamp' => $min_ts, 'nap_newbie'=>$nap_newbie), "wfNewArticlePatrolAddOnCreation");
		}
	} catch (Exception $e) {
		return true;
	}	
	return true;
}

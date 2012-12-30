<?php

if ( !defined( 'MEDIAWIKI' ) ) {
exit(1);
}

/**#@+
 * A simple extension that allows users to enter a title before creating a page. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RCPatrol',
	'author' => 'Travis Derouin',
	'description' => 'An improved way of doing RC Patrol', 
);

$wgSpecialPages['RCPatrol'] 		= 'RCPatrol';
$wgAutoloadClasses['RCPatrol'] 		= dirname( __FILE__ ) . '/RCPatrol.body.php';

$wgSpecialPages['RCPatrolGuts'] 	= 'RCPatrolGuts';
$wgAutoloadClasses['RCPatrolGuts'] 	= dirname( __FILE__ ) . '/RCPatrol.body.php';

$wgSpecialPages['RCActiveWidget'] 		= 'RCActiveWidget';
$wgAutoloadClasses['RCActiveWidget'] 	= dirname( __FILE__ ) . '/RCPatrol.body.php';

/*
$wgHooks['ArticleRollbackComplete'][] = array("wfPatrolRollbackedEdits");


function wfPatrolRollbackedEdits($article, $user, $target) {

	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('recentchanges',
				array('rc_id'),
				array('rc_this_oldid < ' . $article->mLatest, 'rc_cur_id' => $article->getTitle()->getArticleID(), 'rc_patrolled'=>0)
			);
	$ids = array();
	while ($row = $dbr->fetchObject($res)) {
		$ids[] = $row->rc_id;
	}
	#print_r($dbr);
	echo "{$article->getTitle()->getArticleID()} Would mark these recentchanges as patrolled ids: " . print_r($ids, true);
	return true;
}
*/

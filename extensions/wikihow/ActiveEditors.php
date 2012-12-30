<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that shows the most active users in the main namespace. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:ActiveEditors-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfActiveEditors';

/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfRateArticleForm");

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'ActiveEditors',
	'author' => 'Travis Derouin',
	'description' => 'Simple extension that shows the most active editors in the main namespace.',
	'url' => 'http://www.wikihow.com/WikiHow:ActiveEditors-Extension',
);

function wfActiveEditors() {
	global $wgMessageCache;
	 $wgMessageCache->addMessages(
        array(
			'activeeditors' => 'Most Active Editors',
			'activeeditors_today' => 'Today',
			'activeeditors_lastweek' => 'Last Week',
			'activeeditor_user' => '',
			'activeeditor_numedits' => 'Edits',
		)
	);
}

// returns a nice small table displaying the active editors.

function wfActiveEditorsDisplay() {
	global $wgUser;

	$date =  gmdate("Ymd");
	$dbr =& wfGetDB( DB_SLAVE);
	$sql = "select rev_user, rev_user_text, count(*) as C from revision left join page on rev_page = page_id where rev_timestamp like '$date%' and rev_user != 0 and page_namespace = 0 group by rev_user order by C desc limit 5";
	$res = $dbr->query($sql);
	$sk = $wgUser->getSkin();
	//$result = "<div style='border: 1px solid #000; padding: 5px; float:right;'>
	$result ="
<style type='text/css'>
#active_editors {
    vertical-align: top;
    text-align: left;
    width: 165px;
    float: right;
    margin-top: 33px;
    margin-right:18px;
    padding: 5px;
    border: 1px solid #ccc;
}
</style>
	<div id='active_editors'>
		<img src='/skins/common/images/people.png' height='20px'>&nbsp;<b>" . wfMsg('activeeditors') . "</b><br/><br/>
		<table width='100%'>
			<tr><td><i>" . wfMsg('activeeditors_today')  . "</i></td><td align='right'>" . wfMsg('activeeditor_numedits') . "</td></tr>";
    while ( $row = $dbr->fetchObject( $res ) ) {
		$u = User::newFromName($row->rev_user_text);
		$real_name = $u->getRealName();
		if ($real_name == '')
			$real_name = $row->rev_user_text;	
		$result .= "<tr><td>" . $sk->makeLinkObj($u->getUserPage(), $real_name) . "</td><td align='right'>{$row->C}</td></tr>";
	}
	$result .= "</table><br/>";

    $dbr->freeResult( $res );


	$last_week = gmmktime() - 60 * 60 * 24 * 7;
	$monday = $last_week;
	for ($i=0; $i < 7 ; $i++) {
		if (date("N", $monday) == "1") break;
			$monday = $monday -  60 * 60 * 24 ;
	}
	//echo "Monday date " . date ("Ymd", $monday);
//	echo "Sunday date " . date ("Ymd", $monday + 60*60*24*7);
	$next_monday = $monday + 60*60*24*7;
	$x = date("Ymd", $monday);
	$y = date("Ymd", $next_monday);
	$sql = "select rev_user, rev_user_text, count(*) as C from revision left join page on rev_page = page_id where rev_timestamp between '{$x}000000' and '{$y}000000' and rev_user != 0 and page_namespace = 0 group by rev_user order by C desc limit 5";
	$res = $dbr->query($sql);
	$result .= "
		<table width='100%'>
			<tr><td><i> " . wfMsg('activeeditors_lastweek') . "</i>
</td><td align='right'>" . wfMsg('activeeditor_numedits') . "</td></tr>";
    while ( $row = $dbr->fetchObject( $res ) ) {
		$u = User::newFromName($row->rev_user_text);
		$real_name = $u->getRealName();
		if ($real_name == '')
			$real_name = $row->rev_user_text;	
		$result .= "<tr><td>" . $sk->makeLinkObj($u->getUserPage(), $real_name) . "</td><td align='right'>{$row->C}</td></tr>";
	}
	$result .= "</table>";
    $dbr->freeResult( $res );

	$result .= "</div>";
	return $result;

}
?>

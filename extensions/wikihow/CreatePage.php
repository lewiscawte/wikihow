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
	'name' => 'CreatePage',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way entering a title and searching for potential duplicate articles before creating a page',
	'url' => 'http://www.wikihow.com/WikiHow:CreatePage-Extension',
);
$wgExtensionMessagesFiles['CreatePage'] = dirname(__FILE__) . '/CreatePage.i18n.php';

$wgSpecialPages['CreatePage'] = 'CreatePage';
$wgSpecialPages['CreatePageTitleResults'] = 'CreatePageTitleResults';
$wgSpecialPages['CreatepageWarn'] = 'CreatepageWarn';
$wgSpecialPages['ProposedRedirects'] = 'ProposedRedirects';
$wgSpecialPages['CreatepageEmailFriend'] = 'CreatepageEmailFriend';
$wgSpecialPages['CreatepageFinished'] = 'CreatepageFinished';
$wgSpecialPages['CreatepageReview'] = 'CreatepageReview';
$wgSpecialPages['SuggestionSearch'] = 'SuggestionSearch';
$wgSpecialPages['ManageSuggestions'] = 'ManageSuggestions';
$wgAutoloadClasses['CreatePage'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['CreatePageTitleResults'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['CreatepageWarn'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['ProposedRedirects'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['CreatepageEmailFriend'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['CreatepageFinished'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['CreatepageReview'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['SuggestionSearch'] = dirname( __FILE__ ) . '/CreatePage.body.php';
$wgAutoloadClasses['ManageSuggestions'] = dirname( __FILE__ ) . '/CreatePage.body.php';

$wgHooks['ArticleDelete'][] = array("wfCheckSuggestionOnDelete");
$wgHooks['ArticleSaveComplete'][] = array("wfCheckSuggestionOnSave");
$wgHooks['ArticleSaveComplete'][] = array("wfTrackEditCompletion");
$wgHooks['ArticleSave'][] = array("wfCheckForCashSpammer");
$wgHooks['TitleMoveComplete'][] = array("wfCheckSuggestionOnMove");
$wgHooks['ArticlePageDataBefore'][] = array("wfShowFollowUpOnCreation");

$wgLogTypes[]                   	= 'suggestion';
$wgLogNames['suggestion']          = 'suggestionlogpage';
$wgLogHeaders['suggestion']        = 'suggestionlogtext';

$wgLogTypes[]                       = 'redirects';
$wgLogNames['redirects']   			= 'redirects';
$wgLogHeaders['redirects'] 			= 'redirectstext';
$wgLogActions['redirects/added'] = 'redirects_logsummary';

function wfClearSuggestionsCache($t) {
	global $wgMemc;

	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select(array('suggested_links', 'suggested_titles'),
			array('sl_page'),
			array('st_title' => $t->getDBKey(), 'sl_sugg = st_id')
		);
	while ($row = $dbr->fetchObject($res)) {
    	$key = "suggested_titles_:" . $row->sl_page;
    	$wgMemc->set($key, null);
	}
	return true;
}
function wfCheckSuggestionOnDelete($article, $user, $reason) {
    try {
        $dbr = wfGetDB(DB_SLAVE);
        $t = $article->getTitle();
        if (!$t || $t->getNamespace() != NS_MAIN)
            return true;
        $dbw = wfGetDB(DB_MASTER);
        $key = generateSearchKey(trim($t));
        $dbw->update('suggested_titles',
                    array('st_used' => 0),
					array('st_key' => $key), 
					"wfCheckSuggestionOnSave");
		wfClearSuggestionsCache($t);
    } catch (Exception $e) {
        return true;
    }
    return true;
}

function wfCheckSuggestionOnMove( &$ot, &$nt, &$wgUser, $pageid, $redirid) {
    $dbw = wfGetDB(DB_MASTER);
    $dbw->update('suggested_titles',
        array('st_used' => 1, 'st_created' => wfTimestampNow(TS_MW)),
        array('st_title' => $nt->getDBKey()),
        "wfCheckSuggestionOnMove");
	wfClearSuggestionsCache($nt);
    return true;
}

/* 
	When a new article is created, mark the suggsted as used in the DB
*/
function wfCheckSuggestionOnSave($article, $user, $text, $summary, $p5, $p6, $p7) {
    try {
        $dbr = wfGetDB(DB_SLAVE);
        $t = $article->getTitle();
        if (!$t || $t->getNamespace() != NS_MAIN)
            return true;
        $num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
		// < 2 for race conditions
        if ($num_revisions < 2) {
            $dbw = wfGetDB(DB_MASTER);
			$key = generateSearchKey(trim($t));
            $dbw->update('suggested_titles',
                    array('st_used' => 1, 'st_created' => wfTimestampNow(TS_MW)), 
					array('st_key' => $key), "wfCheckSuggestionOnSave");
			wfClearSuggestionsCache($t);
        }
        if ($num_revisions == 1) {
            $email = $dbw->selectField('suggested_titles', array('st_notify'),
                array('st_title' => $t->getDBKey()));
            if ($email) {
                $dbw->insert('suggested_notify', array('sn_page' => $article->getId(),
                            'sn_notify' => $email,
                            'sn_timestamp' => wfTimestampNow(TS_MW)));
            }
        }
    } catch (Exception $e) {
        return true;
    }
    return true;
}


function wfGetTopCategory($title = null) {
    global $wgTitle;
    if (!$title)
        $title = $wgTitle;
    $parenttree = $title->getParentCategoryTree();
    $parenttree_tier1 = $parenttree;

    $result = null;
    while ((!$result || $result == "WikiHow") && is_array($parenttree)) {
        $a = array_shift($parenttree);
        if (!$a) {
            $keys = array_keys($parenttree_tier1);
            $result = str_replace("Category:", "", $keys[0]);
            break;
        }
        $last = $a;
        while (sizeof($a) > 0 && $a = array_shift($a) ) {
            $last = $a;
        }
        $keys = array_keys($last);
        $result = str_replace("Category:", "", $keys[0]);
    }
    return  Title::makeTitle(NS_CATEGORY, $result);
}

function wfGetSuggestedTitles($t) {
	global $wgUser, $wgMemc;

	$html = "";
	if (!$t)
		return $html;

    // use memcached to store results
    $key = wfMemcKey("suggested_titles_:" . $t->getArticleID());
	$result = $wgMemc->get($key);
    if ($result) {
        return $result;
    }

	wfProfileIn("wfGetSuggestedTitles");
	$dbr = wfGetDB(DB_SLAVE);
    $group = date("W") % 5;


    $res = $dbr->select('suggested_links',
                array('sl_sugg'),
                array('sl_page' => $t->getArticleID()),
                array('ORDER BY' => 'sl_sort')
            );
    $ids = array();
    while ($row=$dbr->fetchObject($res)) {
        $ids[] = $row->sl_sugg;
    }
	$randStr = wfRandom();
    if (sizeof($ids) == 0) {
        $top = wfGetTopCategory($t);
        if ($top)
            $res = $dbr->query("select st_title from suggested_titles where st_used = 0 and st_patrolled=1 
                and st_group = $group and st_category = " . $dbr->addQuotes($top->getText()) . " 
				and st_random > $randStr limit 5;");
    } else {
        $sql = "(" . implode(", ", $ids) . ")";
        $sql = "select st_title from suggested_titles where st_used = 0 and st_patrolled=1 
				and st_group = $group and st_id in $sql limit 5;";
        $res = $dbr->query($sql);
    }

    if ($dbr->numRows($res) == 0) {
        $top = wfGetTopCategory($t);
        if ($top) {
            $sql = "select st_title from suggested_titles where st_used = 0 and st_patrolled=1 
                and st_group = $group and st_category = " . $dbr->addQuotes($top->getText()) 
			. "  and st_random > $randStr limit 5;";
            $res = $dbr->query($sql);
        }
    }

	while ($row = $dbr->fetchObject($res)) {
		$title = Title::newFromText($row->st_title);
		if (!$title) continue;
		$sp = SpecialPage::getTitleFor( 'CreatePage', $title->getText() );
		$html .= "<li><a onclick='clickshare(46);' href='{$sp->getFullUrl()}' class='new'>" . wfMsg('howto', $title->getText()) ."</a></li>\n";
	}
	if ($html != "") {
		$html = "
<h2>" . wfMsg('suggested_titles_section') . "</h2>
<div id='suggested_titles'>"
. wfMsg('suggested_titles_section_description') . "
<br/><br/>
<ul id='gatSuggestedTitle'>{$html}</ul>
</div>
		";
	}
    $wgMemc->set($key, $html);
	wfProfileOut("wfGetSuggestedTitles");
	return $html;
}

function wfGetTitlesToImprove($t) {
	global $wgUser;

	$html = "";
	if (!$t)
		return $html;
	$dbr = wfGetDB(DB_SLAVE);
	$sk = $wgUser->getSkin();
	$res = $dbr->select(
			array('improve_links'),
			array('il_namespace', 'il_title'), 
			array('il_from'=>$t->getArticleID()),
			"wfGetTitlesToImprove", 
			array('LIMIT' => 5)
		);

	while ($row = $dbr->fetchObject($res)) {
		$title = Title::makeTitle($row->il_namespace, $row->il_title);
		if (!$title) continue;
		$html .= "<li><a onclick='clickshare(45);' href='{$title->getFullUrl()}' rel='nofollow'>" . wfMsg('howto', $title->getText()) ."</a></li>\n";
	}
	if ($html != "") {
		$html = "<p><br /></p>
<div id='suggested_titles'>
<div class='SecL'></div>
<div class='SecR'></div>
<h2>" . wfMsg('titles_to_improve_section') . "</h2>"
. wfMsg('titles_to_improve_description') . "
<br/><br/>
<ul>{$html}</ul>
</div>
		";
	}
	return $html;
}

function wfShowFollowUpOnCreation($article, $details) {
	global $wgTitle, $wgRequest, $wgOut, $wgUser;

	try {
        $t = $article->getTitle();
        if (!$t || $t->getNamespace() != NS_MAIN)
            return true;        
		$dbr = wfGetDB(DB_MASTER);
		$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
		if ($num_revisions > 1) return true;
		$user_name  = $dbr->selectField('revision', 'rev_user_text', array('rev_page=' . $article->getId()));
		if ((strpos($_SERVER['HTTP_REFERER'], 'action=edit') !== false
			 || strpos($_SERVER['HTTP_REFERER'], 'action=submit2') !== false)
			&& $wgUser->getName() == $user_name
			&& (!isset($_SESSION["aen_dialog"][$article->getId()]))
			)
		{

			$wgOut->addHTML('<script type="text/javascript" language="javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.js?rev=') . WH_SITEREV . '"></script>
								<script type="text/javascript" language="javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/authoremails.js?rev=') . WH_SITEREV . '"></script>
  	                     <link rel="stylesheet" href="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.css?rev=') . WH_SITEREV . '" type="text/css" />');

			if ($wgUser->getID() == 0) {

				setcookie('aen_anon_newarticleid',$article->getId(),time()+3600); 
				setcookie('aen_dialog_check',$article->getId(),time()+3600); 

				$wgOut->addHTML('
					<script type="text/javascript">
						var whNewLoadFunc = function() {
							if ( getCookie("aen_dialog_check") != "" ) {
								jQuery("#dialog-box").load("/Special:CreatepageFinished", function(){
									$( "#dialog-box" ).dialog( "option", "position", "center" );
								});
								jQuery("#dialog-box").dialog({
									width: 560,
									modal: true,
									title: "' . wfMsg('createpage_congratulations') . '"
								});
								//popModal("/Special:CreatepageFinished", 600, 180);
								deleteCookie("aen_dialog_check");
							}
						};
						// during move to jquery...
						if (typeof document.observe == "function") {
							document.observe("dom:loaded", whNewLoadFunc);
						} else {
							$(document).ready(whNewLoadFunc);
						}
					</script>
				');
			} else {

				if ($wgUser->getOption( 'enableauthoremail' ) != '1') {
					setcookie('aen_dialog_check',$article->getId(),time()+3600);

					$wgOut->addHTML('
						<script type="text/javascript">
						var whNewLoadFunc = function() {
							if ( getCookie("aen_dialog_check") != "" ) {
								jQuery("#dialog-box").load("/Special:CreatepageFinished", function(){
									$( "#dialog-box" ).dialog( "option", "position", "center" );
								});
								jQuery("#dialog-box").dialog({
									width: 750,
									modal: true,
									title: "' . wfMsg('createpage_congratulations') . '"
								});
								//popModal("/Special:CreatepageFinished", 600, 340);
								deleteCookie("aen_dialog_check");
							}
						};
						// during move to jquery...
						if (typeof document.observe == "function") {
							document.observe("dom:loaded", whNewLoadFunc);
						} else {
							$(document).ready(whNewLoadFunc);
						}
						</script>
					');
				}
			}
			$_SESSION["aen_dialog"][$article->getId()] = 1;
		}
	} catch (Exception $e) {
	}
	return true;
}


function wfCheckForCashSpammer($article, $user, $text, $summary, $flags, $p1, $p2, $flags) {
    if ($text) {
        if ($article->getTitle()->getText() == "Yrt291x"
			|| $article->getTitle()->getText() == "Spam Blacklist") 
				return true;
        $msg = preg_replace('@<\![-]+-[\n]+|[-]+>@U', '', wfMsg('yrt291x'));
        $msgs = split("\n", $msg);
        foreach ($msgs as $m) {
            $m = trim($m);
            if ($m == "") continue;
            if (stripos($text, $m) !== false) {
                return false;
            }
        }
    }
    return true;
}

function wfTrackEditToken($user, $token, $title, $guided) {

	global $wgLanguageCode;
	if ($wgLanguageCode != 'en');
		return true;

	$dbw = wfGetDB(DB_MASTER);
	$dbw->insert('edit_track', 
			array(	'et_user' 			=> $user->getID(), 
					'et_user_text'		=> $user->getName(),
					'et_token' 			=> $token,
					'et_page' 			=> $title->getArticleID(),
					'et_timestamp_start'=> wfTimestampNow(), 
					'et_guided' 		=> ($guided?1:0)
				)
		); 
}
function wfTrackEditCompletion($article, $user, $text, $summary, $p5, $p6, $p7) {
	global $wgLanguageCode;
	if ($wgLanguageCode != 'en');
		return true;
	global $wgRequest;	
	$token = $wgRequest->getVal('wpEditTokenTrack');

	if ($token) {
		$dbw = $dbw = wfGetDB(DB_MASTER);
		$dbw->update('edit_track',
			array (
					'et_completed' => 1,
					'et_timestamp_completed' => wfTimestampNow(),
			), 
			array(
					'et_token'	=> $token,
			)
		);
	}
	return true;
}


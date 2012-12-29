<?

class RCPatrol extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'RCPatrol' );
    }

	function getListofEditors($result) {
		$dbr = wfGetDB(DB_SLAVE);
		$users = array();
		$users_len = array();
		$res = $dbr->select('recentchanges', 
								array('rc_user', 'rc_user_text', 'rc_new_len', 'rc_old_len'),
								array("rc_id<=" . $result['rchi'], 
									"rc_id>=" . $result['rclo'], "rc_cur_id" => $result['rc_cur_id']));
		while ($row= $dbr->fetchObject($res)) {
			$u = array();
			if (isset($users[$row->rc_user_text])) {
				$u = $users[$row->rc_user_text];
				$u['edits']++;
				$u['len'] += $row->rc_new_len - $row->rc_old_len;
				$users[$row->rc_user_text] = $u;
				$users_len[$row->rc_user_text] = $u['len'];
				continue;
			}
			$u['id'] = $row->rc_user;
			$u['user_text'] = $row->rc_user_text;
			$u['edits']++;
			$u['len'] = $row->rc_new_len - $row->rc_old_len;
			$users_len[$row->rc_user_text] = $u['len'];
			$users[$row->rc_user_text] = $u;
		}
		$result['users'] = $users;
		$result['users_len'] = $users_len;
		return $result;
	}
	
	function getNextArticleToPatrol($rcid = null) {
		global $wgUser;
		while ($result = self::getNextArticleToPatrolInner()) {
			if (isset($result['users'][$wgUser->getName()])) {
				RCPatrol::skipArticle($result['rc_cur_id']);
			} else {
				break;
			}
		}
		return $result;
	}
	
	function getNextArticleToPatrolInner($rcid = null) {
        global $wgRequest, $wgUser, $wgCookiePrefix;
        $show_namespace     = $wgRequest->getVal('namespace');
        $invert             = $wgRequest->getVal('invert');
        $reverse            = $wgRequest->getVal('reverse');
        $featured           = $wgRequest->getVal('featured');
		$title				= $wgRequest->getVal('target');
		$skiptitle			= $wgRequest->getVal('skiptitle');

		$t = null;
		if ($title) 
			$t = Title::newFromText($title);
		$skip = null;
		if ($skiptitle)
			$skip = Title::newFromText($skiptitle);

        $dbr =& wfGetDB( DB_MASTER );
        $sql = "SELECT rc_id, rc_cur_id, rc_moved_to_ns, 
            rc_moved_to_title, rc_new, rc_namespace, rc_title, rc_last_oldid, rc_this_oldid FROM recentchanges " .
            ($featured ? " LEFT OUTER JOIN page on page_title = rc_title and page_namespace = rc_namespace " : "") .
            " WHERE ";

#echo "<b>REV: $reverse</b>";
		if (!$wgRequest->getVal('ignore_rcid') && $rcid)
            $sql .= " rc_id " . ($reverse == 1 ? " > " : " < ")  . " $rcid and ";

		$sql .= " rc_patrolled = 0  " .
            ($featured ? " AND page_is_featured = 1 " : "")
            . " AND rc_user_text != '" . $wgUser->getName() . "' "
        ;

		#$show_namespace = "0";
        if ($show_namespace != "")  {
            $sql .= " AND rc_namespace " . ($invert ? '!=' : '=') . $show_namespace ;
        } else  {
            // ignore video
            $sql .= " AND rc_namespace NOT IN ( " . NS_VIDEO .") ";
        }

		#$sql .= " AND rc_new = 0 ";
		if ($t) {
			$sql .= " AND rc_title != " . $dbr->addQuotes($t->getDBKey());
		}
		if ($skip) {
			$sql .= " AND rc_title != " . $dbr->addQuotes($skip->getDBKey());
		}
		# has the user skipped any articles?
        $cookiename = $wgCookiePrefix."Rcskip";
        $skipids = "";
        if (isset($_COOKIE[$cookiename])) {
            $ids = array_unique(split(",", $_COOKIE[$cookiename]));
            $good = array(); //safety first
            foreach ($ids as $id) {
                if (preg_match("@[^0-9]@", $id))
                    continue;
                $good[] = $id;
            }
            $skipids = " AND rc_cur_id NOT IN (" . implode(",", $good) . ") ";
        }
        $sql .= "$skipids ORDER by rc_id " . ($reverse == 1 ? " ASC " : " DESC ") . "  LIMIT 1";
   
		//execute the query
	     $res = $dbr->query($sql);
	
		$result = array();
        if ( $row = $dbr->fetchObject( $res ) ) {
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
            if ($row->rc_moved_to_title != "")
                $t = Title::makeTitle($row->rc_moved_to_ns, $row->rc_moved_to_title);
		
			// always compare to current version
			$result['rc_cur_id'] = $row->rc_cur_id;
			$result['new'] 		= $dbr->selectField('revision', array('max(rev_id)'), array('rev_page' => $row->rc_cur_id));
            $result['rchi']   	= $dbr->selectField('recentchanges', array('rc_id'), array('rc_this_oldid' => $result['new']));
            $result['user']   	= $dbr->selectField('recentchanges', array('rc_user_text'), array('rc_this_oldid' => $result['new']));
            $result['rclo']   	= $dbr->selectField('recentchanges', array('min(rc_id)'), array('rc_patrolled'=>0,"rc_cur_id"=>$row->rc_cur_id));
			if (!$result['rclo'])
				$result['rclo'] = $row->rc_id;
			if (!$result['rchi'])
				$result['rchi'] = $row->rc_id;
		
			# is the last patrolled edit a rollback? if so, show the diff starting at that edit
			# makes it more clear when someone has reverted vandalism
			$comm = $dbr->selectField('recentchanges', array('rc_comment'), array('rc_id'=>$result['rclo']));
			if (strpos($comm, "Reverted edits by") !== false) {
				$row2 = $dbr->selectRow('recentchanges', array('rc_id', 'rc_comment'), 
					array("rc_id < {$result['rclo']}", 'rc_cur_id' => $row->rc_cur_id),
					"getNextArticleToPatrol",
					array("ORDER BY" => "rc_id desc", "LIMIT"=>1)
					);
				if ($row2) {
					$result['rclo'] = $row2->rc_id;
				}
			}
			$result['old'] 		= $dbr->selectField('recentchanges', array('rc_last_oldid'), array('rc_id' => $result['rclo']));
			// if the article is new set 'old' to -1 so DifferenceEngine doesn't just show last edit
           	if ($dbr->selectField('recentchanges', array('rc_new'), array('rc_id'=>$result['rclo'])))
				$result['old'] = -1;
			$result['title'] 	= $t;
			$result['rcid']		= $row->rc_id;
            $result['count']    = $dbr->selectField('recentchanges', 
									array('count(*)'), 
									array("rc_id<=" . $result['rchi'], 
										"rc_id>=" . $result['rclo'], "rc_patrolled=0", "rc_cur_id" => $row->rc_cur_id));
			$result = self::getListofEditors($result);
#print_r($result); exit;
			return $result;
        }
        return null;
	}

	function getMarkAsPatrolledLink ($title, $rcid, $hi, $low, $count, $setonload) {
		global $wgRequest;
    	$sns 	= $wgRequest->getVal('show_namespace');
    	$inv	= $wgRequest->getVal('invert');
   		$fea	= $wgRequest->getVal('featured');
    	$rev 	= $wgRequest->getVal('reverse');

		$url = "/Special:RCPatrolGuts?target=" . urlencode($title->getFullText()) 
			. "&action=markpatrolled&rcid={$rcid}"
			. "&invert=$inv&reverse=$rev&featured=$fea&show_namespace=$sns"
			. "&rchi={$hi}&rclow={$low}"
		;
		$base = "mp('{$url}";

       	$msg =  wfMsg( 'markaspatrolleddiff' ); 
		$class1 = "class='button button150' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
		$class2 = "class='button white_button' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
        $link	=  " <input type='button' $class2 id='skippatrolurl' onclick=\"return skip();\" accesskey='s' title='skip [ctrl s]' value='" . wfMsg('skip') . "'/>";
		 $link	.=  "<input type='button' $class1 id='markpatrolurl' onclick=\"return mp();\" accesskey='p' title='mark as patrolled [ctrl p]' value='{$msg}'/>";
		if ($setonload) {
			$link .= "<script type='text/javascript'>marklink = '$url';
				skiplink = '$url&skip=1';
				var whNewLoadFunc = function() {
					setupTabs();
					grabnext('$url&grabnext=true');
				};
				// during move to jquery...
				$(document).ready(whNewLoadFunc);
				</script>";

		}
		# this is kind of dumb, but it works
		$link .= "<div id='newlinkpatrol' style='display:none;'>$url</div><div id='newlinkskip' style='display:none;'>$url&skip=1</div>"			
			 . "<div id='skiptitle' style='display:none;'>" . urlencode($title->getDBKey()) . "</div>"
			 . "<div id='permalink' style='display:none;'>" . str_replace("&action=markpatrolled", "&action=permalink", $url)  . "</div>";
		return $link;
	}

    function generateRollback( $rev, $oldid = 0) {
       global $wgUser, $wgRequest, $wgTitle;
       $title = $rev->getTitle();
                
       $extraRollback = $wgRequest->getBool( 'bot' ) ? '&bot=1' : '';
       $extraRollback .= '&token=' . urlencode(
       $wgUser->editToken( array( $title->getPrefixedText(), $rev->getUserText() ) ) );
            
        if ($oldid)
            $url = $title->getFullURL() . "?action=rollback&old={$oldid}&from=" . urlencode( $rev->getUserText() ). $extraRollback . "&useajax=true";
        else    
            $url = $title->getFullURL() . "?action=rollback&from=" . urlencode( $rev->getUserText() ). $extraRollback . "&useajax=true";
        $class = "class='button white_button_100' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
            
        $s  = "<script type='text/javascript' src='".wfGetPad('/extensions/min/f/extensions/wikihow/rollback.js')."'></script>
            <script type='text/javascript'>
                var gRollbackurl = \"{$url}\";
               var msg_rollback_complete = \"" . htmlspecialchars(wfMsg('rollback_complete')) . "\";
               var msg_rollback_fail = \"" . htmlspecialchars(wfMsg('rollback_fail')) . "\";
               var msg_rollback_inprogress = \"" . htmlspecialchars(wfMsg('rollback_inprogress')) . "\";
               var msg_rollback_confirm= \"" . htmlspecialchars(wfMsg('rollback_confirm')) . "\";
            </script>
                <a $class href='' onclick='return rollback();' accesskey='r' title='rollback [ctrl r]'>" . wfMsg('rollbacklink') . "</a>
            </span>";
		$s .= "<div id='newrollbackurl' style='display:none;'>{$url}</div>";
        return $s;

    }


	function getQuickEdit($title, $result) {
		global $wgServer;

		// build the array of users for the quick note link sorted by the # of bytes changed descending, i.e. more is better
		$users = array();
		$sorted = $result['users_len'];
		if (!$sorted)
			return;
		asort($sorted, SORT_NUMERIC);
		$sorted = array_reverse($sorted);
		foreach ($sorted as $s=>$len) {
			$u = User::newFromName($s);
			if (!$u) {
				// handle anons
				$u = new User();
				$u->setName($s);
			}
			$users[] = $u;
		}
#print_r($users); print_r($result); exit;
    	$editURL = $wgServer . '/Special:Newarticleboost?type=editform&target=' . urlencode($title->getFullText());
		$class = "class='button white_button_100' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'";
        $link =  "<a title='quick edit [ctrl e]' accesskey='e' href='' $class onclick=\"return initPopupEdit('".$editURL."') ;\">" . htmlspecialchars( wfMsg( 'editold-quick' ) ) . "</a> ";

		$qn = str_replace("href", "accesskey='n' title='quick note [ctrl n]' $class href", QuickNoteEdit::getQuickNoteLinkMultiple($title, $users));
		$link = $qn . $link;
		return $link;
    }


	function getButtons($result, $rev) {
		$t = $result['title'];
		$s = "<table cellspacing='0' cellpadding='0' style='xborder: 1px solid #eee; margin-top:-15px; margin-left: -23px; width:673px;'><tr><td style='vertical-align: middle; xborder: 1px solid #999;' class='rc_header'>";
		$u = new User();
		$u->setName($result['user']);
		$s .= "<a href='' onclick='return goback();' class='button button_arrow' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ></a>";
		$s .= RCPatrol::getQuickEdit($t, $result);
		$s .= RCPatrol::generateRollback($rev, $result['old']);
		$s .= RCPatrol::getMarkAsPatrolledLink($result['title'], $result['rcid'], $result['rchi'], $result['rclo'], $result['count'], true);	
		$s .= "</td></tr>";
		$s .= RCPatrol::getAdvancedTab($t, $result);
		$s .= RCPatrol::getOrderingTab();
		$s .= RCPatrol::getHelpTab();
		
		$s .= "</table>";
		$s .= "<div id='rc_subtabs'>
			<div class='tableft' id='rctab_advanced'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>Advanced</a>
				</div>
			</div>
			<div class='tableft' id='rctab_ordering'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>RC Ordering</a>
				</div>
			</div>
			<div class='tableft' id='rctab_help'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>Help</a>
				</div>
			</div>
		</div>";
		$s .= "<div id='rollback-status' style='background-color: #FFFF00;'></div>";
		$s .= "<div id='numrcusers' style='display:none;'>" . sizeof($result['users']) . "</div>";
		$s .= "<div id='numedits' style='display:none;'>". sizeof($result['count']) . "</div>";
		return $s;
	}
	
	function getAdvancedTab($t, $result){
		$tab = "<tr class='rc_submenu' id='rc_advanced'><td style='text-align:center; padding:10px;'>";
		$tab .= "<a href='{$t->getFullURL()}?action=history' target='new'>Page History</a> |";
		$tab .= " <a href='{$t->getFullURL()}?oldid={$result['old']}&diff={$result['new']}' target='new'>View Diff</a> |";
		$tab .= " <a href='{$t->getTalkPage()->getFullURL()}' target='new'>Discuss</a>";
		if ($t->userCan('move')) {
			$tab .= " | <a href='{$t->getFullURL()}?action=delete' target='new'>Delete</a> |";
			$mp = SpecialPage::getTitleFor("Movepage", $t);
			$tab .= " <a href='{$mp->getFullURL()}' target='new'>Rename</a> |";
			$tab .= " <a href='#'>Add to Spam</a>";
		}
		
		$tab .= "</td></tr>";
		return $tab;
	}
	
	function getOrderingTab(){
		global $wgRequest; 
		$reverse = $wgRequest->getVal('reverse', 0);
#echo $reverse; print_r($_GET);
		$tab = "<tr class='rc_submenu' id='rc_ordering'><td>
			<div id='controls' style='text-align:center'>
			<input type='radio' name='reverse' value='0' " . (!$reverse? "checked" : "") . " style='height: 10px;' onchange='changeReverse();'> Newest to Oldest
			<input type='radio' name='reverse' value='1' id='reverse' " . ($reverse? "checked" : "") . " style='height: 10px; margin-left:10px;' onchange='changeReverse();'> Oldest to Newest
			&nbsp; &nbsp; | &nbsp; &nbsp; Namespace: " .  HTMLnamespaceselector($namespace, '') .
			" </div></td></tr>";
		return $tab;
	}
	
	function getHelpTab(){
		$tab = "<tr class='rc_submenu' id='rc_help'><td>" . wfMsgWikiHtml('rcpatrolhelp_top') . wfMsg('rcpatrolhelp_bottom') . "</td></tr>";
		return $tab;
	}

	function getGlobalPatrolRankings() {
		global $wgMemc;
		$key = "rcpatrolglobalrankings";
		$val = $wgMemc->get($key);
		if ($val)
			return $val;
		$dbr = wfGetDB(DB_SLAVE); 
		$users = array();
		$res = $dbr->query ("select log_user, count(*) as C
            from logging where log_type='patrol' group by log_user having  C > 20 order by C desc");        
		$index = 0;
		while ($row = $dbr->fetchObject($res)) {
			$users[$index] = $row->log_user;
			$index++;
		}
		$wgMemc->set($key, $users, 24*3600);
	}

	function getQuickEditsToday() {
        global $wgUser;
		$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
        $dbr = wfGetDB(DB_SLAVE); 
        $count = $dbr->selectField('recentchanges',
            array('count(*)'),
            array('rc_user' => $wgUser->getID(), "rc_timestamp > '{$cutoff}'", "rc_comment like '%Quick edit%'")
            );
        return number_format($count, 0, ".", ", ");
    }

	function getUnPatrolledEdits() {
        $dbr = wfGetDB(DB_SLAVE); 
        $count = $dbr->selectField('recentchanges',
            array('count(*)'),
            array('rc_patrolled' => 0)
            );
        return number_format($count, 0, ".", ", ");
	}
	function getEditsPatrolled($alltime) {
		global $wgUser;
        $dbr = wfGetDB(DB_SLAVE); 
		$options = array('log_user' => $wgUser->getID(), 'log_type'=>'patrol');
		if (!$alltime) {
			// just today
			$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
			$options[] = "log_timestamp > '{$cutoff}'";
		}
		$count = $dbr->selectField('logging',
			array('count(*)'),
			$options
			);
		return number_format($count, 0, ".", ", ");
	}

	function getActiveWidget() {
		global $wgUser;
	
		$html = "<h3>" . wfMsg('rcpatrolstats_currentstats') . "</h3><table class='rcleaders'>";
		

		$users = RCPatrol::getGlobalPatrolRankings();
		$myrank = wfMsg('newpatroller');
		$myid = $wgUser->getID();
		foreach ($users as $rank=>$id) {
			if ($id == $myid) {
				$myrank = ($rank + 1);	
				break;
			}
		}
		$unp = RCPatrol::getUnPatrolledEdits();
		$today = RCPatrol::getEditsPatrolled(false);
		$quick =  RCPatrol::getQuickEditsToday();
		$patrolled = RCPatrol::getEditsPatrolled(true);

		$html .= "<tr class='dashed'><td>" . wfMsg('rcpatrolstats_numunpatrolled') . "</td><td class='patrolcount'>{$unp}</tr>";
		$html .= "<tr><td>" . wfMsg('rcpatrolstats_editspatrolledtoday') . "</td><td class='patrolcount' id='patrolledcount'>{$today}</td></tr>";
		$html .= "<tr class='dashed'><td>" . wfMsg('rcpatrolstats_quickeditstoday') . "</td><td class='patrolcount' id='quickedits'>{$quick}</td></tr>";
		$html .= "<tr><td>" . wfMsg('rcpatrolstats_alltimepatrolled'). "</td><td class='patrolcount' id='alltime'>{$patrolled}</td></tr>";
		$html .= "<tr class='dashed'><td>" . wfMsg('rcpatrolstats_alltimerank'). "</td><td class='patrolcount'>{$myrank}</td></tr>";
		$html .= "</table><center>" . wfMsg('rcpatrolstats_activeupdate') . "</center>";	
		return $html;
	}
	
	function setActiveWidget() {
		global $wgUser;
		$html = "<div id='rcactivewidget'>" . $this->getActiveWidget() . "</div>";
        $skin = $wgUser->getSkin();
        $skin->addWidget($html);
	}

	function getLeaderboard() {
		global $wgMemc, $wgUser;		

		$key = "rcwidgetactive_leader_board";
		$val = $wgMemc->get($key);
		if ($val)
			return $val;
		$cutoff = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
		$dbr = wfGetDB(DB_SLAVE); 
		$res = $dbr->query ("select log_user, count(*) as C
			from logging where log_type='patrol' and log_timestamp > '{$cutoff}' group by log_user order by C desc LIMIT 6");
		$html = "<h3>" .wfMsg('rcpatrolstats_leaderboard_title') . "</h3><table class='rcleaders rcleaderboard'>";
		while ($row = $dbr->fetchObject($res)) {
			$u = User::newFromID($row->log_user);

            $img = Avatar::getPicture($u->getName(), true);
            if ($img == '') {
            	$img = Avatar::getDefaultPicture();
            }
			$c = number_format($row->C, 0, ".", ", ");
			$html .= "<tr><td class='rcleaderimg'>{$img}</td><td><a href='{$u->getUserPage()->getFullURL()}' target='new'>{$u->getName()}</a></td><td class='patrolcount'>{$c}</td></tr>";
		}
		$html .= "</table><center>" . wfMsg('rcpatrolstats_leaderboardupdate') . "</center>";	

		// cache for 10 minutes
		$wgMemc->set($key, $html, 600);

		return $html;
	}
	function setLeaderboard() {
		global $wgUser;
		$html = "<div id='rcleaderboard'>" . RCPatrol::getLeaderboard() . "</div>";
		$skin = $wgUser->getSkin();
		$skin->addWidget($html);
	}	
	function execute() {
		global $wgServer, $wgRequest, $wgOut, $wgUser;	

		if (!$wgUser->isAllowed( 'patrol' )) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$this->setActiveWidget();
		$this->setLeaderboard();
		$wgOut->addHTML("<script type='text/javascript' src='/extensions/wikihow/rcpatrol.js'></script>");
		$wgOut->addHTML('<link rel="stylesheet" href="/extensions/wikihow/rcpatrol.css" />');
		$wgOut->addHTML(QuickNoteEdit::displayQuickEdit() . QuickNoteEdit::displayQuickNote());
		//$wgOut->addHTML("<div id='controls'>Namespace: " .  HTMLnamespaceselector($namespace, '') . " <input type='checkbox' name='reverse' id='reverse' style='height: 10px;' onchange='changeReverse();'> Reverse Order</div>");
		$result = RCPatrol::getNextArticleToPatrol();
		if ($result) {
			$wgOut->addHTML("<div id='bodycontents2'>");
			//$wgOut->addHTML("rchi: " . $result['rchi']);
			$d = new DifferenceEngine($result['title'], $result['old'], $result['new']);
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev));
			$d->showDiffPage();
			$wgOut->addHTML("</div>");
		} else {
			$wgOut->addWikiMsg( 'markedaspatrolledtext' );
		}
	}

	function skipArticle($id) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
        // skip the article for now
        $cookiename = "Rcskip";
        $cookie = $id;
       	if (isset($_COOKIE[$wgCookiePrefix.$cookiename])) 
        	$cookie .= "," . $_COOKIE[$wgCookiePrefix.$cookiename];
        $exp = time() + 2*60*60; // expire after 2 hours
        setcookie( $wgCookiePrefix.$cookiename, $cookie, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		$_COOKIE[$wgCookiePrefix.$cookiename] = $cookie;
	}
}


class RCPatrolGuts extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RCPatrolGuts' );
    }

	function execute($par) {
		global $wgRequest, $wgOut;

		$t	= Title::newFromText($wgRequest->getVal('target'));	

		$wgOut->setArticleBodyOnly(true);
		if ($wgRequest->getVal('action') == 'permalink') {		
			$result = array();
			$result['title'] = $t;
			$result['rchi'] = $wgRequest->getVal('rchi');
			$result['rclo'] = $wgRequest->getVal('rclow');
			$result['rcid'] = $wgRequest->getVal('rcid');
			$result['rc_cur_id'] = $t->getArticleID();
			$result = RCPatrol::getListofEditors($result);
			$wgOut->addHTML("<div id='articletitle' style='display:none;'>{$t->getFullText()}</div>");
            $d = new DifferenceEngine($t, $wgRequest->getVal('oldid'), $wgRequest->getVal('diff'));
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev));
            $d->showDiffPage();
			return;
		}
		$a	= new Article($t);
		if (!$wgRequest->getVal('grabnext')) {
			if (!$wgRequest->getVal('skip') && $wgRequest->getVal('action') == 'markpatrolled') {
				$a->markPatrolled();
			} else if ($wgRequest->getVal('skip')) {
				// skip the article for now
				RCPatrol::skipArticle($t->getArticleID());
			}
		}
		$wgOut->clearHTML();
		$wgOut->redirect('');
		$result = RCPatrol::getNextArticleToPatrol($wgRequest->getVal('rcid'));
        if ($result) {
			$t = $result['title'];
			#$wgOut->addHTML("<b>Article id: {$t->getArticleID()}</b>");
			$wgOut->addHTML("<div id='bodycontents2'>");
			//$wgOut->addHTML("rchi: " . $result['rchi']);
			$wgOut->addHTML("<div id='articletitle' style='display:none;'>{$t->getFullText()}</div>");
            $d = new DifferenceEngine($result['title'], $result['old'], $result['new']);
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev));
            $d->showDiffPage();
			$wgOut->addHTML("</div>");
        } else {
			#$wgOut->setStatusCode(204);
			$wgOut->addWikiMsg( 'markedaspatrolledtext' );
		}
	}
}

class RCActiveWidget extends SpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RCActiveWidget' );
    }

  	function execute($par) {
        global $wgOut, $wgRequest;

		$wgOut->setArticleBodyOnly(true);
		if ($wgRequest->getVal('leaderboard')) {
			$html = RCPatrol::getLeaderboard();	
		} else {
			$html = RCPatrol::getActiveWidget();
		}
		$wgOut->addHTML($html);
		return;	
	}
}



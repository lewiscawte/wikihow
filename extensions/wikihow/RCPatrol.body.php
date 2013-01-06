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
		while ($row = $dbr->fetchObject($res)) {
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
		while ($result = self::getNextArticleToPatrolInner($rcid)) {
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
		$show_namespace	 = $wgRequest->getVal('namespace');
		$invert			 = $wgRequest->getVal('invert');
		$reverse			= $wgRequest->getVal('reverse');
		$featured		   = $wgRequest->getVal('featured');
		$title				= $wgRequest->getVal('target');
		$skiptitle			= $wgRequest->getVal('skiptitle');
		$rc_user_filter		= trim(urldecode($wgRequest->getVal('rc_user_filter')));

		$t = null;
		if ($title) 
			$t = Title::newFromText($title);
		$skip = null;
		if ($skiptitle)
			$skip = Title::newFromText($skiptitle);

		$cat = "";
		if ($wgUser->getID() != 0 && $wgUser->getOption('contentfilter') != 0) {
			$filter = $wgUser->getOption('contentfilter');
			if ($filter == 1) $cat = " AND page_catinfo & " . CAT_TEEN . " = " . CAT_TEEN;
			if ($filter == 2) $cat = " AND page_catinfo & " . CAT_TEEN . " = 0 ";
		}

		$dbr =& wfGetDB( DB_MASTER );
		$sql = "SELECT rc_id, rc_cur_id, rc_moved_to_ns, 
			rc_moved_to_title, rc_new, rc_namespace, rc_title, rc_last_oldid, rc_this_oldid FROM recentchanges " .
			" LEFT OUTER JOIN page on page_title = rc_title and page_namespace = rc_namespace " .
			" WHERE ";

#echo "<b>REV: $reverse</b>";
		if (!$wgRequest->getVal('ignore_rcid') && $rcid)
			$sql .= " rc_id " . ($reverse == 1 ? " > " : " < ")  . " $rcid and ";

		// if we filter by user we show both patrolled and non-patrolled edits
		if ($rc_user_filter) {
			$sql .= " rc_user_text = " . $dbr->addQuotes($rc_user_filter);
			if ($rcid) 
				$sql .= " AND rc_id < " . $rcid;
		} else  {
			$sql .= " rc_patrolled = 0 ";
		}

		// can't patrol your own edits
		$sql .= "  AND rc_user_text != " . $dbr->addQuotes($wgUser->getName());

		// only featured?
		if ($featured)
			$sql .= " AND page_is_featured = 1 ";
		
		#$show_namespace = "0";
		if ($show_namespace != "")  {
			$sql .= " AND rc_namespace " . ($invert ? '!=' : '=') . $show_namespace ;
		} else  {
			// ignore video
			$sql .= " AND rc_namespace NOT IN ( " . NS_VIDEO .") ";
		}

		// log entries have namespace = -1, we don't want to show those, hide bots too
		$sql .= " AND rc_namespace >= 0 AND rc_bot = 0 ";
		$sql .= $cat; 

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
		$sql .= "$skipids ORDER by page_is_featured DESC, rc_id " . ($reverse == 1 ? " ASC " : " DESC ") . "  LIMIT 1";

#echo $sql; 
		//execute the query
		$res = $dbr->query($sql);
		
		$result = array();
		if ( $row = $dbr->fetchObject( $res ) ) {
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
			if ($row->rc_moved_to_title != "")
				$t = Title::makeTitle($row->rc_moved_to_ns, $row->rc_moved_to_title);
		
			$result['rc_cur_id'] = $row->rc_cur_id;

			if ($rc_user_filter) {
				$result['rchi'] = $result['rclo'] = $row->rc_id;
				$result['new'] 		= $dbr->selectField('recentchanges', array('rc_this_oldid'), array('rc_id' => $row->rc_id));
			} else {
				// always compare to current version
				$result['new'] 		= $dbr->selectField('revision', array('max(rev_id)'), array('rev_page' => $row->rc_cur_id));
				$result['rchi']   	= $dbr->selectField('recentchanges', array('rc_id'), array('rc_this_oldid' => $result['new']));
				$result['rclo']   	= $dbr->selectField('recentchanges', array('min(rc_id)'), array('rc_patrolled'=>0,"rc_cur_id"=>$row->rc_cur_id));

				// do we have a reverted edit caught between these 2? 
				// if so, only show the reversion, because otherwise you get the reversion trapped in the middle
				// and it shows a weird diff page.
				$hi = isset($result['rchi']) ? $result['rchi'] : $row->rc_id;

				if ($hi) {
					$reverted_id = $dbr->selectField('recentchanges', array('min(rc_id)'), 
						array('rc_comment like "Reverted%"', "rc_id < $hi" , "rc_id >= {$result['rclo']}", "rc_cur_id"=>$row->rc_cur_id)
						); 
					if ($reverted_id ) {
						$result['rchi'] = $reverted_id;
						$result['new'] 		= $dbr->selectField('recentchanges', array('rc_this_oldid'), array('rc_id' => $reverted_id));
						$row->rc_id = $result['rchi'];
					}
				} else {
					$email = new MailAddress("alert@wikihow.com");
					$subject = "Could not find hi variable " . date("r");
					$body = print_r($_SERVER, true) . "\n\n" . $sql; 
					UserMailer::send($email, $email, $subject, $body);
				}


				if (!$result['rclo'])
					$result['rclo'] = $row->rc_id;
				if (!$result['rchi'])
					$result['rchi'] = $row->rc_id;
			
				# is the last patrolled edit a rollback? if so, show the diff starting at that edit
				# makes it more clear when someone has reverted vandalism
				$result['vandal'] = 0;
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
					$result['vandal'] = 1;
				}
			}
			$result['user']   	= $dbr->selectField('recentchanges', array('rc_user_text'), array('rc_this_oldid' => $result['new']));
			$result['old'] 		= $dbr->selectField('recentchanges', array('rc_last_oldid'), array('rc_id' => $result['rclo']));
			// if the article is new set 'old' to -1 so DifferenceEngine doesn't just show last edit
		   	if ($dbr->selectField('recentchanges', array('rc_new'), array('rc_id'=>$result['rclo']))) {
				$result['old'] = -1;
			}
			$result['title'] 	= $t;
			$result['rcid']		= $row->rc_id;
			$result['count']	= $dbr->selectField('recentchanges', 
									array('count(*)'), 
									array("rc_id<=" . $result['rchi'], 
										"rc_id>=" . $result['rclo'], "rc_patrolled=0", "rc_cur_id" => $row->rc_cur_id));
			$result = self::getListofEditors($result);
#print_r($result); exit;
			return $result;
		}
		return null;
	}

	function getMarkAsPatrolledLink ($title, $rcid, $hi, $low, $count, $setonload, $new, $old, $vandal) {
		global $wgRequest;
		$sns 	= $wgRequest->getVal('show_namespace');
		$inv	= $wgRequest->getVal('invert');
   		$fea	= $wgRequest->getVal('featured');
		$rev 	= $wgRequest->getVal('reverse');

		$url = "/Special:RCPatrolGuts?target=" . urlencode($title->getFullText()) 
			. "&action=markpatrolled&rcid={$rcid}"
			. "&invert=$inv&reverse=$rev&featured=$fea&show_namespace=$sns"
			. "&rchi={$hi}&rclow={$low}&new={$new}&old={$old}&vandal={$vandal}"
		;
		$base = "mp('{$url}";

		$class1 = "class='button button150' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
		$class2 = "class='button white_button' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
			$link	=  " <input type='button' $class2 id='skippatrolurl' onclick=\"return skip();\" accesskey='s' title='" . wfMsg('rcpatrol_skip_title') . 
			"' value='" . wfMsg('rcpatrol_skip_button') . "'/>";
		$link	.=  "<input type='button' $class1 id='markpatrolurl' onclick=\"return mp();\" accesskey='p' title='" . wfMsg('rcpatrol_patrolled_title') . 
			"' value='" . wfMsg('rcpatrol_patrolled_button') . "'/>";
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
			 . "<input id='permalink' type='hidden' value='" . str_replace("&action=markpatrolled", "&action=permalink", $url)  . "'/>";
		return $link;
	}

	function generateRollback( $rev, $oldid = 0) {
	   global $wgUser, $wgRequest, $wgTitle;
	   $title = $rev->getTitle();
				
	   $extraRollback = $wgRequest->getBool( 'bot' ) ? '&bot=1' : '';
	   $extraRollback .= '&token=' . urlencode(
	   $wgUser->editToken( array( $title->getPrefixedText(), $rev->getUserText() ) ) );
			
		if ($oldid) {
			$url = $title->getFullURL() . "?action=rollback&old={$oldid}&from=" . urlencode( $rev->getUserText() ). $extraRollback . "&useajax=true";
		} else {
			$url = $title->getFullURL() . "?action=rollback&from=" . urlencode( $rev->getUserText() ). $extraRollback . "&useajax=true";
		}

		// loop, check it 5 times because I think this changes
		$o = $_SESSION['wsEditToken']; 
		for ($i = 0; $i < 5; $i++) {
			$x = $_SESSION['wsEditToken'];
			if ($x != $o) {
				// well here's our problem
				$url .= "&bad1={$o}&bad2={$x}";
				break;
			}
		}

		// debug all of this crap for bug 461
		global $wgCookiePrefix;
		$url .= "&timestamp=" . wfTimestampNow() . "&wsEditToken=" . $_SESSION['wsEditToken'] . "&sidx=" . session_id(); 
		$url .= '&wsEditToken_set=' . $_SESSION['wsEditToken_set'];
		$url .= '&hostname=' .  $_SESSION['wsEditToken_hostname'];
		$cookiesid = $_COOKIE[$wgCookiePrefix.'_session'];
		$url .= "&cookiesid=" . $cookiesid; 
		$url .= "&s_started=" . $_SESSION['started'];
		$url .= '&wsUserName=' .  $_SESSION['wsUserName'];
		$url .= '&cookiesUserName=' .  $_COOKIE[$wgCookiePrefix.'UserName'];
		$url .= '&ip=' . wfGetIP();
		$url .= "&wgUser=" . urlencode($wgUser->getName());

		$class = "class='button white_button_100' style='float: left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ";
			
		$s  = "<script type='text/javascript' src='".wfGetPad('/extensions/min/f/extensions/wikihow/rollback.js')."'></script>
			<script type='text/javascript'>
				var gRollbackurl = \"{$url}\";
			   var msg_rollback_complete = \"" . htmlspecialchars(wfMsg('rollback_complete')) . "\";
			   var msg_rollback_fail = \"" . htmlspecialchars(wfMsg('rollback_fail')) . "\";
			   var msg_rollback_inprogress = \"" . htmlspecialchars(wfMsg('rollback_inprogress')) . "\";
			   var msg_rollback_confirm= \"" . htmlspecialchars(wfMsg('rollback_confirm')) . "\";
			</script>
				<a id='rb_button' $class href='' onclick='return rollback();' accesskey='r' title='" . wfMsg('rcpatrol_rollback_title') . "'>" . wfMsg('rcpatrol_rollback_button') . "</a>
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
		$link =  "<a id='qe_button' title='" . wfMsg("rcpatrol_quick_edit_title") . "' accesskey='e' href='' $class onclick=\"return initPopupEdit('".$editURL."') ;\">" . 
			htmlspecialchars( wfMsg( 'rcpatrol_quick_edit_button' ) ) . "</a> ";

		$qn = str_replace("href", "accesskey='n' title='" . wfMsg("rcpatrol_quick_note_title") . "' $class href", QuickNoteEdit::getQuickNoteLinkMultiple($title, $users));
		$link = $qn . $link;
		return $link;
	}


	function getButtons($result, $rev, $rcTest = null) {
		wfLoadExtensionMessages('RCPatrol');
		$t = $result['title'];
		$s = "<table cellspacing='0' cellpadding='0' style='xborder: 1px solid #eee; margin-top:-15px; margin-left: -23px; width:673px;'><tr><td style='vertical-align: middle; xborder: 1px solid #999;' class='rc_header'>";
		$u = new User();
		$u->setName($result['user']);
		$s .= "<a id='gb_button' href='' onclick='return goback();' class='button button_arrow' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' ></a>";
		$s .= RCPatrol::getQuickEdit($t, $result);
		$s .= RCPatrol::generateRollback($rev, $result['old']);
		$s .= RCPatrol::getMarkAsPatrolledLink($result['title'], $result['rcid'], $result['rchi'], $result['rclo'], $result['count'], true, $result['new'], $result['old'], $result['vandal']);	
		$s .= RCTestStub::getThumbsUpButton($result, $rcTest);
		$s .= "</td></tr>";
		$s .= RCPatrol::getAdvancedTab($t, $result);
		$s .= RCPatrol::getOrderingTab();
		$s .= RCPatrol::getUserTab();
		$s .= RCPatrol::getHelpTab();
		$s .= "</table>";
		$s .= "<div id='rc_subtabs'>
			<div class='tableft' id='rctab_advanced'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>" . wfMsg('rcpatrol_advanced_tab') . "</a>
				</div>
			</div>
			<div class='tableft' id='rctab_ordering'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>" . wfMsg('rcpatrol_ordering_tab') . "</a>
				</div>
			</div>
			<div class='tableft' id='rctab_user'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>" . wfMsg('rcpatrol_user_tab') . "</a>
				</div>
			</div>
			<div class='tableft' id='rctab_help'>
				<div class='tabright'>
					<a href='#'><div class='arrow'></div>" . wfMsg('rcpatrol_help_tab') . "</a>
				</div>
			</div>
		</div>";
		$s .= "<div id='rollback-status' style='background-color: #FFFF00;'></div>";
		$s .= "<div id='thumbsup-status' style='background-color: #FFA;display:none;padding:2px;'></div>";
		$s .= "<div id='numrcusers' style='display:none;'>" . sizeof($result['users']) . "</div>";
		$s .= "<div id='numedits' style='display:none;'>". sizeof($result['count']) . "</div>";
		return $s;
	}
	
	function getAdvancedTab($t, $result){
		$tab = "<tr class='rc_submenu' id='rc_advanced'><td style='text-align:center; padding:10px;'>";
		$tab .= "<a href='{$t->getFullURL()}?action=history' target='new'>" . wfMsg('rcpatrol_page_history') . "</a> |";
		if ($result['old'] > 0) {
			$tab .= " <a href='{$t->getFullURL()}?oldid={$result['old']}&diff={$result['new']}' target='new'>" . wfMsg('rcpatrol_view_diff') . "</a> |";
		}
		$tab .= " <a href='{$t->getTalkPage()->getFullURL()}' target='new'>" . wfMsg('rcpatrol_discuss') . "</a>";
		if ($t->userCan('move')) {
			$tab .= " | <a href='{$t->getFullURL()}?action=delete' target='new'>" . wfMsg('rcpatrol_delete') . "</a> |";
			$mp = SpecialPage::getTitleFor("Movepage", $t);
			$tab .= " <a href='{$mp->getFullURL()}' target='new'>" . wfMsg('rcpatrol_rename') . "</a> ";
			#$tab .= " <a href='#'>" . wfMsg('rcpatrol_spam') . "</a>";
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
			<input type='radio' name='reverse' value='0' " . (!$reverse? "checked" : "") . " style='height: 10px;' onchange='changeReverse();'> " . wfMsg('rcpatrol_newest_oldest') . " 
			<input type='radio' name='reverse' value='1' id='reverse' " . ($reverse? "checked" : "") . " style='height: 10px; margin-left:10px;' onchange='changeReverse();'> " . 
			wfMsg('rcpatrol_oldest_newest') . "
			&nbsp; &nbsp; | &nbsp; &nbsp; " . wfMsg('rcpatrol_namespace') . ": " .  HTMLnamespaceselector($namespace, '') .
			" </div></td></tr>";
		return $tab;
	}

	function getUserTab() {
		$tab = "<tr class='rc_submenu' id='rc_user'><td>
			<div id='controls' style='text-align:center'>
				" . wfMsg('rcpatrol_username') . ": <input type='text' name='rc_user_filter' id='rc_user_filter' size='30' onchange='changeUserFilter();'/>	
				<input type='button' value='" . wfMsg('rcpatrol_go') . "' onclick='changeUser(true);'/>
				|
				<a href='#' onclick='changeUser(false);'>" . wfMsg('rcpatrol_off') . "</a>
			</div></td></tr>";
		return $tab;
	}
	
	function getHelpTab(){
		global $wgLanguageCode;

		if ($wgLanguageCode == 'en') {
			$helpTop = wfMsg('rcpatrolhelp_top');
		} else {
			$helpTop = wfMsgWikiHtml('rcpatrolhelp_top');
		}

		$tab = "<tr class='rc_submenu' id='rc_help'><td>" . $helpTop . wfMsg('rcpatrolhelp_bottom') . "</td></tr>";
		return $tab;
	}

	function getGlobalPatrolRankings() {
		global $wgMemc;
		$key = wfMemcKey("rcpatrolglobalrankings");
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

	function setActiveWidget() {
		$standings = new RCPatrolStandingsIndividual();
		$standings->addStatsWidget();
		$standings = new QuickEditStandingsIndividual();
		$standings->addStatsWidget();
	}

	function setLeaderboard() {
		$standings = new QuickEditStandingsGroup();
		$standings->addStandingsWidget();
	}
	
	function execute() {
		global $wgServer, $wgRequest, $wgOut, $wgUser, $wgLanguageCode;	
		wfLoadExtensionMessages('RCPatrol');

		if (!$wgUser->isAllowed( 'patrol' )) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$this->setActiveWidget();
		// INTL: Leaderboard is across the user database so we'll just enable for English at the moment
		if ($wgLanguageCode == 'en') {
			$this->setLeaderboard();
		}
		$wgOut->addHTML("<script type='text/javascript' src='/extensions/wikihow/rcpatrol.js'></script>");
		$wgOut->addHTML('<link rel="stylesheet" href="/extensions/wikihow/rcpatrol.css" />');
		$wgOut->addHTML(QuickNoteEdit::displayQuickEdit() . QuickNoteEdit::displayQuickNote());
		//$wgOut->addHTML("<div id='controls'>Namespace: " .  HTMLnamespaceselector($namespace, '') . " <input type='checkbox' name='reverse' id='reverse' style='height: 10px;' onchange='changeReverse();'> Reverse Order</div>");
		$result = RCPatrol::getNextArticleToPatrol();
		if ($result) {
			$wgOut->addHtml("<div id='rct_results'></div>");
			$wgOut->addHTML("<div id='bodycontents2'>");
			//$wgOut->addHTML("rchi: " . $result['rchi']);

			// Initialize the RCTest object. This is use to inject 
			// tests into the RC Patrol queue.
			$rcTest = null;
			$testHtml = "";
			if (class_exists('RCtest')) {
				$rcTest = new RCTest();
				$testHtml = $rcTest->getTestHtml();
			}

			$d = RCTestStub::getDifferenceEngine($result, $rcTest);
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev, $rcTest));
			$d->showDiffPage();
			$wgOut->addHTML($testHtml);
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

	function getUnpatrolledCount() {
		$dbr = wfGetDB(DB_SLAVE); 
		$count = $dbr->selectField('recentchanges', array('count(*)'), array('rc_patrolled'=>0));
		$count = number_format($count, 0, ".", ",");
		$count .= wfMsg('rcpatrol_helplink');
		return $count;
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
			$result['old'] = $wgRequest->getVal('old');
			$result['new'] = $wgRequest->getVal('new');
			$result['vandal'] = $wgRequest->getVal('vandal');
			$result['rc_cur_id'] = $t->getArticleID();
			$result = RCPatrol::getListofEditors($result);
			$wgOut->addHTML("<div id='articletitle' style='display:none;'>{$t->getFullText()}</div>");
			$d = new DifferenceEngine($t, $wgRequest->getVal('oldid'), $wgRequest->getVal('diff'));
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev));
			$d->showDiffPage();
			$wgOut->disable();
			$response['html'] = $wgOut->getHTML();
			print_r(json_encode($response));
			return;
		}
		$a	= new Article($t);
		if (!$wgRequest->getVal('grabnext')) {
			if (class_exists('RCTest') && $wgRequest->getVal('rctest')) {
				// Don't do anything if it's a test
			} else if (!$wgRequest->getVal('skip') && $wgRequest->getVal('action') == 'markpatrolled') {
				$a->markPatrolled();
			} else if ($wgRequest->getVal('skip')) {
				// skip the article for now
				RCPatrol::skipArticle($t->getArticleID());
			}
		}

		$wgOut->clearHTML();
		$wgOut->redirect('');
		$result = RCPatrol::getNextArticleToPatrol($wgRequest->getVal('rcid'));
		$response = array();
		if ($result) {
			$rcTest = null;
			$testHtml = "";
			if (class_exists('RCtest')) {
				$rcTest = new RCTest();
				$testHtml = $rcTest->getTestHtml();
			}
			$t = $result['title'];
			#$wgOut->addHTML("<b>Article id: {$t->getArticleID()}</b>");
			$wgOut->addHTML("<div id='bodycontents2'>");
			//$wgOut->addHTML("rchi: " . $result['rchi']);
			$titleText = RCTestStub::getTitleText($result, $rcTest);
			$wgOut->addHTML("<div id='articletitle' style='display:none;'>$titleText</div>");

			// Initialize the RCTest object. This is use to inject 
			// tests into the RC Patrol queue.

			$d = RCTestStub::getDifferenceEngine($result, $rcTest);
			$d->loadRevisionData();
			$wgOut->addHTML(RCPatrol::getButtons($result, $d->mNewRev, $rcTest));
			$d->showDiffPage();
			$wgOut->addHtml($testHtml);

			$wgOut->addHTML("</div>");
			$response['unpatrolled'] = self::getUnpatrolledCount();
		} else {
			#$wgOut->setStatusCode(204);
			$wgOut->addWikiMsg( 'markedaspatrolledtext' );
			$response['unpatrolled'] = self::getUnpatrolledCount();
		}
		$wgOut->disable();
		header('Vary: Cookie' );
		$response['html'] = $wgOut->getHTML();
		print_r(json_encode($response));
		return;
	}
}


class Points extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Points' );
	}

	function getRandomEdit($t = null) {
		// get a random page
		if (!$t) {
			$rp = new RandomPage(); 
			$t = $rp->getRandomTitle();
		}
		
		// pick a random one
		$dbr = wfGetDB(DB_SLAVE);
		$revid = $dbr->	selectField('revision', array('rev_id'), array('rev_page'=>$t->getArticleID()), 
				"RandomEdit::getRandomEdit", array("ORDER BY" => "rand()", "LIMIT"=>1));
		$r = Revision::newFromID($revid);
		return $r;
	}

	function getDiffToMeasure($r) {

		$dbr = wfGetDB(DB_SLAVE);
		$result = array(); 
		// get the low, we compare this against the last edit
		// which was made by a different user
		$revlo = $dbr->selectField('revision', 'rev_id', 
			array('rev_page'=>$r->mTitle->getArticleID(), 
					'rev_user_text != ' . $dbr->addQuotes($r->mUserText),
				'rev_id < ' . $r->mId
				),
			"RandomEdit::getDiffToMeasure",
			array("ORDER BY"=>"rev_id desc", "LIMIT"=>1)
			);

		
		// get the highest edit in this sequence of edits by this user
		$not_hi_row  = $dbr->selectRow('revision', array('rev_id', 'rev_comment', 'rev_user_text'), 
			array('rev_page'=>$r->mTitle->getArticleID(), 
					'rev_user_text != ' . $dbr->addQuotes($r->mUserText),
				'rev_id > ' . $r->mId
				)
			);
		$revhi = null;
		if (!$not_hi_row) {
			$revhi = $r->mId;
		} else {
			$revhi = $dbr->selectField('revision', 'rev_id', 
				array('rev_page'=>$r->mTitle->getArticleID(), 'rev_id <  ' . $not_hi_row->rev_id),
				"RandomEdit::getDiffToMeasure",
				array("ORDER BY"=>"rev_id desc", "LIMIT"=>1)
				);
			$result['nextcomment'] = $not_hi_row->rev_comment;
			$result['nextuser'] = $not_hi_row->rev_user_text;
		}

		$hi = Revision::newFromID($revhi);
		$hitext = $hi->getText();

		$lotext = "";
		if ($revlo) {
			$lo = Revision::newFromID($revlo);
			$lotext = $lo->getText();
		}

		if ($lotext == "") {
			$result['newpage']= 1;
		} else {
			$result['newpage']= 0;
		}
		$opts = array('rev_page'=>$r->mTitle->getArticleID(), 'rev_id <= ' . $revhi);
		if ($revlo) {
			$opts[] = 'rev_id >  ' . $revlo;
		}
		$result['numedits'] = $dbr->selectField('revision', 'count(*)', $opts);
		$result['diff'] =  wfDiff($lotext, $hitext);
		$result['revhi'] = $hi;
		$result['revlo'] = $lo;
		return $result;
	}

	function getPoints($r, $d, $de, $showdetails = false) {
		require_once("extensions/wikihow/WikiHow.php");

		global $wgOut;
		$points = 0; 

		$oldText = "";
		if ($d['revlo']) {
			$oldText = $d['revlo']->mText;
		}
		$newText = $d['revhi']->mText;

		$flatOldText = preg_replace("@[^a-zA-z]@", "", WikiHow::textify($oldText));

		// get the points based on number of new / changed words
		$diffhtml = $de->generateDiffBody( $d['revlo']->mText, $d['revhi']->mText);
		$addedwords = 0;
		preg_match_all('@<span class="diffchange diffchange-inline">[^>]*</span>@m', $diffhtml, $matches);
		foreach ($matches[0] as $m) {
			$m = WikiHow::textify($m);
			preg_match_all("@\b\w+\b@", $m, $words);
			$addedwords += sizeof($words[0]);
		}
		preg_match_all('@<td class="diff-addedline">(.|\n)*</td>@Um', $diffhtml, $matches);
		#echo $diffhtml; print_r($matches); exit;
		foreach ($matches[0] as $m) {
			if (preg_match("@diffchange-inline@", $m)) {
				// already accounted for in change-inline
				continue;
			}
			$m = WikiHow::textify($m);
			
			// account for changes in formatting and punctuation 
			// by flattening out the change piece of text and comparing to the 
			// flattened old version of the text
			$flatM = preg_replace("@[^a-zA-z]@", "", $m); 
			if (!empty($flatM) && strpos($flatOldText, $flatM) !== false) {
				continue;
			}
			preg_match_all("@\b\w+\b@", $m, $words);
			$addedwords += sizeof($words[0]);
		}

		if ($showdetails) $wgOut->addHTML("<h3>Points for edit (10 max):</h3><ul>");
		if (preg_match("@Reverted@", $r->mComment)) {
			if ($showdetails) $wgOut->addHTML("<li>No points : reverted edit.</li></ul><hr/>");
			return 0;
		}
		if (preg_match("@Reverted edits by.*" . $d['revhi']->mUserText . "@", $d['nextcomment'])) {
			if ($showdetails) $wgOut->addHTML("<li>No points: This edit was reverted by {$d['nextuser']}\n</li></ul><hr/>");
			return 0;
		}

		$wordpoints = min(floor($addedwords / 100), 5);
		if ($showdetails) $wgOut->addHTML("<li>Approx # of new words: " . $addedwords . ": $wordpoints points (1 point per 100 words, max 5)</li>");  
		$points += $wordpoints;

		// new images
		$newimagepoints = array();
		preg_match_all("@\[\[Image:[^\]|\|]*@", $newText, $images);
		$newimages = $newimagepoints = 0;
		foreach ($images[0] as $i) {
			if (strpos($oldText, $i) === false) {
				$newimagepoints++;
				$newimages++;
			}
		}
		$newimagepoints = min($newimagepoints, 2);
		$points += $newimagepoints;
		if ($showdetails) $wgOut->addHTML("<li>Number of new images: " . $newimages . ": $newimagepoints points (1 point per image, max 2)</li>");  

		// new page points
		if ($d['newpage']) {
			if ($showdetails) $wgOut->addHTML("<li>New page: 1 point</li>");
			$points += 1;
		}


		// template points
		preg_match_all("@\{\{[^\}]*\}\}@", $newText, $templates);
		foreach ($templates[0] as $t) {
			if (strpos($oldText, $t) === false && $t != "{{reflist}}") {
				if ($showdetails) $wgOut->addHTML("<li>Template added: 1 point</li>");
				$points++;
				break;
			}
		}

		// category added points
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $newText, $cats);
		foreach ($cats[0] as $c) {
			if (strpos($oldText, $c) === false) {
				if ($showdetails) $wgOut->addHTML("<li>Category added: 1 point</li>");
				$points++;
				break;
			}
		}
			
		$points = min($points, 10);
		if ($showdetails) $wgOut->addHTML("</ul>");
		if ($showdetails) $wgOut->addHTML("<b>Total points: {$points}</b><hr/>");
		
		return $points;
	}

	// group the edits of the page together by user
	function getEditGroups($title) {
		$dbr = wfGetDB(DB_MASTER);
		$res = $dbr->select('revision', array('rev_id', 'rev_user_text', 'rev_timestamp', 'rev_user'), 
				array('rev_page'=>$title->getArticleID()));
		$results = array(); 
		$last_user = null;
		$x = null;
		while ($row = $dbr->fetchObject($res)) {
			if ($last_user == $row->rev_user_text) {
				$x['edits']++;
				$x['max_revid'] = $row->rev_id;
				$x['max_revtimestamp'] = $row->rev_timestamp;
			} else {
				if ($x) {
					$results[] = $x;
				}
				$x = array();
				$x['user_id'] = $row->rev_user;
				$x['user_text'] = $row->rev_user_text;
				$x['max_revid'] = $row->rev_id;
				$x['min_revid'] = $row->rev_id;
				$x['max_revtimestamp'] = $row->rev_timestamp;
				$x['edits'] = 1;
				$last_user = $row->rev_user_text;
			}
		}
		$results[] = $x;
		return array_reverse($results);
	}
	function execute($par)  {
		global $wgRequest, $wgOut, $wgUser; 
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
        
		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
            $wgOut->setArticleRelated( false );
            $wgOut->setRobotpolicy( 'noindex,nofollow' );
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }

		if ($target) {
			if (preg_match("@[^0-9]@", $target)) {
				$t = Title::newFromURL($target);
			} else {
				$r = Revision::newFromID($target);		
				if ($wgRequest->getVal('popup')) {
					$wgOut->setArticleBodyOnly(true);
					$wgOut->addHTML("<style type='text/css'>
						table.diff  {
							margin-left: auto; margin-right: auto;
						}
						table.diff td {
							max-width: 400px;
						}
						</style>");
				}
				$wgOut->addHTML("Revid: {$r->mId}\n");
				$d = self::getDiffToMeasure($r);
				$de = new DifferenceEngine($r->mTitle, $d['revlo']->mId, $d['revhi']->mId);
				self::getPoints($r, $d, $de, true);
				if (!$d['revlo']) {
					$de->mOldRev = null;
					$de->mOldid = null;
				}
				$de->showDiffPage();
				return;
			}
		} else {
			$rp = new RandomPage(); 
			$t = $rp->getRandomTitle();
		}
	
		$wgOut->addHTML("<script type='text/javascript'>
function getPoints(rev) {
	$('#img-box').load('/Special:Points/' + rev + '?popup=true', function() {
			$('#img-box').dialog({
			   width: 750,
			   modal: true,
				title: 'Points', 
			   show: 'slide',
				closeOnEscape: true,
				position: 'center'
			});
	});
	return false;
}
</script>
");
		// get the groups of edits
		$group = self::getEditGroups($t); 
		$wgOut->addHTML("Title: <a href='{$t->getFullURL()}?action=history' target='new'>{$t->getFullText()}</a><br/><br/>");
		$wgOut->addHTML("<table width='100%'><tr><td><u>User</u></td><td><u># Edits</u></td>");
		$wgOut->addHTML("<td><u>Date</u></td><td><u>Points</u></td></tr>");
		foreach ($group as $g) {
			$r = Revision::newFromID($g['max_revid']);
			$d = self::getDiffToMeasure($r);
			$de = new DifferenceEngine($r->mTitle, $d['revlo']->mId, $d['revhi']->mId);
			$points = self::getPoints($r, $d, $de);
			$date = date("Y-m-d", wfTimestamp(TS_UNIX, $g['max_revtimestamp']));
			$wgOut->addHTML("<tr><td>{$g['user_text']}</td><td>{$g['edits']}</td><td>{$date}</td>");
			$wgOut->addHTML("<td><a href='#' onclick='return getPoints({$g['max_revid']});'>{$points}</a></td></tr>");
		}
		$wgOut->addHTML("</table>");

	
			
	}
}

class RCTestStub {
	// Inject the test diff if it's RCPatrol is supposed to show a test
	public static function getDifferenceEngine($result, &$rcTest) {
		if (class_exists('RCTest')) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();
			}
		}
		return new DifferenceEngine($result['title'], $result['old'], $result['new']);
	}

	// Change the title to the test Title if RCPatrol is supposed to show a test
	public static function getTitleText($result, &$rcTest) {
		if (class_exists('RCTest')) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();
			}
		}
		$t = $result['title'];
		return $t->getFullText();
	}

	public static function getThumbsUpButton($result, &$rcTest) {
		if (class_exists('RCTest')) {
			if ($rcTest && $rcTest->isTestTime()) {
				$result = $rcTest->getResultParams();
			}
		}
		return ThumbsUp::getThumbsUpButton($result);
	}
}

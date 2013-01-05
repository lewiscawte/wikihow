<?php

class Newarticleboost extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Newarticleboost' );
	}

	/**
	 *
	 * Returns the total number of New Articles waiting to be
	 * NAB'd. Does not include Newbie Nab.
	 */
	function getNABCount(&$dbr){
		$old = wfTimestamp(TS_MW, time() - 60 * 60);
		$sql = "SELECT count(*) as C FROM newarticlepatrol, page left join templatelinks on tl_from = page_id and tl_title='Inuse' left join suggested_titles on page_title = st_title WHERE page_id=nap_page AND page_is_redirect=0 AND nap_patrolled=0 AND nap_timestamp < '$old' AND tl_title is NULL AND nap_newbie = 0";
		$res = $dbr->query($sql);

		$row = $dbr->fetchObject($res);

		return $row->C;
	}
	
	/**
	 *
	 * Returns the id of the last NAB.
	 */
	function getLastNAB(&$dbr){
		$old = wfTimestamp(TS_MW, time() - 60 * 60);
		$res = $dbr->select('newarticlepatrol', array('nap_user_ci', 'nap_timestamp_ci'), array('nap_patrolled' => 1, 'nap_newbie' => 0), 'Newarticleboost::getLastNAB', array("ORDER BY"=>"nap_timestamp_ci DESC", "LIMIT"=>1));

		$row = $dbr->fetchObject($res);
		$nabuser = array();
		$nabuser['id'] = $row->nap_user_ci;
		$nabuser['date'] = wfTimeAgo($row->nap_timestamp_ci);

		return $nabuser;
	}

	/**
	 *
	 * Gets the total number of articles patrolled by the given user after the given
	 * timestamp.
	 */
	function getUserNABCount(&$dbr, $userId, $starttimestamp){
		$row = $dbr->selectField('newarticlepatrol', 'count(*) as count', array('nap_patrolled' => 1, 'nap_user_ci' => $userId, 'nap_timestamp_ci > "' . $starttimestamp . '"'));
		return $row;
	}
	
	
	function isNABbed(&$dbr, $page){
		$nap_patrolled = $dbr->selectField('newarticlepatrol', 'nap_patrolled', array('nap_page' => $page));
		
		if ($nap_patrolled === '0') {
			$boosted = false;
		}
		else {
			//is == 1 or isn't in the table
			$boosted = true;
		}
		
		return $boosted;
	}

	function getHighestNAB(&$dbr, $period='7 days ago'){
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$res = $dbr->select('logging', array('*', 'count(*) as C', 'MAX(log_timestamp) as recent_timestamp'), array("log_type" => 'nap', 'log_timestamp > "' . $starttimestamp . '"'), 'NewArticleBoost::getTopContributor', array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		$nabuser = array();
		$nabuser['id'] = $row->log_user;
		$nabuser['date'] = wfTimeAgo($row->recent_timestamp);

		return $nabuser;
	}

	function getNABList($newbie_only = false) {
		global $wgOut, $wgUser, $wgLang;
		$dbr = wfGetDB(DB_SLAVE);
		$sk = $wgUser->getSkin();
		$old = wfTimestamp(TS_MW, time() - 60 * 60);
		list( $limit, $offset ) = wfCheckLimits();
		$newbie_opt = "";
		if ($newbie_only)
			$newbie_opt = " AND nap_newbie = 1 ";
		else
			$newbie_opt = " AND nap_newbie = 0 ";
			// they can do newbie only and want to do it

		// what about category filters?
		$cat = $wgUser->getCatFilter();

		$sql = "SELECT page_namespace, page_title, nap_timestamp , st_title, nap_page
				FROM	newarticlepatrol, page left join templatelinks on tl_from = page_id and tl_title='Inuse'
					   left join suggested_titles on page_title = st_title
				WHERE 	page_id=nap_page AND page_is_redirect=0 AND nap_patrolled=0 AND nap_timestamp < '$old' AND tl_title is NULL  {$newbie_opt} {$cat}
				GROUP BY page_title
				ORDER 	BY nap_page desc
				LIMIT 	$offset, $limit";
		$res = $dbr->query($sql);
		$wgOut->addHTML("<table width='100%' class='nablist'><tr class='toprow'><td>#</td><td>Article</td><td>ST?</td><td>Created</td></tr>");
		$index = 0;
		while ($row = $dbr->fetchObject($res)) {
			$index++;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			$s = SpecialPage::getTitleFor( 'Newarticleboost', $t->getText() );
			$wgOut->addHTML("<tr><td>{$index}.<!--{$row->nap_page}--></td><td class='link''>" . $sk->makeLinkObj($s, $t->getText(), $newbie_only ? "newbie=1" : null ) . "</td><td class='sugg'>");
			if ($row->st_title != null) {
				$wgOut->addHTML("<img src='/extensions/wikihow/CheckMark.png' height='16' width='16' alt='suggestion'/>");
			}
			$wgOut->addHTML("</td><td>");
			if ($row->nap_timestamp != '') {
				$wgOut->addHTML($wgLang->timeanddate($row->nap_timestamp) );
			}
			$wgOut->addHTML("</td></tr>\n");
		}
		$wgOut->addHTML("</table>");
		$dbr->freeResult($res);
	}
	function execute ($par) {
		global $wgRequest, $wgUser, $wgOut, $wgLang, $wgServer;

		wfLoadExtensionMessages('Newarticleboost');

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// set tidy on to avoid IE8 complaining about browser compatibility
		$opts = $wgOut->parserOptions();
		$opts->setTidy(true);
		$wgOut->parserOptions($opts);
		$wgOut->addMeta('X-UA-Compatible', 'IE=8');

		$type = $wgRequest->getVal('type', null);
		/// SHOW THE EDIT FORM
		if ($type == 'editform') {
			global $wgTitle;
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromURL($wgRequest->getVal('target'));
			$wgTitle = $t;
			$a = new Article($t);
			$editor = new EditPage( $a );
			$editor->edit();

			if ($wgOut->mRedirect != "" && $wgRequest->wasPosted()) {
				$wgOut->redirect('');
				$r = Revision::newFromTitle($t);
				$wgOut->addHTML($wgOut->parse($r->getText()));
			}
			return;
		}

		if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
			$wgOut->setArticleRelated( false );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);
		$me  = Title::makeTitle(NS_SPECIAL, "Newarticleboost");
		$can_newbie = in_array( 'newbienap', $wgUser->getRights() );
		$do_newbie = $wgRequest->getVal("newbie") == 1 && $can_newbie;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$sk = $wgUser->getSkin();
		$wgOut->addHTML('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/newarticlepatrol.css"; /*]]>*/</style>');
		$wgOut->addHTML('<script type="text/javascript">
				var gAutoSummaryText = "' . wfMsg('nap_autosummary') . '";
				var gChangesLost = "'.wfMsg('all-changes-lost').'";
				</script>');
		$wgOut->addHTML('<script type="text/javascript" src="/skins/common/clientscript.js"></script>');
		$wgOut->addHTML('<script type="text/javascript" src="/extensions/wikihow/newarticlepatrol.js"></script>');

		if ($target == '') {
			if ($can_newbie && $wgRequest->getVal('flushnewbie') == 1) {
				$opts ="";
				if ($wgRequest->getVal('flushlimit')) {
					$opts = "ORDER BY nap_page LIMIT " . $wgRequest->getVal('flushlimit');
				}
				$wgOut->addHTML("Newbie queue flushed.");
				$dbw->query('update newarticlepatrol set nap_newbie=0 where nap_newbie = 1 and nap_patrolled = 0 ' . $opts);
			}
			$btn_class = "style='float: right; margin-bottom: 10px;' class='button white_button_150' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'";
			if ($can_newbie) {
				if ($do_newbie) {
					$wgOut->addHTML("<a $btn_class href='/Special:Newarticleboost'>All articles</a><br/>");
				} else {
					$wgOut->addHTML("<a $btn_class href='/Special:Newarticleboost?newbie=1'>Newbie articles</a><br/>");
				}
			}
			$this->getNABList($do_newbie);
			if ($can_newbie) {
				$btn_class = "style='float: right; margin-top: 10px;' class='button white_button_150' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'";
				if ($do_newbie) {
					$wgOut->addHTML("<a $btn_class onclick='checkNewbieFlush();'>Flush Newbie Queue</a><div style='float:right; margin-top: 14px;'>Limit: <input type='text' size='2' id='newbieflush_limit'/></div>");
				}
			}
		} else {

			if ($wgRequest->wasPosted()) {
				$err = false;
				$aid = $wgRequest->getVal('page');
				if ($wgRequest->getVal('nap_submit', null) != null) {
					$t = Title::newFromID($aid);

					// MARK ARTICLE AS PATROLLED
					$ts = wfTimestampNow();

					$dbw->update('newarticlepatrol',
						array('nap_timestamp_ci' => $ts, "nap_user_ci={$wgUser->getId()}", "nap_patrolled=1"),
						array("nap_page={$aid}"));

					wfRunHooks("NABMarkPatrolled", array($aid));

					// LOG ENTRY
					if ($t) {
	   					$params = array($aid);
						$log = new LogPage( 'nap', false );
						$log->addEntry( 'nap', $t, wfMsg('nap_logsummary', $t->getFullText()), $params );
					}

					// ADD ANY TEMPLATES
					$formVars = $wgRequest->getValues();
					$newTemplates = "";
					$templatesArray = array();
					foreach ($formVars as $key=>$value) {
						if (strpos($key, "template") === 0 && $value == "on") {
							$i = substr($key, strlen("template"), 1);
							$template = substr($key, strlen("template") + 2, strlen($key) - strlen("template") - 2);
							$params = "";
							foreach ($formVars as $key2=>$value2) {
								if (strpos($key2, "param$i") === 0) {
									$params .= "|";
									$params .= $value2;
								}
							}
							if ($template == "nfddup") {
								$template = "nfd|dup";
							}
							$newTemplates .= "{{{$template}{$params}}}";
							$templatesArray[] = $template;
						}
					}
#print_r($_post); print_r($templatesArray); exit;
					if ($newTemplates != "") {
						$r = Revision::newFromTitle($t);
						$a = new Article($t);
						$text = $r->getText();
						// were these templates were already added, maybe a back button situation?
						if (strpos($text, $newTemplates) === false) {
							$text = "$newTemplates\n$text";
							$watch = $t->userIsWatching(); // preserve watching just in case
							if ($a->updateArticle($text, wfMsg('nap_applyingtemplatessummary', implode(", ", $templatesArray)), false, $watch)) {
								$wgOut->redirect('');
							}
						}
					}




					// Rising star actions FS RS
					if ($wgRequest->getVal('cb_risingstar', null) == "on") {

						$dateStr = $wgLang->timeanddate(wfTimestampNow());

						$user = $wgUser->getName();
						$real_name = User::whoIsReal($wgUser->getID());
						if ($real_name == "") {
							$real_name = $user;
						}

						// post onto user talk page
						//
						//
					 	if ($wgRequest->getVal('prevuser', null) != "") {
							$this->notifyUserOfRisingStar($t, $wgRequest->getVal('prevuser'));
						}

						// Give user a thumbs up. Set oldId to -1 as this should be the first revision
						//if (class_exists('ThumbsUp')) {
						//	ThumbsUp::thumbNAB(-1, $t->getLatestRevID(), $t->getArticleID());
						//}

						// post onto article discussion page
						//
						//
						{
							$text = "";
							$article = "";

							$u = new User();
							$u->setName($wgRequest->getVal('prevuser', null));
							$up1 = $u->getUserPage();
							$un1 = $u->getName();
							$up2 = $wgUser->getUserPage();
							$un2 = $wgUser->getName();

							$tp = $t->getTalkPage();
							$comment = '{{Rising-star-discussion-msg-2|[['.$up1.'|'.$un1.']]|[['.$up2.'|'.$un2.']]}}' . "\n";
							$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);

							wfRunHooks("MarkTitleAsRisingStar", array($t)); 

							if ($tp->getArticleId() > 0) {
								$r = Revision::newFromTitle($tp);
								$text = $r->getText();
							}
							$article = new Article($tp);

							$text = "$comment\n\n" . $text;


							$watch = false;
							if ($wgUser->getID() > 0)
								$watch = $wgUser->isWatched($tp);

	  					if ($tp->getArticleId() > 0) {
							$article->updateArticle($text, wfMsg('nab-rs-discussion-editsummary'), true, $watch);
							} else {
								$article->insertNewArticle($text, wfMsg('nab-rs-discussion-editsummary'), true, $watch, false, false, true);
							}
						}

						// add to fs feed page
						//
						//
						{
							$text = "";
							$article = "";
						 $fsfeed = Title::newFromURL('wikiHow:Rising-star-feed');
							$r = Revision::newFromTitle($fsfeed);
							$article = new Article($fsfeed);
							$text = $r->getText();

							$watch = false;
							if ($wgUser->getID() > 0)
								$watch = $wgUser->isWatched($t->getTalkPage());

							$text .= "\n".  date('==Y-m-d==') . "\n" . $t->getFullURL() . "\n";
							$article->updateArticle($text, wfMsg('nab-rs-feed-editsummary'), true, $watch);
						}
					}


					// delete if neessary
					if ($wgRequest->getVal('delete', null) != null && $wgUser->isAllowed('delete')) {
						$a = new Article($t);
						$a->doDelete($wgRequest->getVal("delete_reason"));
					}

					// move if neessary
					if ($wgRequest->getVal('move', null) != null && $wgUser->isAllowed('move')) {
						if ($wgRequest->getVal('move_newtitle', null) == null) {
							$wgOut->addHTML("error: no target page title specified.");
							return;
						}
						$nt = Title::newFromText($wgRequest->getVal('move_newtitle'));
						$ret  = $t->moveTo($nt);
						if (is_string($ret)) {
							$wgOut->addHTML("Renaming of the article failed: " . wfMsg($ret));
							$err= true;
						}

						# move the talk page if it exists
						$ott = $t->getTalkPage();
						if( $ott->exists() ) {
							$ntt = $nt->getTalkPage();
							$error = $ott->moveTo( $ntt );
						}

						$t = $nt;
					}

					// MARK ALL PREVIOUS EDITS AS PATROLLED IN RC
					$maxrcid = $wgRequest->getVal('maxrcid');
					if ($maxrcid != '') {
						$res = $dbr->select('recentchanges', 'rc_id', array('rc_id<=' . $maxrcid, 'rc_cur_id=' . $aid, 'rc_patrolled=0'));
						while ($row = $dbr->fetchObject($res)) {
							RecentChange::markPatrolled( $row->rc_id );
							PatrolLog::record( $row->rc_id, false );
						}
						$dbr->freeResult($res);
					}

					wfRunHooks("NABArticleFinished", array($aid));
				}
				// GET NEXT UNPATROLLED ARTICLE
				//clear the checkout
				if ($wgRequest->getVal('nap_skip') && $wgRequest->getVal('page') ) {
					$dbw->update('newarticlepatrol', array('nap_user_co=0'), array("nap_page={$wgRequest->getVal('page')}"));
				}
				$old = wfTimestamp(TS_MW, time() - 30 * 60);
				$id = "";
				$newbie_opt = "";
				if ($do_newbie)
					$newbie_opt = " AND nap_newbie=1 ";
				else
					$newbie_opt = " AND nap_newbie=0 ";
				$cat = $wgUser->getCatFilter();
				$sql = "SELECT nap_page
						FROM newarticlepatrol, page left outer join templatelinks on page_id = tl_from and tl_title='Inuse'
						WHERE nap_page <  $aid AND nap_patrolled=0 AND (nap_user_co=0 || nap_timestamp_co < '$old') AND nap_page=page_id
							AND tl_title is null AND page_is_redirect=0 {$newbie_opt} {$cat}
						ORDER BY nap_page desc
						 LIMIT 1;";
				$res = $dbr->query($sql);
				if (($row = $dbr->fetchObject($res)) != null)
					$id = $row->nap_page;
				if ($id == "") {
					$wgOut->addHTML("Unable to get next id to patrol.");
					return;
				}

				$t = Title::newFromID($id);
				$nap = SpecialPage::getTitleFor( 'Newarticleboost', $t->getText() );
				$url = $nap->getFullURL() . ($do_newbie?"?newbie=1":"");
				if (!$err) $wgOut->redirect($url);
				else $wgOut->addHTML("<br/><br/>Click <a href='{$nap->getFullURL()}'>here</a> to continue.");
				return;
			}
			$t = Title::newFromURL($target);


			$r = Revision::newFromTitle($t);
			if (!$r) {
				$wgOut->addHTML("Error: No revision for {$t->getFullText()}. Click <a href='/Special:Newarticleboost'>here</a> to return to the list.");
				return;
			}
			$in_nab = $dbr->selectField('newarticlepatrol', 'count(*)', array('nap_page'=>$t->getArticleID())) > 0;
				$newbs  = $dbr->selectField("newarticlepatrol", "nap_newbie", "nap_page=" . $t->getArticleId());
			if (!$in_nab || ($newbs && !$can_newbie)) {
				$wgOut->addHTML("Error: This article is not in the NAB list");
				return;
			}
			$locked = false;

			$min_timestamp = $dbr->selectField("revision", "min(rev_timestamp)", "rev_page=" . $t->getArticleId());
			$first_user = $dbr->selectField("revision", "rev_user_text", array("rev_page=" . $t->getArticleId(), 'rev_timestamp' => $min_timestamp));
			$first_user_id = $dbr->selectField("revision", "rev_user", array("rev_page=" . $t->getArticleId(), 'rev_timestamp' => $min_timestamp));
			$u = new User();
			if ($first_user_id) {
				$u->setId($first_user_id);
				$u->loadFromDatabase();
			} else {
				$u->setName($first_user);
			}

			$user_talk = $u->getTalkPage();
			$ut_id = $user_talk->getArticleID();
			$display_name = $u->getRealName() == "" ? $u->getName() : $u->getRealName();

			$wgOut->setPageTitle(wfMsg('nap_title', $t->getFullText()));
			$count = $dbr->selectField('suggested_titles', array('count(*)'), array('st_title' => $t->getDBKey()));
			$extra = "";
			if ($count > 0)
				$extra = " - from Suggested Titles database";
			$wgOut->addWikiText(wfMsg('nap_writtenby', $u->getName(), $display_name, $extra));

			$wgOut->addHTML(wfMsgExt('nap_quicklinks', 'parseinline', $me->getFullText() . "/" . $t->getFullText() ));

			/// CHECK TO SEE IF ARTICLE IS LOCKED OR ALREADY PATROLLED
			$aid = $t->getArticleID();
			$old = wfTimestamp(TS_MW, time() - 30 * 60);

			$patrolled = $dbr->selectField('newarticlepatrol', 'nap_patrolled', array("nap_page=$aid"));
			if ($patrolled) {
				$locked = true;
				$wgOut->addHTML(wfMsgExt("nap_patrolled", 'parse'));
			} else {
				$user_co = $dbr->selectField('newarticlepatrol', 'nap_user_co', array("nap_page=$aid", "nap_timestamp_co > '$old'"));
				if ($user_co != '' && $user_co != 0 && $user_co != $wgUser->getId()) {
					$x = User::newFromId($user_co);
					$wgOut->addHTML(wfMsgExt("nap_usercheckedout", 'parse', $x->getName()));
					$locked = true;
				} else {
					// CHECK OUT THE ARTICLE TO THIS USER
					$ts = wfTimestampNow();
					$dbw->update('newarticlepatrol', array('nap_timestamp_co' => $ts, 'nap_user_co' => $wgUser->getId()), array("nap_page= $aid"));
				}
			}

			#if (function_exists("LSearch::wfGetGoogleSearchResultTitles")) {
			if (true){
				/// SIMILAR RESULT
				$wgOut->addHTML("<div class='nap_section'>");
				$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_similarresults') . "</div>");
				$count = 0;
				$l = new LSearch();
				$hits  = $l->googleSearchResultTitles($t->getFullText(), 0, 5);
				if (sizeof($hits) > 0) {
					$html = "";
					foreach  ($hits as $hit) {
						$t1 = $hit;
						$id = rand(0, 500);
						if ($t1 == null || $t1->getFullURL() == $t->getFullURL() || $t1->getNamespace() != NS_MAIN) continue;
						$safe_title = htmlspecialchars(str_replace("'", "&#39;", $t1->getText()));

						$html .= "<tr><td>"
							. $sk->makeLinkObj($t1, wfMsg('howto', $t1->getText() ))
							. "</td><td style='text-align:right; width: 200px;'>[<a href='#action' onclick='nap_Merge(\"{$safe_title}\");'>" . wfMsg('nap_merge') . "</a>] "
							. " [<a href='#action' onclick='javascript:nap_Dupe(\"{$safe_title}\");'>" . wfMsg('nap_duplicate') . "</a>] "
							. " <span id='mr_$id'>[<a onclick='javascript:nap_MarkRelated($id, {$t1->getArticleID()}, {$t->getArticleID()});'>" . wfMsg('nap_related') . "</a>]</span> "
							. "</td></tr>";
						$count++;
					}
				}
				if ($count == 0)
					$wgOut->addHTML(wfMsg('nap_no-related-topics'));
				else
					$wgOut->addHTML(wfMsg('nap_already-related-topics') . "<table style='width:100%;'>$html</table>");

				$wgOut->addHTML(wfMsg('nap_othersearches', urlencode($t->getFullText()) ));
				$wgOut->addHTML("<div class='nap_footer'> </div>");
				$wgOut->addHTML("</div>");
			}

			/// COPYRIGHT CHECKER
			$cc_check = SpecialPage::getTitleFor( 'Copyrightchecker', $t->getText() );
			$wgOut->addHTML("<script type='text/javascript'>window.onload = nap_cCheck; var nap_cc_url = \"{$cc_check->getFullURL()}\";</script>");
			$wgOut->addHTML("<div class='nap_section'>");
			$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_copyrightchecker') . "</div>");
			$wgOut->addHTML("<div id='nap_copyrightresults'><center><img src='/extensions/wikihow/rotate.gif' alt='loading..'/></center></div>");
			$wgOut->addHTML("<center><input type='button' class='guided-button' onclick='nap_cCheck();' value='Check'/></center>");
			$wgOut->addHTML("<div class='nap_footer'> </div>");
			$wgOut->addHTML("</div>");

			/// ARTICLE PREVIEW
			$editUrl = $me->getFullURL() . "?type=editform&target=" . urlencode($t->getFullText()) . "&fromnab=1";
			$wgOut->addHTML("<div class='nap_section'>");
			$wgOut->addHTML("<a name='article'></a>");
			$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_articlepreview')
							. " - <a href=\"{$t->getFullURL()}\" target=\"new\">" . wfMsg('nap_articlelinktext')."</a>"
							. " - <a href=\"{$t->getEditURL()}\" target=\"new\">" . wfMsg('edit')."</a>"
							. " - <a href=\"{$t->getFullURL()}?action=history\" target=\"new\">" . wfMsg('history')."</a>"
							. " - <a href=\"{$t->getTalkPage()->getFullURL()}?action=history\" target=\"new\">" . wfMsg('discuss')."</a>"
						. "</div>");
			$wgOut->addHTML("<div id='article_contents' ondblclick='nap_editClick(\"$editUrl\");'>");
			$wgOut->addHTML($wgOut->parse($r->getText()));
			$wgOut->addHTML("</div>");
			$wgOut->addHTML("<center><input id='editButton' type='button' class='guided-button' name='wpEdit' value='" . wfMsg('edit') .
							"' onclick='nap_editClick(\"$editUrl\");'/></center>");
			$wgOut->addHTML("<div class='nap_footer'> </div>");
			$wgOut->addHTML("</div>");

			/// DiSCUSSION PREVIEW
			$tp = $t->getTalkPage();
			$wgOut->addHTML("<div class='nap_section'>");
			$wgOut->addHTML("<a name='talk'></a>");
			$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_discussion')
							. " - <a href=\"{$tp->getFullURL()}\" target=\"new\">" . wfMsg('nap_articlelinktext')."</a> - <a id='show_link' href='' onclick='jQuery(\"#disc_page\").fadeIn(); jQuery(\"#show_link\").hide(); return false;'>Show</a>"
						. "</div><div id='disc_page'>");
			if ($tp->getArticleID() > 0) {
				$rp = Revision::newFromTitle($tp);
				$wgOut->addHTML($wgOut->parse($rp->getText()));
			} else {
				$wgOut->addHTML(wfMsg('nap_discussionnocontent'));
			}
	 		$wgOut->addHTML(Postcomment::getForm(true, $tp, true));
			$wgOut->addHTML("</div><div class='nap_footer'> </div>");
			$wgOut->addHTML("</div>");

			/// USER INFORMATION
			$wgOut->addHTML("<div class='nap_section'>");
			$wgOut->addHTML("<a name='user'></a>");
			$used_templates = array();
			if ($ut_id > 0) {
				$res = $dbr->select('templatelinks', array('tl_title'), array('tl_from=' . $ut_id));
				while($row = $dbr->fetchObject($res)) {
					$used_templates[] = strtolower($row->tl_title);
				}
				$dbr->freeResult($res);
			}
			$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_userinfo')
							. " - <a href=\"{$user_talk->getFullURL()}\" target=\"new\">" . wfMsg('nap_articlelinktext')."</a>"
				. "</div>");
			$contribs = SpecialPage::getTitleFor( 'Contributions', $u->getName() );

			$regDateTxt = "";
			if ($u->getRegistration() > 0) {
				preg_match('/^(\d{4})(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/D',$u->getRegistration(),$da);
				$uts=gmmktime((int)$da[4],(int)$da[5],(int)$da[6],
									(int)$da[2],(int)$da[3],(int)$da[1]);
				$regdate = gmdate( 'F j, Y', $uts);
				$regDateTxt = wfMsg('nap_regdatetext',$regdate) . ' ';
			}

			$key = 'nap_userinfodetails_anon';
			if ($u->getID() != 0) {
				$key = 'nap_userinfodetails';
			}
		   	$wgOut->addWikiText(wfMsg($key,
					$u->getName(),
					number_format(User::getAuthorStats($first_user), 0, "", ","),
					$t->getFullText(),
					$regDateTxt
				 )
				);

			if (User::getAuthorStats($first_user) < 50) {
				if ($user_talk->getArticleId() == 0) {
					$wgOut->addHTML(wfMsg('nap_newwithouttalkpage'));
				} else {
					$rp = Revision::newFromTitle($user_talk);
					$xtra = "";
					if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE 8.0") === false)
						$xtra = "max-height: 300px; overflow: scroll;";
					$wgOut->addHTML("<div style='border: 1px solid #eee; {$xtra}'>" . $wgOut->parse($rp->getText()) . "</div>");
				}
			}

			if (($user_talk->getArticleId() != 0) && (sizeof($used_templates) > 0)) {
				$wgOut->addHTML('<br />' . wfMsg('nap_usertalktemplates', implode($used_templates, ", ")));
			}

			$wgOut->addHTML(Postcomment::getForm(true, $user_talk, true));
			$wgOut->addHTML("<div class='nap_footer'> </div>");
			$wgOut->addHTML("</div>");

			/// ACTION INFORMATION
			$maxrcid = $dbr->selectField('recentchanges', 'max(rc_id)', array('rc_cur_id=' . $aid));
			$wgOut->addHTML("<div class='nap_section'>");
			$wgOut->addHTML("<a name='action'></a>");
			$wgOut->addHTML("<div class='nap_header'>" . wfMsg('nap_action') . "</div>");
			$wgOut->addHTML("<form action='{$me->getFullURL()}' name='nap_form' method='post' onsubmit='return checkNap();'>");
			$wgOut->addHTML("<input type='hidden' name='target' value='" . htmlspecialchars($t->getText()) . "'/>");
			$wgOut->addHTML("<input type='hidden' name='page' value='{$aid}'/>");
			$wgOut->addHTML("<input type='hidden' name='newbie' value='". $wgRequest->getVal('newbie', 0) . "'/>");
			$wgOut->addHTML("<input type='hidden' name='prevuser' value='" . $u->getName() . "'/>");
			$wgOut->addHTML("<input type='hidden' name='maxrcid' value='{$maxrcid}'/>");
			$wgOut->addHTML("<table><tr><td valign='top'>");
			$suggested = $dbr->selectField('suggested_titles', 'count(*)', array('st_title'=>$t->getDBKey()));
			if ($suggested > 0) {
				$wgOut->addHTML(wfMsg('nap_suggested_warning'));
			}
 			$wgOut->addHTML("</td></tr><tr><td>" .  wfMsg('nap_actiontemplates') . "</td></tr>");
			$wgOut->addHTML("</table>");
			if ($wgUser->isAllowed( 'delete' )  || $wgUser->isAllowed( 'move' ) ) {
				$wgOut->addHTML(wfMsg('nap_actionmovedeleteheader'));
				if ($wgUser->isAllowed( 'move' )) {
					$wgOut->addHTML(wfMsg('nap_actionmove', htmlspecialchars($t->getText())));
				}
				if ($wgUser->isAllowed( 'delete' )) {
					$wgOut->addHTML(wfMsg('nap_actiondelete'));
				}
			}

			// BUTTONS
			$wgOut->addHTML("<input type='submit' value='" . wfMsg('nap_skip') . "' id='nap_skip' name='nap_skip'/>");
			if (!$locked)
				$wgOut->addHTML("<input type='submit' value='" . wfMsg('nap_markaspatrolled') . "' id='nap_submit' name='nap_submit'/>");
			$wgOut->addHTML("</form>");
			$wgOut->addHTML("<div class='nap_footer'> </div>");
			$wgOut->addHTML("</div>");

			$wgOut->addHTML(<<<END
<script type='text/javascript'>

var tabindex = 1;
for(i = 0; i < document.forms.length; i++) {
	for (j = 0; j < document.forms[i].elements.length; j++) {
		switch (document.forms[i].elements[j].type) {
			case 'submit':
			case 'text':
			case 'textarea':
			case 'checkbox':
			case 'button':
				document.forms[i].elements[j].tabIndex = tabindex++;
				break;
			default:
				break;
		}
	}
}

</script>

END
);


		}
	}

	// puts the Rising-star-usertalk-msg on the user's talk page and emails the user
	function notifyUserOfRisingStar($t, $name) {
		global $wgUser, $wgLang;
		$user = $wgUser->getName();
		$real_name = User::whoIsReal($wgUser->getID());
		if ($real_name == "") {
				$real_name = $user;
		}

		$dateStr = $wgLang->timeanddate(wfTimestampNow());
		$text = "";
		$article = "";
		$u = new User();
		$u->setName($name);

		$user_talk = $u->getTalkPage();
		$comment = '{{subst:Rising-star-usertalk-msg|[['.$t->getText().']]}}' . "\n";
		$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);

		if ($user_talk->getArticleId() > 0) {
			$r = Revision::newFromTitle($user_talk);
			$text = $r->getText();
		}
		$article = new Article($user_talk);

		$text .= "\n\n$formattedComment\n\n";

		$article->doEdit($text, wfMsg('nab-rs-usertalk-editsummary'));
		//
		// Send author email notification
		//
		AuthorEmailNotification::notifyRisingStar($t->getText(), $name, $real_name, $user);
	}
}

class NABStatus extends SpecialPage {
	function __construct() {
		SpecialPage::SpecialPage( 'NABStatus' );
	}
	function execute($par) {
		global $wgTitle, $wgOut, $wgRequest, $wgUser;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		$sk = $wgUser->getSkin();
		$dbr = wfGetDB(DB_SLAVE);

		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/newarticlepatrol.css"; /*]]>*/</style>');
		$wgOut->addHTML(wfMsg('nap_statusinfo'));
		$wgOut->addHTML("<br/><center>");
		$days = $wgRequest->getVal('days', 1);
		if ($days == 1) {
			$wgOut->addHTML(" [". wfMsg('nap_last1day') . "] ");
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last7day'), "days=7") . "] ");
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last30day'), "days=30") . "] ");
		} else if ($days == 7) {
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last1day'), "days=1") . "] ");
			$wgOut->addHTML(" [" . wfMsg('nap_last7day') . "] ");
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last30day'), "days=30") . "] ");
		} else if ($days == 30) {
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last1day'), "days=1") . "] ");
			$wgOut->addHTML(" [" . $sk->makeLinkObj($wgTitle, wfMsg('nap_last7day'), "days=7") . "] ");
			$wgOut->addHTML(" [" . wfMsg('nap_last30day') . "] ");

		}

		$old = wfTimestamp(TS_MW, time() - 60 * 60 * 24 * $days);
		$boosted = $dbr->selectField(array('newarticlepatrol', 'page'),
					array('count(*)'),
					array('page_id=nap_page', 'page_is_redirect=0', 'nap_patrolled=1', "nap_timestamp_ci > '$old'"),
					"wfSpecialNABStatus");
		$newarticles = $dbr->selectField(array('recentchanges'),
					array('count(*)'),
					array('rc_new=1', 'rc_namespace='. NS_MAIN, "rc_timestamp > '$old'"),
					"wfSpecialNABStatus");
		$na_boosted = $dbr->selectField(array('recentchanges', 'newarticlepatrol'),
					array('count(*)'),
					array('rc_new=1', 'rc_namespace='. NS_MAIN, "rc_timestamp > '$old'", "nap_page=rc_cur_id", "nap_patrolled=1"),
					"wfSpecialNABStatus");

		$boosted = number_format($boosted, 0, "", ",");
		$newarticles = number_format($newarticles, 0, "", ",");
		$na_boosted = number_format($na_boosted, 0, "", ",");
		$per_boosted = $newarticles > 0 ? number_format($na_boosted/ $newarticles * 100, 2) : 0;

		$wgOut->addHTML("<br/><br/><div>
				<table width='50%' align='center' class='status'>
					<tr>
						<td>" . wfMsg('nap_totalboosted') . "</td>
						<td>$boosted</td>
					</tr>
					<tr>
						<td>" . wfMsg('nap_numnewboosted') . "</td>
						<td>$na_boosted</td>
					</tr>
					 <tr>
						<td>" . wfMsg('nap_numarticles') . "</td>
						<td>$newarticles</td>
					</tr>
					<tr>
						<td>" . wfMsg('nap_perofnewbosted') . "</td>
						<td>$per_boosted%</td>
					</tr>
				</table>
				</div>");
		$wgOut->addHTML("</center>");

		$wgOut->addHTML("<br/>" . wfMsg('nap_userswhoboosted') . "<br/><br/><center>
				<table width='500px' align='center' class='status'>" );

		$total = $dbr->selectField('logging', 'count(*)',  array ('log_type'=>'nap', "log_timestamp>'$old'"));

		 $sql = "SELECT log_user, count(*) as C
				FROM logging where log_type='nap' and log_timestamp > '$old'
				GROUP BY log_user ORDER BY C desc limit 20;";

		$res = $dbr->query($sql);
		$index = 1;
			$wgOut->addHTML("<tr>
						   <td></td>
							<td>User</td>
							<td  align='right'>" . wfMsg('nap_numboosted') . "</td>
							<td align='right'>" . wfMsg('nap_perboosted') . "</td>
							</tr>
			");
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$u = User::newFromID($row->log_user);
			$percent = $total == 0 ? "0" : number_format($row->C / $total * 100, 0);
			$count = number_format($row->C, 0, "", ',');
			$log = $sk->makeLinkObj(Title::makeTitle( NS_SPECIAL, 'Log'), $count, 'type=nap&user=' .  $u->getName());
			$wgOut->addHTML("<tr>
				<td>$index</td>
				<td>" . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td>
				<td  align='right'>{$log}</td>
				<td align='right'> $percent % </td>
				</tr>
			");
			$index++;
		}
		$dbr->freeResult($res);
		$wgOut->addHTML("</table></center>");

	/**
		$res = $dbr->select('recentchanges', array('rc_user_text',
													'sum(rc_new_len - rc_old_len) as S',
													'avg(rc_new_len - rc_old_len) as A',
													'count(*) as C'),
											array('rc_comment like "' . $dbr->strencode(wfMsg('nap_autosummary')) .'%"', "rc_timestamp>'$old'"),
											"wfSpecaiLNabStatus",
											array('GROUP BY' => 'rc_user_text', 'ORDER BY' => 'C desc'));

		$wgOut->addHTML("<br/>Articles edited from NAB<br/><br/><center>
				<table width='500px' align='center' class='status'>" );
			$wgOut->addHTML("<tr>
							<td>User</td>
							<td  align='right'>Number edited</td>
							<td  align='right'>Avg char. added</td>
							<td  align='right'>Total char. added</td>
							</tr>
			");
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$u = User::newFromName($row->rc_user_text);
			$c = number_format($row->C, 0, "", ',');
			$a = number_format($row->A, 1, ".", ',');
			$s = number_format($row->S, 0, "", ',');
			$wgOut->addHTML("<tr>
							<td>" . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td>
							<td  align='right'>{$c}</td>
							<td  align='right'>{$a}</td>
							<td  align='right'>{$s}</td>
							</tr>
			");
		}
		$dbr->freeResult($res);
		$wgOut->addHTML("</table></center>");
	*/
	}
}


class Copyrightchecker extends UnlistedSpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Copyrightchecker','',false, true );
	}

	function execute($par) {
		global $wgRequest, $wgOut, $IP;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if (is_null($target)) {
			$wgOut->addHTML("<b>Error:</b> No parameter passed to Copyrightchecker.");
			return;
		}

		$query = $wgRequest->getVal('query');

   		wfLoadExtensionMessages('Newarticleboost');

		require_once("$IP/extensions/wikihow/GoogleSearch.php");

		$t = Title::newFromURL($target);
		$r = Revision::newFromTitle($t);
		$wgOut->setArticleBodyOnly(true);

		if (!$query) {
			// Get the text and strip the steps header, any templates, flatten it to HTML and strip the tags
			$text = $r->getText();
			$text = preg_replace("/^==[ ]+" . wfMsg('steps') . "[ ]+==/mix", "", $text);
			$text = preg_replace("/{{[^}]*}}/im", "", $text);
			$parts = preg_split("@\.@", $text);
			shuffle($parts);
			$queries = array();	
			foreach ($parts as $p) {
				$p = trim($p); 
				$words = split(" ", $p);
				if (sizeof($words) > 5) {
					if (sizeof($words) >  15) {
						$words = array_slice($words, 0, 15);
						$p = implode(" ", $words);
					}
					$queries[] = $p;
					if (sizeof($queries) == 2) {
						break;
					}
				}
			}
			$query = '"' . implode('" AND "',  $queries) . '"';
		}

		require_once('GoogleAjaxSearch.body.php');
		$results = GoogleAjaxSearch::getGlobalWebResults($query, 8, null);

		// Filter out results from wikihow.com
		if (sizeof($results) > 0 && is_array($results)) {
			$newresults = array();
			for ($i = 0; $i < sizeof($results); $i++) {
				if (strpos($results[$i]['url'], "http://www.wikihow.com/") === 0)
					continue;
				$newresults[] = $results[$i];
			}
			$results = $newresults;
		}

		// Process results
		if (sizeof($results) > 0 && is_array($results)) {
			$wgOut->addHTML(wfMsg("nap_copyrightlist", $query) . "<table width='100%'>");
			for ($i = 0; $i < 3 && $i < sizeof($results); $i++) {
				$match = $results[$i];
				$c = json_decode($match['content']);
				$wgOut->addHTML("<tr><td><a href='{$match['url']}' target='new'>{$match['title']}</a>
					<br/>$c
					<br/><font size='-2'>{$match['url']}</font></td><td style='width: 100px; text-align: right; vertical-align: top;'><a href='' onclick='return nap_copyVio(\"" . htmlspecialchars($match['url']) . "\");'>Copyvio</a></td></tr>");
			}
			$wgOut->addHTML("</table>");
		} else {
			$wgOut->addHTML(wfMsg('nap_nocopyrightfound', $query));
		}
		return;
	}
}


class Markrelated extends UnlistedSpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Markrelated','',false, true );
	}

	// adds a related wikihow to the article t1 to t2
	function addRelated($t1, $t2, $summary = "Adding related wikihow from NAB", $top = false, $linkedtext = null) {

#echo "putting a link in '{$t1->getText()}' to '{$t2->getText()}'\n\n";
		if ($linkedtext)
			$link = "*[[{$t2->getText()}|" . wfMsg('howto', $linkedtext) . "]]";
		else
			$link = "*[[{$t2->getText()}|" . wfMsg('howto', $t2->getText()) . "]]";
		$a = new Article($t1);
		$text = $a->getContent(true);
		for ($i = 0; $i <30; $i++) {
			$s = $a->getSection($text, $i);
			if (preg_match("@^==[ ]*" . wfMsg('relatedwikihows') . "@m", $s)) {
				if (preg_match("@{$t2->getText()}@m", $s)) {
					$found = true;
					break;
				}
				if ($top)
					$s = preg_replace("@==\n@", "==\n$link\n", $s);
				else
					$s .= "\n{$link}\n";
				$text = $a->replaceSection($i, $s);
				$found = true;
				break;
			} else if (preg_match("@^==[ ]*(" . wfMsg('sources') . ")@m", $s)) {
				// we have gone too far
				$s = "\n== " . wfMsg('relatedwikihows') . " ==\n{$link}\n\n" . $s;
				$text = $a->replaceSection($i, $s);
				$found = true;
				break;
			}
		}
		if (!$found) {
			$text .= "\n\n== " . wfMsg('relatedwikihows') . " ==\n{$link}\n";
		}
		if (!$a->doEdit($text, $summary))
			echo "Didn't save\n";
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
			$wgOut->setArticleRelated( false );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$wgOut->disable();
		$p1 = $wgRequest->getVal('p1');
		$p2 = $wgRequest->getVal('p2');
		$t1 = Title::newFromID($p1);
		$t2 = Title::newFromID($p2);
		$this->addRelated($t1, $t2);
		$this->addRelated($t2, $t1);
		return;
	}
}



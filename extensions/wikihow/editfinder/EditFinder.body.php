<?
require_once('WikiHow.php');

$efType;

class EditFinder extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'EditFinder' );
	}
	
	/**
	 * Set html template path for EditFinder actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	function getNext() {
		global $wgRequest, $wgOut, $wgUser;

		$dbm = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);
		
		// mark skipped 
		if ($wgRequest->getVal('skip', null)) {
			$t = Title::newFromText($wgRequest->getVal('skip'));
			$id = $t->getArticleID();
			$dbm->update('editfinder', array('editfinder_skip=editfinder_skip+1', 'editfinder_skip_ts'=>wfTimestampNow()), 
				array('editfinder_id'=>$id));
		}
		
		$a = array();
		
		//get user's cats
		$cats = array();
		$row = $dbr->selectRow('suggest_cats', array('*'), array('sc_user'=>$wgUser->getID()));
		if ($row) {
			$field = $row->sc_cats;
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);
		}
	
		$aid = $wgRequest->getVal('id');
		
		if ($aid) {
			//get a specific article
			$sql = "SELECT editfinder_edittype, editfinder_id from editfinder WHERE 
				editfinder_id = $aid ORDER BY editfinder_page_counter DESC LIMIT 1;";
		} 
		else {
			$edittype = $wgRequest->getVal( 'edittype' );

			$timediff = date("YmdHis", strtotime("-1 day"));
			$sql = "SELECT editfinder_edittype, editfinder_id from editfinder WHERE 
				editfinder_last_viewed < '$timediff'
				AND editfinder_edittype = '$edittype' ";				
			if (count($cats) > 0) {
				$sql .= " AND editfinder_cat IN ('".implode($cats, "','")."') ";
			}
			$sql .= " ORDER BY editfinder_page_counter DESC LIMIT 1;";
		}
		
		$res = $dbr->query($sql); 

		while ($row = $dbr->fetchObject($res)) {
			$pageid = $row->editfinder_id;
			$edittype = $row->editfinder_edittype;
		}
		//nothing?  really?
		if (empty($pageid)) return $a;
		
		//touch db
		$dbm->update('editfinder', array('editfinder_last_viewed'=>wfTimestampNow()),array('editfinder_id'=>$pageid));			
					
		$t = Title::newFromID($pageid);
		
		$a['aid'] = $pageid;
		$a['title'] = $t->getText();
		$a['url'] = $t->getLocalURL();
		$a['edittype'] = $edittype;
		
		
		//return array
		return( $a );
	}
	
	function showHead($a) {
		global $wgOut;
		
		//add main article info
		$vars = array('title' => $a['title'], 'titlelink' => $a['url'], 'edittype' => $a['edittype']);
		$html = EasyTemplate::html('editfinder_head', $vars);
		$wgOut->addHTML($html);
	}
	
	
	function confirmationModal($type,$id) {
		global $wgOut, $Title;

		$fname = "EditFinder::confirmationModal";
		wfProfileIn($fname); 

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|How to ".$t->getText()."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Thanks for your edits to <a href='".$t->getLocalURL()."'>How to ".$t->getText()."</a>.</p>
			<p>Would it be appropriate to remove the <span class='template_type'>".strtoupper($type)."</span> from this article?</p>
			<div style='clear:both'></div>
			<span style='float:right'>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_yes')."' onclick='editFinder.closeConfirmation(true);return false;' >
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_no')."' onclick='editFinder.closeConfirmation(false);return false;' >
			</span>
			</div>";
		$wgOut->addHTML( $content );
		wfProfileOut($fname); 
	}
	
	function cancelConfirmationModal($id) {
		global $wgOut, $Title;

		$fname = "EditFinder::cancelConfirmationModal";
		wfProfileIn($fname); 

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|How to ".$t->getText()."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Are you sure you want to stop editing <a href='".$t->getLocalURL()."'>How to ".$t->getText()."</a>?</p>
			<div style='clear:both'></div>
			<span style='float:right'>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_yes')."' id='efcc_yes'>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_no')."' id='efcc_no'>
			</span>
			</div>";
		$wgOut->addHTML( $content );
		wfProfileOut($fname); 
	}

	
	
	
	/**	
	 * fetchStats
	 * get the use stats
	 **/
	function fetchStats() {
		global $wgUser, $wgMemc;
        $fname = "EditFinder::fetchStats";
        wfProfileIn($fname);

		$dbr = wfGetDB(DB_SLAVE);

		$ts_today = date('YmdG',strtotime('today')) . '000000';
		$ts_week = date('YmdG',strtotime('7 days ago')) . '000000';
		$userid = $wgUser->getID();
		$today = $dbr->selectField('logging', 'count(*)',  array ('log_type'=>'editfinder','log_user'=>$userid, "log_timestamp>'$ts_today'"));
		$week = $dbr->selectField('logging', 'count(*)',  array ('log_type'=>'editfinder','log_user'=>$userid, "log_timestamp>'$ts_week'"));
		$all = $dbr->selectField('logging', 'count(*)',  array ('log_type'=>'editfinder','log_user'=>$userid));

		$standing = $this->getStanding($wgUser);

		$defaultmsg = wfMsg('editfinder_msg_instructions');
		$msg = $defaultmsg;

		if ($today == 1) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_firsttoday'));
		} else if ($today < 5) {
			$msg = wfMsg('editfinder_msg_instructions');
		} else if ($all == 1) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_firstever'));
		} else if (($today%10 == 0) && ($today != 0)) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_multiple10', $today));
		} else if ($standing < 5) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top5'));
		} else if ($standing < 10) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top10'));
		} else if ($standing < 25) {
			$msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top25'));
		}

		$s_arr = array(
			'today' => $today,
			'week' => $week,
			'all' => $all,
			'standing' => $standing,
			'message' => $msg,
			'defaultmsg' => $defaultmsg,
		);

		wfProfileOut($fname);
		return $s_arr;
	}

	function getStanding($user) {
		global $wgOut;
        $fname = "EditFinder::getStanding";
        wfProfileIn($fname);
		$standings = $this->getStandingsFromCache();
		$index = 1;
		foreach ($standings as $s => $c) {
			if ($s == $user->getName()) {
				wfProfileOut($fname);
				return $index;
			}
			$index++;	
		}
		wfProfileOut($fname);
		return 0;
	}	
	
	function getStandingsFromCache() {
		global $wgMemc, $wgUser;
		$fname = "EditFinder::getStandingsFromCache";
		wfProfileIn($fname);
		$key = "editfinder_standings";
		$standings = $wgMemc->get($key);
		if (!$standings) {
			$dbr = wfGetDB(DB_SLAVE);
			$ts = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
			$sql = "SELECT log_user, count(*) as C from logging WHERE 
				log_timestamp > '{$ts}' and log_type = 'editfinder'
				group by log_user order by C desc limit 25;";	
			$res = $dbr->query($sql); 
			$standings = array();
			while ($row = $dbr->fetchObject($res)) {
				$u_name = $wgUser->whoIs($row->log_user);
				$standings[$u_name] = $row->C;
			}
			$wgMemc->set($key, $standings, 3600);
		}
		wfProfileOut($fname);
		return $standings;
		
	}

	/**	
	 * addStatsWidget
	 * add stats widget to right rail
	 **/
	function addStatsWidget() {
		global $wgUser;
		$fname = "EditFinder::addStatsWidget";
		wfProfileIn($fname);
		$sk = $wgUser->getSkin();
		$display = "
		<div class='editfinder_stats' id='editfinder_stats'>
		<h3></h3>
		<table>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Today</a></td>
			<td id='editfinder_stats_today'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added?period=7'>This Week</a></td>
			<td id='editfinder_stats_week'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Total</a></td>
			<td id='editfinder_stats_all'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Rank This Week</a></td>
			<td id='editfinder_stats_standing'>&nbsp;</td>
		</tr>
		</table>
		</div>";

		$sk->addWidget( $display );
		wfProfileOut($fname);
	}

	/**	
	 * addStandingsWidget
	 * add standings widget
	 **/
	function addStandingsWidget() {
		global $wgUser;
        $fname = "EditFinder::addStandingsWidget";
        wfProfileIn($fname);

		$sk = $wgUser->getSkin();

		$display = "
		<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/Leaderboard.css'; /*]]>*/</style>
		<div class='editfinder_stats' id='editfinder_standings'>
		<h3>".wfMsg('editfinder_standings_title')."</h3>
		<div id='editfinder_standings_table'>
		".$this->getStandingsTable()."
		</div>
		<p class='bottom_link' style='text-align:center; padding-top:5px'>
		Updating in  <span id='stup'>10</span> minutes
		</p>

		</div>";

		$sk->addWidget( $display );
		wfProfileOut($fname);
	}

	/**	
	 * getStandingsTable
	 * get standings table dynamically
	 **/
	function getStandingsTable() {
		global $wgUser;
        $fname = "EditFinder::getStandingsTable";
        wfProfileIn($fname);

		$sk = $wgUser->getSkin();
		$display = "
		<table>";

		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$data = $this->getStandingsFromCache() ;
		$count = 0;
      	foreach($data as $key => $value) {
			$u = new User();
			$u->setName($key);
			if (($value > 0) && ($key != '')) {

				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}
	
				$display .="
				<tr>
					<td class='leader_image'>" . $img . "</td>
					<td>" . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td>
					<td class='leader_count'>" . $value . "</td>
				</tr>";
				$count++;
			}
			if ($count > 5) {break;}			
		}
		$display .= "
		</table>";
		wfProfileOut($fname);
		return $display;
	}
	
	
	/**
	 * EXECUTE
	 **/
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang, $wgParser, $efType, $wgTitle;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
			
		$userGroups = $wgUser->getGroups();
		if (!in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		
		wfLoadExtensionMessages('EditFinder');
		
		self::setTemplatePath();

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($wgRequest->getVal( 'fetchArticle' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode($this->getNext());
			return;
			
		} else if ($wgRequest->getVal( 'show-article' )) {
			$wgOut->setArticleBodyOnly(true);
			
			if ($wgRequest->getVal('aid') == 'undefined') {
				$wgOut->addHTML('<div class="article_inner">No articles found.  Select more categories and try again.</div>');
				return;
			}
			
			$t = Title::newFromID($wgRequest->getVal('aid'));
			
			$articleTitleLink = $t->getLocalURL();
			$articleTitle = $t->getText();
			$edittype = $a['edittype'];
						
			//get article
			$a = new Article($t);
			
            $r = Revision::newFromTitle($t);
            $popts = $wgOut->parserOptions();
            $popts->setTidy(true);
            $popts->enableLimitReport();
            $parserOutput = $wgParser->parse( $r->getText(), $t, $popts, true, true, $a->getRevIdFetched() );
            $popts->setTidy(false);
            $popts->enableLimitReport( false );
            $html = WikiHowTemplate::mungeSteps($parserOutput->getText(), array('no-ads'));
			$wgOut->addHTML($html);
			return;
			
		} else if ($wgRequest->getVal( 'edit-article' )) {
			// SHOW THE EDIT FORM
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('aid'));
			$a = new Article($t);
			$editor = new EditPage( $a );
			$editor->edit();
			
		} else if ($wgRequest->getVal( 'fetchStats' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchStats() );
        	wfProfileOut($fname);
			return;
			
		} else if ($wgRequest->getVal( 'getstandings' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->getStandingsTable();
        	wfProfileOut($fname);
			return;
			
		} else if ($wgRequest->getVal( 'action' ) == 'submit') {
			$wgOut->setArticleBodyOnly(true);
			
			$efType = $wgRequest->getVal('type');
			
			$t = Title::newFromID($wgRequest->getVal('aid'));
			$a = new Article($t);
			
			//log it
			$params = array($efType);            
			$log = new LogPage( 'editfinder', false ); // false - dont show in recentchanges
			$log->addEntry('', $t, 'improved a '.$efType.' article', $params);
			
			$editor = new EditPage( $a );
			$editor->edit();
			
		} else if ($wgRequest->getVal( 'confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('type'),$wgRequest->getVal('aid')) ;
        	wfProfileOut($fname);
			return;
			
		} else if ($wgRequest->getVal( 'cancel-confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->cancelConfirmationModal($wgRequest->getVal('aid')) ;
        	wfProfileOut($fname);
			return;
			
		} else { //default view (same as most of the views)
			$sk = $wgUser->getSkin();
			$wgOut->setArticleBodyOnly(false);
			
			$efType = strtolower($wgRequest->getVal( 'type' ));
			
			//add main article info
			$vars = array('pagetitle' => wfMsg($efType),'question' => wfMsg('editfinder-question'),'yep' => wfMsg('editfinder_yes'),'nope' => wfMsg('editfinder_no'));
			$html = EasyTemplate::html('editfinder_main',$vars);
			$wgOut->addHTML($html);			
			//fire it up
			$wgOut->addHTML("<script type='text/javascript'>jQuery(window).load(editFinder.init);</script>");
			
			$wgOut->setHTMLTitle(wfMsg($efType).' Tool - wikiHow');
		}
		
		
		$this->addStatsWidget();
		$this->addStandingsWidget();
	}
}
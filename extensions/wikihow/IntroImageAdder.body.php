<?
class IntroImageAdder extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'IntroImageAdder' );

	}

	/**	
	 * checkForIntroImage
	 * Checks an article to see if it contains an image in the intro section
	 **/
	function checkForIntroImage($t) {
		$r = Revision::newFromTitle($t);
		$intro = Article::getSection($r->getText(), 0);

		if (preg_match('/\[\[Image:(.*?)\]\]/', $intro)){
			return true;
		} else {
			return false;
		}
	}

	/**	
	 * addIntroImage
	 * Called from EasyImageUploader and adds image to intro section and updates article
	 **/
	function addIntroImage($v) {
		global $wgOut, $wgRequest;

		$title = $wgRequest->getVal('iiatitle');
		$imgtag = "[[Image:".$v['imageFilename']."|thumb|right|251px]]";
		$json = '';

		$t = Title::newFromText($title);
		$r = Revision::newFromTitle($t);
		$intro = Article::getSection($r->getText(), 0);

		if (!preg_match('/\[\[Image:(.*?)\]\]/', $intro)){
			$article = new Article($t);
			$text = $imgtag . $r->getText();
			$ret = $article->doEdit($text, wfMsg('iia-editsummary'), EDIT_MINOR);
		} else {
			wfDebug("IntroImageAdder - image already exists for article $title \n");
		}

		//global $wgParser;
		//$content = wfMsg('iia_confirmation_dialog', $t->getText(), $imgtag );
		//$output = $wgParser->parse($content, $t, new ParserOptions() );
		//$content = $output->getText();

		$json['status'] = "SUCCESS";
		//$json['dialog'] = $content;
		$json['title'] = urlencode( $t->getText() );
		$json['img'] = urlencode( $v['imageFilename'] );

		return json_encode( $json );
		
	}

	function confirmationModal($iiatitle, $img) {
		global $wgOut, $wgParser, $Title, $wgServer;

		$t = Title::newFromText($iiatitle);
		$imgtag = "[[Image:".$img."|251px]]";
		$titletag = "[[$iiatitle|How to $iiatitle]]";
		$content = wfMsg('iia_confirmation_dialog', $titletag, $imgtag );
		$output = $wgParser->parse($content, $Title, new ParserOptions() );
		$content = $output->getText();
		$content = "
<div class='iia_modal'>
$content
<div style='clear:both'></div>
<span style='float:right'>
<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('iia_confirmation_button')."' onclick='introImageAdder.closeConfirmation();return false;' >
</span>
<input type='checkbox' id='confirmModalFlag' >   ".wfMsg('iia_confirmation_dialog_flagmsg')."
</div>";

		$wgOut->addHTML( $content );
	}

	/**	
	 * getSearchTerms
	 * Passed in a title an removes stop words and unneccessary punctuation
	 **/
	function getSearchTerms($t) {
		$stopwords = explode(',',wfMsg('iia_stopwords'));
		$exclude = array();
		foreach ($stopwords as $word) {
			array_push($exclude, strtoupper(trim($word)));
		}

		$t = str_replace("-"," ",$t);
		$t_arr = explode(" ",$t);
		$s_arr = array();
		foreach ($t_arr as $word) {
			if ((strlen($word) > 2) &&
				(!in_array(strtoupper($word),$exclude)) ){
				array_push($s_arr, $word);
			}
		}
		$s = implode(" ",$s_arr);

		//Characters to replace from string
		//$s = preg_replace('/\W/', ' ', $s);
		$s = preg_replace('/[,()"]/', '', $s);
		return $s;
	}
	

	/**	
	 * getNext
	 * Get the next article to show
	 **/
	function getNext(){
		global $wgRequest;

		$dbm = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);

		// mark skipped 
		if ($wgRequest->getVal('skip', null)) {
			$t = Title::newFromText($wgRequest->getVal('skip'));
			$id = $t->getArticleID();
			$dbm->update('imageadder', array('imageadder_skip=imageadder_skip+1', 'imageadder_skip_ts'=>wfTimestampNow()), 
				array('imageadder_page'=>$id, 'imageadder_inuse'=>0));
		}
		$a = array();
		while(1) {
			//NOTE SQL Queries are excluding pageid 5791 cause it's a Categories page, don't know why it's not in wikihow NS.
			$opts = array('imageadder_inuse != 1', 'imageadder_page != 5791', 'imageadder_skip < 2');
			if (rand(0,9) < 7) {
				//ORDER BY PAGE_COUNTER
				$pageid = $dbm->selectField('imageadder', 'imageadder_page',  
					$opts,			
					"IntroImageAdder::getNext", 
					array("ORDER BY" => "imageadder_page_counter DESC", "LIMIT" => 1));
			} else {
				//ORDER BY PAGE_TOUCHED
				$pageid = $dbr->selectField('imageadder', array('imageadder_page'), 
					$opts,
					"IntroImageAdder::getNext", 
					array("ORDER BY" => "imageadder_page_touched DESC", "LIMIT" => 1));
			}

			if (empty($pageid)) {
				$a = array('error' => 'No articles need images');
				return $a;
			}

			/*
			 * XXNOTE: One day when we can prefetch search terms we will do this instead of call the function
			 * $sql = "SELECT imageadder_page,imageadder_terms from imageadder where imageadder_inuse != 1";
			 * $res = $dbr->query($sql);
			 */
			
			$sql = "update imageadder set imageadder_inuse=1 where imageadder_page=$pageid";
			$res = $dbm->query($sql);

			$t = Title::newFromID($pageid);
	
			if ($t->getArticleId() > 0) {
				if ($this->checkForIntroImage( $t ) ) {
					//$wgOut->addHTML($x ." has intro image.<br/>");
				} else {
					//$wgOut->addHTML($x ." does NOT have an intro image.<br/>");
					$a['aid'] = $t->getArticleId();
					$a['title'] = $t->getText();
					$a['url'] = $t->getLocalURL();
					$a['terms'] = $this->getSearchTerms($t->getText());
					return( $a );
				}
			} else {
				//$wgOut->addHTML($x ." is not a valid article.<br/>");
			}
		}
	}

	/**	
	 * show
	 * Display the main window.  Right now it's skinless
	 **/
	function show() {
		global $wgOut, $wgUser;

		$sk = $wgUser->getSkin();
		//$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML( Easyimageupload::getUploadBoxJS() );

		$wgOut->addHTML( "
	<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/introimageadder.css?2'; /*]]>*/</style>
	<script type='text/javascript' src='/extensions/wikihow/introimageadder.js?4'></script>
	<script type='text/javascript' src='/extensions/wikihow/winpop.js'></script>
	<link rel='stylesheet' href='/extensions/wikihow/winpop.css' type='text/css' />

	<div id='IntroImageAdder'>
		<div id='introimageheader'>
			<h1>". wfMsg('iia_title')."</h1>
			<div id='iia_msg'>
			</div>
		</div><!--end introimageheader-->
		<div style='clear:both;'></div>
		<div id='iia_main'><img src='/extensions/wikihow/rotate.gif' alt='' class='eiu-wheel' id='eiu-wheel-details' /></div>
	</div>
<script type='text/javascript'>
var replacelinks = false;
var pastmessages = [];

jQuery(window).load(introImageAdder.init);
</script>

		");
		return;
	}

	/**	
	 * addStatsWidget
	 * add stats widget to right rail
	 **/
	function addStatsWidget() {
		global $wgUser;

		$sk = $wgUser->getSkin();
		$display = "
		<div class='iia_stats'>
		<h3>".wfMsg('iia_stats_title')."</h3>
		<table>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Today</a></td>
			<td id='iia_stats_today'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added?period=7'>This Week</a></td>
			<td id='iia_stats_week'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Total</a></td>
			<td id='iia_stats_all'>&nbsp;</td>
		</tr>
		<tr>
			<td><a href='/Special:Leaderboard/images_added'>Rank This Week</a></td>
			<td id='iia_stats_standing'>&nbsp;</td>
		</tr>
		</table>
		</div>";

		$sk->addWidget( $display );
	}

	/**	
	 * addStandingsWidget
	 * add standings widget
	 **/
	function addStandingsWidget() {
		global $wgUser;

		$sk = $wgUser->getSkin();

		$display = "
		<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/Leaderboard.css'; /*]]>*/</style>
		<div class='iia_stats'>
		<h3>".wfMsg('iia_standings_title')."</h3>
		<div id='iia_standings_table'>
		".$this->getStandingsTable()."
		</div>
		<p class='bottom_link' style='text-align:center; padding-top:5px'>
		Updating in  <span id='stup'>10</span> minutes
		</p>

		</div>";

		$sk->addWidget( $display );
	}


	/**	
	 * getStandingsTable
	 * get standings table dynamically
	 **/
	function getStandingsTable() {
		global $wgUser;

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

		return $display;
	}

	function getStandingsFromCache() {
		global $wgMemc;
		$key = "imageadder_standings";
		$standings = $wgMemc->get($key);
		if (!$standings) {
			$dbr = wfGetDB(DB_SLAVE);
			$ts = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
			$sql = "SELECT rc_user_text, count(*) as C from recentchanges WHERE 
				rc_timestamp > '{$ts}' and rc_comment='Added Image using ImageAdder Tool'
				group by rc_user_text order by C desc limit 25;";	
			$res = $dbr->query($sql); 
			$standings = array();
			while ($row = $dbr->fetchObject($res)) {
				$standings[$row->rc_user_text] = $row->C;
			}
			$wgMemc->set($key, $standings, 3600);
		}
		return $standings;
	}
	function getStanding($user) {	
		$standings = $this->getStandingsFromCache();
		$index = 1;
		foreach ($standings as $s => $c) {
			if ($s == $user->getName())
				return $index;
			$index++;	
		}
		return 0;
	}
	/**	
	 * fetchStats
	 * get the use stats
	 **/
	function fetchStats() {
		global $wgUser, $wgMemc;

		$dbr = wfGetDB(DB_SLAVE);
		$dbm = wfGetDB(DB_MASTER);

		$ts_today = date('YmdG',strtotime('today')) . '000000';
		$ts_week = date('YmdG',strtotime('7 days ago')) . '000000';
		$username = $wgUser->getName();
		$today = $dbr->selectField('image', 'count(*)',  array ('img_user_text'=>$username, "img_timestamp>'$ts_today'"));
		$week = $dbr->selectField('image', 'count(*)',  array ('img_user_text'=>$username, "img_timestamp>'$ts_week'"));
		$all = $dbr->selectField('image', 'count(*)',  array ('img_user_text'=>$username));

		$standing = $this->getStanding($wgUser);

		//$percent = number_format(($stats['standing'] / $stats['standingcount'] * 100), 0,'','');
		$defaultmsg = wfMsg('iia_msg_instructions');
		$msg = $defaultmsg;

		if ($today == 1) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_firsttoday'));
		} else if ($today < 5) {
			$msg = wfMsg('iia_msg_instructions');
		} else if ($all == 1) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_firstever'));
		} else if (($today%10 == 0) && ($today != 0)) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_multiple10', $today));
		} else if ($standing < 5) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_top5'));
		} else if ($standing < 10) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_top10'));
		} else if ($standing < 25) {
			$msg = wfMsg('iia_msg_icon', wfMsg('iia_msg_top25'));
		}

		$s_arr = array(
			'today' => $today,
			'week' => $week,
			'all' => $all,
			'standing' => $standing,
			'message' => $msg,
			'defaultmsg' => $defaultmsg,
		);

		return $s_arr;
	}

	/**	
	 * fetchMessage
	 * gets a message.  NOT CURRENTLY USED.
	 **/
/*
	function fetchMessage() {
		$messages = array(
			'Congratulations, you added your first image to an article!',
			'Wow, you are on a roll, you\'ve added N images today',
			'Congratulations, you are now in the top 25 of image reviewers!',
			'Congratulations, you are now in the top 10 of image reviewers!',
			'Congratulations, you are now in the top 5 of image reviewers!');

		$msg = $messages[rand(0,count($messages) - 1)];

		return $msg;
	}
*/
	/**	
	 * fetchArticle
	 * Gets the next article (getNext) and prepares to return it in a json object with stats
	 * and corresponding message
	 **/
	function fetchArticle() {

		$a = $this->getNext();
/* 
 *
 * COMMENTED OUT AND PUT IN STATS FETCH BECAUSE OF TIMING ISSUES
 *
 *
		$stats = $this->fetchStats();
		//$percent = number_format(($stats['standing'] / $stats['standingcount'] * 100), 0,'','');

		$msg = wfMsg('iia_msg_instructions');

		if ($stats['today'] == 1) {
			$msg = wfMsg('iia_msg_firsttoday');
		} else if ($stats['today'] < 5) {
			$msg = wfMsg('iia_msg_instructions');
		} else if ($stats['all'] == 1) {
			$msg = wfMsg('iia_msg_firstever');
		} else if (($stats['today']%10 == 0) && ($stats['today'] != 0)) {
			$msg = wfMsg('iia_msg_multiple10', $stats['today']);
		} else if ($stats['standing'] < 5) {
			$msg = wfMsg('iia_msg_top5');
		} else if ($stats['standing'] < 10) {
			$msg = wfMsg('iia_msg_top10');
		} else if ($stats['standing'] < 25) {
			$msg = wfMsg('iia_msg_top25');
		}

		//$art_arr = array_merge($a, $stats, array('message' => $msg));
*/
		return $a;
			 
	}

	/**
	 * EXECUTE
	 **/
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );


		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		//XXNOTE Temporary push to prod code.  When released, remove admin requirement
		//
		//if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
		//	$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
		//	rreturn;
		//}

		if ($wgRequest->getVal( 'fetchArticle' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchArticle() );
			return;
		} else if ($wgRequest->getVal( 'confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('iiatitle'), $wgRequest->getVal('imgtag') ) ;
			return;
		} else if ($wgRequest->getVal( 'standingsTable' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->getStandingsTable() ;
			return;
		} else if ($wgRequest->getVal( 'fetchStats' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchStats() );
			return;
		} else if ($wgRequest->getVal( 'fetchMessage' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchMessage() );
			return;
		}

		$this->addStatsWidget();
		$this->addStandingsWidget();
		$this->show();
		return;
	}
}

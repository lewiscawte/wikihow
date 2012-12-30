<?

class IntroImageAdder extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('IntroImageAdder');
	}

	/**	
	 * checkForIntroImage
	 * Checks an article to see if it contains an image in the intro section
	 **/
	function checkForIntroImage($t) {
		$r = Revision::newFromTitle($t);
		$intro = Article::getSection($r->getText(), 0);

		if (preg_match('/\[\[Image:(.*?)\]\]/', $intro)) {
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

		$fname = "IntroImageAdder::addIntroImage";
		wfProfileIn($fname); 

		$title = $wgRequest->getVal('iiatitle');
		$imgtag = "[[Image:".$v['imageFilename']."|{{BASEPAGENAME}}|right|251px]]";
		$json = '';

		$t = Title::newFromText($title);
		$r = Revision::newFromTitle($t);
		$intro = Article::getSection($r->getText(), 0);

		if (!preg_match('/\[\[Image:(.*?)\]\]/', $intro)) {
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

		wfProfileOut($fname); 
		return json_encode( $json );
		
	}

	function confirmationModal($iiatitle, $img) {
		global $wgOut, $wgParser, $Title, $wgServer;

		$fname = "IntroImageAdder::confirmationModal";
		wfProfileIn($fname); 

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
		wfProfileOut($fname); 
	}

	/**	
	 * getSearchTerms
	 * Passed in a title an removes stop words and unneccessary punctuation
	 **/
	function getSearchTerms($t) {
		$fname = "IntroImageAdder::getSearchTerms";
		wfProfileIn($fname);
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
		wfProfileOut($fname);
		return $s;
	}

	/**	
	 * getNext
	 * Get the next article to show
	 **/
	function getNext() {
		global $wgRequest, $wgUser;

		$fname = "IntroImageAdder::getNext";
		wfProfileIn($fname);

		$dbm = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);

		// mark skipped 
		if ($wgRequest->getVal('skip', null)) {
			$t = Title::newFromText($wgRequest->getVal('skip'));
			$id = $t->getArticleID();
			$dbm->update('imageadder', array('imageadder_skip=imageadder_skip+1', 'imageadder_skip_ts'=>wfTimestampNow()), 
				array('imageadder_page'=>$id));
		}
		$a = array();
		
		for ($i = 0; $i < 10; $i++) {
		
			$timediff = wfTimestampNow()-(60*60*24); //24 hours ago

			//NOTE SQL Queries are excluding pageid 5791 cause it's a Categories page, don't know why it's not in wikihow NS.
			$opts = array("imageadder_last_viewed < $timediff",'imageadder_page != 5791', 
				'imageadder_skip < 2', 'imageadder_hasimage' => 0
			);

			// handle the cat filter if they have it selected
			$cat = $wgUser->getCatFilter(); 
			$tables = array('imageadder'); 
			if ($cat != "") {
				$tables[] = 'page';
				$opts[] = preg_replace("@.*AND@", "", $cat);	
				$opts[] = "imageadder_page = page_id";
			}


			if (mt_rand(0,9) < 7) {
				//ORDER BY PAGE_COUNTER
				$pageid = $dbr->selectField($tables, 'imageadder_page',  
					$opts,			
					"IntroImageAdder::getNext", 
					array("ORDER BY" => "imageadder_page_counter DESC", "LIMIT" => 1));
			} else {
				//ORDER BY PAGE_TOUCHED
				$pageid = $dbr->selectField($tables, array('imageadder_page'), 
					$opts,
					"IntroImageAdder::getNext", 
					array("ORDER BY" => "imageadder_page_touched DESC", "LIMIT" => 1));
			}

			//No articles need images?
			if (empty($pageid)) continue;			
			
			/*
			 * XXNOTE: One day when we can prefetch search terms we will do this instead of call the function
			 * $sql = "SELECT imageadder_page,imageadder_terms from imageadder where imageadder_inuse != 1";
			 * $res = $dbr->query($sql);
			 */
			$dbm->update('imageadder', array('imageadder_last_viewed'=>wfTimestampNow()),array('imageadder_page'=>$pageid));			

			$t = Title::newFromID($pageid);
			if (!$t) continue;

			//prove false
			$b_good = true;
	
			//valid article?
			if ($t->getArticleId() == 0) $b_good = false;
			
			//does it have an intro image already?
			if ($this->checkForIntroImage( $t ) ) {
	 			$b_good = false;
				$dbw = wfGetDB(DB_MASTER);
				$dbw->update('imageadder', array('imageadder_hasimage'=>1), array("imageadder_page"=>$pageid));
			}
			
			//is this a redirect?
			$article = new Article($t);
			if ($article->isRedirect()) $b_good = false;
			
			if ($b_good) {
				$a['aid'] = $t->getArticleId();
				$a['title'] = $t->getText();
				$a['url'] = $t->getLocalURL();
				$a['terms'] = $this->getSearchTerms($t->getText());
				wfProfileOut($fname);
				return( $a );
			}
			else {
				//not be good; mark it skipped
				$dbm->update('imageadder', array('imageadder_skip=imageadder_skip+1', 'imageadder_skip_ts'=>wfTimestampNow()), 
					array('imageadder_page'=>$t->getArticleId()));
			}
		}
	
		//send error msg
		$a['aid'] = '0';
		$a['title'] = 'No articles need images';
		$a['url'] = '';
		$a['terms'] = 'fail whale';
		wfProfileOut($fname);
		return $a;
	}

	/**	
	 * show
	 * Display the main window.  Right now it's skinless
	 **/
	function show() {
		global $wgOut, $wgUser;

		$fname = "Introimageadder::show";
		wfProfileIn($fname);
	
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
		wfProfileOut($fname);
		return;
	}

	/**	
	 * addStatsWidget
	 * add stats widget to right rail
	 **/
	function addStatsWidget() {
		global $wgUser;
		$fname = "Introimageadder::addStatsWidget";
		wfProfileIn($fname);
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
		wfProfileOut($fname);
	}

	/**	
	 * addStandingsWidget
	 * add standings widget
	 **/
	function addStandingsWidget() {
		global $wgUser;
        $fname = "Introimageadder::addStandingsWidget";
        wfProfileIn($fname);

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
		wfProfileOut($fname);
	}

	/**	
	 * getStandingsTable
	 * get standings table dynamically
	 **/
	function getStandingsTable() {
		global $wgUser;
        $fname = "Introimageadder::getStandingsTable";
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

	function getStandingsFromCache() {
		global $wgMemc;
		$fname = "Introimageadder::getStandingsFromCache";
		wfProfileIn($fname);
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
		wfProfileOut($fname);
		return $standings;
	}

	function getStanding($user) {	
        $fname = "Introimageadder::getStanding";
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

	/**	
	 * fetchStats
	 * get the use stats
	 **/
	function fetchStats() {
		global $wgUser, $wgMemc;
        $fname = "Introimageadder::fetchStats";
        wfProfileIn($fname);

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

		wfProfileOut($fname);
		return $s_arr;
	}

	/**	
	 * fetchArticle
	 * Gets the next article (getNext) and prepares to return it in a json object with stats
	 * and corresponding message
	 **/
	function fetchArticle() {
        $fname = "Introimageadder::fetchArticle";
        wfProfileIn($fname);
		$a = $this->getNext();
        wfProfileOut($fname);
		return $a;
	}

	/**
	 * EXECUTE
	 **/
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
        $fname = "Introimageadder::execute";
        wfProfileIn($fname);
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
        	wfProfileOut($fname);
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
        	wfProfileOut($fname);
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
        	wfProfileOut($fname);
			return;
		} else if ($wgRequest->getVal( 'confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('iiatitle'), $wgRequest->getVal('imgtag') ) ;
        	wfProfileOut($fname);
			return;
		} else if ($wgRequest->getVal( 'standingsTable' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->getStandingsTable() ;
        	wfProfileOut($fname);
			return;
		} else if ($wgRequest->getVal( 'fetchStats' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchStats() );
        	wfProfileOut($fname);
			return;
		} else if ($wgRequest->getVal( 'fetchMessage' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode( $this->fetchMessage() );
        	wfProfileOut($fname);
			return;
		}

		$wgOut->setHTMLTitle('Intro Image Adder - wikiHow');

		$this->addStatsWidget();
		$this->addStandingsWidget();
		$this->show();
        wfProfileOut($fname);
		return;
	}
}

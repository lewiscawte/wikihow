<?php
require_once('WikiHow.php');
	
class ProfileBox extends UnlistedSpecialPage {
	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ProfileBox' );
	}


	/***************************
	 **
	 **
	 ***************************/
	function getPBTitle() {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

		$name = "";
/*
 		$real_name = User::whoIsReal(User::idFromName($wgTitle->getDBKey()));
		$name = $wgTitle->getDBKey();
		if ( $real_name != "") {
			//$this->set("pagetitle", $real_name);
			$name = $real_name;
		}
*/

		$name .= wfMsg('profilebox-name');
		$name .= " for ". $wgUser->getName();
		$avatar = Avatar::getPicture($wgUser->getName());

		if ($wgUser->getID() > 0) {
			$pbDate = gmdate('M d, Y',wfTimestamp(TS_UNIX,$wgUser->getRegistration()));
		}

		 
		$heading = $avatar . "<div id='avatarNameWrap'><h1 class=\"firstHeading\">" . $name . "</h1><div id='regdate'>Joined wikiHow: ".$pbDate."</div></div><div style='clear: both;'> </div><br />";

		return $heading;

	}

	/***************************
	 **
	 **
	 ***************************/
	function displayBox($u) {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

		$display = "";

		//$display .= ProfileBox::getPBTitle();

		$t = Title::newFromText($u->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$live = $r->getText();
			$live = strip_tags($live, '<p><br><b><i>');
		}
		$t = Title::newFromText($u->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$occupation = $r->getText();
			$occupation = strip_tags($occupation, '<p><br><b><i>');
		}
		$t = Title::newFromText($u->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$aboutme = $r->getText();
			$aboutme = strip_tags($aboutme, '<p><br><b><i>');
			$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
			$aboutme = stripslashes($aboutme);
		}
		if ($u->getOption('profilebox_stats') == 1) { $checkStats = 'true'; }
		else { $checkStats = 'false'; }

		if ($u->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'true'; }
		else { $checkStartedEdited = 'false'; }

		if ($u->getOption('profilebox_favs') == 1) { $checkFavs = 'true'; }
		else { $checkFavs = 'false'; }

		$profilebox_name = wfMsg('profilebox-name');
		$profilebox_contributions = wfMsg('profilebox-contributions');
		$profilebox_edited_more = wfMsg('profilebox-edited-more');

		$display = "
<script language='javascript' src='/extensions/wikihow/profilebox.js?1'></script>
<link rel='stylesheet' media='all' href='/extensions/wikihow/profilebox.css' type='text/css' />

<script language='javascript'>
	var profilebox_username = '".$u->getName()."';
	var profilebox_name = '$profilebox_name';
	var msg_contributions = '$profilebox_contributions';
	var msg_edited_more = '$profilebox_edited_more';
	var pbstats_check = $checkStats;
	var pbstartededited_check = $checkStartedEdited;
	var pbfavs_check = false;
</script>\n";


		$display .= "<div id='profileBoxID'>\n";
		$display .= "<div class='article_inner'>";

		if (($live != '') || ($occupation != '') || ($aboutme != '')) {
			$display .= " <span id='profileBoxInfo' class='profileBoxInfo' style='display:block;'>\n";
			if ($live != '') {
				$display .= "<p><strong>Location:</strong> ".$live."</p>\n";
			}
			if ($occupation != '') {
				if (preg_match('/^http:\/\//',$occupation, $matches)) {
					$display .= "<p><strong>My Blog/Website:</strong> <a href='".$occupation."' rel='nofollow'>".$occupation."</a></p>\n";
				} else {
					$display .= "<p><strong>My Blog/Website:</strong> ".$occupation."</p>\n";
				}
			}
			if ($aboutme != '') {
				$display .= "<p><strong>About me:</strong><br />".$aboutme."</p>\n";
			}
	
			$display .= "</span>";
		}
	
		if ($u->getOption('profilebox_stats') == 1) {
			$display .= "
	
<div id='profileBoxStats' class='profileBoxStats' style='display:block;'>
<span><strong>My wikiHow Stats:</strong></span><br />
<span style='font-size:70%;'><em>(updated daily)</em></span><br /><br />
<div id='profileBoxStatsContent'></div>
</div>";
		} else {
			//$display .= "<div></div>\n";
		}
			
		$display .=" <div style='clear:both'></div>";
		$display .="</div><!--end article_inner-->";

		if ($u->getOption('profilebox_startedEdited') == 1) {
			$display .= "
<div id='pbTabs'>
<ul class='menu_tabbed'>
	<li><a id='pbTab1' class='selected' onclick='pbTabOn(this); return false;' href='#'>Articles Started</a></li>
	<li><a id='pbTab2' onclick='pbTabOn(this); return false;' href='#'>Article Edits</a></li>
</ul>
<div id='pbTabsContent'> </div>
</div>";
		}

/*
		if ($u->getOption('profilebox_favs') == 1) {
			$display .= "
<div id='pbFavs' class='profileBox'>
<strong>Favorite Articles:</strong><br />
<div id='pbFavsContent'></div>
</div>";
		}
*/

		$display .="
<div id='pbTalkpage' style='float:right; padding-top:10px; margin-right:27px'><a href='/".$u->getTalkPage()."'>Go to My Talk Page &raquo;</a></div>
<div style='clear:both'></div>

<script language='javascript'>
if (typeof jQuery == 'undefined') {
	Event.observe(window, 'load', pbInit);
} else {
	jQuery(window).load(pbInit);
}
</script>

</div>

		";


		return $display;
	}


	/***************************
	 **
	 **
	 ***************************/
	function displayForm() {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

		$wgOut->addHTML($this->getPBTitle());

		$live = '';
		$occupation = '';
		$aboutme = '';
		if ($wgUser->getOption('profilebox_display') == 1) { 
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-live');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$live = $r->getText();
			}
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$occupation = $r->getText();
			}
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$aboutme = $r->getText();
				$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
				$aboutme = stripslashes($aboutme);
			}

			if ($wgUser->getOption('profilebox_stats') == 1) { $checkStats = 'CHECKED'; }
			if ($wgUser->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'CHECKED'; }
			if ($wgUser->getOption('profilebox_favs') == 1) { $checkFavs = 'CHECKED'; }

			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav1'))) {
				if ($t->getArticleId() > 0) {
					$fav1 = $t->getText();
					$fav1id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav2'))) {
				if ($t->getArticleId() > 0) {
					$fav2 = $t->getText();
					$fav2id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav3'))) {
				if ($t->getArticleId() > 0) {
					$fav3 = $t->getText();
					$fav3id = $t->getArticleId();
				}
			}
	
		} else {
			$checkStats = 'CHECKED'; 
			$checkStartedEdited = 'CHECKED';
			$checkFavs = 'CHECKED';
		}


		$wgOut->addHTML("
<!-- <script language='javascript' src='/extensions/wikihow/cropper/lib/scriptaculous.js'></script> -->
<script>jQuery.noConflict();</script>
<script type='text/javascript' src='/extensions/wikihow/prototype1.8.2/p.js'></script>
<script language='javascript' src='/extensions/wikihow/profilebox.js?1'></script>
<link rel='stylesheet' media='all' href='/extensions/wikihow/profilebox.css' type='text/css' />


<form method='post' name='profileBoxForm'>
<div class='profileBox'>
<strong>Demographic information (Optional):</strong><br /><br />
<table width='100%' >
<tr>
	<td width='120'>Location:</td>
	<td width='530'><input type='text' name='live' value='".$live."'></td>
</tr>
<tr>
	<td>My Blog/Website:</td>
	<td><input type='text' name='occupation' value='".$occupation."'></td>
</tr>
<tr>
	<td valign='top'>About me:</td>
	<td><textarea name='aboutme' cols='55' rows='5' style='overflow:auto;padding-left:0px;' >".$aboutme."</textarea></td>
</tr>
</table>
</div>

<div class='profileBox'>
<strong>Display the following information on my user page (highly recommended):</strong> <br /><br />
<input type='checkbox' name='articleStats' ".$checkStats."> ".wfMsg('profilebox-checkbox-stats')."<br />
<input type='checkbox' name='articleStartedEdited' ".$checkStartedEdited."> ".wfMsg('profilebox-checkbox-startededited')."<br />
");
/*
<input type='checkbox' name='articleFavs' ".$checkFavs." > ".wfMsg('profilebox-checkbox-favs')."<br />
				<input type='text' id='pbFav1' name='pbFav1' value='".$fav1."' class='selectFavs'> <a onclick='deleteFav(1);'>X</a><br />
				<div id='autocomplete_choices1' class='autocomplete'></div>
				<input type='text' id='pbFav2' name='pbFav2' value='".$fav2."' class='selectFavs'> <a onclick='deleteFav(2);'>X</a><br />
				<div id='autocomplete_choices2' class='autocomplete'></div>
				<input type='text' id='pbFav3' name='pbFav3' value='".$fav3."' class='selectFavs'> <a onclick='deleteFav(3);'>X</a><br />
				<div id='autocomplete_choices3' class='autocomplete'></div>
<input type='hidden' id='fav1' name='fav1' value='".$fav1id."' >
<input type='hidden' id='fav2' name='fav2' value='".$fav2id."' >
<input type='hidden' id='fav3' name='fav3' value='".$fav3id."' >
*/

$wgOut->addHTML("
<br />

<!-- <input type='checkbox' name='recentTalkpage'> Most recent talk page messages<br /> -->
</div>
<br />

<span style='float:right;margin-right:20px;'><input class='button white_button_100 submit_button' type='submit' id='gatProfileSaveButton' name='save' value='Save' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' /> <a href='/".$wgUser->getUserPage()."' style='line-height:27px;'>Cancel</a></span>
		
</form>

<script language='javascript'>
Event.observe(window, 'load', pbInitForm);
</script>

");
	}


	/***************************
	 **
	 **
	 ***************************/
	function pbConfig() {
		global $wgUser, $wgRequest, $wgOut;

			$live = mysql_real_escape_string(strip_tags($wgRequest->getVal('live'), '<p><br><b><i>'));
			$occupation = mysql_real_escape_string(strip_tags($wgRequest->getVal('occupation'), '<p><br><b><i>'));
			$aboutme = mysql_real_escape_string(strip_tags($wgRequest->getVal('aboutme'), '<p><br><b><i>'));

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-live');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($live, 'profilebox-live-update', true, $watch);
			} else {
				$article->insertNewArticle($live, 'profilebox-live-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($occupation, 'profilebox-occupation-update', true, $watch);
			} else {
				$article->insertNewArticle($occupation, 'profilebox-occupation-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($aboutme, 'profilebox-aboutme-update', true, $watch);
			} else {
				$article->insertNewArticle($aboutme, 'profilebox-aboutme-update', true, $watch, false, false, true);
			}

		$userpageurl = $wgUser->getUserPage() . '';
		$t = Title::newFromText( $userpageurl, NS_USER );
		$article = new Article($t);
		$userpage = " \n";
		if ($t->getArticleId() > 0) {
			/*
			$r = Revision::newFromTitle($t);
			$curtext .= $r->getText();

			if (!preg_match('/<!-- blank -->/',$curtext)) {
				$userpage .= $curtext;
				$article->updateArticle($userpage, 'profilebox-userpage-update', true, $watch);
			}
			*/
		} else {
			$article->insertNewArticle($userpage, 'profilebox-userpage-update', true, $watch, false, false, true);
		}

		$wgUser->setOption('profilebox_fav1', $wgRequest->getVal('fav1'));
		$wgUser->setOption('profilebox_fav2', $wgRequest->getVal('fav2'));
		$wgUser->setOption('profilebox_fav3', $wgRequest->getVal('fav3'));

		if ($wgRequest->getVal('articleStats') == 'on') {
			$wgUser->setOption('profilebox_stats', 1);
		} else {
			$wgUser->setOption('profilebox_stats', 0);
		}

		if ($wgRequest->getVal('articleStartedEdited') == 'on') {
			$wgUser->setOption('profilebox_startedEdited', 1);
		} else {
			$wgUser->setOption('profilebox_startedEdited', 0);
		}

/*
		if ( ($wgRequest->getVal('articleFavs') == 'on') &&
				($wgRequest->getVal('fav1') || $wgRequest->getVal('fav2') || $wgRequest->getVal('fav3')) )
		{
			$wgUser->setOption('profilebox_favs', 1);
		} else {
			$wgUser->setOption('profilebox_favs', 0);
		}
*/
		 
		$wgUser->setOption('profilebox_display', 1);

		$wgUser->saveSettings();
		
	}

	/***************************
	 **
	 **
	 ***************************/
	function removeData() {
		global $wgUser, $wgRequest;

		$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-live-empty' );
			}
		}

		$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-occupation-empty');
			}
		}

		$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-aboutme-empty');
			}
		}

		$wgUser->setOption('profilebox_stats', 0);
		$wgUser->setOption('profilebox_startedEdited', 0);
		$wgUser->setOption('profilebox_favs', 0);

		$wgUser->setOption('profilebox_fav1', 0);
		$wgUser->setOption('profilebox_fav2', 0);
		$wgUser->setOption('profilebox_fav3', 0);

		$wgUser->setOption('profilebox_display', 0);
		$wgUser->saveSettings();

		return "SUCCESS";
		
	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchStats($pagename) {
		global $wgUser;
		
     	$dbr = wfGetDB(DB_SLAVE);
     	$dbw = wfGetDB(DB_MASTER);
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('profilebox_ajax_error');
			return;
		}

		$cachetime = 86400;
		if ($wgUser->getID() == $u->getID()) {
			$cachetime = 60;
		}
		
		$updateflag = 0;
		$response = array();
		$sql = "select *  from profilebox where pb_user=".$u->getID();
		$res = $dbr->query($sql);
		if ($row=$dbr->fetchObject($res)) {
			$now = time();
			$last = strtotime($row->pb_lastUpdated . " UTC");
			$diff = $now - $last;

			if ($diff <= $cachetime) {
				$response['created'] = number_format($row->pb_started, 0, "", ",");
				$response['edited'] = number_format($row->pb_edits, 0, "", ",");
				$response['patrolled'] = number_format($row->pb_patrolled, 0, "", ",");
				$response['viewership'] = number_format($row->pb_viewership, 0, "", ",");
				$response['uid'] = $u->getID();
				$response['contribpage'] = "/Special:Contributions/" . $u->getName();

				echo json_encode($response);
				return;
			} else {
				$updateflag = 1;
			}
		} else {
			$updateflag = 1;
		}

		if ($updateflag) {
			$options = array("fe_user_text='" . $u->getName() . "'");
			$created = $dbr->selectField('firstedit', 'count(*)', $options, 'pbCreated');

			$options = array('log_user=' . $u->getID(), 'log_type' => 'patrol');
			$patrolled = $dbr->selectField('logging', 'count(*)', $options, "pbPatrolled");

			$edited = User::getAuthorStats($u->getName());

			$viewership = 0;
			$vsql = "select sum(distinct(page_counter)) as viewership from page,revision where page_namespace=0 and page_id=rev_page and rev_user=".$u->getID()." and rev_user_text='".$u->getName()."'";
			//More accurate but will take longer
			//$vsql = "select sum(distinct(page_counter)) as viewership from page,revision where page_namespace=0 and page_id=rev_page and rev_user=".$u->getID()." GROUP BY rev_page;
			$vres = $dbr->query($vsql);
			while ($row=$dbr->fetchObject($vres)) {
				$viewership += $row->viewership;
			}

			$sql = "INSERT INTO profilebox (pb_user,pb_started,pb_edits,pb_patrolled,pb_viewership,pb_lastUpdated) ";
			$sql .= "VALUES (".$u->getID().",$created, $edited, $patrolled, $viewership, '".wfTimestampNow()."') ";
			$sql .= "ON DUPLICATE KEY UPDATE pb_started=$created,pb_edits=$edited,pb_patrolled=$patrolled,pb_viewership=$viewership,pb_lastUpdated='".wfTimestampNow()."'";
			$res = $dbw->query($sql);

			$response['created'] = number_format($created, 0, "", ",");
			$response['edited'] = number_format($edited, 0, "", ",");
			$response['patrolled'] = number_format($patrolled, 0, "", ",");
			$response['viewership'] = number_format($viewership, 0, "", ",");
			$response['uid'] = $u->getID();
			$response['contribpage'] = "/Special:Contributions/" . $u->getName();

			echo json_encode($response);
		}

		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchCreated($pagename, $limit = '') {
     	$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);


		// GET FEATURED ARTICLES
		require_once('FeaturedArticles.php');
		$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
		$fares = $dbr->query($fasql);
		while ($row=$dbr->fetchObject($fares)) {
			$fa[ $row->page_title ] = 1;
		}

		// DB CALL
		$order = array();
		$order['ORDER BY'] = 'fe_timestamp DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('firstedit','page'),
			array ('page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'),
			array ('fe_page=page_id', 'fe_user_text' => $t->getText(), "page_title not like 'Youtube%'"),
			"",
			$order
			);
		$display = "<table class='pbTable' cellspacing='0'>\n";
		$display .= "
<tr class='pbTableHeader'>
	<th class='pbTableTitle'>Article Title</th>
	<th class='pbTableViews'>Views</th>
	<th  class='pbTableRS'>Rising Star</th>
	<th class='pbTableFA'>Featured</th>
</tr>\n";
		while ($row=$dbr->fetchObject($res)) {
			
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$t->getArticleID(), 'pl_list'=>'risingstar')) > 0;
			$risingstar = "";
			if ($rs) {
				$risingstar = "<img src='/extensions/wikihow/star-green.png' height='20px' width='20px'>";
			}

			if ($fa[ $t->getDBKey() ]) {
				//$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
				$featured = "<img src='/extensions/wikihow/star-blue.png' height='20px' width='20px'>";
			} else {
				$featured = "";
			}
			

			if ($t->getArticleID() > 0)  {
				$display .= "  <tr>\n";
				$display .= "    <td><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
				$display .= "    <td align='center'>".number_format($row->page_counter, 0, '',',') ."</td>\n";
				$display .= "    <td align='center'>$risingstar</td>\n";
				$display .= "    <td align='center'>$featured</td>\n";
				$display .= "  </tr>\n";
			}
		}
		$display .= "</table>\n";
		$dbr->freeResult($res);

		echo $display;
		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchEdited($pagename, $limit = '') {
     	$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);

		// GET FEATURED ARTICLES
		require_once('FeaturedArticles.php');
		$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
		$fares = $dbr->query($fasql);
		while ($row=$dbr->fetchObject($fares)) {
			$fa[ $row->page_title ] = 1;
		}

		// DB CALL
		$order = array();
		$order['ORDER BY'] = 'rev_timestamp DESC';
		$order['GROUP BY'] = 'page_title';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('revision','page'),
			array ('page_id', 'page_title', 'page_namespace', 'rev_timestamp', 'page_counter'),
			array ('rev_page=page_id', 'rev_user_text' => $t->getText(), 'page_namespace' => NS_MAIN),
			"",
			$order
			);

		$display = "<table class='pbTable' cellspacing='0'>\n";
		$display .= "
<tr class='pbTableHeader'>
	<th class='pbTableTitle'>Article Title</th>
	<th class='pbTableViews'>Views</th>
	<th class='pbTableRS'>Rising Star</th>
	<th class='pbTableFA'>Featured</th>
</tr>\n";
		while ($row=$dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);

			$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$t->getArticleID(), 'pl_list'=>'risingstar')) > 0;
			$risingstar = "";
			if ($rs) {
				$risingstar = "<img src='/extensions/wikihow/star-green.png' height='20px' width='20px'>";
			}

			if ($fa[ $t->getPartialURL() ]) {
				//$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
				$featured = "<img src='/extensions/wikihow/star-blue.png' height='20px' width='20px'>";
			} else {
				$featured = "";
			}
			
			if ($t->getArticleID() > 0)  {
				$display .= "  <tr>\n";
				$display .= "    <td><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
				$display .= "    <td align='center'>".number_format($row->page_counter, 0, '',',') ."</td>\n";
				$display .= "    <td align='center'>$risingstar</td>\n";
				$display .= "    <td align='center'>$featured</td>\n";
				$display .= "  </tr>\n";
			}
		}
		$display .= "</table>\n";
		$dbr->freeResult($res);

		echo $display;
		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchFavs($pagename) {
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('profilebox_ajax_error');
			return;
		}

		$display = "";

		for ($i=1;$i<=3;$i++) {
			$fav = 'profilebox_fav'.$i;
			$page_id = '';
			$page_id = $u->getOption($fav);

			if ($page_id) {
				$t = Title::newFromID($page_id);
				if ($t->getArticleID() > 0)  {
					$display .= "<a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a><br />\n";
				}
			}
		}
		
		echo $display;
		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function favsTitleSelector() {
		global $wgRequest;
     	$dbr = wfGetDB(DB_SLAVE);
		$name = preg_replace('/ /','-', strtoupper($wgRequest->getVal('pbTitle')));

		$order = array();
		//$order['ORDER BY'] = 'page_timestamp DESC';
		$order['LIMIT'] = '6';

		$res = $dbr->select(
			array('page'),
			array ('page_id','page_title'),
			array ("UPPER(page_title) like '%".$name."%'", 'page_namespace' => NS_MAIN),
			"",
			$order
			);
		$display = "<ul>\n";
		//$display .= "  <li>" . $name . "</li>\n";
		while ($row=$dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($t->getArticleID() > 0)  {
				$display .= "  <li id=".$row->page_id.">" . $t->getFullText() . "</li>\n";
			}
		}
		$display .= "</ul>\n";
		$dbr->freeResult($res);

		echo $display;
		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function execute ($par ) {
		global $wgUser, $wgOut, $wgTitle, $wgServer, $wgRequest, $IP; 

		$type = $wgRequest->getVal('type');
		$wgOut->setArticleBodyOnly(true); 

		//Just Display Box - Can probably delete now that it's being loaded in the skin.
		if ($type == 'display') {
			//$wgOut->setArticleBodyOnly(true); 
			$wgOut->addHTML($this->displayBox());
			return;
		} else if ($type == 'favsselector') {
			$wgOut->setArticleBodyOnly(true); 
			$this->favsTitleSelector();
			return;
		} else if ($type == 'ajax') {
			$wgOut->setArticleBodyOnly(true); 
			$element = $wgRequest->getVal('element');
			$pagename = $wgRequest->getVal('pagename');
			if (($element != '') && ($pagename != '')) {
				switch($element) {
					case 'stats':
						$this->fetchStats($pagename);
						break;
					case 'created':
						$this->fetchCreated($pagename, 5);
						break;
					case 'createdall':
						$this->fetchCreated($pagename, 100);
						break;
					case 'edited':
						$this->fetchEdited($pagename, 5);
						break;
					case 'editedall':
						$this->fetchEdited($pagename, 100);
						break;
					case 'favs':
						$this->fetchFavs($pagename);
						break;
					default:
						wfDebug("ProfileBox ajax requesting  unknown element: $element \n");
				}
			}
			return;
		}

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if( $wgUser->getID() == 0) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$wgOut->setArticleBodyOnly(true); 

		if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true); 
			$this->pbConfig();

			$t = $wgUser->getUserPage();
			$wgOut->redirect($t->getFullURL());
		} else if ($type == 'remove') {
			$wgOut->setArticleBodyOnly(true); 
			$this->removeData();
			$wgOut->addHTML("SUCCESS");
		} else {
			$wgOut->setArticleBodyOnly(false); 
			$this->displayForm() ;
		}

	}
}
				

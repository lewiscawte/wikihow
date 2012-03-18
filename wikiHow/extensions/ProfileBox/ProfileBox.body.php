<?php
require_once('WikiHow.php');

class ProfileBox extends UnlistedSpecialPage {

	var $featuredArticles;

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

		$name .= wfMsg('profilebox-name');
		$name .= " for ". $wgUser->getName();
		$avatar = Avatar::getPicture($wgUser->getName());

		if ($wgUser->getID() > 0) {
			$pbDate = gmdate('M d, Y',wfTimestamp(TS_UNIX,$wgUser->getRegistration()));
		}


		$heading = $avatar . "<div id='avatarNameWrap'><h1 class=\"firstHeading\">" . $name . "</h1><div id='regdate'>Joined wikiHow: ".$pbDate."</div></div><div style='clear: both;'> </div><br />";

		return $heading;

	}

	function getStatsDispalay($stats, $username){
		global $wgUser;

		wfLoadExtensionMessages('ProfileBox');

		$sk = $wgUser->getSkin();

		$display = "";

		if($stats['nab'] == 1 || $stats['admin'] == 1 || $stats['fa'] == 1 || $stats['created'] != 0 || $stats['edited'] != 0 || $stats['patrolled'] != 0 || $stats['viewership'] != 0){

			$display .= "<div id='profileBoxStats' class='pb-stats' style='display:block;'>";

			$right = 15;
			if($stats['nab'] == 1){
				$display .= "<a href='/Special:ProfileBadges'><div class='pb-nab pb-badge' style='right:{$right}px'></div></a>";
				$right += 75;
			}
			if($stats['admin'] == 1){
				$display .= "<a href='/Special:ProfileBadges'><div class='pb-admin pb-badge' style='right:{$right}px'></div></a>";
				$right += 75;
			}
			if($stats['fa'] == 1){
				$display .= "<a href='/Special:ProfileBadges'><div class='pb-fa pb-badge' style='right:{$right}px'></div></a>";
				$right += 75;
			}

			$display .= "<span><strong>My wikiHow Stats:</strong></span><table id='profileBoxStatsContent'>";

			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );

			$count = 0;

			$display .= "<tr>";
			if($stats['created'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['created'] . " " . $sk->makeKnownLinkObj( $contribsPage , 'Articles Started' ) . "</td>";
				$count++;
			}
			if($stats['edited'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['edited'] . " Article Edits</a></td>";
				$count++;
			}

			if($count == 2)
				$display .= "</tr><tr>";
			if($stats['patrolled'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['patrolled'] . " <a href='/Special:Log?type=patrol&user=" . $username . "'>Edits Patrolled</a></td>";
				$count++;
			}
			if($count == 2)
				$display .= "</tr><tr>";
			if($stats['viewership'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['viewership'] . " Article Views</td>";
				$count++;
			}
		
			$display .= "</tr></table></div>";

		}

		return $display;
	}

	/***************************
	 **
	 **
	 ***************************/
	function displayBox($u) {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

		wfLoadExtensionMessages('ProfileBox');

		$display = "";

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
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox.css?') . WH_SITEREV . "' type='text/css' />

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

		if ($aboutme != '') {
			$display .= "<p id='pb-aboutme'><strong>About me. </strong>".$aboutme."</p>\n";
		}

		if($checkStats == 'true' || $checkStartedEdited == 'true')
			$stats = self::fetchStats("User:" . $u->getName());
		if ($checkStats == 'true'){

			$display .= ProfileBox::getStatsDispalay($stats, $u->getName());
		}

		$display .= "<div class='clearall'></div>";
		$display .="</div><!--end article_inner-->";

		if ($checkStartedEdited == 'true') {
			$data = self::fetchCreatedData(mysql_real_escape_string("User:" . $wgTitle->getText()), 6);
			$hasMoreCreated = count($data) > 5 ? true : false;
			$createdHtml = self::fetchCreated($data, 5);

			$display .= "
				<table class='pb-articles' id='pb-created' cellspacing='0' cellpadding='0'>
					<thead><tr>
						<th class='first pb-title'><strong>Articles Started</strong> (" . $stats['created'] . ")</th>
						<th class='middle pb-star'>Rising Stars</th>
						<th class='middle pb-feature'>Featured</th>
						<th class='last pb-view'>Views</th></tr></thead>";

			$display .= "<tbody>" . $createdHtml . "</tbody>";
			$display .= "<tfoot>";
			if($hasMoreCreated)
				$display .= "<tr><td class='pb-title'><a href='#' id='created_more' onclick='pbShow_articlesCreated(\"more\"); return false;'>View more &raquo;</a><a href='#' id='created_less' style='display:none;' onClick='pbShow_articlesCreated(); return false;'><< View Less</a></td><td colspan='3' class='pb-view'>&nbsp;</td></tr>";
			$display .= "<tr class='pb-footer'><td style='border:none;' colspan='4'><img alt='' src='" . wfGetPad("/skins/WikiHow/images/sttable_bottom.png") . "' /></td></tr></tfoot>";
			$display .="</table>";
/*
			$display .= "
				<table class='pb-articles' id='pb-edited' cellspacing='0' cellpadding='0'>
					<thead>
					<tr>
						<th class='first pb-title'><strong>Edits</strong> (" . $stats['edited'] . ")</th>
						<th class='middle pb-star'>Rising Stars</th>
						<th class='middle pb-feature'>Featured</th>
						<th class='last pb-view'>Views</th></tr></thead>";
			$display .= "<tbody>" . ProfileBox::fetchEdited("User:" . $wgTitle->getText(), 5) . "</tbody>";
			$display .= "<tfoot><tr><td class='pb-title'><a href='#' id='edit_more' onclick='pbShow_articlesEdited(\"more\"); return false;'>View most recent 100 &raquo;</a><a href='#' id='edit_less' style='display:none;' onclick='pbShow_articlesEdited(); return false;'><< View Less</a></td><td colspan='3' class='pb-view'>&nbsp;</td></tr>";
			$display .= "<tr class='pb-footer'><td style='border:none;' colspan='4'><img alt='' src='" . wfGetPad("/skins/WikiHow/images/sttable_bottom.png") . "' /></td></tr></tfoot>";
			$display .="</table>";
*/
			if (class_exists('ThumbsUp')) {
				$dataThumbs = self::fetchThumbsData(mysql_real_escape_string("User:" . $wgTitle->getText()), 6);
				$hasMoreThumbs = count($dataThumbs) > 5 ? true : false;
				$thumbsHtml = self::fetchThumbed($dataThumbs, 5);

				$display .= "
					<table class='pb-articles' id='pb-thumbed' cellspacing='0' cellpadding='0'>
						<thead><tr>
							<th class='first pb-title'><strong>Thumbed Up Edits</strong></th>
							<!--<th class='middle pb-feature'>My Edit</th>
							<th class='last pb-view'>Thumbs</th>-->
							<th class='last pb-view'>Date</th>
						</tr></thead>";
				$display .= "<tbody>" . $thumbsHtml . "</tbody>";
				$display .= "<tfoot>";
				if($hasMoreThumbs)
					$display .= "<tr><td class='pb-title'><a href='#' id='thumbed_more' onclick='pbShow_Thumbed(\"more\"); return false;'>View more &raquo;</a><a href='#' id='thumbed_less' style='display:none;' onClick='pbShow_Thumbed(); return false;'><< View Less</a></td><td colspan='1' class='pb-view'>&nbsp;</td></tr>";
				$display .= "<tr class='pb-footer'><td style='border:none;' colspan='2'><img alt='' src='" . wfGetPad("/skins/WikiHow/images/sttable_bottom.png") . "' /></td></tr></tfoot>";
				$display .="</table>";
			}
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
<div id='pbTalkpage' style='float:right; padding-top:10px; margin-right:27px; padding-bottom:10px;'><a href='/".$u->getTalkPage()."'>Go to My Talk Page &raquo;</a></div>
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
<!-- <script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/scriptaculous.js?') . WH_SITEREV . "'></script> -->
<script>jQuery.noConflict();</script>
<script type='text/javascript' src='" . wfGetPad('/extensions/wikihow/prototype1.8.2/p.js?') . WH_SITEREV . "'></script>
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox.css?') . WH_SITEREV . "' type='text/css' />


<form method='post' name='profileBoxForm'>
<div class='profileBox'>
<strong>Demographic information (Optional):</strong><br /><br />
<table width='100%' >
<tr>
	<td width='120'>Location:</td>
	<td width='530'><input class='loginText input_med' type='text' name='live' value='".$live."'></td>
</tr>
<tr>
	<td>My Blog/<br />Website:</td>
	<td><input class='loginText input_med' type='text' name='occupation' value='".$occupation."'></td>
</tr>
<tr>
	<td valign='top'>About me:</td>
	<td><textarea class='textarea_med' name='aboutme' cols='55' rows='5' style='overflow:auto;' >".$aboutme."</textarea></td>
</tr>
</table>
</div>

<div class='profileBox'>
<strong>Display the following information on my user page (highly recommended):</strong> <br /><br />
<input type='checkbox' name='articleStats' ".$checkStats."> ".wfMsg('profilebox-checkbox-stats')."<br /><br />
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

	function initProfileBox($user){

		$user->setOption('profilebox_fav1', "");
		$user->setOption('profilebox_fav2', "");
		$user->setOption('profilebox_fav3', "");


		$user->setOption('profilebox_stats', 1);

		$user->setOption('profilebox_startedEdited', 1);

		$user->setOption('profilebox_display', 1);

		$user->saveSettings();
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
			} else if($live != ''){
				$article->insertNewArticle($live, 'profilebox-live-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($occupation, 'profilebox-occupation-update', true, $watch);
			} else if($occupation != ''){
				$article->insertNewArticle($occupation, 'profilebox-occupation-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($aboutme, 'profilebox-aboutme-update', true, $watch);
			} else if($aboutme != ''){
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
		$row=$dbr->fetchObject($res);
		if ($row) {
			$now = time();
			$last = strtotime($row->pb_lastUpdated . " UTC");
			$diff = $now - $last;

			if (isset($row->pb_lastUpdated) && $diff <= $cachetime) {
				$response['created'] = number_format($row->pb_started, 0, "", ",");
				$response['edited'] = number_format($row->pb_edits, 0, "", ",");
				$response['patrolled'] = number_format($row->pb_patrolled, 0, "", ",");
				$response['viewership'] = number_format($row->pb_viewership, 0, "", ",");
				$response['uid'] = $u->getID();
				$response['contribpage'] = "/Special:Contributions/" . $u->getName();
				if (class_exists('ThumbsUp')) {
					$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
					$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
				}

				$updateflag = 0;
			} else {
				$updateflag = 1;
			}
		} else {
			$updateflag = 1;
		}

		if ($updateflag) {
			$options = array("fe_user='" . $u->getID() . "'");
			$created = $dbr->selectField('firstedit', 'count(*)', $options, 'pbCreated');

			$options = array('log_user=' . $u->getID(), 'log_type' => 'patrol');
			$patrolled = $dbr->selectField('logging', 'count(*)', $options, "pbPatrolled");

			$edited = User::getAuthorStats($u->getName());

			$viewership = 0;
			$vsql = "select sum(page_counter) as viewership from page,firstedit where page_namespace=0 and page_id=fe_page and fe_user=".$u->getID();
			//More accurate but will take longer
			//$vsql = "select sum(distinct(page_counter)) as viewership from page,revision where page_namespace=0 and page_id=rev_page and rev_user=".$u->getID()." GROUP BY rev_page;
			$vres = $dbr->query($vsql);
			while ($row1=$dbr->fetchObject($vres)) {
				$viewership += $row1->viewership;
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
			if (class_exists('ThumbsUp')) {
				$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
				$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
			}

		}

		//check badges
		$groups = $u->getGroups();
		$rights = $u->getRights();
		if ( in_array( 'sysop', $groups ) )
			$response['admin'] = 1;
		else
			$response['admin'] = 0;
		if( in_array('newarticlepatrol', $rights ) )
			$response['nab'] = 1;
		else
			$response['nab'] = 0;
		$resFA = $dbr->select(array('firstedit', 'templatelinks'), '*', array('fe_page=tl_from', 'fe_user' => $u->getID(), ('tl_title = "Fa" OR tl_title = "FA"') ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		$resRS = $dbr->select(array('firstedit', 'pagelist'), '*', array('fe_page=pl_page', 'fe_user' => $u->getID() ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		if($dbr->numRows($resFA) + $dbr->numRows($resRS) >= 5)
			$response['fa'] = 1;
		else
			$response['fa'] = 0;


		return $response;
	}

	function fetchThumbed($data, $limit = 5) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);
		$result = self::getThumbsTableHtml($data, $dbr, $limit);

		return $result;
	}

	function fetchThumbsData($username, $limit) {
		global $wgMemc, $wgUser;

		$username = urldecode($username);
		$cacheKey = wfMemcKey("pb-thumbs-$username-$limit");
		$result = $wgMemc->get($cacheKey);

		$profileOwner = $wgUser->getId() != 0 && 'User:' . $wgUser->getName() == $pagename;
		if (!$profileOwner && $result) {
			return $result;
		}

     	$dbr = wfGetDB(DB_SLAVE);


		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['GROUP BY'] = 'thumb_rev_id';
		$order['ORDER BY'] = 'rev_id DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('thumbs','page', 'revision'),
			array ('page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id', 'rev_timestamp'),
			array ('thumb_recipient_id' => $u->getID(), 'thumb_exclude=0', 'thumb_page_id=page_id', 'thumb_rev_id = rev_id'),
			"",
			$order
			);

		while($row = $dbr->fetchRow($res)){
			$results[] = $row;
		}

		$dbr->freeResult($res);

		$wgMemc->set($cacheKey, $results, 60*10);
		return $results;
	}

	function fetchTopEditData($username) {
     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName($username);

		$order = array();
		$order['GROUP BY'] = 'thumb_rev_id';
		$order['ORDER BY'] = 'cnt DESC';
		if ($limit) {
			$order['LIMIT'] = 1;
		}
		$res = $dbr->select(
			array('thumbs','page'),
			array ('page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id'),
			array ('thumb_recipient_id' => $u->getID(), 'thumb_exclude=0', 'thumb_page_id=page_id'),
			"",
			$order
			);

		return $res;
	}

	function getThumbsTableHtml(&$data, &$dbr, $limit = '') {
		global $wgUser, $wgTitle;

		$html = '';

		// Display the most-thumbed article at the top of the table
		/*$topRevid = -1;
		if ($row = $dbr->fetchObject($topRes)) {
			$topRevId = $row->thumb_rev_id;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($t->getArticleID() > 0)  {
				$html .= self::getThumbsRowHtml($t, $row);
			}
		}*/

		// Show the most recent thumbs up
		$count = 0;
			if(count($data) > 0){
			foreach($data as $row){
				if($limit != '' && $count >= $limit)
					break;
				$t = Title::makeTitle($row['page_namespace'], $row['page_title']);

				if ($t->getArticleID() > 0)  {
					$html .= self::getThumbsRowHtml($t, $row);
				}
				$count++;
			}
		}

		if (strlen($html) == 0) {
			$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
			if($profileOwner)
				$html .= "<tr><td class='pb-title'>" . wfMsgWikiHtml('pb-noedits') . "</td><td class='pb-view'>&nbsp;</td></tr>";
			else
				$html .= "<tr><td class='pb-title'>" . wfMsg('pb-noarticles-anon') . "</td><td class='pb-view'>&nbsp;</td></tr>";
		}
		return $html;
	}

	function getThumbsRowHtml(&$t, &$row) {
		global $wgUser;

		$sk = $wgUser->getSkin();

		$diff = $sk->makeKnownLinkObj($t, wfTimeAgo($row['rev_timestamp']),'diff=' . $row['thumb_rev_id'] . '&oldid=PREV');

		$html = "";
		$html .= "  <tr>";
		$html .= "    <td class='pb-title'><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
	//	$html .= "    <td class='pb-feature'>$diff</td>\n";
	//	$html .= "    <td class='pb-view'>".number_format($row->cnt, 0, '',',') ."</td>\n";
		$html .= "    <td class='pb-view'>$diff</td>\n";
		$html .= "  </tr>\n";
		return $html;
	}


	/***************************
	 **
	 **
	 ***************************/
	function fetchCreated(&$data, $limit = '') {
		$dbr = wfGetDB(DB_SLAVE);

		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES
			require_once('FeaturedArticles.php');
			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);

			while ($row=$dbr->fetchObject($fares)) {
				$this->featuredArticles[ $row->page_title ] = 1;
			}
		}

		// DB CALL
		//$res = self::fetchCreatedData(mysql_real_escape_string($pagename), $limit);

		return self::getTableHtml($data, $limit, $dbr);
	}

	function getTableHtml(&$data, $limit, &$dbr){
		global $wgUser, $wgTitle;

		$html = "";
		$count = 0;

		if(count($data) > 0){
			foreach ($data as $row) {
				if($limit != '' && $count >= $limit)
					break;

				$t = Title::makeTitle($row['page_namespace'], $row['page_title']);
				$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$t->getArticleID(), 'pl_list'=>'risingstar')) > 0;
				$risingstar = "";
				if ($rs) {
					$risingstar = "<img src='/extensions/wikihow/star-green.png' height='20px' width='20px'>";
				}
				else{
					$risingstar = "&nbsp;";
				}

				if ($this->featuredArticles[ $t->getDBKey() ]) {
					//$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
					$featured = "<img src='/extensions/wikihow/star-blue.png' height='17px' width='21px'>";
				} else {
					$featured = "&nbsp";
				}


				if ($t->getArticleID() > 0)  {
					$html .= "  <tr>";
					$html .= "    <td class='pb-title'><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
					$html .= "    <td class='pb-star'>$risingstar</td>";
					$html .= "    <td class='pb-feature'>$featured</td>";
					$html .= "    <td class='pb-view'>".number_format($row['page_counter'], 0, '',',') ."</td>\n";
					$html .= "  </tr>\n";
				}

				$count++;
			}
		}

		if($html == ""){
			$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
			if($profileOwner)
				$html .= "<tr><td class='pb-title' colspan='3'>" . wfMsgWikiHtml('pb-noarticles') . "</td><td class='pb-view'>&nbsp;</td></tr>";
			else
				$html .= "<tr><td class='pb-title' colspan='3'>" . wfMsg('pb-noarticles-anon') . "</td><td class='pb-view'>&nbsp;</td></tr>";
		}
		return $html;
	}

	/**
	 * Gets the sql result for articles created by the given user
	 */
	function fetchCreatedData($username, $limit){
		global $wgMemc, $wgUser;

		$result = $wgMemc->get(wfMemcKey("pb-fetchCreatedData" . $username . "-" . $limit));
		$profileOwner = $wgUser->getId() != 0 && 'User:' . $wgUser->getName() == $pagename;
		if (!profileOwner && $result) {
			return result;
		}

     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['ORDER BY'] = 'fe_timestamp DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('firstedit','page'),
			array ('page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'),
			array ('fe_page=page_id', 'fe_user' => $u->getID(), "page_title not like 'Youtube%'"),
			"",
			$order
			);

		while($row = $dbr->fetchRow($res)){
			$results[] = $row;
		}

		$dbr->freeResult($res);

		$wgMemc->get(wfMemcKey("pb-fetchCreatedData" . $username . "-" . $limit), $results);

		return $results;
	}



	/***************************
	 **
	 **
	 ***************************/
	function fetchEdited($pagename, $limit = '') {
     	$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);

		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES
			require_once('FeaturedArticles.php');
			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);
			while ($row=$dbr->fetchObject($fares)) {
				$fa[ $row->page_title ] = 1;
			}
		}

		// DB CALL
		$res = self::fetchEditedData($dbr->strencode($pagename), $limit);

		return self::getTableHtml($res, $dbr);
	}

	function fetchEditedData($username, $limit){
     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['ORDER BY'] = 'rev_timestamp DESC';
		$order['GROUP BY'] = 'page_title';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('revision','page'),
			array ('page_id', 'page_title', 'page_namespace', 'rev_timestamp', 'page_counter'),
			array ('rev_page=page_id', 'rev_user' => $u->getID(), 'page_namespace' => NS_MAIN),
			"",
			$order
			);

		return $res;
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
			$dbr = wfGetDB(DB_SLAVE);
			$pagename = $dbr->strencode($wgRequest->getVal('pagename'));
			if (($element != '') && ($pagename != '')) {
				switch($element) {
					case 'thumbed':
						$data = self::fetchThumbsData($pagename, 5);
						echo $this->fetchThumbed($data, 5);
						break;
					case 'thumbedall':
						$data = self::fetchThumbsData($pagename, 100);
						echo $this->fetchThumbed($data, 100);
						break;
					case 'stats':
						echo $this->fetchStats($pagename);
						break;
					case 'created':
						$data = self::fetchCreatedData($pagename, 5);
						echo $this->fetchCreated($data, 5);
						break;
					case 'createdall':
						$data = self::fetchCreatedData($pagename, 100);
						echo $this->fetchCreated($data, 100);
						break;
					/*case 'edited':
						echo $this->fetchEdited($pagename, 5);
						break;
					case 'editedall':
						echo $this->fetchEdited($pagename, 100);
						break;*/
					case 'favs':
						echo $this->fetchFavs($pagename);
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

	static function getPageTop($u){
		global $wgUser, $wgRequest;

		$profilebox_name = wfMsg('profilebox-name');
		if ($realName = User::whoIsReal($u->getId())) {
			$pbDate = "<div>Real Name: $realName</div>";
		}
		if ($u->getOption('profilebox_display'))  {
			$pbDate .="<div id='regdateProfilebox'>On wikiHow: ";
			if ($u->getRegistration() != '') {
				$pbDate .= ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,$u->getRegistration()));
			} else {
				$pbDate .= ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,'20060725043938'));
			}
			$action = $wgRequest->getVal('action', '');
			if ($u->getID() == $wgUser->getID() && $action != "history" && $action != "edit")   {
				$pbDate = "<div class='gatRelativeWrapper'><div id='gatEditRemoveButtons'><div id='gatEditRemoveButtonsInner'><a href='/Special:Profilebox' id='gatProfileEditButton' >Edit $profilebox_name</a> | <a href='#' onclick='removeUserPage();'>Remove $profilebox_name</a></div></div></div>" . $pbDate;
			}
			$pbDate .= "</div>";

			$t = Title::newFromText($u->getUserPage() . '/profilebox-live');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$live = $r->getText();
				if($live != "")
					$pbDate .= "<div>Location: " . $live . "</div>";
			}
			$t = Title::newFromText($u->getUserPage() . '/profilebox-occupation');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$occupation = $r->getText();
				if($occupation != "")
					$pbDate .= "<div style='margin-bottom:5px'>Website: <a href='" . $occupation . "'>" . $occupation . "</a></div>";
			}
		} else {
			$pbDate ="<div id='regdate' >";
			if ($u->getID() == $wgUser->getID())   {
				$pbDate .= "<input type='button' class='button white_button_100 submit_button' id='gatProfileCreateButton' onclick=\"window.location.href='/Special:Profilebox';\" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' value='Edit Profile' style='margin:0;' />";
			}
			$pbDate .= "</div>";
		}



		return $pbDate;
	}

	static function getMemberLength($joinDate){
		wfLoadExtensionMessages('RCWidget');
		if($joinDate != ''){

		}

		$now = time();
		$lengths = array("60","60","24","7","4.35","12");

		$periods = array(wfMsg("day-plural"), wfMsg("week-plural"), wfMsg("month-plural"), wfMsg("year-plural"));
		$period = array(wfMsg("day"), wfMsg("week"), wfMsg("month"), wfMsg("year"));

		$difference = $now - $joinDate;
		$difference /= 60*60*24; //take away milliseconds and seconds

		/*for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}*/
		$difference = round($difference);

		$over = "over ";
		if($difference < 1){ //today
			return "since today";
		}
		else if($difference < 7){ //this week (days)
			return $over . $difference . " " . ($difference>1?$periods[0]:$period[0]);
		}
		else{
			$difference = round($difference/7);
			if($difference < 4){ //this month (weeks)
				return $over . $difference . " " . ($difference>1?$periods[1]:$period[1]);
			}
			else{
				$difference = round($difference/4.35);
				if($difference < 12){ //this year (months)
					return $over . $difference . " " . ($difference>1?$periods[2]:$period[2]);
				}
				else{
					$years = floor($difference/12);
					$months = $difference % 12;

					return $over . $years . " " . ($years>1?$periods[3]:$period[3]) . " " . $months . " " . ($months>1?$periods[2]:$period[2]);
				}
			}

		}
	}
}


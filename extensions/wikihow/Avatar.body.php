<?php
require_once('WikiHow.php');

class Avatar extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Avatar' );
	}

	function displayManagement() {
		global $wgOut, $wgUser, $wgRequest;

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$inappropriate = wfMsg('avatar-rejection-ut-inappropriate');
		$copyright = wfMsg('avatar-rejection-ut-copyright');
		$other = wfMsg('avatar-rejection-ut-other');

		$wgOut->addHTML("
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/prototype.js?') . WH_SITEREV . "'></script>
	<script type='text/javascript' src='".wfGetPad('/extensions/min/f/skins/common/jquery.md5.js?') . WH_SITEREV ."'></script>
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/avatar.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/cropper/cropper.css?') . WH_SITEREV . "' type='text/css' />
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/avatar.css?') . WH_SITEREV . "' type='text/css' />
<script type='text/javascript'>
	var msg_inappropriate = '".addslashes(preg_replace('/\n/','',$inappropriate))."';
	var msg_copyright = '".addslashes(preg_replace('/\n/','',$copyright))."';
	var msg_other = '".addslashes(preg_replace('/\n/','',$other))."';
</script>");

		$wgOut->addHTML("<h1>User Picture Management</h1>\n");
		$wgOut->addHTML(wfMsg('avatar-mgmt-instructions') . "<br />\n");

		$dbr = wfGetDB(DB_SLAVE);

		$sql = "SELECT av_patrol,count(av_patrol) as count from avatar group by av_patrol";
		$res = $dbr->query($sql, __METHOD__);

		$total = 0;
		if ($dbr->numRows($res) > 0) {
			while ($row = $dbr->fetchObject($res)) {
				if ($row->av_patrol == 0) {
					$wgOut->addHTML("Users with pictures to patrol: ". $row->count . "<br />");
					$total += $row->count;
				} else if ($row->av_patrol == 2) {
					$wgOut->addHTML("Users who have removed pictures: ". $row->count . "<br />");
				} else {
					$total += $row->count;
				}
			}
		}
		$wgOut->addHTML("Total user pictures currently in use: ". $total . "<br /><br />");

		$sql = "SELECT * from avatar where av_patrol=0 order by av_dateAdded";
		if ($wgRequest->getVal("reverse"))
			$sql .= " DESC ";
		$sql .= " LIMIT 100";
		$res = $dbr->query($sql, __METHOD__);

		if( $dbr->numRows($res) > 0) {
			while ($row = $dbr->fetchObject($res)) {
				$u = User::newFromID($row->av_user);
				$imgPath = self::getAvatarOutPath($row->av_user . ".jpg");
				$img = "<img src='" . $imgPath . $row->av_user . ".jpg' height=80px width=80px/>";
				//handle Facebook images
				if (!empty($row->av_image))
					$img = "<img src='{$row->av_image}' />";
				$wgOut->addHTML("
<div id='div_".$row->av_user."' style='width:600px'>
	<div style='float:left;margin:10px; width:80px; text-align:center;'>
	{$img}
	</div>

	<div style='padding-top: 40px; width: 350px; float:left;'>
	<span style='margin:30px;text-align: left;'>
		<a href='/".preg_replace('/\s/','-',$u->getUserPage())."'>".$u->getName()."</a>
		(<a href='/".$u->getTalkPage()."' target='_blank'>Talk</a> |
		<a href='/Special:Contributions/".$u->getName()."' target='_blank'>Contributions</a> |
		<a href='/Special:Blockip/".$u->getName()."' target='_blank'>Block</a> )

	</div>
	<div style='float: right; height: 40px; padding-top:30px;'>
		<input type='button' name='accept' value='Accept' onclick=\"avatarAccept('".$row->av_user."');\" />
		<input type='button' name='reject' value='Reject'  onclick=\"avatarReject(this,'".$row->av_user."');\" /><br/>
	</div>


<div style='clear: both;width= 70%;border:1px solid #AAA;'> </div>
</div>
<div style='clear: both;'> </div>

			");

			}

			$wgOut->addHTML("
<div class='avatarModalPage' id='avatarModalPage'>
   <div class='avatarModalBackground' id='avatarModalBackground'></div>
   <div class='avatarModalContainerReject' id='avatarModalContainerReject'>
	  <div class='avatarModalTitle'><span style='float:right;'><a onclick=\"avatarRejectReset();\">X</a></span>".wfMsg('avatar-reject-modal-instructions')."</div>
	  <div class='avatarModalBody'>
			<div id='reasonmodal' >
				<form name='rejectReason' id='rejectReason'>
				<table><tr>
				<td>Reject Reason:</td>
				<td>
				<select name='reason' SIZE=1 onchange='changeMessage();'>
					<option selected value='inappropriate'>Inappropriate or Offensive</option>
					<option value='copyright'>Copyright Violation</option>
					<option value='other'>Other</option>
				</select>
				</td>
				</tr><tr>
				<td valign='top'>Message:</td>
				<td>
				<textarea id='reason_msg' cols='55' rows='5'></textarea>
				</td></tr></table>

				<input type='hidden' name='reasonUID' value='0'>
				</form>
				<div style='clear: both;padding:5px;'> </div>
				<div style='float:right;padding-right:10px;'>
					<input type='button' name='reject' value='Reject'  onclick=\"avatarReject2();\" />
					<a onclick=\"avatarRejectReset();\" >Cancel</a>
				</div>
				<div style='clear: both;'> </div>
			</div>
		</div>
	</div>
</div>
			");

		} else {
			$wgOut->addHTML("No new avatars to patrol.");
		}
	}

	function accept($uid) {
		global $wgUser, $wgOut;

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$wgOut->setArticleBodyOnly(true);

		$dbw = wfGetDB(DB_MASTER);
		$sql = "UPDATE avatar set av_patrol=1,av_patrolledBy=".$wgUser->getID().",av_patrolledDate='".wfTimestampNow()."' where av_user=".$uid;
		$res = $dbw->query($sql, __METHOD__);

		return("SUCCESS");

	}

	function reject($uid, $reason, $message) {
		global $wgUser, $wgOut, $wgLang;

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$wgOut->setArticleBodyOnly(true);

		$dbw = wfGetDB(DB_MASTER);

		//REMOVE PICTURE
		$ret = $this->removePicture($uid);
		if (preg_match('/FAILED/',$ret,$matches)) {
			wfDebug("Avatar removePicture failed: ".$ret."\n");
		}

		//UPDATE DB RECORD
		$sql = "UPDATE avatar set av_patrol=-1,av_patrolledBy=".$wgUser->getID().",av_patrolledDate='".wfTimestampNow()."',av_rejectReason='".mysql_real_escape_string($reason)."' where av_user=".$uid;

		$res = $dbw->query($sql, __METHOD__);


		//POST ON TALK PAGE
		$dateStr = $wgLang->timeanddate(wfTimestampNow());

		$user = $wgUser->getName();
		$real_name = User::whoIsReal($wgUser->getID());
		if (!$real_name) $real_name = $user;

		$u = User::newFromID($uid);

		$user_talk = $u->getTalkPage();

		$comment = "";
		$text = "";
		$article = "";
		if ($message) {
			$comment = $message . "\n";
		}

		if ($comment) {
			$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);

			if ($user_talk->getArticleId() > 0) {
				$r = Revision::newFromTitle($user_talk);
				$text = $r->getText();
			}
			$article = new Article($user_talk);

			$text .= "\n\n$formattedComment\n\n";

			$watch = false;
			if ($wgUser->getID() > 0)
				$watch = $wgUser->isWatched($user_talk);

			if ($user_talk->getArticleId() > 0) {
				$article->updateArticle($text, wfMsg('avatar-rejection-usertalk-editsummary'), true, $watch);
			} else {
				$article->insertNewArticle($text, wfMsg('avatar-rejection-usertalk-editsummary'), true, $watch, false, false, true);
			}

		}

		return("SUCCESS");
	}

	function getAvatarRaw($name) {
		global $IP;

		// bullshit function to just return the URL since getPicture is all f**cked up
		$u = User::newFromName($name);
		if (!$u) {
			return array('type' => 'df', 'url' => '');
		}
		$u->load();

		$dbr = wfGetDB(DB_SLAVE);
		// check for facebook
		if ($u->isFacebookUser()) {
			$row = $dbr->selectRow('avatar', array('av_image','av_patrol'), array('av_user'=>$u->getID()), __METHOD__);
			if ($row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
				return array('type' => 'fb', 'url' => $row->av_image);
			}
		}

		//checks for redirects for users that go that route
		//rather than just changing the username
		$up = $u->getUserPage();
		$a = new Article($up, 0); //need to put 0 as the oldID b/c Article gets the old id out of the URL
		if ($a->isRedirect()) {
			$t = Title::newFromRedirect( $a->fetchContent() );
			if (!($u = User::newFromName($t->getText()))) {
				return array('type' => 'df', 'url' => '');
			}
		}

		$row = $dbr->selectRow('avatar', array('av_dateAdded'), array('av_user'=>$u->getID()), __METHOD__);
		$imgPath = self::getAvatarOutPath($u->getID() . ".jpg");
		$cropout = $IP . $imgPath . $u->getID() .".jpg";
		if (file_exists($cropout)) {
			return array('type' => 'av', 'url' => $u->getID() .".jpg?" . $row->av_dateAdded);
		}

		return array('type' => 'df', 'url' => '');
	}

	function getAvatarURL($name) {
		$raw = self::getAvatarRaw($name);
		if ($raw['type'] == 'df') {
			return wfGetPad('/skins/WikiHow/images/default_profile.png');
		} elseif ($raw['type'] == 'fb') {
			return $raw['url'];
		} elseif ($raw['type'] == 'av') {
			$imgName = explode("?", $raw['url']);
			$imgPath = self::getAvatarOutPath($imgName[0]);
			return wfGetPad($imgPath . $raw['url']);
		}
	}

	function getPicture($name, $raw = false, $fromCDN = false) {
		global $wgUser, $IP, $wgTitle;

		if (!($u = User::newFromName($name))) {
			return;
		}

		/* not sure what's going on here, User Designer-WG.de ::newFromName does not work, mId==0 */
		if ($u->getID() == 0) {
			$dbr = wfGetDB(DB_SLAVE);
			$id = $dbr->selectField('user', array('user_id'), array('user_name'=> $name), __METHOD__);
			$u = User::newFromID($id);
		}

		$imgPath = self::getAvatarOutPath($u->getID() . ".jpg");
		$crop_out = $IP . $imgPath . $u->getID() .".jpg";

		$ret = "";
		if (!$raw) {
			$ret = "<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/avatar.css?') . WH_SITEREV . "' type='text/css' />
			<script type='text/javascript' src='".wfGetPad('/extensions/min/f/skins/common/jquery.md5.js?') . WH_SITEREV ."'></script>
			<script language='javascript' src='" . wfGetPad('/extensions/wikihow/avatar.js?') . WH_SITEREV . "'></script>\n";
		}

		# handle facebook users
		if ($u->isFacebookUser()) {
			$dbr = wfGetDB(DB_SLAVE);
			$row = $dbr->selectRow('avatar', array('av_image','av_patrol'), array('av_user'=>$u->getID()), __METHOD__);
			if ($row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
				$imgUrl = $row->av_image;
				$ret .= "<div id='avatarID' class='avatar_fb'><img id='avatarULimg' src='{$imgUrl}'  height='50px' width='50px' /><br/><div id='avatarULaction'>";
				if ($u->getID() == $wgUser->getID() && ($wgTitle->getNamespace() == NS_USER))
					$ret .="<a href='#' onclick='removeButton();return false;'>remove</a>";
				$ret .= "</div></div>";
				return $ret;
			}
		}
		if (($wgUser->getID() == $u->getID()) && ($wgUser->getID() > 0) && ($wgTitle->getNamespace() == NS_USER)) {
			$ret .= "<div class='avatar' id='avatarID'>";
			$url = self::getAvatarURL($name);
			if(stristr($url, "default_profile.png")){
				$ret .= "
				<div id='avatarULaction'><div class='avatarULtextBox'><a class='avatar_upload' onclick='uploadImageLink();return false;' id='gatUploadImageLink' href='#'></a></div></div>";
			}
			else{
				$ret .= "<img id='avatarULimg' src='" .  $url . "' height='80px' width='80px' />";
				$ret .= "<a href onclick='removeButton();return false;' onhover='background-color: #222;' >remove</a> | <a href onclick='editButton();return false;' onhover='background-color: #222;'>edit</a>";
			}
			/*$ret .= Avatar::display() . "<div id='avatarID' class='avatar'>";
			$ret .= "
				<img id='avatarULimg' src='' height='80px' width='80px' style='display:none' /><br/>
				<div id='avatarULaction'></div>
				</div>";*/
			$ret .= "</div>";
		} else {
			if (file_exists($crop_out)) {
				if ($raw) {
					$imgUrl = self::getAvatarURL($name);
					$ret .= "<img src='" . $imgUrl . "' />";
				} else {
					$ret .= "<div id='avatarID' class='avatar'>";
					$ret .= "<img id='avatarULimg' src='" .  self::getAvatarURL($name) . "' height='80px' width='80px' /></div>";
				}
			} else {
				//XXNOTE Can return default image here.  But Not until we force profile images
				$ret = "";
			}
		}

		return $ret;
	}

	function getDefaultPicture() {
		$ret = "<img src='" . wfGetPad('/skins/WikiHow/images/default_profile.png') . "'>";
		return $ret;
	}

	function removePicture($uid = '') {
		global $wgUser, $IP;

		if ($uid == '') {
			$u = $wgUser->getID();
		} else {
			$u = $uid;
		}


		$fileext = array('jpg','png','gif','jpeg');

		$imgPath = self::getAvatarOutPath($u . ".jpg");
		$crop_out = $IP . $imgPath . $u .".jpg";

		$imgPath = self::getAvatarInPath($u);
		$crop_in = $IP . $imgPath . $u ;

		$imgPath = self::getAvatarInPath("tmp_" . $u);
		$crop_in2 = $IP . $imgPath . $u ;
		if (file_exists($crop_out)) {
			if(unlink($crop_out)) {
				foreach ($fileext as $ext) {

					$imgPath = self::getAvatarInPath("$u.$ext");
					$crop_in = $IP . $imgPath . $u . "." . $ext;
					if (file_exists($crop_in)) {
						if(!unlink($crop_in)) {
							wfDebug("can't delete" . $crop_in . "\n");
						}
					}

					$imgPath = self::getAvatarInPath("tmp_$u.$ext");
					$crop_in2 = $IP . $imgPath . "tmp_" . $u . "." . $ext;
					if (file_exists($crop_in2)) {
						if(!unlink($crop_in2)) {
							wfDebug("can't delete" . $crop_in2 . "\n");
						}
					}
				}
				return "SUCCESS: files removed $crop_out and $crop_in ";
			} else {
				wfDebug("FAILED: files exists could not be removed. $crop_out and $crop_in");
				return "FAILED: files exists could not be removed. $crop_out and $crop_in";
			}
		}
		// files don't have to exist if we use av_image
		$dbw = wfGetDB(DB_MASTER);
		$sql = "UPDATE avatar set av_patrol=2,av_patrolledBy=".$u.",av_patrolledDate='".wfTimestampNow()."' where av_user=".$u;
		$res = $dbw->query($sql, __METHOD__);
		// Remove avatar url (av_image) for FB users
		$user = User::newFromID($u);
		if ($user && $user->isFacebookUser()) {
			$sql = "UPDATE avatar set av_image='' where av_user=".$u;
			$res = $dbw->query($sql, __METHOD__);
			return "SUCCESS: Facebook avatar removed";
		}

		return "FAILED: files do not exist. $crop_out and $crop_in";
	}

	function display() {
		global $wgOut, $wgTitle, $wgUser;

		if ($wgTitle->getNamespace() != NS_USER) {
			return $avatarDisplay;
		}

		$avatarDisplay .= "
	<script>jQuery.noConflict();</script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/prototype.js?') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/scriptaculous.js?load=builder,dragdrop&') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/cropper.js?') . WH_SITEREV . "'></script>
	<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/cropper/cropper.css?') . WH_SITEREV . "' type='text/css' />";

		$imgPath = self::getAvatarInPath($wgUser->getID() . ".jpg");
		$avatarDisplay .= "
<script type='text/javascript'>
		var wgUserID = '".$wgUser->getID()."';
		var nonModal = false;
</script>

<div id='avatarModalPage'>
	<div class='avatarModalBackground' id='avatarModalBackground'></div>
	<div class='avatarModalContainer' id='avatarModalContainer'>
		<img height='10' width='679' src='" . wfGetPad('/skins/WikiHow/images/article_top.png') . "' alt=''/>
		<div class='avatarModalContent'>
		<div class='avatarModalTitle'><span style='float:right;'><a onclick=\"closeButton();\"><img src='" . wfGetPad('/extensions/wikihow/winpop_x.gif') . "' width='21' height='21' alt='close window' /></a></span>". wfMsg('avatar-instructions',$wgUser->getName())."</div>
		<div class='avatarModalBody'>
			<div id='avatarUpload'>
				<form name='avatarFileSelectForm' action='/Special:Avatar?type=upload' method='post' enctype='multipart/form-data' onsubmit=\"getNewPic(); return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})\">
					File: <input type='file' id='uploadedfile' name='uploadedfile' size='40' /> <input type='submit' id='gatAvatarImageSubmit' value='SUBMIT' />
				</form>
				<div id='avatarResponse'></div><br />
			</div>

		<div id='avatarCrop' >
			<div id='avatarCropBorder' >
				<div id='avatarImgBlock' style='width: 490px;margin-left: 50px;'>
					<div id='avatarJS'>
						<img src='" . $imgPath . $wgUser->getID().".jpg' id='avatarIn' />
					</div>
					<div id='avatarPreview'>
					Cropped Preview:<br />
					<div id='avatarPreview2'>
					</div>
					</div>
				</div>

				<div style='clear: both;'> </div>
				</div>
				<div>".wfMsg('avatar-copyright-notice')."</div>
				<div id='cropSubmit' >
				<form name='crop' method='post' >
					<input type='button' value='Crop and Save' id='gatAvatarCropAndSave' onclick='ajaxCropit();' style='font-size:120%;'/>&nbsp;
					<a onclick=\"closeButton();\">Cancel</a>
					<!-- <a onclick=\"alert($('avatarPreview2').innerHTML);\">vutest</a> -->
					<input type='hidden' name='cropflag' value='false' />
					<input type='hidden' name='image' value='".$wgUser->getID().".jpg' />
					<input type='hidden' name='type' value='crop' />
					<input type='hidden' name='x1' id='x1' />
					<input type='hidden' name='y1' id='y1' />
					<input type='hidden' name='x2' id='x2' />
					<input type='hidden' name='y2' id='y2' />
					<input type='hidden' name='width' id='width' />
					<input type='hidden' name='height' id='height' />
				</form>
				</div>
				<div style='clear: both;'> </div>

			</div>
		</div>
		</div><!--end avatarModalContent-->
		<img width='679' src='" . wfGetPad('/skins/WikiHow/images/article_bottom_wh.png') . "' alt=''/>
	</div>
</div> ";

		return $avatarDisplay;
	}

	function displayNonModal() {
		global $wgOut, $wgTitle, $wgUser, $wgRequest;

		$imgname = '';
		$avatarReload = '';
		if ($wgRequest->getVal('reload')) {
			//$imgname = "tmp_".$wgUser->getID().".jpg?".rand();
			$imgname = "tmp_".$wgUser->getID().".jpg";
			$imgPath = self::getAvatarInPath($imgname);
			$avatarReload = "var avatarReload = true;";
		} else {
			$imgname = $wgUser->getID().".jpg";
			$imgPath = self::getAvatarInPath($imgname);
			$avatarReload = "var avatarReload = false;";
		}

		$avatarCrop = '';
		$avatarNew = "var avatarNew = false;";
		if ($wgRequest->getVal('new')) {
			$avatarCrop = "style='display:none;'";
			$avatarNew = "var avatarNew = true;";
		}

		$wgOut->addHTML("\n<!-- AVATAR CODE START -->\n<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/avatar.css?') . WH_SITEREV . "' type='text/css' />\n");

		$wgOut->addHTML( "
	<script>jQuery.noConflict();</script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/prototype.js?') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/lib/scriptaculous.js?load=builder,dragdrop&') . WH_SITEREV . "'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/cropper/cropper.js?') . WH_SITEREV . "'></script>
	<script type='text/javascript' src='".wfGetPad('/extensions/min/f/skins/common/jquery.md5.js?') . WH_SITEREV ."'></script>
	<script language='javascript' src='" . wfGetPad('/extensions/wikihow/avatar.js?') . WH_SITEREV . "'></script>
	<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/cropper/cropper.css?') . WH_SITEREV . "' type='text/css' />


<script type='text/javascript'>
		var wgUserID = '".$wgUser->getID()."';
		var nonModal = true;
		var userpage = '".$wgUser->getUserPage()."';
		$avatarReload\n
		$avatarNew\n
</script>

	  <div class='avatarModalBody' style='height:500px;'>
	  <div>". wfMsg('avatar-instructions',$wgUser->getName())."</div>
		 <div id='avatarUpload' >
				<form name='avatarFileSelectForm' action='/Special:Avatar?type=upload&reload=1' method='post' enctype='multipart/form-data' >
					File: <input type='file' id='uploadedfile' name='uploadedfile' size='40' /> <input type='submit' id='gatAvatarImageSubmit' value='SUBMIT' />
				</form>
				<div id='avatarResponse'></div><br />
		 </div>

		 <div id='avatarCrop' $avatarCrop >
			<div id='avatarCropBorder' >
					<div id='avatarImgBlock' style='width: 490px;margin-left: 50px;'>
						<div id='avatarJS'>
							<img src='" . $imgPath . $imgname . "?" . rand() . "' id='avatarIn' />
						</div>
						<div id='avatarPreview'>
						Cropped Preview:<br />
						<div id='avatarPreview2'>
						</div>
						</div>
					</div>
				<div style='clear: both;'> </div>
				</div>

				<div>".wfMsg('avatar-copyright-notice')."</div>

				<div id='cropSubmit' >
				<form name='crop' method='post' >
					<input type='button' value='Crop and Save' id='gatAvatarCropAndSave' onclick='ajaxCropit();' style='font-size:120%;'/>&nbsp;
					<a onclick=\"closeButton();\">Cancel</a>
					<!-- <a onclick=\"alert($('avatarPreview2').innerHTML);\">vutest</a> -->
					<input type='hidden' name='cropflag' value='false' />
					<input type='hidden' name='image' value='".$imgname."' />
					<input type='hidden' name='type' value='crop' />
					<input type='hidden' name='x1' id='x1' />
					<input type='hidden' name='y1' id='y1' />
					<input type='hidden' name='x2' id='x2' />
					<input type='hidden' name='y2' id='y2' />
					<input type='hidden' name='width' id='width' />
					<input type='hidden' name='height' id='height' />
				</form>
				</div>

		 </div>
	  </div>
<script type='text/javascript'>
Event.observe(window, 'load', initNonModal);
</script>");

		$wgOut->addHTML("<!-- AVATAR CODE ENDS -->\n");
	}

	function purgePath($path) {
		global $wgUseSquid, $wgServer;
		if ($wgUseSquid) {
			$urls = array($wgServer . $path);
			$u = new SquidUpdate( $urls );
			$u->doUpdate();
			wfDebug("Avatar: Purging path of " . print_r($urls, true) . "\n");
		}
		return true;
	}

	function crop() {
		global $wgUser, $wgOut, $wgTitle, $wgServer, $wgRequest, $IP, $wgImageMagickConvertCommand;

		$imagesize = 80;
		if ($wgRequest->getVal('cropflag') == 'false') {return false;}

		$image = $wgRequest->getVal('image');
		$x1 = $wgRequest->getVal('x1');
		$y1 = $wgRequest->getVal('y1');
		$x2 = $wgRequest->getVal('x2');
		$y2 = $wgRequest->getVal('y2');
		$width = $wgRequest->getVal('width');
		$height = $wgRequest->getVal('height');

		$imgPath = self::getAvatarInPath($image);
		$crop_in = $IP . $imgPath . $image;

		$imgPath = self::getAvatarInPath($wgUser->getID() . ".jpg");
		$crop_in2 = $IP . $imgPath . $wgUser->getID() .".jpg";

		$imgPath = self::getAvatarOutPath($wgUser->getID() . ".jpg");
		$crop_out = $IP . $imgPath . $wgUser->getID() .".jpg";

		if ($crop_in != $crop_in2) {
			if (!copy($crop_in, $crop_in2)) {
				wfDebug("Avatar: failed copy $crop_in to $crop_in2 \n");
			}
		}

		$doit = "$wgImageMagickConvertCommand -crop " . $width . "x" . $height . "+$x1+$y1 " .  $crop_in . " +repage -strip " . $crop_out;
		$result = system($doit, $ret);
		wfDebug("Avatar: ran command $doit got result $result and code $ret\n");
		if (!$ret) {
			if ($width > $imagesize) {
				$doit = "$wgImageMagickConvertCommand " . $crop_out . " -resize ".$imagesize."x".$imagesize." " . $crop_out;
				$result = system($doit, $ret);
				wfDebug("Avatar: ran command $doit got result $result and code $ret\n");
			}
		} else {
			wfDebug('trace 2: '.$ret.' from: '.$doit);
			return false;
		}

		self::purgePath($imgPath . $wgUser->getID() .".jpg");
		self::purgePath("/User:" . $wgUser->getName());

		return true;
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgTitle, $wgServer, $wgRequest, $IP, $wgImageMagickConvertCommand;
		$dbw = wfGetDB(DB_MASTER);

		$type = $wgRequest->getVal('type');
		if ($type == 'upload') {
			$wgOut->setArticleBodyOnly(true);

			//GET EXT
			$fileext = array('jpg','png','gif','jpeg');
			$f = basename( $_FILES['uploadedfile']['name']);
			$basename = "";
			$extensions = "";

			wfDebug("Avatar: Working with file $f\n");
			$pos = strrpos($f, '.');
			if ($pos === false) { // dot is not found in the filename
				$msg = "Invalid filename extension not recognized filename: $f\n";
				$response['status'] = 'ERROR';
				$response['msg'] = $msg;

				wfDebug("Avatar: Invalid extension no period $f\n");
				echo json_encode($response);
				return;
			} else {
				$basename = substr($f, 0, $pos);
				$extension = substr($f, $pos+1);
				if ( !in_array(strtolower($extension), array_map('strtolower', $fileext)) ) {
					$msg = "Invalid filename extension not recognized filename: $f\n";
					$response['status'] = 'ERROR';
					$response['msg'] = $msg;
					wfDebug("Avatar: $msg");
					echo json_encode($response);
					return;
				}
			}

			$imgPath = self::getAvatarInPath("tmp2_" . $wgUser->getID() . "." . strtolower($extension));
			$target_path = $IP . $imgPath . "tmp2_" . $wgUser->getID() .".". strtolower($extension);
			$imgPath = self::getAvatarInPath("tmp_" . $wgUser->getID() . ".jpg");
			$target_path2 = $IP . $imgPath . "tmp_" . $wgUser->getID() .".jpg" ;

			if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
				wfDebug("Avatar: Moved uploaded file from {$_FILES['uploadedfile']['tmp_name']} to {$target_path}\n");

				//converting filetype
				$count = 0;
				while ($count < 3){
					$doit = $wgImageMagickConvertCommand . " " . $target_path . " " . $target_path2;
					$result = system($doit, $ret);
					wfDebug("Avatar: Converting, $doit result $result code: $ret\n");

					if ($ret != 127) {
						break;
					} else {
						$count++;
					}
				}

				$ratio = 1;
				$maxw = 350;
				$maxh = 225;
				$size = getimagesize($target_path2);
				$width = $size[0];
				$height = $size[1];

				if (($width < $maxw) && ($height < $maxh)) {
					$ratio = 1;
				} else {
					if ($maxh/$height > $maxw/$width) {
						$ratio = $maxw/$width;
					} else {
						$ratio = $maxh/$height;
					}
				}

				$msg = "The file ".  basename( $_FILES['uploadedfile']['name']).  " has been uploaded. ";
				if ($ratio != 1) {
					$newwidth = number_format(($width * $ratio), 0, '.', '');
					$newheight = number_format(($height * $ratio), 0, '.', '');
					$doit = $wgImageMagickConvertCommand . " " . $target_path2 . " -resize ".$newwidth."x".$newheight." " . $target_path2;
					$result = system($doit, $ret);
					wfDebug("Avatar: Converting, $doit result $result code: $ret\n");
				}
				if ($wgRequest->getVal('reload')) {
					wfDebug("Avatar: Got a reload, returning\n");
					header( 'Location: '.$wgServer.'/Special:Avatar?type=nonmodal&reload=1' ) ;
					return;
				}

				$response['status'] = 'SUCCESS';
				$response['msg'] = $msg;
				$response['basename'] = $basename;
				$response['extension'] = "jpg";
				wfDebug("Avatar: Success, " . print_r($response, true) . "\n");
				$res =  json_encode($response);
				echo $res;
				return;
			} else{
				if ($wgRequest->getVal('reload')) {
					header( 'Location: '.$wgServer.'/Special:Avatar?type=nonmodal' ) ;
					return;
				}
				wfDebug("Avatar: Unable to move uploaded file from {$_FILES['uploadedfile']['tmp_name']} to {$target_path}\n");
				$msg = "There was an error uploading the file, please try again!";
				$response['status'] = 'ERROR';
				$response['msg'] = $msg;
				echo json_encode($response);
				return;
			}
		} else if ($type == 'crop') {
			$wgOut->setArticleBodyOnly(true);
			if ($this->crop()) {

			$sql = "INSERT INTO avatar (av_user, av_patrol, av_dateAdded) ";
				$sql .= "VALUES ('".$wgUser->getID()."',0,'".wfTimestampNow()."') ON DUPLICATE KEY UPDATE av_patrol=0,av_dateAdded='".wfTimestampNow()."'";
			$ret = $dbw->query($sql, __METHOD__);
			wfRunHooks("AvatarUpdated", array($wgUser));

				$wgOut->addHTML('SUCCESS');
			} else {
				$wgOut->addHTML('FAILED');
			}
			//$wgOut->redirect($wgServer."/".$wgUser->getUserPage());
		} else if ($type == 'unlink') {
			$wgOut->setArticleBodyOnly(true);
			$ret = $this->removePicture();
			self::purgePath("/User:" . $wgUser->getName());
			if (preg_match('/SUCCESS/',$ret)) {
				$wgOut->addHTML('SUCCESS:'.$ret);
			} else {
				$wgOut->addHTML('FAILED:'.$ret);
			}
		} else if ($type == 'accept') {
			$ret = $this->accept($wgRequest->getVal('uid'));
			if (preg_match('/SUCCESS/',$ret)) {
				$wgOut->addHTML('SUCCESS:'.$ret);
			} else {
				$wgOut->addHTML('FAILED:'.$ret);
			}
		} else if ($type == 'reject') {
			$ret = $this->reject($wgRequest->getVal('uid'), $wgRequest->getVal('r'), $wgRequest->getVal('m'));
			if (preg_match('/SUCCESS/',$ret)) {
				$wgOut->addHTML('SUCCESS:'.$ret);
			} else {
				$wgOut->addHTML('FAILED:'.$ret);
			}
		} else if ($type == 'nonmodal') {
			$this->displayNonModal();
		} else {
			//$avDisplay = $this->displayManagement();
			//no longer want to show this page
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
		}

	}

	static function getAvatarInPath($name) {
		// hash level is 2 deep
		$path = "/images/avatarIn/" . self::getHashPath($name);
		return $path;
	}

	static function getAvatarOutPath($name) {
		// hash level is 2 deep
		$path = "/images/avatarOut/" . self::getHashPath($name);
		return $path;
	}

	static function getHashPath($name) {
		return FileRepo::getHashPathForLevel( $name, 2);
	}

	static function insertAvatarIntoDiscussion($discussionText){
		$text = "";
		$parts = preg_split('@(<p class="de_user".*</p>)@im', $discussionText, 0, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = 0; $i < sizeof($parts); $i++) {
			if (preg_match('@(<p class="de_user".*</p>)@im', $parts[$i])) {
				$pos = strpos($parts[$i], 'href="/User:');
				$endpos = strpos($parts[$i], '"', $pos + 12);
				$username = substr($parts[$i], $pos + 12, $endpos - $pos - 12);

				$len = strlen('<p class="de_user">');
				$text .= substr($parts[$i], 0, $len) . "<img src='" . wfGetPad(Avatar::getAvatarURL($username)) . "' />" . substr($parts[$i], $len);
			}
			else {
				$text .= $parts[$i];
			}
		}
		return $text;
	}

}


<?

class FBConnect extends UnlistedSpecialPage {
	var $facebook = null; // Handle to facebook API
	var $userid = 0;
	var $returnto = "";

    function __construct($source = null) {
        UnlistedSpecialPage::UnlistedSpecialPage( 'FBConnect' );
    }

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgFBConnectAPIkey, $wgFBConnectSecret, $wgLanguageCode;
		require_once('extensions/wikihow/facebook-platform/php/facebook.php');

		$this->returnto = "/wikiHow:Community?v=" . rand(0,1100);
		$this->userid = $_COOKIE[$wgFBConnectAPIkey . "_user"];
		$userid = $this->userid;
		if (!$userid) {
			$wgOut->addHTML("An error occurred.");
			return;
		}
		$this->setWgUser();

		$this->facebook = new Facebook($wgFBConnectAPIkey, $wgFBConnectSecret);
		$result = $this->facebook->api_client->users_getInfo($userid, 'name, pic, pic_big, pic_square, current_location, about_me, profile_blurb, contact_email, affiliations');

		if (!$wgRequest->wasPosted()) {
			// If they still have the FB_* name, show them the registration form with a proposed name
			if (strpos($wgUser->getName(), "FB_") !== false) {
				$this->printRegForm($result);
			} else {
				// Update the avatar image with the latest
				$this->updateAvatar($result);

				// All logged in. Return them to wherever they're supposed to go
				$wgOut->redirect($this->returnto);
				setcookie( 'wiki_returnto', 0, time() - 3600);
			}
		} else {
			$this->processRegForm($result);
		}
	}	

	function setWgUser() {
		global $wgUser;
		$dbr = wfGetDB(DB_SLAVE); 
		$wh_userid = $dbr->selectField('facebook_connect', array('wh_user'), array('fb_user' => $this->userid));
		$dbw = wfGetDB(DB_MASTER);
		// Never here before?  create a new user and log them in
		if ($wh_userid == null) {
			$u = User::createNew('FB_' . $this->userid);
			if (!$u) {
				$u = User::newFromName('FB_' . $this->userid);
			}
			$dbw->insert('facebook_connect', array('wh_user' => $u->getID(), 'fb_user' => $this->userid));	
		} else {
			$u = User::newFromID($wh_userid);
			$dbw->update('facebook_connect', array('num_login = num_login + 1'), array('wh_user' => $wh_userid));
		}
		$wgUser = $u;		
		$wgUser->setCookies();
	}


	function printRegForm(&$result, $username = null, $email = '', $error = '') {
		global $wgOut;
		$username = $username !== null ? $username : $this->getProposedUsername($result[0]['name']);
		$prefill = true;
		if(!$username) {
			$prefill = false;	
		}
		$email = $email ? $email : $result[0]['contact_email'];
		$picture = $result[0]['pic_square'];
		$friendsHtml = $this->getAppFriendsHtml();
		$affiliations = $this->getAffiliations($result);
		if(strlen($affiliations)) {
			$affiliations .= ' &middot;';
		}

		$numFriends = count($this->facebook->api_client->friends_get());
		$fbicon = wfGetPad('/skins/WikiHow/images/facebook_share_icon.gif');
		wfLoadExtensionMessages('FBConnect');
		if ($prefill) {
			$html = wfMsg('fbc_form_prefill', $fbicon, $username, $error, $picture, $affiliations, $numFriends, $email, $friendsHtml);
		} else {
			$html = wfMsg('fbc_form_no_prefill', $fbicon, $username, $error, $email, $friendsHtml);
		}
		$tags = HtmlSnips::makeUrlTags('css', array('fbconnect.css'), '/extensions/wikihow/', FBCONNECT_DEBUG);
		$tags .= HtmlSnips::makeUrlTags('js', array('fbconnect.js'), '/extensions/wikihow/', FBCONNECT_DEBUG);
		$wgOut->addHtml($tags);
		$wgOut->addHtml($html);
	}

	function processRegForm(&$result) {
		global $wgRequest, $wgUser, $wgOut;
		$dbw = wfGetDB(DB_MASTER);
		$userOverride = strlen($wgRequest->getVal('requested_username'));
		$newname = $userOverride ? $wgRequest->getVal('requested_username') : $wgRequest->getVal('proposed_username');
		$newname = $dbw->strencode($newname);
		$email = $dbw->strencode($wgRequest->getVal('email'));
		$realname = $userOverride ? '' : $dbw->strencode($result[0]['name']);

		$newname = User::getCanonicalName($newname);
		$exist = User::newFromName($newname);
		if ($exist->getID() > 0 && $exist->getID() != $wgUser->getID()) {
			$this->printRegForm($result, $newname, $email, wfMsg('fbconnect_username_inuse', $newname));
			return;
		}
		$dbw->update('user',
			array('user_name' => $newname, 'user_email' => $email, 'user_real_name' => $realname),
			array('user_id' => $wgUser->getID())
			);
		if (!$userOverride) {
			$this->updateAvatar($result);
		}
		$wgUser->invalidateCache();
		$wgUser->loadFromID();
		$wgUser->setCookies();
		wfRunHooks( 'AddNewAccount', array( $wgUser, true ) );

		// All registered. Send them along their merry way
		$wgOut->redirect($this->returnto);
		setcookie( 'wiki_returnto', 0, time() - 3600);
	}

	function userNameIsFacebookUser($name) {
		$dbr = wfGetDB(DB_SLAVE);
		return $dbr->selectField( array('facebook_connect', 'user'),
				array('count(*)'),
				array('user_id=wh_user', 'user_name'=>$name)
			) > 0;
	}

	//update avatar picture
	function updateAvatar(&$result) {
		global $wgLanguageCode, $wgUser;

		if ($wgLanguageCode == 'en') {
			$dbr = wfGetDB(DB_SLAVE);
			$dbw = wfGetDB(DB_MASTER);
			$res = $dbr->select('avatar', array('av_image', 'av_patrol'), array("av_user=" . $wgUser->getID()));
			$row = $dbr->fetchObject($res);
			if ($row && $row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
				$dbw->query ("INSERT INTO avatar(av_user, av_image) VALUES ({$wgUser->getID()}, '{$result[0]['pic_square']}')
								ON DUPLICATE KEY UPDATE av_image = '{$result[0]['pic_square']}'");
			}
		}
	}

	function getProposedUsername ($fullname) {
		$proposed_name = '';
		
		$res = array(
					array('@([\s]*)@', '$1'),
					array('@([a-z]?)[a-z]*([\s]*)([a-z]+).*@im', '$1$3'),
					array('@([a-z]+)([\s]*)([a-z]?).*@im', '$1$3'),
				);
		foreach ($res as $re) {
			$name = preg_replace($re[0], $re[1], $fullname);
			$u = User::newFromName($name);
			if ($u && $u->getID() == 0) {
				$proposed_name = $name;
				break;
			}
		}
		return $proposed_name;
	}

	function getAppFriendsHtml() {
		global $wgOut;
		$html = "";
		$friends = $this->facebook->api_client->friends_getAppUsers();
		$friends = $this->facebook->api_client->users_getInfo($friends, 'name, pic_square');
		$numFriends = count($friends);
		if($friends === '' || $numFriends == 0) {
			return $html;
		}
		$friendsToDisplay = 3;

		for($i = 0; $i < $numFriends && $i < $friendsToDisplay; $i++) {
			$pic = $friends[$i]['pic_square'];
			$html .= "<img class='fbc_avatar' src='$pic'/> ";
		}
		$html .= "$numFriends ";
		$html .= ($numFriends - $friendsToDisplay) > 1 ? " of your friends have registered" : "friend has registered";
		return $html;
	}
	
	function getAffiliations(&$result) {
		$affiliations = $result[0]['affiliations'];
		$affCnt = count($affiliations);
		$affStr = "";
		if ($affiliations === '' || $affCnt == 0) {
			return $affStr;
		}

		for($i = 0; $i < 2 && $i < $affCnt; $i++) {
			$affStr .= $affiliations[$i]['name'] . ", ";
		}
		return substr($affStr, 0, strlen($affStr) - 2);
	}
}

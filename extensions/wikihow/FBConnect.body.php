<?

class FBConnect extends UnlistedSpecialPage {
    function __construct($source = null) {
        UnlistedSpecialPage::UnlistedSpecialPage( 'FBConnect' );
    }

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgFBConnectAPIkey, $wgFBConnectSecret, $wgLanguageCode;
		require_once('extensions/wikihow/facebook-platform/php/facebook.php');
		$userid = $_COOKIE[$wgFBConnectAPIkey . "_user"];
		$facebook = new Facebook($wgFBConnectAPIkey, $wgFBConnectSecret);

		$dbr = wfGetDB(DB_SLAVE); 

		if (!$userid) {
			$wgOut->addHTML("An error occurred.");
			return;
		}
			
		$wh_userid = $dbr->selectField( 'facebook_connect',
			array('wh_user'),
			array('fb_user' => $userid)
			);
		
		$dbw = wfGetDB(DB_MASTER);
		$first = false;
		if ($wh_userid == null) {
			// never here before? 
			// create a new user and log them in
			$u = User::createNew('FB_' . $userid);
			if (!$u)
				$u = User::newFromName('FB_' . $userid);
            else
                $first = true;
			$dbw->insert('facebook_connect', array('wh_user' => $u->getID(), 'fb_user' => $userid));	
		} else {
			$u = User::newFromID($wh_userid);
			$dbw->update('facebook_connect',
				array('num_login = num_login + 1'),
				array('wh_user' => $wh_userid)
			);
		}


		$num_login = $dbr->selectField('facebook_connect',
                array('num_login'),
                array('wh_user' => $wh_userid)
            );

		$wgUser = $u;		
		$wgUser->setCookies();
	
		$returnto = $_COOKIE['wiki_returnto'];
		if (empty($returnto) || strpos($returnto, "Userlogin") !== false 
			|| strpos($returnto, "Userlogout") !== false ) 
			$returnto = "/Main-Page";
		//bypass cache copy of page
		if (strpos($returnto, "?") === false) {
			$returnto .= "?v=" . rand(0, 11000);
		}
		// make it the wikihow community page for now
		$returnto = "/wikiHow:Community?v=" . rand(0,1100);
        if ($first)
            $returnto = "/wikiHow:NewFBConnectUser?v=" . rand(0,1100);

		$me = Title::makeTitle(NS_SPECIAL, "FBConnect");
		$result= $facebook->api_client->users_getInfo($userid, 'name, pic, pic_big, pic_square, current_location, about_me, profile_blurb');
		if (!$wgRequest->wasPosted()) {

			//update avatar picture
			if ($wgLanguageCode == 'en') {
				$dbr = wfGetDB(DB_SLAVE);
				$res = $dbr->select('avatar',
									array('av_image', 'av_patrol'),
									array("av_user=" . $wgUser->getID()));
				$row = $dbr->fetchObject($res);
				if ($row && $row->av_image && ($row->av_patrol == 0 || $row->av_patrol == 1)) {
					$dbw->query ("INSERT INTO avatar(av_user, av_image) VALUES ({$wgUser->getID()}, '{$result[0]['pic_square']}')
									ON DUPLICATE KEY UPDATE av_image = '{$result[0]['pic_square']}'");
				}
			}

			// if it's third login, let them build a freakin profile
			if ($num_login == 3 && false) {

				$location = preg_replace("/, $/", "", implode(", ", $result[0]['current_location']));
				
				$wgOut->addHTML(wfMsg('fbconnect_buildprofile',
								$returnto, 	
								$result[0]['pic'],
								$result[0]['pic_big'],
								$result[0]['pic_square'],
								htmlspecialchars($location),
								htmlspecialchars($result[0]['about_me'])
							)
					);
				return;
			} 

			// still got the FB_* name
			if (strpos($wgUser->getName(), "FB_") !== false) {
				$proposed_name = '';
				// try a few proposed names
				$fullname = $result[0]['name'];
				
				$res = array(
							array('@([a-z]+)([\s]*)([a-z]?).*@im', '$1$3'),
							array('@([a-z]?)[a-z]*([\s]*)([a-z]+).*@im', '$1$3'),
							array('@[\s]*@', ''),
							array('@([a-z]?)[a-z]*([\s]*)([a-z]+).*@im', '$1$31'),
						);
				foreach ($res as $re) {
			   		$name = preg_replace($re[0], $re[1], $fullname);
#echo $fullname; echo $name; exit;
					$u = User::newFromName($name);
					if ($u && $u->getID() == 0) {
						$proposed_name = $name;
						break;
					}
				}
			} else {
				$wgOut->redirect($returnto);
				setcookie( 'wiki_returnto', 0, time() - 3600);
			}
			$wgOut->addHTML(wfMsg('fbconnect_configure_account', 
				htmlspecialchars($proposed_name),		
				$wgUser->getEmail(),
				$returnto,
				$result[0]['pic_square'],
				''///error
				)
			);
		} else {
			$newname = User::getCanonicalName($wgRequest->getVal('newusername'));
			$email = $wgRequest->getVal('email');
			$exist = User::newFromName($newname);
			if ($exist->getID() > 0 && $exist->getID() != $wgUser->getID()) {
				$wgOut->addHTML(wfMsg('fbconnect_configure_account',             
					'',
            		$email,
            		$returnto,
					$result[0]['pic_square'],
					wfMsg('fbconnect_username_inuse', $newname)
                )
				);
				return;
			}
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('user',
				array('user_name' => $newname, 'user_email' => $email),
				array('user_id' => $wgUser->getID())
				);
			if ($wgRequest->getCheck('picture')) {
				if ($wgLanguageCode == 'en') {
					$dbw->query ("INSERT INTO avatar(av_user, av_image) VALUES ({$wgUser->getID()}, '{$result[0]['pic_square']}')
									ON DUPLICATE KEY UPDATE av_image = '{$result[0]['pic_square']}'");
				}
			}
			$wgUser->invalidateCache();
			$wgUser->loadFromID();
			$wgUser->setCookies();
			wfRunHooks( 'AddNewAccount', array( $wgUser, true ) );
			if ($returnto)
				$wgOut->redirect($returnto);
				setcookie( 'wiki_returnto', 0, time() - 3600);
			// i think there's no need to change the user's talk page, becaus they JUSt created an account
		}
		//print_r($_COOKIE);
	}	

	function userNameIsFacebookUser($name) {
		$dbr = wfGetDB(DB_SLAVE);
		return $dbr->selectField( array('facebook_connect', 'user'),
				array('count(*)'),
				array('user_id=wh_user', 'user_name'=>$name)
			) > 0;
	}
}

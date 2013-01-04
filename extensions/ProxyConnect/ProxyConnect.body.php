<?
class ProxyConnect extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'ProxyConnect' );
    }

	function updateRemote() {
		global $wgUser, $wgVanillaDB;
		try {
			$db = new Database($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
			$oldignore = $db->ignoreErrors(true); 

			// get vanilla user id
			$vid = $db->selectField('GDN_UserAuthentication', array('UserID'), array('ForeignUserKey'=> $wgUser->getID()));
			if (!$vid) return true; 
			
			$photo =  Avatar::getAvatarURL($wgUser->getName());
			$updates = array(	
					"Photo"=> $photo,
					"Email"=> $wgUser->getEmail(),
				);
			if (in_array('bureaucrat', $wgUser->getGroups())) 
				$updates["Admin"] = 1;
			else
				$updates["Admin"] = 0;
			$opts = array('UserID'=>$vid);
			$db->update('GDN_User', $updates, $opts);
			if ( $wgUser->isBlocked() ){
				$email = new MailAddress("alerts@wikihow.com");
				$subject = "Invalid/valid forums block for " . $wgUser->getName();
				$body = print_r($user->mBlock, true) . "\n" . print_r($wgUser, true);
				UserMailer::send($email, $email, $subject, $body);
				$db->update('GDN_UserRole', array('RoleID = 99'), $opts);
			} else if (in_array('bureaucrat', $wgUser->getGroups())) {
				$db->update('GDN_UserRole', array('RoleID = 16'), $opts);
			} else if (in_array('sysop', $wgUser->getGroups())) {
				$db->update("GDN_User", array('Permissions'=>'a:14:{i:0;s:19:"Garden.SignIn.Allow";i:1;s:22:"Garden.Activity.Delete";i:2;s:25:"Vanilla.Categories.Manage";i:3;s:19:"Vanilla.Spam.Manage";s:24:"Vanilla.Discussions.View";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Discussions.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:24:"Vanilla.Discussions.Edit";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:28:"Vanilla.Discussions.Announce";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:24:"Vanilla.Discussions.Sink";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:25:"Vanilla.Discussions.Close";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:26:"Vanilla.Discussions.Delete";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:20:"Vanilla.Comments.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:21:"Vanilla.Comments.Edit";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Comments.Delete";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}}'), $opts);
				$db->update('GDN_UserRole', array('RoleID = 32'), $opts);
			} else {
				$db->update("GDN_User", array('Permissions'=>'a:4:{i:0;s:19:"Garden.SignIn.Allow";s:24:"Vanilla.Discussions.View";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Discussions.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:20:"Vanilla.Comments.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}}'), $opts); 
				$db->update('GDN_UserRole', array('RoleID = 8'), $opts);
				echo $db->lastQuery();
			}
			$db->ignoreErrors($oldignore);
		} catch (Exception $e) {
			echo "oops {$e->getMessage()}\n";
		}
		return true;
	}
    function execute ($par) {
		global $wgUser, $wgOut;
		$wgOut->disable();
		header("Content-type: text/plain;");
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', 0 ) . ' GMT' );
       	header( "Cache-Control: private, must-revalidate, max-age=0" );

		if ($wgUser->getID() == 0) {
			return;
		}
		
		$avatar = wfGetPad(Avatar::getAvatarURL($wgUser->getName()));
		$result = "";
		$result .= "UniqueID={$wgUser->getID()}\n";
		$result .= "Name={$wgUser->getName()}\n";
		$result .= "Email={$wgUser->getEmail()}\n";
		$result .= "Avatar={$avatar}\n";
		$result .= "CurrentDate=" . date("r") . "\n";
		$result .= "Groups=" . implode(',', $wgUser->getGroups()) . "\n";
		wfDebug("ProxyConnect: returning $result\n");

		#$a = new MailAddress("alerts@wikihow.com");
		#UserMailer::send($a, $a, "Output for ProxyConnect", $result);

		echo $result;
		self::updateRemote();
		return;
	}
}

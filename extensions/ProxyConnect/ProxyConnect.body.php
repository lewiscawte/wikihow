<?
class ProxyConnect extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'ProxyConnect' );
    }

	function updateRemote() {
		global $wgUser, $wgVanillaDB;
		try {
			$db = new Database($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
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
			if ( $wgUser->isBlocked() ) {
				$db->update('GDN_UserRole', array('RoleID = 1'), $opts);
			} else if (in_array('bureaucrat', $wgUser->getGroups())) {
				$db->update('GDN_UserRole', array('RoleID = 16'), $opts);
			} else if (in_array('sysop', $wgUser->getGroups())) {
				$db->update('GDN_UserRole', array('RoleID = 32'), $opts);
			}
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

		echo $result;
		self::updateRemote();
		return;
	}
}

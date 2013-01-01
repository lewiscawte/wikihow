<?
class Vanilla extends UnlistedSpecialPage {

	public static function setUserRole($userid, $role) {
		global $wgVanillaDB;
		$db = new Database($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
	   	// get vanilla user id
		$vid = $db->selectField('GDN_UserAuthentication', array('UserID'), array('ForeignUserKey'=> $userid));
		$updates = array("RoleID"=>$role);
		$opts = array('UserID'=>$vid);
		$db->update('GDN_UserRole', $updates, $opts);
		return true;
	}

	public static function setAvatar($user) {
		global $wgVanillaDB;
		$db = new Database($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
	   	// get vanilla user id
		$vid = $db->selectField('GDN_UserAuthentication', array('UserID'), array('ForeignUserKey'=> $user->getID()));
		$updates = array("Photo"=>Avatar::getAvatarURL($user->getName()));
		$opts = array('UserID'=>$vid);
		$db->update('GDN_User', $updates, $opts);
		wfDebug("Vanilla: Updating avatar " . print_r($updates, true) . print_r($opts, true) . "\n");
		return true;
	}

    function __construct($source = null) {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Vanilla' );
    }

	function execute($par) {
		global $wgUser, $wgOut;
		if ($wgUser->getID() == 0) {
			$wgOut->redirect('/Special:Userlogin?returnto=vanilla');
			return;
		}
		$wgOut->addHTML("You are not logged into the forums because you do not have an email address specified in your <a href='/Special:Preferences'>preferences</a>.");
		return;
	}
}


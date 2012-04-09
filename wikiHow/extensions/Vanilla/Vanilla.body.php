<?php
/**
 * Special:Vanilla -- functions for interacting with a working Vanilla forum
 * instance.
 * Hooked functions and functions used by hooked functions are public and
 * static.
 *
 * @file
 * @ingroup Extensions
 */
class Vanilla extends UnlistedSpecialPage {
	/**
	 * Set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Vanilla' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut;
		if ( $wgUser->isAnon() ) {
			$userLogin = SpecialPage::getTitleFor( 'Userlogin' );
			$wgOut->redirect(
				$userLogin->getFullURL( 'returnto=vanilla' )
			);
			return;
		}
		$wgOut->addHTML( wfMsgExt( 'vanilla-error-not-logged-in', 'parse' ) );
		return;
	}

	/**
	 * Set the role of a MediaWiki user to something in the Vanilla database.
	 *
	 * @param $userId Integer: MediaWiki user ID
	 * @param $role Integer: Vanilla role ID (1 for blocked users)
	 * @return Boolean: true
	 */
	public static function setUserRole( $userId, $role ) {
		global $wgVanillaDB;
		$db = new Database(
			$wgVanillaDB['host'],
			$wgVanillaDB['user'],
			$wgVanillaDB['password'],
			$wgVanillaDB['dbname']
		);
		// Get Vanilla user ID
		$vid = $db->selectField(
			'GDN_UserAuthentication',
			array( 'UserID' ),
			array( 'ForeignUserKey' => $userId ),
			__METHOD__
		);
		$updates = array( 'RoleID' => $role );
		$opts = array( 'UserID' => $vid );
		$db->update( 'GDN_UserRole', $updates, $opts, __METHOD__ );
		return true;
	}

	/**
	 * Set the given user's avatar in Vanilla to their wiki avatar.
	 *
	 * @param $user Object: MediaWiki User object
	 * @return Boolean: true
	 */
	public static function setAvatar( $user ) {
		global $wgVanillaDB;
		$db = new Database(
			$wgVanillaDB['host'],
			$wgVanillaDB['user'],
			$wgVanillaDB['password'],
			$wgVanillaDB['dbname']
		);
		// Get Vanilla user ID
		$vid = $db->selectField(
			'GDN_UserAuthentication',
			array( 'UserID' ),
			array( 'ForeignUserKey' => $user->getId() ),
			__METHOD__
		);
		$updates = array(
			'Photo' => Avatar::getAvatarURL( $user->getName() )
		);
		$opts = array( 'UserID' => $vid );
		$db->update( 'GDN_User', $updates, $opts, __METHOD__ );
		wfDebugLog(
			'Vanilla',
			'Updating avatar ' . print_r( $updates, true ) .
				print_r( $opts, true )
		);
		return true;
	}

	/**
	 * When a user logs out from the wiki, destroy the cookies set by Vanilla.
	 *
	 * @return Boolean: true
	 */
	public static function destroyCookies() {
		global $wgCookieDomain;
		$cookies = array( 'Vanilla', 'Vanilla-Volatile' );
		foreach ( $cookies as $c ) {
			setcookie( $c, ' ', time() - 3600, '/', '.' . $wgCookieDomain );
			unset( $_COOKIE[$c] );
		}
		return true;
	}

	/**
	 * If the returnto URL parameter is set to 'vanilla' (Special:Vanilla sets it
	 * to that), redirect the user to the Vanilla forum.
	 *
	 * @return Boolean: true
	 */
	public static function processVanillaRedirect() {
		global $wgRequest, $wgOut;
		if ( $wgRequest->getVal( 'returnto' ) == 'vanilla' ) {
			$wgOut->redirect( wfMsgForContent( 'vanilla-forum-url' ) );
		}
		return true;
	}

	/**
	 * When a user is blocked via MediaWiki's blocking interface, block the
	 * user in Vanilla too, if the user is a registered one.
	 *
	 * @param $block Object: Block object
	 * @param $user Object: User object
	 * @return Boolean: true
	 */
	public static function blockVanillaUser( $block, $user ) {
		try {
			if ( $block->mUser == 0 ) {
				return true;
			}
			Vanilla::setUserRole( $block->mUser, 1 );
		} catch ( Exception $e ) {
			print_r( $e );
			exit;
		}
		return true;
	}
}
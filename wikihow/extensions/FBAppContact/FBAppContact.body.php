<?php

class FBAppContact extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'FBAppContact' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgFBAppId, $wgFBAppSecret;

		if ( !class_exists( 'Facebook' ) ) {
			global $IP;
			require_once( $IP . '/extensions/facebook-platform/facebook-php-sdk-771862b/src/facebook.php' );
		}

		$wgOut->setArticleBodyOnly( true );

		$accessToken = $wgRequest->getVal( 'token', null );
		if ( is_null( $accessToken ) ) {
			return;
		}

		$this->facebook = new Facebook( array(
			'appId' => $wgFBAppId,
			'secret' => $wgFBAppSecret
		) );
		$this->facebook->setAccessToken( $accessToken );
		$result = $this->facebook->api( '/me' );

		$dbw = wfGetDB( DB_MASTER );
		$fields = array(
			'fc_user_id' => $result['id'],
			'fc_first_name' => $result['first_name'],
			'fc_last_name' => $result['last_name'],
			'fc_email' => $result['email']
		);
		$dbw->insert( 'facebook_contacts', $fields, __METHOD__, array( 'IGNORE' ) );
		return;
	}
}

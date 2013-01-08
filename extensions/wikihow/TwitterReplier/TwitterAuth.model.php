<?php

class TwitterAuthModel
{

	private $dbw;
	private $dbr;

	const COOKIE_TABLE = 'twitterreplier_cookie';
	const OAUTH_TABLE = 'twitterreplier_oauth';

	function __construct()
	{
		if ( empty( $this->dbw ) ) {
			$this->dbw = wfGetDB( DB_MASTER );
		}

		if ( empty( $this->dbr ) ) {
			$this->dbr = wfGetDB( DB_SLAVE );
		}
	}

	public function isValidHash( $hash )
	{
		$resp = false;
		$fields = array( );
		$fields[] = 'hash';

		$conds = array( );
		$conds['hash'] = $hash;

		try {
			$resp = $this->dbr->selectField( self::COOKIE_TABLE, $fields, $conds );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return $resp;
	}

	public function getHash()
	{
		return!empty( $_COOKIE[TRCOOKIE] ) ? $_COOKIE[TRCOOKIE] : false;
	}

	public function getUserToken( $userId, $userIdType = 'twitter_user_id' )
	{
		$token = false;

		$fields = array( );
		$fields[] = 'token';

		switch ( $userIdType ) {
			case "twitter_user_id":
			case "wikihow_user_id":
				$where = array( );
				$where[$userIdType] = $userId;
				break;
			default:
				return false;
		}

		try {
			$token = $this->dbr->selectField( self::OAUTH_TABLE, $fields, $where );
			el( $this->dbr->lastQuery() );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return $token;
	}

	public function getUserSecret( $userId, $userIdType = 'twitter_user_id' )
	{
		$secret = false;

		$fields = array( );
		$fields[] = 'secret';

		switch ( $userIdType ) {
			case "twitter_user_id":
			case "wikihow_user_id":
				$where = array( );
				$where[$userIdType] = $userId;
				break;
			default:
				return false;
		}


		try {
			$secret = $this->dbr->selectField( self::OAUTH_TABLE, $fields, $where );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return $secret;
	}

	public function getUserTwitterIdByHash( $hash )
	{
		$twitterUserId = false;
		$fields = array( );
		$fields[] = 'twitter_user_id';

		$where = array( );
		$where['hash'] = $hash;

		try {
			$twitterUserId = $this->dbr->selectField( self::COOKIE_TABLE, $fields, $where );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return $twitterUserId;
	}

	public function getUserTwitterIdByWHUserId( $whUserId )
	{
		/* TODO: I think the joins option is added in a later versio of MW
		  $twitterUserId = false;
		  $fields = array();
		  $fields[] = 'twitter_user_id';

		  $where = array();
		  $where['wikihow_user_id'] = $whUserId;

		  $joins = array();
		  $joins[self::OAUTH_TABLE] = array( 'LEFT JOIN' => self::OAUTH_TABLE . '.twitter_user_id = ' . self::COOKIE_TABLE . '.twitter_user_id' );


		  try {
		  $twitterUserId = $this->dbr->selectField( self::COOKIE_TABLE, $fields, $where, null, null, $joins );
		  el( $this->dbr->lastQUery(), __FUNCTION__ );
		  }
		  catch ( Exception $e ) {
		  echo $e->getMessage();
		  }
		 */

		$sql = "SELECT " . self::COOKIE_TABLE . ".twitter_user_id 
				FROM " . self::OAUTH_TABLE . " 
				LEFT JOIN " . self::COOKIE_TABLE . " ON " . self::COOKIE_TABLE . ".twitter_user_id = " . self::OAUTH_TABLE . ".twitter_user_id
				WHERE wikihow_user_id = " . $this->dbr->addQuotes( $whUserId );

		try {
			$results = $this->dbr->query( $sql );
			
			$row = $this->dbr->fetchRow( $results );
			
			return $row['twitter_user_id'];
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return $twitterUserId;
	}

	public function saveAccessToken( $token, $secret, $twitterUserId, $whUserId )
	{
		// TODO: add two way encryption
		$sql = "INSERT IGNORE INTO " . self::OAUTH_TABLE . "
			 (id, token, secret, twitter_user_id, wikihow_user_id, created_on, updated_on )
			 VALUES ( NULL, " . $this->dbw->addQuotes( $token ) . ", " . $this->dbw->addQuotes( $secret ) . ", " . $this->dbw->addQuotes( $twitterUserId ) . ", " . $this->dbw->addQUotes( $whUserId ) . ", " . $this->dbw->addQuotes( date( "Y-m-d H:i:s" ) ) . ", " . $this->dbw->addQuotes( date( "Y-m-d H:i:s" ) ) . ")
			 ON DUPLICATE KEY UPDATE token=VALUES(token), secret=VALUES(secret), updated_on=VALUES(updated_on)";

		if ( $whUserId > 0 ) {
			$sql .= ', wikihow_user_id=VALUES(wikihow_user_id)';
		}
		el( $sql, __FUNCTION__ );
		try {
			$this->dbw->query( $sql );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}
	}

	public function saveHash( $hash, $twitterUserId )
	{
		$createdOn = date( "Y-m-d H:i:s" );
		$updatedOn = date( "Y-m-d H:i:s" );

		$sql = "INSERT IGNORE INTO " . self::COOKIE_TABLE . " ( id, twitter_user_id, hash, created_on, updated_on )
			VALUES ( NULL, " . $this->dbw->addQuotes( $twitterUserId ) . ", " . $this->dbw->addQuotes( $hash ) . ", " . $this->dbw->addQuotes( $createdOn ) . ", " . $this->dbw->addQuotes( $updatedOn ) . " )
			ON DUPLICATE KEY UPDATE updated_on=VALUES( updated_on ), hash=VALUES(hash)";

		try {
			$this->dbw->query( $sql );
		}
		catch ( Exception $e ) {
			echo $e->getMessage();
		}
	}

	public function validateUserTokens( $token, $secret )
	{
		$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET, $token, $secret );
		$twitterInfo = $twitterObj->get_accountVerify_credentials();
		$twitterInfo->response;

		//TODO: finish off this method
	}

	public function updateWHUserId( $twitterUserId, $whUserId )
	{
		if ( is_numeric( $whUserId ) && $whUserId > 0 ) {
			$field_values = array( );
			$field_values['wikihow_user_id'] = $whUserId;

			$cond = array( );
			$cond['wikihow_user_id'] = '0';

			try {
				$this->dbw->update( self::OAUTH_TABLE, $field_values, $cond );
			}
			catch ( Exception $e ) {
				echo $e->getMessage();
			}
		}
	}

	public function getUserAvatar()
	{
		$avatar = false;
		$hash = $this->getHash();
		$twitterId = $this->getUserTwitterIdByHash( $hash );

		$token = $this->getUserToken( $twitterId );
		$secret = $this->getUserSecret( $twitterId );

		try {
			$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET, $token, $secret );
			$twitterInfo = $twitterObj->get_accountVerify_credentials();
			$avatar = $twitterInfo->profile_image_url;
		}
		catch ( Exception $e ) {
			
		}
		
		return $avatar;
	}

	public function getUserScreenName()
	{
		$screenName = false;
		$hash = $this->getHash();
		$twitterId = $this->getUserTwitterIdByHash( $hash );

		$token = $this->getUserToken( $twitterId );
		$secret = $this->getUserSecret( $twitterId );

		try {
			$twitterObj = new EpiTwitter( CONSUMERKEY, CONSUMERSECRET, $token, $secret );
			$twitterInfo = $twitterObj->get_accountVerify_credentials();
			$screenName = $twitterInfo->screen_name;
		}
		catch ( Exception $e ) {
			
		}

		return $screenName;
	}

}
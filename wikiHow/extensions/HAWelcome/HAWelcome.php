<?php

global $IP;
require_once("$IP/includes/JobQueue.php");

/**
 * HAWelcomeJob -- Welcome user after first edit
 *
 * @file
 * @ingroup JobQueue
 *
 * @copyright Copyright © Krzysztof Krzyżaniak for Wikia Inc.
 * @author Krzysztof Krzyżaniak (eloy) <eloy@wikia-inc.com>
 * @author Maciej Błaszkowski (Marooned) <marooned at wikia-inc.com>
 * @date 2009-02-02
 * @version 0.5
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This is a MediaWiki extension and cannot be used standalone.\n";
	exit( 1 );
}

$wgExtensionCredits['other'][] = array(
	'name' => 'HAWelcome',
	'version' => '0.6',
	'author' => array('Krzysztof Krzyżaniak', '[http://www.wikia.com/wiki/User:Marooned Maciej Błaszkowski (Marooned)]'),
	'description' => 'Highly Automated Welcome Tool',
);


/**
 * used hooks
 */
//$wgHooks[ "ArticleSaveComplete" ][]	= "HAWelcomeJob::revisionInsertComplete";
$wgHooks[ "ArticleSaveComplete" ][]	= "HAWelcomeJob::revisionInsertComplete";
$wgHooks[ "UserLoginComplete" ][]	= "HAWelcomeJob::signUpComplete";
$wgHooks[ "FBLoginComplete" ][]		= "HAWelcomeJob::signUpComplete";


/**
 * register job class
 */
$wgJobClasses[ "HAWelcome" ] = "HAWelcomeJob";

/**
 * used messages
 */
$wgExtensionMessagesFiles[ "HAWelcome" ] = dirname(__FILE__) . '/HAWelcome.i18n.php';



class HAWelcomeJob extends Job {

	private
		$mUserId,
		$mUserName,
		$mUserIP,
		$mUser,
		$mAnon,
		$mSysop;

	const WELCOMEUSER = "WelcomeBot";
	const DEFAULTUSER = "Krystle";

	/**
	 * Construct a job
	 *
	 * @param Title $title The title linked to
	 * @param array $params Job parameters (table, start and end page_ids)
	 * @param integer $id job_id
	 */
/*	public function __construct( $title, $params, $id = 0 ) {
		wfLoadExtensionMessages( "HAWelcome" );
		parent::__construct( "HAWelcome", $title, $params, $id );

		$this->mUserId   = $params[ "user_id" ];
		$this->mUserIP   = $params[ "user_ip" ];
		$this->mUserName = $params[ "user_name" ];
		$this->mAnon     = (bool )$params[ "is_anon" ];
		$this->mSysop    = false;

		if( $this->mAnon ) {
			$this->mUser = User::newFromName( $this->mUserIP, false );
		}
		else {
			$this->mUser = User::newFromId( $this->mUserId );
		}
*/
		/**
		 * fallback
		 */
/*		if( ! $this->mUser ) {
			$this->mUser = User::newFromName( $this->mUserName );
		}
	}
*/	

	public function run() {
		return;
	}
	/**
	 * main entry point
	 *
	 * @access public
	 */
	public function runEditThanks($Title) {
		global $wgUser, $wgTitle, $wgErrorLog;

		wfProfileIn( __METHOD__ );
		
		//set the variables (used to be in __construct() )
		$mUserId   = $wgUser->getID();
		$mUserIP   = wfGetIp();
		$mUserName = $wgUser->getName();
		$mAnon     = $wgUser->isAnon();
		$mSysop    = false;

		if( $mAnon ) {
			$mUser = User::newFromName( $mUserIP, false );
		}
		else {
			//only for anons right now
			return false;
			$mUser = User::newFromId( $mUserId );
		}

		/**
		 * fallback
		 */
		if( !$mUser ) {
			$mUser = User::newFromName( $mUserName );
		}
		

		$oldValue = $wgErrorLog;
		$wgErrorLog = true;

		/**
		 * overwrite $wgUser for ~~~~ expanding
		 */
		$sysop = trim( wfMsgForContent( "welcome-user" ) );
		if( !in_array( $sysop, array( "@disabled", "-" ) ) ) {

			$tmpUser = $wgUser;
			$wgUser  = User::newFromName( self::WELCOMEUSER );
			$flags = 0;
			$bot_message = trim( wfMsgForContent( "welcome-bot" ) );
			if( ($bot_message == '@bot') || ($wgUser && $wgUser->isAllowed( 'bot' )) ) {
				$flags = EDIT_FORCE_BOT | EDIT_SUPPRESS_RC;
			}
			else {
				$flags = EDIT_SUPPRESS_RC;
			}

			if( $mUser && $mUser->getName() !== self::WELCOMEUSER ) {

				/**
				 * check again if talk page exists
				 */
				$talkPage  = $mUser->getUserPage()->getTalkPage();

				if( $talkPage ) {

					if ($mAnon) {
						$user = User::newFromName( self::DEFAULTUSER )->getId();
						$mSysop = User::newFromId( $user );
					}
					else {
						$mSysop = self::getLastSysop();
					}
					$gEG = $mSysop->getEffectiveGroups();
					$isSysop = in_array('sysop', $gEG);
					$isStaff = in_array('staff', $gEG);
					unset($gEG);
					$tmpTitle	= $Title;
					$sysopPage    = $mSysop->getUserPage()->getTalkPage();
					$signature    = self::expandSig();

					$wgTitle     = $talkPage;
					$welcomeMsg  = false;
					$talkArticle = new Article( $talkPage, 0 );

//					if( ! $talkArticle->exists() ) {
						if( $mAnon ) {				
							if( self::isEnabled( "message-anon" ) ) {
								if( $isStaff && !$isSysop ) {
									$key = "welcome-message-anon-staff";
								}
								else {
									$key = "welcome-message-anon";
								}
								$welcomeMsg = wfMsgExt( $key, array("parsemag", "content"),
								array(
									self::getPrefixedText($tmpTitle),
									$sysopPage->getPrefixedText(),
									$signature,
									wfEscapeWikiText( $mUser->getName() ),
								));
							}
						}
						else {
							/**
							 * now create user page (if not exists of course)
							 */
							
							if(self::isEnabled( "page-user" )) {
								$userPage = $mUser->getUserPage();

								if( $userPage ) {
									$wgTitle = $userPage;
									$userArticle = new Article( $userPage, 0 );
									if( ! $userArticle->exists() ) {
										$pageMsg = wfMsgForContent( "welcome-user-page" );
										$userArticle->doEdit( $pageMsg, false, $flags );
									}
								}
							}

							if( self::isEnabled( "message-user" ) ) {
								if( $isStaff && !$isSysop ) {
									$key = "welcome-message-user-staff";
								}
								else {
									$key = "welcome-message-user";
								}
								$welcomeMsg = wfMsgExt( $key, array("parsemag", "content"),
								array(
									self::getPrefixedText($tmpTitle),
									$sysopPage->getPrefixedText(),
									$signature,
									wfEscapeWikiText( $mUser->getName() ),
								));
							}
						}
						if( $welcomeMsg ) {
							global $wgLang;
							
							$wgTitle = $talkPage; /** is it necessary there? **/
							$dateStr = $wgLang->timeanddate(wfTimestampNow());
							$real_name = User::whoIsReal($mSysop->getID());
							if ($real_name == "") { $real_name = $mSysop->getName(); }
							$comment = $welcomeMsg;
							
							$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $mSysop->getName(), $real_name, $comment);

							$talkArticle->doEdit( $formattedComment, wfMsgForContent( "welcome-message-log" ), $flags );
						}
//					}
					$wgTitle = $tmpTitle;
				}
			}

			$wgUser = $tmpUser;
			$wgErrorLog = $oldValue;
		}

		wfProfileOut( __METHOD__ );

		return true;
	}

	public function runWelcome() {
		global $wgUser;

		wfProfileIn( __METHOD__ );
		
		//set the variables (used to be in __construct() )
		$mUserId   = $wgUser->getID();
		$mUserIP   = wfGetIp();
		$mUserName = $wgUser->getName();
		$mAnon     = $wgUser->isAnon();
		$mSysop    = false;

		if( $mAnon ) {
			return false;
		}
			
		$mUser = User::newFromId( $mUserId );

		/**
		 * fallback
		 */
		if( !$mUser ) {
			$mUser = User::newFromName( $mUserName );
		}
		

		$oldValue = $wgErrorLog;
		$wgErrorLog = true;

		/**
		 * overwrite $wgUser for ~~~~ expanding
		 */
		$sysop = trim( wfMsgForContent( "welcome-user" ) );
		if( !in_array( $sysop, array( "@disabled", "-" ) ) ) {

			$tmpUser = $wgUser;
			$wgUser  = User::newFromName( self::WELCOMEUSER );
			$flags = 0;
			$bot_message = trim( wfMsgForContent( "welcome-bot" ) );
			if( ($bot_message == '@bot') || ($wgUser && $wgUser->isAllowed( 'bot' )) ) {
				$flags = EDIT_FORCE_BOT | EDIT_SUPPRESS_RC;
			}
			else {
				$flags = EDIT_SUPPRESS_RC;
			}

			if( $mUser && $mUser->getName() !== self::WELCOMEUSER ) {

				/**
				 * check again if talk page exists
				 */
				$talkPage  = $mUser->getUserPage()->getTalkPage();

				if( $talkPage ) {

					$mSysop = self::getLastSysop();
					$gEG = $mSysop->getEffectiveGroups();
					$isSysop = in_array('sysop', $gEG);
					$isStaff = in_array('staff', $gEG);
					unset($gEG);
					$sysopPage    = $mSysop->getUserPage()->getTalkPage();
					$signature    = self::expandSig();
					$welcomeMsg  = false;
					$talkArticle = new Article( $talkPage, 0 );

					if( ! $talkArticle->exists() ) {
						/**
						 * now create user page (if not exists of course)
						 */
						
						if(self::isEnabled( "page-user" )) {
							$userPage = $mUser->getUserPage();

							if( $userPage ) {
								$userArticle = new Article( $userPage, 0 );
								if( ! $userArticle->exists() ) {
									$pageMsg = wfMsgForContent( "welcome-user-page" );
									$userArticle->doEdit( $pageMsg, false, $flags );
								}
							}
						}

						if( self::isEnabled( "message-user" ) ) {
							if( $isStaff && !$isSysop ) {
								$key = "welcome-message-user-staff";
							}
							else {
								$key = "welcome-message-user";
							}
							$welcomeMsg = wfMsgExt( $key, array("parsemag", "content"),
							array(
								'',
								$sysopPage->getPrefixedText(),
								$signature,
								wfEscapeWikiText( $mUser->getName() ),
							));
						}
						if( $welcomeMsg ) {
							global $wgLang;
							
							$dateStr = $wgLang->timeanddate(wfTimestampNow());
							$real_name = User::whoIsReal($mSysop->getID());
							if ($real_name == "") { $real_name = $mSysop->getName(); }
							$comment = $welcomeMsg;
							
							$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $mSysop->getName(), $real_name, $comment);

							$talkArticle->doEdit( $formattedComment, wfMsgForContent( "welcome-message-log" ), $flags );
						}
					}
				}
			}

			$wgUser = $tmpUser;
			$wgErrorLog = $oldValue;
		}

		wfProfileOut( __METHOD__ );

		return true;
	}

	//returns boolean
	//tests if the user has opted out of the welcoming tool
	public function IsUserWelcoming($uid) {
		$user = User::newFromId( $uid );
		
		if ($user->getOption('welcomer') == 0) {
			return true;
		}
		else {
			return false;
		}
	}


	/**
	 * get last active sysop for this wiki, use local user database
	 *
	 * @access public
	 *
	 * @return User class instance
	 */
	public function getLastSysop() {
		global $wgCityId, $wgMemc, $wgLanguageCode;

		wfProfileIn( __METHOD__ );

		/**
		 * maybe already loaded?
		 */
		if( ! $mSysop ) {

			$sysop = trim( wfMsgForContent( "welcome-user" ) );
			if( !in_array( $sysop, array( "@disabled", "-" ) ) ) {

				if( in_array( $sysop, array( "@latest", "@sysop" ) ) ) {
					/**
					 * first: check memcache, maybe we have already stored id of sysop
					 */
					$sysopId = $wgMemc->get( wfMemcKey( "last-sysop-id" ) );
					
					if (!self::IsUserWelcoming($sysopId)) {
						$sysopId = '';
					}
					
					if( $sysopId ) {
						$mSysop = User::newFromId( $sysopId );
					}
					else {
						/**
						 * second: check database, could be expensive for database
						 */
						$dbr = wfGetDB( DB_SLAVE );

						/**
						 * get all users which are sysops/sysops or staff or helpers
						 * but not bots
						 *
						 * @todo check $db->makeList( $array )
						 */
						$groups = ($sysop !== "@sysop")
							? array( "ug_group" => array( "sysop", "bot", "newarticlepatrol" ) )
							: array( "ug_group" => array( "sysop", "bot" ) );

						$bots   = array();
						$admins = array();
						$res = $dbr->select(
							array( "user_groups" ),
							array( "ug_user, ug_group" ),
							$dbr->makeList( $groups, LIST_OR ),
							__METHOD__
						);
						while( $row = $dbr->fetchObject( $res ) ) {
							if( $row->ug_group == "bot" ) {
								$bots[] = $row->ug_user;
							} 
							else {
								$admins[] = $row->ug_user;
							}
						}
						$dbr->freeResult( $res );

						/**
						 * remove bots from admins
						 */
						$admins = array( "rev_user" => array_unique( array_diff( $admins, $bots ) ) );
						$res = $dbr->select(
							array( "revision" ),
							array( "rev_user", "rev_user_text"),
							array(
								$dbr->makeList( $admins, LIST_OR ),
								"rev_timestamp > " . $dbr->addQuotes(  $dbr->timestamp( time() - 5184000 ) ) // 60 days ago (24*60*60*60)
							),
							__METHOD__,
							array( "ORDER BY" => "rev_timestamp DESC", "DISTINCT", "LIMIT" => 10 )
						);

						while ( $row = $dbr->fetchObject( $res ) ) {
							if (self::IsUserWelcoming($row->rev_user)) {
								$user = $row->rev_user;
								break;
							}
						}
						if ( empty( $user ) ) {
							$user = User::newFromName( self::DEFAULTUSER )->getId();
						}

						$mSysop = User::newFromId( $user );
						//$wgMemc->set( wfMemcKey( "last-sysop-id" ), $user, 86400 );
						$wgMemc->set( wfMemcKey( "last-sysop-id" ), $user, 3600 );
					}
				}
				else {
					$mSysop = User::newFromName( $sysop );
				}
			}
		}
		wfProfileOut( __METHOD__ );

		return $mSysop;
	}

	/**
	 * revisionInsertComplete
	 *
	 * static method called as hook
	 *
	 * @static
	 * @access public
	 *
	 * @param Revision	$revision	revision object
	 * @param string	$url		url to external object
	 * @param string	$flags		flags for this revision
	 *
	 * @return true means process other hooks
	 */
	public static function revisionInsertComplete( &$revision, $url, $flags ) {
		global $wgUser, $wgCityId, $wgCommandLineMode, $wgSharedDB,
			$wgErrorLog, $wgMemc, $wgRequest;
		
		//do nothing if the user clicked 'undo'
		if ($wgRequest->getVal( 'wpUndoEdit' )) {
			return false;
		}
		
		wfProfileIn( __METHOD__ );
		
		/* first edit? */
		//if (User::edits($wgUser->getID()) == 1) {
			
			/**
			 * Do not create task when DB is locked (rt#12229)
			 * Do not create task when we are in $wgCommandLineMode
			 */
			$oldValue = $wgErrorLog;
			$wgErrorLog = true;
			if( !wfReadOnly() && ! $wgCommandLineMode ) {
				wfLoadExtensionMessages( "HAWelcome" );

				/**
				 * Revision has valid Title field but sometimes not filled
				 */
				$Title = $revision->getTitle();
				if( !$Title ) {
					$Title = Title::newFromId( $revision->getPage(), GAID_FOR_UPDATE );
					$revision->setTitle( $Title );
				}

				/**
				 * get groups for user rt#12215
				 */
				$groups = $wgUser->getEffectiveGroups();
				$invalid = array(
					"bot" => true,
					"staff" => true,
					"helper" => true,
					"sysop" => true,
					"bureaucrat" => true,
					"vstf" => true,
				);
				$canWelcome = true;
				foreach( $groups as $group ) {
					if( isset( $invalid[ $group ] ) && $invalid[ $group ] ) {
						$canWelcome = false;
						break;
					}
				}

				/**
				 * put possible welcomer into memcached, RT#14067
				 */
				if( $wgUser->getId() && self::isWelcomer( $wgUser ) ) {
					//$wgMemc->set( wfMemcKey( "last-sysop-id" ), $wgUser->getId(), 86400 );
					$wgMemc->set( wfMemcKey( "last-sysop-id" ), $wgUser->getId(), 3600 );
				}

				if( $Title && $canWelcome && !empty( $wgSharedDB ) ) {

					$welcomer = trim( wfMsgForContent( "welcome-user" ) );

					if( $welcomer !== "@disabled" && $welcomer !== "-" ) {

						/**
						 * check if talk page for wgUser exists
						 *
						 * @todo check editcount for user
						 */
						$talkPage = $wgUser->getUserPage()->getTalkPage();
						if( $talkPage ) {
							$talkArticle = new Article( $talkPage, 0 );
							if( !$talkArticle->exists() ) {
								//run the talk page stuff
								self::runEditThanks($Title);
							}
						}
					}
				}
			}
		//}
		$wgErrorLog = $oldValue;
		wfProfileOut( __METHOD__ );

		return true;
	}


	/**
	 * signUpComplete
	 *
	 * static method called as hook
	 *
	 * @static
	 * @access public
	 *
	 * @param Revision	$revision	revision object
	 * @param string	$url		url to external object
	 * @param string	$flags		flags for this revision
	 *
	 * @return true means process other hooks
	 */
	public static function signUpComplete() {
		global $wgUser, $wgCityId, $wgCommandLineMode, $wgSharedDB,
			$wgErrorLog, $wgMemc;
		
		wfProfileIn( __METHOD__ );
		
		
		/**
		 * Do not create task when DB is locked (rt#12229)
		 * Do not create task when we are in $wgCommandLineMode
		 */
		$oldValue = $wgErrorLog;
		$wgErrorLog = true;
		if( !wfReadOnly() && ! $wgCommandLineMode ) {
			wfLoadExtensionMessages( "HAWelcome" );

			/**
			 * get groups for user rt#12215
			 */
			$groups = $wgUser->getEffectiveGroups();
			$invalid = array(
				"bot" => true,
				"staff" => true,
				"helper" => true,
				"sysop" => true,
				"bureaucrat" => true,
				"vstf" => true,
			);
			$canWelcome = true;
			foreach( $groups as $group ) {
				if( isset( $invalid[ $group ] ) && $invalid[ $group ] ) {
					$canWelcome = false;
					break;
				}
			}

			/**
			 * put possible welcomer into memcached, RT#14067
			 */
			if( $wgUser->getId() && self::isWelcomer( $wgUser ) ) {
				//$wgMemc->set( wfMemcKey( "last-sysop-id" ), $wgUser->getId(), 86400 );
				$wgMemc->set( wfMemcKey( "last-sysop-id" ), $wgUser->getId(), 3600 );
			}

			if($canWelcome && !empty( $wgSharedDB ) ) {

				$welcomer = trim( wfMsgForContent( "welcome-user" ) );
				
				if( $welcomer !== "@disabled" && $welcomer !== "-" ) {

					/**
					 * check if talk page for wgUser exists
					 *
					 * @todo check editcount for user
					 */
					$talkPage = $wgUser->getUserPage()->getTalkPage();
					if( $talkPage ) {
						$talkArticle = new Article( $talkPage, 0 );
						if( !$talkArticle->exists() ) {
							//run the talk page stuff
							self::runWelcome();
						}
					}
				}
			}
		}
		$wgErrorLog = $oldValue;
		wfProfileOut( __METHOD__ );

		return true;
	}
	
	

	/**
	 * expandSig -- hack, expand signature from message for sysop
	 *
	 * @access private
	 */
	private function expandSig( ) {

		global $wgContLang, $wgUser;

		wfProfileIn( __METHOD__ );

		// get the welcomer
		$mSysop = self::getLastSysop();

		// backup the current
		$tmpUser = $wgUser;
		// swap in the welcomer (why do we need to do this?)
		$wgUser = $mSysop;

		// figure out who/what this welcomer is
		$gEG = $mSysop->getEffectiveGroups();
		$isStaff = in_array('staff', $gEG);
		$isSysop = in_array('sysop', $gEG);

		// only build these once, since its used both cases
		$SysopName = wfEscapeWikiText( $mSysop->getName() );
		$userLink = sprintf(
			'[[%s:%s|%s]]',
			$wgContLang->getNsText(NS_USER),
			$SysopName,
			$SysopName
			);

		// in cases where user is both staff and sysop, use sysop mode
		if(!$isStaff || $isSysop) {
			$signature = sprintf(
				"-- %s ([[%s:%s|%s]]) %s",
				$userLink,
				$wgContLang->getNsText(NS_USER_TALK),
				$SysopName,
				wfMsgForContent( "talkpagelinktext" ),
				$wgContLang->timeanddate( wfTimestampNow( TS_MW ) )
				);
		} else {
			// $1 = wiki link to user's user: page
			// $2 = plain version of user's name (for future use)
			$signature = wfMsgForContent('staffsig-text', $userLink, $SysopName);
		}

		// restore from backup
		$wgUser = $tmpUser;

		wfProfileOut( __METHOD__ );

		return $signature;
	}

	/**
	 * @access public
	 *
	 * @return Title instance of Title object
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @access private
	 *
	 * @return Title instance of Title object
	 */
	public function getPrefixedText($title) {

		$prefixedText = $title->getPrefixedText();

		wfRunHooks( 'HAWelcomeGetPrefixText' , array( &$prefixedText, $title ) ); //

		return $prefixedText;
	}


	/**
	 * check if some (or all) functionality is disabled/enabled
	 *
	 * @param String $what default false
	 *
	 * possible vaules for $what: page-user, message-anon, message-user
	 *
	 * @access public
	 *
	 * @return Bool disabled or not
	 */
	public function isEnabled( $what ) {

		wfProfileIn( __METHOD__ );

		$return = false;
		$message = wfMsgForContent( "welcome-enabled" );
		//LogPage::log( __METHOD__, "enabled", $message );
		if( in_array( $what, array( "page-user", "message-anon", "message-user" ) )
			&& strpos( $message, $what  ) !== false ) {
			$return	= true;
		}

		wfProfileOut( __METHOD__ );

		return $return;
	}

	/**
	 * check if user can welcome other users
	 *
	 * @static
	 * @access public
	 *
	 * @param User	$User	instance of User class
	 *
	 * @return boolean	status of operation
	 */
	static public function isWelcomer( &$User ) {

		wfProfileIn( __METHOD__ );

		$sysop  = trim( wfMsgForContent( "welcome-user" ) );
		$groups = $User->getEffectiveGroups();
		$result = false;

		/**
		 * bots can't welcome
		 */
		if( !in_array( "bot", $groups ) ) {
			if( $sysop === "@sysop" ) {
				$result = in_array( "sysop", $groups ) ? true : false;
			}
			else {
				$result =
					in_array( "sysop", $groups ) ||
					in_array( "newarticlepatrol", $groups )
						? true : false;
			}
		}
		wfProfileOut( __METHOD__ );

		return $result;
	}
}


$wgSpecialPages['HAWelcomeEdit'] = 'HAWelcomeEdit';
$wgSpecialPageGroups['HAWelcomeEdit'] = 'wiki';

$wgAvailableRights[] = 'HAWelcomeEdit';
$wgGroupPermissions['*']['HAWelcomeEdit'] = false;
$wgGroupPermissions['staff']['HAWelcomeEdit'] = true;

$wgAutoloadClasses['HAWelcomeEdit'] = dirname(__FILE__) . '/HAWelcomeEdit.body.php';

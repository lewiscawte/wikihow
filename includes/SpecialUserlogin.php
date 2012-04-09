<?php
/**
 *
 * @addtogroup SpecialPage
 */

/**
 * constructor
 */
function wfSpecialUserlogin( $par = '' ) {
	global $wgRequest, $wgHooks;
	if( session_id() == '' ) {
		wfSetupSession();
	}

	$form = new LoginForm( $wgRequest, $par );
	$form->execute();
	$wgHooks['BeforeTabsLine'][] = array('LoginForm::topContent', $form);
}

/**
 * implements Special:Login
 * @addtogroup SpecialPage
 */
class LoginForm {

	const SUCCESS = 0;
	const NO_NAME = 1;
	const ILLEGAL = 2;
	const WRONG_PLUGIN_PASS = 3;
	const NOT_EXISTS = 4;
	const WRONG_PASS = 5;
	const EMPTY_PASS = 6;
	const RESET_PASS = 7;
	const ABORTED = 8;
	// XXADDED
	const NO_EMAIL = 9;
	const MULTIPLE_EMAILS = 10;

	var $mName, $mPassword, $mRetype, $mReturnTo, $mCookieCheck, $mPosted;
	var $mAction, $mCreateaccount, $mCreateaccountMail, $mMailmypassword;
	var $mLoginattempt, $mRemember, $mEmail, $mDomain, $mLanguage;

	/**
	 * Constructor
	 * @param WebRequest $request A WebRequest object passed by reference
	 */
	function LoginForm( &$request, $par = '' ) {
		global $wgLang, $wgAllowRealName, $wgEnableEmail;
		global $wgAuth;

		$this->mType = ( $par == 'signup' ) ? $par : $request->getText( 'type' ); # Check for [[Special:Userlogin/signup]]
		$this->mName = $request->getText( 'wpName' );
		$this->mPassword = $request->getText( 'wpPassword' );
		$this->mRetype = $request->getText( 'wpRetype' );
		$this->mRetypeEmail = $request->getText( 'wpRetypeEmail' );
		$this->mDomain = $request->getText( 'wpDomain' );
		$this->mReturnTo = $request->getVal( 'returnto' );
		$this->mCookieCheck = $request->getVal( 'wpCookieCheck' );
		$this->mPosted = $request->wasPosted();
		$this->mCreateaccount = $request->getCheck( 'wpCreateaccount' );
		$this->mCreateaccountMail = $request->getCheck( 'wpCreateaccountMail' )
		                            && $wgEnableEmail;
		$this->mMailmypassword = $request->getCheck( 'wpMailmypassword' )
		                         && $wgEnableEmail;
		$this->mLoginattempt = $request->getCheck( 'wpLoginattempt' );
		$this->mAction = $request->getVal( 'action' );
		$this->mRemember = $request->getCheck( 'wpRemember' );
		$this->mLanguage = $request->getText( 'uselang' );

		if( $wgEnableEmail ) {
			$this->mEmail = $request->getText( 'wpEmail' );
		} else {
			$this->mEmail = '';
		}
		if( $wgAllowRealName && $request->getText('wpUseRealNameAsDisplay') == "on") {
		    $this->mRealName = $request->getText( 'wpRealName' );
		} else {
		    $this->mRealName = '';
		}

		if( !$wgAuth->validDomain( $this->mDomain ) ) {
			$this->mDomain = 'invaliddomain';
		}
		$wgAuth->setDomain( $this->mDomain );

		# When switching accounts, it sucks to get automatically logged out
		if( $this->mReturnTo == $wgLang->specialPage( 'Userlogout' ) ) {
			$this->mReturnTo = '';
		}
	}

	function execute() {
		global $wgOut;
		// XXCHANGED
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/wikihow/LoginReminder.js?rev=') . WH_SITEREV . '"></script>');
		if (class_exists('WikihowCSSDisplay'))
			WikihowCSSDisplay::setSpecialBackground(true);
		if ( !is_null( $this->mCookieCheck ) ) {
			$this->onCookieRedirectCheck( $this->mCookieCheck );
			return;
		} else if( $this->mPosted ) {
			if( $this->mCreateaccount ) {
				return $this->addNewAccount();
			} else if ( $this->mCreateaccountMail ) {
				return $this->addNewAccountMailPassword();
			} else if ( $this->mMailmypassword ) {
				return $this->mailPassword();
			} else if ( ( 'submitlogin' == $this->mAction ) || $this->mLoginattempt ) {
				return $this->processLogin();
			}
		}
		$this->mainLoginForm( '' );
	}

	/**
	 * @private
	 */
	function addNewAccountMailPassword() {
		global $wgOut;

		if ('' == $this->mEmail) {
			$this->mainLoginForm( wfMsg( 'noemail', htmlspecialchars( $this->mName ) ) );
			return;
		}

		$u = $this->addNewaccountInternal();

		if ($u == NULL) {
			return;
		}

		// Wipe the initial password and mail a temporary one
		$u->setPassword( null );
		$u->saveSettings();
		$result = $this->mailPasswordInternal( $u, false, 'createaccount-title', 'createaccount-text' );

		wfRunHooks( 'AddNewAccount', array( $u, true ) );

		$wgOut->setPageTitle( wfMsg( 'accmailtitle' ) );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		$wgOut->setArticleRelated( false );

		if( WikiError::isError( $result ) ) {
			$this->mainLoginForm( wfMsg( 'mailerror', $result->getMessage() ) );
		} else {
			$wgOut->addWikiMsg( 'accmailtext', $u->getName(), $u->getEmail() );
			$wgOut->returnToMain( false );
		}
		$u = 0;
	}


	/**
	 * @private
	 */
	function addNewAccount() {
		global $wgUser, $wgEmailAuthentication;
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;

		# Create the account and abort if there's a problem doing so
		$u = $this->addNewAccountInternal();
		if( $u == NULL )
			return;

		# If we showed up language selection links, and one was in use, be
		# smart (and sensible) and save that language as the user's preference
		global $wgLoginLanguageSelector;
		if( $wgLoginLanguageSelector && $this->mLanguage )
			$u->setOption( 'language', $this->mLanguage );

		# Default teen filter to ON
		//$u->setOption('contentfilter',2);
			
		# Save user settings and send out an email authentication message if needed
		$u->saveSettings();
		if( $wgEmailAuthentication && User::isValidEmailAddr( $u->getEmail() ) ) {
			global $wgOut;
			$error = $u->sendConfirmationMail();
			if( WikiError::isError( $error ) ) {
				$wgOut->addWikiMsg( 'confirmemail_sendfailed', $error->getMessage() );
			} else {
				$wgOut->addWikiMsg( 'confirmemail_oncreate' );
			}
		}
		# If not logged in, assume the new account as the current one and set session cookies
		# then show a "welcome" message or a "need cookies" message as needed
		if( $wgUser->isAnon() ) {
			// XXADDED
			// Set a cohort cookie for a very, very long time (10 years). 
			// Only set one cookie.  If user creates additional accounts later, too bad.
			if (!isset($_COOKIE[$wgCookiePrefix . 'acctTypeA'])) {
				$cookieExp = time() + (60 * 60 * 24 * 365 * 10);
				$cohortType = time() % 2;
				// cookie value is "<userid>|<acct class>"
				setcookie( $wgCookiePrefix.'acctTypeA', $u->getID() . "|" . $cohortType, $cookieExp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
			}

			// XXAdded 
			// Another cohort cookie test.  In this case, we'll also set a session cookie to be used to only tracking subsequent
			// visits to wikihow
			if (!isset($_COOKIE[$wgCookiePrefix . 'acctTypeB'])) {
				$cookieExp = time() + (60 * 60 * 24 * 365 * 10);
				$cohortType = time() % 2;
				// cookie value is "<userid>|<acct class>"
				setcookie( $wgCookiePrefix.'acctTypeB', $u->getID() . "|" . $cohortType, $cookieExp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
				// session cookie to represent that acctTypeB cookie was just created
				setcookie( $wgCookiePrefix . 'acctSes', "1", 0);
			}

			$wgUser = $u;
			$wgUser->setCookies();
			wfRunHooks( 'AddNewAccount', array( $wgUser ) );
			if( $this->hasSessionCookie() ) {
				//return $this->successfulLogin( wfMsg( 'welcomecreation', $wgUser->getName() ), false );
				return $this->successfulLogin( wfMsg( 'welcomecreation-landing'), false, true );
			} else {
				return $this->cookieRedirectCheck( 'new' );
			}
		} else {
			# Confirm that the account was created
			global $wgOut;
			$self = SpecialPage::getTitleFor( 'Userlogin' );
			$wgOut->setPageTitle( wfMsgHtml( 'accountcreated' ) );
			$wgOut->setArticleRelated( false );
			$wgOut->setRobotPolicy( 'noindex,nofollow' );
			$wgOut->addHtml( wfMsgWikiHtml( 'accountcreatedtext', $u->getName() ) );
			$wgOut->returnToMain( false, $self );
			wfRunHooks( 'AddNewAccount', array( $u ) );
			return true;
		}
	}

	/**
	 * @private
	 */
	function addNewAccountInternal() {
		global $wgUser, $wgOut;
		global $wgEnableSorbs, $wgProxyWhitelist;
		global $wgMemc, $wgAccountCreationThrottle;
		global $wgAuth, $wgMinimalPasswordLength;
		global $wgEmailConfirmToEdit;

		// If the user passes an invalid domain, something is fishy
		if( !$wgAuth->validDomain( $this->mDomain ) ) {
			$this->mainLoginForm( wfMsg( 'wrongpassword' ) );
			return false;
		}

		// If we are not allowing users to login locally, we should
		// be checking to see if the user is actually able to
		// authenticate to the authentication server before they
		// create an account (otherwise, they can create a local account
		// and login as any domain user). We only need to check this for
		// domains that aren't local.
		if( 'local' != $this->mDomain && '' != $this->mDomain ) {
			if( !$wgAuth->canCreateAccounts() && ( !$wgAuth->userExists( $this->mName ) || !$wgAuth->authenticate( $this->mName, $this->mPassword ) ) ) {
				$this->mainLoginForm( wfMsg( 'wrongpassword' ) );
				return false;
			}
		}

		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return false;
		}

		# Check permissions
		if ( !$wgUser->isAllowed( 'createaccount' ) ) {
			$this->userNotPrivilegedMessage();
			return false;
		} elseif ( $wgUser->isBlockedFromCreateAccount() ) {
			$this->userBlockedMessage();
			return false;
		}

		$ip = wfGetIP();
		if ( $wgEnableSorbs && !in_array( $ip, $wgProxyWhitelist ) &&
		  $wgUser->inSorbsBlacklist( $ip ) )
		{
			$this->mainLoginForm( wfMsg( 'sorbs_create_account_reason' ) . ' (' . htmlspecialchars( $ip ) . ')' );
			return;
		}

		# Now create a dummy user ($u) and check if it is valid
		$name = trim( $this->mName );
		$u = User::newFromName( $name, 'creatable' );
		if ( is_null( $u ) ) {
			$this->mainLoginForm( wfMsg( 'noname' ), 'error_username' );
			return false;
		}

		if ( 0 != $u->idForName() ) {
			$this->mainLoginForm( wfMsg( 'userexists' ), 'error_username' );
			return false;
		}

		//XXADDED
		if (strpos($this->mName, "_") === 0) {
			$this->mainLoginForm( wfMsg( 'invalidusernamechar', "_" ) );
			return false;
		}

		if ( 0 != strcmp( $this->mPassword, $this->mRetype ) ) {
			$this->mainLoginForm( wfMsg( 'badretype' ), 'error_password' );
			return false;
		}

		# check for minimal password length
		if ( !$u->isValidPassword( $this->mPassword ) ) {
			if ( !$this->mCreateaccountMail ) {
				$this->mainLoginForm( wfMsg( 'passwordtooshort', $wgMinimalPasswordLength ), 'error_password' );
				return false;
			} else {
				# do not force a password for account creation by email
				# set pseudo password, it will be replaced later by a random generated password
				$this->mPassword = '-';
			}
		}

		# if you need a confirmed email address to edit, then obviously you need an email address.
		if ( $wgEmailConfirmToEdit && empty( $this->mEmail ) ) {
			$this->mainLoginForm( wfMsg( 'noemailtitle' ) );
			return false;
		}

		if( !empty( $this->mEmail ) && !User::isValidEmailAddr( $this->mEmail ) ) {
			$this->mainLoginForm( wfMsg( 'invalidemailaddress' ) );
			return false;
		}

		# Set some additional data so the AbortNewAccount hook can be
		# used for more than just username validation
		$u->setEmail( $this->mEmail );
		$u->setRealName( $this->mRealName );

		$abortError = '';
		if( !wfRunHooks( 'AbortNewAccount', array( $u, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			$this->mainLoginForm( $abortError, 'error_captcha' );
			return false;
		}

		if ( $wgAccountCreationThrottle && $wgUser->isPingLimitable() ) {
			$key = wfMemcKey( 'acctcreate', 'ip', $ip );
			$value = $wgMemc->incr( $key );
			if ( !$value ) {
				$wgMemc->set( $key, 1, 86400 );
			}
			if ( $value > $wgAccountCreationThrottle ) {
				$this->throttleHit( $wgAccountCreationThrottle );
				return false;
			}
		}

		if( !$wgAuth->addUser( $u, $this->mPassword, $this->mEmail, $this->mRealName ) ) {
			$this->mainLoginForm( wfMsg( 'externaldberror' ) );
			return false;
		}

		return $this->initUser( $u, false );
	}

	/**
	 * Actually add a user to the database.
	 * Give it a User object that has been initialised with a name.
	 *
	 * @param $u User object.
	 * @param $autocreate boolean -- true if this is an autocreation via auth plugin
	 * @return User object.
	 * @private
	 */
	function initUser( $u, $autocreate ) {
		global $wgAuth;

		$u->addToDatabase();

		if ( $wgAuth->allowPasswordChange() ) {
			$u->setPassword( $this->mPassword );
		}

		$u->setEmail( $this->mEmail );
		$u->setRealName( $this->mRealName );
		$u->setToken();

		$wgAuth->initUser( $u, $autocreate );

		$u->setOption( 'rememberpassword', $this->mRemember ? 1 : 0 );
		$u->saveSettings();

		# Update user count
		$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();

		return $u;
	}

	/**
	 * Internally authenticate the login request.
	 *
	 * This may create a local account as a side effect if the
	 * authentication plugin allows transparent local account
	 * creation.
	 *
	 * @public
	 */
	function authenticateUserData() {
		global $wgUser, $wgAuth;
		if ( '' == $this->mName ) {
			return self::NO_NAME;
		}

		// XXCHANGED - by Reuben to allow login via Username or Email
		$u = null;

		// Check if $this->mName is actually an email address
		$looksLikeEmail = strpos($this->mName, '@') !== false;
		if ( $looksLikeEmail ) {
			list($u, $count) = User::newFromEmailAddress( $this->mName );
		}

		// Only do the username lookup if it didn't look like an email address
		// or the email addresses didn't have exactly 1 account attached
		if ( is_null( $u ) ) {
			$u = User::newFromName( $this->mName );
			// Show error specific to email addresses if there's no username
			// with an '@' in it either
			if ($looksLikeEmail) {
				if ($count < 1) {
					return self::NO_EMAIL;
				} elseif ($count > 1) {
					return self::MULTIPLE_EMAILS;
				}
			}

			if ( is_null( $u ) || !User::isUsableName( $u->getName() ) ) {
				return self::ILLEGAL;
			}
		}

		if ( 0 == $u->getID() ) {
			global $wgAuth;
			/**
			 * If the external authentication plugin allows it,
			 * automatically create a new account for users that
			 * are externally defined but have not yet logged in.
			 */
			if ( $wgAuth->autoCreate() && $wgAuth->userExists( $u->getName() ) ) {
				if ( $wgAuth->authenticate( $u->getName(), $this->mPassword ) ) {
					$u = $this->initUser( $u, true );
				} else {
					return self::WRONG_PLUGIN_PASS;
				}
			} else {
				return self::NOT_EXISTS;
			}
		} else {
			$u->load();
		}

		// Give general extensions, such as a captcha, a chance to abort logins
		$abort = self::ABORTED;
		if( !wfRunHooks( 'AbortLogin', array( $u, $this->mPassword, &$abort ) ) ) {
			return $abort;
		}
		
		if (!$u->checkPassword( $this->mPassword )) {
			if( $u->checkTemporaryPassword( $this->mPassword ) ) {
				// The e-mailed temporary password should not be used
				// for actual logins; that's a very sloppy habit,
				// and insecure if an attacker has a few seconds to
				// click "search" on someone's open mail reader.
				//
				// Allow it to be used only to reset the password
				// a single time to a new value, which won't be in
				// the user's e-mail archives.
				//
				// For backwards compatibility, we'll still recognize
				// it at the login form to minimize surprises for
				// people who have been logging in with a temporary
				// password for some time.
				//
				// As a side-effect, we can authenticate the user's
				// e-mail address if it's not already done, since
				// the temporary password was sent via e-mail.
				//
				if( !$u->isEmailConfirmed() ) {
					$u->confirmEmail();
				}

				// At this point we just return an appropriate code
				// indicating that the UI should show a password
				// reset form; bot interfaces etc will probably just
				// fail cleanly here.
				//
				$retval = self::RESET_PASS;
			} else {
				$retval = '' == $this->mPassword ? self::EMPTY_PASS : self::WRONG_PASS;
			}
		} else {
			$wgAuth->updateUser( $u );
			$wgUser = $u;

			$retval = self::SUCCESS;
		}
		wfRunHooks( 'LoginAuthenticateAudit', array( $u, $this->mPassword, $retval ) );
		return $retval;
	}

	function processLogin() {
		global $wgUser, $wgAuth;

		switch ($this->authenticateUserData())
		{
			case self::SUCCESS:
				# We've verified now, update the real record
				if( (bool)$this->mRemember != (bool)$wgUser->getOption( 'rememberpassword' ) ) {
					$wgUser->setOption( 'rememberpassword', $this->mRemember ? 1 : 0 );
					$wgUser->saveSettings();
				} else {
					$wgUser->invalidateCache();
				}
				$wgUser->setCookies();

				if( $this->hasSessionCookie() ) {
					return $this->successfulLogin( wfMsg( 'loginsuccess', $wgUser->getName() ) );
				} else {
					return $this->cookieRedirectCheck( 'login' );
				}
				break;

			case self::NO_NAME:
			case self::ILLEGAL:
				$this->mainLoginForm( wfMsg( 'noname' ), 'error_username' );
				break;
			case self::WRONG_PLUGIN_PASS:
				$this->mainLoginForm( wfMsg( 'wrongpassword' ), 'error_password' );
				break;
			case self::NOT_EXISTS:
				if( $wgUser->isAllowed( 'createaccount' ) ){
					$this->mainLoginForm( wfMsg( 'nosuchuser', htmlspecialchars( $this->mName ) ), 'error_username' );
				} else {
					$this->mainLoginForm( wfMsg( 'nosuchusershort', htmlspecialchars( $this->mName ) ), 'error_username' );
				}
				break;
			// XXADDED
			case self::NO_EMAIL:
				$this->mainLoginForm( wfMsg( 'noemail_login' ), 'error_username' );
				break;
			case self::MULTIPLE_EMAILS:
				$this->mainLoginForm( wfMsg( 'multipleemails_login' ), 'error_username' );
				break;
			case self::WRONG_PASS:
				$this->mainLoginForm( wfMsg( 'wrongpassword' ), 'error_password' );
				break;
			case self::EMPTY_PASS:
				$this->mainLoginForm( wfMsg( 'wrongpasswordempty' ), 'error_password' );
				break;
			case self::RESET_PASS:
				$this->resetLoginForm( wfMsg( 'resetpass_announce' ), 'error_password' );
				break;
			default:
				wfDebugDieBacktrace( "Unhandled case value" );
		}
	}

	function resetLoginForm( $error ) {
		global $wgOut;
		$reset = new PasswordResetForm( $this->mName, $this->mPassword );
		$reset->error($error, 'error_general');
		$reset->execute( null );
	}

	/**
	 * @private
	 */
	function mailPassword() {
		global $wgUser, $wgOut, $wgAuth;

		if( !$wgAuth->allowPasswordChange() ) {
			$this->mainLoginForm( wfMsg( 'resetpass_forbidden' ) );
			return;
		}

		# Check against blocked IPs
		# fixme -- should we not?
		if( $wgUser->isBlocked() ) {
			$this->mainLoginForm( wfMsg( 'blocked-mailpassword' ) );
			return;
		}

		# Check against the rate limiter
		if( $wgUser->pingLimiter( 'mailpassword' ) ) {
			$wgOut->rateLimited();
			return;
		}

		if ( '' == $this->mName ) {
			$this->mainLoginForm( wfMsg( 'noname' ) );
			return;
		}
		$u = User::newFromName( $this->mName );
		if( is_null( $u ) ) {
			$this->mainLoginForm( wfMsg( 'noname' ) );
			return;
		}
		if ( 0 == $u->getID() ) {
			$this->mainLoginForm( wfMsg( 'nosuchuser', $u->getName() ) );
			return;
		}

		# Check against password throttle
		if ( $u->isPasswordReminderThrottled() ) {
			global $wgPasswordReminderResendTime;
			# Round the time in hours to 3 d.p., in case someone is specifying minutes or seconds.
			$this->mainLoginForm( wfMsg( 'throttled-mailpassword',
				round( $wgPasswordReminderResendTime, 3 ) ) );
			return;
		}

		$result = $this->mailPasswordInternal( $u, true, 'passwordremindertitle', 'passwordremindertext' );
		if( WikiError::isError( $result ) ) {
			$this->mainLoginForm( wfMsg( 'mailerror', $result->getMessage() ) );
		} else {
			$this->mainLoginForm( wfMsg( 'passwordsent', $u->getName() ), 'success' );
		}
	}


	/**
	 * @param object user
	 * @param bool throttle
	 * @param string message name of email title
	 * @param string message name of email text
	 * @return mixed true on success, WikiError on failure
	 * @private
	 */
	function mailPasswordInternal( $u, $throttle = true, $emailTitle = 'passwordremindertitle', $emailText = 'passwordremindertext' ) {
		global $wgCookiePath, $wgCookieDomain, $wgCookiePrefix, $wgCookieSecure;
		global $wgServer, $wgScript;

		if ( '' == $u->getEmail() ) {
			return new WikiError( wfMsg( 'noemail', $u->getName() ) );
		}

		$np = $u->randomPassword();
		$u->setNewpassword( $np, $throttle );

		setcookie( "{$wgCookiePrefix}Token", '', time() - 3600, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );

		$u->saveSettings();

		$ip = wfGetIP();
		if ( '' == $ip ) { $ip = '(Unknown)'; }

		$m = wfMsg( $emailText, $ip, $u->getName(), $np, $wgServer . $wgScript );
		$result = $u->sendMail( wfMsg( $emailTitle ), $m, null, null, false );

		return $result;
	}


	/**
	 * @param string $msg Message that will be shown on success
	 * @param bool $auto Toggle auto-redirect to main page; default true
	 * @private
	 */
	function successfulLogin( $msg, $auto = true, $redir = false ) {
		global $wgUser;
		global $wgOut;
		global $wgServer;

		# Run any hooks; ignore results

		wfRunHooks('UserLoginComplete', array(&$wgUser));

		if ($redir) {
			$wgOut->redirect($wgServer ."/". $msg);
		}

		$wgOut->setPageTitle( wfMsg( 'loginsuccesstitle' ) );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		$wgOut->setArticleRelated( false );
		$wgOut->addWikiText( $msg );
		if ( !empty( $this->mReturnTo ) ) {
			$wgOut->returnToMain( $auto, $this->mReturnTo );
		} else {
			$wgOut->returnToMain( $auto );
		} 

		/// added for GA tracking
		$wgOut->addHTML("
<script type='text/javascript'> try { var pageTracker = _gat._getTracker('" . WH_GA_ID. "'); pageTracker._trackPageview(); pageTracker._setVar('logged_in'); } catch(err) {}</script>
"); 

	}

	/** */
	function userNotPrivilegedMessage() {
		global $wgOut;

		$wgOut->setPageTitle( wfMsg( 'whitelistacctitle' ) );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		$wgOut->setArticleRelated( false );

		$wgOut->addWikiMsg( 'whitelistacctext' );

		$wgOut->returnToMain( false );
	}

	/** */
	function userBlockedMessage() {
		global $wgOut, $wgUser;

		# Let's be nice about this, it's likely that this feature will be used
		# for blocking large numbers of innocent people, e.g. range blocks on 
		# schools. Don't blame it on the user. There's a small chance that it 
		# really is the user's fault, i.e. the username is blocked and they 
		# haven't bothered to log out before trying to create an account to 
		# evade it, but we'll leave that to their guilty conscience to figure
		# out.

		$wgOut->setPageTitle( wfMsg( 'cantcreateaccounttitle' ) );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		$wgOut->setArticleRelated( false );

		$ip = wfGetIP();
		$blocker = User::whoIs( $wgUser->mBlock->mBy );
		$block_reason = $wgUser->mBlock->mReason;

		$wgOut->addWikiMsg( 'cantcreateaccount-text', $ip, $block_reason, $blocker );
		$wgOut->returnToMain( false );
	}

	/**
	 * @private
	 */
	function mainLoginForm( $msg, $msgtype = 'error' ) {
		global $wgUser, $wgOut, $wgAllowRealName, $wgEnableEmail;
		global $wgCookiePrefix, $wgAuth, $wgLoginLanguageSelector;
		global $wgAuth, $wgEmailConfirmToEdit;

		if ( $this->mType == 'signup' ) {
			if ( !$wgUser->isAllowed( 'createaccount' ) ) {
				$this->userNotPrivilegedMessage();
				return;
			} elseif ( $wgUser->isBlockedFromCreateAccount() ) {
				$this->userBlockedMessage();
				return;
			}
		}

		if ( '' == $this->mName ) {
			if ( $wgUser->isLoggedIn() ) {
				$this->mName = $wgUser->getName();
			} else {
				$this->mName = isset( $_COOKIE[$wgCookiePrefix.'UserName'] ) ? $_COOKIE[$wgCookiePrefix.'UserName'] : null;
			}
		}

		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );

		if ( $this->mType == 'signup' ) {
			$template = new UsercreateTemplate();
			$q = 'action=submitlogin&type=signup';
			$linkq = 'type=login';
			$linkmsg = 'gotaccount';
		} else {
			$template = new UserloginTemplate();
			$q = 'action=submitlogin&type=login';
			$linkq = 'type=signup';
			$linkmsg = 'nologin';
		}

		if ( !empty( $this->mReturnTo ) ) {
			$returnto = '&returnto=' . wfUrlencode( $this->mReturnTo );
			$q .= $returnto;
			$linkq .= $returnto;
		}

		# Pass any language selection on to the mode switch link
		if( $wgLoginLanguageSelector && $this->mLanguage )
			$linkq .= '&uselang=' . $this->mLanguage;

		$link = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl( $linkq ) ) . '">';
		$link .= wfMsgHtml( $linkmsg . 'link' ); # Calling either 'gotaccountlink' or 'nologinlink'
		$link .= '</a>';

		$linkPassword = "/Special:LoginReminder";//htmlspecialchars( $titleObj->getLocalUrl( 'type=forgotpassword' . $returnto ) );
		$linkUsername = htmlspecialchars( $titleObj->getLocalUrl( 'type=forgotusername' . $returnto ) );

		# Don't show a "create account" link if the user can't
		if( $this->showCreateOrLoginLink( $wgUser ) ){
			$template->set( 'link', wfMsgHtml( $linkmsg, $link ) );
			$this->mLink = wfMsgHtml( $linkmsg, $link );
		}
		else{
			$template->set( 'link', '' );
			$this->mLink = '';
		}

		$template->set( 'forgotPassword', $linkPassword );
		$template->set( 'forgotUsername', $linkUsername );
		$template->set( 'createLink', $link);
		$template->set( 'header', '' );
		$template->set( 'name', $this->mName );
		$template->set( 'password', $this->mPassword );
		$template->set( 'retype', $this->mRetype );
		$template->set( 'retypeEmail', $this->mRetypeEmail );
		$template->set( 'email', $this->mEmail );
		$template->set( 'realname', $this->mRealName );
		$template->set( 'domain', $this->mDomain );

		$template->set( 'action', $titleObj->getLocalUrl( $q ) );
		$template->set( 'message', $msg );
		$template->set( 'messagetype', $msgtype );
		$template->set( 'createemail', $wgEnableEmail && $wgUser->isLoggedIn() );
		$template->set( 'userealname', $wgAllowRealName );
		$template->set( 'useemail', $wgEnableEmail );
		$template->set( 'emailrequired', $wgEmailConfirmToEdit );
		$template->set( 'canreset', $wgAuth->allowPasswordChange() );
		$template->set( 'remember', $wgUser->getOption( 'rememberpassword' ) or $this->mRemember  );

		# Prepare language selection links as needed
		if( $wgLoginLanguageSelector ) {
			$template->set( 'languages', $this->makeLanguageSelector() );
			if( $this->mLanguage )
				$template->set( 'uselang', $this->mLanguage );
		}

		// Give authentication and captcha plugins a chance to modify the form
		$wgAuth->modifyUITemplate( $template );
		if ( $this->mType == 'signup' || $this->mType == 'forgotusername' ) {
			wfRunHooks( 'UserCreateForm', array( &$template ) );
		} else {
			wfRunHooks( 'UserLoginForm', array( &$template ) );
		}
		
		$wgOut->setPageTitle( wfMsg( 'userlogin' ) );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );
		$wgOut->setArticleRelated( false );
		$wgOut->disallowUserJs();  // just in case...
		$wgOut->addTemplate( $template );
	}

	/**
	 * @private
	 */
	function showCreateOrLoginLink( &$user ) {
		if( $this->mType == 'signup' ) {
			return( true );
		} elseif( $user->isAllowed( 'createaccount' ) ) {
			return( true );
		} else {
			return( false );
		}
	}

	/**
	 * Check if a session cookie is present.
	 *
	 * This will not pick up a cookie set during _this_ request, but is
	 * meant to ensure that the client is returning the cookie which was
	 * set on a previous pass through the system.
	 *
	 * @private
	 */
	function hasSessionCookie() {
		global $wgDisableCookieCheck, $wgRequest;
		return $wgDisableCookieCheck ? true : $wgRequest->checkSessionCookie();
	}

	/**
	 * @private
	 */
	function cookieRedirectCheck( $type ) {
		global $wgOut;

		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$check = $titleObj->getFullURL( 'wpCookieCheck='.$type );

		return $wgOut->redirect( $check );
	}

	/**
	 * @private
	 */
	function onCookieRedirectCheck( $type ) {
		global $wgUser;

		if ( !$this->hasSessionCookie() ) {
			if ( $type == 'new' ) {
				return $this->mainLoginForm( wfMsg( 'nocookiesnew' ) );
			} else if ( $type == 'login' ) {
				return $this->mainLoginForm( wfMsg( 'nocookieslogin' ) );
			} else {
				# shouldn't happen
				return $this->mainLoginForm( wfMsg( 'error' ) );
			}
		} else {
			return $this->successfulLogin( wfMsg( 'loginsuccess', $wgUser->getName() ) );
		}
	}

	/**
	 * @private
	 */
	function throttleHit( $limit ) {
		global $wgOut;

		$wgOut->addWikiMsg( 'acct_creation_throttle_hit', $limit );
	}

	/**
	 * Produce a bar of links which allow the user to select another language
	 * during login/registration but retain "returnto"
	 *
	 * @return string
	 */
	function makeLanguageSelector() {
		$msg = wfMsgForContent( 'loginlanguagelinks' );
		if( $msg != '' && !wfEmptyMsg( 'loginlanguagelinks', $msg ) ) {
			$langs = explode( "\n", $msg );
			$links = array();
			foreach( $langs as $lang ) {
				$lang = trim( $lang, '* ' );
				$parts = explode( '|', $lang );
				if (count($parts) >= 2) {
					$links[] = $this->makeLanguageSelectorLink( $parts[0], $parts[1] );
				}
			}
			return count( $links ) > 0 ? wfMsgHtml( 'loginlanguagelabel', implode( ' | ', $links ) ) : '';
		} else {
			return '';
		}
	}

	/**
	 * Create a language selector link for a particular language
	 * Links back to this page preserving type and returnto
	 *
	 * @param $text Link text
	 * @param $lang Language code
	 */
	function makeLanguageSelectorLink( $text, $lang ) {
		global $wgUser;
		$self = SpecialPage::getTitleFor( 'Userlogin' );
		$attr[] = 'uselang=' . $lang;
		if( $this->mType == 'signup' )
			$attr[] = 'type=signup';
		if( $this->mReturnTo )
			$attr[] = 'returnto=' . $this->mReturnTo;
		$skin = $wgUser->getSkin();
		return $skin->makeKnownLinkObj( $self, htmlspecialchars( $text ), implode( '&', $attr ) );
	}

	function topContent($form){
		if($form->mType == "signup"){
			echo '<p class="top_header"><span class="top_link">' . $form->mLink . '</span><span class="headline">' . wfMsg('createaccount') . '</span></p>';
		}
		else if($form->mMailmypassword){
			echo '<p class="top_header"><span class="top_link">' . $form->mLink . '</span><span class="headline">' . wfMsg('accmailtitle') . '</span> </p>';
		}
		else{
			echo '<p class="top_header"><span class="top_link">' . $form->mLink . '</span><span class="headline">' . wfMsg('login') . '</span> </p>';
		}
		return true;
	}
}


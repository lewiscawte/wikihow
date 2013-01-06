<?php

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class LoginReminder extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'LoginReminder' );
	}

	function execute($par) {
		global $wgRequest, $wgUser, $wgOut;

		$wgOut->setArticleBodyOnly(true);
		if($wgRequest->getVal('submit')) {
			$result = self::mailPassword();
			if(isset($result))
				echo json_encode($result);
			return;
		}
		else{
			$template = new QuickTemplate();
			$template->set('header', '');
			wfRunHooks( 'UserCreateForm', array( &$template ) );
			self::displayForm($template);
		}
	}

	function displayForm($template) {
		global $wgOut, $wgRequest;
		wfLoadExtensionMessages('LoginReminder');

		if( $this->data['message'] ) {
		?>
			<div class="<?php $this->text('messagetype') ?>box">
				<?php if ( $this->data['messagetype'] == 'error' ) { ?>
					<h2><?php $this->msg('loginerror') ?>:</h2>
				<?php } ?>
				<?php $this->html('message') ?>
			</div>
			<div class="visualClear"></div>
		<?php } ?>

		<div class="modal_form">

		<form name="userlogin" method="post" action="#" onSubmit="return checkSubmit('wpName2', 'wpCaptchaWord', 'wpCaptchaId');">
			<div id="userloginprompt"><?php wfMsg('loginprompt') ?></div>
			<table>
				<tr>
					<td class="mw-label"><label for='wpName2'><?php echo wfMsg('username_or_email_html') ?></label></td>
					<td class="mw-input">
						<div style="position:relative">
							<input type='text' class='loginText input_med' name="wpName" id="wpName2" value="<?php echo $wgRequest->getVal('name') ?>" size='20' />
							<div class="mw-error-bottom mw-error" id="wpName2_error" style="display:none;">
								<div class="mw-error-top">
								</div>
							</div>
							<input type="hidden" id="wpName2_showhide" />
						</div>
					</td>
				</tr>
				<tr>
					<td class="mw-label" style="vertical-align:bottom;"><label style="display:block; padding-bottom: 35px">Security</label></td>
					<td class="mw-input">
						<div style="position:relative">
							<?php $template->html('header'); /* pre-table point for form plugins... */ ?>
							<div class="mw-error-bottom mw-error" id="wpCaptchaWord_error" style="display:none;">
								<div class="mw-error-top">
								</div>
							</div>
							<input type="hidden" id="wpCaptchaWord_showhide" />
						</div>
					</td>
				</tr>
				<tr>
					<td class="mw-submit"></td>
					<td style="padding-top:5px">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td>
								<input type="submit" value="Reset Password" id="wpMailmypassword" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class="button button150 submit_button" name="wpMailmypassword" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" >
								</td>
								<td>

								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</form>

		</div>
		<div id="loginend"><?php wfMsg( 'loginend' ); ?></div>
		<div id="password-reset-dialog" style="display:none" title="Password Reset">
			<div style="font-size:14px">
				<?=wfMsg('loginreminder_password_reset')?><br/>
			</div>
			<br/>
			<input type="submit" value="OK" id="password-reset-ok" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class="button button52 submit_button" style="float:right">
		</div>
		<?php

	}

	function mailPassword() {
		global $wgUser, $wgOut, $wgAuth, $wgRequest;

		$result = array();

		if( !$wgAuth->allowPasswordChange() ) {
			$result['error_general'] = wfMsg( 'resetpass_forbidden' );
			return $result;
		}

		# Check against blocked IPs
		# fixme -- should we not?
		if( $wgUser->isBlocked() ) {
			$result['error_general'] = wfMsg( 'blocked-mailpassword' );
			return $result;
		}

		# Check against the rate limiter
		if( $wgUser->pingLimiter( 'mailpassword' ) ) {
			$wgOut->disable();
			$wgOut->rateLimited();
			$result['error_general'] = "<h4>" . wfMsg('actionthrottled') . "</h4>";
			$result['error_general'] .= wfMsg('actionthrottledtext');
			return $result;
		}

		$name = $wgRequest->getVal('name');

		if ( !isset($name) || '' == $name ) {
			$result['error_username'] = wfMsg( 'noname' );
			return $result;
		}
		$name = trim( $name );

		$u = null;
		// If $name looks like an email address, we look it up by email
		// address first
		$looksLikeEmail = strpos($name, '@') !== false;
		if ( $looksLikeEmail ) {
			list($u, $count) = User::newFromEmailAddress( $name );
		}

		if ( is_null( $u ) ) {
			$u = User::newFromName( $name );
			// Show error specific to email addresses if there's no username
			// with an '@' in it either
			if ($looksLikeEmail) {
				if ($count < 1) {
					$result['error_username'] = wfMsg( 'noemail_login' );
					return $result;
				} elseif ($count > 1) {
					$result['error_username'] = wfMsg( 'multipleemails_login' );
					return $result;
				}
			}
		}

		if( is_null( $u ) ) {
			$result['error_username'] = wfMsg( 'noname' );
			return $result;
		}
		if ( 0 == $u->getID() ) {
			$result['error_username'] = wfMsg( 'nosuchuser', $u->getName() );
			return $result;
		}

		$abortError = '';
		if( !wfRunHooks( 'AbortAccountReminder', array( $u, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			$result['error_captcha'] = $abortError;

			//had a problem with the captcha, need to load a new one
			$template = new QuickTemplate();
			$template->set('header', '');
			wfRunHooks('AccountReminderNewCaptcha', array( &$template ));
			
			//hack since templates ECHO the data you want
			ob_start();
			$template->html('header');
			$var = ob_get_contents();
			ob_end_clean();
			//end hack
			
			$result['newCaptcha'] = $var;
			return $result;
		}

		# Check against password throttle
		if ( $u->isPasswordReminderThrottled() ) {
			global $wgPasswordReminderResendTime;
			# Round the time in hours to 3 d.p., in case someone is specifying minutes or seconds.
			$result['error_general'] = wfMsg( 'throttled-mailpassword', round( $wgPasswordReminderResendTime, 3 ) );
			return $result;
		}

		$mailResult = $this->mailPasswordInternal( $u, true, 'passwordremindertitle', 'passwordremindertext' );
		if( WikiError::isError( $mailResult ) ) {
			$result['error_general'] = wfMsg( 'mailerror', $mailResult->getMessage() );
			return $result;
		} else {
			$result['success'] = wfMsgHtml( 'passwordsent', $u->getName() );
			return $result;
		}
	}

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

}

class LoginFacebook extends UnlistedSpecialPage {
	function __construct() {
		SpecialPage::SpecialPage( 'LoginFacebook' );
	}

	function execute($par) {
		global $wgRequest, $wgUser, $wgOut, $wgHooks;
		
		$wgOut->setHTMLTitle('Login Via Facebook - wikiHow');
		$wgHooks['BeforeTabsLine'][] = array('LoginFacebook::topContent');
		if (class_exists('WikihowCSSDisplay'))
			WikihowCSSDisplay::setSpecialBackground(true);

		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$link = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl( 'type=signup' ) ) . '">';
		$link .= wfMsgHtml( 'nologinlink' );
		$link .= '</a>';

		$linkLogin = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl('type=login' ) ) . '">';
		$linkLogin .= wfMsgHtml( 'gotaccountlink' ); 
		$linkLogin .= '</a>';
		
		$form = "<div id='userloginForm'>

			<table border='0' width='100%'>
				<tr>
					<td style='text-align:center;'>" . wfMsg('facebook_account_login', $linkLogin, $link ) . "</td>
				</tr>
			</table>

		</div>";
		$wgOut->addHTML($form);
		wfRunHooks( 'FBLoginForm', array() );
	}

	function topContent() {
		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$link = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl( 'type=signup' ) ) . '">';
		$link .= wfMsgHtml( 'nologinlink' );
		$link .= '</a>';
		echo '<p style="padding:0 27px 15px 23px"><span style="font-size: 28px">' . wfMsg( 'Loginfacebook' ) . '</span> <span style="float:right;">' . wfMsgHtml( 'nologin', $link ) . '</span></p>';
		
		return true;
	}
}

class LoginCheck extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'LoginCheck' );
	}

	function execute($par) {
		global $wgRequest, $wgOut;

		$wgOut->setArticleBodyOnly(true);

		$username = $wgRequest->getVal('username');
		if(isset($username))
			echo json_encode(self::checkUsername($username));
	}

	function checkUsername($username) {
		$dbr = wfGetDB(DB_SLAVE);

		$result = $dbr->selectField('user', 'count(*)', array('user_name' => $username));
		if($result > 0) {
			return array('error' => wfMsg('userexists'));
		}
		else {
			return array('success' => '1');
		}
	}
	
}


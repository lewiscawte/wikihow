<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

	class LoginReminder extends SpecialPage {

		function __construct() {
			SpecialPage::SpecialPage( 'LoginReminder' );
		}

		function execute($par) {
			global $wgRequest, $wgUser, $wgOut;

			//wfLoadExtensionMessages('LoginReminder');
			$wgOut->setArticleBodyOnly(true);
			if($wgRequest->getVal('submit')){
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

		function displayForm($template){
			global $wgOut, $wgRequest;
			?>

				<script type="text/javascript">
					function checkSubmit(name, captchaWord, captchaId) {
						var params = 'submit=true&name=' + jQuery("#" + name).val() + '&wpCaptchaId=' + jQuery("#" + captchaId).val() + '&wpCaptchaWord=' + jQuery("#" + captchaWord).val();
						var that = this;
						var url = '/Special:LoginReminder?' + params;
						jQuery.get(url, function(json) {
							if (json) {
								data = jQuery.parseJSON( json )
								jQuery(".mw-error").hide();
								if(data.success){
									jQuery('#form_message').html(data.success);
									jQuery('#dialog-box').dialog('close');
								}
								else{
									if(data.error_username){
										jQuery('#error_username p').html(data.error_username);
										jQuery('#error_username').show();
									}
									if(data.error_captcha){
										jQuery('#error_captcha p').html(data.error_captcha);
										jQuery('#error_captcha').show();
										jQuery('.captcha').html(decodeURI(data.newCaptcha));
									}
									if(data.error_generl){
										jQuery('#error_generl p').html(data.error_username);
										jQuery('#error_generl').show();
									}
								}
							} else {
								that.displayNetworkError();
							}
						});
						return false;
					};
				</script>
			<?php

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
						<td class="mw-label"><label for='wpName2'><?php echo wfMsg('yourname') ?></label></td>
						<td class="mw-input">
							<div style="position:relative">
								<input type='text' class='loginText input_med' name="wpName" id="wpName2" value="<?php echo $wgRequest->getVal('name') ?>" size='20' />
								<div class="mw-error-bottom mw-error" id="error_username" style="display:none;">
									<div class="mw-error-top">
										<h4>Error</h4>
										<p></p>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td class="mw-label" style="vertical-align:bottom;"><label style="display:block; padding-bottom: 70px">Security</label></td>
						<td class="mw-input">
							<div style="position:relative">
								<?php $template->html('header'); /* pre-table point for form plugins... */ ?>
								<div class="mw-error-bottom mw-error" id="error_captcha" style="display:none;">
									<div class="mw-error-top">
										<h4>Error</h4>
										<p></p>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td class="mw-submit"></td>
						<td style="padding-top:5px">
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td>
									<input type="submit" value="E-mail password" id="wpMailmypassword" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class="button button150 submit_button" name="wpMailmypassword" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" >
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
				$wgOut->rateLimited();
				return;
			}

			$name = $wgRequest->getVal('name');

			if ( !isset($name) || '' == $name ) {
				$result['error_username'] = wfMsg( 'noname' );
				return $result;
			}
			$name = trim( $name );
			$u = User::newFromName( $name );
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
			$result = $u->sendMail( wfMsg( $emailTitle ), $m );

			return $result;
		}

	}

?>

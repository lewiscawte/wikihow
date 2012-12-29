<?php

class Welcome extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Welcome' );
	}

	static function send( $to, $from, $subject, $body, $replyto=null ) {
		global $wgSMTP, $wgOutputEncoding, $wgErrorString, $wgEnotifImpersonal;
		global $wgEnotifMaxRecips;

		if ( is_array( $to ) ) {
			wfDebug( __METHOD__.': sending mail to ' . implode( ',', $to ) . "\n" );
		} else {
			wfDebug( __METHOD__.': sending mail to ' . implode( ',', array( $to->toString() ) ) . "\n" );
		}

			# In the following $headers = expression we removed "Reply-To: {$from}\r\n" , because it is treated differently
			# (fifth parameter of the PHP mail function, see some lines below)

			# Line endings need to be different on Unix and Windows due to 
			# the bug described at http://trac.wordpress.org/ticket/2603
			if ( wfIsWindows() ) {
				$body = str_replace( "\n", "\r\n", $body );
				$endl = "\r\n";
			} else {
				$endl = "\n";
			}
			$headers =
				"MIME-Version: 1.0$endl" .
				"Content-type: text/html; charset={$wgOutputEncoding}$endl" .
				"Content-Transfer-Encoding: 8bit$endl" .
				"X-Mailer: MediaWiki mailer$endl".
				'From: ' . $from->toString();
			if ($replyto) {
				$headers .= "{$endl}Reply-To: " . $replyto->toString();
			}

			$wgErrorString = '';
			$html_errors = ini_get( 'html_errors' );
			ini_set( 'html_errors', '0' );
			set_error_handler( array( 'UserMailer', 'errorHandler' ) );
			wfDebug( "Sending mail via internal mail() function\n" );

			if (function_exists('mail')) {
				if (is_array($to)) {
					foreach ($to as $recip) {
						$sent = mail( $recip->toString(), wfQuotedPrintable( $subject ), $body, $headers );
					}
				} else {
					$sent = mail( $to->toString(), wfQuotedPrintable( $subject ), $body, $headers );
				}
			} else {
				$wgErrorString = 'PHP is not configured to send mail';
			}

			restore_error_handler();
			ini_set( 'html_errors', $html_errors );
	}

	function sendWelcome() {
		global $wgUser, $wgOut, $wgLang, $wgServer, $wgSMTP;


		if ($wgUser->getID() == 0) {
			wfDebug("Welcome email:User must be logged in.\n");
			return true;
		}

		if ($wgUser->getOption( 'disablemarketingemail' ) == '1' ) {
			wfDebug( "Welcome email: Marketing preference not selected.\n");
			return true;
		}

		if ($wgUser->getEmail() == "") {
			wfDebug("Welcome email: No email address found.\n");
			return true;
		}

		$subject = wfMsg('welcome-email-subject');
			
		$from_name = "";
		$validEmail = "";
		$from_name = wfMsg('welcome-email-fromname');

		if ($wgUser->getID() > 0) {
			$to_name = $wgUser->getName();
			$to_real_name = $wgUser->getRealName();
			if ($to_real_name != "") {
				$to_name = $real_name;
			}
			$username = $to_name;
			$email = $wgUser->getEmail();

			$validEmail = $email;
			$to_name .= "<$email>";
		}

			
		//server,username,talkpage,username
		$body = wfMsg('welcome-email-body', $wgServer, $username, $wgServer .'/'. preg_replace('/ /','-',$wgUser->getTalkPage()), $wgUser->getName()  );

		$from = new MailAddress ($from_name);	
		$to = new MailAddress ($to_name);
		if (Welcome::send($to, $from, $subject, $body, false)) {
			wfDebug( "Welcome email: got an en error while sending.\n");
		};

		return true;

	}

	function execute ($par) {
		global $wgUser, $wgRequest, $wgOut, $wgServer;
		wfLoadExtensionMessages('Welcome');		
		$fname = 'Welcome';

		$wgOut->setArticleBodyOnly(true);

		$username = $wgRequest->getVal('u', null);
		
		if ($username != '') {
			$u = new User();
			$u->setName($username);
		} else {
			echo 'Sorry invalid request.<br />';
			return;
		}

		//server,username,talkpage,username
		$body = wfMsg('welcome-email-body', $wgServer, $username, $wgServer .'/'. preg_replace('/ /','-',$u->getTalkPage()), $username  );

		echo $body;	
	}
}
		
	

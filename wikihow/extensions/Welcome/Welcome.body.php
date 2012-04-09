<?php

class Welcome extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Welcome' );
	}

	function sendWelcome() {
		global $wgUser;
		return self::sendWelcomeUser( $wgUser );
	}

	function sendWelcomeUser( $user ) {
		global $wgServer;

		if ( $user->getID() == 0 ) {
			wfDebugLog( 'Welcome', 'User must be logged in.' );
			return true;
		}

		if ( $user->getOption( 'disablemarketingemail' ) == '1' ) {
			wfDebugLog( 'Welcome', 'Marketing preference not selected.' );
			return true;
		}

		if ( $user->getEmail() == '' ) {
			wfDebugLog( 'Welcome', 'No e-mail address found.' );
			return true;
		}

		$subject = wfMessage( 'welcome-email-subject' )->text();

		$fromMessage = wfMessage( 'welcome-email-fromname' );
		$fromName = $fromMessage->params( $wgServer )->text();

		$to_name = $user->getName();
		$to_real_name = $user->getRealName();
		if ( $to_real_name != '' ) {
			$to_name = $real_name;
		}
		$username = $to_name;
		$email = $user->getEmail();

		$to_name .= " <$email>";

		// If the actual e-mail message is empty, bail out.
		$msg = wfMessage( 'welcome-email-body' );
		if ( $msg->isBlank() ) {
			return true;
		}

		// server, username, talk page, username
		$body = $msg->params( $wgServer, $username, $u->getTalkPage()->getFullURL(), $user->getName() )->plain();

		$from = new MailAddress( $fromName );
		$to = new MailAddress( $to_name );
		$contentType = 'text/html; charset=UTF-8';
		if ( !UserMailer::send( $to, $from, $subject, $body, false, $contentType ) ) {
			wfDebugLog( 'Welcome', 'Got an en error while sending.' );
		}

		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgUser, $wgRequest, $wgOut, $wgServer;

		$wgOut->setArticleBodyOnly( true );

		$username = $wgRequest->getVal( 'u', $par );

		if ( $username != '' ) {
			$u = new User();
			$u->setName( $username );
		} else {
			echo wfMessage( 'welcome-invalid-request' )->parse() . '<br />';
			return;
		}

		$msg = wfMessage( 'welcome-email-body' );
		if ( !$msg->isBlank() ) {
			// $username is passed twice because this is the same message that
			// is used in sendWelcomeUser() and thus the number of parameters
			// must remain the same
			$body = $msg->params( $wgServer, $username, $u->getTalkPage()->getFullURL(), $username )->plain();
			echo $body;
		}
	}
}
<?php

class AdminResetPassword extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminResetPassword', 'adminresetpassword' );
	}

	/**
	 * Resets a user's password (account found by username). The logic here
	 * was lifted from LoginReminder.body.php (but it wasn't generalized
	 * there -- it was for e-mail only).
	 *
	 * @param $username String: the username
	 * @return String: a temporary password string to give to the user
	 */
	function resetPassword( $username ) {
		$user = User::newFromName( $username );
		if ( $user->getID() > 0 ) {
			$newPassword = User::randomPassword();
			// TODO: log this action somewhere, along with which user did it
			$user->setNewpassword( $newPassword, false );
			$user->saveSettings();
			return $newPassword;
		} else {
			return '';
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		// Check permissions
		if ( !$wgUser->isAllowed( 'adminresetpassword' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// If the user is blocked, they don't need to access this page
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// The JS file POSTs into this very page
		if ( $wgRequest->wasPosted() ) {
			$username = $wgRequest->getVal( 'username', '' );

			// So that we don't get a special page inside a special page :P
			// We're not interested in the skin, we just need the content
			$wgOut->setArticleBodyOnly( true );

			$newPass = $this->resetPassword( $username );

			if ( $newPass ) {
				$url = SpecialPage::getTitleFor( 'Userlogin' )->getFullURL();
				$tmpl = wfMessage( 'adminresetpassword-success', $username, $newPass, $url )->parseAsBlock();
				$result = array( 'result' => $tmpl );
			} else {
				$result = array(
					'result' => wfMsg( 'adminresetpassword-error', $username )
				);
			}
			echo json_encode( $result );
			return;
		}

		// Set headers, such as the page title, robot policy, etc.
		$this->setHeaders();

		$enter = wfMsg( 'adminresetpassword-enter-username' );
		$reset = wfMsg( 'adminresetpassword-reset' );
		$postURL = $this->getTitle()->getFullURL();
		$tmpl = <<<EOHTML
<form method="post" action="$postURL">
<h4>$enter</h4>
<br />
<input id="reset-username" type="text" size="40" />
<button id="reset-go" disabled="disabled">$reset</button><br />
<br />
<div id="reset-result">
</div>
</form>
EOHTML;

		// Add JS
		$wgOut->addModules( 'ext.adminResetPassword' );

		// Output the form
		$wgOut->addHTML( $tmpl );
	}
}

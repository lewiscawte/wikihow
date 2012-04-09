<?php

class AdminMarkEmailConfirmed extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminMarkEmailConfirmed', 'adminmarkemailconfirmed' );
	}

	/**
	 * Confirm a user's email address (account found by username).
	 *
	 * @param $username String: the username
	 * @return String: their e-mail address, if any
	 */
	function confirmEmailAddress( $username ) {
		$user = User::newFromName( $username );
		if ( $user && $user->getID() > 0 ) {
			$user->confirmEmail();
			$emailAddr = $user->getEmail();
			return $emailAddr;
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
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		// Check permissions
		if ( !$wgUser->isAllowed( 'adminmarkemailconfirmed' ) ) {
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

			$emailAddr = $this->confirmEmailAddress( $username );

			if ( $emailAddr ) {
				$tmpl = wfMessage( 'adminmarkemailconfirmed-success', $username, $emailAddr )->parseAsBlock();
				$result = array( 'result' => $tmpl );
			} else {
				$result = array(
					'result' => wfMsg( 'adminmarkemailconfirmed-error', $username )
				);
			}
			echo json_encode( $result );
			return;
		}

		// Set headers, such as the page title, robot policy, etc.
		$this->setHeaders();

		$instructions = wfMsg( 'adminmarkemailconfirmed-instructions' );
		$confirm = wfMsg( 'adminmarkemailconfirmed-confirm-button' );
		$postURL = $this->getTitle()->getFullURL();
		$tmpl = <<<EOHTML
<form method="post" action="$postURL">
<h4>$instructions</h4>
<br />
<input id="action-username" type="text" size="40" />
<button id="action-go" disabled="disabled">$confirm</button><br/>
<br />
<div id="action-result">
</div>
</form>
EOHTML;

		// Add JS
		$wgOut->addModules( 'ext.adminMarkEmailConfirmed' );

		// Output the form
		$wgOut->addHTML( $tmpl );
	}
}

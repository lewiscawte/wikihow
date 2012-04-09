<?php

class FBNuke extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'FBNuke', 'fbnuke' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgUser;

		// Check permissions
		if ( !$wgUser->isAllowed( 'fbnuke' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Set headers, such as the page title, robot policy, etc.
		$this->setHeaders();

		$userName = $wgRequest->getVal( 'uname', $par );
		$removeWikiAccount = $wgRequest->getVal( 'whremove', '' ) == 'on' ? true : false;
		if ( $userName ) {
			$this->removeAccount( $userName, $removeWikiAccount );
		}
		$this->showForm();
	}

	function showForm() {
		global $wgOut;

		$html = '<form action="' . $this->getTitle()->getFullURL() . '" method="get">';
		$html .= '<p><label for="uname">' . wfMsg( 'fbnuke-username' ) . ' </label>';
		$html .= '<input type="text" name="uname" /></p>';
		$html .= '<p><input type="checkbox" name="whremove" /> <label for="whremove">' .
			wfMsgExt( 'fbnuke-remove-wiki-account', 'parsemag' ) . '</label></p>';
		$html .= '<input type="submit" name="remove" value="' .
			wfMsg( 'fbnuke-submit-button' ) . '" />';
		$html .= '</form>';
		$wgOut->addHTML( $html );
	}

	function removeAccount( $userName, $removeWikiAccount = false ) {
		global $wgOut, $wgUser;

		$dbw = wfGetDB( DB_MASTER );

		$userName = $dbw->strencode( $userName );
		if( strtolower( $wgUser->getName() ) == strtolower( $userName ) ) {
			$wgOut->addHTML(
				'<h4>' . wfMsg( 'fbnuke-error-cant-remove-self' ) .
				'</h4><br /><br />'
			);
			return;
		}

		$userId = $dbw->selectField(
			'user',
			array( 'user_id' ),
			array( 'user_name' => $userName ),
			__METHOD__
		);
		if ( !$userId ) {
			$wgOut->addHTML(
				'<h4>' . wfMsg( 'fbnuke-user-not-found', $userName ) .
				'</h4><br /><br />'
			);
			return;
		}

		$dbw->delete(
			'facebook_connect',
			array( 'wh_user' => $userId ),
			__METHOD__
		);

		if ( $removeWikiAccount ) {
			$dbw->delete( 'user', array( 'user_id' => $userId ), __METHOD__ );
		}

		$wgOut->addHTML(
			'<h4>' . wfMsg( 'fbnuke-success', $userName ) . '</h4><br /><br />'
		);
	}
}
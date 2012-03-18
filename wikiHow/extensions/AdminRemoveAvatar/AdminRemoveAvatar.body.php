<?php

class AdminRemoveAvatar extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminRemoveAvatar', 'adminremoveavatar' );
	}

	/**
	 * Pull a user account (by username) and remove the avatar file associated.
	 *
	 * @param $username String: the username
	 * @return Boolean: true if action was successful
	 */
	function removeAvatar( $username ) {
		global $IP;

		$user = User::newFromName( $username );
		$userID = $user->getID();

		if ( $userID > 0 ) {
			// TODO1: log this action somewhere, along with the user who did it
			// TODO2: Purge /User:$username page from Varnish

			$imgDir = Avatar::getAvatarOutPath( "$userID.jpg" );
			$path = "$IP$imgDir$userID.jpg";
			$ret = false;
			if ( file_exists( $path ) ) {
				wfSuppressWarnings();
				$ret = unlink( $path );
				wfRestoreWarnings();
			}

			$ret = true;
			// Hack: Pick some arbitrary number of files to scan through.
			// Let's say 50
			for ( $i = 0; $i < 50; $i++ ) {
				$imgDir = Avatar::getAvatarOutPath("$userID-$i.jpg");
				$imgPath = "$IP$imgDir$userID-$i.jpg";
				if ( file_exists( $imgPath ) ) {
					wfSuppressWarnings();
					$unlinkedFile = unlink( $file );
					wfRestoreWarnings();
					if ( !$unlinkedFile ) {
						$ret = false;
						break;
					}
				}
			}

			return $ret;
		} else {
			return false;
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser, $wgSquidMaxage;

		// Check permissions
		if ( !$wgUser->isAllowed( 'adminremoveavatar' ) ) {
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

			$success = $this->removeAvatar( $username );

			if ( $success ) {
				$cacheHours = round( 1.0 * $wgSquidMaxage / ( 60 * 60 ), 1 );
				$tmpl = wfMessage( 'adminremoveavatar-removed', $username, $cacheHours )->parseAsBlock();
				$result = array( 'result' => $tmpl );

				// Log the removal
				$log = new LogPage( 'avatarrm', false ); // false - don't show in recentchanges
				$params = array();
				$log->addEntry(
					'',
					Title::newFromText( $username, NS_USER ),
					wfMessage( 'adminremoveavatar-log-entry', $wgUser->getName(), $username )->inContentLanguage()->plain(),
					$params
				);
			} else {
				$result = array(
					'result' => wfMsg( 'adminremoveavatar-error', $username )
				);
			}
			echo json_encode( $result );
			return;
		}

		// Set headers, such as the page title, robot policy, etc.
		$this->setHeaders();

		$rules = wfMsg( 'adminremoveavatar-rules' );
		$enter = wfMsg( 'adminremoveavatar-enter-username' );
		$reset = wfMsg( 'adminremoveavatar-reset' );
		$postURL = $this->getTitle()->getFullURL();
		$tmpl = <<<EOHTML
<form method="post" action="$postURL">
<p>$rules</p>
<br />
<h4>$enter</h4>
<br />
<input id="admin-username" type="text" size="40" />
<button id="admin-go" disabled="disabled">$reset</button><br />
<br/>
<div id="admin-result"></div>
</form>
EOHTML;

		// Add JS
		$wgOut->addModules( 'ext.adminRemoveAvatar' );

		// Output the form
		$wgOut->addHTML( $tmpl );
	}
}

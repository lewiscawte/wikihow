<?php

class UserTalkTool extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'UserTalkTool', 'usertalktool' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgUser, $wgOut, $wgLang;

		// Check that the user is allowed to access UserTalkTool
		if ( !$wgUser->isAllowed( 'usertalktool' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Make sure that the user isn't blocked
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Check for target
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if ( $target == null || $target == '' ) {
			$wgOut->addHTML( wfMsg( 'usertalktool-error-no-target' ) );
			return;
		}

		$dbr = wfGetDB( DB_SLAVE );

		// Process form
		if ( $wgRequest->wasPosted() ) {
			$wgOut->setArticleBodyOnly( true );

			$utmsg = $wgRequest->getVal( 'utmessage' );

			if ( $utmsg != '' ) {
				$ts = wfTimestampNow();

				$user = $wgUser->getName();
				$real_name = User::whoIsReal( $wgUser->getID() );
				if ( $real_name == '' ) {
					$real_name = $user;
				}

				// User
				$utitem = $wgRequest->getVal( 'utuser' );
				wfDebugLog( 'UserTalkTool', "posting user: $utitem" );
				wfDebugLog( 'UserTalkTool', 'by admin user: ' . $wgUser->getID() );

				if ( $utitem != '' ) {
					// Post user talk page
					$text = '';
					$aid = '';
					$a = '';
					$formattedComment = '';

					$u = new User();
					$u->setName( $utitem );
					$user_talk = $u->getTalkPage();

					$dateStr = $wgLang->timeanddate( wfTimestampNow() );

					$formattedComment = wfMsg(
						'postcomment_formatted_comment',
						$dateStr,
						$user,
						$real_name,
						mysql_real_escape_string( $utmsg ) // @todo FIXME: this is awfully silly
					);

					$aid = $user_talk->getArticleId();
					if ( $aid > 0 ) {
						$r = Revision::newFromTitle( $user_talk );
						$text = $r->getText();
					}
					$a = new Article( $user_talk, 0 );
					$text .= "\n\n$formattedComment\n\n";

	 				if ( $aid > 0 ) {
						$a->updateArticle( $text, '', true, false, false, '', true );
					} else {
						$article->doEdit( $value, '', ( EDIT_MINOR & EDIT_SUPPRESS_RC ) );
					}

					// Mark changes as patrolled
					$res = $dbr->select(
						'recentchanges',
						'MAX(rc_id) AS rc_id',
						array(
							'rc_title' => $utitem,
							'rc_user' => $wgUser->getID(),
							'rc_cur_id' => $aid,
							'rc_patrolled' => 0
						),
						__METHOD__
					);

					foreach ( $res as $row ) {
						wfDebugLog( 'UserTalkTool', 'mark patrol rcid: ' . $row->rc_id );
						RecentChange::markPatrolled( $row->rc_id );
						PatrolLog::record( $row->rc_id, false );
					}

					wfDebugLog( 'UserTalkTool', 'Done. Completed posting for [' . $utitem . ']' );
					$wgOut->addHTML( wfMsg( 'usertalktool-done', $utitem ) );
				} else {
					wfDebugLog( 'UserTalkTool', 'No user' );
					$wgOut->addHTML( wfMsg( 'usertalktool-error-no-user' ) );
				}
			} else {
				wfDebugLog( 'UserTalkTool', 'No message to post' );
				$wgOut->addHTML( wfMsg( 'usertalktool-error-no-msg', $utitem ) );
				return;
			}

			$wgOut->redirect( '' );
		} else {
			// Add JavaScript
			$wgOut->addModules( 'ext.userTalkTool' );

			// Define the variable to avoid PHP whining about undefined vars...
			$utList = 0;

			// Get the list of recipients
			if ( $target ) {
				$t = Title::newFromUrl( $target );
				if ( $t->getArticleId() <= 0 ) {
					$wgOut->addHTML( wfMsg( 'usertalktool-error' ) );
					return;
				} else {
					$r = Revision::newFromTitle( $t );
					$text = $r->getText();

					global $wgContLang;
					$talkspaceName = $wgContLang->getNsText( NS_USER_TALK );
					$utcount = preg_match_all(
						'/\[\[' . str_replace( ' ', '_', $talkspaceName ) . ':(.*?)[#\]\|]/',
						$text,
						$matches
					);
					$utList = $matches[1];
				}
			}

			// Display amount of found user talk pages
			if ( count( $utList ) == 0 ) {
				$wgOut->addHTML( wfMsg( 'usertalktool-no-talk-pages-found' ) );
				return;
			} else {
				$wgOut->addHTML( wfMsgExt( 'usertalktool-talkpagesfound', 'parsemag', count( $utList ) ) . '<br />' );
			}

			// Textarea and form
			$wgOut->addHTML( '<form id="utForm" method="post">' );

			// Display the list of user talk pages
			$wgOut->addHTML(
				'<div id="utlist" style="border: 1px grey solid; margin: 15px 0px 15px 0px; padding: 15px; height: 215px; overflow: auto">
					<ol id="ut_ol">' . "\n"
			);
			foreach ( $utList as $utItem ) {
				$wgOut->addHTML(
					'<li id="ut_li_' . preg_replace( '/\s/m', '-', $utItem ) . '">' .
						Linker::link( Title::newFromText( $utItem, NS_USER_TALK ), $utItem ) .
					'</li>' . "\n"
				);
			}
			$wgOut->addHTML( '</ol></div>' . "\n" );

			// Textarea and form
			$wgOut->addHTML(
				'<div id="formdiv">' . wfMsg( 'usertalktool-sendbox' ) . '<br />
<textarea id="utmessage" name="utmessage" rows="6" style="margin: 5px 0px 5px 0px;"></textarea>
<input id="utuser" type="hidden" name="utuser" value="" />

<input tabindex="4" type="button" value="' . wfMsg( 'usertalktool-send' ) . '" class="btn" id="postcommentbutton" style="font-size: 110%; font-weight: bold" />

</form>
</div>
<div id="resultdiv"></div>' . "\n"
			);
		}
	}
}



<?php

class ThankAuthors extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ThankAuthors' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgLang;
		global $wgRequest, $IP, $wgFilterCallback;

		// Set the page title, robot policy, etc.
		$this->setHeaders();

		// @todo FIXME/CHECKME: is this require needed?
		require_once("$IP/extensions/WikiHow/EditPageWrapper.php");

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if ( !$target ) {
			$wgOut->addHTML( wfMsgHtml( 'thankauthors-error' ) );
			return;
		}

		$title = Title::newFromDBKey( $target );

		if ( !$wgRequest->getVal( 'token' ) ) {
			$talk_page = $title->getTalkPage();

			$token = $this->getToken1();
			$thanks_msg = wfMessage(
				'thankauthors-thank-you-kudos',
				$title->getText(),
				wfMsg( 'howto', $title->getText() )
			)->parse();
			$thanks_msg = str_replace( "\n", '', $thanks_msg );
			$thanks_msg = str_replace( '"', '&quote', $thanks_msg );

			// add the form HTML
			$wgOut->addHTML(<<<EOHTML
				<script type='text/javascript'>
				function submitThanks() {
					var url = '{$this->getTitle()->getFullURL()}?token=' +
						$( '#token' )[0].value + '&target=' +
						$( '#target' )[0].value + '&details=' +
						$( '#details' )[0].value;
					var form = $( '#thanks_form' );
					form.html( "{$thanks_msg}" );
					$.get( url );
					return true;
				}
				</script>

				<div id="thanks_form">
EOHTML
			);

			$wgOut->addWikiMsg(
				'thankauthors-enjoyed-reading-article',
				$title->getFullText(),
				$talk_page->getFullText()
			);

			$wgOut->addHTML(
				"<input id=\"target\" type=\"hidden\" name=\"target\" value=\"$target\"/>
				<input id=\"token\" type=\"hidden\" name=\"$token\" value=\"$token\"/>\n"
			);

			$wgOut->addHTML(
				"\n<textarea style='width:400px;' id=\"details\" rows=\"5\" cols=\"100\" name=\"details\"></textarea><br/>
					<button onclick='submitThanks();'>" . wfMsg( 'submit' ) . '</button>
				</div>'
			);
		} else {
			// this is a post, accept the POST data and create the
			// Request article

			$wgOut->setArticleBodyOnly( true );

			$article = new Article( $title );
			// stupid bug that doesn't load the last edit unless you ask it to
			$article->loadLastEdit();
			$contributors = $article->getContributors( 0, 0, true );
			$user = $wgUser->getName();
			$real_name = User::whoIsReal( $wgUser->getID() );
			if ( $real_name == '' ) {
				$real_name = $user;
			}
			$dateStr = $wgLang->timeanddate( wfTimestampNow() );
			$comment = $wgRequest->getVal( 'details' );
			$text = $title->getFullText();

			wfDebugLog( 'ThankAuthors', 'got text...' );

			// filter out links
			$preg = "/[^\s]*\.[a-z][a-z][a-z]?[a-z]?/i";
			$matches = array();
			if ( preg_match( $preg, $comment, $matches ) > 0 ) {
				$wgOut->addHTML( wfMsg( 'thankauthors-no-urls', $matches[0] )  );
				return;
			}

			$comment = strip_tags( $comment );

			$formattedComment = '<!-- start entry --->
		<div id="discussion_entry">
			<table width="100%">
				<tr>
					<td width="50%" valign="top" class="discussion_entry_user">'
						. wfMsgHtml( 'thankauthors-comment-said', $user, $real_name, $text ) .
					'</td>
					<td align="right" width="50%" class="discussion_entry_date">'
						. wfMsgHtml( 'thankauthors-date', $dateStr ) . "<br />
					</td>
				</tr>
				<tr>
					<td colspan=2 class=\"discussion_entry_comment\">
						$comment
					</td>
				</tr>
			</table>
		</div>
		<!-- end entry -->\n";

			wfDebugLog( 'ThankAuthors', "comment $formattedComment" );
			wfDebugLog( 'ThankAuthors', 'Checking blocks...' );

			if ( $wgUser->isBlocked() ) {
				$this->blockedIPpage();
				return;
			}
			if ( !$wgUser->getID() && $wgWhitelistEdit ) {
				$this->userNotLoggedInPage();
				return;
			}

			if ( $target == 'Spam-Blacklist' ) {
				$wgOut->readOnlyPage();
				return;
			}

			wfDebugLog( 'ThankAuthors', 'checking read only' );
			if ( wfReadOnly() ) {
				$wgOut->readOnlyPage();
				return;
			}

			wfDebugLog( 'ThankAuthors', 'checking rate limiter' );
			if ( $wgUser->pingLimiter( 'userkudos' ) ) {
				$wgOut->rateLimited();
				return;
			}

			wfDebugLog( 'ThankAuthors', 'checking blacklist' );

			if ( $wgFilterCallback && $wgFilterCallback( $title, $comment, '' ) ) {
				// Error messages or other handling should be
				// performed by the filter function
				return;
			}

			wfDebugLog( 'ThankAuthors', 'checking tokens' );

			$userToken = $wgRequest->getVal( 'token' );
			$token1 = $this->getToken1();
			$token2 = $this->getToken2();
			if ( $userToken != $token1 && $userToken != $token2 ) {
				wfDebugLog( 'ThankAuthors', "User kudos token doesn't match user: $userToken token1: $token1 token2: $token2" );
				return;
			}

			wfDebugLog( 'ThankAuthors', 'going through contributors');

			foreach ( $contributors as $c ) {
				$id = $c[0];
				$u = $c[1];
				wfDebugLog( 'ThankAuthors', "going through contributors $u $id\n" );
				if ( $id == '0' ) {
					continue; // forget the anon users.
				}
				$t = Title::newFromText( $u, NS_USER_KUDOS );
				$a = new Article( $t );
				$update = $t->getArticleID() > 0;
				$text = '';
				if ( $update ) {
					$text = $a->getContent( true );
					$text .= "\n\n" . $formattedComment;
					if ( $wgFilterCallback &&
						$wgFilterCallback( $t, $text, $text ) )
					{
						// Error messages or other handling should be
						// performed by the filter function
						return;
					}
				}
				if ( $update ) {
					$a->updateArticle( $text, '', true, false, false, '', false );
				} else {
					$a->insertNewArticle( $text, "", true, false, false, false, false );
				}
			}

			wfDebugLog( 'ThankAuthors', 'done' );
			$wgOut->addHTML( wfMsgHtml( 'thankauthors-done' ) );
			$wgOut->redirect( '' );
		}
	}

	function getToken1() {
		global $wgRequest, $wgUser;
		$d = substr( wfTimestampNow(), 0, 10 );
		$s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] .
			$_SERVER['REMOTE_ADDR'] . $wgRequest->getVal( 'target' ) . $d;
		wfDebugLog( 'ThankAuthors', "generating token 1 ($s) " . md5( $s ) . "\n" );
		return md5( $s );
	}

	function getToken2() {
		global $wgRequest, $wgUser;
		$d = substr( wfTimestamp( TS_MW, time() - 3600 ), 0, 10 );
		$s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] .
			$_SERVER['REMOTE_ADDR'] . $wgRequest->getVal( 'target' ) . $d;
		wfDebugLog( 'ThankAuthors', "generating token 2 ($s) " . md5( $s ) . "\n" );
		return md5( $s );
	}

}
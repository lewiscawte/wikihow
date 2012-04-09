<?php

class Request {

	function notifyRequest( $titleObj, $actualTitleObj ) {
		global $wgUser;

		$dbKey = $titleObj->getDBkey();
		if ( $actualTitleObj != null ) {
			$dbKey = $actualTitleObj->getDBkey();
		}

		$author_name = '';
		if ( $wgUser->getID() > 0 ) {
			$author_name = $wgUser->getRealName();
			if ( $author_name == '' ) {
				$author_name = $wgUser->getName();
			}
		}
		$subject = wfMsg( 'howto', $titleObj->getText() );
		$text = wfMsg( 'howto', $titleObj->getText() ) . "\n\n";

		if ( $wgUser->getID() > 0 ) {
			$text = wfMessage(
				'requesttopic-request-answered-email-by-logged-in-user',
				$titleObj->getText(),
				$titleObj->getFullURL(),
				$author_name,
				$wgUser->getTalkPage()->getFullURL()
			)->parse();
		} else {
			$text = wfMessage(
				'requesttopic-request-answered-email',
				$titleObj->getText(),
				$titleObj->getFullURL()
			)->parse();
		}

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'page', 'revision', 'user' ),
 			array( 'user_name', 'user_real_name', 'user_email' ),
			array(
				'rev_user = user_id',
				'page_namespace' => NS_ARTICLE_REQUEST,
				'page_title' => $dbKey,
				'rev_page = page_id'
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_id', 'LIMIT' => 1 )
		);
		while ( ( $row = $dbr->fetchObject( $res ) ) != null ) {
			$name = $row->user_real_name;
			if ( $name == '' ) {
				$name = $row->user_name;
			}
			$email = $row->user_email;
			if ( $email != '' ) {
				$senderInfo = wfMessage( 'requesttopic-email-sender' )->inContentLanguage()->plain();
				$to = new MailAddress( $email );
				$from = new MailAddress( $senderInfo );
				$mailResult = UserMailer::send( $to, $from, $subject, $text );
			}
		}
	}

	function getArticleRequestTop() {
		global $wgTitle, $wgArticle, $wgRequest, $wgUser;

		$s = '';

		$sk = $wgUser->getSkin();

		$action = $wgRequest->getVal( 'action' );

		if (
			$wgTitle->getNamespace() == NS_ARTICLE_REQUEST &&
			$action == '' &&
			$wgTitle->getArticleID() > 0
		)
		{
			$askedBy = $wgArticle->getUserText();
			$authors = $wgArticle->getContributors( 1 );
			$real_name = User::whoIsReal( $authors[0][0] );
			if ( $real_name != '' ) {
				$askedBy = $real_name;
			} elseif ( $authors[0][0] == 0 ) {
				$askedBy = wfMessage( 'requesttopic-anonymous-user' )->plain(); // previously used the 'user_anonymous' message
			} else {
				$askedBy = $authors[0][1];
			}
			$dateAsked = date( 'F d, Y', wfTimestamp( TS_UNIX, $wgArticle->getTimestamp() ) );

			$s .= '<div class="article_inner"><table>
			<tr>
			<td width="20%" valign="top">' . wfMessage( 'requesttopic-request' )->plain() . '</td><td><b>' .
				wfMsg( 'howto', $wgTitle->getText() ) . '</b></td>
			</tr>
			<tr>
				<td>' . wfMessage( 'requesttopic-asked-by' )->plain() . '</td><td>' . $askedBy . '</td>
			</tr>
			<tr>
				<td>' . wfMessage( 'requesttopic-date' )->plain() . '</td>
				<td>' . $dateAsked . '</td>
			</tr>
			<tr>
				<td valign="middle">' . wfMessage( 'requesttopic-details' )->plain() . '</td>
				<td><b>	';
		}

		return $s;
	}

	function getArticleRequestBottom() {
		global $wgTitle, $wgExtensionAssetsPath, $wgRequest;

		$arrowImgPath = $wgExtensionAssetsPath . '/RequestTopic/arrow.jpg';
		$s = '';
		$rt = $wgTitle->getPrefixedURL();
		$action = $wgRequest->getVal( 'action' );

		if (
			$wgTitle->getNamespace() == NS_ARTICLE_REQUEST &&
			$action == '' &&
			$wgTitle->getArticleID() > 0
		)
		{
			$s .= '</td>
					</tr>
					<tr>
						';
			$t = Title::makeTitle( NS_MAIN, $wgTitle->getText() );
			if ( $t->getArticleID() > 0 ) {
				$s .= '<td style="padding-left: 50px" colspan="2"><br /><br />' .
					wfMsg( 'requesttopic-answered-topic', $t->getText(), $t->getFullURL() ) .
					'</a>.';
			} else {
				$s .= '<td style="padding-left: 250px" colspan="2">
					<br /><br />' . wfMessage( 'requesttopic-can-you-help' )->plain() . "<br /><br />
					<img src=\"$arrowImgPath\" align=\"middle\">&nbsp;&nbsp;" .
						Linker::link(
							$wgTitle,
							wfMessage( 'requesttopic-write-howto', $wgTitle->getText() )->plain(),
							array(),
							array(
								'action' => 'edit',
								'requested' => $wgTitle->getDBkey()
							)
						) . '<br />
					<br /><!--<font size=-3>' . wfMessage( 'requesttopic-requested-topic-removed' )->plain() . "</font><br /><br />-->
					<img src=\"$arrowImgPath\" align=\"middle\">&nbsp;&nbsp;" .
						Linker::link(
							SpecialPage::getTitleFor( 'EmailLink' ),
							wfMessage( 'requesttopic-send-this-request' )->plain(),
							array(),
							array(
								'target' => $wgTitle->getPrefixedURL(),
								'returnto' => $rt
							)
						) .
					'<br /><font size="-3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
						wfMessage( 'requesttopic-know-an-expert' )->plain() . "</font><br /><br />
					<img src=\"$arrowImgPath\" align=\"middle\"> &nbsp;" .
					Linker::link(
						SpecialPage::getTitleFor( 'CreatePage' ),
						wfMessage( 'requesttopic-write-related' )->plain()
					) . ' <br />
					<font size="-3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
						wfMessage( 'requesttopic-topic-remains' )->plain() . "</font><br /><br />
					<img src=\"$arrowImgPath\" align=\"middle\"> &nbsp;" .
					Linker::link(
						Title::newFromText( wfMessage( 'aboutpage' )->inContentLanguage()->plain() ),
						wfMessage( 'requesttopic-learn-more-about-site' )->parse()
					) . ' <br />';
			}

			$s .= '</td>
				</tr>
			</table></div>';
		}

		return $s;
	}

	function notifyRequester( $article, $user, $user, $text, $summary ) {
		global $wgContLang, $wgTitle, $wgRequest;

		$requested = $wgRequest->getVal( 'requested', null );
		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		$summaryMsg = wfMessage( 'requesttopic-now-answered' )->inContentLanguage()->plain();

		if ( $requested != null && $summary != $summaryMsg ) {
			$actualTitleObj = Title::newFromText( $wgTitle->getDBkey(), NS_ARTICLE_REQUEST );
			$actualKey = $wgTitle->getDBKey();
			if ( $requested != $actualKey ) {
				$ot = Title::newFromText( $requested, NS_ARTICLE_REQUEST );
				$nt = Title::newFromText( $actualKey, NS_ARTICLE_REQUEST );
				$error = $ot->moveTo( $nt );
				if ( $error !== true ) {
					echo $error;
				}
				$actualTitleObj = $nt;
			}
			Request::notifyRequest( $wgTitle, $actualTitleObj );
			// strip categories
			$at = new Article( $actualTitleObj );
			$text = $at->getContent( true );
			// @todo FIXME: ereg_* functions are deprecated since PHP 5.3+
			$text = ereg_replace( "[\[]+$categoryNamespaceName\:([- ]*[.]?[a-zA-Z0-9_/-?&%])*[]]+", '', $text );
			$answeredRequestsCategory = wfMessage( 'requesttopic-answered-category' )->inContentLanguage()->plain();
			$text .= "[[$categoryNamespaceName:$answeredRequestsCategory]]";
			$at->doEdit( $text, $summaryMsg, EDIT_MINOR );
		}

		return true;
	}
}
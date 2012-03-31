<?php

class RequestTopic extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'RequestTopic' );
	}

	function uncategorizeRequest( $article, $user, $reason ) {
		global $wgContLang, $wgOut;

		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		// Is the article brand new?
		$t = $article->getTitle();
		if ( $t->getNamespace() == NS_MAIN ) {
			$answeredCategoryName = wfMessage( 'requesttopic-answered-category' )->inContentLanguage()->plain();
			$dbr = wfGetDB( DB_SLAVE );
			$r = Title::makeTitle( NS_ARTICLE_REQUEST, $t->getText() );

			if ( $r->getArticleID() < 0 ) {
				$r = Title::makeTitle( NS_ARTICLE_REQUEST, EditPageWrapper::formatTitle( $t->getText() ) );
			}

			if ( $r->getArticleID() > 0 ) {
				$res = $dbr->select(
					'revision',
					array( 'rev_id' ),
					array( 'rev_page' => $r->getArticleId() ),
					__METHOD__,
					array( 'ORDER BY' => 'rev_id DESC' )
				);
				$answered .= '[[' . $categoryNamespaceName . ':' . $answeredCategoryName . ']]';
				$origcat = '';

				foreach ( $res as $row ) {
					$rev = Revision::newFromId( $row->rev_id );
					$text = $rev->getText();
					// does it match answered?
					if ( strpos( $text, $answered ) === false ) {
						preg_match( '/\[\[' . $categoryNamespaceName . '[^\]]*\]\]/', $text, $matches );
						if ( sizeof( $matches[0] ) > 0 ) {
							$origcat = $matches[0];
						}
						break;
					}
				}

				if ( $origcat != null ) {
					$revision = Revision::newFromTitle( $r );
					$text = $revision->getText();
					if ( strpos( $text, $answeredCategoryName ) !== false ) {
						$ra = new Article( $r );
						// @todo FIXME: ereg_* functions are deprecated since PHP 5.3+
						$text = ereg_replace( "[\[]+" . $categoryNamespaceName . "\:([- ]*[.]?[a-zA-Z0-9_/-?&%])*[]]+", '', $text );
						$text .= "\n$origcat";
						$ra->doEdit(
							$text,
							wfMessage( 'requesttopic-request-no-longer-answered' )->inContentLanguage()->plain(),
							EDIT_MINOR
						);
						$wgOut->redirect( '' );
					}
				}
			}
		}

		return true;
	}

	function notifyRequests( $article, $user, $text, $summary, $p5, $p6, $p7 ) {
		global $wgContLang;

		Request::notifyRequester( $article, $user, $user, $text, $summary );

		$answeredCategoryName = wfMessage( 'requesttopic-answered-category' )->inContentLanguage()->plain();
		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		// Is the article brand new?
		$t = $article->getTitle();

		if ( $t->getNamespace() == NS_MAIN ) {
			$dbr = wfGetDB( DB_SLAVE );
			$num_revisions = $dbr->selectField(
				'revision',
				'COUNT(*)',
				array( 'rev_page' => $article->getId() ),
				__METHOD__
			);
			if ( $num_revisions == 1 ) {
				// new article
				$r = Title::makeTitle( NS_ARTICLE_REQUEST, $t->getText() );
				if ( $r->getArticleID() < 0 ) {
					$r = Title::makeTitle(
						NS_ARTICLE_REQUEST,
						EditPageWrapper::formatTitle( $t->getText() )
					);
				}

				if ( $r->getArticleID() > 0 ) {
					$revision = Revision::newFromTitle( $r );
					$text = $revision->getText();
					if ( strpos( $text, $answeredCategoryName ) === false ) {
						$ra = new Article( $r );
						// @todo FIXME: ereg_* functions are deprecated since PHP 5.3+
						$text = ereg_replace( "[\[]+$categoryNamespaceName\:([- ]*[.]?[a-zA-Z0-9_/-?&%])*[]]+", '', $text );
						$text .= "\n[[" . $categoryNamespaceName . ':' . $answeredCategoryName . ']]';
						$ra->doEdit(
							$text,
							wfMessage( 'requesttopic-request-now-answered' )->inContentLanguage()->plain(),
							EDIT_MINOR
						);
					}
				}
			}
		}

		return true;
	}

	function getCategoryOptions( $default = '' ) {
		global $wgUser;

		// only do this for logged in users
		$t = Title::newFromDBKey( wfMessage( 'requesttopic-requestcategories-page' )->inContentLanguage()->plain() );
		$r = Revision::newFromTitle( $t );
		if ( !$r ) {
			return '';
		}

		$cat_array = explode( "\n", $r->getText() );
		$s = '';

		foreach( $cat_array as $line ) {
			$line = trim( $line );
			if ( $line == '' || strpos( $line, '[[' ) === 0 ) {
				continue;
			}
			$tokens = explode( ':', $line );
			$val = '';
			$val = trim( $tokens[sizeof( $tokens ) - 1] );
			$s .= '<option value="' . $val . '">' . $line . "</option>\n";
		}

		$s = str_replace( "\"$default\"", "\"$default\" selected=\"selected\"", $s );

		return $s;
	}

	function getForm( $hidden = false ) {
		global $wgOut, $wgUser, $wgScriptPath, $wgLang;

		$topic =  $details = $override = $name = $email = $category = '';

		if ( isset( $_POST['topic'] ) ) {
			$topic = htmlspecialchars( $_POST['topic'] );
			$override = '<input type="hidden" name="override" value="yes">';
		}
		if ( isset( $_POST['details'] ) ) {
			$details = htmlspecialchars( $_POST['details'] );
		}
		if ( isset( $_POST['email'] ) ) {
			$email = htmlspecialchars( $_POST['email'] );
		}
		if ( isset( $_POST['name'] ) ) {
			$name = htmlspecialchars( $_POST['name'] );
		}
		if ( isset( $_POST['category'] ) ) {
			$category = $_POST['category'];
		}

		$action = $this->getTitle()->getFullURL();

		$onsubmit = 'false';
		$dropdown = '';
		$categories = $this->getCategoryOptions();
		if ( $categories != '' ) {
			$onsubmit = 'true';
			$dropdown = '<select name="category">
					<option value="">' . wfMessage( 'requesttopic-categorize-request' )->plain() . "</option>
					{$categories}
				</select>";
		}

		// Add JS, which is active when the $onsubmit variable is 'true'
		$wgOut->addModules( 'ext.requestTopic' );

		// add the form HTML
		$wgOut->addHTML(
			"<form id=\"requesttopic\" name=\"requesttopic\" method=\"post\" action=\"{$action}\" data-onsubmit=\"{$onsubmit}\">{$override}"
		);

		if ( $hidden ) {
			$mainPageObj = Title::newMainPage();
			$wgOut->addHTML(
				"<input type=\"hidden\" name=\"topic\" value=\"$topic\">
					<input type=\"hidden\" name=\"details\" value=\"$details\">
					<input type=\"hidden\" name=\"name\" value=\"$name\">
					<input type=\"hidden\" name=\"email\" value=\"$email\">
					<input type=\"hidden\" name=\"category\" value=\"$category\">
					<input tabindex=\"11\" type=\"button\" name=\"nosubmit\"
						value=\"" . wfMessage( 'requesttopic-dont-submit' )->plain() .
						"\" class=\"btn\" onmouseover=\"this.className='btn btnhov'\" onmouseout=\"this.className='btn'\"
						onclick='window.location.href=\"" . $mainPageObj->escapeLocalURL() . "\"' />
					" . wfMessage( 'requesttopic-page-covers-request' )->plain() . " <br /><br />
					<input tabindex=\"11\" type=\"submit\" name=\"submit\"
						value='" . wfMessage( 'requesttopic-submit-anyway' )->plain() .
						"' class=\"btn\" onmouseover=\"this.className='btn btnhov'\" onmouseout=\"this.className='btn'\" />
					" . wfMessage( 'requesttopic-unique-topic' )->plain() .
				'</form>'
			);
			return;
		}

		$wgOut->addHTML(
				'<table border="0">
				<tr>
					<td><b>"' . wfMsg( 'howto', '' ) . " <input type=text size=\"40\" name=\"topic\" value=\"$topic\" >\"</td>
				</tr>
				<tr>
					<td>
					{$dropdown}
					</td>
				</tr>
				<tr>
					<td colspan=\"4\"><br /><b>" . wfMessage( 'requesttopic-optional-information' )->plain() . '</b></td>
				</tr>'
		);

		// do this if the user isn't logged in
		$login = SpecialPage::getTitleFor( 'Userlogin' );

		if ( $wgUser->getID() <= 0 ) {
			$wgOut->addHTML(
					'<tr>
						<td colspan="4" bgcolor="#cccccc">
							<table bgcolor="white" width="100%">
								<tr>
									<td>
										<input type="checkbox" name="login" value="false" checked="true" />
										<font face="Arial, Helvetica, sans-serif" size="2">' .
											wfMessage( 'requesttopic-email-upon-article-written' )->plain() . '<br />
										</font>
									</td>
								</tr>
								<tr>
									<td valign="top" width="50%">
										<table cellpadding="2">
											<tr>
												<td><font face="Arial, Helvetica, sans-serif" size="2">' .
													wfMessage( 'requesttopic-name' )->plain() . "</font></td>
												<td><input id=\"input\" type=\"text\" name=\"name\" value=\"$name\"></td>
											</tr>
											<tr>
												<td colspan=\"2\">
													<font face=\"Arial, Helvetica, sans-serif\" size=\"-2\" color=\"#666666\">" .
													wfMessage( 'requesttopic-optional-blank-anonymous' )->parse() .
													'</font>
												</td>
											<tr>
												<td><font face="Arial, Helvetica, sans-serif" size="2">' .
													wfMessage( 'requesttopic-email' )->plain() .
												"</font></td>
												<td><input id=input type=\"text\" name=\"email\" value=\"$email\"></td>
											</tr>
											<tr>
												<td colspan=\"2\"><font face=\"Arial, Helvetica, sans-serif\" size=\"-2\" color=\"#666666\">" .
													wfMessage( 'requesttopic-optional-email-notify' )->plain() .
												'</font>
												</td>
										</table>
									</td>
								</tr>
							<tr>
								<td>' .
									wfMsg( 'requesttopic-or-login-here', $login->getFullURL( array(
										'returnto' => $this->getTitle()->getPrefixedDBkey() ) ) ) .
								'</td>
							</tr>
						</table>
					</td>
				</tr>'
			);
		}

		$wgOut->addHTML(
			'<tr>
				<td colspan="4"><br />' . wfMessage( 'requesttopic-additional-topic-details' )->parse() . "</td>
			</tr>
			<tr>
				<td colspan=\"4\"><textarea rows=\"5\" cols=\"55\" name=\"details\">$details</textarea></td>
			</tr>
			<tr>
				<td colspan=\"4\"><input type=\"submit\" value=\"" . wfMsg( 'requesttopic-submit' ) . '" /></td>
			</tr>
			<tr>
				<td colspan="2"><br /><br />' .
					wfMessage( 'requesttopic-view-suggested' )->parse() .
				'</td>
			</tr>
			</table>
		</form>'
		);
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgUser, $wgOut, $wgLang, $wgContLang, $wgRequest, $wgParser;
		global $wgLanguageCode, $wgFilterCallback;

		// Blocked users can't make any new requests, obviously
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if ( !$wgRequest->wasPosted() ) {
			$wgOut->addHTML(
				'<b>' . wfMessage( 'requesttopic-looking-for-how' )->parse() .
				'</b> <br /> ' . wfMessage( 'requesttopic-request-article-ex-dog' )->parse() .
				'<br /><br /> '
			);
			$this->getForm();
		} else {
			// this is a post, accept the POST data and create the
			// Request article
			$topic = $wgRequest->getVal( 'topic' );
			$details = $wgRequest->getVal( 'details' );

			if ( $wgUser->getID() == 0 && preg_match( '@http://@i', $details ) ) {
				$wgOut->addWikiMsg( 'requesttopic-error-anon-no-links' );
				return;
			}

			if ( !isset( $_POST['override'] ) && $wgLanguageCode == 'en' ) {
				$l = new LSearch();
				$titles = $l->googleSearchResultTitles( $topic, 0, 5 );
				if ( sizeof( $titles ) > 0 ) {
					$wgOut->addHTML(
						wfMessage( 'requesttopic-already-related-topics' )->parse() . '<br />
						<ul id="Things_You27ll_Need">'
					);
					$count = 0;
					foreach ( $titles as $t ) {
						if ( $count == 10 ) {
							break;
						}
						if ( $t == null ) {
							continue;
						}
						$wgOut->addHTML(
							'<li style="margin-bottom: 0px"><a href="' .
								$t->getFullURL() . '">' .
								wfMessage( 'howto', $t->getText() )->parse() .
							'</a></li>'
						);
						$count++;
					}
					$wgOut->addHTML( '</ul>' );
					$wgOut->addWikiMsg( 'requesttopic-no-submit-existing-topic' ) );
					$this->getForm( true );
					return;
				}
			}

			// cut off extra ?'s or whatever
			if ( $wgLanguageCode == 'en' ) {
				// @todo FIXME: ereg_* functions are deprecated since PHP 5.3+
				while ( !ereg( '[a-zA-Z0-9)\"]$', $topic ) ) {
					$topic = substr( $topic, 0, strlen( $topic ) - 1 );
				}
			}
			if ( $wgLanguageCode == 'en' ) {
				$topic = EditPageWrapper::formatTitle( $topic );
			}
			$title = Title::newFromText( $topic, NS_ARTICLE_REQUEST );

			$category = $wgRequest->getVal( 'category', '' );
			if ( $category == '' ) {
				$category = wfMessage( 'requesttopic-category-other' )->inContentLanguage()->plain();
			}

			$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
			$categoryName = wfMessage( 'requesttopic-request-category', $category )->inContentLanguage()->plain();
			$details .= "\n[[$categoryNamespaceName:$categoryName]]";

			// check if we can do this
			if ( $wgUser->pingLimiter() ) {
				$wgOut->rateLimited();
				return;
			}

			if ( $wgFilterCallback
				&& $wgFilterCallback( $title, $details, $tmp ) )
			{
				// Error messages or other handling should be performed by
				// the filter function
				return;
			}

			// create a user
			$user = null;
			if ( $wgUser->getID() == 0 ) {
				if ( $wgRequest->getVal( 'email', null ) ) {
					$user = User::createTemporaryUser(
						$wgRequest->getVal( 'name' ),
						$wgRequest->getVal( 'email' )
					);
					$wgUser = $user;
				}
			}

			if ( $title->getArticleID() <= 0 ) {
				// not yet created. good.
				$article = new Article( $title );
				//$ret = $article->insertNewArticle( $details, '', false, false, false, $user );
				$ret = $article->doEdit( $details, '' );
				wfRunHooks(
					'ArticleSaveComplete',
					// @todo FIXME: this is horrible. I don't know how I should
					// implement the additional parameters added in later versions
					// of MediaWiki, so I just made them null/false, but that
					// probably breaks something...
					array( &$article, &$user, $details, '', false, false, null, null, false, false, false )
				);

				// clear the redirect that is set by doEdit
				$wgOut->redirect( '' );

				$options = ParserOptions::newFromUser( $wgUser );
				$wgParser->parse( $details, $title, $options );
			} else {
				// TODO: what to do here? give error / warning? append details?
				// this question has already been asked, if you want to ask
				// a slightly different question, go here:
			}

			$wgOut->addWikiMsg( 'requesttopic-thank-you-requesting-topic' );
			$wgOut->returnToMain( false );
		}
	}

	/**
	 * Register the canonical names for our custom namespaces and their talkspaces.
	 *
	 * @param $list Array: array of namespace numbers with corresponding
	 *                     canonical names
	 * @return Boolean: true
	 */
	public static function registerCanonicalNamespaces( &$list ) {
		$list[NS_ARTICLE_REQUEST] = 'Request';
		$list[NS_ARTICLE_REQUEST_TALK] = 'Request_talk';
		return true;
	}
}

class ListRequestedTopics extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ListRequestedTopics' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgLang, $wgOut, $wgRequest, $wgScript, $wgUser;

		$offset = $wgRequest->getInt( 'offset', 0 );
		$numPerPage = 50;
		$dbr = wfGetDB( DB_SLAVE );

		$total = $dbr->selectField(
			'page',
			'COUNT(*)',
			array(
				'page_namespace' => NS_ARTICLE_REQUEST,
				'page_is_redirect' => 0
			)
		);

		$wgOut->addWikiMsg( 'listrequestedtopics-about' );
		$wgOut->addHTML( '<br /><br />' );
		$wgOut->addWikiMsg( 'listrequestedtopics-request-topic-here' );
		$wgOut->addHTML( '<br /><br />' );
		$wgOut->addWikiMsg( 'listrequestedtopics-browse' );
		$wgOut->addHTML( '<table style="padding-left: 20px; margin-top:30px;" width="100%" cellpadding="0">' );

		$res = $dbr->query(
			"SELECT p1.page_title AS page_title, p1.page_touched AS page_touched, p2.page_title AS article_title FROM
				{$dbr->tableName( 'page' )} p1 LEFT OUTER JOIN {$dbr->tableName( 'page' )} p2 ON p1.page_title=p2.page_title AND p2.page_namespace = " . NS_MAIN .
				' WHERE p1.page_namespace = ' . NS_ARTICLE_REQUEST .
				" AND p1.page_is_redirect = 0 ORDER BY page_touched DESC LIMIT $offset, $numPerPage",
			__METHOD__
		);

		if ( $dbr->numRows( $res ) == 0 ) {
			$wgOut->addHTML(
				'<tr><td colspan="4">' .
				wfMessage( 'listrequestedtopics-no-topics' )->parse() .
				'<br /></td></tr>'
			);
		}

		$parity = 0;
		$count = 0;
		$datestr = '';

		foreach ( $res as $row ) {
			$year = substr( $row->page_touched, 0, 4 );
			$month = $wgLang->getMonthName( substr( $row->page_touched, 4, 2 ) );

			$str = "$month $year";

			if ( $count == 0 ) {
				$wgOut->addHTML(
					"<tr><td style=\"padding-left: 0px\"><b>$str</b></td>
						<td></td>
					</tr>
					<tr><td colspan=\"3\">&nbsp;</td></tr>
					</tr>"
				);
				$datestr = $str;
			}

			if ( $datestr != $str ) {
				$wgOut->addHTML(
					"<tr><td colspan=\"3\">&nbsp;</td></tr>
					<tr>
						<td style=\"padding-left: 0px\"><b>$str</b></td><td><font size=-2></td>
						<td></td>
					</tr>
					<tr><td colspan=\"3\">&nbsp;</td></tr>
					</tr>"
				);
				$datestr = $str;
			}

			$bgcolor = '#eeeeee';
			if ( $count % 2 == 0 ) {
				$bgcolor = '#ffffff';
			}

			$request = Title::makeTitle( NS_ARTICLE_REQUEST, $row->page_title );
			$title = null;
			if ( $row->article_title ) {
				$title = Title::makeTitle( NS_MAIN, $row->page_title );
			}

			if ( !$request ) {
				continue;
			}

			$found = false;
			if ( $title ) {
				// article is answered
				$found = true;
				$wgOut->addHTML( "<tr><td bgcolor=\"$bgcolor\" width=\"60%\">" );
				$wgOut->addHTML( Linker::link( $title, $title->getText() ) );
				$wgOut->addHTML(
					'<sup><span style="color: #339900">' .
						wfMessage( 'requesttopic-answered' )->parse() .
					'</span></sup>'
				);
				$wgOut->addHTML(
					"<td bgcolor=\"$bgcolor\">" .
					Linker::link( $title, wfMessage( 'listrequestedtopics-view-article' )->plain() )
				);
			} else {
				// article is NOT answered
				$wgOut->addHTML( "<tr><td bgcolor=\"$bgcolor\" width=\"60%\">" );
				$wgOut->addHTML( Linker::link( $request, $request->getText() ) );

				$wgOut->addHTML(
					"<td width=\"25%\" bgcolor=\"$bgcolor\"><a id=\"gatCreateArticle\" href=\"$wgScript?title=" .
						$request->getDBkey() . '&action=edit&requested=' .
						$request->getDBkey() . '">' .
						wfMessage( 'listrequestedtopics-write-article' )->plain() .
						'</a>'
				);
			}

			if ( $wgUser->isAllowed( 'delete' ) ) {
				$wgOut->addHTML( ' <br />- ' . Linker::link(
					$request,
					wfMsg( 'delete' ),
					array(),
					array( 'action' => 'delete' )
				) );
				if ( !$found ) {
					$wgOut->addHTML( ' - ' . Linker::link(
						SpecialPage::getTitleFor( 'Movepage' ),
						wfMsg( 'edit_title' ),
						array(),
						array( 'target' => $request->getPrefixedURL() )
					) );
				}
			}
			$wgOut->addHTML( " </td> </tr> \n" );

			$count++;
		}

		// next links
		$me = $this->getTitle();
		$wgOut->addHTML( '<tr><td><br /><br />' );
		if ( $offset > 0 ) {
			$wgOut->addHTML(
				'(' . Linker::link(
					$me,
					wfMsg( 'prevn', $numPerPage ),
					array(),
					array( 'offset' => ( $offset - $numPerPage ) )
				) . ')'
			);
		}
		if ( $offset + $numPerPage < $total ) {
			$offset += $numPerPage;
			$wgOut->addHTML(
				' (' . Linker::link(
					$me,
					wfMsg( 'nextn', $numPerPage ),
					array(),
					array( 'offset' => $offset )
				) . ')'
			);
		}

		$wgOut->addHTML( '</td></tr></table><br /><br />' );
		$wgOut->returnToMain( false );
	}

}
<?php

class ManagePageList extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ManagePageList', 'managepagelist' );
	}

	/**
	 * Show the new special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgUser, $wgLang, $wgExtensionAssetsPath;

		// Need a special permission to use this special page.
		if( !$wgUser->isAllowed( 'managepagelist' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Can't use this special page when the database is locked.
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// No access for blocked users.
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Add CSS
		$wgOut->addModules( 'ext.managePageList' );

		$list = $wgRequest->getVal( 'list', 'risingstar' );

		$dbr = wfGetDB( DB_SLAVE );

		// Handle removals
		if ( $wgRequest->getVal( 'a' ) == 'remove' ) {
			$t = Title::newFromID( $wgRequest->getInt( 'id' ) );
			$dbw = wfGetDB( DB_MASTER );
			$dbw->delete(
				'pagelist',
				array(
					'pl_page' => $wgRequest->getInt( 'id' ),
					'pl_list' => $list
				),
				__METHOD__
			);
			$wgOut->addHTML(
				'<p style="color: blue; font-weight: bold;">' .
				wfMsg( 'managepagelist-page-removed', $t->getFullText() ) .
				'</p>'
			);
		}

		if ( $wgRequest->wasPosted() ) {
			if ( $wgRequest->getVal( 'newlist' ) ) {
				$list = $wgRequest->getVal( 'newlist' );
				$mw = Title::makeTitle(
					NS_MEDIAWIKI,
					'Pagelist_' . $wgRequest->getVal( 'newlist' )
				);
				$a = new Article( $mw );
				$a->doEdit(
					$wgRequest->getVal( 'newlistname' ),
					wfMsgForContent( 'managepagelist-creation-summary' )
				);
			}
			if ( $wgRequest->getVal( 'newtitle' ) ) {
				$url = $wgRequest->getVal( 'newtitle' );
				$url = preg_replace( '@http://@', '', $url );
				$url = preg_replace( '@.*/@U', '', $url );
				$t = Title::newFromURL( $url );
				if ( !$t || !$t->getArticleID() ) {
					$wgOut->addHTML(
						'<p style="color: red; font-weight: bold;">' .
						wfMsg( 'managepagelist-error-page-id', $wgRequest->getVal( 'newtitle' ) ) .
						'</p>'
					);
				} else {
					$query = $dbr->selectField(
						'pagelist',
						'COUNT(*)',
						array(
							'pl_page' => $t->getArticleID(),
							'pl_list' => $list
						),
						__METHOD__
					);
					if ( $query > 0 ) {
						$wgOut->addHTML(
							'<p style="color: red; font-weight: bold;">' .
							wfMsg( 'managepagelist-error-already-listed' ) .
							'</p>'
						);
					} else {
						$dbw = wfGetDB( DB_MASTER );
						$dbw->insert(
							'pagelist',
							array(
								'pl_page' => $t->getArticleID(),
								'pl_list' => $list
							),
							__METHOD__
						);
						if ( $list == 'risingstar' && class_exists( 'Newarticleboost' ) ) {
							// add the rising star template to the discussion page
							$talk = $t->getTalkPage();
							$a = new Article( $talk );
							$text = $a->getContent();
							$min = $dbr->selectField(
								'revision',
								array( 'MIN(rev_id)' ),
								array( 'rev_page' => $t->getArticleId() ),
								__METHOD__
							);
							$name = $dbr->selectField(
								'revision',
								'rev_user_text',
								array( 'rev_id' => $min ),
								__METHOD__
							);
							$text = wfMsgForContent(
								'managepagelist-template',
								$name,
								$wgUser->getName()
							) . "\n" . $text;
							$a->doEdit(
								$text,
								wfMsgForContent( 'nab-rs-discussion-editsummary' )
							);

							// add the comment to the user's talk page
							Newarticleboost::notifyUserOfRisingStar( $t, $name );
						}
						$wgOut->addHTML(
							'<p style="color: blue; font-weight: bold;">' .
							wfMsg( 'managepagelist-page-added', $t->getFullText() ) .
							'</p>'
						);
					}
				}
			}
		}
		$wgOut->setPageTitle(
			wfMsg( 'managepagelist-title', wfMsg( 'pagelist_' . $list ) )
		);
		$wgOut->addHTML(
			'<form name="addform" method="post" action="' . $this->getTitle()->escapeFullURL() . '">
				<table style="width: 100%;">
					<tr>
						<td style="width: 430px;">' .
							wfMsg( 'managepagelist-add-page' ) .
							'<input type="text" name="newtitle" id="newtitle" />
						</td>
						<td style="width: 32px; vertical-align: bottom;">
							<input type="image" class="addicon" src="' . $wgExtensionAssetsPath . '/ManagePageList/plus.png" onclick="javascript:document.addform.submit()" />
						</td>
						<td style="text-align: right;">' . wfMsg( 'managepagelist-view-list' ) . '<br />
							<select onchange=\'window.location.href=wgServer + wgScriptPath + "/Special:ManagePageList&list=" + this.value;\'>'
		);

		$res = $dbr->select(
			'pagelist',
			'DISTINCT(pl_list)',
			array(),
			__METHOD__
		);
		foreach( $res as $row ) {
			if ( $row->pl_list == $list ) {
				$wgOut->addHTML(
					'<option selected="selected" style="font-weight: bold;">' .
						wfMsg( 'pagelist_' . $row->pl_list ) .
					"</option>\n"
				);
			} else {
				$wgOut->addHTML(
					'<option>' . wfMsg( 'pagelist_' . $row->pl_list ) .
					"</option>\n"
				);
			}
		}
		$wgOut->addHTML(
			'</select>
		</td>
		</tr>
		</table>
		</form>'
		);
		$res = $dbr->select(
			array( 'page', 'pagelist' ),
			array( 'page_title', 'page_namespace', 'page_id' ),
			array( 'page_id = pl_page', 'pl_list' => $list ),
			__METHOD__,
			array( 'ORDER BY' => 'pl_page DESC' )
		);

		$wgOut->addHTML(
			'<br /><p>' .
			wfMsg(
				'managepagelist-page-count',
				'parsemag',
				$wgLang->formatNum( $dbr->numRows( $res ) )
			) . '</p>'
		);
		$wgOut->addHTML( '<table class="pagelist">' );

		$index = 0;
		foreach( $res as $row ) {
			$t = Title::makeTitle(
				$row->page_namespace,
				$row->page_title
			);
			if ( !$t ) {
				$wgOut->addHTML(
					wfMsg(
						'managepagelist-error-make-title',
						$row->page_namespace,
						$row->page_title
					) . "\n"
				);
				continue;
			}
			if ( $index % 2 == 0 ) {
				$wgOut->addHTML( '<tr>' );
			} else {
				$wgOut->addHTML( '<tr class="shaded">' );
			}
			$wgOut->addHTML(
				'<td class="pagelist_title"><a href="' . $t->getFullURL() . '" target="new">' . $t->getFullText() . '</td>
					<td>
						<a href="' . $this->getTitle()->escapeFullURL( array( 'a' => 'remove', 'list' => $list, 'id' => $row->page_id ) ) . '" onclick="return confirm(\"' . wfMsg( 'managepagelist-confirm') . '\")">
							<img src="' . $wgExtensionAssetsPath . '/ManagePageList/rcwDelete.png" style="height: 24px; width: 24px;">
						</a>
					</td>'
			);
			$wgOut->addHTML( '</tr>' );
			$index++;
		}
		$wgOut->addHTML( '</table>' );

		$wgOut->addHTML(
			'<form name="addlistform" method="post" action="' . $this->getTitle()->escapeFullURL() . '">
				<br /><br />
				<table width="100%">
					<tr>
						<td>' . wfMsg( 'managepagelist-create-new' ) . '<br /><br />' .

						wfMsg( 'managepagelist-id' ) . '<input type="text" name="newlist" id="newlist" />' .
						wfMsg( 'managepagelist-name' ) . '<input type="text" name="newlistname" id="newlistname" /><br /><br />' .
						wfMsg( 'managepagelist-page' ) . '<input type="text" name="newtitle" id="newtitle" />
					</td>
					<td style="width: 32px; vertical-align: bottom;">
						<input type="image" class="addicon" src="' . $wgExtensionAssetsPath . '/ManagePageList/plus.png" onclick="javascript:document.addlistform.submit()" />
					</td>
				</tr>
			</table>
		</form>'
		);
	}
}

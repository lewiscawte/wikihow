<?php

class DocentSettings extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'DocentSettings' );
	}

	function getCheckbox( $name, $already_subscribed, $trail = '', $disable_name = true ) {
		$option = '';
		$display = $name;
		$name = htmlspecialchars( $name );
		$style = '';
		if( isset( $already_subscribed[$display] ) ) {
			$option = ' checked="checked" disabled="disabled"';
			if( $disable_name ) {
				$name = 'disbled_name';
				return "<input {$style} type=\"checkbox\" name=\"{$name}\"{$option}> <i>{$display}</i> {$trail}";
			} else {
				$option = ' checked="checked"';
			}
		}
		return "<input {$style} type=\"checkbox\" name=\"{$name}\" value=\"{$name}\"{$option}> {$display} {$trail}";
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgUser, $wgOut, $wgLang, $wgServer;

		// Add CSS & JS
		$wgOut->addModules( 'ext.docentSettings' );

		$dbr = wfGetDB( DB_SLAVE );

		if ( $wgRequest->wasPosted() ) {
			$new = array();
			$new_key = array();
			foreach( $wgRequest->getValues() as $key => $value ) {
				if( $value && ( $key != 'title' ) ) {
					$t = Title::makeTitle( NS_CATEGORY, $key );
					if( $t->getArticleID() == 0 ) {
						$wgOut->addHTML( wfMsg( 'docentsettings-error', $key ) );
					} else {
						$new[] = $t;
						$new_key[$t->getDBkey()] = 1;
					}
				}
			}

			$dbw = wfGetDB( DB_MASTER );

			// Update the mailman settings
			$old_key = array();
			$res = $dbr->select(
				'docentcategories',
				array( 'dc_to' ),
				array( 'dc_user' => $wgUser->getID() ),
				__METHOD__
			);
			foreach ( $res as $row ) {
				$old_key[$row->dc_to] = 1;
			}

			$remove = array();
			foreach( $old_key as $key => $value ) {
				if( !isset( $new_key[$key] ) ) {
					$remove[] = $key;
				}
			}
			$add = array();
			foreach( $new_key as $key => $value ) {
				if( !isset( $old_key[$key] ) ) {
					$add[] = $key;
				}
			}

			foreach( $add as $a ) {
				$t = Title::makeTitle( NS_CATEGORY, $a );
				$dbw->delete(
					'mailman_unsubscribe',
					array(
						'mm_user' => $wgUser->getID(),
						'mm_list' => $t->getDBkey(),
						'mm_done=0'
					),
					__METHOD__
				);
				$dbw->insert(
					'mailman_subscribe',
					array(
						'mm_user' => $wgUser->getID(),
						'mm_list' => $t->getDBkey()
					),
					__METHOD__
				);
			}
			foreach( $remove as $a ) {
				$t = Title::makeTitle( NS_CATEGORY, $a );
				$dbw->delete(
					'mailman_subscribe',
					array(
						'mm_user' => $wgUser->getID(),
						'mm_list' => $t->getDBkey(),
						'mm_done=0'
					),
					__METHOD__
				);
				$dbw->insert(
					'mailman_unsubscribe',
					array(
						'mm_user' => $wgUser->getID(),
						'mm_list' => $t->getDBkey()
					),
					__METHOD__
				);
			}

			// Update the mailman settings
			$dbw->delete(
				'docentcategories',
				array( 'dc_user' => $wgUser->getID() ),
				__METHOD__
			);

			foreach( $new as $t ) {
				$dbw->insert(
					'docentcategories',
					array(
						'dc_user' => $wgUser->getID(),
						'dc_to' => $t->getDBkey()
					),
					__METHOD__
				);
			}
		}
		$wgOut->addHTML( wfMsg( 'docentsettings-info' ) . '<br /><br />' );
		$cats = Categoryhelper::getCategoryDropDownTree();
		$count = 0;

		$wgOut->addHTML( "<form method=\"post\" action=\"{$this->getTitle()->getFullURL()}\">" );
		// check the referrer
		$refer = $_SERVER['HTTP_REFERER'];
		// @todo FIXME: this does NOT take $wgScriptPath OR even i18n into account...
		// --ashley, 18 June 2011
		if ( $refer && strpos( $refer, $wgServer . '/Category' ) === 0 ) {
			$refer = str_replace( $wgServer . '/', '', $refer );
			$t = Title::newFromURL( $refer );
			if ( $t && !isset( $already_subscribed[$t->getText()] ) ) {
				$already_subscribed[$t->getText()] = 1;
				$wgOut->addHTML(
					wfMsg( 'docentsettings-add' ) .
					' <table width="100%" class="docentsettings"><tr>'
				);
				$wgOut->addHTML(
					'<td>' . $this->getCheckbox(
						$t->getText(), $already_subscribed, '', false
					) . '</td>'
				);
				$wgOut->addHTML( '</tr><tr>' );
				$wgOut->addHTML( '</tr></table><br />' );
			}
		}

		$wgOut->addHTML( wfMsg( 'docentsettings-current' ) . '<br /><br />
			<table width="100%" class="docentsettings"><tr>'
		);

		$count = 0;
		$res = $dbr->select(
			'docentcategories',
			array( 'dc_to' ),
			array( 'dc_user' => $wgUser->getId() ),
			__METHOD__
		);
		$already_subscribed = array();
		foreach ( $res as $row ) {
			$t = Title::makeTitle( NS_CATEGORY, $row->dc_to );
			$already_subscribed[$t->getText()] = 1;
			$wgOut->addHTML(
				'<td>' . $this->getCheckbox(
					$t->getText(), $already_subscribed, '', false
				) . '</td>'
			);
			if( $count % 2 == 1 ) {
				$wgOut->addHTML( '</tr><tr>' );
			}
			$count++;
		}
		$wgOut->addHTML( '</tr><table>' );

		// Get the list of categories to ignore
		$templates = wfMsgForContent( 'docentsettings_categories_to_ignore' );
		$t_arr = explode( "\n", str_replace( ' ', '-', str_replace( " \n", "\n", $templates ) ) );
		$templates = $dbr->buildList( $t_arr );
		/*
		$templates = "'" . implode( "','", $t_arr ) . "'";
		$sql = "SELECT cl_to, COUNT(*) AS C
				FROM revision LEFT JOIN page ON rev_page = page_id AND page_namespace = 0 LEFT JOIN categorylinks ON cl_from=page_id
				WHERE rev_user={$wgUser->getID()} AND cl_to IS NOT NULL AND cl_to NOT IN ({$templates})
				GROUP BY cl_to HAVING C > 3 ORDER BY C DESC LIMIT 20;";
		$res = $dbr->query( $sql, __METHOD__ );
		*/
		$res = $dbr->select(
			array( 'revision', 'page', 'categorylinks' ),
			array( 'cl_to', 'COUNT(*) AS C' ),
			array(
				'rev_user' => $wgUser->getId(),
				'cl_to IS NOT NULL',
				"cl_to NOT IN ($templates)"
			),
			__METHOD__,
			array(
				'GROUP BY' => 'cl_to',
				'HAVING' => 'C > 3',
				'ORDER BY' => 'C DESC',
				'LIMIT' => 20
			),
			array(
				'page' => array( 'LEFT JOIN', array( 'rev_page = page_id', 'page_namespace' => 0 ) ),
				'categorylinks' => array( 'LEFT JOIN', 'cl_from = page_id' )
			)
		);

		$wgOut->addHTML(
			'<br />' . wfMsg( 'docentsettings-recommendations' ) . '<br /><br />
			<table width="100%" class="docentsettings">
				<tr>'
		);

		$count = 0;
		foreach ( $res as $row ) {
			$t = Title::makeTitle( NS_CATEGEORY, $row->cl_to );
			$wgOut->addHTML(
				'<td> ' . $this->getCheckbox(
					$t->getText(), $already_subscribed, "({$row->C})"
				) . '</td>'
			);
			if( $count % 2 == 1 ) {
				$wgOut->addHTML( '</tr><tr>' );
			}
			$count++;
		}

		$wgOut->addHTML(
			'</tr></table><br />' .
			wfMsg( 'docentsettings-all-categories' ) . '<br /><br />
			<table width="100%" class="docentsettings">'
		);

		foreach( $cats as $key => $subcat ) {
			if( $key == '' ) {
				continue;
			}
			$float = 'float: left;';
			if( $count % 2 == 0 ) {
				$wgOut->addHTML( '<tr>' );
			}
			$id = strtolower( str_replace( ' ', '_', $key ) );
			$wgOut->addHTML( '<td> ' . $this->getCheckbox( $key, $already_subscribed, '' ) );
			$wgOut->addHTML( '<br /><br />' );
			if( sizeof( $subcat ) > 0 ) {
				$wgOut->addHTML("
					<div style=\"font-size: 80%\">
						<a class=\"docentsettings-showhide-link\" data-cat-id=\"{$id}\">"
							. wfMsg( 'docentsettings-showhide' ) .
						"</a>
					</div>
					<br /><div id=\"subcats_{$id}\" style=\"display: none;\">"
				);
				foreach( $subcat as $s ) {
					$s = substr( $s, 2 );
					$i = 10 + 10 * substr_count( $s, '*' );
					$s = str_replace( '*', '', $s );
					$wgOut->addHTML(
						"<div style=\"padding-left: {$i}px;\" class=\"docentSettingsDiv\">" .
						$this->getCheckbox( $s, $already_subscribed, '' ) . ' <br /></div>'
					);
				}
				$wgOut->addHTML( '</div>' );
			}
			$wgOut->addHTML( '</td>' );
			if( $count % 2 == 1 ) {
				$wgOut->addHTML( '</tr>' );
			}
			$count++;
		}

		$wgOut->addHTML(
			'</table>
			<input type="submit" style="font-weight: bold; font-size: 110%" accesskey="s" value="' . wfMsg( 'submit' ) . '" />
			</form>'
		);
	}

	function getDocentsForCategory( $title ) {
		$html = '';
		$results = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'docentcategories', 'user' ),
			array( 'user_id', 'user_name', 'user_real_name' ),
			array( 'dc_user = user_id', 'dc_to' => $title->getDBKey() ),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$u = new User(); // uh...this looks a bit dodgy to me, given that the convention is to use the newFrom* functions...
			$u->setName( $row->user_name );
			$u->setRealName( $row->user_real_name );
			$results[] = $u;
		}
		$html = '<div id="docents">';
		if ( sizeof( $results ) > 0 ) {
			$html .= '<h2><span id="docent_header">' .
				wfMsg( 'docentsettings-docents' ) .
				"</span></h2><p>\n";
			$first = true;
			foreach( $results as $u ) {
				$display = $u->getRealName() == '' ? $u->getName() : $u->getRealName();
				if ( $first ) {
					$first = false;
				} else {
					$html .= '<strong>&bull;</strong>';
				}
				$html .= ' <a href="' . $u->getUserPage()->getFullURL() . "\">{$display}</a>\n";
			}
			$html .= '</p>';
		} else {
			$html .= '<h2><span id="docent_header">' . wfMsg( 'docentsettings-docents' ) . "</span></h2><p>\n";
			$html .= wfMsg( 'docentsettings-no-docents' );
		}

		$html .= '<div id="become_docent"><span>+</span>' .
			wfMsg( 'docentsettings-become-a-docent', wfMsgForContent( 'docentsettings-help-page' ) ) .
		'</div></div>';
		return $html;
	}
}

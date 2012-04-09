<?php

class MonitorPages extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'MonitorPages' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgUser, $wgRequest, $wgServer, $wgContLang, $wgScriptPath;

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$dbw = wfGetDB( DB_MASTER );
		$me = $this->getTitle();

		// Add CSS
		$wgOut->addModules( 'ext.monitorPages' );

		if( !strlen( $target ) ) {
			if( $wgRequest->getVal( 'deactivate', null ) && $wgUser->isAllowed( 'monitorpages-add' ) ) {
				$t = Title::newFromURL( $wgRequest->getVal( 'deactivate' ) );
				$id = $t->getArticleId();
				$dbw->update(
					'google_monitor',
					array( 'gm_active' => 0 ),
					array( 'gm_page' => $id ),
					__METHOD__
				);
			}

			if( $wgRequest->getVal( 'activate', null ) && $wgUser->isAllowed( 'monitorpages-add' ) ) {
				$t = Title::newFromURL( $wgRequest->getVal( 'activate' ) );
				$id = $t->getArticleId();
				$dbw->update(
					'google_monitor',
					array( 'gm_active' => 1 ),
					array( 'gm_page' => $id ),
					__METHOD__
				);
			}

			if( $wgRequest->wasPosted() && $wgUser->isAllowed( 'monitorpages-add' ) ) {
				$vals = $wgRequest->getVal( 'pages' );
				$vals = str_replace( "\r\n", "\n", $vals );
				$pages = explode( "\n", $vals );
				foreach( $pages as $p ) {
					$p = trim( $p );
					if( $p == '' ) {
						continue;
					}
					$p = str_replace( 'http://www.wikihow.com/', '', $p );
					$p = str_replace( $wgServer . $wgScriptPath . '/', '', $p );
					$t = Title::newFromURL( urldecode( $p ) );
					if( !$t ) {
						$wgOut->addHTML( wfMsg( 'monitorpages-error', $p ) . '<br />' );
						continue;
					}
					$id  = $t->getArticleID();
					$wgOut->addHTML( wfMsg( 'monitorpages-adding', $id ) );
					$dbw->insert(
						'google_monitor',
						array( 'gm_page' => $id ),
						__METHOD__
					);
				}
			}

			$res = $dbw->select(
				array( 'page', 'google_monitor' ),
				array( 'page_namespace', 'page_title' ),
				array( 'page_id = gm_page', 'gm_active' => 1 ),
				__METHOD__
			);

			$wgOut->addHTML( '<h2>' . wfMsgHtml( 'monitorpages-pages-monitored' ) . '</h2><ol>' );
			foreach ( $res as $row ) {
				$t = Title::makeTitle( $row->page_namespace, $row->page_title );
				$dest = SpecialPage::getTitleFor( 'MonitorPages', $t->getText() );
				$wgOut->addHTML(
					'<li>' . Linker::link( $dest, $t->getFullText() ) .
					' - (' . Linker::link(
						$me,
						wfMsgHtml( 'monitorpages-deactivate' ),
						array(),
						array( 'deactivate' => $t->getPrefixedURL() )
					) . ')</li>'
				);
			}
			$wgOut->addHTML( '</ol>' );

			$res = $dbw->select(
				array( 'page', 'google_monitor' ),
				array( 'page_namespace', 'page_title' ),
				array( 'page_id = gm_page', 'gm_active' => 0 ),
				__METHOD__
			);

			$wgOut->addHTML(
				'<h2>' . wfMsgHtml( 'monitorpages-previously-monitored' ) .
				'</h2><ol>'
			);
			foreach ( $res as $row ) {
				$t = Title::makeTitle( $row->page_namespace, $row->page_title );
				$dest = SpecialPage::getTitleFor( 'MonitorPages', $t->getPrefixedURL() );
				$wgOut->addHTML(
					'<li>' . Linker::link( $dest, $t->getFullText() ) .
					' - (' . Linker::link(
						$me,
						wfMsgHtml( 'monitorpages-activate' ),
						array(),
						array( 'activate' => $t->getPrefixedURL() )
					) . ')</li>'
				);
			}
			$wgOut->addHTML( '</ol>' );

			if( $wgUser->isAllowed( 'monitorpages-add' ) ) {
				$wgOut->addHTML(
					wfMsgHtml( 'monitorpages-add-pages' ) . ' <br />
					<form method="post" action="' . $me->getFullURL() . '">
					<textarea name="pages" rows="3" cols="60"></textarea>
					<br /><br />
					<input type="submit" value="' . wfMsgHtml( 'monitorpages-submit' ) . '" />
					</form>'
				);
			}
		} else {
			$t = Title::newFromURL( $target );
			$id = $t->getArticleID();
			$res = $dbw->select(
				'google_monitor_results',
				array( 'gmr_timestamp', 'gmr_position' ),
				array( 'gmr_page' => $id ),
				__METHOD__
			);
			$wgOut->addHTML(
				wfMsg( 'monitorpages-results', Linker::link( $t, $t->getText() ) ) .
				' - (<a href="http://www.google.com/search?q=' . urlencode( wfMsg( 'howto', $t->getText() ) ) . '" target="new">' .
				wfMsgHtml( 'monitorpages-link' ) . '</a>)<br /><br />'
			);
			$wgOut->addHTML( '<ol>' );
			$lastpos = -1;
			foreach ( $res as $row ) {
				$tsToFormat = wfTimestamp( TS_MW, $row->gmr_timestamp );
				$timestamp = $wgContLang->timeanddate( $tsToFormat );
				$class = '';
				if( $lastpos > 0 ) {
					if( $lastpos > $row->gmr_position ) {
						$class = 'monitor_good';
					} elseif( $row->gmr_position > $lastpos ) {
						$class = 'monitor_bad';
					}
				}
				$wgOut->addHTML(
					"<li class=\"$class\">$timestamp - " .
						wfMsg( 'monitorpages-position', $row->gmr_position ) .
					'</li>'
				);
				$lastpos = $row->gmr_position;
			}
			$wgOut->addHTML( '</ol>' );
			$wgOut->addHTML( Linker::link( $me, wfMsgHtml( 'monitorpages-returnto' ) ) );
		}
	}
}

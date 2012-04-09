<?php
/**
 * @file
 * @ingroup SpecialPage
 */
class ActiveEditors extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'ActiveEditors' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgOut, $wgScriptPath, $wgContentNamespaces;

		// Set page title etc.
		$this->setHeaders();

		// Add CSS
		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) ) {
			$wgOut->addModules( 'ext.activeEditors' );
		} else {
			$wgOut->addExtensionStyle( $wgScriptPath . '/extensions/ActiveEditors/ActiveEditors.css' );
		}

		$date = gmdate( 'Ymd' );
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'revision', 'page' ),
			array( 'rev_user', 'rev_user_text', 'COUNT(*) AS C' ),
			array(
				"rev_timestamp LIKE '$date%'",
				'rev_user <> 0',
				'page_namespace' => array( $wgContentNamespaces )
			),
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user',
				'ORDER BY' => 'C DESC',
				'LIMIT' => 5
			),
			array( 'page' => array( 'LEFT JOIN', 'rev_page = page_id' ) )
		);

		// Today's most active editors
		$result = '<div id="active_editors">
		&nbsp;<b>' . wfMsg( 'activeeditors' ) . '</b><br /><br />
		<table width="100%">
			<tr>
				<td><i>' . wfMsg( 'activeeditors-today' ) . '</i></td>
				<td align="right">' . wfMsg( 'activeeditors-numedits' ) . '</td>
			</tr>';

		foreach ( $res as $row ) {
			$u = User::newFromName( $row->rev_user_text );
			$realName = $u->getRealName();
			// If no real name has been set, just use the user's username.
			if ( $realName == '' ) {
				$realName = $row->rev_user_text;
			}
			$result .= '<tr><td>' . Linker::link(
				$u->getUserPage(), $realName
			) . '</td>';
			$result .= '<td align="right">' . $row->C . '</td></tr>';
		}
		$result .= '</table><br />';

		$lastWeek = gmmktime() - 60 * 60 * 24 * 7;
		$monday = $lastWeek;
		for( $i = 0; $i < 7; $i++ ) {
			if ( date( 'N', $monday ) == '1' ) {
				break;
			}
			$monday = $monday -  60 * 60 * 24;
		}

		$nextMonday = $monday + 60 * 60 * 24 * 7;
		$x = date( 'Ymd', $monday );
		$y = date( 'Ymd', $nextMonday );

		$res = $dbr->select(
			array( 'revision', 'page' ),
			array( 'rev_user', 'rev_user_text', 'COUNT(*) AS C' ),
			array(
				"rev_timestamp BETWEEN '{$x}000000' AND '{$y}000000'",
				'rev_user <> 0',
				'page_namespace' => array( $wgContentNamespaces )
			),
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user',
				'ORDER BY' => 'C DESC',
				'LIMIT' => 5
			),
			array( 'page' => array( 'LEFT JOIN', 'rev_page = page_id' ) )
		);

		// Last week's most active editors
		$result .= '<table width="100%">
			<tr>
				<td><i>' . wfMsg( 'activeeditors-lastweek' ) . '</i></td>
				<td align="right">' . wfMsg( 'activeeditors-numedits' ) .
				'</td>
			</tr>';
		foreach ( $res as $row ) {
			$u = User::newFromName( $row->rev_user_text );
			// If the user has supplied a real name, use it.
			// If not, just use their username.
			$realName = $u->getRealName();
			if ( $realName == '' ) {
				$realName = $row->rev_user_text;
			}
			$result .= '<tr><td>' . Linker::link(
				$u->getUserPage(), $realName
			) . '</td>';
			$result .= '<td align="right">' . $row->C . '</td></tr>';
		}
		$result .= '</table>';
		$result .= '</div>';

		$wgOut->addHTML( $result );
	}
}
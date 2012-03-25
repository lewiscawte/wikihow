<?php
/**
 * @file
 */
class ManagePageListHooks {

	/**
	 * Handler for the MediaWiki update script, update.php; this code is
	 * responsible for creating the categorylinkstop table in the database when
	 * the user runs maintenance/update.php.
	 *
	 * @param $updater DatabaseUpdater
	 * @return Boolean: true
	 */
	public static function createPagelistTable( $updater ) {
		$dir = dirname( __FILE__ );

		$updater->addExtensionUpdate( array(
			'addTable', 'pagelist', "$dir/pagelist.sql", true
		) );

		return true;
	}

	public static function updatePageListRisingStar( $t ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'pagelist',
			array( 'pl_page' => $t->getArticleID(), 'pl_list' => 'risingstar' ),
			__METHOD__
		);
		return true;
	}
}
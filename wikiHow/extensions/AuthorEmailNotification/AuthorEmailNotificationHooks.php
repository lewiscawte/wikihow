<?php
/**
 * Hooked functions used by the AuthorEmailNotification extension.
 * All functions are public and static and they all return boolean true.
 *
 * @file
 * @ingroup Extensions
 */

class AuthorEmailNotificationHooks {

	public static function sendModNotification( &$rcid, &$article ) {
		$articleTitle = null;
		if ( $article ) {
			$articleTitle = $article->getTitle();
		}

		try {
			if ( $articleTitle && $articleTitle->getArticleID() != 0 ) {
				$dbw = wfGetDB( DB_MASTER );
				$r = Revision::loadFromPageId( $dbw, $articleTitle->getArticleID() );
				$u = User::newFromId( $r->getUser() );
				AuthorEmailNotification::notifyMod( $article, $u, $r );
			}
		} catch ( Exception $e ) {
		}

		return true;
	}

	public static function attributeAnon( $user ) {
		try {
			if ( isset( $_COOKIE['aen_anon_newarticleid'] ) ) {
				$aid = $_COOKIE['aen_anon_newarticleid'];
				AuthorEmailNotification::reassignArticleAnon( $aid );
				$user->incEditCount();
				if ( $user->getEmail() != '' ) {
					AuthorEmailNotification::addUserWatch( $aid, 1 );
				}
			}
		} catch ( Exception $e ) {
		}

		return true;
	}

	public static function setUserTalkOption() {
		global $wgUser;

		try {
			$wgUser->setOption( 'usertalknotifications', 0 );
			$wgUser->saveSettings();
		} catch ( Exception $e ) {
		}

		return true;
	}

	public static function addFirstEdit( $article, $details ) {
		global $wgUser;

		try {
			$t = $article->getTitle();
			if ( !$t || $t->getNamespace() != NS_MAIN ) {
				return true;
			}
			$dbr = wfGetDB( DB_MASTER );
			$numRevisions = $dbr->selectField(
				'revision',
				'COUNT(*)',
				array( 'rev_page' => $article->getId() ),
				__METHOD__
			);
			if ( $numRevisions > 1 ) {
				return true;
			}
			$user_name = $dbr->selectField(
				'revision',
				'rev_user_text',
				array( 'rev_page' => $article->getId() ),
				__METHOD__
			);

			if (
				(
					strpos( $_SERVER['HTTP_REFERER'], 'action=edit' ) !== false ||
					strpos( $_SERVER['HTTP_REFERER'], 'action=submit2' )
				) && $wgUser->getName() == $user_name
			)
			{
				$dbw = wfGetDB( DB_MASTER );
				$sql = "INSERT IGNORE INTO {$dbw->tableName( 'firstedit' )} SELECT rev_page, rev_user, rev_user_text, MIN(rev_timestamp) FROM {$dbw->tableName( 'page' )}, {$dbw->tableName( 'revision' )} WHERE page_id = rev_page AND page_namespace = 0 AND page_is_redirect = 0 AND page_id = " .
					$article->getId() . ' GROUP BY rev_page';
				$ret = $dbw->query( $sql, __METHOD__ );
			}
		} catch ( Exception $e ) {
		}

		return true;
	}

	/**
	 * Handler for the MediaWiki update script, update.php; this code is
	 * responsible for creating the email_notifications table in the database
	 * when the user runs maintenance/update.php.
	 *
	 * @param $updater DatabaseUpdater
	 * @return Boolean: true
	 */
	public static function createTable( $updater ) {
		$dir = dirname( __FILE__ );

		$updater->addExtensionUpdate( array(
			'addTable', 'email_notifications', "$dir/email_notifications.sql", true
		) );

		return true;
	}
}
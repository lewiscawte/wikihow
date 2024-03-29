<?php
/**
 * ConfigStorage class (and associated Special:AdminConfigEditor page) exist
 * to edit and store large configuration blobs (such as lists of 1000+ URLs)
 * because we've found that MediaWiki messages are not optimal for this task.
 * But it's important that they're non-engineer editable, so we provide an
 * admin interface to edit them.
 */

class ConfigStorage {

	const MAX_KEY_LENGTH = 64;

	/**
	 * List all current config keys.
	 */
	public static function dbListConfigKeys() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'config_storage', 'cs_key', '', __METHOD__ );
		$keys = array();
		while ( $row = $res->fetchRow() ) {
			$keys[] = $row['cs_key'];
		}
		$res->free();
		return $keys;
	}

	/**
	 * Pulls the config for a given key from either memcache (if it's there)
	 * or the database.
	 */
	public static function dbGetConfig( $key ) {
		global $wgMemc;

		$cacheKey = self::getMemcKey( $key );
		$res = $wgMemc->get( $cacheKey );
		if ( $res === null ) {
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->selectField(
				'config_storage',
				'cs_config',
				array( 'cs_key' => $key ),
				__METHOD__
			);

			if ( $res ) {
				$wgMemc->set( $cacheKey, $res );
			}
		}

		return $res;
	}

	/**
	 * Set the new config key in the database (along with the config value).
	 * Clear the memcache key too.
	 */
	public static function dbStoreConfig( $key, $config ) {
		global $wgMemc;

		$cacheKey = self::getMemcKey( $key );
		$wgMemc->delete( $cacheKey );

		$dbw = wfGetDB( DB_SLAVE );
		$dbw->replace(
			'config_storage',
			'cs_key',
			array(
				array(
					'cs_key' => $key,
					'cs_config' => $config
				)
			),
			__METHOD__
		);
	}

	// consistently generate a memcache key
	private static function getMemcKey( $key ) {
		return wfMemcKey( 'cfg', $key );
	}
}
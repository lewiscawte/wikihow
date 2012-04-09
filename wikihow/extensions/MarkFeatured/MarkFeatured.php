<?php
/**
 * MarkFeatured extension -- marks pages as featured depending on RSS feed
 * article contents in page table.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'MarkFeatured',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Marks pages as featured depending on RSS-feed article contents in page table',
);

$wgHooks['ArticleSaveComplete'][] = 'wfMarkFeaturedSaved';

/**
 * When an article is saved, this hook is called to save whether or not
 * the each article is should have the page_is_featured flag set in the
 * database.
 *
 * To set the page_is_featured flag for all pages that have {{Fa}} in them, run
 * this SQL query against the database:
 * UPDATE page SET page_is_featured=1 WHERE page_id IN (SELECT tl_from FROM templatelinks WHERE tl_title='Fa');
 */
function wfMarkFeaturedSaved( &$article, &$user, $text, $summary, $minoredit,
	$watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId, &$redirect
) {
	$t = $article->getTitle();

	// If we don't have a valid Title or we're not editing MediaWiki:RSS-feed,
	// bail out.
	if ( $t == null || $t->getNamespace() != NS_PROJECT || $t->getDBKey() != 'RSS-feed' ) {
		return true;
	}

	$dbw = wfGetDB( DB_MASTER );

	// reuben - i removed this for now to fix an urgent issue for krystle where
	// old FAs don't show up in categories.  the more involved fix is to remove
	// this function wfMarkFeaturedSaved() and just check if an article has
	// the {{fa}}, set the page_is_featured flag for that one article.  With
	// this 'fix', if an article ever shows up in the RSS feed, it will be
	// considered an FA in the DB forever.

	// clear everything from before
	//$success = $dbw->update( 'page', array( /* SET */ 'page_is_featured' => 0 ), array(), __METHOD__ );

	$lines = explode( "\n", $text );
	foreach ( $lines as $line ) {
		if ( ( strpos( $line, 'http://' ) === 0 ) && ( strpos( $line, 'http://blog.wikihow.com' ) == false ) ) {
			$tokens = explode( ' ', $line );
			$t = $tokens[0];
			$t = str_replace( 'http://www.wikihow.com/', '', $t );

			$url = str_replace( $wgServer . $wgScriptPath . '/', '', $t );
			$x = Title::newFromURL( urldecode( $url ) );
			if ( !$x ) {
				continue;
			}

			$ret = $dbw->update(
				'page',
				/* SET */array( 'page_is_featured' => 1 ),
				/* WHERE */array(
					'page_namespace' => $x->getNamespace(),
					'page_title' => $x->getDBKey()
				),
				__METHOD__
			);
		}
	}

	return true;
}
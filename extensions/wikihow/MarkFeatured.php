<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
/**#@+
 */
 
$wgExtensionFunctions[] = 'wfMarkFeaturedHooks';

function wfMarkFeaturedHooks () {
	global $wgHooks;
	$wgHooks['ArticleSaveComplete'][] = array("wfMarkFeaturedSaved");
}

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'MarkFeatured',
	'author' => 'Travis Derouin',
	'description' => 'Marks pages as featured depending on RSS-feed article contents in page table. Run MySQL "alter table page add column page_is_is_featured tinyint(1) unsigned NOT NULL default \'0\';" before installing....',
);



/*
 * When an article is saved, this hook is called to save whether or not
 * the each article is should have the page_is_featured flag set in the 
 * database.
 *
 * update page set page_is_featured=1 where page_id in (select tl_from from templatelinks where tl_title='Fa');
 */
function wfMarkFeaturedSaved(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor) {
	$t = $article->getTitle();


	if ($t == null || $t->getNamespace() != NS_PROJECT || $t->getDBKey() != "RSS-feed") 
		return true;

	$dbw =& wfGetDB( DB_MASTER );

	// reuben - i removed this for now to fix an urgent issue for krystle where
	// old FAs don't show up in categories.  the more involved fix is to remove
	// this function wfMarkFeaturedSaved() and just check if an article has
	// the {{fa}}, set the page_is_featured flag for that one article.  With
	// this 'fix', if an article ever shows up in the RSS feed, it will be
	// considered an FA in the DB forever.

	// clear everything from before
    //$success = $dbw->update( 'page',  array( /* SET */ 'page_is_featured' => 0) , array('1' => '1'));
	
	$lines = split("\n", $text);
	foreach ($lines as $line) {
		if ((strpos($line, "http://") === 0) and (strpos($line, "http://blog.wikihow.com") == false)) {
			$tokens = split (" ", $line);
			$t = $tokens[0];
			$t = str_replace("http://www.wikihow.com/", "", $t);
        	$url = str_replace($wgServer . $wgScriptPath . "/", "", $t);
        	$x = Title::newFromURL(urldecode($url));
			if (!$x) continue;
			$ret = $dbw->update( 'page',  array( /* SET */ 'page_is_featured' => 1), 
					array (/* WHERE */ 'page_namespace' => $x->getNamespace(), 'page_title' => $x->getDBKey() ) );
		}
	}
	return true;
}
	

?>

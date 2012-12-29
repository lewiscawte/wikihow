<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
/**#@+
 */
 
$wgExtensionFunctions[] = 'wfMarkFeaturedHooks';

function wfMarkFeaturedHooks () {
	global $wgHooks;
	$wgHooks['ArticleSaveComplete'][] = array("wfMarkFeaturedSaved");
	$wgHooks['ArticleSave'][] = array("wfBlockIdiot");
}

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'MarkFeatured',
	'author' => 'Travis Derouin',
	'description' => 'Marks pages as featured depending on RSS-feed article contents in page table. Run MySQL "alter table page add column page_is_is_featured tinyint(1) unsigned NOT NULL default \'0\';" before installing....',
);


function wfBlockIdiot (&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor) {
	global $wgOut;
	if ($text != null && stripos($text, "http://www.aadas.com/") !== false) {
		$wgOut->errorpage("no.", "no.");
		return false;
	} else {
		return true;
	}
}

function wfMarkFeaturedSaved (&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor) {
	$t = $article->getTitle();


	if ($t == null || $t->getNamespace() != NS_PROJECT || $t->getDBKey() != "RSS-feed") 
		return true;

	$dbw =& wfGetDB( DB_MASTER );
	// clear everything from before
    $success = $dbw->update( 'page',  array( /* SET */ 'page_is_featured' => 0) , array('1' => '1'));
	
	$lines = split("\n", $text);
	foreach ($lines as $line) {
		if (strpos($line, "http://") === 0) {
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

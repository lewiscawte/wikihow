<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:TopCategoryHooks-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'TopCategoryHooks',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Maintain a table of links from top level categories to articles through a Hook', 
);
$wgHooks['LinksUpdate'][] = array("wfUpdateTopLevelCatTable");

function wfFlattenTopLevelCats($arg, &$results= array()) {
    if (is_array($arg)) {
        foreach ($arg as $a=>$p) {
            if (is_array($p) && sizeof($p) > 0) {
                 wfFlattenTopLevelCats($p, $results);
             } else {
                $results[] = $a;
			}
        }
    }
   return $results;
}

function wfGetTopLevelCats() {
	global $wgMemc;
	$key = "toplevelcats_categorylinkstop";
	$val = $wgMemc->get($key);
	if ($val)
		return $val;
   //initialize the top level array of categories;
    $x = Categoryhelper::getTopLevelCategoriesForDropDown();
    $top = array();
    foreach ($x as $cat) {
        $cat = trim($cat);
        if ($cat == "" || $cat == "Other" || $cat == "WikiHow")
            continue;
        $top[] = $cat;
    }
	$wgMemc->set($key, $top, 86400);
	return $top; 
}

function wfUpdateTopLevelCatTable($linker) {
	// LinksUpdate does not do a lazy update, so neither do we!
		$dbw = wfGetDB(DB_MASTER);		
		$title = $linker->mTitle;
       	$tree = $title->getParentCategoryTree();
        $mine = array_unique(wfFlattenTopLevelCats($tree));
        $dbw->delete('categorylinkstop', array('cl_from'=>$title->getArticleID()));
		$top = wfGetTopLevelCats();
        foreach ($mine as $m) {
            $y = Title::makeTitle(NS_CATEGORY, str_replace("Category:", "", $m));
            if (in_array($y->getText(), $top)) {
                $dbw->insert('categorylinkstop', array('cl_from'=>$title->getArticleID(), 'cl_to'=>$y->getDBKey(), 'cl_sortkey'=>$title->getText()));
            }
        }

	return true;
}

function wfGetTopLevelCategories ($title) {
	$result = array(); 
	$dbr = wfGetDB(DB_SLAVE);
	if (!$title) return $result;
	$res = $dbr->select('categorylinkstop', array('cl_to'), array('cl_from'=>$title->getArticleID()));
	while ($row = $dbr->fetchObject($res)) {
		$results[] = Title::makeTitle(NS_CATEGORY, $row->cl_to);
	}
	return $results;
}

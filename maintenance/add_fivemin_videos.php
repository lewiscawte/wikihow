<?
	require_once("commandLine.inc");

    function getTopCategory($title = null) {
        global $wgTitle;
        if (!$title)
            $title = $wgTitle;
        $parenttree = $title->getParentCategoryTree();
        $parenttree_tier1 = $parenttree;

        $result = null;
        while ((!$result || $result == "WikiHow") && is_array($parenttree)) {
            $a = array_shift($parenttree);
            if (!$a) {
                $keys = array_keys($parenttree_tier1);
                $result = str_replace("Category:", "", $keys[0]);
                break;
            }
            $last = $a;
            while (sizeof($a) > 0 && $a = array_shift($a) ) {
                $last = $a;
            }
            $keys = array_keys($last);
            $result = str_replace("Category:", "", $keys[0]);
        }
        return $result;
    }

$dbr = wfGetDB(DB_MASTER);
$debug = 0;
$sql= " select page_title, page_namespace from page
            where page_namespace=0 and page_is_redirect=0 
            and page_id not in (5, 5791) order by page_counter desc LIMIT 2000";

$dbr->query("delete from video_links where vl_source='fivemin';");

$res = $dbr->query($sql);
$source = "fivemin";


#echo "starting...\n";
$cats = array("Health", "Food-and-Entertaining", "Home-and-Garden");
$counts = array("Health" => 0, "Food-and-Entertaining"=>0,"Home-and-Garden"=>0);
$total = 0;

$skip = 0;

while (($row = $dbr->fetchObject($res)) && $total < 450) {
	$t = Title::makeTitle($row->page_namespace, $row->page_title);
	if (!is_array($t->getParentCategoryTree())) {
		continue;
	}
	$top = getTopCategory($t);
	if (in_array($top, $cats) && $counts[$top] < 150) {
		#echo "checking {$t->getFullText()}, in the right category\n";
  		$r = new RelatedVideos($t, 'wonderhowto');
  		if ($r->hasResults()) {
			#echo "\tskippping {$t->getFullText()}, already has results\n";
            continue;
  		}
		#echo "\tstarting api check...\n";
		$api = new FiveMinApi($t);
		$api->execute($t);
		$api->storeResults(true, false);
		$counts[$top]++;
		$num = sizeof($api->mResults);
		#echo "\tfinished api check, checked {$count} total articles, added $num videos to {$t->getFullURL()}\n";
		echo "{$t->getFullURL()}\n";
		$count++;
	} else {
		$skip++;
		if ($skip % 100 == 0) {
			#echo "skipped $skip articles\n";
		}
	}
}

print_r($counts);

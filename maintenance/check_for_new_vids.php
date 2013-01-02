<?
    require_once("commandLine.inc");

$t1 = time();
$count = 0;
$dbr = wfGetDB(DB_SLAVE);
$debug = 0;
$limit = isset($argv[1]) ? $argv[1] : "200";
$sql= " select page_title, page_namespace, page_id from page
            where page_namespace=0 and page_is_redirect=0 
            and page_id not in (5, 5791) order by page_counter desc limit 100,{$limit}
     ";

$ids = array();
$titles = array();
$res = $dbr->query($sql);
while ($row = $dbr->fetchObject($res)) {
	$ids[] = $row->page_id;
	$count = $dbr->selectField('video_links', 'count(*)', array('vl_page'=>$row->page_id));
	if ($count > 0)
		$titles[] = $row->page_title;
}

print_r($titles); exit;
$urls = array();

foreach ($ids as $id) {
	$sql = "select vl_id, vl_title from video_links where vl_page={$id}";
	$res = $dbr->query($sql);
	while ($row = $dbr->fetchObject($res)) {
		$t  = urlencode(strtolower(str_replace(" ", "-", trim($row->vl_title))));
        $urls[] = "http://www.wikihow.com//video/wht/{$row->vl_id}/how-to-{$t}";	
	}
}

foreach ($urls as $url) {
	echo $url . "\n";
}

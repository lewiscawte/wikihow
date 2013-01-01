<?

	require_once('commandLine.inc');
	for ($batch = 0; $batch < 10; $batch++) {
		$dbr = wfGetDB(DB_SLAVE);
		// GET THE LIST OF TITLES
		#$batch = isset($argv[0]) ? $argv[0] : 0;
		$opts = array("ORDER BY" => "page_id", "LIMIT" => 10000, "OFFSET" => ($batch * 10 *1000));
		if ($batch =="-")
			$opts = array(); 
	
		$res = $dbr->select('page', array('page_namespace', 'page_title'), 
			array('page_namespace'=>NS_MAIN, 'page_is_redirect'=>0
	//			,'page_title'=>'Have-an-Awesome-Overseas-Class-Trip'
				),
			"init_toplevelcategories.php",
			$opts
			);
		$count = 0;
		$updates = array();
		$titles = array();
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if (!$t) {
				continue;
			}
			$titles[] = $t;
		}
	
		// FIGURE OUT WHAT THE CATINFO COLUMN IS SUPPOSED TO BE
		echo "Got titles\n";
		foreach ($titles as $t) {
			$val = $t->getCategoryMask();
			$count++;
			if ($count % 1000 == 0)  {
				echo "Done $count\n";
			}
			$updates[] = "UPDATE page set page_catinfo = $val where page_id={$t->getArticleID()};";
			#echo "{$t->getFullText()} would be $val\n ";
		}
	
		// DO THE UPDATES
		echo "doing " . sizeof($updates) . "\n";
		$count = 0;
		$dbw = wfGetDB(DB_MASTER);
		foreach ($updates as $u ) {
			$dbw = wfGetDB(DB_MASTER);
			#echo $u . "\n";
			$dbw->query($u);
	        $count++;
	        if ($count % 1000 == 0)  {
	            echo "Done $count\n";
	        }
		}
	}	

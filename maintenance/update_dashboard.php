<?
	require_once('commandLine.inc');

	$tdstyle = ' style="vertical-align: top; padding-bottom:   20px; border: 1px solid #eee; background: #CFDDDD;" ';
	$wgTitle = Title::newFromText("Main Page");
	function selectField($dbw, $sql) {
		#wfDebug("Dashboard: $sql\n");
		$res = $dbw->query($sql);
		if ($row = $dbw->fetchObject($res)) 
            foreach($row as $k=>$v)
            return $v;
		return null;
	}
	function getNumCreatedThenDeleted($dbw, $cutoff, $cutoff2 = null) {
		if ($cutoff2)
			$sql = "select ar_title, ar_page_id, min(ar_timestamp) as M from archive where ar_namespace=0 group by ar_page_id having M >= '$cutoff' AND M < '{$cutoff2}'";
		else
			$sql = "select ar_title, ar_page_id, min(ar_timestamp) as M from archive where ar_namespace=0 group by ar_page_id having M >= '$cutoff'";
		wfDebug("Dashboard: $sql\n");
		$res = $dbw->query($sql);
		return $dbw->numRows($res);
	}

	function getUserToolLinks($u) {
		global $wgUser;
		$ret = $wgUser->getSkin()->userToolLinks($u->getID(), $u->getName());
		$ret = str_replace('href="/', 'href="http://www.wikihow.com/', $ret);
		return $ret;
	}

	function getDateStr($ts) {
		return date("Y-m-d", wfTimestamp(TS_UNIX, $ts));
	}

	function getWikiBirthdays($start, $w_cutoff, $dbw) {
		$result = "";
		$thisyear = date("Y") - 1; 
		$ago = 1;
		while ($thisyear > 2004) {
			$ts1 = preg_replace("@^20[0-9]{2}@", $thisyear, $w_cutoff); 
			$ts2 = preg_replace("@^20[0-9]{2}@", $thisyear, $start); 
			$result .= "Between " . getDateStr($ts1) . " and " . getDateStr($ts2) . " <ol>";
			$res = $dbw->select('user', array('user_name', 'user_registration'),
				 array("user_registration > '$ts1'", "user_registration <= '$ts2'", "user_editcount >= 200"));
			while ($row = $dbw->fetchObject($res)) {
				$x = User::newFromName($row->user_name);
				$result .= "<li> " . getUserLink($x) . " (" . getDateStr($row->user_registration) . ") - " . getUserToolLinks($x) . "</li>";
			}
			$result .= "</ol>";
			$thisyear--;
		}
		return $result;
	}


	function newlyActiveUsers($cutoff, $start, $dbw, $tdstyle, $editcount, $period) {
		$result = "<ol>";
		$sql = "select rev_user, rev_user_text, max(rev_timestamp) as M, count(*) as C from rev_tmp group by rev_user having C >= $editcount and M >'{$cutoff}' and M <'{$start}';";
		wfDebug("Dashboard new editors who became active this month/week: $sql\n");
		echo "<!-- $sql --->";
		$res = $dbw->query($sql);
		while ($row = $dbw->fetchObject($res)) {
			$count = selectField($dbw, 'select count(*) from rev_tmp where rev_user=' . $row->rev_user . ' and rev_timestamp < "' . $cutoff . '";');
			if ($count < $editcount ) {
				$x = User::newFromName($row->rev_user_text);
				$c = nf($row->C);
				$result .= "<li> " . getUserLink($x) . " - {$c} total edits, {$count} before this $period " . getUserToolLinks($x) . "</li>\n";
			}
		}
		$result .= "</ol>";
		$result .= "</td><td {$tdstyle}>";
		return $result;
	}
	
	function articleStats($dbw, $cutoff, $cutoff2 = null) {

		global $wgBotIds;
		$notbot = " NOT IN (" . implode($wgBotIds) . ")"; 

		$result = "";
		$result .= "\n<ul><li>Articles that have been deleted : "  . 
			nf($dbw->selectField('logging', array('count(*)'), 
			array('log_type' => 'delete', 
				  "log_timestamp > '{$cutoff}'", 
				  $cutoff2 ? "log_timestamp < '{$cutoff2}'" : "1=1",
				  'log_namespace' => 0
				)));

		$d = getNumCreatedThenDeleted($dbw, $cutoff, $cutoff2);
      	$result .= "\n</li><li> Articles that have been created : "  . 
            nf($dbw->selectField('newarticlepatrol', array('count(*)'),
            array(
                  "nap_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "nap_timestamp < '{$cutoff2}'" : "1=1",
				 
                )) + $d);

        $result .= "- (" . nf($d) . " deleted) \n</li><li> Videos that have been embedded: "  .
            nf($dbw->selectField(array('revision', 'page'),
			array('count(*)'),
            array(
                  "rev_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "rev_timestamp < '{$cutoff2}'" : "1=1",
				 "page_id = rev_page",
					'page_namespace' => NS_VIDEO
                )));

        $result .= "\n</li><li> Photos uploaded: "  . 
            nf($dbw->selectField('logging', array('count(*)'),
            array('log_type' => 'upload',
                  "log_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "log_timestamp < '{$cutoff2}'" : "1=1",
                )));

        $result .= "\n</li><li>Main namespace edits : "  . 
           nf($dbw->selectField(array('revision', 'page'),
            array('count(*)'),
            array(
                  "rev_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "rev_timestamp < '{$cutoff2}'" : "1=1",
                 "page_id = rev_page",
                   'page_namespace' => NS_MAIN,
					'rev_user ' . $notbot,
                )));

        $result .= "\n</li><li> User talk namespace edits : "  .         
           nf($dbw->selectField(array('revision', 'page'),
            array('count(*)'),
            array(
                  "rev_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "rev_timestamp < '{$cutoff2}'" : "1=1",
                 "page_id = rev_page",
                    'page_namespace' => NS_USER_TALK,
					'rev_user ' . $notbot,
                )));

        $result .= "\n</li><li> Reverted main namespace edits : "  .         
           nf($dbw->selectField(array('revision', 'page'),
            array('count(*)'),
            array(
                  "rev_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "rev_timestamp < '{$cutoff2}'" : "1=1",
                 "page_id = rev_page",
                  'page_namespace' => NS_MAIN,
				 "rev_comment like 'Reverted%'"
                )));

        $result .= "\n</li><li> User registrations : "  .
           nf($dbw->selectField(array('user'),
            array('count(*)'),
            array(
                  "user_registration> '{$cutoff}'",
				  $cutoff2 ?  "user_registration < '{$cutoff2}'" : "1=1",
                 "user_name NOT like 'Anonymous%'"
                )));

		$result .= "</ul>";	
		return $result;	
	}

    function getActivityChange ($dbw, $c1, $c2, $decline) {
        // how many edits in previous period?
        $sql = "SELECT rev_user, rev_user_text, count(*) as C from rev_tmp 
            WHERE rev_timestamp < '{$c1}' and rev_timestamp > '{$c2}' group by rev_user having C >= 100;";
        #echo $sql . "\n";
		wfDebug("Dashboard activity change: $sql\n");
        $res = $dbw->query($sql);
        while ($row = $dbw->fetchObject($res)) {
            // how many edits in current period? 
            $add = false;
			$old = $row->C;
            $new  = selectField($dbw, 'select count(*) from rev_tmp where rev_user=' . $row->rev_user . ' and rev_timestamp > "' . $c1 . '";');
            if ($decline) {
                if ($new == 0 || $new / $old <= 0.5)
                    $add = true;
            } else {
                if ($new > 0 && $new / $old >= 1.5)
                    $add = true;
            }
            if ($add) {
                $x = User::newFromName($row->rev_user_text);
                $new = nf($new);
                $old = nf($old);
                $result .= "<li> " . getUserLink($x) . " - {$old} &rarr; {$new} " . getUserToolLinks($x) . "</li>\n";
    /*
                if ($decline) {
                    echo "Decling {$x->getName()}, old $old new $new\n";
                } else {
                    echo "Increase {$x->getName()}, old $old new $new\n";
                }
*/
            }
        }
        return $result;
    }

	function debugMsg($msg) {
		echo "\n\n<!--" . date("r") . ": ". $msg . "--->\n";
	}

	function getTopCreators($dbw, $cutoff, $start) {
		$result = "<ol>";
	    $sql = "select nap_page from newarticlepatrol left join page on nap_page = page_id where page_namespace = 0 
				and nap_timestamp > '{$cutoff}' and nap_timestamp < '{$start}'; ";
		wfDebug("Dashboard top creators: $sql\n");

		debugMsg("getting nap $nap ");
	    $res = $dbw->query($sql);
		$pages = array();
		$revisions = array();
	    while ($row = $dbw->fetchObject($res)) {
			$pages[] = $row->nap_page;
	    }
		debugMsg("getting min revisions on pages " .sizeof($pages) . " pages ");
		$count = 0;
		foreach ($pages as $page_id) {
			$revisions[$page_id] = $dbw->selectField("rev_tmp", array("min(rev_id)"), array("rev_page"=>$page_id));
			$count++;
			if ($count % 100 == 0) {
				debugMsg("done $count");
			}
		}
		$users = array();
		debugMsg("getting users on newly created pages " .sizeof($revisions) . " revisions ");
		$count = 0;
		foreach ($revisions as $page_id => $rev_id) {
			if (empty($rev_id)) {
				#echo "<!---uh oh: {$page_id} has no min rev!-->";
				continue;
			}
			$u = selectField($dbw, "select rev_user_text from rev_tmp where rev_id={$rev_id}");
			if(!isset($users[$u])) {
				$users[$u] = 1;
			} else {
				$users[$u]++;
			}
			$count++;
			if ($count % 100 == 0) {
				debugMsg("done $count");
			}
		}
		debugMsg("sorting " .sizeof($users) . " users");
        asort($users, SORT_NUMERIC);
        $users = array_reverse($users);
        array_splice($users, 20);
		$yy = 0;
		debugMsg("outputting all of this now " .sizeof($users) . " users");
		foreach ($users as $u=>$c) {
			$x = User::newFromName($u);
			$c = nf($c);
			if (!$x) $result .= "<li>{$u} - {$c} new articles created</li>\n";
			else $result .= "<li> " . getUserLink($x) . " - {$c} new articles created".  getUserToolLinks($x) . "</li>\n";
			$yy++;
			if ($yy == 20) break;
		}
		$result .= "</ol>";
		return $result;
	}

	function nf($c) {
		return number_format($c, 0, ".", ",");
	}

	function getUserLink($x) {
		if (!$x) return "no user page";
		return "<a href='http://www.wikihow.com/{$x->getUserPage()->getPrefixedUrl()}'>{$x->getName()}</a>";
	}


	/****
	 *
	 * Main execution area
	 *
	 */
	$dbw = wfGetDB(DB_MASTER);

	// get the cutoff dates which we are going to run the report for 
	$start = time();
	if (isset($argv[0])) {
		$start = wfTimestamp(TS_UNIX, $argv[0] . "000000");
	}
	$w_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 7); // 7 days
	$ww_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 14); // 14 days
	$m_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30); // 30 days
	$mm_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 60); // 3days
	$start 		= wfTimestamp(TS_MW, $start); // conver it over to a ts_mw
	$now  		= wfTimestampNow();

	// a list of bots, because we want to exclude them from the report
	$wgBotIds = array(); 
	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('user_groups', array('ug_user'), array('ug_group'=>'Bot'));
	while ($row = $dbr->fetchObject($res)) {
		$wgBotIds[] = $row->ug_user;
	}

	$d = getNumCreatedThenDeleted($dbw, $w_cutoff, $start);
	echo '<body id="body" style="font-family: Arial;">';
	
	echo "\n\n<!-- " . date("r") . " starting ... --->\n";

	// create a temporary table with just the main namespace edits from logged in users
	// it'll make it quicker to do lookups than to do a lookup on the whole revision table
	if ($wgServer != "http://wiki112.wikidiy.com") {
		$dbw->query("
		create temporary table rev_tmp (    
			rev_id int(8) unsigned NOT NULL,    
			`rev_page` int(8) unsigned NOT NULL default '0',     
			`rev_user` int(5) unsigned NOT NULL default '0',     
			`rev_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',     
			`rev_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
		    KEY `rev_id` (`rev_id`), 
		    KEY `rev_page` (`rev_page`), 
     		KEY `rev_timestamp` (`rev_timestamp`),
    		KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
    		KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

		$dbw->query("insert into rev_tmp 
    			select rev_id, rev_page, rev_user, rev_user_text, rev_timestamp from
        		revision, page where page_id=rev_page and page_namespace=0 and rev_user > 0 
				and rev_user NOT IN (" . implode(",", $wgBotIds) . ");
			");
	}

	#$rowCount = $dbw->selectField(array('rev_tmp'), array('count(*)'), array());
	# echo "rev_tmp has $rowCount rows.\n";

	echo "\n\n<!-- " . date("r") . " user stats ... --->\n";
	// get the group of "very active users", they are the ones with 500+ edits
	$users = array();
	wfDebug("Dashboard getting users with > 500 edits: $sql\n");
	$res = $dbw->query("select rev_user, count(*) as C from rev_tmp group by rev_user having C > 500 order by C desc;");
	while ($row = $dbw->fetchObject($res)) {
		$users[$row->rev_user] = $row->C;
	}

	echo "<h1> User stats </h1>\n";
	echo "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle} colspan='2'>";
	echo "<h2>Very active users who have 10+ edits in the past week </h2><ol>" ;
	foreach ($users as $u => $c) {
    	$count = selectField($dbw, 'select count(*) from rev_tmp where rev_timestamp > "' . $w_cutoff . '" and rev_user=' . $u);
		if ($count >= 10) {
			$x = User::newFromID($u);
			$count = nf($count);
			echo "<li>" . getUserLink($x) . " - " .nf($c) . " - {$count} " . getUserToolLinks($x) . "</li>\n";
		}
	}
	echo "</ol></td></tr><tr><td {$tdstyle}>";

    echo "<h2>Users who have 100+ edits in the past month </h2><ol>" ;
	$sql = "select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > '{$m_cutoff} ' and rev_timestamp <  '{$start}' group by rev_user_text having C >= 100 order by C desc;";
	#echo $sql . "\n";
	wfDebug("Dashboard 100+ edits in past month: $sql\n");
	$res = $dbw->query($sql);
	while ($row = $dbw->fetchObject($res)) {
    	$x = User::newFromName($row->rev_user_text);
        echo "<li> " . getUserLink($x) . " - ". nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	echo "</ol></td><td {$tdstyle}>";

    echo "<h2>Users who have 25+ edits in the past week</h2><ol>" ;
	$sql = "select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > '{$w_cutoff}' and rev_timestamp < '{$start}' group by rev_user_text having C >= 25 order by C desc;";
	#echo $sql . "\n";
	wfDebug("Dashboard 25+ edits in past week: $sql\n");
	$res = $dbw->query($sql);
	while ($row = $dbw->fetchObject($res)) {
    	$x = User::newFromName($row->rev_user_text);
        echo "<li> " . getUserLink($x) . " - " . nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	echo "</ol></td></tr><tr><td {$tdstyle}>";

    echo "<h2> Top 100 editors for the past month </h2><ol>" ;
    $sql =  "select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > '$m_cutoff' and rev_timestamp < '$start' group by rev_user_text order by C desc LIMIT 100;";
	#echo $sql . "\n";
	wfDebug("Dashboard top 100 editors in past month: $sql\n");
	$res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
        $x = User::newFromName($row->rev_user_text);
        echo "<li> " . getUserLink($x) . " - " . nf($row->C) . getUserToolLinks($x). "</li>\n";
    }
	echo "</ol></td><td {$tdstyle}>";

    echo "<h2> Top 50 editors for the past week </h2><ol>" ;
    $sql = "select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > '{$w_cutoff}' group by rev_user_text order by C desc LIMIT 50;";
	#echo $sql . "\n";
	wfDebug("Dashboard top 50 editors in past week: $sql\n");
	$res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
        $x = User::newFromName($row->rev_user_text);
        echo "<li> " . getUserLink($x) . " - ".  nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	echo "</ol></td><tr></tr><td {$tdstyle}>";

	/*******
	 *
	 *  Changes in activity level
	 *
	 */
	// who had 100+ edits 2 months ago?
	echo "\n\n<!-- " . date("r") . " changes in activity levels --->\n";
    echo "<h2> Editors with a declining activity level this month</h2>" ;
	wfDebug("Dashboard decling activty this month: ");
	$res = $dbw->query($sql);
	echo getActivityChange ($dbw, $m_cutoff, $mm_cutoff, true);
	echo "</td><td {$tdstyle}>";

    echo "<h2> Editors with a declining activity level this week</h2>" ;
	wfDebug("Dashboard decling activty this week: ");
	echo getActivityChange ($dbw, $w_cutoff, $ww_cutoff, true);
	echo "</td><tr></tr><td {$tdstyle}>";
    
	echo "<h2> Editors with a increasing activity level this month</h2>50% more activity than last month\n" ;
	wfDebug("Dashboard decling activty this increasing activity level this month: ");
	echo getActivityChange ($dbw, $m_cutoff, $mm_cutoff, false);
	echo "</td><td {$tdstyle}>";

    echo "<h2> Editors with a increasing activity level this week</h2>50% more activity than last week\n" ;
	wfDebug("Dashboard decling activty this increasing activity level this week: ");
	echo getActivityChange ($dbw, $w_cutoff, $ww_cutoff, false);
	echo "</td><tr></tr><td {$tdstyle}>";
	
	/*******
	 *
	 *  Users becoming active
	 *
	 */
	echo "\n\n<!-- " . date("r") . " users becoming active --->\n";
    echo "<h2> New editors who became active this month </h2>Users who made their 25th edit this month\n" ;
	echo newlyActiveUsers($m_cutoff, $start, $dbw, $tdstyle, 25, "month") ;

    echo "<h2> New editors who became active this week </h2>Users who made their 10th edit this week\n" ;
	echo newlyActiveUsers($w_cutoff, $start, $dbw, $tdstyle, 10, "week");

	echo "</tr><tr><td {$tdstyle}>";
	
	/*******
	 *
	 *  Top article creators
	 *
	 */
	echo "\n\n<!-- " . date("r") . " top article creators --->\n";
    echo "<h2> Top 20 authors who started articles this month </h2>" ;
	wfDebug("Dashboard top 20 authors who started articles this month ");
	echo getTopCreators($dbw, $m_cutoff, $start);	
	echo "</td><td {$tdstyle}>";
    
	echo "<h2> Top 20 authors who started articles this week </h2>" ;
	wfDebug("Dashboard top 20 authors who started articles this week ");
	echo getTopCreators($dbw, $w_cutoff, $start);	
	echo "</td></tr></table>";

	echo "\n\n<!-- " . date("r") . " article stats --->\n";
	echo "<h1> Article stats</h2>";
   
	// get number of users who had 5+ edits this week 
	$sql = "select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > '$w_cutoff' and rev_timestamp < '{$start}' group by rev_user_text having C >= 5 order by C desc;";
    #echo $sql . "\n";
	wfDebug("Dashboard active 5 edits or more: $sql\n ");
    $res = $dbw->query($sql);
	$active_five_edits_more = $dbw->numRows($res);

	$sql = "select count(distinct(page_id)) from templatelinks left join page on tl_from = page_id and tl_title in ('Stub', 'Copyedit', 'Merge', 'Format', 'Cleanup', 'Accuracy');";
	wfDebug("Dashboard articles in problem categories $sql \n");
	echo "<ul><li>Articles in problem categories (as of " . getDateStr($now) . ")" . nf(selectField($dbw, $sql)) . "</li></ul>";
	echo "<ul><li>wikihow contributors who participated 5 or more times this week: " . nf($active_five_edits_more) . "</li></ul>";
	$sql = "select count(*) from templatelinks where tl_title='Rising-star-discussion-msg-2';";
	wfDebug("Dashboard number of rising starts $sql \n");
	echo "<ul><li>Number of Rising Stars: (as of " . getDateStr($now) . ") " . nf(selectField($dbw, $sql)) . "</li></ul>";
	
	echo "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle}>";
	echo "<h3>Article stats for the past week</h3>" . articleStats($dbw, $w_cutoff, $start) . "\n";	
	echo "</td><td {$tdstyle}>";
	echo "<h3>Article stats for the past month</h3>" . articleStats($dbw, $m_cutoff, $start) . "\n" ;	
	echo "</td></tr></table>";

	/*******
	 *
	 *  Wiki birthdays 
	 *
	 */
	echo "\n\n<!-- " . date("r") . " wiki birthdays --->\n";
	echo "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle}>";
	echo "<h3>Wiki Birthdays</h3>For users with 200+ edits<br/><br/>";
	echo getWikiBirthdays($start, $w_cutoff, $dbw); 
	echo "</td><td {$tdstyle}>";
	echo "</td></tr></table>";

	echo "</body>";


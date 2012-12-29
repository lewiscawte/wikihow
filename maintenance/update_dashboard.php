<?
	require_once('commandLine.inc');

	$tdstyle = ' style="vertical-align: top; padding-bottom:   20px; border: 1px solid #eee; background: #CFDDDD;" ';
	$wgTitle = Title::newFromText("Main Page");
	function selectField($dbw, $sql) {
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
		$res = $dbw->query($sql);
		return $dbw->numRows($res);
	}

	function getUserToolLinks($u) {
		global $wgUser;
if (!$u) {
echo wfBacktrace();
exit;
}
		$ret = $wgUser->getSkin()->userToolLinks($u->getID(), $u->getName());
		$ret = str_replace('href="/', 'href="http://www.wikihow.com/', $ret);
		return $ret;
	}
	function articleStats($dbw, $cutoff, $cutoff2 = null) {

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
                    'page_namespace' => NS_MAIN
                )));

        $result .= "\n</li><li> User talk namespace edits : "  .         
           nf($dbw->selectField(array('revision', 'page'),
            array('count(*)'),
            array(
                  "rev_timestamp > '{$cutoff}'",
				  $cutoff2 ?  "rev_timestamp < '{$cutoff2}'" : "1=1",
                 "page_id = rev_page",
                    'page_namespace' => NS_USER_TALK
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

	function getTopCreators($dbw, $cutoff) {
		$result = "<ol>";
	    $sql = "select nap_page from newarticlepatrol left join page on nap_page = page_id where page_namespace = 0 
				and nap_timestamp > '{$cutoff}'";
	    $res = $dbw->query($sql);
		$pages = array();
		$revisions = array();
	    while ($row = $dbw->fetchObject($res)) {
			$pages[] = $row->nap_page;
	    }
		foreach ($pages as $page_id) {
			$revisions[$page_id] = $dbw->selectField("revision", array("min(rev_id)"), array("rev_page"=>$page_id));
		}
		$users = array();
		foreach ($revisions as $page_id => $rev_id) {
			if (empty($rev_id)) {
				echo "<!---uh oh: {$page_id} has no min rev!-->";
				continue;
			}
			$u = selectField($dbw, "select rev_user_text from revision where rev_id={$rev_id}");
			if(!isset($users[$u])) {
				$users[$u] = 1;
			} else {
				$users[$u]++;
			}
		}
        asort($users, SORT_NUMERIC);
        $users = array_reverse($users);
        array_splice($users, 20);
		$yy = 0;
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
	$dbw = wfGetDB(DB_MASTER);

	$start = time();
	if (isset($argv[0])) {
		$start = wfTimestamp(TS_UNIX, $argv[0] . "000000");
	}
	$w_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 7); // 7 days
	$ww_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 14); // 14 days
	$m_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30); // 30 days
	$mm_cutoff 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 60); // 3days

	$d = getNumCreatedThenDeleted($dbw, $w_cutoff);
	$result .= '<link rel="stylesheet" type="text/css" href="./dashboard.css" /><body id="body" style="font-family: Arial;">';
	$result = '<body id="body" style="font-family: Arial;">';

	if ($wgServer != "http://wiki112.wikidiy.com") {
		$dbw->query("
		create temporary table rev_tmp (    
			rev_id int(8) unsigned NOT NULL,    
			`rev_page` int(8) unsigned NOT NULL default '0',     
			`rev_user` int(5) unsigned NOT NULL default '0',     
			`rev_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',     
			`rev_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
     		KEY `rev_timestamp` (`rev_timestamp`),
    		KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
    		KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

		$dbw->query("insert into rev_tmp 
    			select rev_id, rev_page, rev_user, rev_user_text, rev_timestamp from
        		revision, page where page_id=rev_page and page_namespace=0 and rev_user > 0;");
	}

	$rowCount = $dbw->selectField(array('rev_tmp'), array('count(*)'), array());

	// part 1
	$users = array();
	$res = $dbw->query("select rev_user, count(*) as C from rev_tmp group by rev_user having C > 500 order by C desc;");
	while ($row = $dbw->fetchObject($res)) {
		$users[$row->rev_user] = $row->C;
	}

	$result .= "<h1> User stats </h1>\n";
	$result .= "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle} colspan='2'>";
	$result .= "<h2>Very users who have 10+ edits in the past week </h2><ol>" ;
	foreach ($users as $u => $c) {
    	$count = selectField($dbw, 'select count(*) from rev_tmp where rev_timestamp > "' . $w_cutoff . '" and rev_user=' . $u);
		if ($count >= 10) {
			$x = User::newFromID($u);
			$count = nf($count);
			$result .= "<li>" . getUserLink($x) . " - " .nf($c) . " - {$count} " . getUserToolLinks($x) . "</li>\n";
		}
	}
	$result .= "</ol></td></tr><tr><td {$tdstyle}>";

    $result .= "<h2>Users who have 100+ edits in the past month </h2><ol>" ;
	$sql = 'select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > "' . $m_cutoff . '" group by rev_user_text having C >= 100 order by C desc;';
	#echo $sql . "\n";
	$res = $dbw->query($sql);
	while ($row = $dbw->fetchObject($res)) {
    	$x = User::newFromName($row->rev_user_text);
        $result .= "<li> " . getUserLink($x) . " - ". nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	$result .= "</ol></td><td {$tdstyle}>";

    $result .= "<h2>Users who have 25+ edits in the past week</h2><ol>" ;
	$sql = 'select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > "' . $w_cutoff . '" group by rev_user_text having C >= 25 order by C desc;';
	#echo $sql . "\n";
	$res = $dbw->query($sql);
	while ($row = $dbw->fetchObject($res)) {
    	$x = User::newFromName($row->rev_user_text);
        $result .= "<li> " . getUserLink($x) . " - " . nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	$result .= "</ol></td></tr><tr><td {$tdstyle}>";

    $result .= "<h2> Top 100 editors for the past month </h2><ol>" ;
    $sql =  'select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > "'. $m_cutoff. '" group by rev_user_text order by C desc LIMIT 100;';
	#echo $sql . "\n";
	$res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
        $x = User::newFromName($row->rev_user_text);
        $result .= "<li> " . getUserLink($x) . " - " . nf($row->C) . getUserToolLinks($x). "</li>\n";
    }
	$result .= "</ol></td><td {$tdstyle}>";

    $result .= "<h2> Top 50 editors for the past week </h2><ol>" ;
    $sql = 'select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > "'. $w_cutoff . '" group by rev_user_text order by C desc LIMIT 50;';
	#echo $sql . "\n";
	$res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
        $x = User::newFromName($row->rev_user_text);
        $result .= "<li> " . getUserLink($x) . " - ".  nf($row->C) . getUserToolLinks($x) . "</li>\n";
    }
	$result .= "</ol></td><tr></tr><td {$tdstyle}>";

	// who had 100+ edits 2 months ago?
    $result .= "<h2> Editors with a declining activity level this month</h2>" ;
	$result .= getActivityChange ($dbw, $m_cutoff, $mm_cutoff, true);
	$result .= "</td><td {$tdstyle}>";

    $result .= "<h2> Editors with a declining activity level this week</h2>" ;
	$result .= getActivityChange ($dbw, $w_cutoff, $ww_cutoff, true);
	$result .= "</td><tr></tr><td {$tdstyle}>";
    
	$result .= "<h2> Editors with a increasing activity level this month</h2>50% more activity than last month\n" ;
	$result .= getActivityChange ($dbw, $m_cutoff, $mm_cutoff, false);
	$result .= "</td><td {$tdstyle}>";

    $result .= "<h2> Editors with a increasing activity level this week</h2>50% more activity than last week\n" ;
	$result .= getActivityChange ($dbw, $w_cutoff, $ww_cutoff, false);
	$result .= "</td><tr></tr><td {$tdstyle}>";
	
    $result .= "<h2> New editors who became active this month </h2>Users who made their 25th edit this month.<ol>\n" ;
	$sql = "select rev_user, rev_user_text, max(rev_timestamp) as M, count(*) as C from rev_tmp group by rev_user having C >= 25 and M >'{$m_cutoff}'";
	$res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
    	$count = selectField($dbw, 'select count(*) from rev_tmp where rev_user=' . $row->rev_user . ' and rev_timestamp < "' . $m_cutoff . '";');
		if ($count <= 24) {
            $x = User::newFromName($row->rev_user_text);
            $c = nf($row->C);
            $result .= "<li> " . getUserLink($x) . " - {$c} total edits, {$count} before this month " . getUserToolLinks($x) . "</li>\n";
		}
	}
	$result .= "</ol>";
	$result .= "</td><td {$tdstyle}>";
	

    $result .= "<h2> New editors who became active this week </h2>Users who made their 10th edit this week.<ol>\n" ;
    $sql = "select rev_user, rev_user_text, max(rev_timestamp) as M, count(*) as C from rev_tmp group by rev_user having C >= 10 and M >'{$w_cutoff}'";
    $res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
        $count = selectField($dbw, 'select count(*) from rev_tmp where rev_user=' . $row->rev_user . ' and rev_timestamp < "' . $w_cutoff . '";');
        if ($count <= 9) {
            $x = User::newFromName($row->rev_user_text);
            $c = nf($row->C);
            $result .= "<li> " . getUserLink($x) . " - {$c} total edits, {$count} before this week" . getUserToolLinks($x) . "</li>\n";
        }   
    }
	$result .= "</ol>";
	$result .= "</td></tr><tr><td {$tdstyle}>";

/*
    $result .= "<h2> Newbies making an effort</h2>Users who made their 10th edit this week.\n" ;
	$sql = "select rev_user, rev_user_txt, count(*) as C, user_registration from rev_tmp left join wiki_shared.user on rev_user=user_id where user_registration > '{$w_cutoff}' group by rev_user having C > 10;"
    $res = $dbw->query($sql);
    while ($row = $dbw->fetchObject($res)) {
    	$x = User::newFromName($row->rev_user_text);
        $c = nf($row->C);
       	$result .= "<li> " . getUserLink($x) . " - {$c} - \n";
	}
*/

    $sql = 'select rev_user_text, count(*) as C from rev_tmp where rev_timestamp > "' . $w_cutoff . '" group by rev_user_text having C >= 5 order by C desc;';
    #echo $sql . "\n";
    $res = $dbw->query($sql);
	$active_five_edits_more = $dbw->numRows($res);

    $result .= "<h2> Top 20 authors who started articles this month </h2>" ;
	$result .= getTopCreators($dbw, $m_cutoff);	
	$result .= "</td><td {$tdstyle}>";
    
	$result .= "<h2> Top 20 authors who started articles this week </h2>" ;
	$result .= getTopCreators($dbw, $w_cutoff);	
	$result .= "</td></tr></table>";

	$result .= "<h1> Article stats</h2>";
	$sql = "select count(distinct(page_id)) from templatelinks left join page on tl_from = page_id and tl_title in ('Stub', 'Copyedit', 'Merge', 'Format', 'Cleanup', 'Accuracy');";
	$result .= "<ul><li>Articles in problem categories: " . nf(selectField($dbw, $sql)) . "</li></ul>";
	$result .= "<ul><li>wikihow contributors who participated 5 or more times this week: " . nf($active_five_edits_more) . "</li></ul>";
	$sql = "select count(*) from templatelinks where tl_title='Rising-star-discussion-msg-2';";
	$result .= "<ul><li>Number of Rising Stars: " . nf(selectField($dbw, $sql)) . "</li></ul>";
	
	$result .= "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle}>";
	$result .= "<h3>Article stats for the past week</h3>" . articleStats($dbw, $w_cutoff) . "\n";	
	$result .= "</td><td {$tdstyle}>";
	$result .= "<h3>Article stats for the past month</h3>" . articleStats($dbw, $m_cutoff) . "\n" ;	
	$result .= "</td></tr></table></body>";

	echo $result;


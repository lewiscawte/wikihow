<?php

class RCBuddy extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RCBuddy' );
    }


	// GETS THE NUMBER OF UNPATROLLED EDITS TO FEATURED ARTICLES, EXCLUDING EDITS 
	// BY THE CURRENT USER	
	function getFeaturedUnpatrolCount($delay, $skip) {
		global $wgMemc, $wgUser;
		$fname = "RCBuddy:getFeaturedUnpatrolCount";
		wfProfileIn($fname);
		$key = "rcbuddy_unp_count_{$delay}_{$wgUser->getID()}";
		if ($wgMemc->get($key)) {
			wfProfileOut($fname);
			return $wgMemc->get($key);
		}	
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow(array ('recentchanges', 'page'),
				array ('count(*) as c'),
				array ('rc_cur_id=page_id',
						'rc_patrolled=0',
						'rc_user !=' . $wgUser->getId(),
						'page_is_featured=1',
						$delay == 0 ? "1=1" : "rc_timestamp < '" . wfTimestamp( TS_MW, time() - $delay * 60 ) . "'",
						$skip == "" ? "1=1" : "rc_id not in ($skip)",
					),
				"wfSpecialRCBuddy"
			);
		$count = $row->c;
		$wgMemc->set($key, $count, 60);
		return $count;
	}


	// GETS PAGE WIDE STATS ABOUT # OF UNPATROLLED EDITS, # OF USERS ONLINE, 
	// WITH CACHEABLE RESULTS, SINCE THEY ARE PAGE WIDE STATS AND NOT USER SPECIFIC
	function getPageWideStats($results) {
		global $wgMemc;
		$fname = "RCBuddy:getPageWideStats";
		wfProfileIn($fname);
		$key = "rcbuddy_pagewidestats";
		if ($wgMemc->get($key)) {
			wfProfileOut($fname);
			return array_merge($results, $wgMemc->get($key));
		}
		$newstuff = array();
		$dbr = wfGetDB(DB_SLAVE);
	   	$count = $dbr->selectField(array ('recentchanges'),
	           array ('count(*) as c'),
	           array ( 'rc_patrolled=0'),
	            "wfSpecialRCBuddy"
	   );
		$newstuff['unpatrolled_total']= $count;	

		$t =  gmdate("YmdHis", time() - 60 * 30); // thirty minutes ago
		$row = $dbr->selectRow(array ('recentchanges'),
			array ('count(distinct(rc_user)) as c'),
			array ("rc_timestamp > $t ",
					'rc_user > 0',
					),
			"wfSpecialRCBuddy"
		);
		$count = $row->c;
		$newstuff['users_editing'] = $count;	
		$nab_unpatrolled = $dbr->selectField('newarticlepatrol',
				array('count(*)'),
				array('nap_patrolled=0')
			);
		$newstuff['nab_unpatrolled'] = $nab_unpatrolled;

		$wgMemc->set($key, $newstuff, 60); 
		return array_merge($newstuff, $results);
		wfProfileOut($fname);
	}


	/** THE FOLLOWING IS JUST USED BY THE WIKIHOW TOOLBAR. STUPID EH? ***/
    function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $wgServer, $wgCookiePrefix;

		$fname = "RCBuddy::execute";
		wfProfileIn($fname);
		$wgOut->disable(true);
		header("Content-type: text/plain");

		//users may have skipped a bunch of edits, don't include them here
		$skip = "";
		foreach ($_COOKIE as $key=>$value) {
			if (strpos($key, $wgCookiePrefix . "WsSkip_") === 0) {
				if ($skip != "") $skip .= ",";
				$skip .= $value;
			}
		}
		$delay = $wgRequest->getVal('delay');
		$count = $this->getFeaturedUnpatrolCount($delay, $skip); 
		$results = array('unpatrolled_fa' => $count);
		
		$results = self::getPageWideStats($results); 
 
		if( $wgUser->getNewtalk())
			$results['new_talk'] = 1;
		else
			$results['new_talk'] = 0;

		$window = Patrolcount::getPatrolcountWindow();
		$dbr =& wfGetDB( DB_SLAVE );
		$count=$dbr->selectField('logging',
					array('count(*)'),
					array("log_user={$wgUser->getId()}", "log_type='patrol'", 
						"log_timestamp > '{$window[0]}'",
						"log_timestamp < '{$window[1]}'",
					)
				);
		$results['patrolledtoday'] = $count;
		#echo "\ndebug: user: {$wgUser->getId()} date range: {$window[0]}, {$window[1]} user's offset {$wgUser->getOption( 'timecorrection' )}\n";
		foreach ($results as $k=>$v) {
			echo "$k=$v\n";
		}
		wfProfileOut($fname);
		return;
	}
}


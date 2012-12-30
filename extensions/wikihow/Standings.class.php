<?

abstract class StandingsIndividual {

	var $mLeaderboardKey = null;
	var $mStats = null;
 
	function getStandingsTable() {
		$this->fetchStats();

		$rank = $this->mStats['standing'];
		if ($rank == 0) {
			$rank = "N/A";
		}

		$table = "<table>
        <tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Today</a></td>
            <td id='iia_stats_today_{$this->mLeaderboardKey}'>{$this->mStats['today']}</td>
        </tr>
        <tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}?period=7'>This Week</a></td>
            <td id='iia_stats_week_{$this->mLeaderboardKey}'>{$this->mStats['week']}</td>
        </tr>";
		if ($this->showTotal()) {
			$table .= "<tr>
					<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Total</a></td>
					<td id='iia_stats_all_{$this->mLeaderboardKey}'>{$this->mStats['all']}</td></tr>";
		}
        $table .= "<tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Rank This Week</a></td>
            <td id='iia_stats_standing_{$this->mLeaderboardKey}'>{$rank}</td>
        </tr>
        </table>";
		return $table;
	}


	/** 
	 *  Sometimes it doesn't make sense to show the total of all time
	 *  such as quick edits because we are only looking at the RC table
	 *  Subclasses can override this.
	 *
	 */
	function showTotal() {
		return true;
	}


	/** 
	 *  Sometimes a class will want to pass down an additional message/default message 
	 *  based on the stats. 
	 *
	 */
	function getAdditionalMessages() {
		return array(); 
	}

    /** 
     * addStatsWidget
     * add stats widget to right rail
     **/
    function addStatsWidget() {
        global $wgUser;
        $fname = "StandingsIndividual::addStatsWidget";
        wfProfileIn($fname);
        $sk = $wgUser->getSkin();

        $display = "<div class='iia_stats'>
        <h3>{$this->getTitle()}</h3>
		<div id='iia_individual_table_{$this->mLeaderboardKey}'>" . $this->getStandingsTable() . 
        "</div></div>";

        $sk->addWidget( $display );
        wfProfileOut($fname);
    }

    /** 
     * fetchStats
     * get the use stats
     **/
    function fetchStats() {
        global $wgUser, $wgMemc;
        $fname = "StandingsIndividual::fetchStats";
        wfProfileIn($fname);

        $dbr = wfGetDB(DB_SLAVE);

        $ts_today = date('YmdG',strtotime('today')) . '000000';
        $ts_week = date('YmdG',strtotime('7 days ago')) . '000000';

		$tbl = $this->getTable(); 

        $today 	= $dbr->selectField($tbl, 'count(*)',  $this->getOpts($ts_today));
        $week 	= $dbr->selectField($tbl, 'count(*)',  $this->getOpts($ts_week));
        
		if ($this->showTotal()) {
			$all = $dbr->selectField($tbl, 'count(*)',  $this->getOpts()); 
		}	

        $standing = $this->getStanding($wgUser);

        $s_arr = array(
            'today' => $today,
            'week' => $week,
            'all' => $all,
            'standing' => $standing,
        );

		$this->mStats = $s_arr;
        wfProfileOut($fname);
    }

	function getStanding($user) {
		$group = $this->getGroupStandings(); 
		return $group->getStanding($user); 
	}

	public abstract function getTitle(); 
	public abstract function getOpts($ts = null);
	public abstract function getGroupStandings(); 

}


abstract class StandingsGroup {

	var $mCacheKey = null;
	var $mCacheExpiry = 300;
	var $mLeaderboardKey = null;

	/**	
	 * getStandingsTable
	 * get standings table dynamically
	 **/
	function getStandingsTable() {
		global $wgUser;
        $fname = "StandingsGroup::getStandingsTable";
        wfProfileIn($fname);

		$sk = $wgUser->getSkin();
		$display = "<table>";

		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$data = $this->getStandingsFromCache() ;
		$count = 0;
      	foreach($data as $key => $value) {
			$u = new User();
			$u->setName($key);
			if (($value > 0) && ($key != '')) {

				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}

				$id = "";
				if ($wgUser->getName() == $u->getName()) {
					$id = "id='iia_stats_group'";
				}	
				$display .="<tr><td class='leader_image'>{$img}</td><td>" 
						. $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td><td class='leader_count' {$id}>{$value}</td></tr>";
				$count++;
			}
			if ($count > 5) {break;}
	
		}

		$display .= "
		</table>";
		wfProfileOut($fname);
		return $display;
	}

	function getStandingsFromCache() {
		global $wgMemc;
		$fname = "StandingsGroup::getStandingsFromCache";
		wfProfileIn($fname);
		$standings = $wgMemc->get($this->mCacheKey);
		if (!$standings || true) {
			$dbr = wfGetDB(DB_SLAVE);
			$ts = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
			$sql = $this->getSQL($ts);
			$res = $dbr->query($sql); 
			$standings = array();
			$field = $this->getField();
			while ($row = $dbr->fetchObject($res)) {
				$standings[$row->$field] = $row->C;
			}
			$wgMemc->set($this->mCacheKey, $standings, $this->mCacheExpiry);
		}
		wfProfileOut($fname);
		return $standings;
	}

	function getStanding($user) {	
        $fname = "StandingsGroup::getStanding";
        wfProfileIn($fname);
		$standings = $this->getStandingsFromCache();
		$index = 1;
		foreach ($standings as $s => $c) {
			if ($s == $user->getName()) {
				wfProfileOut($fname);
				return $index;
			}
			$index++;	
		}
		wfProfileOut($fname);
		return 0;
	}

    /** 
     * addStandingsWidget
     * add standings widget
     **/
    function addStandingsWidget() {
        global $wgUser, $wgOut;
        $fname = "StandingsGroup::addStandingsWidget";
        wfProfileIn($fname);

        $sk = $wgUser->getSkin();
		$wgOut->addScript("<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/Leaderboard.css?rev=') . WH_SITEREV . "'; /*]]>*/</style>");

        $display = "
        <div class='iia_stats'>
        <h3>".$this->getTitle() . "</h3>
        <div id='iia_standings_table'>
        ".$this->getStandingsTable()."
        </div> " . $this->getUpdatingMessage() . "
        </div>";

        $sk->addWidget( $display );
        wfProfileOut($fname);
    }

	
	public abstract function getSQL($ts); 
	public abstract function getField(); 
	public abstract function getTitle(); 

	function getUpdatingMessage() {
        $msg = "<p class='bottom_link' style='text-align:center; padding-top:5px'>
        Updating in  <span id='stup'>10</span> minutes
        </p>";
		return $msg;
	}


}

class IntroImageStandingsGroup extends StandingsGroup {

	function __constuct() {
		$this->mCacheKey = "imageadder_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text, count(*) as C from recentchanges WHERE 
			rc_timestamp > '{$ts}' and rc_comment='Added Image using ImageAdder Tool'
			group by rc_user_text order by C desc limit 25;";	
		return $sql;
	}		

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMsg('iia_standings_title');
	}
}


class QCStandingsGroup extends StandingsGroup  {
	function __constuct() {
		$this->mCacheKey = "qc_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT user_name, count(*) as C from qc_vote left join wiki_shared.user on qcv_user=user_id WHERE 
			qc_timestamp > '{$ts}'
			group by qcv_user order by C desc limit 25;";	
		return $sql;
	}		

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMsg('qc_standings_title');
	}
}

class QuickEditStandingsGroup extends StandingsGroup  {
	function __constuct() {
		$this->mCacheKey = "quickedit_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text,count(*) as C ".
            "FROM recentchanges ".
            "WHERE rc_comment like 'Quick edit while patrolling' and rc_timestamp >= '$ts' ".
            "GROUP BY rc_user_text ORDER by C desc limit 25";
		return $sql;
	}		

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMsg('rcpatrolstats_leaderboard_title'); 
	}
}

class RCPatrolStandingsGroup extends StandingsGroup  {
	function __constuct() {
		$this->mCacheKey = "rcpatrol_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT user_name, count(*) as C ".
            "FROM logging left join wiki_shared.user on log_user = user_id ".
            "WHERE log_type = 'patrol' and log_timestamp >= '$ts' ".
            "GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}		

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMsg('rcpatrolstats_leaderboard_title'); 
	}
}


class QuickEditStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "rc_quick_edits";
	}

	function getTable() {
		return "recentchanges";
	}

	function showTotal() {
		return false;
	}

	function getTitle() {
		return wfMsg('quickedits_stats'); 
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['rc_user_text'] =$wgUser->getName();
		$opts[] = "rc_comment like 'Quick edit while patrolling' ";
		if ($opts) {
			$opts[]= "rc_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new QuickEditStandingsGroup(); 
	}

}


class RCPatrolStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "rc_edits";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMsg('rcpatrolstats_currentstats'); 
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='patrol';
		if ($opts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new RCPatrolStandingsGroup();
	}

}
class IntroImageStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "images_added";
	}

	function getTable() {
		return "image";
	}

	function getTitle() {
		return wfMsg('iia_stats_title');
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['img_user_text'] =$wgUser->getName();
		if ($opts) {
			$opts[]= "img_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new IntroImageStandingsGroup(); 
	}

}

class QCStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "qc";
	}

	function getTable() {
		return "qc_vote";
	}

	function getTitle() {
		wfLoadExtensionMessages('qc');
		return wfMsg('qc_stats_title');
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['qcv_user']=$wgUser->getID();
		if ($opts) {
			$opts[]= "qc_timestamp >'{$ts}'";
		}
		return $opts;
	}
	
	function getGroupStandings() {
		return new QCStandingsGroup(); 
	}

}

// EditFinder tool
class EditFinderStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "edit_finder";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMsg('editfinder_stats'); 
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='editfinder';
		if ($opts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new RCPatrolStandingsGroup();
	}

	function getAdditionalMessages() {
		$today = $this->mStats['today'];
		$standing = $this->mStats['standing'];

        if ($today == 1) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_firsttoday'));
        } else if ($today < 5) {
            $msg = wfMsg('editfinder_msg_instructions');
        } else if ($all == 1) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_firstever'));
        } else if (($today%10 == 0) && ($today != 0)) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_multiple10', $today));
        } else if ($standing < 5) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top5'));
        } else if ($standing < 10) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top10'));
        } else if ($standing < 25) {
            $msg = wfMsg('editfinder_msg_icon', wfMsg('editfinder_msg_top25'));
        }

		$result = array(
            'message' => $msg,
            'defaultmsg' => $defaultmsg,
		);
	
		return $result;
	}
}

class EditFinderStandingsGroup extends StandingsGroup  {
	function __constuct() {
		$this->mCacheKey = "editfinder_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT user_name, count(*) as C ".
            "FROM logging left join wiki_shared.user on log_user = user_id ".
            "WHERE log_type = 'editfinder' and log_timestamp >= '$ts' ".
            "GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}		

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMsg('editfinder_leaderboard_title'); 
	}
}

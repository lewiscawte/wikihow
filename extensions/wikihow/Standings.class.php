<?

// Extend this class for an individual stats wiget
abstract class StandingsIndividual {

	var $mLeaderboardKey = null;
	var $mStats = null;

	/** 
     *  Gets the raw table, useful for ajax calls that just want the innards
     * 
     */ 
	function getStandingsTable() {
		$this->fetchStats();

		$rank = $this->mStats['standing'];
		if ($rank == 0) {
			$rank = "N/A";
		}


		$today 	= number_format($this->mStats['today'], 0, '.', ",");
		$week 	= number_format($this->mStats['week'], 0, '.', ",");  
		$all	= number_format($this->mStats['all'], 0, '.', ",");  

		$table = "<table>
        <tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Today</a></td>
            <td class='stats_count' id='iia_stats_today_{$this->mLeaderboardKey}'>{$today}</td>
        </tr>
        <tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}?period=7'>This Week</a></td>
            <td class='stats_count' id='iia_stats_week_{$this->mLeaderboardKey}'>{$week}</td>
        </tr>";
		if ($this->showTotal()) {
			$table .= "<tr>
					<td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Total</a></td>
					<td class='stats_count' id='iia_stats_all_{$this->mLeaderboardKey}'>{$all}</td></tr>";
		}
        $table .= "<tr>
            <td><a href='/Special:Leaderboard/{$this->mLeaderboardKey}'>Rank This Week</a></td>
            <td class='stats_count' id='iia_stats_standing_{$this->mLeaderboardKey}'>{$rank}</td>
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
     * get the stats in an array
     **/
    function fetchStats() {
        global $wgUser, $wgMemc, $wgLang;
        $fname = "StandingsIndividual::fetchStats";
        wfProfileIn($fname);

        $dbr = wfGetDB(DB_SLAVE);

        $ts_today = date('Ymd',strtotime('today')) . '000000';
        $ts_week = date('Ymd',strtotime('7 days ago')) . '000000';

		$timecorrection = $wgUser->getOption( 'timecorrection' );
		$ts_today = $wgLang->userAdjust( $ts_today, $timecorrection );
		$ts_week = $wgLang->userAdjust( $ts_week, $timecorrection );

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
		return $this->mStats;
    }

	function getStanding($user) {
		$group = $this->getGroupStandings(); 
		return $group->getStanding($user); 
	}

	public abstract function getTitle(); 
	public abstract function getOpts($ts = null);
	public abstract function getGroupStandings(); 

}

// Extend this class if you a leaderboard type group standings
abstract class StandingsGroup {

	var $mCacheKey = null;

	// how long should the standings array be in the cache? 5min default
	var $mCacheExpiry = 300; 
	var $mLeaderboardKey = null;

	/**	
	 * getStandingsTable
	 * returns just the raw table for the standings, useful for ajax calls
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
				$display .="<tr><td class='leader_image'>{$img}</td><td class='leader_name'>"
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

	/**  	
     * 	This returns an array of users, in order for their standings.
	 *	If it's no in the cache, it builds it and puts it in the cache.
	 */
	function getStandingsFromCache() {
		global $wgMemc;
		$fname = "StandingsGroup::getStandingsFromCache";
		wfProfileIn($fname);
		$standings = $wgMemc->get($this->mCacheKey);
		if (!$standings) {
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
			wfDebug("Standings: didn't get the cache set {$this->mCacheKey} {$this->mCacheExpiry} " . print_r($standings, true) . "\n");
		} else {
			wfDebug("Standings: DID get the cache\n");
		}
		wfProfileOut($fname);
		return $standings;
	}

	/** 
 	 * 	Returns where a particular users stands in this group
	 *  0 if they aren't in the top X
 	 *
 	 */
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
     * Generates the actual HTML for the widget, and adds the necessary CSS to the skin
	 *	
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

	/**
	 *
	 * Takes a row number and returns the count for that row.
	 * If there are not enough rows, returns the count for the
	 * last row that exists.
	 * If there are NO rows, returns 0
	 * 
	 */
	public function getStandingByIndex($rowNum){
		$standings = $this->getStandingsFromCache();
		$index = 1;
		$c = 0;
		foreach ($standings as $s => $c) {
			if($index == $rowNum)
				return $c;
			$index++;
		}

		return $c;
	}

	/**
 	 * You can override this if you don't want your standings widget to update 
 	 * automatically
	 */
	function getUpdatingMessage() {
        $msg = "<p class='bottom_link' style='text-align:center; padding-top:5px'>
        Updating in <span id='stup'>10</span> minutes
        </p>";
		return $msg;
	}


}

class IntroImageStandingsGroup extends StandingsGroup {

	function __construct() {
		$this->mCacheKey = wfMemcKey("imageadder_standings");
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text, count(*) as C from recentchanges WHERE 
			rc_timestamp > '{$ts}' and rc_comment='Edit via [[Special:IntroImageAdder|Image Picker]]: Added an image'
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

class ArticleWrittenStandingsGroup extends StandingsGroup {

	function __construct() {
		$this->mCacheKey = "articlewritten_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT 'Newpages' as type,
				count(*)as C,
				rc_title AS title,
				rc_cur_id AS cur_id,
				rc_user AS \"user\",
				rc_user_text AS user_text
			FROM recentchanges, page
			WHERE rc_cur_id=page_id AND rc_timestamp >= '". $ts ."'
			AND rc_user_text != 'WRM' AND rc_user != '0' AND rc_new = 1 AND rc_namespace = 0 AND page_is_redirect = 0
			group by user_text order by C limit 25";
		return $sql;
	}

	function getField() {
		return "user_text";
	}

	function getTitle() {
		return wfMsg('iia_standings_title');
	}
}

class NABStandingsGroup extends StandingsGroup {

	function __construct() {
		$this->mCacheKey = "nab_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT nap_user_ci, count(*) as C from newarticlepatrol WHERE
			nap_timestamp_ci > '{$ts}' and nap_patrolled=1
			group by nap_user_ci order by C desc limit 25;";
		return $sql;
	}

	function getField() {
		return "nap_user_ci";
	}

	function getTitle() {
		return wfMsg('nab_standings_title');
	}
}

class VideoStandingsGroup extends StandingsGroup {

	function __construct() {
		$this->mCacheKey = "videoadder_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT va_user_text, count(*) as C from videoadder WHERE
			va_timestamp >= '{$ts}' AND (va_skipped_accepted = '0' OR va_skipped_accepted = '1')
			group by va_user ORDER BY C desc";
		return $sql;
	}

	function getField() {
		return "va_user_text";
	}

	function getTitle() {
		return wfMsg('va_topreviewers');
	}
}


class QCStandingsGroup extends StandingsGroup  {
	function __construct() {
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

class NFDStandingsGroup extends StandingsGroup  {
	function __construct() {
		$this->mCacheKey = "nfd_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT user_name, count(*) as C from nfd_vote left join wiki_shared.user on nfdv_user=user_id WHERE
			nfdv_timestamp > '{$ts}'
			group by nfdv_user order by C desc limit 25;";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMsg('nfd_standings_title');
	}
}

class QuickEditStandingsGroup extends StandingsGroup  {
	function __construct() {
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
	function __construct() {
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
		if ($ts) {
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
		if ($ts) {
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
		if ($ts) {
			$opts[]= "img_timestamp >'{$ts}'";
		}
		return $opts;
	}
	
	function getGroupStandings() {
		return new IntroImageStandingsGroup(); 
	}

}

class CategorizationStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "articles_categorized";
	}

	function getTable() {
		return "recentchanges";
	}

	function getTitle() {
		return wfMsg('categorization_stats_title');
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['rc_user_text'] =$wgUser->getName();
		$opts[] = "rc_comment like 'categorization'";
		if ($ts) {
			$opts[]= "rc_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new IntroImageStandingsGroup();
	}

}

class VideoStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "videos_reviewed";
	}

	function getTable() {
		return "videoadder";
	}

	function getTitle() {
		return wfMsg('va_yourstats');
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['va_user_text'] =$wgUser->getName();
		if ($ts) {
			$opts[]= "va_timestamp >'{$ts}'";
		}
		$opts[] = "(va_skipped_accepted = '0' OR va_skipped_accepted = '1')";
		return $opts;
	}

	function getGroupStandings() {
		return new VideoStandingsGroup();
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
		if ($ts) {
			$opts[]= "qc_timestamp >'{$ts}'";
		}
		return $opts;
	}
	
	function getGroupStandings() {
		return new QCStandingsGroup(); 
	}

}

class NFDStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "nfd";
	}

	function getTable() {
		return "nfd_vote";
	}

	function getTitle() {
		wfLoadExtensionMessages('nfdGuardian');
		return wfMsg('nfd_stats_title');
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['nfdv_user']=$wgUser->getID();
		if ($ts) {
			$opts[]= "nfdv_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new NFDStandingsGroup();
	}

}

// EditFinder / Article Repair Shop tool
class EditFinderStandingsIndividual extends StandingsIndividual {

	function __construct($type = 'format') {
		$this->mLeaderboardKey = "repair_".$type;
		$this->mEFType = $type;
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMsg('ef_statind_title')." - ".ucfirst(wfMsg('statind_'.$this->mEFType)); 
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='EF_'.$this->mEFType;
		if ($ts) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new EditFinderStandingsGroup($this->mEFType);
	}

}

class EditFinderStandingsGroup extends StandingsGroup  {
	function __construct($type = 'format') {
		global $wgRequest;

		$typeParam = strtolower($wgRequest->getVal('type'));
		if (strlen($typeParam)) {
			$type = $typeParam;
		}
		$this->mCacheKey = "editfinder_" . $type . "_standings";
		$this->mEFType = $type;
	}

	function getSQL($ts) {
		$sql = "SELECT user_name, count(*) as C ".
            "FROM logging left join wiki_shared.user on log_user = user_id ".
            "WHERE log_type = 'EF_".$this->mEFType."' and log_timestamp >= '$ts' ".
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

class CategorizationStandingsGroup extends StandingsGroup  {
	function __construct() {
		$this->mCacheKey = "categorization_standings";
	}

	function getSQL($ts) {
		$sql = "SELECT rc_user_text,rc_title, count(*) as C ".
			"FROM recentchanges ".
			"WHERE rc_comment like 'categorization' and rc_timestamp >= '$ts' AND rc_user_text != 'WRM' ".
			"GROUP BY rc_user_text desc limit 25" ;
		return $sql;
	}

	function getField() {
		return "rc_user_text";
	}

	function getTitle() {
		return wfMsg('categorization_leaderboard_title');
	}
}

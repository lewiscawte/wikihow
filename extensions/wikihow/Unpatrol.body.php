<?
class Unpatrol extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Unpatrol' );
    }

		function padVar ($name) {
			global $wgRequest;
			$val = $wgRequest->getVal($name);
			if ($val && strlen($val) < 2)
				$val = "0" . $val;
			return $val;
		}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser;

		if ( !in_array( 'bureaucrat', $wgUser->getGroups() ) ) {
         	$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
         	return;
		}

		$wgOut->addHTML("
				<form action='/Special:Unpatrol' method='POST'>
					Username: <input type='text' name='username' value='{$wgRequest->getVal('username', '')}'/> <br/><br/>
					Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
							Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
							Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
					End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
                            Day: <input type='text' name='day_2' size='2'>
                            Hour (GMT): <input type='text' name='hour_2' size='2'> <br/><br/>
					<input type='submit'/>
				</form>	");
		if ($wgRequest->wasPosted()) {
    		$user   = $wgRequest->getVal('username');
    	
			$start  = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end =  $wgRequest->getVal('month_2') ? 
						date("Y") . $this->padVar('month_1') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;
		
		    $cutoff = wfTimestamp(TS_MW, $start);
		    $cutoff2 = null;
		    if (!$end) {
		        $wgOut->addHTML("reverting changes by $user since {$cutoff}<br/>");
		    } else {
		        $cutoff2 = wfTimestamp(TS_MW, $end);
		        $wgOut->addHTML("reverting changes by $user between {$cutoff} and {$cutoff2} <br/>");
		    }
		
		    $user = User::newFromName($user);

			if ($user->getID() == 0) {
				$wgOut->addHTML("<b>WHoa! There is no user with this name {$wgRequest->getVal('username', '')}, bailing.</b>");
				return;
			}
		
		    $dbw = wfGetDB(DB_MASTER);
		    $options = array('log_user'=>$user->getID(), 'log_type'=>'patrol', "log_timestamp > '{$cutoff}'");
		    if ($cutoff2)
		        $options[] = "log_timetamp < '{$cutoff2}'";
		
		    $res = $dbw->select('logging', array('log_title', 'log_params'), $options);
		
		
		    $oldids = array();
		    while ($row = $dbw->fetchObject($res)) {
		        #echo "{$row->log_title}\t". str_replace("\n", " ", $row->log_params) . "\n";
		        $oldids[]= preg_replace("@\n.*@", "", $row->log_params);
		    }
			if (sizeof($oldids) > 0) {		
		    	$count = $dbw->query("UPDATE recentchanges set rc_patrolled=0 where rc_this_oldid IN (" . implode(", ", $oldids) . ");");
    			$wgOut->addHTML( "Unpatrolled " . sizeof($oldids) . " patrols by {$user->getName()}\n");
			} else {
    			$wgOut->addHTML( "There were no patrolled edits to undo for this time frame.<br/>");
			}	
		}		
		return;	
	}
}

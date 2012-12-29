<?php

class RCWidget extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RCWidget' );
	}

	function getDTDifferenceString($date) {
		$date = $date . " UTC";

		if(empty($date)) {
			return "No date provided";
		}

		$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		$lengths = array("60","60","24","7","4.35","12","10");

		$now = time();
		$unix_date = strtotime($date);

		// check validity of date
		if(empty($unix_date)) {
			return "Bad date: $date";
		}

		// is it future date or past date
		if($now > $unix_date) {
			$difference = $now - $unix_date;
			$tense = "ago";

		} else {
			$difference = $unix_date - $now;
			$tense = "from now";
		}

		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if($difference != 1) {
			$periods[$j].= "s";
		}

		return "$difference $periods[$j] {$tense} ";
	}

	function addRCElement(&$widget, &$count, $obj) {
		if ((strlen(strip_tags($obj['text'])) < 100) &&
			 (strlen($obj['text']) > 0)) {
			$widget[$count++] = $obj;
		}
	}

	function filterLog(&$widget, &$count, $row) {
		$obj = "";
		$u = new User;
		$u = $u->newFromId($row->log_user);
		$real_user = $u->getName();

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$real_user)){
			$wuser = 'An Anonymous visitor';
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $real_user;
			$wuserLink = '/User:'.$real_user;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->log_title)){
			$destUser = 'An Anonymous visitor';
			$destUserLink = '/User:'.$row->log_title;
		} else {
			$destUser = $row->log_title;
			$destUserLink = '/'.$row->log_title;
		}

		switch ($row->log_type) {
			case 'patrol':
				if ($row->log_namespace == NS_USER) {
					$obj['type'] = 'patrol';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_patrolled').' ';
					$obj['text'] .= '<a href="/User:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				} else if ($row->log_namespace == NS_USER_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_patrolled').' ';
					$obj['text'] .= '<a href="/User_talk:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				} else if ($row->log_namespace == NS_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_patrolled').' ';
					$obj['text'] .= '<a href="/Discussion:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				} else if ($row->log_namespace == NS_MAIN) {
					$obj['type'] = 'patrol';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_patrolled').' ';
					$obj['text'] .= '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				}
				$this->addRCElement($widget, $count, $obj);
				break;
			case 'nap':
				$obj['type'] = 'nab';
				$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
				$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$obj['text'] .= ' '.wfMsg('action_boost').' ';
				$obj['text'] .= '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
				$this->addRCElement($widget, $count, $obj);
				break;
			case 'upload':
				if ( ($row->log_action == 'upload') && ($row->log_namespace == 6)) {
					$obj['type'] = 'image';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_image').' ';
					if (strlen($row->log_title) > 25) {
						$obj['text'] .= '<a href="/Image:'.$row->log_title.'">'.substr($row->log_title,0,25).'...</a>';
					} else {
						$obj['text'] .= '<a href="/Image:'.$row->log_title.'">'.$row->log_title.'</a>';
					}
					$this->addRCElement($widget, $count, $obj);
				}
				break;
			case 'vidsfornew':
				if ( ($row->log_action == 'added') && ($row->log_namespace == 0)) {
					$obj['type'] = 'video';
					$obj['ts'] = $this->getDTDifferenceString($row->log_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_addedvideo').' ';
					$obj['text'] .= '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
					$this->addRCElement($widget, $count, $obj);
				}
				break;
		}
	}

	function filterRC(&$widget, &$count, $row) {
		$obj = "";
		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_user_text)){
			$wuser = 'An Anonymous visitor';
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $row->rc_user_text;
			$wuserLink = '/User:'.$row->rc_user_text;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_title)){
			$destUser = 'An Anonymous visitor';
			$destUserLink = '/User:'.$row->rc_title;
		} else {
			$destUser = $row->rc_title;
			$destUserLink = '/'.$row->rc_title;
		}

		switch ($row->rc_namespace) {
			case NS_MAIN: //MAIN
				if (preg_match('/^New page:/',$row->rc_comment)) {
					$obj['type'] = 'newpage';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_newpage').' ';
					$obj['text'] .= '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$this->addRCElement($widget, $count, $obj);
				} else if (preg_match('/^categorization/',$row->rc_comment)) {
					$obj['type'] = 'categorized';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_categorized').' ';
					$obj['text'] .= '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$this->addRCElement($widget, $count, $obj);
				} else if ( (preg_match('/^\/* Steps *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Tips *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Warnings *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Things You\'ll Need *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Ingredients *\//',$row->rc_comment)) ||
								(preg_match('/^$/',$row->rc_comment)) ||
								(preg_match('/^Quick edit/',$row->rc_comment)) ) {
					$obj['type'] = 'edit';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_edit').' ';
					$obj['text'] .= '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$this->addRCElement($widget, $count, $obj);
				}
				break;
			case NS_TALK: //DISCUSSION
				if (!preg_match('/^Reverts edits by/',$row->rc_comment)) {
					if (preg_match('/^Marking new article as a Rising Star from From/',$row->rc_comment)) {
						$obj['type'] = 'risingstar';
						$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
						$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$obj['text'] .= ' '.wfMsg('action_risingstar1').' ';
						$obj['text'] .= '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
						$obj['text'] .= ' '.wfMsg('action_risingstar2');
					} else if ($row->rc_comment == '') {
						$obj['type'] = 'discussion';
						$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
						$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$obj['text'] .= ' '.wfMsg('action_discussion').' ';
						$obj['text'] .= '<a href="/Discussion:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					}
					$this->addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_TALK: //USER_TALK
				if (!preg_match('/^Revert/',$row->rc_comment)) {
					$obj['type'] = 'usertalk';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_usertalk').' ';
					$obj['text'] .= '<a href="/User_talk:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$this->addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_KUDOS: //KUDOS
				$obj['type'] = 'kudos';
				$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
				$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$obj['text'] .= ' '.wfMsg('action_fanmail').' ';
				$obj['text'] .= '<a href="/User_kudos:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				$this->addRCElement($widget, $count, $obj);
				break;
			case NS_VIDEO: //VIDEO
				// I KNOW I HAVE VIDEO FOR BOTH RC & LOGGING. LOGGING ONLY DOESN'T SEEM TO CATCH EVERYTHING.
				if (preg_match('/^adding video/',$row->rc_comment)) {
					$obj['type'] = 'video';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_addedvideo').' ';
					$obj['text'] .= '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$this->addRCElement($widget, $count, $obj);
				}
				break;
			case NS_SPECIAL: //OTHER
				if (preg_match('/^New user/',$row->rc_comment)) {
					$obj['type'] = 'newuser';
					$obj['ts'] = $this->getDTDifferenceString($row->rc_timestamp);
					$obj['text'] = '<a href="/User:'.$row->rc_user_text.'">'.$wuser.'</a>';
					$obj['text'] .= ' '.wfMsg('action_newuser').' ';
					$this->addRCElement($widget, $count, $obj);
				}
				break;

		}

		return $obj;
	}

	function showWidget() {
		global $wgUser;

?>
	<script type="text/javascript" >
	<?php if ($wgUser->getID() > 0): ?>
		var rc_URL = '/Special:RCWidget/current';
		var rc_ReloadInterval = 60000;
	<?php else: ?>
		var rc_URL = '/Special:RCWidget';
		var rc_ReloadInterval = 160000;
	<?php endif; ?>

		$(window).load(rcwLoad);
	</script>

	<div id='rcwidget_divid'>
		<h3><span onclick="location='/Special:Recentchanges';" style="cursor:pointer;"><?= wfMsg('recentchanges');?></span></h3>
		<div id='rcElement_list' class='widgetbox'>
			<div id='IEdummy'></div>
		</div>
		<div id='rcwDebug' style='display:none'>
			<input id='testbutton' type='button' onclick='rcTest();' value='test'>
			<input id='stopbutton' type='button' onclick='rcTransport();' value='stop'>
			<span id='teststatus' ></span>
		</div>
	</div>
<?php
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgLang, $wgServer, $wgRequest;
		wfLoadExtensionMessages('RCWidget');
		$fname = 'RCWidget';


		if ($par == 'current') {
			$wgOut->setSquidMaxage( 60 );
			$wgRequest->response()->header( 'Cache-Control: s-maxage=60, must-revalidate, max-age=60' );
		} else {
			$wgOut->setSquidMaxage( 160 );
			$wgRequest->response()->header( 'Cache-Control: s-maxage=160, must-revalidate, max-age=160' );
		}

		$wgOut->setArticleBodyOnly(true);
		$wgOut->sendCacheControl();

		$dbr = wfGetDB(DB_SLAVE);

		$cutoff_unixtime = time() - ( 3600 ); // one hour
		$cutoff_unixtime = time() - ( 30 * 86400 ); // 30 days
		$cutoff = $dbr->timestamp( $cutoff_unixtime );
		$currenttime = $dbr->timestamp( time() );

		// QUERY RECENT CHANGES TABLE
		$sql = "SELECT rc_timestamp,rc_user_text,rc_namespace,rc_title,rc_comment,rc_patrolled ".
			"FROM recentchanges ".
			"ORDER BY rc_timestamp DESC " ;
		$sql = $dbr->limitResult($sql, 200, 0);
		$res = $dbr->query( $sql, $fname );

		// QUERY LOGIN TABLE
		$logsql = "SELECT log_id,log_timestamp,log_user,log_namespace,log_title,log_comment,log_type,log_action ".
			"FROM logging ".
			//"WHERE log_type in ('patrol','newusers','nap') ".
			"ORDER BY log_timestamp DESC " ;
			// other WHERE 'upload'
		$logsql = $dbr->limitResult($logsql, 200, 0);
		$logres = $dbr->query( $logsql, $fname );

		$count = 0;
		$widget['servertime'] = $currenttime;

		// MERGE  TABLES and FILTER RESULTS
		//
		$rl = $dbr->fetchObject($logres) ;
		$rr = $dbr->fetchObject($res) ;
		$patrol_limit = 5;
		$patrol_count = 0;
		$patrol_prevUser = "";
		$patrol_prevTitle = "";
		$kudos_count = 0;
		$kudos_limit = 3;
		while (1) {

			if ($rr && $rl) {
				if ($rl->log_timestamp > $rr->rc_timestamp) {
					if ($rl->log_action != 'patrol') {
						$this->filterLog($widget, $count, $rl);
					} else if (($rl->log_action == 'patrol') && ($patrol_count < $patrol_limit)) {
						if (($patrol_prevUser != $rl->log_user) || ($patrol_prevTitle != $rl->log_title)) {
							$this->filterLog($widget, $count, $rl);
						}
						$patrol_prevUser = $rl->log_user;
						$patrol_prevTitle = $rl->log_title;
						$patrol_count++;
					}
					$rl = $dbr->fetchObject($logres) ;
				} else {
					if ($rr->rc_namespace != NS_USER_KUDOS) {
						$this->filterRC($widget, $count, $rr);
					}else if (($rr->rc_namespace == NS_USER_KUDOS) && ($kudos_count < $kudos_limit)) {
						$this->filterRC($widget, $count, $rr);
						$kudos_count++;
					}
					$rr = $dbr->fetchObject($res) ;
				}
			} else if ($rr) {
				if ($rr->rc_namespace != NS_USER_KUDOS) {
					$this->filterRC($widget, $count, $rr);
				} else if (($rr->rc_namespace == NS_USER_KUDOS) && ($kudos_count < $kudos_limit)) {
					$this->filterRC($widget, $count, $rr);
					$kudos_count++;
				}
				$rr = $dbr->fetchObject($res) ;
			} else if ($rl) {
				if ($rl->log_action != 'patrol') {
					$this->filterLog($widget, $count, $rl);
				} else if (($rl->log_action == 'patrol') && ($patrol_count < $patrol_limit)) {
					if (($patrol_prevUser != $rl->log_user) || ($patrol_prevTitle != $rl->log_title)) {
						$this->filterLog($widget, $count, $rl);
					}
					$patrol_prevUser = $rl->log_user;
					$patrol_prevTitle = $rl->log_title;
					$patrol_count++;
				}
				$rl = $dbr->fetchObject($logres) ;
			} else {
				break;
			}
		}
		$dbr->freeResult($res);
		$dbr->freeResult($logres);

		$jsFunc = $wgRequest->getVal('function', '');
		if ($jsFunc) {
			print $jsFunc . '( ' . json_encode($widget) . ' );';
		} else {
			print json_encode($widget);
		}

	}
}



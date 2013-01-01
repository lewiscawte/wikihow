<?php

class RCWidget extends UnlistedSpecialPage {

	private static $mBots = null;

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RCWidget' );
		$wgHooks['allowMaxageHeaders'][] = array('RCWidget::allowMaxageHeadersCallback');
	}

	public static function getDTDifferenceString($date, $isUnixTimestamp = false) {
		wfLoadExtensionMessages('RCWidget');
		if (empty($date)) {
			return "No date provided";
		}

		if ($isUnixTimestamp) {
			$unix_date = $date;
		} else {
			$date = $date . " UTC";
			$unix_date = strtotime($date);
		}

		$now = time();
		$lengths = array("60","60","24","7","4.35","12","10");

		// check validity of date
		if (empty($unix_date)) {
			return "Bad date: $date";
		}

		// is it future date or past date
		if ($now > $unix_date) {
			$difference = $now - $unix_date;
			$tenseMsg = 'rcwidget_time_past_tense';
		} else {
			$difference = $unix_date - $now;
			$tenseMsg = 'rcwidget_time_future_tense';
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);

		if ($difference != 1) {
			$periods = array(wfMsg("second-plural"), wfMsg("minute-plural"), wfMsg("hour-plural"), wfMsg("day-plural"), 
						wfMsg("week-plural"), wfMsg("month-plural"), wfMsg("year-plural"), wfMsg("decade-plural"));
		} else {
			$periods = array(wfMsg("second"), wfMsg("minute"), wfMsg("hour"), wfMsg("day"), 
						wfMsg("week"), wfMsg("month"), wfMsg("year"), wfMsg("decade"));
		}

		return wfMsg($tenseMsg, $difference, $periods[$j]);
	}

	private static function addRCElement(&$widget, &$count, $obj) {
		if ((strlen(strip_tags($obj['text'])) < 100) &&
			 (strlen($obj['text']) > 0)) {
			$widget[$count++] = $obj;
		}
	}

	private static function getBotIDs() {
		if (!is_array(self::$mBots)) {
			self::$mBots = User::getBotIDs();
		}
		return self::$mBots; 
	}

	private static function filterLog(&$widget, &$count, $row) {

		$bots = self::getBotIDS();
 		if (in_array($row->log_user, $bots)) {
			return;
		}
	
		$obj = "";
		$u = new User;
		$u = $u->newFromId($row->log_user);
		$real_user = $u->getName();

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$real_user)){
			$wuser = wfMsg('rcwidget_anonymous_visitor');;
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $real_user;
			$wuserLink = '/User:'.$real_user;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->log_title)){
			$destUser = wfMsg('rcwidget_anonymous_visitor');;
			$destUserLink = '/User:'.$row->log_title;
		} else {
			$destUser = $row->log_title;
			$destUserLink = '/'.$row->log_title;
		}

		switch ($row->log_type) {
			case 'patrol':

			$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
			if ($row->log_namespace == NS_USER) {
					$obj['type'] = 'patrol';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/User:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_patrolled', $userLink, $resourceLink);
				} else if ($row->log_namespace == NS_USER_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/User_talk:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_patrolled', $userLink, $resourceLink);
				} else if ($row->log_namespace == NS_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/Discussion:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_patrolled', $userLink, $resourceLink);
				} else if ($row->log_namespace == NS_MAIN) {
					$obj['type'] = 'patrol';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_patrolled', $userLink, $resourceLink);
				}
				self::addRCElement($widget, $count, $obj);
				break;
			case 'nap':
				$obj['type'] = 'nab';
				$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
				$userLink  = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$resourceLink = '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
				$obj['text'] = wfMsg('action_boost', $userLink, $resourceLink);
				self::addRCElement($widget, $count, $obj);
				break;
			case 'upload':
				if ( ($row->log_action == 'upload') && ($row->log_namespace == 6)) {
					$obj['type'] = 'image';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					if (strlen($row->log_title) > 25) {
						$resourceLink = '<a href="/Image:'.$row->log_title.'">'.substr($row->log_title,0,25).'...</a>';
					} else {
						$resourceLink = '<a href="/Image:'.$row->log_title.'">'.$row->log_title.'</a>';
					}
					$obj['text'] = wfMsg('action_image', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case 'vidsfornew':
				if ( ($row->log_action == 'added') && ($row->log_namespace == 0)) {
					$obj['type'] = 'video';
					$obj['ts'] = self::getDTDifferenceString($row->log_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
					$obj['text'] = wfMsg('action_addedvideo', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;
		}
	}

	private static function filterRC(&$widget, &$count, $row) {
		$bots = self::getBotIDS();
 		if (in_array($row->rc_user, $bots)) {
			return;
		}
	
		$obj = "";
		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_user_text)){
			$wuser = wfMsg('rcwidget_anonymous_visitor');;
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $row->rc_user_text;
			$wuserLink = '/User:'.$row->rc_user_text;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_title)){
			$destUser = wfMsg('rcwidget_anonymous_visitor');;
			$destUserLink = '/User:'.$row->rc_title;
		} else {
			$destUser = $row->rc_title;
			$destUserLink = '/'.$row->rc_title;
		}

		switch ($row->rc_namespace) {
			case NS_MAIN: //MAIN
				if (preg_match('/^New page:/',$row->rc_comment)) {
					$obj['type'] = 'newpage';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_newpage', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				} else if (preg_match('/^categorization/',$row->rc_comment)) {
					$obj['type'] = 'categorized';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_categorized', $userLink, $resourceLink);;
					self::addRCElement($widget, $count, $obj);
				} else if ( (preg_match('/^\/* Steps *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Tips *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Warnings *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Things You\'ll Need *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Ingredients *\//',$row->rc_comment)) ||
								(preg_match('/^$/',$row->rc_comment)) ||
								(preg_match('/^Quick edit/',$row->rc_comment)) ) {
					$obj['type'] = 'edit';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] .= wfMsg('action_edit', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_TALK: //DISCUSSION
				if (!preg_match('/^Reverts edits by/',$row->rc_comment)) {
					if (preg_match('/^Marking new article as a Rising Star from From/',$row->rc_comment)) {
						$obj['type'] = 'risingstar';
						$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
						$userLink= '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
						$obj['text'] = wfMsg('action_risingstar', $userLink, $resourceLink);
					} else if ($row->rc_comment == '') {
						$obj['type'] = 'discussion';
						$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
						$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$resourceLink = '<a href="/Discussion:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
						$obj['text'] = wfMsg('action_discussion', $userLink, $resourceLink);
					}
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_TALK: //USER_TALK
				if (!preg_match('/^Revert/',$row->rc_comment)) {
					$obj['type'] = 'usertalk';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="/User_talk:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_usertalk', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_KUDOS: //KUDOS
				$obj['type'] = 'kudos';
				$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
				$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$resourceLink = '<a href="/User_kudos:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				$obj['text'] = wfMsg('action_fanmail', $userLink, $resourceLink);
				self::addRCElement($widget, $count, $obj);
				break;
			case NS_VIDEO: //VIDEO
				// I KNOW I HAVE VIDEO FOR BOTH RC & LOGGING. LOGGING ONLY DOESN'T SEEM TO CATCH EVERYTHING.
				if (preg_match('/^adding video/',$row->rc_comment)) {
					$obj['type'] = 'video';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMsg('action_addedvideo', $userLink, $resourceLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_SPECIAL: //OTHER
				if (preg_match('/^New user/',$row->rc_comment)) {
					$obj['type'] = 'newuser';
					$obj['ts'] = self::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="/User:'.$row->rc_user_text.'">'.$wuser.'</a>';
					$obj['text'] = wfMsg('action_newuser', $userLink);
					self::addRCElement($widget, $count, $obj);
				}
				break;

		}

		return $obj;
	}

	public static function showWidget() {
?>
	<script type="text/javascript" >
		var rc_URL = '/Special:RCWidget';
		var rc_ReloadInterval = 60000;

		$(window).load(rcwLoad);
	</script>

	<div id='rcwidget_divid'>
		<a class="rc_help rcw-help-icon" title="<?php echo wfMsg('rc_help');?>" href="/<?= wfMsg('rcchange-patrol-article') ?>"></a>
		<h3><span class="weather" onclick="location='/index.php?title=Special:Recentchanges&hidepatrolled=1';" style="cursor:pointer;"></span><span onclick="location='/Special:Recentchanges';" style="cursor:pointer;"><?= wfMsg('recentchanges');?></span></h3>
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

	public function execute($par) {
		global $wgOut, $wgRequest;
		wfLoadExtensionMessages('RCWidget');

		$maxAgeSecs = 60;
		$wgOut->setSquidMaxage( $maxAgeSecs );
		$wgRequest->response()->header( 'Cache-Control: s-maxage=' . $maxAgeSecs . ', must-revalidate, max-age=' . $maxAgeSecs );
		$future = time() + $maxAgeSecs;
		$wgRequest->response()->header( 'Expires: ' . gmdate('D, d M Y H:i:s T', $future) );

		$wgOut->setArticleBodyOnly(true);
		$wgOut->sendCacheControl();

		$data = self::pullData();
		$jsonData = json_encode($data);
		$jsFunc = $wgRequest->getVal('function', '');
		if ($jsFunc) {
			print $jsFunc . '( ' . $jsonData . ' );';
		} else {
			print $jsonData;
		}
	}

	/**
	 *
	 * 
	 */
	public static function getLastPatroller(&$dbr){
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "SELECT log_user, log_timestamp FROM logging WHERE log_type='patrol' ORDER BY log_timestamp DESC LIMIT 1";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		$rcuser = array();
		$rcuser['id'] = $row->log_user;
		$rcuser['date'] = wfTimeAgo($row->log_timestamp);

		return $rcuser;
	}

	public static function getTopPatroller(&$dbr, $period='7 days ago'){
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "SELECT log_user, count(log_user) as rc_count, log_timestamp FROM logging WHERE log_type='patrol' and log_timestamp >= '" . $starttimestamp . "' GROUP BY log_user ORDER BY rc_count DESC";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		$rcuser = array();
		$rcuser['id'] = $row->log_user;
		$rcuser['date'] = wfTimeAgo($row->log_timestamp);

		return $rcuser;
	}

	private static function pullData() {
		global $wgMemc;

		$cachekey = wfMemcKey('rcwidget');

		// for logged in users whose requests bypass varnish, this data is
		// cached for $cacheSecs
		$cacheSecs = 15;

		$widget = $wgMemc->get($cachekey);
		if ($widget !== null) {
			return $widget;
		}

		$widget = array();

		$dbr = wfGetDB(DB_SLAVE);

		$cutoff_unixtime = time() - ( 30 * 86400 ); // 30 days
		$cutoff = $dbr->timestamp( $cutoff_unixtime );
		$currenttime = $dbr->timestamp( time() );

		// QUERY RECENT CHANGES TABLE
		$sql = "SELECT rc_timestamp,rc_user_text,rc_namespace,rc_title,rc_comment,rc_patrolled FROM recentchanges ";
		if (sizeof($bots) > 0) {
			$sql .= "WHERE rc_user NOT IN (" . implode(',', $bots) . ") ";
		}
		$sql .= "ORDER BY rc_timestamp DESC " ;
		$sql = $dbr->limitResult($sql, 200, 0);
		$res = $dbr->query( $sql, __METHOD__ );

		// QUERY LOGGING TABLE
		$logsql = "SELECT log_id,log_timestamp,log_user,log_namespace,log_title,log_comment,log_type,log_action ".
			"FROM logging "
			//"WHERE log_type in ('patrol','newusers','nap') ".
			;
		$logsql .= " ORDER BY log_id DESC" ;
		
		// other WHERE 'upload'
		$logsql = $dbr->limitResult($logsql, 200, 0);
		$logres = $dbr->query( $logsql, __METHOD__ );

		$count = 0;
		$widget['servertime'] = $currenttime;

		// MERGE TABLES and FILTER RESULTS
		$rl = $dbr->fetchObject($logres) ;
		$rr = $dbr->fetchObject($res) ;
		$patrol_limit = 5;
		$patrol_count = 0;
		$patrol_prevUser = "";
		$patrol_prevTitle = "";
		$kudos_count = 0;
		$kudos_limit = 3;
		while (true) {

			if ($rr && $rl) {
				if ($rl->log_timestamp > $rr->rc_timestamp) {
					if ($rl->log_action != 'patrol') {
						self::filterLog($widget, $count, $rl);
					} else if (($rl->log_action == 'patrol') && ($patrol_count < $patrol_limit)) {
						if (($patrol_prevUser != $rl->log_user) || ($patrol_prevTitle != $rl->log_title)) {
							self::filterLog($widget, $count, $rl);
						}
						$patrol_prevUser = $rl->log_user;
						$patrol_prevTitle = $rl->log_title;
						$patrol_count++;
					}
					$rl = $dbr->fetchObject($logres) ;
				} else {
					if ($rr->rc_namespace != NS_USER_KUDOS) {
						self::filterRC($widget, $count, $rr);
					}else if (($rr->rc_namespace == NS_USER_KUDOS) && ($kudos_count < $kudos_limit)) {
						self::filterRC($widget, $count, $rr);
						$kudos_count++;
					}
					$rr = $dbr->fetchObject($res) ;
				}
			} else if ($rr) {
				if ($rr->rc_namespace != NS_USER_KUDOS) {
					self::filterRC($widget, $count, $rr);
				} else if (($rr->rc_namespace == NS_USER_KUDOS) && ($kudos_count < $kudos_limit)) {
					self::filterRC($widget, $count, $rr);
					$kudos_count++;
				}
				$rr = $dbr->fetchObject($res) ;
			} else if ($rl) {
				if ($rl->log_action != 'patrol') {
					self::filterLog($widget, $count, $rl);
				} else if (($rl->log_action == 'patrol') && ($patrol_count < $patrol_limit)) {
					if (($patrol_prevUser != $rl->log_user) || ($patrol_prevTitle != $rl->log_title)) {
						self::filterLog($widget, $count, $rl);
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

		$count = self::getUnpatrolledEdits($dbr);
		$widget['unpatrolled'] = $count;

		$wgMemc->set($cachekey, $widget, $cacheSecs);

		return $widget;
	}

	public static function getUnpatrolledEdits(&$dbr) {
		// Query table for unpatrolled edits
		$count = $dbr->selectField('recentchanges',
                array('count(*)'),
                array('rc_patrolled=0'));
		return $count;
	}
		
	public static function allowMaxageHeadersCallback() {
		return false;
	}

}



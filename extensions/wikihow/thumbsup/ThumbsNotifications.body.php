<?

class ThumbsNotifications extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ThumbsNotifications' );
	}

	function execute($par) {
		global $wgUser, $wgOut;

		/*
		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'prefsnologintext' );
			return;
		}
		*/

		$dbw = wfGetDB(DB_MASTER);
		$plusSixHrs = wfTimestampNow() + 6 * 60 * 60;
		$plusSixHrs = wfTimestamp(TS_DB, $plusSixHrs);
		$sql = "UPDATE thumbs_notifications SET tn_last_notified = '$plusSixHrs' WHERE tn_rev_id = " . mysql_real_escape_string($par);
		$result = $dbw->query($sql);

		$wgOut->setArticleBodyOnly(true);
		echo json_encode($result);
	}

	function getNotificationsHTML() {
		global $wgUser;
		$notifications = self::getNotifications($wgUser->getName());
		$js = "<script type='text/javascript' src='" . wfGetPad('/extensions/wikihow/thumbsup/thumbsnotifications.js?') . WH_SITEREV . "'></script>\n";
		return self::formatNotifications($notifications) . $js;
	}

	function getNotifications($userText) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$currentTime = wfTimestamp(TS_DB);
		$oldTime = wfTimestamp() - 6 * 60 * 60;
		$oldTime = wfTimestamp(TS_DB, $oldTime);


		$sql = "
		SELECT 
			GROUP_CONCAT(thumb_giver_text SEPARATOR ',')  AS givers, 
			thumb_rev_id, 
			page_id
		FROM 
			thumbs, 
			page
		WHERE 
			thumb_timestamp > '$oldTime'  AND 
			thumb_recipient_text = '" . $userText . "' AND 
			thumb_page_id = page_id AND
			EXISTS (
				SELECT * 
				FROM 
					thumbs_notifications
				WHERE 
					thumb_rev_id = tn_rev_id AND 
					tn_last_notified <= '$currentTime' AND 
					tn_last_thumbed > '$oldTime'
			)
		GROUP BY 
			thumb_rev_id
		ORDER BY 
			MAX(thumb_timestamp) DESC";

		$res = $dbr->query($sql);

		$notifications = array();
		while($row = $dbr->fetchObject($res)) {
			$notification = array();
			$notification['revid'] =  $row->thumb_rev_id;
			$notification['givers'] = $row->givers;
			$notification['pageid'] = $row->page_id;
			$notifications[] = $notification;
		}
		$dbr->freeResult($res);

		return $notifications;
	}

	function formatNotifications(&$notifications) {
		$html = "";
		foreach ($notifications as $notification) {
			$revId = $notification['revid'];
			$pageId = $notification['pageid'];
			$diffLink = self::formatDiffLink($pageId, $revId);
			$pageLink = self::formatPageLink($pageId);
			$givers = self::formatGivers($notification['givers']);

			$htmlDiv = "<span class='th_close_outer'><a class='th_close' id='$revId'></a></span><div class='th_content'>";
			$htmlDiv .= wfMsg('th_notification', $givers, $diffLink, $pageLink) . "</div>";

			$html .= "
			<span id='th_msg_$revId'>
				<div class='message_box'>
					$htmlDiv
				</div>
			</span>";
		}
		return $html;
	}

	function formatDiffLink($pageId, $revId, $label='edit') {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$diff = "";
		if ($t->getArticleId() > 0) {
			$diff = $sk->makeKnownLinkObj($t, $label, 'diff=' . $revId . '&oldid=PREV');
		}
		return $diff;
	}

	function formatPageLink($pageId) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$page = "";
		if ($t->getArticleId() > 0) {
			$page = "<a href='{$t->getPartialURL()}'>{$t->getFullText()}</a>";
		}
		return $page;
	}

	function formatGivers(&$giversTxt) {
		$givers = array_reverse(explode(",", $giversTxt));
		$numGivers = count($givers);
		$giversToDisplay = 2;
		
		if ($numGivers == 1) {
			$txt .= self::getAvatarLink($givers[0]);
		} 
		elseif ($numGivers == 2) {
			$txt .= self::getAvatarLink($givers[0]) . " and " . self::getAvatarLink($givers[1]);
		}
		elseif ($numGivers > 2) {
			$remaining = $numGivers - $giversToDisplay;
			$txt .= self::getAvatarLink($givers[0]) . ", " . self::getAvatarLink($givers[1]) . " and ";
			for ($i = 2; $i < $numGivers; $i++) {
				$txt .= self::getAvatarLink($givers[$i], false) . " ";
			}
			$txt .= "$remaining other ";
			$txt .= $remaining > 1 ? "people" : "person";
		}
		return $txt;
	}

	function getTalkPageLink(&$userText) {
		global $wgUser, $wgServer;
		$uTalkPage = $userText;
		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();	
			if ($t) {
				$sk = $wgUser->getSkin();
				$uTalkPage = $sk->makeKnownLinkObj($t, $userText);
			}
		}
		return $uTalkPage;
	}

	function getAvatarLink(&$userText, $showText = true) {
		global $wgUser;
		$uTalkPage = "<img class='th_avimg' src='" . Avatar::getAvatarUrl($userText) . "'/>";
		//if (!$showText) {
			$uTalkPage .= "<span>Hi, I'm $userText</span>";
		//}
		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();	
			if ($t) {
				$sk = $wgUser->getSkin();
				$uTalkPage = $sk->makeKnownLinkObj($t, $uTalkPage, '', '', '', 'class="th_tooltip" title=""', ' ');
				if ($showText) {
					$uTalkPage .= " " . $sk->makeKnownLinkObj($t, $userText, '', '', '', 'title=""', ' ');
				}
			}
		}
		return $uTalkPage;
	}
}

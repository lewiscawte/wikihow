<?

class ThumbsNotifications extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ThumbsNotifications' );
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		/*
		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'prefsnologintext' );
			return;
		}
		*/

		$dbw = wfGetDB(DB_MASTER);
		$revId = intval($wgRequest->getVal('rev'));
		$giverIds = $dbw->strencode($wgRequest->getVal('givers'));
		$sql = "UPDATE thumbs SET thumb_notified = 1 WHERE thumb_rev_id = $revId and thumb_giver_id IN ($giverIds)";
		$result = $dbw->query($sql);

		$wgOut->setArticleBodyOnly(true);
		echo json_encode($result);
	}

	function getNotificationsHTML() {
		global $wgUser;
		$notifications = self::getNotifications($wgUser->getName());
		$js = HtmlSnips::makeUrlTags('js', array('thumbsnotifications.js'), 'extensions/wikihow/thumbsup', THUMBSUP_DEBUG);
		return self::formatNotifications($notifications) . $js;
	}

	function getNotifications($userText) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$currentTime = wfTimestamp(TS_DB);
		$oldTime = wfTimestamp() - 30 * 24 * 60 * 60;
		$oldTime = wfTimestamp(TS_DB, $oldTime);
		$userText = mysql_real_escape_string($userText);

		$sql = "
			SELECT 
				GROUP_CONCAT(thumb_giver_text SEPARATOR ',')  AS givers, 
				GROUP_CONCAT(thumb_giver_id SEPARATOR ',')  AS giver_ids, 
				thumb_rev_id, 
				page_id
			FROM 
				thumbs, 
				page
			WHERE 
				thumb_recipient_text = '" . $userText . "' AND 
				thumb_timestamp > '$oldTime'  AND 
				thumb_notified = 0 AND
				thumb_page_id = page_id
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
			$notification['giver_ids'] = $row->giver_ids;
			$notification['pageid'] = $row->page_id;
			$notifications[] = $notification;
		}
		$dbr->freeResult($res);

		return $notifications;
	}

	function formatNotifications(&$notifications) {
		$html = "";
		$count = 1;
		foreach ($notifications as $notification) {
			$revId = $notification['revid'];
			$pageId = $notification['pageid'];
			$diffLink = self::formatDiffLink($pageId, $revId);
			$pageLink = self::formatPageLink($pageId);
			$givers = self::formatGivers($notification['givers']);
			$shareHtml = self::formatSharing();
			$htmlDiv = "<span class='th_close_outer'><a class='th_close' id='$revId'></a></span> $shareHtml <div class='th_content'>";
			$htmlDiv .= wfMsg('th_notification', $givers, $diffLink, $pageLink) . "</div>";

			$html .= "
			<span id='th_msg_$revId'>
				<div class='message_box th_message_box'>
						$htmlDiv
				</div>
				<div class='th_giver_ids'>{$notification['giver_ids']}</div>
			</span>";
			
			// only show a max of 5 thumbs up notifications at a time
			if (++$count == 5) break;
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
			$page = $sk->makeKnownLinkObj($t, $t->getFullText(), '', '', '', 'class="th_t_url" ');
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

	function getAvatarLink(&$userText, $showText = true) {
		global $wgUser;
		$uTalkPage = "<img class='th_avimg' src='" . Avatar::getAvatarUrl($userText) . "'/>";
		$uTalkPage .= "<span class='tooltip_span'>Hi, I'm $userText</span>";

		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();	
			if ($t) {
				$sk = $wgUser->getSkin();
				$uTalkPage = $sk->makeKnownLinkObj($t, $uTalkPage, '#post', '', '', 'class="tooltip" title=""', ' ');
				if ($showText) {
					$uTalkPage .= " " . $sk->makeKnownLinkObj($t, $userText, '#post', '', '', 'title=""', ' ');
				}
			}
		}
		return $uTalkPage;
	}
	function formatSharing() {
		$html = "<span class='th_sharing'> share on: ";
		$html .= "<span class='th_sharing_icon th_facebook'></span>";
		$html .= "<span class='th_sharing_icon th_twitter'></span>";
		$html .= "</span>";
		return $html;
	}
}

<?

class ThumbsUp extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'ThumbsUp' );
	}
	
	function execute($par) {
		global $wgOut, $wgRequest;

		$fname = 'ThumbsUp::execute';
		wfProfileIn( $fname );

		$revOld = $wgRequest->getVal('revold');
		$revNew = $wgRequest->getVal('revnew');
		$pageId = $wgRequest->getVal('pageid');
		
		$retVal = false;
		if (intval($revOld) && intval($revNew) && intval($pageId)) {
			self::thumbMultiple($revOld, $revNew, $pageId);
			$retVal = true;
		}
		
		$wgOut->setArticleBodyOnly(true);
		echo json_encode($retVal);

		wfProfileOut( $fname );
	}

	static function quickNoteThumb($revOld, $revNew, $pageId, $recipientText) {
		global $wgUser; 

		$fname = 'ThumbsUp::quickNoteThumb';
		wfProfileIn( $fname );
		
		$u = User::newFromName($recipientText);
		$recipientId = (is_object($u)) ? $u->getId() : 0;

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('revision',
			array('rev_id, rev_user, rev_user_text'),
			array("rev_id>" . $revOld, "rev_id<=" . $revNew, "rev_page" => $pageId, "rev_user" => $recipientId, "rev_user_text" => $recipientText));

		$recipients  = array();
		if ($row = $dbr->fetchObject($res)) {
			self::thumb($row->rev_id, $row->rev_user, $row->rev_user_text, $pageId, false);
		}

		wfProfileOut( $fname );
	}

	function thumbMultiple($revOld, $revNew, $pageId) {
		global $wgUser; 

		$fname = 'ThumbsUp::thumbMultiple';
		wfProfileIn( $fname );

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('revision',
			array('rev_id, rev_user, rev_user_text'),
			array("rev_id>" . $revOld, "rev_id<=" . $revNew, "rev_page" => $pageId),
			array("ORDER BY"=>"rev_id"));

		$recipients  = array();
		$recipient = $row->rev_user_text;
		while ($row = $dbr->fetchObject($res)) {
			// Only give one thumb up in the case of multiple intermediary revisions for RC Patrol
			if (!in_array($recipient, $recipients)) {
				self::thumb($row->rev_id, $row->rev_user, $row->rev_user_text, $pageId);
			}
			$recipients[] = $recipient;
		}

		wfProfileOut( $fname );
	}

	/*
	* NAB thumbs up are a little different. We just want to give a thumb up to the first revision
	* ie the edit that created the article
	*/
	function thumbNAB($revOld, $revNew, $pageId) {
		$fname = 'ThumbsUp::thumbNAB';
		wfProfileIn( $fname );

		$minRev = self::getFirstArticleRevision($pageId);
		self::thumbMultiple(-1, $minRev, $pageId);

		wfProfileOut( $fname );
	}


	function getFirstArticleRevision($pageId) {
		$fname = 'ThumbsUp::getFirstArticleRevision';
		wfProfileIn( $fname );

		$dbr = wfGetDB(DB_SLAVE);
		$minRev = $dbr->selectField('revision', array('min(rev_id)'), array("rev_page" => $pageId));

		wfProfileOut( $fname );

		return $minRev;
	}

	function thumb($revisionId, $thumbRecipientId, $thumbRecipientText, $pageId, $sendNotification = false) {
		global $wgUser;

		$fname = 'ThumbsUp::thumb';
		wfProfileIn( $fname );

		$dbr = wfGetDB(DB_SLAVE);

		/*
		Thumb for:
		- revision authors who have accounts (not anons)
		- thumb givers that are logged in
		- revisions that are not already thumbed by the current giver/user
		- revisions that aren't authored by the giver/user
		*/ 
		if($wgUser->isLoggedIn() && !self::isThumbedByCurrentUser($revisionId) && $wgUser->getID() != $thumbRecipientId && self::isThumbableTitle($pageId)) {
			$wgUserName = $wgUser->getName();
			$dbw = wfGetDB(DB_MASTER);
			$now = wfTimestamp(TS_DB);

			// Add a row to the thumbs table for detailed info on who gave thumb, who received, etc
			$dbw->insert('thumbs', array('thumb_giver_id' => $wgUser->getID(), 'thumb_giver_text' => $wgUserName, 
					'thumb_recipient_id'=>$thumbRecipientId, 'thumb_recipient_text'=>$thumbRecipientText, 'thumb_rev_id'=>$revisionId,
					'thumb_page_id'=>$pageId, 'thumb_timestamp'=>$now));

			// Update thumbs up counts for the profilebox table. Do it only once in the case where a recipient might have multiple
			// edits in this rc patrol item
			if ($thumbRecipientId > 0) {
				$sql = "INSERT INTO profilebox (pb_user, pb_thumbs_given) VALUES (" . $wgUser->getID() .", 1) ";
				$sql .= "ON DUPLICATE KEY UPDATE pb_thumbs_given=pb_thumbs_given + 1";
				$res = $dbw->query($sql);
				$sql = "INSERT INTO profilebox (pb_user, pb_thumbs_received) VALUES (" . mysql_real_escape_string($thumbRecipientId) . ", 1) ";
				$sql .= "ON DUPLICATE KEY UPDATE pb_thumbs_received=pb_thumbs_received + 1";
				$res = $dbw->query($sql);
			}
			
			$t = Title::newFromID($pageId);
			if (is_object($t)) {
				// Add a log entry
				wfLoadExtensionMessages('ThumbsUp');
				$revisionLinkName = "r$revisionId";
				$revisionLink = $t->getFullURL() . "?oldid=$revisionId&diff=prev";
				$log = new LogPage('thumbsup', false);
				$log->addEntry('', $t, wfMsgHtml('thumbslogentry', $thumbRecipientText, $revisionLink, $revisionLinkName, $t->getFullText()));

				// Send a talk page message and email if the pref is set and, in the case of multiple revisions,
				// only send a single talk page and email message per recipient
				$thumbsTalkOption = self::getThumbsTalkOption($thumbRecipientId);
				if ($sendNotification && $thumbsTalkOption === 0) {
					self::notifyUserOfThumbsUp($t, $thumbRecipientId, $revisionLink, $revisionId, $thumbRecipientText); 
				}
			}
		}
		wfProfileOut( $fname );
	}

	// Returns 0 if preference is set, 1 if preference isn't set
	static function getThumbsTalkOption($thumbRecipientId) {
		if ($thumbRecipientId > 0) {
			$u = User::newFromId($thumbRecipientId);
			$thumbsTalkOption = $u->getOption('thumbsnotifications');
			// If the option hasn't been initialized yet, set it to on (0) by default
			if ($thumbsTalkOption === '') {
				$u->setOption('thumbsnotifications', 0);
				$u->saveSettings();
				$thumbsTalkOption = 0;
			}
		}
		else {
			// Always send a talk page notification for anons
			$thumbsTalkOption = 0;
		}
		return intVal($thumbsTalkOption);
	}

	// Returns 0 if preference is set, 1 if preference isn't set
	static function getEmailOption($thumbRecipientId) {
		if ($thumbRecipientId > 0) {
			$u = User::newFromId($thumbRecipientId);
			$thumbsEmailOption = $u->getOption('thumbsemailnotifications');
			// If the option hasn't been initialized yet, set it to on (0) by default
			if ($thumbsTalkOption === '') {
				$u->setOption('thumbsemailnotifications', 0);
				$u->saveSettings();
				$thumbsEmailOption = 0;
			}
		}
		else {
			// Never send an email for anons
			$thumbsEmailOption = 1;
		}
		return intVal($thumbsEmailOption);
	}

	function isThumbedByCurrentUser($revisionId) {
		global $wgUser;

		$fname = 'ThumbsUp::isThumbedByCurrentUser';
		wfProfileIn( $fname );

		$dbr = wfGetDB(DB_SLAVE);
		$thumb_rev_id = $dbr->selectField("thumbs", array("thumb_rev_id"), array("thumb_rev_id" => $revisionId, "thumb_giver_id" => $wgUser->getID()));

		wfProfileOut( $fname );

		return $thumb_rev_id > 0; 
	}

	
	function notifyUserOfThumbsUp($t, $recipientId, $diffUrl, $revisionId, $recipientText) {
		global $wgUser, $wgLang;

		$fname = 'ThumbsUp::notifyUserOfThumbsUp';
		wfProfileIn( $fname );

		$user = $wgUser->getName();
		$real_name = User::whoIsReal($wgUser->getID());
		if ($real_name == "") {
			$real_name = $user;
		}

		$dateStr = $wgLang->timeanddate(wfTimestampNow());
		$text = "";
		$article = "";
		if ($recipientId > 0) {
			$u = User::newFromId($recipientId);
			$user_talk = $u->getTalkPage();
		} else {
			$u = null;
			$user_talk = Title::makeTitle( NS_USER_TALK, $recipientText );
		}

		$comment = wfMsg('thumbs_talk_msg', $diffUrl, $t->getText());
		$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);

		if ($user_talk->getArticleId() > 0) {
			$r = Revision::newFromTitle($user_talk);
			$text = $r->getText();
		}

		$article = new Article($user_talk);
		$text .= "\n\n$formattedComment\n\n";
		$article->doEdit($text, wfMsg('thumbs-up-usertalk-editsummary'));

		// Auto patrol talk page messages to not anger the rc patrollers with thumbs up chatter
		self::autoPatrolTalkMessage($user_talk->getArticleId());


		if ($recipientId > 0) {
			// Send thumbs email notification (only if option set)
			//$thumbsEmailOption = $u->getOption('thumbsemailnotifications');

			// jsmall pref Ignore preference for right now and always send an email. Uncomment above when ready to use preference
			$thumbsEmailOption = 0;

			// If the option hasn't been initialized yet, set it to 1 by default
			if ($thumbsEmailOption === '') {
				$u->setOption('thumbsemailnotifications', 0);
				$u->saveSettings();
				$thumbsEmailOption = 0;
			}
			if ($thumbsEmailOption === 0) {
				$track_title = '?utm_source=thumbsup_message&utm_medium=email&utm_term=title_page&utm_campaign=talk_page_message';
				$track_diff = '&utm_source=thumbsup_message&utm_medium=email&utm_term=diff_page&utm_campaign=talk_page_message';
				$diffHref = "<a href='$diffUrl$track_diff'>edit</a>";
				$titleHref = "<a href='" . $t->getFullURL() . "$track_title'>" . $t->getText() . "</a>";
				$emailComment = wfMsg('thumbs_talk_email_msg', $diffHref, $titleHref);
				AuthorEmailNotification::notifyUserTalk($user_talk->getArticleId(), $wgUser->getID() ,$emailComment, 'thumbsup');
			}
		}

		wfProfileOut( $fname );
	}
	
	function autoPatrolTalkMessage($talkPageArticleId) {
		global $wgUser; 

		$fname = 'ThumbsUp::autoPatrolTalkMessage';
		wfProfileIn( $fname );


		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('recentchanges', 
			array('rc_patrolled'=>1), 
			array('rc_user'=>$wgUser->getID(), 'rc_cur_id'=>$talkPageArticleId, 'rc_comment'=>wfMsg('thumbs-up-usertalk-editsummary')), 
			"autoPatrolTalkMessage", 
			array("ORDER BY" => "rc_id DESC", "LIMIT"=>1));

		wfProfileOut( $fname );
	}
	
	function isThumbableTitle($articleId) {
		$t = Title::newFromID($articleId);
		return $t->getNamespace() == NS_MAIN;
	}
	
	/*****************************
	** getThumbsUpButton()
	**
	** required results values:
	** - $result['new'] = the revision id
	** - $result['old'] = previous id [$r->getPrevious()->getID()];
	** - $result['title'] = title object;		
	********************************/
	function getThumbsUpButton(&$result, $diffPage = false) {
		global $wgUser;
		wfLoadExtensionMessages('RCPatrol');
		$link = "";
		$r = Revision::newFromId($result['new']);
		$t = $result['title'];
		$diffClass = $diffPage ? ' th_diff' : '';
		if (class_exists('ThumbsUp') && wfMsg('thumbs_feature') == 'on') {
			// Don't show a thumbs up if the user has already given a thumb to the most recent revision
			if (ThumbsUp::isThumbedByCurrentUser($result['new'])) {
				$link = "<input type='button' class='button thumbbutton alreadyThumbed $diffClass'/>";
			}
			else if ($result['vandal'] != 1 && $wgUser->getID() != $r->getUser() && $t->getNamespace() == NS_MAIN) {
				/*
				Show a thumbs up button for:
				- NS_MAIN titles only
				- non-anon revision authors who have accounts
				- thumb givers that are logged in
				- revisions that are not already thumbed by the current giver/user
				- revisions that aren't authored by the current giver/user
				- revisions that don't appear to be vandalism
				*/ 
				$link = "<input type='button' title='" . wfMsg('rcpatrol_thumb_title') . "' accesskey='t' class='button thumbbutton $diffClass'/>";
				$link  .= "<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/thumbsup/thumbsup.js?') . WH_SITEREV . "'></script>";
				$link .= "<div id='thumbUp'>/Special:ThumbsUp?revold=" . $result['old'] . "&revnew=" . $result['new'] .  "&pageid=" . $t->getArticleID() . "</div>";
				$langKeys = array('rcpatrol_thumb_msg_pending', 'rcpatrol_thumb_msg_complete');
				$link .=  WikiHow_i18n::genJSMsgs($langKeys);
			} else {
				// Display a disabled thumb up button. This isn't an article that can be thumbed up
				$link = "<input type='button' class='button thumbbutton disabledThumb $diffClass'/>";
			}
		}
		return $link;
	}
}

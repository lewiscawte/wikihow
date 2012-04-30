<?

class AuthorEmailNotification extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'AuthorEmailNotification' );

	}

	/**************************************
	 *
	 *
	 **************************************/
	function addNotification($article, $email = '') {
		global $wgUser;

		$t = Title::newFromText( $article );
		$aid = $t->getArticleID();

		if (($wgUser->getID() > 0) && ($aid != 0)) {
			if ($wgUser->getEmail() != '') {
				$this->addUserWatch($aid, 1);
			} else {
				if ($email != '') {
					$wgUser->setEmail( $email );
					$wgUser->saveSettings();
					$this->addUserWatch($aid, 1);
				}
			}
		}
	}

	/**************************************
	 *
	 *
	 **************************************/
	function reassignArticleAnon($articleid) {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);
		$rev_id  = $dbw->selectField('revision', 'rev_id', array('rev_page=' . $articleid, 'rev_user_text=\'' . wfGetIP() .'\'' ));

		if ($rev_id != '') {
			wfDebug("AXXX: reassinging {$rev_id} to {$wgUser->getName()}\n");
			$ret = $dbw->update('revision', 
				array('rev_user_text' => $wgUser->getName(), 'rev_user' => $wgUser->getID() ), 
				array('rev_id' => $rev_id) );
			$ret = $dbw->update('recentchanges', 
				array('rc_user_text' => $wgUser->getName(), 'rc_user' => $wgUser->getID() ), 
				array('rc_this_oldid' => $rev_id) );
		}

		$ret = $dbw->update('firstedit', array('fe_user_text=\'' . $wgUser->getName() . '\'', 'fe_user=' . $wgUser->getID() ), array('fe_page=' . $articleid, 'fe_user_text=\'' . wfGetIP() .'\'') );
		return false;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function notifyThumbsUp($articlename, $recipientUserName, $giverName, $giverUserName, $revisionId) {
		$track_title = "?utm_source=thumbs_up_email&utm_medium=email&utm_term=article_title&utm_campaign=thumbs_up_email";
		$track_talk = "?utm_source=thumbs_up_email&utm_medium=email&utm_term=user_talk&utm_campaign=thumbs_up_email";
		$track_diff = "?utm_source=thumbs_up_email&utm_medium=email&utm_term=article_diff&utm_campaign=thumbs_up_email";

		$t = Title::newFromText($articlename);

		if (!isset($t)) {return true;}

		$diffLink = $t->getFullURL( $track_diff . '&oldid=' . $revisionId . '&diff=PREV');
		$titlelink = "<a href='".$t->getFullURL() . $track_title . "'>".$t->getText()."</a>";

		$user = User::newFromName($recipientUserName);;
		$giverUser = User::newFromName($giverUserName);
		$giverTalkPageLink = $giverUser->getTalkPage()->getFullURL() . $track_talk;
		$giverTalkPageLink = '<a href="' . $giverTalkPageLink .'">' . $giverName . '</a>';

		$from_name = wfMsg('aen_from');
		$subject = wfMsg('aen_thumbs_subject', $articlename);
		$body = wfMsg('aen_thumbs_body', $user->getName(), $titlelink, $giverTalkPageLink, $diffLink);
		AuthorEmailNotification::notify($user, $from_name, $subject, $body);

		wfDebug("AEN DEBUG notifyThumbsUp called. Email sent for $articlename, thumbs upper is $giverName\n\n$body\n");

		return true;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function notifyRisingStar($articlename, $username, $nabName, $nabusername) {
		$dbw = &wfGetDB(DB_MASTER);

		$track_title = "?utm_source=rising_star_email&utm_medium=email&utm_term=article_title&utm_campaign=rising_star_email";
		$track_talk = "?utm_source=rising_star_email&utm_medium=email&utm_term=user_talk&utm_campaign=rising_star_email";

		$t = Title::newFromText($articlename);
		$titlelink = "<a href='".$t->getFullURL() . $track_title . "'>".$t->getText()."</a>";
		if (!isset($t)) {return true;}

		$user = User::newFromName($username);
		$nabUser = User::newFromName($nabusername);
		$talkPageUrl = $nabUser->getTalkPage()->getFullURL() . $track_talk;
		$nabName = '<a href="' . $talkPageUrl .'">' . $nabName . '</a>';

		$res = $dbw->select(
					array('email_notifications'),
					array ('en_watch', 'en_risingstar_email', 'en_last_emailsent', 'en_user'),
					array ('en_page='.$t->getArticleID()),
					"AuthorEmails"
            );

		if ($row = $dbw->fetchObject($res)) {

			if ($row->en_risingstar_email != NULL) {
				$now = time();
				$last = strtotime($row->en_risingstar_email . " UTC");
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}
			if ( 
				( $user->getEmail() != '') && 
				( $row->en_watch == 1) && 
				( $diff > 86400 )
				) {

				$ret = $dbw->update('email_notifications', 
						array('en_risingstar_email=\'' . wfTimestampNow() . '\'', 'en_last_emailsent=\'' . wfTimestampNow() . '\''), 
						array('en_page='.$t->getArticleID(), 'en_user' => $user->getID() ) );
	
				$from_name = wfMsg('aen_from');
				$subject = wfMsg('aen_rs_subject', $articlename);
				$body = wfMsg('aen_rs_body', $user->getName(), $titlelink, $nabName);
	
				AuthorEmailNotification::notify($user, $from_name, $subject, $body);
				wfDebug("AEN DEBUG notifyRisingStar called. Email sent for $articlename, nabber is $nabName\n\n$body\n");
			} else {
				wfDebug("AEN DEBUG notifyRisingStar called.  Did not meet conditions.  No email sent for $articlename \n");
			}
		}
		return true;

	}

	/**************************************
	 *
	 *
	 **************************************/
	function notifyFeatured($title) {

		$dbw = wfGetDB(DB_MASTER);
		$track_title = '?utm_source=featured_email&utm_medium=email&utm_term=article_title&utm_campaign=featured_email';
		echo "notifyFeatured en_page: ".$title->getArticleID()." notifyFeatured attempting.\n";

		$res = $dbw->select(
					array('email_notifications'),
					array ('en_watch', 'en_featured_email', 'en_last_emailsent', 'en_user'),
					array ('en_page='.$title->getArticleID()),
					"AuthorEmails"
            );

		if ($row = $dbw->fetchObject($res)) {

			if ($row->en_featured_email != NULL) {
				$now = time();
				$last = strtotime($row->en_featured_email . " UTC");
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}

			if (($row->en_watch == 1) && ($diff > 86400) ) {

				$user = User::newFromID( $row->en_user );
				$titlelink = "<a href='".$title->getFullURL() . $track_title . "'>".$title->getText()."</a>";
	
				if ( $user->getEmail() != '')  {
					$ret = $dbw->update('email_notifications', 
							array('en_featured_email=\'' . wfTimestampNow() . '\'', 'en_last_emailsent=\'' . wfTimestampNow() . '\''), 
							array('en_page='.$title->getArticleID(), 'en_user' => $user->getID() ) );
		
					$from_name = wfMsg('aen_from');
					$subject = wfMsg('aen_featured_subject', $title->getText());
					$body = wfMsg('aen_featured_body', $user->getName(), $titlelink );
		

					echo "Sending en_page:".$title->getArticleID()." for ".$user->getName()." article:".$title->getText()."\n";
					AuthorEmailNotification::notify($user, $from_name, $subject, $body);
				}
			} else {
				echo "Article not watched or recently sent.  Not sending.\n";
			}
		} else {
			echo "Article not in email_notification table\n";
		}

		return true;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function notifyViewership($title, $user, $milestone, $viewership, $last_vemail_sent) {
		$dbw = &wfGetDB(DB_MASTER);

		$track_title = '?utm_source=n_views_email&utm_medium=email&utm_term=article_title&utm_campaign=n_views_email';

		if ($last_vemail_sent != NULL) {
			$now = time();
			$last = strtotime($row->en_viewership_email . " UTC");
			$diff = $now - $last;
		} else {
			$diff = 86400 * 10;
		}
		if ($diff > 86400) {
			$titlelink = "<a href='".$title->getFullURL() . $track_title . "'>".$title->getText()."</a>";

			$from_name = wfMsg('aen_from');
			$subject = wfMsg('aen_viewership_subject', $title->getText(), number_format($milestone));
			$body = wfMsg('aen_viewership_body', $user->getName(), $titlelink, number_format($milestone));

			$ret = $dbw->update('email_notifications', 
					array('en_viewership_email=\'' . wfTimestampNow() . '\'', 'en_viewership=\'' . $viewership . '\'', 'en_last_emailsent=\'' . wfTimestampNow() . '\''), 
					array('en_page='.$title->getArticleID(), 'en_user' => $user->getID() ) );

			echo "AEN notifyViewership  [TITLE] ".$title->getText()." --- ".$title->getArticleID()." [USER] ".$user->getName()." [VIEWS]".$row->en_viewership."::".$viewership." - Sending Viewership Email.\n";

			AuthorEmailNotification::notify($user, $from_name, $subject, $body);
		} else {	
			echo "AEN notifyViewership [TITLE] ".$title->getText()." :: ".$title->getArticleID()." [USER] ".$user->getName()." [VIEWS]".$row->en_viewership."::".$viewership." - Threshold encountered, too soon last email sent $diff seconds ago.\n";
		}

		return true;

	}

	/**************************************
	 *
	 * Notify the original author of the article if he/she so requests once the edit is patrolled
	 * Exceptions:
	 * - The author has already been notified in a 24 hour period
	 * - The edit was made by the author of the article
	 * - The edit is a roll back
	 *
	 **************************************/
	function notifyMod(&$article, &$editUser, &$revision) {
		global $wgMemc;

		$authors = $article->getContributors(1);
		// Don't send an email if the author of the revision is the creator of the article
		if ($editUser->getName() == $authors[0][1]) { 
			return true; 
		}	

		// Don't create a mod email if there isn't a revision created
		if (is_null($revision)) {
			return true;
		}

		// Don't send an email if it's a rollback. 
		if (preg_match("@Reverted edits by@", $revision->getComment())) {
			return true;
		}

		$t = $article->getTitle();
		$dbr = &wfGetDB(DB_SLAVE);
		$res = $dbr->select(
					array('email_notifications'),
					array ('en_watch', 'en_user', 'en_watch_email', 'en_last_emailsent'),
					array ('en_page='.$t->getArticleID()),
					"AuthorEmails");

		if ($row = $dbr->fetchObject($res)) {
			$key = wfMemcKey($t->getArticleID() . '-aen');
			$recentEmail = $wgMemc->get($key);
			if (is_null($recentEmail)) {
				$recentEmail = false;
			}
			
			// They're watching this, right?
			$sendEmail = $row->en_watch == 1;
			// See how long it's been since we've sent an email. If it's been more than a day, send an email
			if (!is_null($row->en_watch_email)) {
				$last = strtotime($row->en_watch_email . " UTC");
				if  (time() - $last > 86400) {
					$sendEmail = true && $sendEmail && !$recentEmail;
				}
			} 
			$recipientUser = User::newFromID($row->en_user);
			if ($sendEmail) {
				$dbw = wfGetDB(DB_MASTER);
				$dbw->update('email_notifications', array('en_watch_email' => wfTimestampNow(), 'en_last_emailsent' => wfTimestampNow()), 
					array('en_page' => $t->getArticleID(), 'en_user' => $recipientUser->getID()));
	
				// Set a flag that lets us know a recent email was set
				// This is to prevent us from sending multiple emails if there are db delays in replication
				$wgMemc->set($key, true, time() + 60 * 30);
				AuthorEmailNotification::sendModEmail($t, $recipientUser, $revision, $editUser);
			}
		} else {
			wfDebug("AEN DEBUG: notifyMod" . $t->getArticleID() . " was modified but notification email not sent.\n");
		}
		return true;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function populateTrackingLinks($editType, &$titleLink, &$editLink, &$diffLink, &$articleTitle, &$revision) {
		switch ($editType) {
			case 'image':
				$utm_source = 'image_added_email';
				break;
			case 'video':
				$utm_source = 'video_added_email';
				break;
			case 'categorization':
				$utm_source = 'categorization_added_email';
				break;
			case 'default':
				$utm_source = 'n_edits_email';
				break;
		}
		$track_title = '&utm_source=' . $utm_source .'&utm_medium=email&utm_campaign=n_edits_email';
		$prevRevId = $articleTitle->getPreviousRevisionID($revision->getId());

		//$titleLink = "<a href='".$articleTitle->getFullURL('utm_term=article_title' . $track_title) . "'>" . $articleTitle->getText() . "</a>";
		//$editLink = "<a href='".$articleTitle->getFullURL('action=edit&utm_term=article_edit' . $track_title)."'>editing it</a>";
		//$diffLink = "<a href='" .$articleTitle->getFullURL( 'utm_term=article_diff&oldid=' . $prevRevId . '&diff=' . $revision->getId() . $track_title) . "'>diff page</a>";
		$titleLink = $articleTitle->getFullURL('utm_term=article_title' . $track_title);
		$editLink = $articleTitle->getFullURL('action=edit&utm_term=article_edit' . $track_title);
		$diffLink = $articleTitle->getFullURL( 'utm_term=article_diff&oldid=' . $prevRevId . '&diff=' . $revision->getId() . $track_title);
	}

	function getEditUserHtml(&$user) {
		$html = "";
		// If a registered, non-deleted user
		if ($user->getId() != 0) {
			$track_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page&utm_campaign=n_edits_email';
			$talkPageUrl = $user->getTalkPage()->getFullURL() . $track_talk;
			$editUserHref = '<a href="' . $talkPageUrl .'">' . $user->getName() . '</a>';
		}
		if (strlen($editUserHref)) {
			$html = " by " . $editUserHref;
		}
		return $html;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function sendModEmail(&$articleTitle, &$recipientUser, &$revision, &$editUser) {
		$from_name = wfMsg('aen_from');
		$titleLink = '';
		$editLink = '';
		$diffLink = '';
		$articleName = $articleTitle->getText();

		$comment = $revision->getComment();
		$editUser = self::getEditUserHtml($editUser);
		if (stripos($comment, "Added image:") !== FALSE || stripos($comment, "Added Image using ImageAdder Tool") !== FALSE) {
			AuthorEmailNotification::populateTrackingLinks('image', $titleLink, $editLink, $diffLink, $articleTitle, $revision);
			$subject = wfMsg('aen_mod_subject_image', $articleName);
			$body = wfMsg('aen_mod_body_image1', $recipientUser->getName(), $titleLink, $editUser, $editLink, $articleName );
		} else if (stripos($comment, "adding video") !== FALSE || stripos($comment, "changing video") !== FALSE) {
			AuthorEmailNotification::populateTrackingLinks('video', $titleLink, $editLink, $diffLink, $articleTitle, $revision);
			$subject = wfMsg('aen_mod_subject_video', $articleName);
			$body = wfMsg('aen_mod_body_video1', $recipientUser->getName(), $titleLink, $editUser, $editLink, $articleName );
		} else if (stripos($comment, "categorization") !== FALSE) {
			AuthorEmailNotification::populateTrackingLinks('categorization', $titleLink, $editLink, $diffLink, $articleTitle, $revision);
			$subject = wfMsg('Aen_mod_subject_categorization', $articleName);
			$body = wfMsg('aen_mod_body_categorization1', $recipientUser->getName(), $titleLink, $editUser, $diffLink, $editLink, $articleName );
		} else {
			AuthorEmailNotification::populateTrackingLinks('default', $titleLink, $editLink, $diffLink, $articleTitle, $revision);
			$subject = wfMsg('aen_mod_subject_edit', $articleName);
			$body = wfMsg('aen_mod_body_edit', $recipientUser->getName(), $titleLink, $editUser, $diffLink, $editLink, $articleName );
		}		
		AuthorEmailNotification::notify($recipientUser, $from_name, $subject, $body);
		wfDebug("AEN DEBUG email notification: " . $subject . "\n\n" . $body . "\n\n");
	}

	/**************************************
	 *
	 *
	 **************************************/
	function notifyUserTalk($aid, $from_uid, $comment, $type='talk') {
		global $wgServer, $wgLang, $wgParser;

		$fname = "AuthorEmailNotification::notifyUserTalk";
		wfProfileIn($fname);

		$dateStr = $wgLang->timeanddate(wfTimestampNow());
		if ($type == 'talk') {
			$track_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page&utm_campaign=talk_page_message';
			$track_sender_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page_sender&utm_campaign=talk_page_message';
		}
		else {
			$track_talk = '?utm_source=thumbsup_message&utm_medium=email&utm_term=talk_page&utm_campaign=talk_page_message';
			$track_sender_talk = '?utm_source=thumbsup_message&utm_medium=email&utm_term=talk_page_sender&utm_campaign=talk_page_message';
		}


		if ($aid == 0) {return;}
		if (preg_match('/{{.*?}}/', $comment, $matches)) { return; } 

		$t = Title::newFromID($aid);

		if ($type == 'talk') {
			$options = new ParserOptions();
			$output = $wgParser->parse($comment, $t, new ParserOptions());

			$comment = $output->getText();
			$comment = preg_replace('/href="\//', 'href="'.$wgServer.'/', $comment);
			$comment = strip_tags($comment,'<br><a>');
		}

		$fromuser = User::newFromID($from_uid);

		if (isset($t)) {
			$touser = User::newFromName($t->getText());
		} else {
			// no article no object
			return;
		}

		if (!$touser) return; 

		if ( $t->getArticleID() > 0 && 
				$t->getNamespace() == NS_USER_TALK && 
				$touser->getEmail() != '' &&
            $touser->getOption('usertalknotifications') == '0' ) {

			$talkpagelink = $wgServer . '/' . rawurlencode($t->getTalkPage()) . $track_talk; 		
			$talkpagesenderlink = $wgServer . '/' . rawurlencode($fromuser->getTalkPage()) . $track_sender_talk; 		

			$from_name = wfMsg('aen_from');
			$subject = wfMsg('aen_usertalk_subject', $t->getTalkPage(), $fromuser->getName());
			$body = wfMsg('aen_usertalk_body', $fromuser->getName(), $touser->getName(), $talkpagelink, $comment ,$dateStr, $talkpagesenderlink );

			AuthorEmailNotification::notify($touser, $from_name, $subject, $body);
			wfDebug("AEN DEBUG: notifyUserTalk send. from:".$fromuser->getName()." to:".$touser->getName()." title:".$t->getTalkPage()."\nbody: " . $body . "\n");

		} else {
			wfDebug("AEN DEBUG: notifyUserTalk - called no article: ".$t->getArticleID()."\n");
		}
		
		wfProfileOut($fname);
		return true;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function notify($user, $from_name, $subject, $body, $type = "") {
		global $wgServer, $wgOutputEncoding;

		$fname = "AuthorEmailNotification::notify";
		wfProfileIn($fname);
		$isDev = false;
		if ( strpos($wgServer,"wikidiy.com") !== false ) {
			wfDebug("AuthorEmailNotification in dev not notifying: TO: ".  $user->getName() .",FROM: $from_name\n");
			$isDev = true;
			$subject = "[FROM DEV] $subject";
		}
		
		if ( $user->getEmail() != '')  {
			$validEmail = "";

			if ($user->getID() > 0) {
				$to_name = $user->getName();
				$to_real_name = $user->getRealName();
				if ($to_real_name != "") {
					$to_name = $real_name;
				}
				$username = $to_name;
				$email = $user->getEmail();
	
				$validEmail = $email;
				$to_name .= " <$email>";
			}

			$from = new MailAddress ($from_name);	
			$to = new MailAddress ($to_name);

			if ($type == 'text') {
				if (!$isDev) {
					UserMailer::send($to, $from, $subject, $body);
				}
				//XX HARDCODE SEND TO ELIZABETH FOR TEST
				$to = new MailAddress ("elizabethwikihowtest@gmail.com");
				UserMailer::send($to, $from, $subject, $body);
			} else {
				//FOR HTML EMAILS
				$content_type = "text/html; charset={$wgOutputEncoding}";
				if (!$isDev) {
					UserMailer::send($to, $from, $subject, $body, null, $content_type);
				}
				//XX HARDCODE SEND TO ELIZABETH FOR TEST
				$to = new MailAddress ("elizabethwikihowtest@gmail.com");
				UserMailer::send($to, $from, $subject, $body, null, $content_type);
			}

			wfProfileOut($fname);
			return true;
		}
	}

	/**************************************
	 *
	 *
	 **************************************/
	function processFeatured() {
		global $wgServer, $wgFeedClasses;

		echo "Processing Featured Articles Notification\n";

		require_once('FeaturedArticles.php');

		$days = 1;
		date_default_timezone_set("UTC");
		$feeds = FeaturedArticles::getFeaturedArticles($days);


		$now = time();
		$tomorrow = strtotime('tomorrow');
		$today = strtotime('today');

		echo "Tomorrow: ".date('m/d/Y H:i:s',$tomorrow)."[$tomorrow] Today: ".date('m/d/Y H:i:s',$today)."[$today] NOW: ".date('m/d/Y H:i:s',$now)." \n";

		foreach ($feeds as $f ) {
			

				$url = $f[0];
				$d = $f[1];
				echo "Processing url: $url with epoch ".date('m/d/Y H:i:s',$d)."[$d]\n";

				if (($d > $tomorrow)||($d < $today)) continue;
		
				$url = str_replace("http://www.wikihow.com/", "", $url);
				$url = str_replace($wgServer . "/", "", $url);
				$title = Title::newFromURL(urldecode($url));
				$title_text = $title->getText();
				if (isset($f[2]) && $f[2] != null && trim($f[2]) != '') {
					$title_text = $f[2];
				} else { 
					$title_text = wfMsg('howto', $title_text);
				}

				if (isset($title)) {
					echo "Featured: $title_text [AID] ".$title->getArticleID()." [URL] $url\n";
					AuthorEmailNotification::notifyFeatured($title);
				} else {
					echo "Warning Featured: could not retrieve article id for $url\n";
				}
		}
	}

	/**************************************
	 * SEE maintenance/emailNotifications.php, this is no longer used.
	 *
	 **************************************/
	function processViewership() {

		$thresholds = array(25, 100, 500, 1000, 5000);
		$thresh2 = 10000;

		$dbr = &wfGetDB(DB_SLAVE);


		$res = $dbr->select(
					array('email_notifications'),
					array ('en_viewership_email', 'en_viewership', 'en_user', 'en_page'),
					array ('en_watch=1',),
					"AuthorEmails"
            );

		while ($row = $dbr->fetchObject($res)) {
			$sendflag = 0;
			$viewership = 0;
			$milestone = 0;

			$title = Title::newFromID( $row->en_page );
			$user = User::newFromID( $row->en_user );

			if (isset($title)) {

				$viewership  = $dbr->selectField('page', 'page_counter', array('page_id=' . $title->getArticleID()));

				$prev = $row->en_viewership;
	
				if ($viewership > $thresh2) {
					$a = floor($prev / $thresh2);
					$b = floor($viewership / $thresh2);
					if ( $b > $a ) {
						$milestone = $b * $thresh2; 
						$sendflag = 1;
					}
				} else {
					foreach ($thresholds as $level) {
						if ( ($prev < $level) && ($level < $viewership) ) {
							$milestone = $level;
							$sendflag = 1;
						}
					}
				}


				if ($sendflag) {
					echo "Processing: [TITLE] ".$title->getText()."(".$title->getArticleID().") [USER] ".$user->getName().", [VIEWS]".$row->en_viewership." - ".$viewership." [MILESTONE] $milestone \n";
	
					AuthorEmailNotification::notifyViewership($title, $user, $milestone, $viewership, $row->en_viewership_email);
				} else {
					echo "Skipping: [TITLE] ".$title->getText()."(".$title->getArticleID().") [USER] ".$user->getName().", [VIEWS]".$row->en_viewership." - ".$viewership." [MILESTONE] $milestone \n";
				}

			} else {
				echo "Article Removed: [PAGE] ".$row->en_page." [USER] ".$row->en_user."\n";
			}
		}

	}


	//*************
	//show page for logged in users
	//
	//*************
	function showUser() {
		global $wgRequest, $wgOut, $wgUser;

		$dbr = &wfGetDB(DB_SLAVE);

		$wgOut->addHTML('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/Authorleaderboard.css"; /*]]>*/</style>');

		$order = array();	
		switch ($wgRequest->getVal('orderby')) {
			case 'popular':
				$order['ORDER BY'] = 'page_counter DESC ';
				break;
			case 'time_asc':
				$order['ORDER BY'] = 'fe_timestamp ASC ';
				break;
			default:
				$order['ORDER BY'] = 'fe_timestamp DESC ';
		}

		//$order['LIMIT'] = $onebillion;
		$order['GROUP BY'] = 'page_id';

		$res = $dbr->select(
					array('firstedit','page'),
					array ('page_title', 'page_id', 'page_namespace', 'fe_timestamp'),
					array ('fe_page=page_id', 'fe_user_text' => $wgUser->getName()),
					"AuthorEmails",
					$order
            );

		$res2 = $dbr->select(
					array('email_notifications'),
					array ('en_page','en_watch'),
					array ('en_user=' . $wgUser->getID()),
					"AuthorEmails"
            );

		$watched = array();
		while ($row2=$dbr->fetchObject($res2)) { $watched[ $row2->en_page ] = $row2->en_watch; }
		$articlecount = $dbr->numRows($res);
		if ($articlecount > 500) {
			$wgOut->addHTML('<div style="overflow:auto;width:600px;imax-height:300px;height:300px;border:1px solid #336699;padding-left:5px:margin-bottom:10px;">'."\n");
		} else {
			$wgOut->addHTML('<div>'."\n");
		}


		if ($wgRequest->getVal('orderby')) {
			$orderby = '<img id="icon_navi_up" src="/extensions/wikihow/icon_navi_up.jpg" height=13 width=13 />';
		} else {
			$orderby = '<img id="icon_navi_down" src="/extensions/wikihow/icon_navi_down.jpg" height=13 width=13 />';
		}

		$wgOut->addHTML("<form method='post'>" );
		$wgOut->addHTML("<br/><center><table width='500px' align='center' class='status'>" );
		// display header
		$index = 1;
		$aen_email = wfMsg('aen_form_email');
		$aen_title = wfMsg('aen_form_title');
		$aen_created = wfMsg('aen_form_created');
		$wgOut->addHTML("<tr>
			<td><strong>$aen_email</strong></td>
			<td><strong>$aen_title</strong></td>
			<td><strong>$aen_created</strong> <a id='aen_date' onclick='aenReorder(this);'>$orderby</a></td>
			</tr>
		");


		while ($row=$dbr->fetchObject($res)) {

			$class = "";
			$checked = "";
			$fedate = "";

			if ($index % 2 == 1) 
				$class = 'class="odd"';

			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($watched[ $row->page_id ]) {
				$checked = "CHECKED";
			}

			$fedate = date('M d, Y', strtotime($row->fe_timestamp ." UTC"));

			$wgOut->addHTML("<tr $class >");
			$wgOut->addHTML("<td align='center'><input type='checkbox' name='articles-". $index ."' value='". $row->page_id ."' $checked /></td><td><a href='/". htmlspecialchars($row->page_title,ENT_QUOTES) ."' >".$t."</a></td><td align='center'>".$fedate." <!--".$row->page_id."--></td>\n");
			$wgOut->addHTML("</tr>");
			$watched[ $row->page_id ] = 99;
			$index++;
		}
		$wgOut->addHTML("</table>");

		$wgOut->addHTML("<br/><div style='width:500px;text-align:right;'>" );
		$wgOut->addHTML("<input type='hidden' name='articlecount' value='".$index."' />\n");
		$wgOut->addHTML("<input type='submit' name='action' value='Save' />\n");
		$wgOut->addHTML("<br/></div>");

		$wgOut->addHTML("</div>");
		

		foreach ($watched as $key => $value) {
			$t = Title::newFromID( $key );
			if ($value != 99) 
				$wgOut->addHTML("<!-- DEBUG AEN not FE: $key ==> $value *** $t <br /> -->\n");
		}

		//DEBUG CODE TO TEST EMAILS
/*
		$wgOut->addHTML("<br /><br />
				<input type='button' name='aen_rs_email' value='rising star email' onClick='send_test(\"rs\");'  />
				<input type='button' name='aen_mod_email' value='edit email' onClick='send_test(\"mod\");'  />
				<input type='button' name='aen_featured_email' value='featured email' onClick='send_test(\"featured\");'  />
				<input type='button' name='aen_viewership' value='viewership email' onClick='send_test(\"viewership\");'  />\n");
*/

		$dbr->freeResult($res);
		$wgOut->addHTML("</center>\n");
		$wgOut->addHTML("</form>\n");
	}

	/**************************************
	 *
	 *
	 **************************************/
	function addUserWatch($target, $watch) {
		global $wgUser;
		$dbw = &wfGetDB(DB_MASTER);

		$sql = "INSERT INTO email_notifications (en_user,en_page,en_watch) ";
		$sql .= "VALUES ('".$wgUser->getID()."','".$target."',".$watch.") ON DUPLICATE KEY UPDATE en_watch=".$watch;
		$ret = $dbw->query($sql);
		return $ret;
	}

	/**************************************
	 *
	 *
	 **************************************/
	function addUserWatchBulk($articles) {
		global $wgUser;
		$dbw = &wfGetDB(DB_MASTER);

		//RESET ALL FOR USER
		$ret = $dbw->update('email_notifications', array('en_watch=0'), array('en_user=' . $wgUser->getID() ) );

		//SET ARTICLES TO WATCH
		$articleset =  implode(',', $articles);

		foreach ($articles as $article) {
			$sql = "INSERT INTO email_notifications (en_user,en_page,en_watch) ";
			$sql .= "VALUES ('".$wgUser->getID()."','".$article."',1) ON DUPLICATE KEY UPDATE en_watch=1";
			$ret = $dbw->query($sql);

		}
	}

	/**************************************
	 *
	 *
	 **************************************/
	function execute ($par) {
		global $wgServer, $wgRequest, $wgOut, $wgUser;
      wfLoadExtensionMessages('AuthorEmailNotification');
      $fname = 'AuthorEmailNotification';

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if( $wgUser->getID() == 0) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}


		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$action = $wgRequest->getVal( 'action' );

		$dbr = &wfGetDB(DB_SLAVE);
	
		$me = Title::makeTitle(NS_SPECIAL, "AuthorEmailNotification");



		if ($action == 'Save') {
			$articles = array();
			$articlecount = $wgRequest->getVal( 'articlecount' );
			for($i=1;$i<= ($articlecount + 1);$i++) {
				$item = $wgRequest->getVal( 'articles-'.$i );
				if (($item != '') && ($item != 0)) {
					array_push($articles, $item);
				}
			}

			$this->addUserWatchBulk($articles);
		} else if ($action == 'update') {
			$watch = 1;
			$watch = $wgRequest->getVal( 'watch' );

			if ( ($target != "") ) {
				$this->addUserWatch($target, $watch);
			} else {
				wfDebug('Ajax call for AuthorEmailNotifications with improper parameters.');
			}
			return;
		} else if ($action == 'addNotification') {

			$email = '';
			$email = $wgRequest->getVal( 'email' );
			
			$this->addNotification( $target, $email );

			return;
		} else if ($action == 'updatePreferences') {
			wfDebug("AEN DEBUG in updatepreferences\n");
			if ($wgRequest->getVal( 'dontshow' ) == 1) {
				wfDebug("AEN DEBUG in dontshow\n");
				$wgUser->setOption( 'enableauthoremail', 1 );
				wfDebug("AEN DEBUG in settingoption\n");
				$wgUser->saveSettings();
			}
			return;
		} else if ($action == 'testsend') {
			//FOR TESTING 
  	       $subject = "";
  	       $body = "";

			  $title = "Help Your Dog Lose Weight";
			  $titlelink = "<a href='$wgServer/Help-Your-Dog-Lose-Weight'>Help Your Dog Lose Weight</a>";

			switch($target) {
				case 'rs':
  	       $subject = wfMsg('aen_rs_subject', $title);
  	       $body = wfMsg('aen_rs_body', $wgUser->getName(), $titlelink);
					break;
				case 'mod':
  	       $subject = wfMsg('aen_mod_subject', $title);
  	       $body = wfMsg('aen_mod_body', $wgUser->getName(), $titlelink);
					break;
				case 'featured':
  	       $subject = wfMsg('aen_featured_subject', $title);
  	       $body = wfMsg('aen_featured_body', $wgUser->getName(), $titlelink);
					break;
				case 'viewership':
  	       $subject = wfMsg('aen_viewership_subject', $title, '12768');
  	       $body = wfMsg('aen_viewership_body', $wgUser->getName(), $titlelink, '12768');
					break;
			}

			if ( $wgUser->getEmail() != '')  {
  	       	$from_name = wfMsg('aen_from');
  	       	$this->notify($wgUser, $from_name, $subject, $body);
			}

			return;
		}

		$wgOut->addHTML("
			<script type='text/javascript' src='" . wfGetPad('/extensions/wikihow/authoremails.js?rev=') . WH_SITEREV . "'></script>
		");

		$wgOut->addHTML(wfMsg('emailn_title') . "<br/><br/>");
		$this->showUser();
	
		return;
	}
}

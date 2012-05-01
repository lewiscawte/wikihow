<?php

class AuthorEmailNotification extends SpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AuthorEmailNotification' );
	}

	/**
	 * @param $article String: page name used to create a corresponding Title
	 * @param $email String:
	 */
	function addNotification( $article, $email = '' ) {
		global $wgUser;

		$t = Title::newFromText( $article );
		$aid = $t->getArticleID();

		if ( ( $wgUser->getID() > 0) && ( $aid != 0 ) ) {
			if ( $wgUser->getEmail() != '' ) {
				self::addUserWatch( $aid, 1 );
			} else {
				if ( $email != '' ) {
					$wgUser->setEmail( $email );
					$wgUser->saveSettings();
					self::addUserWatch( $aid, 1 );
				}
			}
		}
	}

	/**
	 * @param $articleId Integer: article ID number
	 * @return Boolean: false
	 */
	static function reassignArticleAnon( $articleId ) {
		global $wgUser;

		$dbw = wfGetDB( DB_MASTER );
		$revId = $dbw->selectField(
			'revision',
			'rev_id',
			array( 'rev_page' => $articleId, 'rev_user_text' => wfGetIP() ),
			__METHOD__
		);

		if ( $revId != '' ) {
			wfDebugLog( 'AuthorEmailNotification', "reassinging {$revId} to {$wgUser->getName()}\n" );
			$ret = $dbw->update(
				'revision',
				array(
					'rev_user_text' => $wgUser->getName(),
					'rev_user' => $wgUser->getID()
				),
				array( 'rev_id' => $revId ),
				__METHOD__
			);
			$ret = $dbw->update(
				'recentchanges',
				array(
					'rc_user_text' => $wgUser->getName(),
					'rc_user' => $wgUser->getID()
				),
				array( 'rc_this_oldid' => $revId ),
				__METHOD__
			);
		}

		$ret = $dbw->update(
			'firstedit',
			array(
				'fe_user_text' => $wgUser->getName(),
				'fe_user' => $wgUser->getID()
			),
			array(
				'fe_page' => $articleId,
				'fe_user_text' => wfGetIP()
			),
			__METHOD__
		);

		return false;
	}

	/**
	 * Notify $recipientUserName that $giverUserName gave them a thumbs up for
	 * the edit with the revision ID $revisionId to the article $articleName.
	 *
	 * @param $articleName String: article name, used to build a Title object
	 * @param $recipientUserName String: name of the user whose edit was given a thumbs up
	 * @param $giverName String: real name of the user who gave the thumbs up
	 * @param $giverUserName String: username of the person who gave the thumbs up
	 * @param $revisionId Integer: revision ID number of the good edit
	 * @return Boolean: true
	 */
	function notifyThumbsUp( $articleName, $recipientUserName, $giverName, $giverUserName, $revisionId ) {
		$track_title = '?utm_source=thumbs_up_email&utm_medium=email&utm_term=article_title&utm_campaign=thumbs_up_email';
		$track_talk = '?utm_source=thumbs_up_email&utm_medium=email&utm_term=user_talk&utm_campaign=thumbs_up_email';
		$track_diff = '?utm_source=thumbs_up_email&utm_medium=email&utm_term=article_diff&utm_campaign=thumbs_up_email';

		$t = Title::newFromText( $articleName );

		if ( !isset( $t ) ) {
			return true;
		}

		$diffLink = $t->getFullURL( $track_diff . '&oldid=' . $revisionId . '&diff=PREV' );
		$titleLink = '<a href="' . $t->getFullURL() . $track_title . '">' . $t->getText() . '</a>';

		$user = User::newFromName( $recipientUserName );
		$giverUser = User::newFromName( $giverUserName );
		$giverTalkPageLink = $giverUser->getTalkPage()->getFullURL() . $track_talk;
		$giverTalkPageLink = '<a href="' . $giverTalkPageLink . '">' . $giverName . '</a>';

		$from_name = wfMessage( 'aen-from' )->parse();
		$subject = wfMsg( 'aen-thumbs-subject', $articleName );
		$body = wfMsg( 'aen-thumbs-body', $user->getName(), $titleLink, $giverTalkPageLink, $diffLink );
		AuthorEmailNotification::notify( $user, $from_name, $subject, $body );

		wfDebugLog(
			'AuthorEmailNotification',
			"notifyThumbsUp called. E-mail sent for $articleName, thumbs upper is $giverName\n\n$body\n"
		);

		return true;
	}

	/**
	 * Notify the user that an article of theirs has been marked as a Rising
	 * Star.
	 *
	 * @param $articleName
	 * @param $username
	 * @param $nabName
	 * @param $nabUsername
	 * @return Boolean: true
	 */
	function notifyRisingStar( $articleName, $username, $nabName, $nabUsername ) {
		$dbw = wfGetDB( DB_MASTER );

		$t = Title::newFromText( $articleName );
		$titleLink = Linker::link(
			$t,
			$t->getText(),
			array(),
			array(
				'utm_source' => 'rising_star_email',
				'utm_medium' => 'email',
				'utm_term' => 'article_title',
				'utm_campaign' => 'rising_star_email'
			)
		);
		if ( !isset( $t ) ) {
			return true;
		}

		$user = User::newFromName( $username );
		$nabUser = User::newFromName( $nabUsername );
		$talkPageUrl = $nabUser->getTalkPage()->getFullURL( array(
			'utm_source' => 'rising_star_email',
			'utm_medium' => 'email',
			'utm_term' => 'user_talk',
			'utm_campaign' => 'rising_star_email'
		) );
		$nabName = Linker::link(
			$nabUser->getTalkPage(),
			$nabName,
			array(),
			array(
				'utm_source' => 'rising_star_email',
				'utm_medium' => 'email',
				'utm_term' => 'user_talk',
				'utm_campaign' => 'rising_star_email'
			)
		);

		$res = $dbw->select(
			array( 'email_notifications' ),
			array(
				'en_watch', 'en_risingstar_email', 'en_last_emailsent',
				'en_user'
			),
			array( 'en_page' => $t->getArticleID() ),
			__METHOD__
		);
		$row = $dbw->fetchObject( $res );

		if ( $row ) {
			if ( $row->en_risingstar_email != null ) {
				$now = time();
				$last = strtotime( $row->en_risingstar_email . ' UTC' );
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}

			if (
				( $user->getEmail() != '' ) &&
				( $row->en_watch == 1 ) &&
				( $diff > 86400 )
			)
			{
				$ret = $dbw->update(
					'email_notifications',
					array(
						'en_risingstar_email' => wfTimestampNow(),
						'en_last_emailsent' => wfTimestampNow(),
					),
					array(
						'en_page' => $t->getArticleID(),
						'en_user' => $user->getID()
					),
					__METHOD__
				);

				$from_name = wfMessage( 'aen-from' )->parse();
				$subject = wfMsg( 'aen-rs-subject', $articleName );
				$body = wfMsg( 'aen-rs-body', $user->getName(), $titleLink, $nabName );

				AuthorEmailNotification::notify( $user, $from_name, $subject, $body );
				wfDebugLog(
					'AuthorEmailNotification',
					"notifyRisingStar called. E-mail sent for $articleName, nabber is $nabName\n\n$body\n"
				);
			} else {
				wfDebugLog(
					'AuthorEmailNotification',
					"notifyRisingStar called. Did not meet conditions. No e-mail sent for $articleName\n"
				);
			}
		}

		return true;
	}

	/**
	 * @param $title
	 * @return Boolean: true
	 */
	static function notifyFeatured( $title ) {
		echo 'notifyFeatured en_page: ' . $title->getArticleID() . " notifyFeatured attempting.\n";

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			array( 'email_notifications' ),
			array(
				'en_watch', 'en_featured_email', 'en_last_emailsent',
				'en_user'
			),
			array( 'en_page' => $title->getArticleID() ),
			__METHOD__
		);
		$row = $dbw->fetchObject( $res );

		if ( $row ) {
			if ( $row->en_featured_email != null ) {
				$now = time();
				$last = strtotime( $row->en_featured_email . ' UTC' );
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}

			if ( ( $row->en_watch == 1 ) && ( $diff > 86400 ) ) {
				$user = User::newFromID( $row->en_user );
				$titleLink = Linker::link(
					$title,
					$title->getText(),
					array(),
					array(
						'utm_source' => 'featured_email',
						'utm_medium' => 'email',
						'utm_term' => 'article_title',
						'utm_campaign' => 'featured_email'
					)
				);

				if ( $user->getEmail() != '' ) {
					$ret = $dbw->update(
						'email_notifications',
						array(
							'en_featured_email' => wfTimestampNow(),
							'en_last_emailsent' => wfTimestampNow()
						),
						array(
							'en_page' => $title->getArticleID(),
							'en_user' => $user->getID()
						),
						__METHOD__
					);

					$from_name = wfMessage( 'aen-from' )->parse();
					$subject = wfMessage( 'aen-featured-subject', $title->getText() )->parse();
					$body = wfMsg( 'aen-featured-body', $user->getName(), $titleLink );

					echo 'Sending en_page:' . $title->getArticleID() . ' for ' .
						$user->getName() . ' article:' . $title->getText() . "\n";
					AuthorEmailNotification::notify( $user, $from_name, $subject, $body );
				}
			} else {
				echo "Article not watched or recently sent. Not sending.\n";
			}
		} else {
			echo "Article not in email_notifications table\n";
		}

		return true;
	}

	/**
	 * @param $title Title: Title object representing the page
	 * @param $user
	 * @param $milestone
	 * @param $viewership
	 * @param $last_vemail_sent
	 * @return Boolean: true
	 */
	static function notifyViewership( $title, $user, $milestone, $viewership, $last_vemail_sent ) {
		$dbw = wfGetDB( DB_MASTER );

		if ( $last_vemail_sent != null ) {
			$now = time();
			$last = strtotime( $row->en_viewership_email . ' UTC' );
			$diff = $now - $last;
		} else {
			$diff = 86400 * 10;
		}

		if ( $diff > 86400 ) {
			$titleLink = Linker::link(
				$title,
				$title->getText(),
				array(),
				array(
					'utm_source' => 'n_views_email',
					'utm_medium' => 'email',
					'utm_term' => 'article_title',
					'utm_campaign' => 'n_views_email'
				)
			);

			$from_name = wfMessage( 'aen-from' )->parse();
			$subject = wfMsg(
				'aen-viewership-subject',
				$title->getText(),
				number_format( $milestone )
			);
			$body = wfMsg(
				'aen-viewership-body',
				$user->getName(),
				$titleLink,
				number_format( $milestone )
			);

			$ret = $dbw->update(
				'email_notifications',
				array(
					'en_viewership_email' => wfTimestampNow(),
					'en_viewership' => $viewership ,
					'en_last_emailsent' => wfTimestampNow(),
				),
				array(
					'en_page' => $title->getArticleID(),
					'en_user' => $user->getID()
				),
				__METHOD__
			);

			echo 'AEN notifyViewership  [TITLE] ' . $title->getText() . ' --- ' .
				$title->getArticleID() . ' [USER] ' . $user->getName() .
				' [VIEWS]' . $row->en_viewership . '::' . $viewership .
				" - Sending Viewership Email.\n";

			AuthorEmailNotification::notify( $user, $from_name, $subject, $body );
		} else {
			echo 'AEN notifyViewership [TITLE] ' . $title->getText() .
				' :: ' . $title->getArticleID() . ' [USER] ' .
				$user->getName() . ' [VIEWS]' . $row->en_viewership . '::' .
				$viewership . " - Threshold encountered, too soon last email sent $diff seconds ago.\n";
		}

		return true;
	}

	/**
	 * Notify the original author of the article if he/she so requests once the edit is patrolled
	 * Exceptions:
	 * - The author has already been notified in a 24 hour period
	 * - The edit was made by the author of the article
	 * - The edit is a roll back
	 *
	 * @param $article
	 * @param $editUser
	 * @param $revision Revision
	 * @return Boolean: true
	 */
	static function notifyMod( &$article, &$editUser, &$revision ) {
		global $wgMemc;

		$authors = $article->getContributors( 1 );
		// Don't send an email if the author of the revision is the creator of the article
		if ( $editUser->getName() == $authors[0][1] ) {
			return true;
		}

		// Don't create a mod e-mail if there isn't a revision created
		if ( is_null( $revision ) ) {
			return true;
		}

		// Don't send an email if it's a rollback.
		// @todo FIXME
		if ( preg_match( '@Reverted edits by@', $revision->getComment() ) ) {
			return true;
		}

		$t = $article->getTitle();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'email_notifications' ),
			array( 'en_watch', 'en_user', 'en_watch_email', 'en_last_emailsent' ),
			array( 'en_page' => $t->getArticleID() ),
			__METHOD__
		);
		$row = $dbr->fetchObject( $res );

		if ( $row ) {
			$key = wfMemcKey( $t->getArticleID() . '-aen' );
			$recentEmail = $wgMemc->get( $key );
			if ( is_null( $recentEmail ) ) {
				$recentEmail = false;
			}

			// They're watching this, right?
			$sendEmail = $row->en_watch == 1;
			// See how long it's been since we've sent an email. If it's been more than a day, send an email
			if ( !is_null( $row->en_watch_email ) ) {
				$last = strtotime( $row->en_watch_email . ' UTC' );
				if ( time() - $last > 86400 ) {
					$sendEmail = true && $sendEmail && !$recentEmail;
				}
			}
			$recipientUser = User::newFromID( $row->en_user );
			if ( $sendEmail ) {
				$dbw = wfGetDB( DB_MASTER );
				$dbw->update(
					'email_notifications',
					array(
						'en_watch_email' => wfTimestampNow(),
						'en_last_emailsent' => wfTimestampNow()
					),
					array(
						'en_page' => $t->getArticleID(),
						'en_user' => $recipientUser->getID()
					),
					__METHOD__
				);

				// Set a flag that lets us know a recent email was set
				// This is to prevent us from sending multiple e-mails if there
				// are database delays in replication
				$wgMemc->set( $key, true, time() + 60 * 30 );
				AuthorEmailNotification::sendModEmail( $t, $recipientUser, $revision, $editUser );
			}
		} else {
			wfDebugLog(
				'AuthorEmailNotification',
				'notifyMod' . $t->getArticleID() . " was modified but notification e-mail not sent.\n"
			);
		}

		return true;
	}

	/**
	 * @param $editType String: edit type
	 * @param $titleLink
	 * @param $editLink
	 * @param $diffLink
	 * @param $articleTitle Title
	 * @param $revision
	 */
	function populateTrackingLinks( $editType, &$titleLink, &$editLink, &$diffLink, &$articleTitle, &$revision ) {
		switch ( $editType ) {
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
		$track_title = '&utm_source=' . $utm_source . '&utm_medium=email&utm_campaign=n_edits_email';
		$prevRevId = $articleTitle->getPreviousRevisionID( $revision->getId() );

		//$titleLink = "<a href='".$articleTitle->getFullURL('utm_term=article_title' . $track_title) . "'>" . $articleTitle->getText() . "</a>";
		//$editLink = "<a href='".$articleTitle->getFullURL('action=edit&utm_term=article_edit' . $track_title)."'>editing it</a>";
		//$diffLink = "<a href='" .$articleTitle->getFullURL( 'utm_term=article_diff&oldid=' . $prevRevId . '&diff=' . $revision->getId() . $track_title) . "'>diff page</a>";
		$titleLink = $articleTitle->getFullURL( 'utm_term=article_title' . $track_title );
		$editLink = $articleTitle->getFullURL( 'action=edit&utm_term=article_edit' . $track_title );
		$diffLink = $articleTitle->getFullURL(
			'utm_term=article_diff&oldid=' . $prevRevId . '&diff=' .
			$revision->getId() . $track_title
		);
	}

	function getEditUserHtml( &$user ) {
		$html = '';
		// If a registered, non-deleted user
		if ( $user->getId() != 0 ) {
			$track_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page&utm_campaign=n_edits_email';
			$talkPageUrl = $user->getTalkPage()->getFullURL() . $track_talk;
			$editUserHref = '<a href="' . $talkPageUrl .'">' . $user->getName() . '</a>';
		}
		if ( strlen( $editUserHref ) ) {
			$html = ' by ' . $editUserHref;
		}
		return $html;
	}

	/**
	 * @todo FIXME: English-specific much?
	 *
	 * @param $articleTitle
	 * @param $recipientUser
	 * @param $revision
	 * @param $editUser
	 */
	static function sendModEmail( &$articleTitle, &$recipientUser, &$revision, &$editUser ) {
		$from_name = wfMessage( 'aen-from' )->parse();
		$titleLink = '';
		$editLink = '';
		$diffLink = '';
		$articleName = $articleTitle->getText();

		$comment = $revision->getComment();
		$editUser = self::getEditUserHtml( $editUser );
		if ( stripos( $comment, 'Added image:' ) !== false || stripos( $comment, 'Added Image using ImageAdder Tool' ) !== false ) {
			AuthorEmailNotification::populateTrackingLinks(
				'image',
				$titleLink,
				$editLink,
				$diffLink,
				$articleTitle,
				$revision
			);
			$subject = wfMessage( 'aen-mod-subject-image', $articleName )->parse();
			$body = wfMsg(
				'aen-mod-body-image1',
				$recipientUser->getName(),
				$titleLink,
				$editUser,
				$editLink,
				$articleName
			);
		} elseif ( stripos( $comment, 'adding video' ) !== false || stripos( $comment, 'changing video' ) !== false ) {
			AuthorEmailNotification::populateTrackingLinks(
				'video',
				$titleLink,
				$editLink,
				$diffLink,
				$articleTitle,
				$revision
			);
			$subject = wfMessage( 'aen-mod-subject-video', $articleName )->parse();
			$body = wfMsg(
				'aen-mod-body-video1',
				$recipientUser->getName(),
				$titleLink,
				$editUser,
				$editLink,
				$articleName
			);
		} elseif ( stripos( $comment, 'categorization' ) !== false ) {
			AuthorEmailNotification::populateTrackingLinks(
				'categorization',
				$titleLink,
				$editLink,
				$diffLink,
				$articleTitle,
				$revision
			);
			$subject = wfMessage( 'aen-mod-subject-categorization', $articleName )->parse();
			$body = wfMsg(
				'aen-mod-body-categorization1',
				$recipientUser->getName(),
				$titleLink,
				$editUser,
				$diffLink,
				$editLink,
				$articleName
			);
		} else {
			AuthorEmailNotification::populateTrackingLinks(
				'default',
				$titleLink,
				$editLink,
				$diffLink,
				$articleTitle,
				$revision
			);
			$subject = wfMessage( 'aen-mod-subject-edit', $articleName )->parse();
			$body = wfMsg(
				'aen-mod-body-edit',
				$recipientUser->getName(),
				$titleLink,
				$editUser,
				$diffLink,
				$editLink,
				$articleName
			);
		}
		AuthorEmailNotification::notify( $recipientUser, $from_name, $subject, $body );
		wfDebugLog(
			'AuthorEmailNotification',
			'email notification: ' . $subject . "\n\n" . $body . "\n\n"
		);
	}

	/**
	 * Notify a user about an edit to their talk page.
	 *
	 * @param $aid Integer: article ID number
	 * @param $from_uid
	 * @param $comment
	 * @param $type String
	 * @return Boolean: true on success
	 */
	function notifyUserTalk( $aid, $from_uid, $comment, $type = 'talk' ) {
		global $wgServer, $wgLang, $wgParser;

		wfProfileIn( __METHOD__ );

		$dateStr = $wgLang->timeanddate( wfTimestampNow() );
		if ( $type == 'talk' ) {
			$track_talk = array(
				'utm_source' => 'talk_page_message',
				'utm_medium' => 'email',
				'utm_term' => 'talk_page',
				'utm_campaign' => 'talk_page_message'
			);
			$track_sender_talk = array(
				'utm_source' => 'talk_page_message',
				'utm_medium' => 'email',
				'utm_term' => 'talk_page_sender',
				'utm_campaign' => 'talk_page_message'
			);
		} else {
			$track_talk = array(
				'utm_source' => 'thumbsup_message',
				'utm_medium' => 'email',
				'utm_term' => 'talk_page',
				'utm_campaign' => 'talk_page_message'
			);
			$track_sender_talk = array(
				'utm_source' => 'thumbsup_message',
				'utm_medium' => 'email',
				'utm_term' => 'talk_page_sender',
				'utm_campaign' => 'talk_page_message'
			);
		}

		if ( $aid == 0 ) {
			return;
		}

		if ( preg_match( '/{{.*?}}/', $comment, $matches ) ) {
			return;
		}

		$t = Title::newFromID( $aid );

		if ( $type == 'talk' ) {
			$options = new ParserOptions();
			$output = $wgParser->parse( $comment, $t, new ParserOptions() );

			$comment = $output->getText();
			$comment = preg_replace( '/href="\//', 'href="' . $wgServer . '/', $comment );
			$comment = strip_tags( $comment, '<br><a>' );
		}

		$fromuser = User::newFromID( $from_uid );

		if ( isset( $t ) ) {
			$toUser = User::newFromName( $t->getText() );
		} else {
			// no article, no object
			return;
		}

		if ( !$toUser ) {
			return;
		}

		if (
			$t->getArticleID() > 0 &&
			$t->getNamespace() == NS_USER_TALK &&
			$toUser->getEmail() != '' &&
			$toUser->getOption( 'usertalknotifications' ) == '0'
		)
		{
			$talkpageLink = $t->getTalkPage()->escapeFullURL( $track_talk );
			$talkpageSenderLink = $fromuser->getTalkPage()->escapeFullURL( $track_sender_talk );

			$from_name = wfMessage( 'aen-from' )->parse();
			$subject = wfMsg(
				'aen-usertalk-subject',
				$t->getTalkPage(),
				$fromuser->getName()
			);
			$body = wfMsg(
				'aen-usertalk-body',
				$fromuser->getName(),
				$toUser->getName(),
				$talkpageLink,
				$comment,
				$dateStr,
				$talkpageSenderLink
			);

			AuthorEmailNotification::notify( $toUser, $from_name, $subject, $body );
			wfDebugLog(
				'AuthorEmailNotification',
				'notifyUserTalk send. from:' . $fromuser->getName() . ' to:' .
					$toUser->getName() . ' title:' . $t->getTalkPage() .
					"\nbody: " . $body . "\n"
			);
		} else {
			wfDebugLog(
				'AuthorEmailNotification',
				'notifyUserTalk - called no article: ' . $t->getArticleID() . "\n"
			);
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Notify $user via e-mail about $subject with $from_name as the sender.
	 *
	 * @param $user User: User to send the e-mail
	 * @param $from_name String: name of the sender of the e-mail
	 * @param $subject String: subject of the e-mail
	 * @param $body String: the actual text of the e-mail
	 * @param $type String: set this to 'text' if you don't want HTML e-mails
	 * @return Boolean: true
	 */
	static function notify( $user, $from_name, $subject, $body, $type = '' ) {
		global $wgServer;

		wfProfileIn( __METHOD__ );
		$isDev = false;
		if ( strpos( $wgServer, 'wikidiy.com' ) !== false ) {
			wfDebugLog(
				'AuthorEmailNotification',
				'in dev not notifying: TO: ' . $user->getName() .
					",FROM: $from_name\n"
			);
			$isDev = true;
			$subject = "[FROM DEV] $subject";
		}

		if ( $user->getEmail() != '' ) {
			$validEmail = '';

			if ( $user->getID() > 0 ) {
				$to_name = $user->getName();
				$to_real_name = $user->getRealName();
				if ( $to_real_name != '' ) {
					$to_name = $real_name;
				}
				$username = $to_name;
				$email = $user->getEmail();

				$validEmail = $email;
				$to_name .= " <$email>";
			}

			$from = new MailAddress( $from_name );
			$to = new MailAddress( $to_name );

			if ( $type == 'text' ) {
				if ( $isDev ) {
					$to = new MailAddress( 'elizabethwikihowtest@gmail.com' );
				}
				UserMailer::send( $to, $from, $subject, $body );
			} else {
				// For HTML e-mails
				$contentType = 'text/html; charset=UTF-8';
				if ( $isDev ) {
					$to = new MailAddress( 'elizabethwikihowtest@gmail.com' );
				}
				UserMailer::send( $to, $from, $subject, $body, null, $contentType );
			}

			wfProfileOut( __METHOD__ );
			return true;
		}
	}

	/**
	 * Show page for logged in users
	 */
	function showUser() {
		global $wgRequest, $wgOut, $wgUser, $wgResourceModules, $wgExtensionAssetsPath;

		$dbr = wfGetDB( DB_SLAVE );

		if ( isset( $wgResourceModules['ext.authorLeaderboard'] ) ) {
			$wgOut->addModules( 'ext.authorLeaderboard' );
		}

		$order = array();
		switch ( $wgRequest->getVal( 'orderby' ) ) {
			case 'popular':
				$order['ORDER BY'] = 'page_counter DESC';
				break;
			case 'time_asc':
				$order['ORDER BY'] = 'fe_timestamp ASC';
				break;
			default:
				$order['ORDER BY'] = 'fe_timestamp DESC';
		}

		//$order['LIMIT'] = $onebillion;
		$order['GROUP BY'] = 'page_id';

		$res = $dbr->select(
			array( 'firstedit', 'page' ),
			array( 'page_title', 'page_id', 'page_namespace', 'fe_timestamp' ),
			array( 'fe_page = page_id', 'fe_user_text' => $wgUser->getName() ),
			__METHOD__,
			$order
		);

		$res2 = $dbr->select(
			array( 'email_notifications' ),
			array( 'en_page', 'en_watch' ),
			array( 'en_user' => $wgUser->getID() ),
			__METHOD__
		);

		$watched = array();
		foreach ( $res2 as $row2 ) {
			$watched[$row2->en_page] = $row2->en_watch;
		}
		$articleCount = $dbr->numRows( $res );
		if ( $articleCount > 500 ) {
			$wgOut->addHTML(
				'<div style="overflow:auto;width:600px;imax-height:300px;height:300px;border:1px solid #336699;padding-left:5px:margin-bottom:10px;">' . "\n"
			);
		} else {
			$wgOut->addHTML( "<div>\n" );
		}

		// @todo FIXME
		$imgPath = $wgExtensionAssetsPath . '/AuthorEmailNotification/images/';
		if ( $wgRequest->getVal( 'orderby' ) ) {
			$orderby = '<img id="icon_navi_up" src="' . $imgPath . 'icon_navi_up.jpg" height="13" width="13" alt="" />';
		} else {
			$orderby = '<img id="icon_navi_down" src="' . $imgPath . 'icon_navi_down.jpg" height="13" width="13" alt="" />';
		}

		$wgOut->addHTML( '<form method="post">' );
		$wgOut->addHTML( '<br /><center><table width="500px" align="center" class="status">' );
		// display header
		$index = 1;
		$aen_email = wfMsg( 'aen-form-email' );
		$aen_title = wfMsg( 'aen-form-title' );
		$aen_created = wfMsg( 'aen-form-created' );
		$wgOut->addHTML(
			"<tr>
				<td><strong>$aen_email</strong></td>
				<td><strong>$aen_title</strong></td>
				<td><strong>$aen_created</strong> <a id=\"aen_date\">$orderby</a></td>
			</tr>
		");

		foreach ( $res as $row ) {
			$class = '';
			$checked = '';
			$fedate = '';

			if ( $index % 2 == 1 ) {
				$class = ' class="odd"';
			}

			$t = Title::makeTitle( $row->page_namespace, $row->page_title );
			if ( $watched[$row->page_id] ) {
				$checked = ' checked="checked"';
			}

			$fedate = date( 'M d, Y', strtotime( $row->fe_timestamp . ' UTC' ) );

			$wgOut->addHTML( "<tr$class>" );
			$wgOut->addHTML(
				'<td align="center">
					<input type="checkbox" name="articles-' . $index . '" value="' . $row->page_id . "\"$checked />
				</td>
				<td>" . Linker::link( $t, $t->getText() ) . '</td>
				<td align="center">' . $fedate . ' <!--' . $row->page_id . "--></td>\n"
			);
			$wgOut->addHTML( '</tr>' );
			$watched[$row->page_id] = 99;
			$index++;
		}

		$wgOut->addHTML( '</table>' );

		$wgOut->addHTML( '<br /><div style="width: 500px; text-align: right;">' );
		$wgOut->addHTML( '<input type="hidden" name="articlecount" value="' . $index . "\" />\n" );
		$wgOut->addHTML(
			'<input type="submit" name="action" value="' .
				wfMessage( 'aen-save-btn' )->text() . '" />' . "\n"
		);
		$wgOut->addHTML( '<br /></div>' );

		$wgOut->addHTML( '</div>' );

		foreach ( $watched as $key => $value ) {
			$t = Title::newFromID( $key );
			if ( $value != 99 ) {
				$wgOut->addHTML( "<!-- DEBUG AEN not FE: $key ==> $value *** $t <br /> -->\n" );
			}
		}

		// Debug code to test e-mails
		/*
		$wgOut->addHTML(
			"<br /><br />
				<input type='button' name='aen_rs_email' value='rising star email' />
				<input type='button' name='aen_mod_email' value='edit email' />
				<input type='button' name='aen_featured_email' value='featured email' />
				<input type='button' name='aen_viewership' value='viewership email' />\n"
		);
		*/

		$wgOut->addHTML( "</center>\n" );
		$wgOut->addHTML( "</form>\n" );
	}

	/**
	 * @param $target
	 * @param $watch
	 * @return Boolean: success state
	 */
	static function addUserWatch( $target, $watch ) {
		global $wgUser;
		$dbw = wfGetDB( DB_MASTER );

		$sql = "INSERT INTO {$dbw->tableName( 'email_notifications' )} (en_user,en_page,en_watch) ";
		$sql .= "VALUES ('" . $wgUser->getID() . "','" . $target . "'," . $watch . ") ON DUPLICATE KEY UPDATE en_watch=" . $watch;
		$ret = $dbw->query( $sql, __METHOD__ );
		return $ret;
	}

	/**
	 * @param $articles
	 */
	function addUserWatchBulk( $articles ) {
		global $wgUser;
		$dbw = wfGetDB( DB_MASTER );

		// Reset all for user
		$ret = $dbw->update(
			'email_notifications',
			array( 'en_watch = 0' ),
			array( 'en_user' => $wgUser->getID() ),
			__METHOD__
		);

		// Set articles to watch
		$articleset = implode( ',', $articles );

		foreach ( $articles as $article ) {
			$sql = "INSERT INTO {$dbw->tableName( 'email_notifications' )} (en_user,en_page,en_watch) ";
			$sql .= "VALUES ('" . $wgUser->getID() . "','" . $article . "',1) ON DUPLICATE KEY UPDATE en_watch=1";
			$ret = $dbw->query( $sql, __METHOD__ );
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgServer, $wgRequest, $wgOut, $wgUser;

		// Don't allow blocked users to use this special page
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// Can't use the special page if database is locked...
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// Anons can't use this special page because they don't have /user/
		// preferences, obviously
		if( $wgUser->getID() == 0 ) {
			$wgOut->showErrorPage(
				'aen-no-login',
				'aen-no-login-text',
				array( $this->getTitle()->getPrefixedDBkey() )
			);
			return;
		}

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$action = $wgRequest->getVal( 'action' );

		$dbr = wfGetDB( DB_SLAVE );

		if ( $action == 'Save' ) {
			$articles = array();
			$articleCount = $wgRequest->getVal( 'articlecount' );
			for( $i = 1; $i <= ( $articleCount + 1 ); $i++ ) {
				$item = $wgRequest->getVal( 'articles-' . $i );
				if ( ( $item != '' ) && ( $item != 0 ) ) {
					array_push( $articles, $item );
				}
			}

			$this->addUserWatchBulk( $articles );
		} elseif ( $action == 'update' ) {
			$watch = 1;
			$watch = $wgRequest->getVal( 'watch' );

			if ( ( $target != '' ) ) {
				self::addUserWatch( $target, $watch );
			} else {
				wfDebugLog( 'AuthorEmailNotification', 'AJAX call with improper parameters.' );
			}
			return;
		} elseif ( $action == 'addNotification' ) {
			$email = '';
			$email = $wgRequest->getVal( 'email' );

			$this->addNotification( $target, $email );

			return;
		} elseif ( $action == 'updatePreferences' ) {
			wfDebugLog( 'AuthorEmailNotification', 'in updatePreferences' );
			if ( $wgRequest->getVal( 'dontshow' ) == 1 ) {
				wfDebugLog( 'AuthorEmailNotification', 'in dontshow' );
				$wgUser->setOption( 'enableauthoremail', 1 );
				wfDebugLog( 'AuthorEmailNotification', 'in settingoption' );
				$wgUser->saveSettings();
			}
			return;
		} elseif ( $action == 'testsend' ) {
			// For testing
			$subject = '';
			$body = '';

			$title = 'Help Your Dog Lose Weight';
			$titleLink = Linker::link( Title::newFromText( $title ), $title );

			switch( $target ) {
				case 'rs':
					$subject = wfMsg( 'aen-rs-subject', $title );
					$body = wfMsg( 'aen-rs-body', $wgUser->getName(), $titleLink );
					break;
				case 'mod':
					$subject = wfMessage( 'aen-mod-subject', $title )->parse();
					$body = wfMsg( 'aen-mod-body', $wgUser->getName(), $titleLink );
					break;
				case 'featured':
					$subject = wfMessage( 'aen-featured-subject', $title )->parse();
					$body = wfMsg( 'aen-featured-body', $wgUser->getName(), $titleLink );
					break;
				case 'viewership':
					$subject = wfMsg( 'aen-viewership-subject', $title, '12768' );
					$body = wfMsg( 'aen-viewership-body', $wgUser->getName(), $titleLink, '12768' );
					break;
			}

			if ( $wgUser->getEmail() != '' ) {
				$from_name = wfMessage( 'aen-from' )->parse();
				self::notify( $wgUser, $from_name, $subject, $body );
			}

			return;
		}

		// Add JS
		$wgOut->addModules( 'ext.authorEmailNotification' );

		$wgOut->addHTML( wfMsg( 'aen-emailn-title' ) . '<br /><br />' );
		$this->showUser();

		return;
	}
}

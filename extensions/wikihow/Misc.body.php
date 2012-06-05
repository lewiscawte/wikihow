<?php
//
// We don't really have a place to put random, small pieces of functionality.
// This class addresses that.
//

class Misc {

	/*
	 * adminPostTalkMessage
	 * - returns true/false
	 * 
	 * $to_user = User object of who is getting this talk message
	 * $from_user = User object of who is sending this talk message
	 * $comment	= The text that is displayed in the talk page message
	 */
	public static function adminPostTalkMessage($to_user, $from_user, $comment) {
		global $wgLang;
		$existing_talk = '';
		
		//make sure we have everything we need...
		if (empty($to_user) || empty($from_user) || empty($comment)) return false;
		
		$from = $from_user->getName();
		if (!$from) return false; //whoops
		$from_realname = $from_user->getRealName();
		$dateStr = $wgLang->date(wfTimestampNow());
		$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $from, $from_realname, $comment);

		$talkPage = $to_user->getUserPage()->getTalkPage();

		if ($talkPage->getArticleId() > 0) {
			$r = Revision::newFromTitle($talkPage);
			$existing_talk = $r->getText() . "\n\n";
		}
		$text = $existing_talk . $formattedComment ."\n\n";
		
		$flags = EDIT_FORCE_BOT | EDIT_SUPPRESS_RC;
		
		$article = new Article($talkPage);
		$result = $article->doEdit($text, "", $flags);
		
		return $result;
	}

}


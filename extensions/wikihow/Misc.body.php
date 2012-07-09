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

	public static function getDTDifferenceString($date, $isUnixTimestamp = false) {
		wfLoadExtensionMessages('Misc');
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

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
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
}


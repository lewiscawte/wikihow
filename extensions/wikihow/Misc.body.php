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

	// Format a binary number 
	public static function formatBinaryNum($n) {
		return sprintf('%032b', $n);
	}

	// Check if an $ip address (string) is within an IP network
	// and netmask, defined in $range (string).
	//
	// Note: $ip and $range need to be perfectly formatted!
	public static function isIpInRange($ip, $range) {
		list($range, $maskbits) = explode('/', $range);
		list($i1, $i2, $i3, $i4) = explode('.', $ip);
		list($r1, $r2, $r3, $r4) = explode('.', $range);
		$numi = ($i1 << 24) | ($i2 << 16) | ($i3 << 8) | $i4;
		$numr = ($r1 << 24) | ($r2 << 16) | ($r3 << 8) | $r4;
		$mask = 0;
		for ($i = 1; $i <= $maskbits; $i++) {
			$mask |= 1 << (32 - $i);
		}
		$masked = $numi & $mask;
		//print self::formatBinaryNum($masked) . ' ' .
		//	self::formatBinaryNum($numr) . ' ' . 
		//	self::formatBinaryNum($numi) . "\n";
		return $masked === $numr;
	}

	/**
	 * Add a check to see if the proxy we're going through is CloudFlare. See 
	 * ranges:
	 *
	 * https://www.cloudflare.com/wiki/What_are_the_CloudFlare_IP_address_ranges
	 */
	function checkCloudFlareProxy($ip, &$trusted) {
		$ranges = array(
			'204.93.240.0/24', '204.93.177.0/24', '199.27.128.0/21',
			'173.245.48.0/20', '103.22.200.0/22', '141.101.64.0/18',
			'108.162.192.0/18', '190.93.240.0/20',
		);
		if (!$trusted && preg_match('@^(\d{1,3}\.){3}\d{1,3}$@', $ip)) {
			foreach ($ranges as $range) {
				if (self::isIpInRange($ip, $range)) {
					$trusted = true;
					break;
				}
			}
		}
		return true;
	}

}


<?

class RCTestAdmin extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RCTestAdmin' );
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$wgOut->setPageTitle('RC Patrol Test Scores');
		$this->printResponse();
	}

	function printResponse() {
		global $wgOut, $wgRequest;

		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$vars['results'] = $this->getScores();
		$vars['days'] = $wgRequest->getVal("days", 7);
		$html = EasyTemplate::html('RCTestAdmin', $vars);
		$wgOut->addHTML($html);
	}

	function getScores() {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$ts = wfTimestamp(TS_MW, time() - 24 * 3600 * $wgRequest->getVal("days", 7));
		$res = $dbr->select('rctest_scores', 
			array('rs_user_name', 'count(rs_user_name) as total', 'sum(rs_correct) as correct'), 
			array("rs_timestamp >= '{$ts}'"), 
			'RCTestAdmin::getScores', 
			array('GROUP BY' => 'rs_user_name', 'ORDER BY' => 'total DESC, correct ASC'));

		$scores = array();
		while ($row = $dbr->fetchObject($res)) {
			$score = get_object_vars($row);
			$total = $score['total'];
			$score['incorrect'] = $this->percent($total - $score['correct'], $total);
			$score['correct'] = $this->percent($score['correct'], $total);
			$scores[] = $score;
		}

		return $scores;
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0) . "%";
	}

}

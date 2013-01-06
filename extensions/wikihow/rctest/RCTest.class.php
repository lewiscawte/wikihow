<?
/**
 * RCTest debug flag -- always check-in as false and make a
 * local edit.
 */
define('RCT_DEBUG', true);

class RCTest {

	var $adjustedPatrolCount;
	var $basePatrolCount;
	var $userInfo;
	var $testInfo;

	function __construct() {
	}


	// Get the number of patrols total - number of patrols a user had when they first start using the rc test tool
	public function getAdjustedPatrolCount() {
		if (!$this->adjustedPatrolCount) {
			$this->adjustedPatrolCount = $this->getTotalPatrols() - $this->getBasePatrolCount();
		}
		return $this->adjustedPatrolCount;
	}


	private function getTotalPatrols() {
		$dbr = wfGetDB(DB_SLAVE);
		$total = $dbr->selectField('logging', 'count(*)', RCPatrolStandingsIndividual::getOpts());
		return $total;
	}

	// Gets the number of patrols a user has when they first start using the rc test tool
	private function getBasePatrolCount() {
		if (!$this->userInfo) {
			if ($this->userExists()) {
				$this->fetchUserInfo();
			}
			else {
				return $this->getTotalPatrols();;
			}

		}
		$userInfo = $this->userInfo;
		return $userInfo['ru_base_patrol_count'];
	}

	public function getUserInfo() {
		if (!$this->userInfo) {
			$this->fetchUserInfo();
		}
		return $this->userInfo;
	}

	private function fetchUserInfo() {
		global $wgUser;

		if(!$this->userExists()) {
			$this->addUser($wgUser);
		}

		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow('rctest_users', array('*'), array('ru_user_id' => $wgUser->getId()));
		if(sizeof($row)) {
			$this->userInfo = get_object_vars($row);
		}
		else {
			throw new Exception("Couldn't retrieve test user");
		}
	}

	private function userExists() {
		global $wgUser;
		$dbr = wfGetDB(DB_SLAVE);
		$exists = $dbr->selectField('rctest_users', array('count(*) as C'), array('ru_user_id' => $wgUser->getId()));
		return $exists > 0;
	}

	private function addUser(&$user) {
		$dbw = wfGetDB(DB_MASTER);
		$basePatrolCount = $this->getBasePatrolCount();
		$dbw->insert('rctest_users', array('ru_user_id' => $user->getId(), 'ru_user_name' => $user->getName(), 'ru_base_patrol_count' => $basePatrolCount));
	}

	public function getResultParams() {
		$testInfo = $this->getTestInfo();

		$r = Revision::newFromId($testInfo['rq_rev_new']);
		if (!$r) {
			throw new Exception("Unable to create revision from testInfo['rq_rev_new'] = {$testInfo['rq_rev_new']}");
		}
		$params['title'] = $r->getTitle();
		$params['old'] = $testInfo['rq_rev_new'];
		$params['new'] = $testInfo['rq_rev_old'];
		return $params;
	}

	public function getTestInfo($testId = null) {
		if (!$this->testInfo) {
			$this->fetchTestInfo($testId);
		}
		return $this->testInfo;
	}

	private function fetchTestInfo($testId = null ) {
		//  If a specific test id is specified, just fetch that
		if ($testId) {
			$this->fetchSpecificTestInfo($testId);
			return;
		}

		// Make sure it's test time
		if (!$this->isTestTime()) {
			throw new Exception("Can't fetch test info if it's not a valid test patrol count");
		}

		$userInfo = $this->getUserInfo();

		// If debugging, use the debug fetcher
		if (RCT_DEBUG) {
			$this->fetchDebugTestInfo();
			return;
		}

		$testDifficulty = array(2 => 1, 10 => 1, 20 => 2, 50 => 2, 100 => 3, 250 => 3);
		$numPatrols = $this->getAdjustedPatrolCount();
		$difficulty = $testDifficulty[$numPatrols];

		$dbr = wfGetDB(DB_SLAVE);
		$sql = "SELECT * FROM rctest_quizzes WHERE rq_difficulty = $difficulty ";
		// Exclude any quizzes already taken
		if (sizeof($userInfo['ru_quiz_revs'])) {
			$sql .= " AND rq_revhi NOT IN (" . explode(',', $userInfo['ru_quiz_revs']) . ") ";
		}
		$sql .= " LIMIT 1";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		$this->testInfo = get_object_vars($row);
	}

	public function gradeTest($testId, $response) {
		$testInfo = $this->getTestInfo($testId);
		$correctIds = $testInfo['rq_ideal_responses'] . ',' . $testInfo['rq_acceptable_responses'];
		$correctIds = explode(",", $correctIds);
		$correct = false !== array_search($response, $correctIds);
		$this->recordScore($correct, $response);

		$result['correct'] = intVal($correct);
		$result['exp'] = $testInfo['rq_explanation'];
		$result['coach'] = $testInfo['rq_coaching'];
		return $result;
	}

	private function recordScore($correct, $response) {
		$ui = $this->getUserInfo();
		$ti = $this->getTestInfo();
		$correct = intVal($correct);
		$dbw = wfGetDB(DB_MASTER);
		$response = $dbw->strencode($response);
		$sql = "
			INSERT IGNORE INTO rctest_scores 
				(rs_user_id, rs_user_name, rs_quiz_id, rs_correct, rs_response) 
			VALUES (
				{$ui['ru_user_id']},
				'{$ui['ru_user_name']}', 
				{$ti['rq_id']},
				$correct, 
				$response
			)";
		$dbw->query($sql);
		$this->setTaken($ti['rq_id']);
	}

	private function setTaken($testId) {
		$userInfo = $this->getUserInfo();
		$takenTestIds = array();
		if ($userInfo['ru_quiz_ids']) {
			$takenTestIds = explode(",", $userInfo['ru_quiz_ids']);
		}
		if (false === array_search($testId, $takenTestIds)) {
			$takenTestIds[] = $testId;
		}
		$takenTestIds = implode(",", $takenTestIds);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('rctest_users', array('ru_quiz_ids' => $takenTestIds), array('ru_user_id' => $userInfo['ru_user_id']));
		$this->userInfo['ru_quiz_ids'] = $takenTestIds;
	}

	private function fetchSpecificTestInfo($testId) {
		$dbr = wfGetDB(DB_SLAVE);
		$testId = $dbr->strencode($testId);
		$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $testId));
		$this->testInfo = get_object_vars($row);
	}

	private function fetchDebugTestInfo() {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$mode = $wgRequest->getVal('rct_mode');
		$id = $wgRequest->getVal('rct_id');
		// Dev server doesn't have rc test revisions in database yet.  Fake it
		if (!$mode || $mode == 'dev') {
			if (time() % 2 == 0)  {
				$row['rq_id'] = 3;
				$row['rq_rev_new'] = 5661600;
				$row['rq_rev_old'] = 5661350;
			} else {
				$row['rq_id'] = 4;
				$row['rq_rev_new'] = 2007382;
				$row['rq_rev_old'] = 2007378;
			}

			if ($id) {
				$row['rq_id'] = $id;
			}
			$row['rq_difficulty'] = 3;
			$row['rq_ideal_responses'] = "0,1";
			$row['rq_acceptable_responses'] = "2";
			$row['rq_incorrect_responses'] = "3";
			$row['rq_correct_txt'] = "Congratulations, you're correct!";
			$row['rq_incorrect_txt'] = "Ooops, better luck next time!";
			$this->testInfo = $row;
		}
		else if ($id) {
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id));
			$this->testInfo = get_object_vars($row);
		}
		else if ($mode == 'carrot') {
			// get one of the 44 tests
			$id = 1 + (time() % 44);
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id));
			$this->testInfo = get_object_vars($row);
		}
	}


	public function isTestTime() {
		// It's always test time when we're debugging!
		if (RCT_DEBUG) {
			return $this->isDebugTestTime();
		}

		// Check how many patrols the user has by subtracting total patrols from starting patrol count for rc test
		// if is 2, 10, 20, 50, 100, or increments of 250 after that, show it's test time!
		$testPatrolCounts = array (2, 10, 20, 50, 100, 250);
		$numPatrols = $this->getAdjustedPatrolCount();
		if (in_array($numPatrols, $testPatrolCounts) || ($numPatrols > 0 && $numPatrols % 250 == 0)) {
			return true;
		}
		else {
			return false;
		}
	}

	private function isDebugTestTime() {
		global $wgRequest;
		return $wgRequest->getVal('rct_mode') ? true : time() % 3 == 0;
	}

	public function getTestHtml() {
		// Only add the html if RC Patrol is supposed to show a test 
		if (!$this->isTestTime()) {
			return;
		}

		$testInfo = $this->getTestInfo();
		$html = "<div id='rct_data'>" . $testInfo['rq_id'] . "</div>";
		$html .= HtmlSnips::makeUrlTags('js', array('rctest.js'), 'extensions/wikihow/rctest', true);
		$html .= HtmlSnips::makeUrlTags('css', array('rctest.css'), 'extensions/wikihow/rctest', true);
		return $html;
	}
}

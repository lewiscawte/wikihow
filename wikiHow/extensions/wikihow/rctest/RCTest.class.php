<?
// Hook to mark quizzes deleted that reference pages no longer in the database
$wgHooks['ArticleDelete'][] = array("wfMarkRCTestDeleted"); 

function wfMarkRCTestDeleted($article, $user, $reason) {
	try {	
		$dbw = wfGetDB(DB_MASTER); 
		$id = $article->getTitle()->getArticleID();
		$dbw->update('rctest_quizzes', array('rq_deleted' => 1), array('rq_page_id' => $id));
	} catch (Exception $e) {

	}
	return true;
}

/*
* Class that is used to inject test patrols into the RC Patrol tool.
*/
class RCTest {

	var $adjustedPatrolCount = null;
	var $basePatrolCount = null;
	var $userInfo = null;
	var $testInfo = null;
	var $moreTests = null;

	function __construct() {
	}


	// Get the number of patrols total - number of patrols a user had when they first start using the rc test tool
	public function getAdjustedPatrolCount() {
		if (is_null($this->adjustedPatrolCount)) {
			$this->adjustedPatrolCount = $this->getTotalPatrols() - $this->getBasePatrolCount();
		}
		return $this->adjustedPatrolCount;
	}

	public function getTotalPatrols() {
		$dbr = wfGetDB(DB_SLAVE);
		$total = $dbr->selectField('logging', 'count(*)', RCPatrolStandingsIndividual::getOpts());
		return $total;
	}

	// Gets the number of patrols a user has when they first start using the rc test tool
	private function getBasePatrolCount() {
		if (is_null($this->userInfo)) {
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
		if (is_null($this->userInfo)) {
			$this->fetchUserInfo();
		}
		return $this->userInfo;
	}

	private function fetchUserInfo() {
		global $wgUser;

		if(!$this->userExists()) {
			$this->addUser($wgUser);
		}

		// Use the master so we can fetch the user if it was just created
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow('rctest_users', array('*'), array('ru_user_id' => $wgUser->getId()));
		if(is_object($row)) {
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
		$dbw->insert('rctest_users', array('ru_user_id' => $user->getId(), 'ru_user_name' => $user->getName(), 'ru_base_patrol_count' => $basePatrolCount, 'ru_next_test_patrol_count' => 2));
	}

	public function getResultParams() {
		$testInfo = $this->getTestInfo();

		$r = Revision::newFromId($testInfo['rq_rev_new']);
		if (!$r) {
			throw new Exception("Unable to create revision from testInfo['rq_rev_new'] = {$testInfo['rq_rev_new']}");
		}
		$params['title'] = $r->getTitle();
		$params['old'] = $testInfo['rq_rev_old'];
		$params['new'] = $testInfo['rq_rev_new'];
		return $params;
	}

	public function getTestInfo($testId = null) {
		if (is_null($this->testInfo)) {
			$this->fetchTestInfo($testId);
		}
		return $this->testInfo;
	}

	private function fetchTestInfo($testId = null ) {
		global $wgRequest;

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
		if ($wgRequest->getVal('rct_mode')) {
			$this->fetchDebugTestInfo();
			return;
		}

		$difficulty = $this->getTestDifficulty();

		$dbr = wfGetDB(DB_SLAVE);
		$sql = "SELECT * FROM rctest_quizzes WHERE rq_deleted = 0 AND rq_difficulty <= $difficulty ";
		// Exclude any quizzes already taken
		if (sizeof($userInfo['ru_quiz_ids'])) {
			$sql .= " AND rq_id NOT IN (" . $userInfo['ru_quiz_ids'] . ") ";
		}
		$sql .= " ORDER BY rq_difficulty LIMIT 1";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		if ($row) {
			$this->testInfo = get_object_vars($row);	
			$this->setTestActive(true);
		} else {
			throw new Exception("Couldn't fetch Test: $sql");
		}
	}

	private function getTestDifficulty() {
		$nextTestPatrolCount = $this->getNextTestPatrolCount();
		if ($nextTestPatrolCount > 250) {
			$difficulty = 3;
		}
		else {
			$testDifficulties = array(2 => 1, 10 => 1, 20 => 2, 50 => 2, 100 => 3, 250 => 3);
			$difficulty = $testDifficulties[$nextTestPatrolCount];
		}
		return $difficulty;
	}

	private function setNextTestPatrolCount() {
		$userInfo = $this->getUserInfo();
		$nextPatrolCount = $userInfo['ru_next_test_patrol_count'];
		// Increment next patrol count by 250 after the 250 mark
		if ($nextPatrolCount >= 250) {
			$newNextPatrolCount = $nextPatrolCount - ($nextPatrolCount % 250) + 250;
		}
		else {
        	$testPatrolCounts = array (2, 10, 20, 50, 100, 250);
			for ($i = 0; $i < sizeof($testPatrolCounts); $i++) {
				if ($nextPatrolCount >= $testPatrolCounts[$i] && $nextPatrolCount < $testPatrolCounts[$i + 1]) {
					$newNextPatrolCount = $testPatrolCounts[$i + 1];
					break;
				}
			}
		}
		if ($nextPatrolCount == $newNextPatrolCount) {
			throw new Exception('rctest next test patrol count not updating properly');
		}
		$dbw = wfGetDB(DB_MASTER);
		$userInfo['ru_next_test_patrol_count'] = $newNextPatrolCount;
		$dbw->update('rctest_users', array('ru_next_test_patrol_count' => $newNextPatrolCount), array('ru_user_id' => $userInfo['ru_user_id']));
	}

	private function getNextTestPatrolCount() {
		$userInfo = $this->getUserInfo();
		return $userInfo['ru_next_test_patrol_count'];
	}

	/*
	* Sets a cookie that denotes whether a test is currently active/administered.  
	*/
	private function setTestActive($active) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		$expiration = $active ? 0 : time() - (3600 * 24);
		$value = $active ? '1' : '';
		setcookie( $wgCookiePrefix.'_rct_a', $value, $expiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}

	/*
	* Checks for the existence of a cookie which denotes that there's an active test
	*/
	private function isTestActive() {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		$active  = $_COOKIE[$wgCookiePrefix . '_rct_a'];
		return !is_null($active);

	}

	public function debugGradeTest($testId, $response) {
		$testInfo = $this->getTestInfo($testId);
		$correctIds = $testInfo['rq_ideal_responses'] . ',' . $testInfo['rq_acceptable_responses'];
		$correctIds = explode(",", $correctIds);

		// See if the response was in the ideal or acceptable responses.  Skip and a link press are always acceptable responses.
		$correct = false !== array_search($response, $correctIds) || $response == RCTestGrader::RESP_SKIP || $response == RCTestGrader::RESP_LINK;

		// Turn off the active flag for the test
		$this->setTestActive(false);

		$result['ideal_responses'] = $testInfo['rq_ideal_responses'];
		$result['correct'] = intVal($correct);
		$result['exp'] = $testInfo['rq_explanation'];
		$result['coach'] = $testInfo['rq_coaching'];
		return $result;
	}

	public function gradeTest($testId, $response) {
		global $wgRequest;

		if ($wgRequest->getVal('rct_mode')) {
			return $this->debugGradeTest($testId, $response);
		}

		$testInfo = $this->getTestInfo($testId);
		$correctIds = $testInfo['rq_ideal_responses'] . ',' . $testInfo['rq_acceptable_responses'];
		$correctIds = explode(",", $correctIds);

		// See if the response was in the ideal or acceptable responses.  Skip and a link press are always acceptable responses.
		$correct = false !== array_search($response, $correctIds) || $response == RCTestGrader::RESP_SKIP || $response == RCTestGrader::RESP_LINK;

		// Don't record a score for this test if the user skipped the patrol
		if ($response != RCTestGrader::RESP_SKIP || $response != RCTestGrader::RESP_LINK) {
			$this->recordScore($correct, $response);
		}

		// Record that the user has taken the test
		$this->setTaken($testInfo['rq_id']);

		// Update the patrol count necessary for the next test to be displayed
		$this->setNextTestPatrolCount();

		// Turn off the active flag for the test
		$this->setTestActive(false);

		$result['correct'] = intVal($correct);
		$result['ideal_responses'] = $testInfo['rq_ideal_responses'];
		$result['exp'] = $testInfo['rq_explanation'];
		$result['coach'] = $testInfo['rq_coaching'];

		return $result;
	}

	private function recordScore($correct, $response) {
		$ui = $this->getUserInfo();
		$ti = $this->getTestInfo();
		$correct = intVal($correct);
		$timestamp = wfTimestampNow();
		$dbw = wfGetDB(DB_MASTER);
		$response = $dbw->strencode($response);

		$sql = "
			INSERT IGNORE INTO rctest_scores 
				(rs_user_id, rs_user_name, rs_quiz_id, rs_correct, rs_response, rs_timestamp) 
			VALUES (
				{$ui['ru_user_id']},
				'{$ui['ru_user_name']}', 
				{$ti['rq_id']},
				$correct, 
				$response,
				'$timestamp'
			)";
		$dbw->query($sql);
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

	/*
	* Loads a specific test given a test id
	*/
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
		if ($id) {
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id));
			$this->testInfo = get_object_vars($row);
		}
		else {
			$numTests = 55;
			$id = 1 + (rand(0,$numTests) % $numTests);
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id, "rq_deleted" => 0));
			if ($row) {
				$this->testInfo = get_object_vars($row);
			}
			else {
				$this->fetchDebugTestInfo();
			}
		}
	}


	/*
	* Returns true if browser is anything other than IE6 or IE7, false otherwise.
	*/
	private function isCompatibleBrowser() {
		return !preg_match('@MSIE (6|7)@',$_SERVER['HTTP_USER_AGENT']);
	}

	/*
	* Returns true if a test should be displayed, false otherwise
	*/
	public function isTestTime() {
		global $wgRequest;
		

		// RCPatrol Test doesn't work for IE 7 and IE 6 beecause of use of negative margins in the css.
		// Don't allow RC Test to show for users of these browsers.
		if (!$this->isCompatibleBrowser()) {
			return false;
		}

		$userInfo = $this->getUserInfo();

		// It's always test time when we're debugging!
		if ($wgRequest->getVal('rct_mode')) {
			return $this->isDebugTestTime();
		}

		// Return false if we are just marking an item as patrolled/skip.
		// We do this because the return data from this is ignored by the client
		// and overwritten by an immmediately following grabnext. See rcpatrol.js for
		// more details
		if (!$wgRequest->getVal('grabnext')) {
			return false;
		}

		// Check to see if the test is currently active 
		if ($this->isTestActive()) {
			return false;
		}

		// Return false if the user has already taken all the tests
		if (!$this->isMoreTests()) {
			return false;
		}

		// If we're past the patrol count for the next test, it's test time
		return $this->getAdjustedPatrolCount() > $this->getNextTestPatrolCount() ? true : false;
	}

	private function isDebugTestTime() {
		global $wgRequest;
		return $wgRequest->getVal('rct_mode') ? true : time() % 3 == 0;
	}

	private function isMoreTests() {
		if (is_null($this->moreTests)) {
			$dbr = wfGetDB(DB_SLAVE);
			$difficulty = $this->getTestDifficulty();
			$sql = "SELECT count(*) as C FROM rctest_quizzes WHERE rq_deleted = 0 AND rq_difficulty <= $difficulty";
			// Exclude any quizzes already taken
			$userInfo = $this->getUserInfo();
			if (sizeof($userInfo['ru_quiz_ids'])) {
				$sql .= " AND rq_id NOT IN (" . $userInfo['ru_quiz_ids'] . ") ";
			}
			$res = $dbr->query($sql);
			$row = $dbr->fetchObject($res);
			$this->moreTests = $row->C > 0;
		}
		return $this->moreTests;
	}

	public function getTestHtml() {
		// Only add the html if RC Patrol is supposed to show a test 
		if (!$this->isTestTime()) {
			return;
		}

		$testInfo = $this->getTestInfo();
		$html = "<div id='rct_data'>" . $testInfo['rq_id'] . "</div>";
		$html .= HtmlSnips::makeUrlTags('js', array('rctest.js'), 'extensions/wikihow/rctest', false);
		$html .= HtmlSnips::makeUrlTags('css', array('rctest.css'), 'extensions/wikihow/rctest', false);
		return $html;
	}

	/*
	* Returns true if rctest preference is set for user, false otherwise. 
	* If preference hasn't been set, defaults preference to on
	*/
	static function isEnabled($userId = null) {
		global $wgUser;
		
		if (is_null($userId)) {
			$userId = $wgUser->getId();
		}

		if ($userId > 0) {
			$u = User::newFromId($userId);
			$option = $u->getOption('rctest');
			// If the option hasn't been initialized yet, set it to on (0) by default
			if ($option === '') {
				$u->setOption('rctest', 0);
				$u->saveSettings();
				$option = 0;
			}
		}
		else {
			// This preference doesn't apply to anons
			$option = 1;
		}
		return !intVal($option);
	}
}

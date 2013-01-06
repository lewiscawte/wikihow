<?

class RCTestGrader extends UnlistedSpecialPage {
	// Response Constants
	const RESP_QUICKNOTE = 1;
	const RESP_QUICKEDIT = 2;
	const RESP_ROLLBACK = 3;
	const RESP_SKIP = 4;
	const RESP_PATROLLED = 5;
	const RESP_THUMBSUP = 6;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RCTestGrader' );
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'prefsnologintext' );
			return;
		}

		$rcTest = new RCTest();
		$testId = $wgRequest->getVal('id');
		$response = $wgRequest->getVal('response');
		$result = $rcTest->gradeTest($testId, $response);
		$wgOut->setArticleBodyOnly(true);
		$this->printResponse($result, $response);
	}

	function printResponse($testResult, $response) {
		global $wgOut;

		wfLoadExtensionMessages('RCTestGrader');
		// Display a special message if the user skipped
		if ($response == RCTestGrader::RESP_SKIP) {
			$testResult['correct'] = wfMsg('rct_skip');
		} else {
			$testResult['correct'] = $testResult['correct'] ? wfMsg('rct_correct') : wfMsg('rct_incorrect');
		}
		$testResult['response'] = $this->getButtonText($response);
		$testResult['heading'] = wfMsg('rct_heading');
		$testResult['intro'] = wfMsg('rct_intro');

		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$html = EasyTemplate::html('RCTestGrader', $testResult);
		$wgOut->addHtml($html);
	}

	function getButtonText($response) {
		wfLoadExtensionMessages('RCTestGrader');
		return wfMsg('button_' . $response);
	}
}

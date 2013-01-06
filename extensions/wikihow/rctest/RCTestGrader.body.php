<?

class RCTestGrader extends UnlistedSpecialPage {

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
		$testResult['correct'] = $testResult['correct'] ? wfMsg('rct_correct') : wfMsg('rct_incorrect');
		$testResult['response'] = $this->getButtonText($response);

		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$html = EasyTemplate::html('RCTestGrader', $testResult);
		$wgOut->addHtml($html);
	}

	function getButtonText($response) {
		wfLoadExtensionMessages('RCTestGrader');
		return wfMsg('button_' . $response);
	}
}

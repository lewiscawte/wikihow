<?
/*
* Mobile QG tool used on mobile wikiHow
*/
class MQG extends UnlistedSpecialPage {

	private $testType = null;
	private $pictureTest = array(7091380, 7144441, 7219401, 7218663, 7243017, 6294667, 5896819);
	//private $pictureTest = array(7810970, 7751394, 7590823, 7820277, 7784697, 7810190, 7628461, 6676800, 7797642, 7772818, 7816548, 7664662, 7813861, 7790215, 7232728);
	private $yesNoTest = array(5213890, 7147850, 6355541, 6438723, 6460734, 6722363, 5474838);
	private $ratingTest = array(5213890, 7147850, 6355541, 6438723, 6460734, 6722363, 5474838);
	private $recommendTest = array(6674256, 5822729, 5584360, 6911606);

	// The qg item to display
	private $qgItem = null;

	// The revision to display
	private $r = null;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('MQG');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $IP, $wgArticle, $wgUser, $isDevServer;

		wfProfileIn(__METHOD__);
		$wgOut->disable(); 
		//header('Vary: Cookie');

		require_once("$IP/extensions/wikihow/mobile/MobileHtmlBuilder.class.php");
		if ($wgRequest->getVal('fetchInnards')) {
			echo json_encode($this->getInnards());
			wfProfileOut(__METHOD__);
			return;
		} else if ($wgRequest->wasPosted()) {
			echo json_encode($this->getInnards());
			wfProfileOut(__METHOD__);
			return;
		} else {
			// Only for initial load
			echo $this->getShell();
		}

		wfProfileOut(__METHOD__);
	}

	private function getInnards() {
		wfProfileIn(__METHOD__);
		$qgItem = $this->getNext();
		$res = $this->getData($qgItem);
		
		$res['html'] = $this->getBodyHtml($qgItem);

		wfProfileOut(__METHOD__);
		return $res;
	}

	private function printInnards(&$innards) {
		wfProfileIn(__METHOD__);
		$wgOut->disable(); 
		header('Vary: Cookie' );
		$result = $this->getInnards();
		echo json_encode($result);
		wfProfileOut(__METHOD__);
		return;
	}

	private function getShell() {
		wfProfileIn(__METHOD__);
		$qgItem = $this->getNext();
		$res = $this->getData($qgItem);
		wfProfileOut(__METHOD__);
		return $this->getShellHtml($qgItem);
	}

	private function getTestRevIds($qgTestType) {
		// default test type
		$testType = $this->pictureTest;

		switch ($qgTestType) {
			case 'pic': 
				$testType = $this->pictureTest;
				break;
			case 'yesno':
				$testType = $this->yesNoTest;
				break;
			case 'rating':
				$testType = $this->recommendTest;
			case 'recommend':
				$testType = $this->recommendTest;
				break;
		}

		return $testType;
	}

	private function getNextRevId() {
		global $wgRequest;
		$revId = $wgRequest->getVal('qc_rev_id', 'start');
		$qgTestRevs = $this->getTestRevIds($this->getQGTestType());

		if ($revId == "null" || $revId == "" || $revId == "start") {
			return $qgTestRevs[0];
		}

		$pos = array_search($revId, $qgTestRevs);
		// Oops. Something must be wrong. We couldn't find the revId
		if ($pos === false) {
			return null;
		}
		// last one in the queue.  Return null to indicate there aren't any more revs to look at
		if ($pos == sizeof($qgTestRevs) - 1) {
			return null;
		}

		return $qgTestRevs[++$pos];
	}

	private function getQGTestType() {
		global $wgRequest;
		$qgType = $wgRequest->getVal('qc_type', 'pic');
		if ($qgType == "null" || $qgType == "") {
			$qgType = "pic";
		}

		return $qgType;
	}
	private function getNext() {
		global $wgRequest;

		wfProfileIn(__METHOD__);

		$qgItem = null;
		if ($revId = $this->getNextRevId()) {
			$qgItem = array('qg_rev_id' => $revId, 'qg_type' => $this->getQGTestType());
			$this->qgItem = $qgItem;
			$this->r = Revision::newFromId($revId);
			$this->t = $this->r->getTitle(); 
		}
		wfProfileOut(__METHOD__);
		if (is_null($qgItem)) {
			//throw new Exception("qgItem is null");
		}
		return $qgItem;
	}

	private function getBodyHtml(&$qgItem) {
		wfProfileIn(__METHOD__);
		$vars = $this->getBodyVars($qgItem);
		wfLoadExtensionMessages('MQG');
		$html = null;
		if ($qgItem) {
			$this->setTemplatePath();
			$html = EasyTemplate::html('mqgtest_body.tmpl.php', $vars);
		} else {
			$this->setTemplatePath();
			$html = EasyTemplate::html('mqg_finished.tmpl.php');
		}
		wfProfileOut(__METHOD__);
		return $html;
	}
	private function getShellHtml(&$qgItem) {
		wfProfileIn(__METHOD__);
		$vars = $this->getShellVars($qgItem);
		wfLoadExtensionMessages('MQG');
		$this->setTemplatePath();
		wfProfileOut(__METHOD__);
		return EasyTemplate::html('mqg.tmpl.php', $vars);
	}

	private function getShellVars(&$qgItem) {
		wfProfileIn(__METHOD__);
		$vars['randomUrl'] = '/' . wfMsg('special-randomizer');
		$vars['mqg_title'] = 'Mobile QG';
		$vars['mqg_css'] = HtmlSnips::makeUrlTags('css', array('mqgtest.css'), 'extensions/wikihow/mqg', false);	
		$vars['mqg_js'] = HtmlSnips::makeUrlTags('js', array('mqgtest.js'), 'extensions/wikihow/mqg', false);	
		wfProfileOut(__METHOD__);
		return $vars;
	}

	private function getPromptHtml(&$qgItem) {
		global $wgRequest;
		$html = "";
		$testType = null;
		switch ($qgItem['qg_type']) {
			case "pic":
				$testType = new MQGPhotoTest($qgItem, $this->r);
				break;
			case "yesno":
				$testType = new MQGYesNoTest($qgItem, $this->r);
				break;
			case "rating":
				$testType = new MQGRatingTest($qgItem, $this->r);
				break;
			case "recommend":
				$testType = new MQGRecommendTest($qgItem, $this->r);
				break;
		}

		if (!is_null($testType)) {
			$html = $testType->getPromptHtml();
		}

		return $html;
	}

	private function getBodyVars(&$qgItem) {
		wfProfileIn(__METHOD__);
		$vars['mqg_article'] = $this->getArticleHtml($qgItem);
		$vars['mqg_prompt_html'] = $this->getPromptHtml($qgItem);
		$vars['mqg_qgItem'] = $this->qgItem;
		wfProfileOut(__METHOD__);
		return $vars;
	}

	private function getData(&$qgItem) {
		wfProfileIn(__METHOD__);
		$data = $this->qgItem;
		wfProfileOut(__METHOD__);
		return $data;
	}

	private function getPicture(&$qgItem) {
		wfProfileIn(__METHOD__);
		$pic = null;
		$r = $this->r;

		if ($r) {
			$intro = Article::getSection($r->getText(), 0);	
			$pic = QCRuleIntroImage::getPicture($intro);
			if ($pic) {
				$pic = $pic->getThumbnail(290, 194);
				$pic->width = floor($pic->getWidth() * .75);
				$pic->height = floor($pic->getHeight() * .75);
			}
		}
		wfProfileOut(__METHOD__);
		return $pic;
	}

	private function getArticleHtml(&$qgItem) {
		wfProfileIn(__METHOD__);
		$t = $this->t;
		$r = $this->r;
		//echo "<a target=_blank href='http://jordan.wikidiy.com/" . $t->getPartialURL() . "?oldid=" . $r->getId() ."'>link</a>";
		$html = '';
		if ($t && $t->exists()) {
			$m = new MobileQGArticleBuilder();
			$html = $m->createByRevision($t, $r);
		}
		wfProfileOut(__METHOD__);
		return $html;
	}

	private function setTemplatePath() {
		EasyTemplate::set_path(dirname(__FILE__).'/');
	}

	private function saveEmail($email) {
		wfProfileIn(__METHOD__);
		$dbw = wfGetDB(DB_MASTER);
		if ($email) {
			$email = $dbw->strencode($email);
			$dbw->insert('mqg_emails', array('mqg_email' => $email, 'mqg_timestamp' => wfTimestamp(TS_MW)), 'MQG::saveEmail', array('IGNORE'));
		}
		wfProfileOut(__METHOD__);
	}
}

abstract class MQGTestType {
	var $qgItem = null;
	var $r = null;

	public function __construct(&$qgItem, &$r) {
		$this->r = $r;
		$this->qgItem = $qgItem;
	}
	
	public abstract function getPromptHtml();

	protected function setTemplatePath() {
		EasyTemplate::set_path(dirname(__FILE__).'/');
	}
}


class MQGPhotoTest extends MQGTestType {
	var $pic = null;

	public function getPromptHtml() {
		$this->setTemplatePath();
		$vars['mqg_pic'] = $this->getPicture();
		$vars['mqg_device'] = MobileWikihow::getDevice();	
		return EasyTemplate::html('mqg_photo_prompt.tmpl.php', $vars);
	}

	public function getPicture() {
		wfProfileIn(__METHOD__);
		$pic = null;
		$r = $this->r;

		if ($r) {
			$intro = Article::getSection($r->getText(), 0);	
			$pic = QCRuleIntroImage::getPicture($intro);
			if ($pic) {
				$pic = $pic->getThumbnail(290, 194);
				$pic->width = floor($pic->getWidth() * .75);
				$pic->height = floor($pic->getHeight() * .75);
			}
		}
		wfProfileOut(__METHOD__);
		return $pic;
	}

}

class MQGRatingTest extends MQGTestType {

	public function getPromptHtml() {
		$this->setTemplatePath();
		$vars['prompt'] = "Rate this article 1 to 5 stars";
		$vars['mqg_prompt_css'] = HtmlSnips::makeUrlTags('css', array('jquery.rating.css'), 'extensions/wikihow/mqg/rating', false);	
		$vars['mqg_prompt_js'] = HtmlSnips::makeUrlTags('js', array('jquery.rating.pack.js', 'jquery.MetaData.js'), 'extensions/wikihow/mqg/rating', false);	
		return EasyTemplate::html('mqg_rating_prompt.tmpl.php', $vars);
	}
}

class MQGRecommendTest extends MQGTestType {

	public function getPromptHtml() {
		$this->setTemplatePath();
		$vars['prompt'] = "Would you recommend this article to a friend?";
		return EasyTemplate::html('mqg_yesno_prompt.tmpl.php', $vars);
	}
}

class MQGYesNoTest extends MQGTestType {

	public function getPromptHtml() {
		$this->setTemplatePath();
		$vars['prompt'] = "Is this article helpful?";
		return EasyTemplate::html('mqg_yesno_prompt.tmpl.php', $vars);
	}
}

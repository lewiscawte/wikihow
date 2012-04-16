<?

class StarterTool extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'StarterTool' );
	}

	/**
	 * Set html template path for StarterTool actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	/**
	 * A Mediawiki callback set in contructor of this class to stop the display
	 * of breadcrumbs at the top of the page.
	 */
	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

	/**
	 * Define a Mediawiki callback to make it so that the body doesn't
	 * get wrapped with <div class="article_inner"></div> ...
	 */
	public static function wrapBodyWithArticleInner() {
		return false;
	}

	public static function getSentence() {
		global $wgStarterPages;

		//get random sentence from our array
		$numb = rand(0,(count($wgStarterPages)-1));
		$the_title = $wgStarterPages[$numb];

		$t = Title::newFromText($the_title);
		$r = Revision::newFromTitle($t);

		$sent = '<span id="starter_sentence">'.$r->getText().'</span>';
		$sent .= '<input type="hidden" value="'.$the_title.'" id="starter_title" />';

		return $sent;
	}

	function getFirstArticleRevision($pageId) {
		$fname = 'StarterTool::getFirstArticleRevision';
		wfProfileIn( $fname );

		$dbr = wfGetDB(DB_SLAVE);
		$minRev = $dbr->selectField('revision', array('min(rev_id)'), array("rev_page" => $pageId), __METHOD__);

		wfProfileOut( $fname );

		return $minRev;
	}

	/**
	 * EXECUTE
	 **/
	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang, $wgParser, $wgHooks, $wgTitle, $wgStarterPages;

		$wgHooks['ShowBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');
		$wgHooks['WrapBodyWithArticleInner'][] = array($this, 'wrapBodyWithArticleInner');

		wfLoadExtensionMessages('StarterTool');

		self::setTemplatePath();

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		//get contents
		if ($wgRequest->getVal('edit')) {
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromText($wgRequest->getVal('starter_title'));
			$a = new Article($t);
			$editor = new EditPage( $a );
			$editor->edit();
			return;
		} elseif ($wgRequest->getVal('getsome')) {
			$wgOut->setArticleBodyOnly(true);
			$wgOut->addHTML(self::getSentence());
			return;

		} elseif ($wgRequest->getVal( 'action' ) == 'submit') {
			$wgOut->setArticleBodyOnly(true);

			$t = Title::newFromText($wgRequest->getVal('starter-title'));
			$a = new Article($t);

			//log it
			$params = array();
			$log = new LogPage( 'Starter_Tool', false ); // false - dont show in recentchanges
			$log->addEntry('', $t, 'Fixed a sentence with the Starter Tool.', $params);

			$text = $wgRequest->getVal('wpTextbox1');
			$sum = $wgRequest->getVal('wpSummary');

			//save the edit
		 	if ($a->doEdit($text,$sum,EDIT_SUPPRESS_RC)) {

				//revert the edit for the next user
				$minRev = self::getFirstArticleRevision($t->getArticleId());

				//don't log rollback for the user
				$oldglobal = $wgUser;
				$wgUser = User::newFromName("MasterSockPuppet421");

				$dbr = wfGetDB(DB_SLAVE);
				$r = Revision::loadFromId($dbr,$minRev);
				$a->doEdit($r->getText(),'Auto-rollback from Starter Tool.',EDIT_SUPPRESS_RC);

				// reset the wguser var
				$wgUser = $oldglobal;

				wfRunHooks("StarterToolSaveComplete", array($a, $text, $sum, $wgUser, $efType));
			}

 			return;
		} else {
			//default; get a sentence

			$wgOut->addScript("<script type='text/javascript' src='". wfGetPad('/extensions/min/f/extensions/wikihow/starter/starter.js?rev=') . WH_SITEREV . "'></script>");
			$wgOut->addScript("<script type='text/javascript' src='". wfGetPad('/extensions/min/f/skins/common/clientscript.js?rev=') . WH_SITEREV . "'></script>");

			$wgOut->addStyle('../extensions/wikihow/starter/starter.css');

			$sk = $wgUser->getSkin();
			$wgOut->setArticleBodyOnly(false);

			$vars = array('pagetitle' => wfMsg('app-name'),'question' => wfMsg('fix-this'),'yep' => wfMsg('yep'), 'nope' => wfMsg('nope'));
			$html = EasyTemplate::html('starter',$vars);
			$wgOut->addHTML($html);
		}

		$wgOut->setHTMLTitle( wfMsg('pagetitle', wfMsg('app-name')) );

	}
}
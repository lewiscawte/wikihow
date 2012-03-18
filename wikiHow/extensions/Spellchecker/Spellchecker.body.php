<?
/*
* 
*/
global $IP;
require_once("$IP/extensions/wikihow/EditPageWrapper.php");

class Spellchecker extends UnlistedSpecialPage {
	
	var $skipTool;
	
	const SPELLCHECKER_EXPIRED = 3600; //60*60 = 1 hour

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Spellchecker');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;
		
		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}
		
		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		
		//start temp code for taking down tool
		/*wfLoadExtensionMessages("Spellchecker");
		
		$wgOut->setHTMLTitle(wfMsg('spellchecker'));
		$wgOut->setPageTitle(wfMsg('spellchecker'));
		
		$wgOut->addWikiText("This tool is temporarily down for maintenance. Please check out the [[Special:CommunityDashboard|Community Dashboard]] for other ways to contribute while we iron out a few issues with this tool. Happy editing!");
		return;*/
		//end temp code
		
		/*if ( !($wgUser->isSysop() || in_array( 'newarticlepatrol', $wgUser->getRights()) ) ) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}*/
		
		wfLoadExtensionMessages("Spellchecker");
		
		$this->skipTool = new ToolSkip("spellchecker", "spellchecker", "sc_checkout", "sc_checkout_user", "sc_page");

		if ( $wgRequest->getVal('getNext') ) {
			$wgOut->disable();
			if( $wgRequest->getVal('articleName') )
				$articleName = $wgRequest->getVal('articleName');
			else
				$articleName = "";
			
			$result = self::getNextArticle($articleName);
			print_r(json_encode($result));
			return;
		}
		else if ($wgRequest->getVal('edit')) {
			$wgOut->disable();
			
			$id = $wgRequest->getVal('id');
			$result = $this->getArticleEdit($id);
			
			print_r(json_encode($result));
			return;
		}
		else if ( $wgRequest->getVal('skip') ) {
			$wgOut->disable();
			$id = $wgRequest->getVal('id');
			$this->skipTool->skipItem($id);
			$this->skipTool->unUseItem($id);
			$result = self::getNextArticle();
			print_r(json_encode($result));
			return;
		}
		else if ( $wgRequest->getVal('cache') ) {
			$this->skipTool->clearSkipCache();
		}
		else if ( $wgRequest->getVal('addWord') ) {
			$wgOut->setArticleBodyOnly(true);
			$result->success = wikiHowDictionary::addWordToDictionary($wgRequest->getVal('word'));
			print_r(json_encode($result));
			return;
		}
		else if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			if ( $wgRequest->getVal('submitEditForm')) {
				//user has edited the article from within the Spellchecker tool
				$wgOut->disable();
				$this->submitEdit();
				$result = self::getNextArticle();
				print_r(json_encode($result));
				return;
			}
		}

		$wgOut->setHTMLTitle(wfMsg('spellchecker'));
		$wgOut->setPageTitle(wfMsg('spellchecker'));

		$wgOut->addScript(HtmlSnips::makeUrlTags('css', array('spellchecker.css'), 'extensions/wikihow/spellchecker', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('spellchecker.js'), 'extensions/wikihow/spellchecker', false));

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		
		$setVars = $wgUser->isSysop() || in_array( 'newarticlepatrol', $wgUser->getRights() );
		$tmpl->set_vars(array('addWords' => $setVars));

		$wgOut->addHTML($tmpl->execute('Spellchecker.tmpl.php'));
		
		// add standings widget
		$group= new SpellcheckerStandingsGroup();
		$indi = new SpellcheckerStandingsIndividual();
		
		$indi->addStatsWidget(); 
		$group->addStandingsWidget();

	}
	
	/**
	 *
	 * Gets the html for editing an article
	 * 
	 */
	function getArticleEdit($articleId) {
		$title = Title::newFromID($articleId);

		if ($title) {
			$revision = Revision::newFromTitle($title);
			$article = new Article($title);
			if ($revision) {

				$text = $revision->getRawText();

				$text = self::markBreaks($text);
				$text = self::replaceNewlines($text);
				
				$content['html'] = "<p>{$text}</p>";
				$content['title'] = "Article: <a href='{$title->getFullURL()}' target='new'>" . wfMsg('howto', $title->getText()) . "</a>";
				//$content['title'] = $title->getText();

				$ep = new EditPageWrapper($article);
				$content['summary'] = "<span id='wpSummaryLabel'><label for='wpSummary'>Summary:</label></span><br /><input tabindex='10' type='text' value='" . wfMsg('spch-summary') . "' name='wpSummary' id='wpSummary' maxlength='200' size='60' /><br />";
				$content['buttons'] = $ep->getEditButtons(0);
				$content['buttons']['cancel'] = "<a href='#' id='spch-cancel'>Done</a>";
				$content['articleId'] = $title->getArticleID();

				return $content;
			}
		}
		
		//return an error message
	}

	
	function getNextArticle($articleName = '') {
		global $wgOut;
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$skippedSql = "";
		$skippedIds = $this->skipTool->getSkipped();
		$expired = wfTimestamp(TS_MW, time() - Spellchecker::SPELLCHECKER_EXPIRED);
		
		$title = Title::newFromText($articleName);
		if($title && $title->getArticleID() > 0) {
			$articleId = $title->getArticleID();
		}
		else if ($skippedIds) {
			$articleId = $dbr->selectField('spellchecker', 'sc_page', array('sc_errors' => 1, 'sc_dirty' => 0, "sc_checkout < '{$expired}'", "sc_page NOT IN ('" . implode("','", $skippedIds) . "')"), __FUNCTION__, array("limit" => 1, "ORDER BY" => "RAND()"));
		}
		else
			$articleId = $dbr->selectField('spellchecker', 'sc_page', array('sc_errors' => 1, 'sc_dirty' => 0, "sc_checkout < '{$expired}'"), __FUNCTION__, array("limit" => 1, "ORDER BY" => "RAND()"));

		if ($articleId) {
			$sql = "SELECT * from `spellchecker_page` JOIN `spellchecker_word` ON sp_word = sw_id WHERE sp_page = {$articleId}"; 
			$res =  $dbr->query($sql);

			$words = array();
			$corrections = array();
			while ($row = $dbr->fetchObject($res)) {
				$words[] = $row->sw_word;
				$corrections[] = $row->sw_corrections;
			}

			$caps = wikiHowDictionary::getCaps();
			$exclusions = array();
			foreach($words as $word) {
				if (preg_match('@\s' . $word . '\s@', $caps)) {
					$exclusions[] = strtoupper($word);
				}
			}

			$title = Title::newFromID($articleId);

			if ($title) {
				$revision = Revision::newFromTitle($title);
				$article = new Article($title);
				if ($revision) {

					$text = $revision->getRawText();

					$text = self::markBreaks($text);
					$text = self::replaceNewlines($text);

					$content['html'] = "<p>{$text}</p>";
					$content['title'] = "Article: <a href='{$title->getFullURL()}' target='new'>" . wfMsg('howto', $title->getText()) . "</a>";

					$content['articleId'] = $title->getArticleID();
					$content['words'] = $words;
					$content['exclusions'] = $exclusions;

					$parserOutput = $wgOut->parse($revision->getText());
					$html = WikiHowTemplate::mungeSteps($parserOutput, array('no-ads'));

					$content['html'] = $html;
					
					$this->skipTool->useItem($articleId);

					return $content;
				}
			}
		}
		
		//return error message
		$content['error'] = true;
		return $content;
	}

	/**
	 *
	 * Marks the BR tags that currently exist in the text so we'll
	 * know to not to remove them later
	 * 
	 */
	function markBreaks($text) {
		$articleText = preg_replace("@<br>@", "<br class='exists'>", $text);

		return $articleText;
	}

	/**
	 *
	 * Removes the class on the BR tag that marks them as having existed
	 * before the edit
	 *
	 */
	function unmarkBreaks($text) {
		$articleText = preg_replace('@<br class="exists">@i', "<br>", $text);
		$articleText = preg_replace('@<br class=exists>@i', "<br>", $articleText); //IE

		return $articleText;
	}

	/**
	 *
	 * Replaces new lines (\n) with BR tags so the format correctly in
	 * an HTML5 editable field
	 * 
	 */
	function replaceNewlines($text) {
		$articleText = preg_replace("@\\n@", "<br />", $text);

		return $articleText;
	}

	/**
	 *
	 * Replaces BR tags in the text with newline characters (\n)
	 * 
	 */
	function insertNewlines($text) {
		$articleText = preg_replace("@<br>@", "\n", $text);
		$articleText = preg_replace("@<BR>@", "\n", $articleText); //IE

		return $articleText;
	}

	/*
	 *
	 * Removes wrapping spans and paragraph tags which are not included
	 * in the raw wikitext.
	 *
	 */
	function removeWordWraps($text) {
		$articleText = preg_replace('@<[/]?(span|p|[ovwxp]:\w+)[^>]*?>@', '', $text);
		$articleText = preg_replace('@<[/]?(SPAN|P|[ovwxp]:\w+)[^>]*?>@', '', $articleText); //IE

		return $articleText;
	}
	
	function removeSpaces($text) {
		$articleText = str_replace("&nbsp;", " ", $text);
		
		return $articleText;
	}
	
	function removeHTMLEntities($text) {
		//convert > symbols
		$articleText = str_replace("&gt;", ">", $text);
		
		//convert < symbols
		$articleText = str_replace("&lt;", "<", $articleText);
		
		//convert & symbols
		$articleText = str_replace("&amp;", "&", $articleText);
		
		return $articleText;
	}

	/*
	 *
	 * Processes an article submit
	 *
	 */
	function submitEdit() {
		global $wgRequest, $wgUser;

		$t = Title::newFromID($wgRequest->getVal('articleId'));
		if ($t) {
			$a = new Article($t);

			$text = $wgRequest->getVal('wpTextbox1');
			$text = self::removeWordWraps($text);
			$text = self::insertNewlines($text);
			$text = self::unmarkBreaks($text);
			$text = self::removeSpaces($text);
			$text = self::removeHTMLentities($text);
			$summary = $wgRequest->getVal('wpSummary');
			
			if ($a) {
				//save the edit
				$a->doEdit($text, $summary);
				
				$params = array();
				if($wgRequest->getVal('isIE') == "true")
					$IE = ", IE";
				else
					$IE = "";
				$log = new LogPage( 'spellcheck', true ); // false - dont show in recentchanges
				$msg = wfMsgHtml('spch-edit-message', "[[{$t->getText()}]]", $IE);
				$log->addEntry('edit', $t, $msg, $params);
				wfRunHooks("Spellchecked", array($wgUser, $t, '0'));
				
				$this->skipTool->unUseItem($a->getID());
			}

		}
	}
	
	static function markAsDirty($id) {
		$dbw = wfGetDB(DB_MASTER);
		
		$sql = "INSERT INTO spellchecker (sc_page, sc_timestamp, sc_dirty, sc_errors) VALUES (" . 
					$id . ", " . wfTimestampNow() . ", 1, 0) ON DUPLICATE KEY UPDATE sc_dirty = '1', sc_timestamp = " . wfTimestampNow();
		$dbw->query($sql);
	}
	
	static function markAsIneligible($id) {
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->update('spellchecker', array('sc_errors' => 0, 'sc_dirty' => 0), array('sc_page' => $id));
	}
	
}

class Spellcheckerwhitelist extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Spellcheckerwhitelist');
	}

	function execute($par) {
		global $IP, $wgOut, $wgUser, $wgMemc;
		
		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}
		
		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		
		wfLoadExtensionMessages("Spellchecker");
		
		$filecontents = file_get_contents($IP . wikiHowDictionary::DICTIONARY_LOC);
		$words = explode("\n", $filecontents);
		asort($words);

		
		$dbr = wfGetDB(DB_SLAVE);
		
		$res = $dbr->select(wikiHowDictionary::CAPS_TABLE, "*");

		$caps = array();
		while($row = $dbr->fetchObject($res)) {
			$caps[] = $row->sc_word;
		}
		asort($caps);
		
		$wgOut->addHTML("<ul>");
		foreach($words as $word) {
			if($word != "" && stripos($word, "personal_ws-1.1") === false)
				$wgOut->addHTML("<li>" . $word . "</li>");
		}
		
		foreach($caps as $word) {
			if($word != "")
				$wgOut->addHTML("<li>" . $word . "</li>");
		}
		
		$wgOut->addHTML("</ul>");
		
		$wgOut->setHTMLTitle(wfMsg('spch-whitelist'));
		$wgOut->setPageTitle(wfMsg('spch-whitelist'));
	}
}

class wikiHowDictionary{
	const DICTIONARY_LOC = "/images/spellcheck/custom.pws";
	const TEMP_TABLE = "spellchecker_temp";
	const CAPS_TABLE = "spellchecker_caps";
	const WORD_TABLE = "spellchecker_word";
	const WORD_FIELD = "st_word";
	
	/***
	 * 
	 * Takes the given word and, if allowed, adds it
	 * to the temp table in the db to be added
	 * to the dictionary at a later time
	 * (added via cron on the hour)
	 * 
	 */
	static function addWordToDictionary($word) {
		$word = trim($word);
		
		//now check to see if the word can be added to the library
		//only allow a-z and apostraphe
		//check for numbers
		if ( preg_match('@[^a-z|\']@i', $word) )
			return false;
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(self::TEMP_TABLE, array(self::WORD_FIELD => $word));
		
		return true;
	}
	
	/***
	 * 
	 * Called via the cron. Adds all the words in the 
	 * temp table to the dictionary
	 * 
	 */
	static function batchAddWordsToDictionary() {
		$dbr = wfGetDB(DB_SLAVE);
		
		$res = $dbr->select(self::TEMP_TABLE, '*');
		$words = array();
		while($row = $dbr->fetchObject($res)) {
			$words[] = $row->st_word;
		}
		
		if (count($words) <= 0)
			return;
		
		$dbw = wfGetDB(DB_MASTER);
		
		$pspell = self::getLibrary();
		
		foreach($words as $word) {
			
			//check to see if its an ALL CAPS word
			if ( !preg_match('@[^A-Z]@', $word) ) {
				$sql = "INSERT IGNORE INTO " . self::CAPS_TABLE . " value ('" . $word . "')";
				$dbw->query($sql);
			}
			else
				pspell_add_to_personal($pspell, $word);
			
			//now go through and check articles that contain that word.
			$sql = "SELECT * FROM `" . self::WORD_TABLE . "` JOIN `spellchecker_page` ON `sp_word` = `sw_id` WHERE sw_word = " . $dbr->addQuotes($word);
			$res = $dbr->query($sql);
			
			while($row = $dbr->fetchObject($res)) {
				$page_id = $row->sp_page;
				$dbw->update('spellchecker', array('sc_dirty' => "1"), array('sc_page' => $page_id));
			}
		}
		
		pspell_save_wordlist($pspell);
		
		$dbw->query("TRUNCATE " . self::TEMP_TABLE);
	}
	
	/***
	 * 
	 * Gets a link to the pspell library
	 * 
	 */
	static function getLibrary() {
		global $IP;
		
		$pspell_config = pspell_config_create("en", 'american');
		pspell_config_mode($pspell_config, PSPELL_FAST);
		pspell_config_personal($pspell_config, $IP . wikiHowDictionary::DICTIONARY_LOC);
		$pspell_link = pspell_new_config($pspell_config);

		return $pspell_link;
	}
	
	/***
	 * 
	 * Checks the given word using the pspell library
	 * and our separate caps whitelist
	 * 
	 * Returns: -1 if the word is ok
	 *			id of the word in the spellchecker_word table
	 * 
	 */
	function spellCheckWord(&$dbw, $word, &$pspell, &$caps) {
		
		// Ignore numbers
		//if (preg_match('/^[A-Z]*$/',$word)) return;
		if (preg_match('/[0-9]/',$word)) return;

		// Return dictionary words
		if (pspell_check($pspell,$word)) {
			// this word is OK
			return -1;
		}
		
		if (preg_match('/^[A-Z]*$/',$word)) {
			//its ALL CAPS so check to see if its in our special
			//ALL CAPS list
			if (preg_match('@\s' . $word . '\s@', $caps)) {
				return -1;
			}
		}

		$suggestions = pspell_suggest($pspell,$word);
		$corrections = "";
		if (sizeof($suggestions) > 0) {
			if (sizeof($suggestions) > 5) {
				$corrections = implode(",", array_splice($suggestions, 0, 5));
			} else {
				$corrections = implode(",", $suggestions);
			}
		} 
		
		//first check to see if it already exists
		$id = $dbw->selectField(self::WORD_TABLE, 'sw_id', array('sw_word' => $word));
		if ($id === false) {
			$dbw->insert(self::WORD_TABLE, array('sw_word' => $word, 'sw_corrections' => $corrections));
			$id = $dbw->insertId();
		}
		
		return $id;

	}
	
	/***
	 * 
	 * Returns a string with all the CAPS words in them
	 * to compare against words that are in articles
	 * 
	 */
	static function getCaps() {
		$dbr = wfGetDB(DB_SLAVE);
		
		$res = $dbr->select(self::CAPS_TABLE, "*");
		
		$capsString = "";
		while($row = $dbr->fetchObject($res)) {
			$capsString .= " " . $row->sc_word . " ";
		}
		
		return $capsString;
	}
}

<?php

require_once('EditPageWrapper.php');
require_once('SpecialLSearch.body.php');

/**#@+
 * A simple extension that allows users to enter a title before creating a page.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class CreatePage extends SpecialPage {
	function __construct() {
		SpecialPage::SpecialPage('CreatePage');
	}

	function cleanupProposedRedirects(&$text) {
		$lines = split("\n", $text);
		$uniques = array();
		foreach ($lines as $line) {
			$params = split("\t", $line);
			if (sizeof($params) != 3) continue;
			$uniques[$line] = $line;
		}
		$text = trim(implode("\n", $uniques));
	}

	function addProposedRedirect($from, $to) {
		global $wgUser, $wgOut;
		if ($wgUser->getID() > 0)
			ProposedRedirects::createProposedRedirect($from->getDBKey(), $to->getDBKey());
	}

	function getRelatedTopicsText($target) {
		global $wgOut, $wgUser;
		$hits = array();
		$t = Title::newFromText(EditPageWrapper::formatTitle($target));
		$l = new LSearch();
		$hits  = $l->googleSearchResultTitles($target, 0,10);
		$count = 0;
		if ($t->getArticleID() > 0) {
			return $wgOut->parse(wfMsg('createpage-title-exists', $t->getFullText()) . "<br/><br/>")
				   .   "<a href='" . $t->getEditURL() . "'>" . wfMsg('createpage-edit-existing') . "</a><br/>";
		}
		if (sizeof($hits) > 0) {
			foreach  ($hits as $hit) {
				$t1 = $hit;
				if ($count == 5) break;
				if ($t1 == null) continue;
				if ($t1->getNamespace() != NS_MAIN) continue;

				// check if the result is a redirect
				$a = new Article($t1);
				if ($a && $a->isRedirect()) continue;

				if ($wgUser->getID() > 0) {
					$gatuser = 'Registered_Editing';
				} else {
					$gatuser = 'Anon_Editing';
				}

				// check if the article exists
				if (strtolower($t1->getText()) == strtolower($target->getText())) {
					return $wgOut->parse(wfMsg('createpage-title-exists', $t1->getFullText()) . "<br/><br/>")
						.   "<a href='" . $t->getEditURL() . "'>" . wfMsg('createpage-edit-existing') . "</a><br/>";
				}

				$s .= "<input type='radio' name='{$target->getDBKey()}' value='{$t1->getDBKey()}' onchange='document.getElementById(\"cp_next\").disabled = false; gatTrack(\"$gatuser\",\"Create_redirect\");'>
						<a href='{$t1->getFullURL()}' target='new'>". wfMsg('howto', $t1->getText() ) . "</a><br/><br/>";
				$count++;
			}
			if ($count == 0) {
				return wfMsg('createpage_step1box_noresults', $s);
			}
			return wfMsg('createpage_step1box_inner', $target->getText(), $target->getDBKey(), $s);
		}
		return wfMsg('createpage_step1box_noresults', $s);
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgLanguageCode, $wgScriptPath;
		$fname = "wfCreatePage";
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$me = Title::newFromText("CreatePage", NS_SPECIAL);
		$sk = $wgUser->getSkin();
		$this->setHeaders();
		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/createpage.css'; /*]]>*/</style> ");
		$wgOut->addHTML("<script type='text/javascript' src='/extensions/wikihow/createpage.js'></script>");
		wfLoadExtensionMessages('CreatePage');

		if ($wgRequest->wasPosted() && $wgRequest->getVal('create_redirects') != null) {
			// has the user submitted a redirect?
			$source = Title::newFromText(EditPageWrapper::formatTitle($wgRequest->getVal('createpage_title')));
			$p1 = Title::newFromText($wgRequest->getVal('createpage_title'));
			if ($wgRequest->getVal($p1->getDBKey()) == 'none' || $wgRequest->getVal($p1->getDBKey()) == null) {
				if (class_exists('Html5editor') && hasHtml5Browser()) {
					$wgOut->redirect($source->getFullURL() . "?create-new-article=true");
				} else {
					$wgOut->redirect($source->getEditURL() . "&review=1");
				}
				return;
			} else {
				$target = Title::newFromText($wgRequest->getVal($p1->getDBKey()));
				if (!$target && $source->getArticleID() > 0){
					$wgOut->redirect($source->getEditURL());
					return;
				}

				// add redirect to list of proposed redirects
				CreatePage::addProposedRedirect($source, $target);
				$wgOut->addWikiText(wfMsg('createpage_redirect_confirmation', $source->getText(), $target->getText(), $target->getEditURL()));
				$wgOut->addHTML(wfMsg('createpage_redirect_confirmation_bottom', $source->getText(), $target->getText(), $target->getEditURL()));
				return;
			}
		}

		if ($par != "" && false) {
			$title = Title::newFromText($par);
			$wgOut->addHTML(wfMsg('createpage_fromsuggestions', htmlspecialchars($title->getText()), $title->getFullURL()));
			return;
		}
		if ($wgRequest->wasPosted() && $wgRequest->getVal('q') != null) {
			$matches = SuggestionSearch::matchKeyTitles($wgRequest->getVal('q'), 30);
			if (sizeof($matches) == 0) {
				$wgOut->addHTML(wfMsg('createpage_nomatches'));
				$this->outputCreatePageForm();
				return;
			}
			$wgOut->addHTML(wfMsg('createpage_matches'));
			$wgOut->addHTML("<table class='cpresults'><tr>");
			for ($i = 0; $i < sizeof($matches); $i++) {
				$t = $matches[$i][0];
				if ($t) {
					$ep = SpecialPage::getTitleFor("CreatePage", $t->getText());
					$wgOut->addHTML("<td><a href='{$ep->getFullURL()}' class='new'>{$t->getFullText()}</a></td>");
				}
				if ($i % 3 == 2) $wgOut->addHTML("</tr><tr>");
			}

			$wgOut->addHTML("</tr></table>");
			$wgOut->addHTML(wfMsg('createpage_tryagain'));
			$this->outputCreatePageForm();
			return;
		}
		if ($target != null) {
			$t = Title::newFromText($target);
			// handle formatting
			$t2 = null;
			if ($wgLanguageCode == 'en') {
				require_once('EditPageWrapper.php');
				$t2 = Title::newFromText(EditPageWrapper::formatTitle($target));
			}

			if ($t2 && $t2->getArticleID() > 0)
				$t = $t2;

			if ($t->getArticleID() > 0) {
				$r = Revision::newFromTitle($t);
				$text =  $r->getText();
				$redirect = Title::newFromRedirect( $text );
				if ($redirect != null)
					$t = $redirect;
				$wgOut->addWikiText(wfMsg('createpage-title-exists', $t->getFullText()) . "<br/><br/>");
				$wgOut->addHTML("<a href='" . $t->getEditURL() . "'>" . wfMsg('createpage-edit-existing') . "</a><br/>"
						. $sk->makeLinkObj($me, wfMsg('createpage-try-again'))
					);
			} else {
				$wgOut->addWikiText(wfMsg('createpage_notefollowing'));
				$wgOut->addHTML(wfMsg('createpage_step1box', htmlspecialchars($t->getFullText()), $this->getRelatedTopicsText($t)));
				//$wgOut->redirect($t->getEditURL());
			}
			return;
		}

		$this->outputCreatePageForm();
	}

	function outputCreatePageForm() {
		global $wgOut, $wgScriptPath;
		wfLoadExtensionMessages('CreatePage');
		$wgOut->addHTML("
		<script type='text/javascript'>
			function checkform() {
				if (document.createform.target.value.indexOf('?') > 0 ) {
					alert('The character ? is not allowed in the title of an article.');
					return false;
				}
				return true;
			}
		</script>
		<script type=\"text/javascript\" src=\"{$wgScriptPath}/skins/common/clientscript.js\"></script>
		<script type=\"text/javascript\" src=\"{$wgScriptPath}/skins/common/ac.js\"></script>
		"
		. wfMsg('createpage_boxes')
		);
		return;
	}
}

class SuggestionSearch extends UnlistedSpecialPage {

	function __construct() {
	   UnlistedSpecialPage::UnlistedSpecialPage( 'SuggestionSearch' );
	}

	function matchKeyTitles ($text, $limit = 10, $namspace=0) {
		$gotit = array();
		$text = trim($text);
		$key = generateSearchKey($text);

		$db =& wfGetDB( DB_MASTER );

		$base = "SELECT suggested_titles.* FROM suggested_titles WHERE ";
		$sql = $base . " convert(st_title using latin1) like " . $db->addQuotes($text . "%"). " and st_used = 0 ";
		$sql .= " LIMIT $limit;";
		$result = array();
		$res = $db->query( $sql, 'Title::matchKeyTitles' );
		while ( $row = $db->fetchObject( $res ) ) {
			$con = array();
			$con[0] = Title::newFromDBkey($row->st_title);
			$result[] = $con;
			$gotit[$row->st_title] = 1;
		}
		if (sizeof($result) == $limit) return $result;

		$base = "SELECT suggested_titles.* FROM suggested_titles WHERE ";
		$sql = $base . " st_key LIKE '%" . str_replace(" ", "%", $key) . "%' and st_used = 0 ";
		$sql .= " LIMIT $limit;";
		$res = $db->query( $sql, 'Title::matchKeyTitles' );
		while ( $row = $db->fetchObject( $res ) ) {
			$con = array();
			$con[0] = Title::newFromDBkey($row->st_title);
			$result[] = $con;
			$gotit[$row->st_title] = 1;
		}

		$sql = $base . " ( st_key like '%" . str_replace(" ", "%", $key) . "%' ";
		$ksplit = split(" ", $key);
		foreach ($ksplit as $k) {
			$sql .= " OR st_key LIKE '%$k%'"  ; // str_replace(" ", "%", $key) . "%' LIMIT $limit;";
		}
		$sql .= " ) ";
		$sql .= " and st_used = 0  ";
		//$sql .= " GROUP BY p1.page_id LIMIT $limit;";
		$sql .= " LIMIT $limit;";
		$res = $db->query( $sql, 'Title::matchKeyTitles' );
		while ( $row = $db->fetchObject( $res ) ) {
			if (!isset($gotit[$row->st_title]))  {
				$con = array();
				$con[0] = Title::newFromDBkey($row->st_title);
				$result[] = $con;
			}
		}
	    return $result;
	}

	function execute () {
		global $wgRequest, $wgOut;

		$t1 = time();
		$search = $wgRequest->getVal("qu");

		if ($search == "") exit;

		$search = strtolower($search);
		if (strpos($search, "how to") === 0) {
			$search = substr($search, 6);
			$search = trim($search);
		}
		$t = Title::newFromText($search, 0);
		$dbkey = $t->getDBKey();

		echo 'sendRPCDone(frameElement, "' . $search . '", new Array(';

		$array = "";
		$titles = $this->matchKeyTitles($search);
		foreach ($titles as $con) {
			$t = $con[0];
			$title = $t->getFullText();
			$array .= '"' . str_replace("\"", "\\\"", $title) . '", ' ;
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;

		echo '), new Array(';

		$array = "";
		foreach ($titles as $con) {
			$array .=  "\" \", ";
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		echo $array;
		echo '), new Array(""));';
		$wgOut->disable();
	}

}

class ManageSuggestions extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ManageSuggestions');
	}
	function execute($par ) {
		global $wgOut, $wgRequest, $wgUser, $wgLanguageCode, $wgScriptPath;
		$fname = "wfManageSuggestions";
		wfLoadExtensionMessages('CreatePage');

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->setHeaders();
		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/createpage.css'; /*]]>*/</style> ");
		if ($wgRequest->wasPosted() && $wgRequest->getVal('q') != null) {
			$matches = SuggestionSearch::matchKeyTitles($wgRequest->getVal('q'), 30);
			if (sizeof($matches) == 0) {
				$wgOut->addHTML(wfMsg('createpage_nomatches'));
				$this->outputCreatePageForm();
				return;
			}
			$wgOut->addHTML(wfMsg('createpage_matches'));
			$wgOut->addHTML("<div class='cpbox'><form method='POST'><table class='cpresults'><tr>");
			for ($i = 0; $i < sizeof($matches); $i++) {
				$t = $matches[$i][0];
				if (!$t) continue;
				if ($t)
					$name = htmlspecialchars($t->getDBKey());
					$wgOut->addHTML("<td><input type='checkbox' name=\"{$name}\"/>&nbsp;<a href='{$t->getEditURL()}' class='new'>{$t->getFullText()}</a></td>");
				if ($i % 3 == 2) $wgOut->addHTML("</tr><tr>");
			}
			$wgOut->addHTML("</tr></table><br/>To delete any of these, select the checkbox and hit the delete button.<br/>
			<input type='hidden' name='delete' value='1'/>
			<input type='submit' value='Delete'/></form></div>
			");
			return;
		} else if ($wgRequest->wasPosted() && $wgRequest->getVal('delete') != null) {
			$dbw = wfGetDB(DB_MASTER);
			$log = new LogPage( 'suggestion', true );
			foreach($wgRequest->getValues() as $key => $value) {
				if ($value != 'on') continue;
				$t = Title::newFromURL($key);
				if ($t && $dbw->delete('suggested_titles', array('st_title' => $t->getDBKey()))) {
					$wgOut->addHTML("The suggestion \"{$t->getText()}\" has been removed.<br/>");
					$msg= wfMsg('managesuggestions_log_remove', $wgUser->getName(), $t->getText());
					$log->addEntry( 'removed', $t, $msg);
				} else {
					$wgOut->addHTML("Could not remove \"{$key}\", report this to Travis.<br/>");
				}
			}
			$wgOut->addHTML("<br/><br/>");
		} else if ($wgRequest->wasPosted() && $wgRequest->getVal('new_suggestions') != null) {
			$dbw = wfGetDB(DB_MASTER);
			$sugg = $wgRequest->getVal('new_suggestions');
			$format = $wgRequest->getVal('formatted') != 'on';
			$lines = split("\n", $sugg);
			require_once("EditPageWrapper.php");
			$log = new LogPage( 'suggestion', true );
			foreach ($lines as $line) {
				$title = trim($line);
				if ($format)
					$title = EditPageWrapper::formatTitle($title);
				$key = generateSearchKey($title);

				$count = $dbw->selectField('suggested_titles', array('count(*)'), array('st_key' => $key));
				if ($count > 0) {
					$wgOut->addHTML("Suggestion \"{$title}\" <b>not</b> added - duplicate suggestion.<br/>");
					continue;
				}

				$t = Title::newFromText($title);
				if ($t->getArticleID() > 0) {
					$wgOut->addHTML("Suggestion \"{$title}\" <b>not</b> added - article exists. <br/>");
					continue;
				}

				$count = $dbw->selectField('skey', array('count(*)'), array('skey_key' => $key));
				if ($count > 0) {
					$wgOut->addHTML("Suggestion \"{$title}\" <b>not</b> added - duplicate article key.<br/>");
					continue;
				}

				$dbw->insert('suggested_titles', array('st_title' => $title, 'st_key' => $key));
				$msg= wfMsg('managesuggestions_log_add', $wgUser->getName(), $title);
				$log->addEntry( 'added', $t, $msg);
				$wgOut->addHTML("Suggestion \"{$title}\" added (key $key) <br/>");
			}
			$wgOut->addHTML("<br/><br/>");
		}

		$wgOut->addHTML(wfMsg('managesuggestions_boxes'));
	}
}

class LogHeight extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'LogHeight');
	}
    function execute($par ) {
		global $wgRequest;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('pageheight',
			array(
				'ph_id'			=> $wgRequest->getVal('page'),
				'ph_height'		=> $wgRequest->getVal('height'),
				'ph_timestamp' 	=>  wfTimestampNow(TS_MW),
				'ph_client'		=> $wgRequest->getVal('client'),
			)
		);
	}
}

class CreatepageWarn extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('CreatepageWarn');
	}

	function execute($par) {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		$warn = $wgRequest->getVal('warn');
		switch ($warn) {
			case 'caps':
				$wgOut->addHTML(wfMsg('createpage_uppercase', $wgRequest->getVal('ratio')));
				break;
			case 'sentences':
				$wgOut->addHTML(wfMsg('createpage_sentences', $wgRequest->getVal('sen')));
				break;
			case 'words':
			default:
				$wgOut->addHTML(wfMsg('createpage_tooshort', $wgRequest->getVal('words')));
		}
		$wgOut->addHTML(wfMsg('createpage_bottomwarn'));
		return;
	}
}

class CreatePageTitleResults  extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('CreatePageTitleResults');
	}

	function execute($par) {
		global $wgRequest, $wgOut;
		$t = Title::newFromText($wgRequest->getVal('target'));
		$s = CreatePage::getRelatedTopicsText($t);
		$wgOut->disable(true);
		echo $s;
		return;
	}
}

class CreatepageReview extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('CreatepageReview');
	}

	function execute($par) {
		global $wgOut, $wgRequest;

		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML("
			<div id='review_intro'>" . wfMsg('createpage_reviewintro') . "</div>
			<div id='article'>
			<div id='preview_landing' style='height: 280px; padding:5px 5px 5px 15px; overflow:auto; border: 1px solid #ccc;'>
				<div style='text-align: center; margin-top: 350px;'>Loading...<br/><img src='/extensions/wikihow/rotate.gif'></div>
			</div>
			</div>
			" . wfMsg('createpage_reviewoptions')
		);
	}
}

class CreatepageFinished extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('CreatepageFinished');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser;
		$wgOut->setArticleBodyOnly(true);

		if ($wgUser->getID() > 0) {
			if ($wgUser->getEmail() == '') {
				$authoremail = "<input type='text' maxlength='240' size='30' id='email_me' value='' style='width:248px;' onkeydown=\"document.getElementById('email_notification').checked = true;\" />
					<input type='hidden' id='email_address_flag' value='0'>";
			} else {
				$authoremail = "<input type='text' readonly='true' maxlength='240' size='30' id='email_me' value='".$wgUser->getEmail()."' style='width:248px;' />
					<input type='hidden' id='email_address_flag' value='1'>";
			}
			if ($wgUser->isFacebookUser()) {
				$wgOut->addHTML(wfMsg('createpage_finished_facebook', $authoremail ));
			} else {
				$wgOut->addHTML(wfMsg('createpage_finished', $authoremail ));
			}
		} else {
			$wgOut->addHTML(wfMsg('createpage_finished_anon'));
		}
	}
}

class CreatepageEmailFriend extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('CreatepageEmailFriend');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser;
		if (!$wgRequest->wasPosted()) return;
		$wgOut->disable();
		$friends = split(",", $wgRequest->getVal('friends'));
		$target = Title::newFromURL($wgRequest->getVal('target'));
		if (!$target) return;
		$rev = Revision::newFromTitle($target);
		if (!$rev) return;
		$summary = Article::getSection($rev->getText(), 0);
		$summary = ereg_replace("<.*>", "", $summary);
		$summary = ereg_replace("\[\[.*\]\]", "", $summary);
		$summary = ereg_replace("\{\{.*\}\}", "", $summary);
		$body = wfMsg('createpage_email_body', $target->getFullText(), $summary, $target->getFullURL());
		$subject = wfMsg('createpage_email_subject', $target->getFullText());
		$count = 0;
		$from = $wgUser->getID() == 0 || $wgUser->getEmail() == '' ? "wiki@wikihow.com" : $wgUser->getEmail();
		$from = new MailAddress($from);
		foreach ($friends as $f) {
			$to = new MailAddress($f);
			UserMailer::send($to, $from, $subject, $body);
			$count++;
			if ($count == 3) break;
		}
	}
}

class ProposedRedirects extends SpecialPage {
	function __construct() {
		SpecialPage::SpecialPage('ProposedRedirects');
	}

	function deleteProposedRedirect($from, $to) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete('proposedredirects', array('pr_from' => $from, 'pr_to'=> $to));
	}

	function createProposedRedirect($from, $to) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('proposedredirects',
			array('pr_from' => $from, 'pr_to'=> $to, 'pr_user' => $wgUser->getID(), 'pr_user_text' => $wgUser->getName(), 'pr_timestamp' => wfTimestampNow())
		);
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;
		if ($wgUser->getID() == 0) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->setHeaders();
		$wgOut->addHTML(wfMsg('proposedredirects_info'));
		$t = Title::makeTitle(NS_PROJECT, "Proposed Redirects");
		$a = new Article($t);

		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/createpage.css'; /*]]>*/</style> ");

		if ($wgRequest->wasPosted()) {
			// deal with collisions of loading and saving
			$changes = array();
			foreach ($wgRequest->getValues() as $key=>$value) {
				if (strpos($key, "id-") === false) continue;
				$id = str_replace("id-", "btn-", $key);
				$newval = $wgRequest->getVal($id);
				switch($newval) {
					case 'accept':
					case 'reject':
						$changes[$value] = $newval;
				}
			}
			foreach ($changes as $c=>$v) {
				$params = split("_", $c);
				$from = Title::makeTitle(NS_MAIN, str_replace("_", " ", $params[0]));
				$to = Title::makeTitle(NS_MAIN, str_replace("_", " ", $params[1]));
				if ($v == 'accept') {
					$a = new Article($from);
					if ($from->getArticleID() == 0)
						$a->insertNewArticle("#REDIRECT [[{$to->getText()}]]\n", "Creating proposed redirect", false, false);
					else
						$a->updateArticle("#REDIRECT [[{$to->getText()}]]\n", "Creating proposed redirect", false, false);
					$log = new LogPage( 'redirects', true );
					$log->addEntry('added', $from, 'added', array($to, $from));
				}
				$this->deleteProposedRedirect($from->getDBKey(), $to->getDBKey());
			}
			$wgOut->redirect('');
		}

		// regrab the text if necessary
		$r = Revision::newFromTitle($t);
		$text = "";
		if ($r) {
			$text = $r->getText();
		}
		$lines = split("\n", $text);
		$s = "";
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('proposedredirects', array('pr_from', 'pr_to', 'pr_user_text'), array(), "ProposedRedirects::execute", array("LIMIT" => 250));
		while ($row = $dbr->fetchObject($res)) {
			$u = User::newFromName($row->pr_user_text);
			$to = Title::newFromText($row->pr_to);
			$from = Title::newFromText($row->pr_from);
			$key = htmlspecialchars($from->getDBKey() . "_" . $to->getDBKey());
			if (!$u)
				$url = "/User:{$row->pr_user_text}";
			else
				$url = $u->getUserPage()->getFullURL();
			$id = rand(0,10000);
			$s .= "<tr>
					<td>{$row->pr_user_text}</td>
					<td><a href='{$from->getFullURL()}' target='new'>{$from->getText()}</td>
					<td>
						<input type='hidden' name='id-{$id}' value=\"{$key}\"/>
						<a href='{$to->getFullURL()}' target='new'>{$to->getText()}</td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='accept'/></td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='skip' CHECKED/></td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='reject'/></td>
					</tr>";
		}
		if ($s == "") {
			$wgOut->addHTML("There are currently no proposed redirects to show. Please check again later.");
		} else {
			$wgOut->addHTML("
					<script type='text/javascript'>
						function clearForm(){
							for (i = 0; i < document.proposedform.elements.length; i++) {
								var e = document.proposedform.elements[i];
								if (e.type=='radio') {
									if (e.value=='skip') {
										e.checked = true;
									} else {
										e.checked = false;
									}
								}
							}
						}
						function rejectAll(){
							for (i = 0; i < document.proposedform.elements.length; i++) {
								var e = document.proposedform.elements[i];
								if (e.type=='radio') {
									if (e.value=='reject') {
										e.checked = true;
									} else {
										e.checked = false;
									}
								}
							}
							alert('Warning! You have chosen to reject all of the proposed redirects, please use this carefully. Press Reset to Undo.');
						}
					</script>
					<form method='POST' action='/Special:ProposedRedirects' name='proposedform'>
						<table class='p_redirects'>
						<tr class='toprow'>
							<td>User</td><td>Title</td><td>Current Article</td><td>Accept</td><td>Skip</td><td>Reject</td>
						</tr>
						$s
						</table>
					<table width='100%'>
						<tr><td>

			");
			if ($wgUser->getName()=='ElizabethD' || $wgUser->isSysop()) {
				$wgOut->addHTML("<input type='button' class='guided-button' value='Reject all' onclick='rejectAll();'>");
			}
			$wgOut->addHTML("</td><td style='text-align: right;'>
								<input type='button' class='guided-button' value='Reset' onclick='clearForm();'>
								<input type='button' class='guided-button' value='Submit' onclick='document.proposedform.submit();'>
							</td></tr></table>
						</form>
				");
		}
	}
}

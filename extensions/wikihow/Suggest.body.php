<?

class RequestTopic extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'RequestTopic' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;

		wfLoadExtensionMessages('RequestTopic');

	
		$pass_captcha = true;	
		if ($wgRequest->wasPosted()) {
        	$fc = new FancyCaptcha();
        	$pass_captcha   = $fc->passCaptcha();
		}

		if ($wgRequest->wasPosted() && $pass_captcha) {
			$dbr = wfGetDB(DB_SLAVE);
			require_once('EditPageWrapper.php');
			$title = EditPageWrapper::formatTitle($wgRequest->getVal('suggest_topic'));
			$s = Title::newFromText($title);
			if (!$s) {
				$wgOut->addHTML("There was an error creating this title.");
				return;
			}
			// does the request exist as an article? 
			if ($s->getArticleiD()) {
				$wgOut->addHTML(wfMsg('suggested_article_exists_title'));
				$wgOut->addHTML(wfMsg('suggested_article_exists_info', $s->getText(), $s->getFullURL()));
				return;
			}
			// does the request exist in the list of suggested titles? 
			$email = $wgRequest->getVal('suggest_email');
			if (!$wgRequest->getVal('suggest_email_me_check'))
				$email ='';

			$count = $dbr->selectField('suggested_titles', array('count(*)'), array('st_title' => $s->getDBKey()));
			$dbw = wfGetDB(DB_MASTER);
			if ($count == 0) {	
				$dbw->insert('suggested_titles', 
						array('st_title'	=> $s->getDBKey(), 
							'st_user'	=> $wgUser->getID(),
							'st_user_text'	=> $wgUser->getName(),
							'st_isrequest'	=> 1, 
							'st_category'	=> $wgRequest->getVal('suggest_category'), 
							'st_suggested'	=> wfTimestampNow(),
							'st_notify'		=> $email,
							'st_source'		=> 'req',
							'st_key'		=> generateSearchKey($title),
							'st_group'		=> rand(0, 4)
						)
					);
			} else if ($email != ''){
				//request exists lets add the user's email to the list of notifications
				$existing = $dbr->selectField('suggested_titles', array('st_notify'), array('st_title' => $s->getDBKey()));
				if ($existing != '')
					$email = "$existing, $email";
				$dbw->update('suggested_titles',
					array('st_notify'     => $email),
					array('st_title'    => $s->getDBKey())
					);
			}
			$wgOut->addHTML(wfMsg("suggest_confirmation", $s->getFullURL(), $s->getText()));
			return;
		}
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
		$wgOut->addHTML(wfMsg('suggest_header'));
		$wgOut->addHTML(wfMsg('suggest_sub_header'));

		$wgOut->addHTML("<form action='/Special:RequestTopic' method='POST' onSubmit='return checkSTForm();' name='suggest_topic_form'>");
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');
		$wgOut->addScript("<script type='text/javascript'/>var gSelectCat = '" . wfMsg('suggest_please_select_cat') . "';
		var gEnterTitle = '" . wfMsg('suggest_please_enter_title') . "';
		var gEnterEmail  = '" . wfMsg('suggest_please_enter_email') . "';
	</script>");

		$fc = new FancyCaptcha();		
		$cats = $this->getCategoryOptions();
		$wgOut->addHTML(wfMsg('suggest_input_form', $cats, $fc->getForm(),  $pass_captcha ? "" : wfMsg('suggest_captcha_failed')));
		$wgOut->addHTML(wfMsg('suggest_notifications_form', $wgUser->getEmail()));
		$wgOut->addHTML(wfMsg('suggest_submit_buttons'));
		$wgOut->addHTML("</form>");
	}

    function getCategoryOptions($default = "") {
            global $wgUser;

            // only do this for logged in users
            $t = Title::newFromDBKey("WikiHow:" . wfMsg('requestcategories') );
            $r = Revision::newFromTitle($t);
            if (!$r)
                return '';
            $cat_array = split("\n", $r->getText());
            $s = "";
            foreach($cat_array as $line) {
                $line = trim($line);
                if ($line == "" || strpos($line, "[[") === 0) continue;
                $tokens = split(":", $line);
                $val = "";
                $val = trim($tokens[sizeof($tokens) - 1]);
                $s .= "<OPTION VALUE=\"" . $val . "\">" . $line . "</OPTION>\n";
            }
            $s = str_replace("\"$default\"", "\"$default\" SELECTED", $s);

            return $s;
    }
}

class ListRequestedTopics extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'ListRequestedTopics' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;
		
		wfLoadExtensionMessages('RequestTopic');
		list( $limit, $offset ) = wfCheckLimits();	
		$dbr = wfGetDB(DB_SLAVE);
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');

		$category = $wgRequest->getVal('category', '');
		$wgOut->addHTML(wfMsg('suggsted_list_topics_title'));
		$wgOut->addHTML(wfMsg('suggested_show_by_category', RequestTopic::getCategoryOptions($category)));
		$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));

		if ($wgRequest->getVal('st_search')) {
			$key = generateSearchKey($wgRequest->getVal('st_search'));
			$sql = "select st_title, st_user_text, st_user from suggested_titles where  st_isrequest = 1 and st_used = 0 
				and st_patrolled=1 and st_key like " . $dbr->addQuotes("%" . str_replace(" ", "%", $key) . "%") . ";";
		} else {
			$sql = "select st_title, st_user_text, st_user from suggested_titles where  st_isrequest = 1  and st_used= 0"
				. ($category == '' ? '' : " AND st_category = " . $dbr->addQuotes($category))
				. " and st_patrolled=1 ORDER BY st_suggested desc LIMIT $offset, $limit";
		}
		$res = $dbr->query($sql);
		$wgOut->addHTML("<br/><br/><table width='100%' class='suggested_titles_list'>
				<tr class='st_top_row'><td class='st_title'>Article request</td><td>Requestor</td><td>Write</td></tr>
			");
		$count = 0;
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromDBKey($row->st_title);
			if (!$t) continue;
			$c = "";
			if ($count % 2 == 1) $c = "class='st_on'";
			if ($row->st_user == 0) {
				$wgOut->addHTML("<tr $c><td class='st_title'>{$t->getText()}</td><td>Anonymous</td>
					<td class='st_write'><a href='{$t->getEditURL()}'>Write Article</td></tr>");
			} else {
				$u = User::newFromName($row->st_user_text);
				$wgOut->addHTML("<tr $c><td class='st_title'>{$t->getText()}</td><td><a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>
					<td class='st_write'><a href='{$t->getEditURL()}'>Write Article</td></tr>");
			}
			$count++;
		}
		$wgOut->addHTML("</table>");
		if ($offset != 0) {
			$wgOut->addHTML("<a style='float: left;' href='/Special:ListRequestedTopics?offset=" . (max($offset - $limit, 0)) . "'>Previous {$limit}</a>");
		}
		if ($count == $limit) {
			$wgOut->addHTML("<a style='float: right;' href='/Special:ListRequestedTopics?offset=" . ($offset + $limit) . "'>Next {$limit}</a>");
		}
		$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));
	}
}


class ManageSuggestedTopics extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'ManageSuggestedTopics' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;

        if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
            $wgOut->setArticleRelated( false );
            $wgOut->setRobotpolicy( 'noindex,nofollow' );
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }

		wfLoadExtensionMessages('RequestTopic');
		list( $limit, $offset ) = wfCheckLimits();	
		
		$wgOut->addHTML('<script type="text/javascript" language="javascript" src="/extensions/wikihow/winpop.js"></script>
   	<link rel="stylesheet" href="/extensions/wikihow/winpop.css" type="text/css" />');


		$dbr = wfGetDB(DB_SLAVE);
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');

		if ($wgRequest->wasPosted()) {
			$accept = array();
			$reject = array();
			$updates = array();
			$newnames = array();
			foreach ($wgRequest->getValues() as $key=>$value) {
				$id = str_replace("ar_", "", $key);
				if ($value== 'accept') {
					$accept[] = $id;
				} else if ($value == 'reject'){
					$reject[] = $id;
				} else if (strpos($key, 'st_newname_') !== false) {
					$updates[str_replace('st_newname_', '', $key)] = $value;
					$newnames[str_replace('st_newname_', '', $key)] = $value;
				}
			}
			$dbw = wfGetDB(DB_MASTER);
			if (sizeof($accept) > 0) {
				$dbw->query("update suggested_titles set st_patrolled=1 where st_id in (" . implode(",", $accept) . ")");
			} 
			if (sizeof($reject) > 0) {
				$dbw->query("delete from suggested_titles where st_id in (" . implode(",", $reject) . ")");
			}

			foreach ($updates as $u=>$v) {
				$t = Title::newFromText($v);
				if (!$t) continue;

				// renames occassionally cause conflicts with existing requests, that's a bummer
				if (isset($newnames[$u])) {
					$page = $dbr->selectField('page', array('page_id'), array('page_title'=>$t->getDBKey()));
					if ($page) {
						// wait, this article is already written, doh
						$notify = $dbr->selectField('suggested_titles', array('st_notify'), array('st_id'=>$u));
						if ($notify) {	
                			$dbw->insert('suggested_notify', array('sn_page' => $page,'sn_notify' => $notify, 'sn_timestamp' => wfTimestampNow(TS_MW)));
						}
						$dbw->delete('suggested_titles', array('st_id' => $u));
					}
					$id = $dbr->selectField('suggested_titles', array('st_id'), array('st_title'=>$t->getDBKey()));
					if ($id) {
						// well, it already exists... like the Highlander, there can be only one
						$notify = $dbr->selectField('suggested_titles', array('st_notify'), array('st_id'=>$u));
						if ($notify != '') {
							// append the notify to the existing
							$dbw->update('suggested_titles', array('st_notify = concat(st_notify, ' . $dbr->addQuotes("\n" . $notify) . ")"), array('st_id' => $id));
						}
						// delete the old one
						$dbw->delete('suggested_titles', array('st_id' => $u));
					}
				}
				$dbw->update('suggested_titles', 
					array('st_title' => $t->getDBKey()),
					array('st_id' => $u));
			} 
			$wgOut->addHTML(sizeof($accept) . " suggestions accepted, " . sizeof($reject) . " suggestions rejected.");
		}
		$sql = "select st_title, st_user_text, st_category, st_id
				from suggested_titles where  st_isrequest = 1  and st_used= 0
				and st_patrolled=0 ORDER BY st_suggested desc LIMIT $offset, $limit";
		$res = $dbr->query($sql);
		$wgOut->addHTML("<br/><br/>
				<form action='/Special:ManageSuggestedTopics' method='POST' name='suggested_topics_manage'>	
				<table width='100%' class='suggested_titles_list'>
				<tr class='st_top_row'>
				<td class='st_title'>Article request</td>
				<td>Category</td>
				<td>Edit Title</td>
				<td>Requestor</td>
				<td>Accept</td>
				<td>Reject</td>
			</tr>
			");
		$count = 0;
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromDBKey($row->st_title);
			if (!$t) continue;
			$c = "";
			if ($count % 2 == 1) $c = "class='st_on'";
			$u = User::newFromName($row->st_user_text);	
				
			$wgOut->addHTML("<tr $c>
					<input type='hidden' name='st_newname_{$row->st_id}' value=''/>	
					<td class='st_title_m' id='st_display_id_{$row->st_id}'>{$t->getText()}</td>
					<td>{$row->st_category}</td>
					<td><a href='' onclick='javascript:editSuggestion({$row->st_id}); return false;'>Edit</a></td>
					" .  ($u ? "<td><a href='{$u->getUserPage()->getFullURL()}' target='new'>{$u->getName()}</a></td>"
							: "<td>{$row->st_user_text}</td>" ) . 
					"<td class='st_radio'><input type='radio' name='ar_{$row->st_id}' value='accept'></td>
					<td class='st_radio'><input type='radio' name='ar_{$row->st_id}' value='reject'></td>
				</tr>");
			$count++;
		}
		$wgOut->addHTML("</table>
			<br/><br/>
			<table width='100%'><tr><td style='text-align:right;'><input type='submit' value='Submit' class='button white_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'/></td></tr></table>
			</form>
			");
	}
}

class RenameSuggestion extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RenameSuggestion' );
    }

    function execute ($par) {
		global $wgOut;
		wfLoadExtensionMessages('RequestTopic');
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML(wfMsg('suggested_edit_title'));
		return;
	}

}

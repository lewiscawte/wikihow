<?
class Managepagelist extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Managepagelist' );
	}

	function execute ($par) {
		global $wgOut, $wgRequest, $wgUser;

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
		 	$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
		 	return;
		}

		$list = $wgRequest->getVal('list', 'risingstar');
		$wgOut->addHTML('<link rel="stylesheet" href="/extensions/wikihow/pagelist.css" type="text/css" />');

		$dbr = wfGetDB(DB_SLAVE);

		// handle removals
		if ($wgRequest->getVal('a') == 'remove') {
			$t = Title::newFromID($wgRequest->getVal('id'));
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete('pagelist', array('pl_page' => $wgRequest->getVal('id'), 'pl_list'=>$list));
			$wgOut->addHTML("<p style='color:blue; font-weight: bold;'>{$t->getFullText()} has been remove from the list.</p>");

		}
		if ($wgRequest->wasPosted()) {
			if ($wgRequest->getVal('newlist')) {
				$list = $wgRequest->getVal('newlist');
				$mw = Title::makeTitle(NS_MEDIAWIKI, 'Pagelist_' . $wgRequest->getVal('newlist'));
				$a = new Article(&$mw);
				$a->doEdit($wgRequest->getVal('newlistname'), "creating new page list");
			}
			if ($wgRequest->getVal('newtitle')) {
				$url = $wgRequest->getVal('newtitle');
				$url = preg_replace("@http://@", "", $url);
				$url = preg_replace("@.*/@U", "", $url);
				$t = Title::newFromURL($url);
				if (!$t || !$t->getArticleID()) {
					$wgOut->addHTML("<p style='color:red; font-weight: bold;'>Error: Couldn't find article id for {$wgRequest->getVal('newtitle')}</p>");
				} else {
					if ($dbr->selectField("pagelist", array("count(*)"), array('pl_page' => $t->getArticleID(), 'pl_list'=>$list)) > 0) {
						$wgOut->addHTML("<p style='color:red; font-weight: bold;'>Oops! This title is already in the list</p>");
					} else {	
						$dbw = wfGetDB(DB_MASTER);
						$dbw->insert('pagelist', array('pl_page' => $t->getArticleID(), 'pl_list'=>$list));
						if ($list == 'risingstar') {
							// add the rising star template to the discussion page
							$talk = $t->getTalkPage();
							$a = new Article($talk);
							$text = $a->getContent();
							$min = $dbr->selectField('revision', array("min(rev_id)"), array('rev_page'=>$t->getArticleId()));
							$name = $dbr->selectField('revision', array('rev_user_text'), array('rev_id'=>$min));
							$text = "{{Rising-star-discussion-msg-2|[[User:{$name}|{$name}]]|[[User:{$wgUser->getName()}|{$wgUser->getName()}]]}}\n" . $text;
							$a->doEdit($text, wfMsg('nab-rs-discussion-editsummary'));

							// add the comment to the user's talk page
							Newarticleboost::notifyUserOfRisingStar($t, $name);
						}	
						$wgOut->addHTML("<p style='color:blue; font-weight: bold;'>{$t->getFullText()} has been added to the list.</p>");
					}
				}
			}
		}
		$wgOut->setPageTitle("Manage page list - " . wfMsg('pagelist_' . $list));
		$wgOut->addHTML("<form name='addform' method='POST' action='/Special:Managepagelist'>
				<table style='width: 100%;'><tr><td style='width: 430px;'>
					Add article to this list by URL or title: 
						<input type='text' name='newtitle' id='newtitle'></td>
					<td style='width: 32px; vertical-align: bottom;'><input type='image' class='addicon' src='/extensions/wikihow/plus.png' onclick='javascript:document.addform.submit()'/></td>
		<td style='text-align: right;'>View list:<br/>
					<select onchange='window.location.href=\"/Special:Managepagelist&list=\" + this.value;'>
			");

		$res = $dbr->query("select distinct(pl_list) from pagelist;");
		while ($row = $dbr->fetchObject($res)) {
			if ($row->pl_list == $list) {
				$wgOut->addHTML("<OPTION SELECTED style='font-weight: bold;'>" . wfMsg('pagelist_' . $row->pl_list) . "</OPTION>\n");
			} else {
				$wgOut->addHTML("<OPTION>" . wfMsg('pagelist_' . $row->pl_list) . "</OPTION>\n");
			}
		}
		$wgOut->addHTML("</select></td></tr></table>
				</form>");
		$res = $dbr->select (array('page', 'pagelist'),
			array('page_title', 'page_namespace', 'page_id'),
			array('page_id=pl_page', 'pl_list'=>$list),
			"Managepagelist::execute",
			array("ORDER BY" => "pl_page DESC", ));

		$wgOut->addHTML("<br/><p>There are " . number_format($dbr->numRows($res), 0, "", ",") . " articles in this list.</p>");
		$wgOut->addHTML("<table class='pagelist'>");
		$index = 0;
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if (!$t) {
				echo "Couldn't make title out of {$row->page_namespace} {$row->page_title}\n";
				continue;
			}
			if ($index % 2 == 0) 
				$wgOut->addHTML("<tr>");
			else
				$wgOut->addHTML("<tr class='shaded'>");
			$wgOut->addHTML("<td class='pagelist_title'><a href='{$t->getFullURL()}' target='new'>{$t->getFullText()}</td>
				<td><a href='/Special:Managepagelist?a=remove&list={$list}&id={$row->page_id}' onclick='return confirm(\"Do you really want to remove this article?\")'><img src='http://www.wikihow.com/extensions/wikihow/rcwDelete.png' style='height: 24px; width: 24px;'></a></td>");
			$wgOut->addHTML("</tr>");
			$index++;
		}	
		$wgOut->addHTML("</table>");


   $wgOut->addHTML("<form name='addlistform' method='POST' action='/Special:Managepagelist'>
				<br/><br/><table width='100%'><tr><td>
					Create a new list: (Example: ID risingstar Name: Rising Stars, every list needs 1+ article)<br/><br/>
	
						ID: <input type='text' name='newlist' id='newlist'> Name: <input type='text' name='newlistname' id='newlistname'><br/><br/>
						Article: <input type='text' name='newtitle' id='newtitle'>
					</td>
					<td style='width: 32px; vertical-align: bottom;'><input type='image' class='addicon' src='/extensions/wikihow/plus.png' onclick='javascript:document.addlistform.submit()'/></td>
				</tr></table>
				</form>
			");

	}
}

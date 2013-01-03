<?
class Bunchpatrol extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'Bunchpatrol' );
    }

    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
	
		if ($target == $wgUser->getName() ) {
			$wgOut->addHTML(wfMsg('bunchpatrol_noselfpatrol'));
			return;	
		}
	
		$sk = $wgUser->getSkin();
		$dbr = &wfGetDB(DB_SLAVE);
		$me = Title::makeTitle(NS_SPECIAL, "Bunchpatrol");

 		$unpatrolled = $dbr->selectField('recentchanges', array('count(*)'), array('rc_patrolled=0'));
		if ( !strlen( $target ) ) {
			$restrict = " AND rc_namespace != 0 ";
			$res = $dbr->query(" select rc_user, rc_user_text, count(*) as C from recentchanges where rc_patrolled=0 {$restrict} group by rc_user_text having C > 2 order by C desc;");
			$wgOut->addHTML("<table width='85%' align='center'>");
			while ( ($row = $dbr->fetchObject($res)) != null) {
				$u = User::newFromName($row->rc_user_text);
				if ($u) {
					$bpLink = SpecialPage::getTitleFor( 'Bunchpatrol', $u->getName() );
					$wgOut->addHTML("<tr><td>" . $sk->makeLinkObj($bpLink,$u->getName()) . "</td><td>{$row->C}</td>");
				}
			}
			$dbr->freeResult($res);
			$wgOut->addHTML("</table>");
			return;
		}
	
		if ($wgRequest->wasPosted() && $wgUser->isAllowed('patrol') ) {
			$values = $wgRequest->getValues();
			$vals = array();
			foreach ($values as $key=>$value) {
				if (strpos($key, "rc_") === 0 && $value == 'on') {
					$vals[] = str_replace("rc_", "", $key);
				}
			}
			foreach ($vals as $val) {
	        	RecentChange::markPatrolled( $val );
	            PatrolLog::record( $val, false );
			}
			$restrict = " AND rc_namespace != 0 ";
	        $res = $dbr->query(" select rc_user, rc_user_text, count(*) as C from recentchanges where rc_patrolled=0 {$restrict} group by rc_user_text having C > 2 order by C desc;");
	        $wgOut->addHTML("<table width='85%' align='center'>");
	        while ( ($row = $dbr->fetchObject($res)) != null) {
	            $u = User::newFromName($row->rc_user_text);
	            if ($u)
	                $wgOut->addHTML("<tr><td>" . $sk->makeLinkObj($me,$u->getName(), "target=" . $u->getName()) . "</td><td>{$row->C}</td>");
	        }
	        $wgOut->addHTML("</table>");
	        return;
		}

		// don't show main namespace edits if there are < 500 total unpatrolled edits
		$target = str_replace('-', ' ', $target);
		$opts = array ('rc_user_text' =>$target, 'rc_patrolled=0');
		$opts[] = 'rc_namespace != 0';
	
		$res = $dbr->select ( 'recentchanges',
				array ('rc_id', 'rc_title', 'rc_namespace', 'rc_this_oldid', 'rc_cur_id', 'rc_last_oldid'),
				$opts,
			"wfSpecialBunchpatrol", 
				array ('LIMIT' => 15)
			);
		$count = 0;
		$wgOut->addHTML("
			<script type='text/javascript'>
			function checkall(selected) {
				for (i = 0; i < document.checkform.elements.length; i++) {
					var e = document.checkform.elements[i];
					if (e.type=='checkbox') {
						e.checked = selected;
					}
				}
			}
			</script>
			<form method='POST' name='checkform' action='{$me->getFullURL()}'>
			<input type='hidden' name='target' value='{$target}'>
			");
		if ($wgUser->isSysop())
			$wgOut->addHTML("Select: <input type='button' onclick='checkall(true);' value='All'/>
					<input type='button' onclick='checkall(false);' value='None'/>
				");
		$wgOut->addHTML(" <table width='100%' align='center' class='bunchtable'>
				<tr><td><b>Patrol?</b></td><td align='center'><b>Diff</b></td></tr>
			");
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
			$diff = $row->rc_this_oldid;
			$rcid = $row->rc_id;
			$oldid = $row->rc_last_oldid;
			$de = new DifferenceEngine( $t, $oldid, $diff, $rcid );
			$wgOut->addHTML("<tr> <td valign='middle' style='padding-right:24px; border-right: 1px solid #eee;'><input type='checkbox' name='rc_{$rcid}'></td><td style='border-top: 1px solid #eee;'>");
			$wgOut->addHTML($sk->makeLinkObj($t));
			$de->showDiffPage(true);
			$wgOut->addHTML("</td></tr>");
			$count++;
		}	
		$wgOut->addHTML("</table><br/><br/>");
		if ($count > 0) {
			$wgOut->addHTML("<input type='submit' value='" . wfMsg('submit') . "'>");
		}
		$wgOut->addHTML("</form>");
		$wgOut->setPageTitle(wfMsg('bunchpatrol'));	
		$dbr->freeResult($res);
		if ($count == 0) {
			$wgOut->addWikiText(wfMsg('bunchpatrol_nounpatrollededits', $target));
		}
	}
}	

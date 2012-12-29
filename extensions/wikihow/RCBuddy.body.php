<?php
class RCBuddy extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RCBuddy' );
    }

    function execute ($par) {
		global $wgOut, $wgUser, $wgRequest, $wgServer, $wgCookiePrefix;
		
	require_once('SpecialRecentchanges.php');
	
		$dbr =& wfGetDB( DB_SLAVE );
	
		$skip = "";
	foreach ($_COOKIE as $key=>$value) {
			if (strpos($key, $wgCookiePrefix . "WsSkip_") === 0) {
				if ($skip != "") $skip .= ",";
				$skip .= $value;
			}
		}
		$delay = $wgRequest->getVal('delay');
		$row = $dbr->selectRow(array ('recentchanges', 'page'),
				array ('count(*) as c'),
				array ('rc_cur_id=page_id',
						'rc_patrolled=0',
						'rc_user !=' . $wgUser->getId(),
						'page_is_featured=1',
						$delay == 0 ? "1=1" : "rc_timestamp < '" . wfTimestamp( TS_MW, time() - $delay * 60 ) . "'",
						$skip == "" ? "1=1" : "rc_id not in ($skip)",
					),
				"wfSpecialRCBuddy"
			);
		$count = $row->c;
		
		if ($wgRequest->getVal('justcount', null) != null) {
			$wgOut->setArticleBodyOnly(true);
			#$wgOut->addHeader('Content-type',  'text/plain');
			header("Content-type: text/plain");
			$wgOut->addHTML("unpatrolled_fa=$count\n");
	    	$row = $dbr->selectRow(array ('recentchanges', 'page'),
	            array ('count(*) as c'),
	            array ('rc_cur_id=page_id',
	                    'rc_patrolled=0',
	                    ),
	            "wfSpecialRCBuddy"
	        );
	    	$count = $row->c;
			$wgOut->addHTML("unpatrolled_total=$count\n");
		
			$t =  gmdate("YmdHis", time() - 60 * 30); // thirty minutes ago
	        $row = $dbr->selectRow(array ('recentchanges'),
	            array ('count(distinct(rc_user)) as c'),
	            array ("rc_timestamp > $t ",
	                    'rc_user > 0',
	                    ),
	            "wfSpecialRCBuddy"
	        );
	    	$count = $row->c;
			$wgOut->addHTML("users_editing=$count\n");
	
	    	if( $wgUser->getNewtalk())
	        	$wgOut->addHTML("new_talk=1\n");
	    	else
	        	$wgOut->addHTML("new_talk=0\n");
	
			$window = Patrolcount::getPatrolcountWindow();
	        $count=$dbr->selectField('logging',
	                    array('count(*)'),
	                    array("log_user={$wgUser->getId()}", "log_type='patrol'", 
							"log_timestamp > '{$window[0]}'",
							"log_timestamp < '{$window[1]}'",
						)
					);
			echo "\ndebug: user: {$wgUser->getId()} date range: {$window[0]}, {$window[1]} user's offset {$wgUser->getOption( 'timecorrection' )}\n";
			echo "patrolledtoday=$count\n";

			$nab_unpatrolled = $dbr->selectField('newarticlepatrol',
					array('count(*)'),
					array('nap_patrolled=0')
				);
			echo "nab_unpatrolled=$nab_unpatrolled\n";	
			return;
		}
		$wgOut->setArticleBodyOnly(true);
	
	    if ($wgUser->getID() > 0) {
	        $sql = "insert into rcbuddy (rcbuddy_user, rcbuddy_count) VALUES ({$wgUser->getID()}, 1) on duplicate key update rcbuddy_count = rcbuddy_count + 1;";
	        $dbw =& wfGetDB( DB_MASTER );
	        $dbw->query($sql);
	    }   
	
		// whose online? 
		$userTable = $dbr->tableName( 'user' );
		$sql = "select user_id, user_name from rcbuddy left join $userTable on rcbuddy_user=user_id where timediff(now(), rcbuddy_update) < '00:01:20'";	
		$res = $dbr->query($sql);
		$users = array();
		while ($row = $dbr->fetchObject($res)) {
			// xclude self
			if ($row->user_name != $wgUser->getName()) 
				$users[] = User::newFromName($row->user_name);
		}
		$sk = $wgUser->getSkin();
		$user_html = "";
		foreach($users as $user) {
			$user_html .= $sk->makeLinkObj($user->getUserPage(), $user->getName()). " ";
		}
		if ($user_html != "") 
			$user_html = wfMsg('rcbuddy_online', $user_html);
	
		$head = "";
		$body_style = "rc_body_style";
		$rcbuddyicon = "rcbuddyicon";
		if ($count > 0) {
			$head = " - ($count)";
			$body_style = "rc_body_style2";
			$rcbuddyicon = "rcbuddyicon2";
		}
	
		// sounds 
		$sound = "";
		$url = $_SERVER['REQUEST_URI'];
		if ($wgRequest->getVal('sound', null) == 1) {
			$url = str_replace("sound=1", "sound=0", $url);
			$sound = "<a href='$url' target='_top'><img src='/extensions/wikihow//audio-volume-high.png'></a>";
			if ($count > 0) 
				$sound .= "<embed src='" . $wgServer . "/extensions/wikihow/rcbuddy.wav' autostart='true' hidden='true'>";
		} else {
			$url = str_replace("sound=0", "", $url);
			$sound = "<a href='$url&sound=1' target='_top'><img src='/extensions/wikihow//audio-volume-muted.png'></a>";
		}
		$wgOut->addHTML("
				<html>
					<head>
						<title>" . wfMsg('rcbuddy_title') . " $head</title>
					</head>
					 <style type=\"text/css\" media=\"screen,projection\">/*<![CDATA[*/ @import \"/skins/WikiHow/main.css\"; /*]]>*/</style>
					<style>
						.rcoptions {
							display:none;
						}
						.namespacesettings {
							display:none;
							background: #ccc;
						}
						.refresh_options {
							display:none;
						}
						p {
							display:none;
							background: #ccc;
						}
						a {
						}
						#rcbuddy {
							padding: 5px;
						}	
						#rc_body_style {
							font-family: Arial, Helvetica, Sans-Serif;
							font-size: x-small;
							background: #F9F7ED;
						}
						#rc_body_style2 {
							font-family: Arial, Helvetica, Sans-Serif;
							font-size: x-small;
							background: #FF9999;
						}
						#rcbuddyicon {
							height: 32px;
							background: url('http://tango.freedesktop.org/static/cvs/tango-icon-theme/32x32/emotes/face-monkey.png') center right no-repeat;
						}
						#rcbuddyicon2 {
							height: 32px;
							background: url('http://tango.freedesktop.org/static/cvs/tango-icon-theme/32x32/emotes/face-surprise.png') center right no-repeat;
						}	
					</style>
					<meta http-equiv=\"refresh\" content=\"60\">	
				<body id='$body_style'>	
		 $user_html 		
					<div id='rcbuddy'>
				$sound 
						<div id='$rcbuddyicon'/>
				");
		wfSpecialRecentchanges($par, $this);	
		$wgOut->addHTML("</div>
					</body>
					<script type='text/javascript'>
						var elements = document.getElementsByTagName('a') ;
						for (var i = 0; i < elements.length; i++) {
							//elements.item(i).onclick = 'window.open(\'' + elements.item(i).href + '\')';
							//elements.item(i).onclick = 'alert(\'hi\');'; //'window.open(\'' + elements.item(i).href + '\')';
							if (elements.item(i).target != '_top')
								elements.item(i).target = 'new';
						}
					</script>
				</html>
			");
	}
}
	

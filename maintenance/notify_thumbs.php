<?
	require_once('commandLine.inc'); 
	$wgUser = User::newFromName("ThumbnailNotifier");
	$dbw = wfGetDB(DB_MASTER); 
	$res = $dbw->select(array('ytnotify','page'), array('page_title', 'page_namespace', 'ytn_user', 'ytn_user_text', 'ytn_page'), 
			array('ytn_published'=>0, 'ytn_page=page_id')); 
	while ($row = $dbw->fetchObject($res)) {
		$t = Title::makeTitle(NS_MAIN, $row->page_title);
		if (YTThumb::hasThumbnails($t)) {
			// notify user
			$u = User::newFromName($row->ytn_user_text); 
			$talkpage = $u->getTalkPage(); 
			$rev = Revision::newFromTitle($talkpage); 
			$text = $rev->getText(); 
			$message = '
<div class="de">
<div class="de_header">
<p class="de_date">On ' . $dateStr = $wgLang->timeanddate(wfTimestampNow()) . '</p>
<p class="de_user">[[User:ThumbnailNotifier|ThumbnailNotifier]] said:</p>
</div>
<div class="de_comment">
Congralations, the thumbnails for the video you recently embedded on [[' . $t->getText() . ']] are
ready to be uploaded.
</div>
<div class="de_reply">[[User_talk:ThumbnailNotifier#post|Reply to ThumbnailNotifier]] </div></div>';
			$text .= $message;
			$a = new Article($talkpage); 
			echo "Notifying {$u->getName()}\n";
			$a->doEdit($text, "Notifying user of thumbnails");
			$dbw->update('ytnotify', array('ytn_published'=>1, 'ytn_published_time'=>wfTimestampNow()), 
				array('ytn_user'=>$row->ytn_user, 'ytn_page'=>$row->ytn_page));
		}
	}

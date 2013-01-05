<?
class ManageVideo extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'ManageVideo' );
    }

    function removeVideo($id) {
        global $wgUser;
        $dbw = wfGetDB(DB_MASTER);
        $dbw->insert('video_blacklist',
            array('vb_id'   => $id,
                'vb_user'   => $wgUser->getId(),
                'vb_user_text' => $wgUser->getName(),
                'vb_timestamp' => wfTimestamp( TS_MW )
            )
        );
        $this->clearFlags($id);
    }
    function execute ($par) {
        global $wgOut, $wgUser, $wgRequest;

        if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }
        $target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$title = Title::newFromText($target);
        $rv = new RelatedVideos($title);
		$me = SpecialPage::getTitleFor("ManageVideo", $title->getText());;


		$this->setHeaders();
		// any to remove?
		if ($wgRequest->getVal('act') == 'remove') {
			$id = $wgRequest->getVal('id');
			RelatedVideos::remove($title, $id);		
			$wgOut->addHTML("Video id {$id} has been removed as Related Video from the article {$title->getText()}<br/><br/>\n");
		}
		$wgOut->addHTML(wfMsg('managevideo_instructions') . "<br/><br/>");
        if ($rv->hasResults()) {
            $vids = $rv->getResults();
            $related_vids = "<table width='80%'>\n";
            foreach ($vids as $v) {
                $related_vids  .= "<tr><td><a href='/video/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) . "'>{$v['title']}</a></td>\n";
				$related_vids  .= "<td><a href='{$me->getFullURL()}?act=remove&id={$v['id']}'>Remove</a></td>";
            }
            $related_vids  .= "</table";
        }
		$wgOut->addHTML($related_vids);
		$wgOut->addHTML("<br/><br/><a href='{$title->getFullURL()}'>Return to the article {$title->getFullText()}</a>");
	}
}


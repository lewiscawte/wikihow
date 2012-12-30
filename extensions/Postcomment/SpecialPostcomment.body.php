<?
class Postcomment extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Postcomment' );
    }

	 function getForm($new_window = false, $title = null, $return_result = false) {
	        
		global $wgUser, $wgTitle,$wgRequest;
		wfLoadExtensionMessages('Postcomment');
	
		$postbtn = " class= 'button button52 submit_button' " 
			   	. "onmouseout = 'button_unswap(this);' " 
            	. "onmouseover = 'button_swap(this);' "
            	. "style = 'margin-right:10px;' ";

		$prevbtn = " class= 'button white_button_100 submit_button' "
                . "onmouseout = 'button_unswap(this);' " 
                . "onmouseover = 'button_swap(this);' "
                . "style = 'margin-right:10px;' ";
			
		if ($title == null)
			$title = $wgTitle;
	
		if (!$title->userCanEdit()) {
			return;
		}
	
		if ( !$wgUser->isAllowed('edit') ) {
			return;
		}
	
		$action = $wgRequest->getVal('action');
	
		// just for talk pages
		if (!$title->isTalkPage() || $action != '' || $wgRequest->getVal('diff', null) != null) 
			return;
	
	    if (!$title->userCanEdit()) {
			echo  wfMsg('postcomment_discussionprotected');
			return;
		}
	
		$sk = $wgUser->getSkin();
	
	   	$user_str = "";
	    if ($wgUser->getID() == 0) {
	        $user_str = wfMsg('postcomment_notloggedin');
	    } else {
			$link = $sk->makeLinkObj($wgUser->getUserPage(), $wgUser->getName());
	        $user_str = wfMsg('postcomment_youareloggedinas', $link);
	    }
	
	    $msg = wfMsg('postcomment_addcommentdiscussionpage');
		$previewPage = Title::makeTitle(NS_SPECIAL, "PostcommentPreview");
		$me = Title::makeTitle(NS_SPECIAL, "Postcomment");
	
		$pc = Title::newFromText("Postcomment", NS_SPECIAL);
	    if ($title->getNamespace() == NS_USER_TALK)
	        $msg = wfMsg('postcomment_leavemessagefor',$title->getText());
		
		$id = rand(0, 10000);
		$newpage = $wgTitle->getArticleId() == 0 ? "true" : "false";

		$fc = null;
		$pass_captcha = true;
		if ($wgUser->getID()== 0) {
			 $fc = new FancyCaptcha();
		}
	   $result = "<div id='postcomment_newmsg_$id'></div>
				<br/><br/>
			<script type='text/javascript'>
				var gPreviewText = \"" . wfMsg('postcomment_generatingpreview') . "\";
				var gPreviewURL = \"{$previewPage->getFullURL()}\";
				var gPostURL = \"{$me->getFullURL()}\";
				var gPreviewMsg = \"" . wfMsg('postcomment_previewmessage') . "\";
				var gNewpage = {$newpage};
			</script>
			<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/Postcomment/postcomment.js?') . WH_SITEREV . "'></script>
			<div id='postcomment_progress_$id' style='display:none;'><center><img src='" . wfGetPad('/extensions/Postcomment/upload.gif') . "' alt='Sending...'/></center></div>
			";


		//XXCHANGED Vu added for google analytics tracking gat
		if ($wgTitle->getNamespace() == NS_TALK) {

			$result .= "<form id=\"gatDiscussionPost\" name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);'>" ;

		} else if ($wgTitle->getNamespace() == NS_USER_TALK) {

			$result .= "<form id=\"gatTalkPost\" name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);'>" ;

		} else {

			$result .= "<form name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);'>" ;

		}

		$result .= "
				<input name=\"target\" type=\"hidden\" value=\"" . htmlspecialchars($title->getPrefixedDBkey()) . "\"/>
		<table>
	        <tr><td valign=\"top\">
	        <a name=\"postcomment\"></a>
	        <a name=\"post\"></a>
	        <b>$msg:</b><br/><br/></td></tr>
	        <tr>
			<td><textarea style='width:500px' tabindex='3' rows='15' cols='100' name=\"comment_text_$id\" id=\"comment_text_$id\"></textarea></td></tr>
	        <tr><td>
	        <input tabindex='4' type='submit' value=\"".wfMsg('postcomment_post')."\" id='postcommentbutton_{$id}' {$postbtn} />
	        <input tabindex='5' type='button' onclick='postcommentPreview(\"$id\");' value=\"".wfMsg('postcomment_preview')."\" {$prevbtn} />
	        </td>
	        
	        </tr>
			<tr>
	        <td>
	        <small>
	        $user_str
			"  . ($pass_captcha ? "" : "<br><br/><font color='red'>Sorry, that phrase was incorrect, try again.</font><br/><br/>") . "
			" . ($fc == null ? "" : $fc->getForm('') ) . "
			</small></td></tr>
	        </table>
	        </form>
			<div id='postcomment_preview_$id' style='border: 1px solid #eee;'>
	
			</div>
			";
		if ($return_result)
			return $result;
		else
			echo $result;
	}
	
	function execute ( $par ) {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgMemc, $wgDBname;
		global $wgRequest, $wgSitename, $wgLanguageCode;
		global $wgFeedClasses, $wgFilterCallback, $wgWhitelistEdit, $wgParser;
		
		wfLoadExtensionMessages('Postcomment');
	
		
		$wgOut->setRobotpolicy( "noindex,nofollow" );
		$fname = "wfSpecialPostcomment";
		
		//echo "topic: " . $wgRequest->getVal("topic_name") . "<BR>";
		//echo "title: " . $wgRequest->getVal("title") . "<BR>";
		//echo "comment: " . $wgRequest->getVal("comment_text") . "<BR>";
		//echo "new_topic id " . $wgRequest->getVal("new_topic") . "<BR>";	
		$t = Title::newFromDBKey($wgRequest->getVal("target"));
		$update = true;
		
	    if (!$t->userCanEdit()) {
	        return;
	    }
	
	    if ( !$wgUser->isAllowed('edit') ) {
	        return; 
	    }   
	
		if ($t == null) {
			$wgOut->errorPage('postcomment', 'postcomment_invalidrequest');
			return;
		} 
		
		if ($t->getArticleID() <= 0) 
			$update = false;
			
		$article = new Article($t);
		
		$user = $wgUser->getName();
		$real_name = User::whoIsReal($wgUser->getID());
		if ($real_name == "") {
			$real_name = $user;
		}
		$dateStr = $wgLang->timeanddate(wfTimestampNow());
	
		$comment = $wgRequest->getVal("comment_text");
		foreach ($wgRequest->getValues() as $key=>$value) {
			if (strpos($key, "comment_text") === 0) {
				$comment = $value;
				break;
			}
		}
		$topic = $wgRequest->getVal("topic_name");
		
		//echo "$dateStr<br/>";
	
		// remove leading space, tends to be a problem with a lot of talk page comments as it breaks the 
		// HTML on the page
		$comment = preg_replace('/\n[ ]*/', "\n", trim($comment));
	
		$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);
	
		if ($wgRequest->getVal('fromajax') == 'true') {
			$wgOut->setArticleBodyOnly(true);
		}	
		$text = "";
		
		if ($update) {
			$r = Revision::newFromTitle($t);
			$text = $r->getText();
		}
		
		$text .= "\n\n$formattedComment\n\n";
		$wgOut->setStatusCode(500);
		
		
		//echo "updating with text:<br/> $text";
		//exit;
		$tmp = "";
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage(); 
			return;
		}
		if ( !$wgUser->getID() && $wgWhitelistEdit ) {
			$this->userNotLoggedInPage();
			return;
		}
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}
	        
	    if ($target == "Spam-Blacklist") {
	   		$wgOut->readOnlyPage();
	        return;                
	    }
	
		if ( $wgUser->pingLimiter() ) {
			$wgOut->rateLimited();
			return;
		}
				
		if ( $wgFilterCallback && $wgFilterCallback( $t, $text, $tmp) ) {
			# Error messages or other handling should be performed by the filter function
			return;
		}
	       
		$matches = array();
		$preg = "/http:\/\/[^] \n'\">]*/";
		$mod = str_ireplace('http://www.wikihow.com', '', $comment);
		preg_match_all($preg, $mod, $matches);
	
		if (sizeof($matches[0] ) > 2) {
			$wgOut->errorPage("postcomment", "postcomment_urls_limit");
			return;
		}
	
		 if (trim(strip_tags($comment)) == ""  ) {
	           $wgOut->errorpage( "postcomment", "postcomment_nopostingtoadd");
	           return;
	        }
	
		if ( !$t->userCanEdit()) {
	       $wgOut->errorpage( "postcomment", "postcomment_discussionprotected");
		   return;   
		}
		
		$watch = false;
		if ($wgUser->getID() > 0) 
		   $watch = $wgUser->isWatched($t);
		
		$fc = new FancyCaptcha(); 
		$pass_captcha   = $fc->passCaptcha(); 
	
		if(!$pass_captcha && $wgUser->getID() == 0) {
			$wgOut->addHTML("Sorry, please enter the correct word. Click <a onclick='window.location.reload(true);'>here</a> to get a new one.<br/><br/>");
			return;
		}	
		if ($update) {
			//echo "trying to update article";
			$article->updateArticle($text, "", true, $watch);	
		} else {
			//echo "inserting new article";
			$article->insertNewArticle($text, $comment, true, $watch, false, false, true);
		}
	

		//XX Vu added to notify users of usertalk updates
		if ( $t->getNamespace() == NS_USER_TALK )
			AuthorEmailNotification::notifyUserTalk($t->getArticleID(), $wgUser->getID() ,$comment);

		if ($wgRequest->getVal('fromajax') == 'true') {
			$wgOut->redirect('');
			$wgTitle = $t;
	
			$formattedComment = $wgParser->preSaveTransform($formattedComment, $t, $wgUser, new ParserOptions() );
			$wgOut->addHTML($wgOut->parse("\n" . $formattedComment));
		}
		
		$wgOut->setStatusCode(200);	
	}
}
class PostcommentPreview extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'PostcommentPreview' );
    }


    function execute ($par) {	
		global $wgOut, $wgRequest, $wgUser, $wgLang, $wgTitle;
		global $wgParser;
	
		wfLoadExtensionMessages('Postcomment');
	    $user = $wgUser->getName();
		$dateStr = $wgLang->timeanddate(wfTimestampNow());
	    $real_name = User::whoIsReal($wgUser->getID());
	    if ($real_name == "") {
	        $real_name = $user;
	    }
	    $comment = $wgRequest->getVal("comment");
	    $comment = preg_replace('/\n[ ]*/', "\n", trim($comment));
	 	$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, $user, $real_name, $comment);
		$formattedComment = $wgParser->preSaveTransform($formattedComment, $wgTitle, $wgUser, new ParserOptions() );
		$result = $wgOut->parse($formattedComment);
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML($result); 
		return;
	
	}
}

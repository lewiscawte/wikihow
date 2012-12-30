<?php

class ThankAuthors extends SpecialPage {	

    function __construct() {
        SpecialPage::SpecialPage( 'ThankAuthors' );
    }
	
	function execute( $par )
	{
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgMemc, $wgDBname, $wgScriptPath;
		global $wgRequest, $wgSitename, $wgLanguageCode;
		global $wgScript, $wgFilterCallback;
		$fname = "wfSpecialThankAuthors";

		$this->setHeaders();

		require_once('EditPageWrapper.php');
		require_once('EditPage.php');
	
		//$action = "Special:ThankAuthors";
	
	
		//$wgOut->setArticleBodyOnly(true);
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if ($target == null || $target == "") {
			$wgOut->addHTML("No target specified. In order to thank a group of authors, a page must be provided. POST:" . print_r($_GET, true));
			return;
		}
		
		$title = Title::newFromDBKey($target);
		$me = Title::makeTitle(NS_SPECIAL, "ThankAuthors");
	
		
		if (!$wgRequest->getVal('token', null)) {			
			$sk = $wgUser->getSkin();
			$talk_page = $title->getTalkPage();
	
	
			$token = $this->getToken1();
			$thanks_msg = wfMsg('thank-you-kudos', $title->getFullURL(), wfMsg('howto', $title->getText()));
			$thanks_msg = str_replace("\n", "",  $thanks_msg);
			$thanks_msg = str_replace("\"", "&quote", $thanks_msg);
			
			// add the form HTML
			$wgOut->addHTML ( "
				<script type='text/javascript'>
					var requester;
					function loadResults() {
						if (requester.readyState == 4) {
	                        if (requester.status == 200) {
							}
						}
					}
					function submitThanks () {
						var strResult;
						try {
	                        requester = new XMLHttpRequest();
						} catch (error) {
	                        try {
	                                requester = new ActiveXObject('Microsoft.XMLHTTP');
	                        } catch (error) {
	                                return false;
	                        }
						}
						var url = '" . $me->getFullURL() . "?token=' + document.getElementById('token').value + '&target=' + document.getElementById('target').value + '&details=' + document.getElementById('details').value;
						var form = document.getElementById('thanks_form');
						form.innerHTML = \"$thanks_msg\";					
						requester.open('GET',url,true);
						requester.send('');
						requester.onreadystatechange = loadResults;
						return true;
	
					}
				</script>
				
				<div id=\"thanks_form\">");
				$wgOut->addWikiText( wfMsg('enjoyed-reading-article',
					$title->getFullText(),
					$talk_page->getFullText() )
					);
					
				$wgOut->addHTML("<input id=\"target\" type=\"hidden\" name=\"target\" value=\"$target\"/>
				<input id=\"token\" type=\"hidden\" name=\"$token\" value=\"$token\"/>
				");
			
			
				$wgOut->addHTML ("
				<TEXTAREA style='width:400px;' id=\"details\" rows=\"5\" cols=\"100\" name=\"details\"></TEXTAREA><br/>
					<button onclick='submitThanks();'>" . wfMsg('submit') . "</button>
				</div>
			");
		} else {
			// this is a post, accept the POST data and create the Request article
				
			$wgOut->setArticleBodyOnly(true);
		
			$article = new Article($title);
			// stupid bug that doesn't load the last edit unless you ask it to
			$article->loadLastEdit(); 
			$contributors = $article->getContributors(0, 0, true);
			$user = $wgUser->getName();
			$real_name = User::whoIsReal($wgUser->getID());
			if ($real_name == "") {
				$real_name = $user;
			}
			$dateStr = $wgLang->timeanddate(wfTimestampNow());
			$comment = $wgRequest->getVal("details");
			$text = $title->getFullText();
	
	wfDebug("STA: got text...");
	
			// filter out links
			//$preg = "/http:\/\/[^] \n'\"]*/";
			$preg = "/[^\s]*\.[a-z][a-z][a-z]?[a-z]?/i";
			$matches = array();
			if (preg_match($preg, $comment, $matches) > 0 ) {		
				$wgOut->addHTML(wfMsg('no_urls_in_kudos', $matches[0] )  );
				return;
			}
		
			$comment = strip_tags($comment); 
			
			$formattedComment = "<!-- start entry --->
		<div id=\"discussion_entry\"><table width=\"100%\">
		   <tr><td width=\"50%\" valign=\"top\" class=\"discussion_entry_user\">
		[[User:$user|$real_name]] said about [[$text]]:
	</td><td align=\"right\" width=\"50%\" class=\"discussion_entry_date\">On $dateStr<br/>
		</td></tr><tr>
	<td colspan=2 class=\"discussion_entry_comment\">
		$comment</td></tr></table></div>
		<!-- end entry -->
		
		";
	
	wfDebug("STA: comment $formattedComment\n");
	wfDebug("STA: Checking blocks...");
	
			$tmp = "";
			if ( $wgUser->isBlocked() ) {
				$this->blockedIPpage();
				return;
			}
			if ( !$wgUser->getID() && $wgWhitelistEdit ) {
				$this->userNotLoggedInPage();
				return;
			}
	                
	                if ($target == "Spam-Blacklist") {
	                        $wgOut->readOnlyPage();
	                        return;
	                }
			wfDebug("STA: checking read only\n");
			if ( wfReadOnly() ) {
				$wgOut->readOnlyPage();
				return;
			}
			wfDebug("STA: checking rate limiter\n");
			if ( $wgUser->pingLimiter('userkudos') ) {
				$wgOut->rateLimited();
				return;
			}
					
			wfDebug("STA: checking blacklist\n");
			
			if ( $wgFilterCallback && $wgFilterCallback( $title, $comment, "") ) {
				# Error messages or other handling should be performed by the filter function
				return;
			}
			
			wfDebug("STA: checking tokens\n");
			
			$usertoken = $wgRequest->getVal('token');
			$token1 = $this->getToken1();
			$token2 = $this->getToken2();
			if ($usertoken != $token1 && $usertoken != $token2) {
				wfDebug ("STA: User kudos token doesn't match user: $usertoken token1: $token1 token2: $token2");
				return;
			}
			wfDebug("STA: going through contributors\n");
	
			foreach ($contributors as $c) {		    
			    $id = $c[0];
			    $u = $c[1];
				wfDebug("STA: going through contributors $u $id\n");
			    if ($id == "0") continue; // forget the anon users.			
				$t = Title::newFromText("User_kudos:" . $u);
				$a = new Article($t);
				$update = $t->getArticleID() > 0;
				$text = "";
				if ($update) 
					$text = $a->getContent(true);
					$text .= "\n\n" . $formattedComment;
			        if ( $wgFilterCallback && $wgFilterCallback( $t, $text, $text) ) {
	                        # Error messages or other handling should be performed by the filter function
	                        return;
	                	}
				if ($update) {
					$a->updateArticle($text, "", true, false, false, '', false);	
				} else {
				//	$a->insertNewArticle($text, "", true, false, false, false);
			      	$a->insertNewArticle($text, "", true, false, false, false, false);
				}
			}
	
			/*				
			$wgOut->addHTML("<b>Thank you for your appreciation.</b>
					<br/><br/>
					Your message has been posted on each authors <a href=\"$wgScriptPath/WikiHow:Kudos\">Kudos</a> page.
					Authors will periodically check their page and will see your thanks posted. Your appreciation matters
					and will keep our authors inspired to keep writing great articles.
	 				<br/><br/>
					Click here to return to <a href=\"" . 
						$title->getFullURL() . "\">" . $title->getFullText() . "</a>
			");
			*/
			wfDebug("STA: done\n");
			$wgOut->addHTML("Done.");
			$wgOut->redirect('');
		}
		
	}
	
	function getToken1() {
	        global $wgRequest, $wgUser;
			$d = substr(wfTimestampNow(), 0, 10);
	        $s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] . $_SERVER['REMOTE_ADDR'] . $wgRequest->getVal("target")  . $d;
		 	wfDebug("STA: generating token 1 ($s) " . md5($s) . "\n");
	        return md5($s);
	}
	                
	function getToken2() {
	        global $wgRequest, $wgUser;
			$d = substr( wfTimestamp( TS_MW, time() - 3600 ), 0, 10);
	        $s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] . $_SERVER['REMOTE_ADDR'] . $wgRequest->getVal("target")  . $d;
		 	wfDebug("STA: generating token 2 ($s) " . md5($s) . "\n");
	        return md5($s);
	} 
}
	

<?

class MyTwitter extends SpecialPage {
	
    function __construct() {
        SpecialPage::SpecialPage( 'MyTwitter' );
    }

	function tweet($msg, $user) {
		// set up the API and post the message
		$dbr = wfGetDB(DB_SLAVE);
		$account = $dbr->selectRow('twitterfeedusers', array('*'), array('tfu_user'=>$user->getID())); 
		$callback = $wgServer . '/Special:TwitterAccounts/'. urlencode($user->getName());
		require_once('twitterapi.php');
		$twitter = new Twitter(WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SEC);
		$twitter->setOAuthToken($account->tfu_token);
		$twitter->setOAuthTokenSecret($account->tfu_secret);
		$result = $twitter->statusesUpdate($msg); 
		$dbw = wfGetDB(DB_MASTER); 
		$dbw->insert('twitterfeedlog', array('tfl_user'=>$user->getID(), 'tfl_user_text'=>$user->getName(),
				'tfl_message' => $msg, 'tfl_timestamp'=>wfTimestampNow()));
	}


	function hasBadTemplate($text) {
		$templates = split("\n", wfMsg('twitterfeed_templates_to_ignore'));
		foreach ($templates as $t) {
			$t = trim($t); 
			if ($t == "") {
				continue;
			}
			if (stripos($text, "{{{$t}") !== false) {
				return true;
			}
		}
		return false;
	}

	function tweetNewArticle($t, $user) {
		$msg = TwitterAccounts::getUpdateMessage($t, "Created ");
		self::tweet($msg, $user);
	}

	function tweetNAB($t, $user) {
		$msg = TwitterAccounts::getUpdateMessage($t, "Boosted ");
		self::tweet($msg, $user);
	}

	function tweetUpload($t, $user) {
		$msg = TwitterAccounts::getUpdateMessage($t, "Uploaded ", true);
		self::tweet($msg, $user);
	}
	
	function tweetEditFinder($t, $user) {
		$msg = TwitterAccounts::getUpdateMessage($t, "Repaired ");
		self::tweet($msg, $user);
	}

	function tweetQuickEdit($t, $user) {
		$msg = TwitterAccounts::getUpdateMessage($t, "Quick Edited ");
		self::tweet($msg, $user);
	}

	function userHasOption($user, $option) {
		$dbr = wfGetDB(DB_SLAVE);
		$account = $dbr->selectRow('twitterfeedusers', array('*'), array('tfu_user'=>$user->getID())); 
		if ($account) {
			$options = split(",", $account->tfu_settings);
			if (in_array($option, $options)) {
				return true;
			}
		}
		return false;
	}


	function execute ($par) {	
		global $wgOut, $wgRequest, $wgUser, $wgServer;

		// this only for staff for now, can remove later
        if (!in_array('staff', $wgUser->getGroups())) {
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }

		// only for logged in users
		if ($wgUser->getID() == 0) {
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
		}

		// send them on their way to verify access to the account
		if ($wgRequest->getVal("link")) {
			$callback = $wgServer . '/Special:MyTwitter/'. urlencode($wgUser->getName());
			require_once('twitterapi.php');
			$twitter = new Twitter(WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SEC);
			$twitter->oAuthRequestToken($callback);
			$twitter->oAuthAuthorize();
			return;
		}
		
		wfLoadExtensionMessages("MyTwitter");
		$this->setHeaders();

		if ($wgRequest->getVal("unlink")) {
			$dbw = wfGetDB(DB_MASTER); 
			$dbw->delete('twitterfeedusers', array('tfu_user'=>$wgUser->getID()));
		}

		// process the call back and update the appropriate tokens
		if ($wgRequest->getVal('oauth_token')) {
			require_once('twitterapi.php');
		  	$twitter = new Twitter(WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SEC);
		  	$twitter->oAuthRequestToken($callback);
			$response = $twitter->oAuthAccessToken($_GET['oauth_token'], $_GET['oauth_verifier']);
			$dbw = wfGetDB(DB_MASTER); 
			$dbw->insert('twitterfeedusers', 
				array('tfu_token'=>$response['oauth_token'],
					'tfu_secret'=>$response['oauth_token_secret'],
					'tfu_user'=>$wgUser->getID(), 
					'tfu_user_text'=>$wgUser->getName()
				) 
				);
			$wgOut->addHTML("<b>You have successfully linked your Twitter account. </b><br/><br/>");
		}

		$dbr = wfGetDB(DB_SLAVE); 
		$account = $dbr->selectRow('twitterfeedusers', array('*'), array('tfu_user'=>$wgUser->getID())); 
		if (!$account) {
			$wgOut->addHTML("You have yet to link your twitter account, click <a href='/Special:MyTwitter?link=1'>here</a> to do so.");	
			return;
		}
	
		$available = array("createpage", "nab", "upload", "editfinder", "quickedit"); 
		$options = split(",", $account->tfu_settings); 
		if ($wgRequest->wasPosted()) {
			$options = array(); 
			// save new settings
			foreach ($wgRequest->getValues() as $key=>$val) {
				if (in_array($key, $available) && $val = "on") {
					$options[] = $key;
				}
			}
			$dbw = wfGetDB(DB_MASTER); 
			$dbw->update('twitterfeedusers', array('tfu_settings'=>implode(',', $options)), array('tfu_user'=>$wgUser->getID()));
		} 
		// process the users settings
		$wgOut->addHTML("Please update my twitter status when I :<br/><br/> <form action='/Special:MyTwitter' method='POST'>");
		foreach ($available as $a) {
			$c = "";
			if (in_array($a, $options)) {
				$c = " CHECKED ";
			}
			$wgOut->addHTML("<input type='checkbox' {$c} name='{$a}'> " . wfMsg('mytwitter_' . $a) . "<br/><br/>");
		}
		$wgOut->addHTML('<input type="submit" style="float: left; background-position: 0px 0pt;" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" class="button button100 submit_button" onclick="needToConfirm = false" title="Save your changes [alt-s] [ctrl-s]" accesskey="s" value="Save" tabindex="15" name="wpSave" id="wpSave">');
		$wgOut->addHTML("</form>");
		$wgOut->addHTML("<div style='font-size: 0.8em; margin-top: 100px;'>Or, on second thought, I don't want my Twitter account linked anymore, click <a href='/Special:MyTwitter?unlink=1'>here</a></div>");
	}

}


class TwitterAccounts extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'TwitterAccounts' );
    }

	function getUpdateMessage($t, $prefix = '', $nohowto = false) {
		// the "How to" version of the message, can be overridden
		$howto = wfMsg('howto', $t->getText());
		if ($nohowto) {
			$howto = $t->getText();
		}
		$msg = $prefix . $howto . " " . $t->getFullURL();
		// need to shorted this
		$url = "http://api.bitly.com/v3/shorten?login=" . WH_BITLY_USERNAME 
			. "&apiKey=" . WH_BITLY_API_KEY
			. "&longUrl=" . urlencode($t->getFullURL());
		$results = json_decode(file_get_contents($url));
		if ($results) {
			$msg = $prefix . $howto . " " . $results->data->url;
		}
		if (strlen($msg) > 140) {
			// still? 
			$maxlength = 140 - strlen($results->data->url) - 3;
			$msg = substr($prefix . $t->getText(), 0, $maxlength) . ".. " . $results->data->url;
		}
		return $msg;
	}


   	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgServer;

		// this only for staff
        if (!in_array('staff', $wgUser->getGroups())) {
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }

        $target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		// set up a new account if we have been given one
		if ($wgRequest->wasPosted()) {
			$cat = Title::makeTitle(NS_CATEGORY, $wgRequest->getVal('category')); 
			/*
			if (!$cat->getArticleID()) {
				$wgOut->addHTML("Oops, this category {$wgRequest->getVal('category')} doesn't seem to exist."); 
				return;
			}
			*/

			// set up the link between the twitter account and the category
			$username = $wgRequest->getVal('username'); 
			$password = $wgRequest->getVal('password'); 
			$dbw = wfGetDB(DB_MASTER); 
			$opts = array('tfc_username'=>$username, 'tfc_category'=>$cat->getDBKey());
			$count = $dbw->selectField('twitterfeedcatgories', 'count(*)', $opts); 
			if ($count == 0) {
				// there can only be 1 pair like this
				$dbw->insert('twitterfeedcatgories', $opts);
			}

			// do we already have auth tokens set up for this twitter account? 
			$row = $dbw->selectRow('twitterfeedaccounts', array('*'), array('tws_username'=>$username));
			if (!$row->tws_token) {
				// insert what we know, create the initial account
				if (!$row) {
					$dbw->insert('twitterfeedaccounts',  array('tws_username'=>$username, 'tws_password'=>$password)); 
				}

				// then send them on their way to authenticate their tokens, they'll be back, don't worry
				$callback = $wgServer . '/Special:TwitterAccounts/'. urlencode($username);
				require_once('twitterapi.php');
			  	$twitter = new Twitter(WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SEC);
			   	$twitter->oAuthRequestToken($callback);
				$twitter->oAuthAuthorize();
				return;
			} 
		}

		// process the call back and update the appropriate tokens
		if ($target && $wgRequest->getVal('oauth_token')) {
			require_once('twitterapi.php');
		  	$twitter = new Twitter(WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SEC);
		  	$twitter->oAuthRequestToken($callback);
			$response = $twitter->oAuthAccessToken($_GET['oauth_token'], $_GET['oauth_verifier']);
			$dbw = wfGetDB(DB_MASTER); 
			$dbw->update('twitterfeedaccounts', 
				array('tws_token'=>$response['oauth_token'],
					'tws_verifier'=>$wgRequest->getVal('oauth_verifier'),
					'tws_secret'=>$response['oauth_token_secret'],
				), 
				array('tws_username'=>$target)
				);
			$wgOut->addHTML("<b>Tokens updated for $target</b><br/><br/>");
		}

		// delete any relationships that were requeted
		if ($wgRequest->getVal('eaction') == 'del') {
			 $username = $wgRequest->getVal('username');
			 $cat = Title::makeTitle(NS_CATEGORY, $wgRequest->getVal('category'));
			 $dbw = wfGetDB(DB_MASTER);
			 $opts = array('tfc_username'=>$username, 'tfc_category'=>$cat->getDBKey());
			 $dbw->delete('twitterfeedcatgories', $opts);
			 $wgOut->addHTML("<b>New articles will no longer be tweeted to {$username} for the category {$cat->getText()}</b><br/><br/>");
		}

		// show all of the active accounts
		$dbr = wfGetDB(DB_MASTER); 
		$res = $dbr->select(array('twitterfeedaccounts','twitterfeedcatgories'), array('*'), array('tfc_username=tws_username'));
		$wgOut->addScript("<script type='text/javascript' src='/extensions/wikihow/twitteraccounts.js'></script>");
		$wgOut->addHTML("<table style='margin-left: auto; margin-right: auto;' width='70%'>
				<tr><td>Username</td><td>Password</td><td>Category</td><td style='text-align:right;'>Delete</td></tr>");
		$index = 0;
		while ($row = $dbr->fetchObject($res)) {
			$cat = Title::makeTitle(NS_CATEGORY, $row->tfc_category); 
			$wgOut->addHTML("<tr><td><a href='http://twitter.com/{$row->tws_username}' target='new'>{$row->tws_username}</a></td>");
			$wgOut->addHTML("<td><span id='pass_$index'>******* 
					<a href='#' onclick='return showPass($index, \"{$row->tws_password}\");'>Show</a></span></td>");
			$wgOut->addHTML("<td><a href='{$cat->getFullURL()}' target='new'>{$cat->getText()}</td>");
			$wgOut->addHTML("<td style='text-align:right;'><a href='#' onclick='twtDelete(\"{$cat->getText()}\", \"{$row->tws_username}\");'>x</td></tr>");
			$index++;
		}
		$wgOut->addHTML("</table>"); 

		// set up a form to process new accounts
		$wgOut->addHTML(<<<END
			<div style='border: 2px solid #eee; padding: 15px; margin-top: 50px;'>
			<b>Add new account:</b><br/>
			<form action='/Special:TwitterAccounts' method='POST'>
			<table width='100%'>
				<tr><td>Twitter Username:</td><td><input type='text' name='username'></td></tr>
				<tr><td>Twitter Password:</td><td><input type='password' name='password'/></td></tr>
				<tr><td>Category:</td><td><input type='text' name='category'> 
						<!--<a href='#' onclick='return showCats();'>Search</a>-->
					</td></tr>
			</table><br/>
				<i>Notes: <ul><li>The twitter account must already exist, this page does not create twitter accounts.</li>
				<li> Category names are case sensitive.</li>
				<li>You may have to authenticate and allow access for the wikihowfeeds application.</li>
				<li>Be sure you don't accidentally use your own twitter account.</li>
				</i><br/><br/>
				<input type='submit' value='Add Account'/>
			</form>	
			</div>
END
);
	}

}


<?

class AdminRemoveAvatar extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('AdminRemoveAvatar');
	}

	/**
	 * Pull a user account (by username) and remove the avatar file associated.
	 *
	 * @param $username string, the username
	 * @return true or false (true iff action was successful)
	 */
	function removeAvatar($username) {
		global $IP;
		$user = User::newFromName($username);
		$userID = $user->getID();
		if ($userID > 0) {
			// TODO1: log this action somewhere, along with the user who did it
			// TODO2: Purge /User:$username page from Varnish

			$path = "$IP/images/avatarOut/$userID.jpg";
			$ret = false;
			if (file_exists($path)) {
				$ret = @unlink($path);
			}

			$gpath = "$IP/images/avatarOut/$userID-*.jpg";
			$files = glob($gpath);
			if (!$files) {
				return $ret;
			}
			$ret = true;
			foreach ($files as $file) {
				if (!@unlink($file)) {
					$ret = false;
				}
			}
			return $ret;
		} else {
			return false;
		}
	}

	/**
	 * Execute special page, but only for staff group members
	 */
	function execute() {
		global $wgRequest, $wgOut, $wgUser, $wgLang, $wgSquidMaxage;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$username = $wgRequest->getVal('username', '');
			$wgOut->setArticleBodyOnly(true);
			$success = $this->removeAvatar($username);
			if ($success) {
				$url = 'http://www.wikihow.com/User:' . preg_replace('@ @', '-', $username);
				$cacheHours = round(1.0 * $wgSquidMaxage / (60 * 60), 1);
				$tmpl = <<<EOHTML
<p>Avatar for '$username' removed from user page.  This change will be visible to non-cookied users within $cacheHours hours and will be visible to cookied users immediately.</p>
<p>See results: <a href='$url'>$url</a></p>
EOHTML;
				$result = array('result' => $tmpl);
			} else {
				$result = array('result' => "error: either user '$username' not found or '$username' didn't have an avatar");
			}
			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle('Admin - Remove Avatar - wikiHow');

$tmpl = <<<EOHTML
<form method="post" action="/Special:AdminRemoveAvatar">
<h4>Enter username of avatar to remove</h4>
<br/>
<input id="admin-username" type="text" size="40" />
<button id="admin-go" disabled="disabled">reset</button><br/>
<br/>
<div id="admin-result"></div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#admin-go')
			.attr('disabled', '')
			.click(function () {
				$('#admin-result').html('loading ...');
				$.post('/Special:AdminRemoveAvatar',
					{ 'username': $('#admin-username').val() },
					function(data) {
						$('#admin-result').html(data['result']);
						$('#admin-username').focus();
					},
					'json');
				return false;
			});
		$('#admin-username')
			.focus()
			.keypress(function (evt) {
				if (evt.which == 13) { // if user hits 'enter' key
					$('#admin-go').click();
					return false;
				}
			});
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}

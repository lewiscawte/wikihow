<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/maintenance/WikiPhoto.class.php");

class AdminLookupPages extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('AdminLookupPages');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute() {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		//$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() /*|| !in_array('staff', $userGroups)*/) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$dbr = wfGetDB(DB_SLAVE);

			$pageList = $wgRequest->getVal('pages-list', '');
			$wgOut->setArticleBodyOnly(true);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			foreach ($pageList as $url) {
				$url = trim($url);
				if (!empty($url)) {
					$id = WikiPhoto::getArticleID($url);
					$images = '';
					if (!empty($id)) {
						$hasNoImages = WikiPhoto::articleBodyHasNoImages($dbr, $id);
						$images = $hasNoImages ? 'no' : 'yes';
					}
					$urls[] = array('url' => $url, 'id' => $id, 'images' => $images);
				}
			}

			$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
			$html .= '<table class="tres"><tr><th width="450px">URL</th><th>ID</th><th>Has steps images?</th></tr>';
			foreach ($urls as $row) {
				$html .= "<tr><td><a href='{$row['url']}'>{$row['url']}</a></td><td>{$row['id']}</td><td>{$row['images']}</td></tr>";
			}
			$html .= '</table>';

			$result = array('result' => $html);
			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle('Admin - Lookup Pages - wikiHow');

$tmpl = <<<EOHTML
<form method="post" action="/Special:AdminLookupPages">
<h4>Enter a list of URLs such as <code>http://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.</h4>
<br/>
<textarea id="pages-list" type="text" rows="10" cols="70"></textarea>
<button id="pages-go" disabled="disabled">process</button><br/>
<br/>
<div id="pages-result">
</div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#pages-go')
			.attr('disabled', '')
			.click(function () {
				$('#pages-result').html('loading ...');
				$.post('/Special:AdminLookupPages',
					{ 'pages-list': $('#pages-list').val() },
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});
		$('#pages-list')
			.focus();
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}

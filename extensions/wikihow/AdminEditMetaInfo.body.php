<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/maintenance/WikiPhoto.class.php");
require_once("$IP/extensions/wikihow/Wikitext.class.php");
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");

class AdminEditMetaInfo extends UnlistedSpecialPage {

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('AdminEditMetaInfo');
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = array();
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$title = WikiPhoto::getArticleTitle($url);
				if ($title && $title->exists()) {
					$id = $title->getArticleId();
					$meta = new ArticleMetaInfo($title);
					$desc = $meta->getDescription();
					$urls[] = array(
						'url' => $url,
						'title' => $title,
						'id' => $id,
						'desc' => $desc,
					);
				}
			}
		}
		return $urls;
	}

	/**
	 * Load the meta description for a page.
	 *
	 * @param int $page page ID
	 * @return array ($desc, $defaultDesc, $wasEdited)
	 */
	private static function loadDesc($page) {
		$title = Title::newFromID($page);
		if ($title) {
			$meta = new ArticleMetaInfo($title);
			$style = $meta->getStyle();
			$desc = $meta->getDescription();
			$defaultDesc = $meta->getDescriptionDefaultStyle();
			$wasEdited = ArticleMetaInfo::DESC_STYLE_EDITED == $style;
		} else {
			$desc = '';
			$defaultDesc = '';
			$wasEdited = false;
		}
		return array($desc, $defaultDesc, $wasEdited);
	}

	/**
	 * Save the description for a page as either default or edited.
	 *
	 * @param string $type 'default' or 'edited'
	 * @param int $page page ID
	 * @param string $desc new meta descript if $type is 'edited'
	 * @return string the actual new meta description that was saved (html
	 *   removed, possibly truncated, etc)
	 */
	private static function saveDesc($type, $page, $desc) {
		$title = Title::newFromID($page);
		if (!$title) return '';

		$desc = trim($desc);
		$meta = new ArticleMetaInfo($title);

		if ('default' == $type) {
			$meta->resetMetaData();
		} elseif ('edited' == $type && $desc) {
			$meta->setEditedDescription($desc);
		} else {
			return '';
		}

		return $meta->getDescription();
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute() {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			$dbr = wfGetDB(DB_SLAVE);

			$action = $wgRequest->getVal('action', '');

			if ('list' == $action) {
				$pageList = $wgRequest->getVal('pages-list', '');
				$urls = self::parseURLlist($pageList);
				if (!empty($urls)) {
					$html = self::genURLListTable($urls);
				} else {
					$html = '<i>ERROR: no URLs given</i>';
				}
				$result = array('result' => $html);
			} elseif ('load-desc' == $action) {
				$page = $wgRequest->getInt('page', '');
				list($desc, $defaultDesc, $wasEdited) = self::loadDesc($page);
				$result = array(
					'desc' => $desc,
					'default-desc' => $defaultDesc,
					'was-edited' => $wasEdited,
				);
			} elseif ('save-desc' == $action) {
				$type = $wgRequest->getVal('edit-type', '');
				$page = $wgRequest->getInt('page', '');
				$desc = $wgRequest->getVal('desc', '');
				$msg = 'saved';
				$desc = self::saveDesc($type, $page, $desc);
				$result = array(
					'result' => $msg,
					'desc' => $desc,
				);
			} else {
				$result = array('result' => 'error: no action');
			}
			print json_encode($result);
		} else {
			$wgOut->setHTMLTitle('Admin - Edit Meta Info - wikiHow');

			$tmpl = self::genAdminForm();
			$wgOut->addHTML($tmpl);
		}
	}

	private static function genAdminForm() {
		$basepage = '/Special:AdminEditMetaInfo';
		$html = <<<EOHTML
<style>
	.edit-list li { list-style: none; padding-bottom: 10px; padding-right: 15px; }
</style>

<form id="urls-submit" method="post" action="/Special:AdminEditMetaInfo">
<input type="hidden" name="action" value="list" />
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Edit Meta Description Info
</div>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea>
<button id="pages-go" disabled="disabled" style="padding: 5px;">list</button><br/>
<br/>
<div id="pages-result">
</div>
</form>

<div class="edit-dialog" style="display:none;" title="">
<span class="data" id="edit-page-id"></span>
<ul class="edit-list">
<li>
	<input id="ec-default" class="ec" type="radio" name="editchoice" value="default">
	<label for="ec-default"><b>Default</b></label><br/>
	<div class="edit-default-desc">
	</div>
</li>
<li>
	<input id="ec-edit" class="ec" type="radio" name="editchoice" value="edited">
	<label for="ec-edit"><b>Hand-Edited</b></label><br/>
	<textarea class="edit-edited-desc" rows="10"></textarea>
</li>
</ul>
<button id="edit-save" disabled="disabled" style="padding 10px; margin:5px;">save meta description</button><br><span style="font-style: italic; font-size: 10px; font-weight: normal;">(note that this description will no longer be automatically updated if hand-edited)</span><br/>
</div>

<script>
(function($) {
	$(document).ready(function() {
		$('#pages-go')
			.attr('disabled', '')
			.click(function() {
				var form = $('#urls-submit').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('{$basepage}',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});

		$('#pages-list')
			.focus();

		$('.edit-desc').live('click', function() {
			var id = $(this).attr('id').replace(/^page-/, '');
			var title = $('.title-' + id).first().html();
			$('#edit-page-id').html(id);
			$.post('{$basepage}',
				'action=load-desc&page=' + id,
				function(data) {
					$('.edit-default-desc').html(data['default-desc']);
					$('.edit-edited-desc').val(data['desc']);

					var edited = data['was-edited'] == 1;
					if (!edited) {
						$('#ec-default').click();
						var editDisabled = 'disabled';
					} else {
						$('#ec-edit').click();
						var editDisabled = '';
					}
					$('#edit-save').attr('disabled', 'disabled');
					$('.edit-edited-desc').attr('disabled', editDisabled);

					$('.edit-dialog')
						.attr('title', 'Edit Description &ldquo;' + title + '&rdquo;')
						.dialog({
							width: 500,
							minWidth: 500
						});

				},
				'json');
			return false;
		});

		// when Edit radio button is clicked
		$('#ec-edit').click(function() {
			$('.edit-edited-desc')
				.attr('disabled', '')
				.focus();
		});

		// when Default radio buttons are clicked
		$('#ec-default').click(function () {
			$('.edit-edited-desc').attr('disabled', 'disabled');
		});

		// when any radio button is clicked
		$('.ec').click(function() {
			$('#edit-save').attr('disabled', '');
		});
		$('.edit-edited-desc').bind('keypress keyup keydown', function() {
			$('#edit-save').attr('disabled', '');
		});

		// when any the save description button is pressed
		$('#edit-save').click(function() {
			var editType = $('input:radio[name=editchoice]:checked').val();
			var id = $('#edit-page-id').html();
			var desc = $('.edit-edited-desc').val();
			$('#edit-save').attr('disabled', 'disabled');

			$.post('{$basepage}',
				'action=save-desc&edit-type=' + editType + '&page=' + id + '&desc=' + encodeURIComponent(desc),
				function(data) {
					$('.result-' + id).html(data['result']);
					$('.desc-' + id).html(data['desc']);
					$('.edit-dialog').dialog('close');
				},
				'json');
		});
	});
})(jQuery);
</script>
EOHTML;
		return $html;
	}

	private static function genURLListTable($urls) {
		$html = <<<EOHTML
<style>
	.tres tr:nth-child(even) { background: #ccc; }
	.data { display: none; }
</style>
<table class="tres"><tr><th width="500px">URL and Description</th><th>Action</th><th>Result</th></tr>
EOHTML;
		foreach ($urls as $row) {
			$titleEnc = htmlentities($row['title']);
			$html .= <<<EOHTML
	<tr><td>
		<span class="data title-{$row['id']}">{$titleEnc}</span>
		<p><a href='{$row['url']}' target="_blank">{$row['url']}</a></p>
		<p><i>currently:</i> <span class='desc-{$row['id']}'>{$row['desc']}</span></p>
	</td><td style="text-align: center;">
		<a class="edit-desc" id="page-{$row['id']}" href='#'>edit</a>
	</td><td class='result-{$row['id']}'>
	</td></tr>
EOHTML;
		}
		$html .= <<<EOHTML
</table>
EOHTML;
		return $html;
	}

}

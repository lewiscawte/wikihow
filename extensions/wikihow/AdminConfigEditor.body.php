<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/maintenance/WikiPhoto.class.php");

class AdminConfigEditor extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		global $wgTitle;
		$this->specialPage = $wgTitle->getPartialUrl();
		UnlistedSpecialPage::UnlistedSpecialPage($this->specialPage);
	}

	private static function validateInput($key, $val) {
		$err = '';
		if ('wikiphoto-article-exclude-list' == $key) {
			$list = self::parseURLlist($val);
			foreach ($list as $item) {
				if (!$item['title']) {
					$err .= $item['url'] . "\n";
				}
			}
		}
		return $err;
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
				$title = WikiPhoto::getArticleTitleNoCheck($url);
				$urls[] = array('url' => $url, 'title' => $title);
			}
		}
		return $urls;
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

			$action = $wgRequest->getVal('action');
			$wgOut->setArticleBodyOnly(true);

			if ('load-config' == $action) {
				$key = $wgRequest->getVal('config-key', '');
				$val = ConfigStorage::dbGetConfig($key);
				$result = array('result' => $val);
			} elseif ('save-config' == $action) {
				$key = $wgRequest->getVal('config-key', '');
				$val = $wgRequest->getVal('config-val', '');
				ConfigStorage::dbStoreConfig($key, $val);
				$errors = self::validateInput($key, $val);
				$output = 'saved and checked input<br/><br/>';
				if ($errors) {
					$output .= 'ERRORS:<br/>' . str_replace("\n", "<br/>\n", $errors);
				} else {
					$output .= "no errors.";
				}
				$result = array('result' => $output);
			} else {
				$result = array('error' => 'bad action');
			}

			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle(wfMsg('pagetitle', 'Admin - Generalized Config Editor'));
		$listConfigs = ConfigStorage::dbListConfigKeys();

		$tmpl = self::getGuts($listConfigs);

		$wgOut->addHTML($tmpl);
	}

	function getGuts($configs) {
		ob_start();
?>
		<form method='post' action='/Special:<?= $this->specialPage ?>'>
		<h4>Select with config data you want to edit.</h4>
		<br/>
		<select id='config-key'>
			<option value=''>--</option>
			<? foreach ($configs as $config): ?>
				<option value='<?= $config ?>'><?= $config ?></option>
			<? endforeach; ?>
		</select><br/>
		<br/>
		<textarea id='config-val' type='text' rows='10' cols='70'></textarea>
		<button id='config-save' disabled='disabled'>save</button><br/>
		<br/>
		<div id='admin-result'>
		</div>
		</form>

		<script>
		(function($) {
			$(document).ready(function() {
				$('#config-save')
					.click(function () {
						$('#admin-result').html('saving ...');
						$.post('/Special:<?= $this->specialPage ?>',
							{ 'action': 'save-config',
							  'config-key': $('#config-key').val(),
							  'config-val': $('#config-val').val() },
							function(data) {
								$('#admin-result').html(data['result']);
								$('#config-val').focus();
							},
							'json');
						return false;
					})

				$('#config-val')
					.keydown(function () {
						$('#config-save').prop('disabled', '');
					});

				$('#config-key')
					.change(function () {
						var configKey = $('#config-key').val();
						if (configKey) {
							$('#admin-result').html('loading ...');
							$.post('/Special:<?= $this->specialPage ?>',
								{ 'action': 'load-config',
								  'config-key': configKey },
								function (data) {
									$('#admin-result').html('');
									$('#config-val')
										.val(data['result'])
										.focus();
									$('#config-save').prop('disabled', '');
								},
								'json');
						} else {
							$('#config-val').val('');
						}

						return false;
					});

			});
		})(jQuery);
		</script>
<?
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}

<?

if (!defined('MEDIAWIKI')) die();

class AdminConfigEditor extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		$this->specialPage = 'AdminConfigEditor';
		UnlistedSpecialPage::UnlistedSpecialPage($this->specialPage);
	}

	private static function validateInput($key, $val) {
		$err = '';
		if (('wikiphoto-article-exclude-list' == $key) || ('wikihow-watermark-article-list' == $key)) {
			$list = self::parseURLlist($val);
			foreach ($list as $item) {
				if (!$item['title'] || !$item['title']->isKnown()) {
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
				$title = WikiPhoto::getArticleTitleNoCheck(urldecode($url));
				$urls[] = array('url' => $url, 'title' => $title);
			}
		}
		return $urls;
	}	
	
	private static function translateValues($values,$list_type) {
		$result = '';
		$list = self::parseURLlist($values);

		foreach ($list as $item) {
			$value = '';
			
			if ($item['title']) {
				if ('id' == $list_type) {
					$value = $item['title']->getArticleID();
					if (!empty($value))  $result .= $value . "\r\n";
				}
				elseif ('url' == $list_type) {
					$value = $item['title']->getDBkey();
					if (!empty($value)) {
						$artid = $item['title']->getArticleID();
						$result .= '<tr>
									  <td>http://www.wikihow.com/'.$value.'</td>
									  <td class="x"><a href="#" class="remove_link" id="'.$artid.'">x</a></td>
									</tr>';
						$hidden .= 'http://www.wikihow.com/'. $value ."\r\n";
					}
				}
			}
		}
		
		if ('url' == $list_type) {
			$result = '<table>'.$result.'</table>
					<div id="config_hidden_val">'.$hidden.'</div>';
		}
		return $result;
	}
	
	private function removeLine($key, $id) {
		$err = '';
		if (!empty($id)) {
			$val = ConfigStorage::dbGetConfig($key);
			$pageList = preg_split('@[\r\n]+@', $val);
			
			$id_pos = array_search($id, $pageList);
			if ($id_pos === false) {
				$err = 'Article not found in list';
			}
			else {
				unset($pageList[$id_pos]);
				$val = implode("\r\n",$pageList);
				ConfigStorage::dbStoreConfig($key, $val);
				
				//now let's return the whole thing back
				$result = $this->translateValues($val,'url');
			}
		}
		else {
			$err = 'Bad article id';
		}
		return array('result' => $result, 'error' => $err);
	}


	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		
		if (strtolower($par) == 'url') {
			$style = 'url';
		}
		else {
			$style = '';
		}

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
				
				$style = $wgRequest->getVal('style', '');
				if ($style == 'url') {
					//translate ids to readable urls
					$val = $this->translateValues($val,$style);
				}
				
				$result = array('result' => $val);
			} elseif ('save-config' == $action) {
				$errors = '';
				$key = $wgRequest->getVal('config-key', '');
				$val = $wgRequest->getVal('config-val', '');
				
				$style = $wgRequest->getVal('style', '');
				if ($style == 'url') {
					//add the hidden values to the new ones
					$val = $wgRequest->getVal('hidden-val', '') . $val;
					//validate for errors
					$errors = self::validateInput($key, $val);
					//translate the good urls back to ids for storage purposes
					$val = $this->translateValues($val,'id');
				}
				
				ConfigStorage::dbStoreConfig($key, $val);
				$errors .= self::validateInput($key, $val);
				$output = 'saved and checked input<br/><br/>';
				if ($errors) {
					$output .= 'ERRORS:<br/>' . str_replace("\n", "<br/>\n", $errors);
				} else {
					$output .= "no errors.";
				}
				
				if ($style == 'url') {
					//translate back to urls for updated display
					$val = $this->translateValues($val,'url');
				}
				
				$result = array('result' => $output, 'val' => $val);
			} elseif ('remove-line' == $action) {
				$key = $wgRequest->getVal('config-key', '');
				$id = $wgRequest->getVal('id', '');
				$result = $this->removeLine($key,$id);
				$result = array('result' => $result['result'],'error' => $result['error']);
			} else {
				$result = array('error' => 'bad action');
			}

			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle(wfMsg('pagetitle', 'Admin - Generalized Config Editor'));
		$listConfigs = ConfigStorage::dbListConfigKeys();

		$tmpl = self::getGuts($listConfigs,$style);

		$wgOut->addHTML($tmpl);
	}

	function getGuts($configs,$style) {
		ob_start();
		
		if ($style == 'url') {
			echo '<h1>URL Config Editor</h1>';
			$bURL = true;
		}
		else {
			$bURL = false;
		}
?>
		<style type="text/css">
		table { width: 100%; }
		td {
			background-color: #EEE;
			padding: 5px;
		}
		td.x { text-align: center; }
		#config_hidden_val { display: none; }
		</style>
		<form method='post' action='/Special:<?= $this->specialPage ?>'>
		<h4>Select with config data you want to edit.</h4>
		<br/>
		<select id='config-key'>
			<option value=''>--</option>
		<? if ($bURL) { ?>
			<option value="wikihow-watermark-article-list">wikihow-watermark-article-list</option>
			<option value="wikiphoto-article-exclude-list">wikiphoto-article-exclude-list</option>
		<? } else {
			foreach ($configs as $config): ?>
				<option value='<?= $config ?>'><?= $config ?></option>
			<? endforeach; 
			}
		?>
		</select><br/>
		<br/>
		<?if ($bURL) echo '<b>Add new:</b>';?>
		<textarea id='config-val' type='text' rows='10' cols='70'></textarea>
		<button id='config-save' disabled='disabled'>save</button><br/>
		<br/>
		<div id='admin-result'></div>
		<div id='url-list'></div>
		<input type='hidden' id='display-style' value='<?=$style?>' />
		</form>

		<script>
		//remove a url from the list
		$('body').on('click', 'a.remove_link', function() {
			var rmvid = $(this).attr('id');
			$(this).hide();
			$.post('/Special:<?= $this->specialPage ?>',
				{ 'action': 'remove-line',
				  'config-key': $('#config-key').val(),
				  'id': rmvid },
				function(data) {
					if (data['error'] != '') {
						alert('Error: '+ data['error']);
					}
					$('#url-list').html(data['result']);
				},
				'json');
			return false;
		});
		
		(function($) {
			$(document).ready(function() {
				$('#config-save')
					.click(function () {
						var dispStyle = $('#display-style').val();
						$('#admin-result').html('saving ...');
						$.post('/Special:<?= $this->specialPage ?>',
							{ 'action': 'save-config',
							  'config-key': $('#config-key').val(),
							  'config-val': $('#config-val').val(),
							  'hidden-val': $('#config_hidden_val').html(),
							  'style': dispStyle },
							function(data) {
								$('#admin-result').html(data['result']);
								if (dispStyle == 'url') {
									$('#url-list').html(data['val']);
									$('#config-val').val('');
								}
								else {
									$('#config-val')
										.val(data['val'])
										.focus();
								}
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
						var dispStyle = $('#display-style').val();
						if (configKey) {
							$('#admin-result').html('loading ...');
							$.post('/Special:<?= $this->specialPage ?>',
								{ 'action': 'load-config',
								  'config-key': configKey,
								  'style': dispStyle},
								function (data) {
									$('#admin-result').html('');
									if (dispStyle == 'url') {
										$('#url-list').html(data['result']);
										$('#config-val').val('');
									}
									else {
										$('#config-val')
											.val(data['result'])
											.focus();
									}
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

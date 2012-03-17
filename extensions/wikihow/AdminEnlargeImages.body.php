<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/maintenance/WikiPhoto.class.php");

class AdminEnlargeImages extends UnlistedSpecialPage {

	const DEFAULT_CENTER_PIXELS = 500;
	const DEFAULT_ENLARGE_PIXELS = 300;

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('AdminEnlargeImages');
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
				$urls[] = array('url' => $url, 'title' => $title);
			}
		}
		return $urls;
	}

	/**
	 * Resize the steps images in a list of Title objects.
	 */
	private static function enlargeImagesUrls(&$urls, $recenter, $px, $introPx) {
		$dbr = wfGetDB(DB_SLAVE);
		foreach ($urls as &$url) {
			$err = '';
			$numImages = 0;
			if (!$url['title']) {
				$err = 'Unable to load article';
			} else {
				$stepsText = '';
				$wikitext = Wikitext::getWikitext($dbr, $url['title']);
				if ($wikitext) {
					list($stepsText, $sectionID) = 
						Wikitext::getStepsSection($wikitext, true);
				}

				if (!$stepsText) {
					$err = 'Unable to load wikitext';
				} else {
					list($stepsText, $numImages, $err) = 
						self::enlargeImages($stepsText, $recenter, $px, false);
					if (!$err) {
						$wikitext = Wikitext::replaceStepsSection($wikitext, $sectionID, $stepsText, true);

						$comment = $recenter ?
							'Enlarging and centering Steps photos' :
							'Enlarging Steps photos to ' . $px . ' pixels';

						if ($introPx) {
							$intro = Wikitext::getIntro($wikitext);
							list($intro, $introImages, $err) = 
								self::enlargeImages($intro, '', $introPx, true);
							$numImages += $introImages;
							$wikitext = Wikitext::replaceIntro($wikitext, $intro);
							
							$comment .= '; enlarging intro image';
						}

						if (!$err) {
							$err = Wikitext::saveWikitext($url['title'], $wikitext, $comment);
						}
					}
				}
			}
			$url['err'] = $err;
			$url['images'] = $numImages;
		}
	}

	/**
	 * Enlarge the images in a section of wikitext.  Currently tested with
	 * both intro and steps sections.
	 */
	private static function enlargeImages($text, $recenter, $px, $isIntro) {
		$orientation = $recenter ? 'center' : '';

		if (!$isIntro) {
			$steps = Wikitext::splitSteps($text);
		} else {
			$steps = array($text);
		}

		$numImages = 0;
		foreach ($steps as &$step) {
			if ($isIntro || Wikitext::isStep($step, false)) {
				list($tokenText, $images) = 
					Wikitext::cutImages($step);

				$step = $tokenText;
				$numImages += count($images);

				foreach ($images as $image) {
					$tag = $image['tag'];
					$modtag = Wikitext::changeImageTag($tag, $px, $orientation);
					if ($recenter) {
						$step = str_replace($image['token'], '', $step);
						$step = trim($step);
						$re = "@[\r\n]+===[^=]*===@m";
						// Special case for alt methods
						if (preg_match($re, $step, $altMethod)) {
							$insert = "<br><br>$modtag" . $altMethod[0] . "\n";
							$step = preg_replace($re, $insert, $step);
						} else {
							$step .= "<br><br>$modtag\n";
						}
					} else {
						$step = str_replace($image['token'], $modtag, $step);
					}
				}
			}
		}

		$text = join('', $steps);
		return array($text, $numImages, $err);
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute() {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$user = $wgUser->getName();
		$allowedUsers = array(
			'Goldenzebra', // Zareen
			'Emazing', // Emma
			'Thomscher', // Thom
			'Chloechen', // Chloe
			'Wikiphoto', // Requested by Thom
		);
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked()
			|| (!in_array($user, $allowedUsers)
				&& !in_array('staff', $userGroups)))
		{
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			$dbr = wfGetDB(DB_SLAVE);

			$center = $wgRequest->getVal('pages-resize', '') == 'enlarge-center';
			$px = $wgRequest->getVal('pages-pixels', '0');
			$px = intval($px);

			$introPx = $wgRequest->getVal('pages-intro-pixels', 0);
			$introPx = intval($introPx);

			$pageList = $wgRequest->getVal('pages-list', '');

			if ($px < 50 || $px > 1000 ||
				($introPx && ($introPx < 50 || $introPx > 1000)))
			{
				$result = array('result' => '<i>ERROR: bad pixel value</i>');
				print json_encode($result);
				return;
			}

			$urls = self::parseURLlist($pageList);
			if (empty($urls)) {
				$result = array('result' => '<i>ERROR: no URLs given</i>');
				print json_encode($result);
				return;
			}

			self::enlargeImagesUrls($urls, $center, $px, $introPx);

			$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
			$html .= '<table class="tres"><tr><th width="400px">URL</th><th>Images changed</th><th>Error</th></tr>';
			foreach ($urls as $row) {
				$html .= "<tr><td><a href='{$row['url']}'>{$row['url']}</a></td><td>{$row['images']}</td><td>{$row['err']}</td></tr>";
			}
			$html .= '</table>';

			$result = array('result' => $html);
			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle('Admin - Enlarge Images - wikiHow');

		$defaultCenterPixels = self::DEFAULT_CENTER_PIXELS;
		$defaultEnlargePixels = self::DEFAULT_ENLARGE_PIXELS;
$tmpl = <<<EOHTML
<form id="images-resize" method="post" action="/Special:AdminEnlargeImages">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Enlarge Article Images
</div>
<div style="font-size: 13px; margin-bottom: 10px; border: 1px solid #dddddd; padding: 10px;">
	<div>
	<span style="margin-right: 10px;">
		<input id="rd-enlarge" type="radio" name="pages-resize" value="enlarge" checked="checked"> Enlarge </input>
	</span>
	<input id="rd-center" type="radio" name="pages-resize" value="enlarge-center"> Enlarge &amp; Center </input>
	<span style="margin-left: 50px;">
		New width in <i>Steps</i>: <input id="pixels" type="text" size="4" name="pages-pixels" value="{$defaultEnlargePixels}" /> (in pixels)
	</span>
	</div>
	<div style="padding: 5px 0 0 0;">
		<input id="rd-intro" class="intro-check" type="checkbox" name="pages-intro" checked="checked" /> <label class="intro-check" for="rd-intro">Increase intro image size to:</label>
		<input id="intro-pixels" type="text" size="4" name="pages-intro-pixels" value="{$defaultEnlargePixels}" /> (in pixels)
	</div>
</div>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea>
<button id="pages-go" disabled="disabled" style="padding: 5px;">process</button><br/>
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
				var form = $('#images-resize').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('/Special:AdminEnlargeImages',
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

		$('#rd-enlarge').click(function () {
			$('#pixels')
				.attr('disabled', '')
				.focus()
				.val('{$defaultEnlargePixels}');
		});

		$('#rd-center').click(function () {
			$('#pixels')
				.attr('disabled', '')
				.val('{$defaultCenterPixels}');
		});

		$('.intro-check').click(function () {
			if ($('#rd-intro:checked').length) {
				$('#intro-pixels')
					.attr('disabled', '')
					.focus();
			} else {
				$('#intro-pixels').attr('disabled', 'disabled');
			}
		});
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}

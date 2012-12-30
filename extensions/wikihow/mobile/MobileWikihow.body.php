<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/includes/EasyTemplate.php");

class MobileWikihow extends UnlistedSpecialPage {

	const NON_MOBILE_COOKIE_NAME = 'wiki_nombl';

	private $executeAsSpecialPage;
	private $language;

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('MobileWikihow');
		$this->executeAsSpecialPage = false;
	}

	public function execute() {
		$this->executeAsSpecialPage = true;
		$this->controller();
	}

	/**
	 * Process params that either display html for the mobile site, redirect
	 * to the mobile site, set cookies, etc.
	 *
	 * @return true if skin should continue processing, false otherwise
	 */
	public function controller() {
		global $wgTitle, $wgRequest, $wgOut;

		self::setTemplatePath();

		$action = $wgRequest->getVal('action', 'view');
		$isArticlePage = $wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $action == 'view';

		if (self::isMobileDomain()) {
			$redir = $wgRequest->getVal('redirect-non-mobile');
			if (!empty($redir) || $redir === '') {
				self::setNonMobileCookie(true);
				$newServer = self::getNonMobileSite();
				$newUrl = 'http://' . $newServer . '/' . $redir;
				$wgOut->redirect($newUrl);
				$wgOut->output();
				return false;
			} elseif (self::hasNonMobileCookie()) {
				self::setNonMobileCookie(false);
			}

			if (self::isMobileViewable()) {
				// If the URL is Special:MobileWikihow, this
				// page has already been processed through the special
				// page code path
				if ($wgTitle->getText() != wfMsg('MobileWikihow') || $this->executeAsSpecialPage) {
					$this->displayHtml();
				}
				return false;
			}
		} elseif (!self::hasNonMobileCookie() && self::isUserAgentMobile()) {
			if (self::isMobileViewable()) {
				$newServer = self::getMobileSite();
				$newUrl = 'http://' . $newServer . '/' . $wgTitle->getPrefixedUrl();
				$wgOut->redirect($newUrl);
				$wgOut->output();
				return false;
			}
		}

		return true;
	}

	private function displayHtml() {
		global $IP, $wgTitle, $wgOut, $wgRequest;
		$articleName = $wgTitle->getText();
		$partialUrl = $wgTitle->getPartialURL();
		$isMainPage = ( $articleName == wfMsg('mainpage') );
		$action = $wgRequest->getVal('action', 'view');
		//$lang = $this->getSiteLanguage();
		$deviceOpts = $this->getDevice();

		$baseDir = '/extensions/wikihow/mobile/';
		$randomUrl = '/' . wfMsg('special-randomizer');
		$titleBar = $isMainPage ? wfMsg('mobile-mainpage-title') : wfMsg('pagetitle', $articleName);
		$headerVars = array(
			'isMainPage' => $isMainPage,
			'title' => $titleBar,
			'cssFiles' => array($baseDir . 'mobile.css'),
			'randomUrl' => $randomUrl,
			'deviceOpts' => $deviceOpts,
		);

		// We separate the lib from JS from the other stuff so that it can
		// be cached.  iPhone caches objects under 25k.
		//self::addJS('/extensions/wikihow/prototype1.8.2/p.js', false);
		self::addJS('/extensions/wikihow/mobile/jquery-1.4.1.min.js', false);

		self::addJS('/extensions/wikihow/mobile/mobile.js', true);
		self::addJS('/skins/common/ga.js', true);

		$redirMainBase = '/' . wfMsg('special') . ':' . wfMsg('MobileWikihow') . '?redirect-non-mobile=';
		$footerVars = array(
			'showAds' => true,
			'itunesUrl' => 'http://itunes.apple.com/us/app/wikihow-how-to-diy-survival-kit/id309209200?mt=8',
			'deviceOpts' => $deviceOpts,
		);

		// get the html for the article
		if (!$isMainPage) $article = $wgOut->getHTML();
		$wgOut->clearHTML();

		// handle search and i10l pages here
		if ($isMainPage) {
			$headerVars['showTagline'] = true;
			$headerVars['cssFiles'][] = $baseDir . 'mobile-featured.css';
			$headerVars['cssFiles'][] = $baseDir . 'mobile-home.css';
			$wgOut->addHTML( EasyTemplate::html('header.tmpl.php', $headerVars) );

			$featured = $this->getFeaturedArticles(7);
			$spotlight = $this->selectSpotlightFeatured($featured);
			$langUrl = '/' . wfMsg('mobile-languages-url');
			$vars = array(
				'randomUrl' => $randomUrl,
				'spotlight' => $spotlight,
				'featured' => $featured,
				'languagesUrl' => $langUrl,
			);
			$wgOut->addHTML( EasyTemplate::html('main-page', $vars) );

			$footerVars['redirMainUrl'] = $redirMainBase;
			$wgOut->addHTML( EasyTemplate::html('footer', $footerVars) );
		} elseif ($action == 'view-languages') {
			$headerVars['cssFiles'][] = $baseDir . 'mobile-results.css';
			$wgOut->addHTML( EasyTemplate::html('header.tmpl.php', $headerVars) );

			$vars = array(
				'languages' => self::getLanguages(),
			);
			$wgOut->addHTML( EasyTemplate::html('language-select', $vars) );

			$footerVars['redirMainUrl'] = $redirMainBase;
			$wgOut->addHTML( EasyTemplate::html('footer', $footerVars) );
		} else { // article page
			$headerVars['cssFiles'][] = $baseDir . 'mobile-article.css';
			$wgOut->addHTML( EasyTemplate::html('header', $headerVars) );

			list($sections, $intro, $firstImage) = $this->parseArticle($article);
			if ($firstImage) {
				$title = Title::newFromURL($firstImage, NS_IMAGE);
				if ($title) {
					$introImage = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
					$thumb = $introImage->getThumbnail(290, 194);
					$width = $thumb->getWidth();
					$height = $thumb->getHeight();
				} else {
					$firstImage = '';
				}
			} 

			if (!$firstImage) {
				$thumb = null;
				$width = 0; $height = 0;
			}

			$articleVars = array(
				'title' => $wgTitle->getText(),
				'sections' => $sections,
				'intro' => $intro,
				'thumb' => &$thumb,
				'width' => $width,
				'height' => $height,
				'deviceOpts' => $deviceOpts,
			);
			$wgOut->addHTML( EasyTemplate::html('article', $articleVars) );

			$footerVars['redirMainUrl'] = $redirMainBase . $partialUrl;
			$baseMainUrl = 'http://' . self::getNonMobileSite() . '/';
			$footerVars['editUrl'] = $baseMainUrl . 'index.php?action=edit&title=' . $partialUrl;
			$wgOut->addHTML( EasyTemplate::html('footer', $footerVars) );
		}
		print $wgOut->getHTML();
	}

	private function parseArticle(&$article) {
		global $wgWikiHowSections;

		$sectionMap = array(
			wfMsg('Intro') => 'intro',
			wfMsg('Ingredients') => 'ingredients',
			wfMsg('Steps') => 'steps',
			wfMsg('Video') => 'video',
			wfMsg('Tips') => 'tips',
			wfMsg('Warnings') => 'warnings',
			wfMsg('relatedwikihows') => 'relatedwikihows',
			wfMsg('sourcescitations') => 'sources',
			wfMsg('thingsyoullneed') => 'thingsyoullneed',
		);

		$lang = $this->getSiteLanguage();
		$device = $this->getDevice();

		// munge steps first
		$opts = array(
			'no-ads' => true,
		);
		$article = WikiHowTemplate::mungeSteps($article, $opts);

		// Make doc correctly formed
$articleText = <<<DONE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="$lang" lang="$lang">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset='utf-8'" />
</head>
<body>
$article
</body>
</html>
DONE;
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->strictErrorChecking = false;
		$doc->recover = true;
		//$doc->preserveWhiteSpace = false;
		@$doc->loadHTML($articleText);
		$doc->normalizeDocument();
		$xpath = new DOMXPath($doc);

		// Delete #featurestar node
		$node = $doc->getElementById('featurestar');
		if (!empty($node)) {
			$node->parentNode->removeChild($node);
		}

		// Remove all "Edit" links
		$nodes = $xpath->query('//a[@id = "gatEditSection"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// Resize youtube video
		$nodes = $xpath->query('//embed');
		foreach ($nodes as $node) {
			$url = '';
			$src = $node->attributes->getNamedItem('src')->nodeValue;
			if (stripos($src, 'youtube.com') === false) {
				$parent = $node->parentNode;
				$grandParent = $parent->parentNode;
				if ($grandParent && $parent) {
					$grandParent->removeChild($parent);
				}
			} else {
				foreach (array(&$node, &$node->parentNode) as $node) {
					$widthAttr = $node->attributes->getNamedItem('width');
					$oldWidth = (int)$widthAttr->nodeValue;
					$newWidth = $device['max-video-width'];
					if ($newWidth < $oldWidth) {
						$widthAttr->nodeValue = (string)$newWidth;

						$heightAttr = $node->attributes->getNamedItem('height');
						$oldHeight = (int)$heightAttr->nodeValue;
						$newHeight = (int)round($newWidth * $oldHeight / $oldWidth);
						$heightAttr->nodeValue = (string)$newHeight;
					}
				}
			}
		}

		/*
		// Remove <a name="..."></a> tags
		$nodes = $xpath->query('//a[@name and not(@href)]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		*/

		// Remove templates from intro so that they don't muck up
		// the text and images we extract
		$nodes = $xpath->query('//div[@class = "template_top"]');
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		//self::walkTree($doc->documentElement, 1);exit;
		//echo $doc->saveHtml();exit;

		// Grab intro text
		$intro = '';
		$nodes = $xpath->query('//body/div/p');
		foreach ($nodes as $i => $node) {
			$text = $node->textContent;
			if (!empty($text) || $i == 1) {
				$introNode = $node;
				$intro = $text;
				break;
			}
		}

		if ($introNode) {
			// Grab first image from article
			$imgs = $xpath->query('//img', $introNode);
			$firstImage = '';
			foreach ($imgs as $img) {
				// parent is an <a> tag
				$parent = $img->parentNode;
				if ($parent->nodeName == 'a') {
					$href = $parent->attributes->getNamedItem('href')->nodeValue;
					if (preg_match('@Image:@', $href)) {
						$firstImage = preg_replace('@^.*Image:([^:]*)([#].*)?$@', '$1', $href);
						$firstImage = urldecode($firstImage);
						break;
					}
				}
			}

			// Remove intro node
			$parent = $introNode->parentNode;
			$parent->removeChild($introNode);
		}

		// Get rid of the <span> element to standardize the html for the
		// next dom query
		$nodes = $xpath->query('//div/span/a[@class = "image"]');
		foreach ($nodes as $a) {
			$parent = $a->parentNode;
			$grandParent = $parent->parentNode;
			$grandParent->replaceChild($a, $parent);
		}

		// Resize all resize-able images
		$nodes = $xpath->query('//div/a[@class = "image"]/img');
		$imgNum = 1;
		foreach ($nodes as $img) {
	
			$srcNode = $img->attributes->getNamedItem('src');
			$widthNode = $img->attributes->getNamedItem('width');
			$width = (int)$widthNode->nodeValue;
			$heightNode = $img->attributes->getNamedItem('height');
			$height = (int)$heightNode->nodeValue;

            // remove meebo onclick handler
			//$a = $img->parentNode;
			//$r = $a->removeAttribute('onclick');

			if ($width > $device['max-image-width']) {
				$newWidth = $device['max-image-width'];
				$newHeight = (int)round($device['max-image-width'] * $height / $width);
				// Image: link is gone now with zooming changes!
				//$href = $a->attributes->getNamedItem('href')->nodeValue;
				//$imgName = preg_replace('@^/Image:@', '', $href);
				//$src = $srcNode->nodeValue;
				//$imgName = preg_replace('@^/images/thumb/./../([^/]+)/.*$@', '$1', $src);
				$a = $img->parentNode;
				$href = $a->attributes->getNamedItem('href')->nodeValue;
				if (!$href) {
					$onclick = $a->attributes->getNamedItem('onclick')->nodeValue;
					$onclick = preg_replace('@.*",[ ]*"@', '', $onclick);
					$onclick = preg_replace('@".*@', '', $onclick);
					$imgName = preg_replace('@.*Image:@', '', $onclick);
				} else {
					$imgName = preg_replace('@^/Image:@', '', $href);
				}
				
				$title = Title::newFromURL($imgName, NS_IMAGE);
				if ($title) {
					$image = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
					$thumb = $image->getThumbnail($newWidth, $newHeight);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$srcNode->nodeValue = $url;
					$widthNode->nodeValue = $newWidth;
					$heightNode->nodeValue = $newHeight;

					// change surrounding div width and height
					$div = $a->parentNode;
					$styleNode = $div->attributes->getNamedItem('style');
					if (preg_match('@^(.*width:)[0-9]+(px;\s*height:)[0-9]+(.*)$@', $styleNode->nodeValue, $m)) {
						$styleNode->nodeValue = $m[1] . $newWidth . $m[2] . $newHeight . $m[3];
					}

					// change grandparent div width too
					$grandparent = $div->parentNode;
					if ($grandparent && $grandparent->nodeName == 'div') {
						$class = $grandparent->attributes->getNamedItem('class');
						if ($class && $class->nodeValue == 'thumb tright') {
							$style = $grandparent->attributes->getNamedItem('style');
							$style->nodeValue = 'width:' . $newWidth . 'px;';
						}
					}

					$thumb = $image->getThumbnail($device['image-zoom-width'], $device['image-zoom-height']);
					$newWidth = $thumb->getWidth();
					$newHeight = $thumb->getHeight();
					$url = wfGetPad($thumb->getUrl());

					$a->setAttribute('id', 'image-zoom-' . $imgNum);
					$a->setAttribute('class', 'image-zoom');
					$a->setAttribute('href', '#');
					$details = array(
						'url' => $url,
						'width' => $newWidth,
						'height' => $newHeight,
					);
					$newDiv = new DOMElement( 'div', htmlentities(json_encode($details)) );
					$a->appendChild($newDiv);
					$newDiv->setAttribute('style', 'display:none;');
					$newDiv->setAttribute('id', 'image-details-' . $imgNum);
					$imgNum++;
				}
			}
		}

		// Remove template from images, add new zoom one
		$nodes = $xpath->query('//img');
		foreach ($nodes as $node) {
			$src = ($node->attributes ? $node->attributes->getNamedItem('src') : null);
			$src = ($src ? $src->nodeValue : '');
			if (stripos($src, 'magnify-clip.png') !== false) {
				$parent = $node->parentNode;
				$parent->parentNode->removeChild($parent);
			}
		}

		// Change the width attribute from any tables with a width set.
		// This often happen around video elements.
		$nodes = $xpath->query('//table/@width');
		foreach ($nodes as $node) {
			$width = preg_replace('@px\s*$@', '', $node->nodeValue);
			if ($width > $device['screen-width'] - 20) {
				$node->nodeValue = $device['screen-width'] - 20;
			}
		}

		//self::walkTree($doc->documentElement, 1);
		$html = $doc->saveHTML();

		$sections = array();
		$sectionsHtml = explode('<h2>', $html);
		unset($sectionsHtml[0]); // remove leftovers from intro section
		foreach ($sectionsHtml as $i => &$section) {
			$section = '<h2>' . $section;
			if (preg_match('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)@i', $section, $m)) {
				$heading = $m[1];
				$section = preg_replace('@^<h2[^>]*>\s*<span[^>]*>\s*([^<]+)</span>(\s|\n)*</h2>@i', '', $section);
				if (isset($sectionMap[$heading])) {
					$key = $sectionMap[$heading];
					$sections[$key] = array(
						'name' => $heading,
						'html' => $section,
					);
				}
			}
		}
		
		// Remove Video section if there is no longer a youtube video
		if (isset($sections['video'])) {
			if ( !preg_match('@<object@i', $sections['video']['html']) ) {
				unset( $sections['video'] );
			}
		}
		// Remove </body></html> from html
		if (count($sections) > 0) {
			$keys = array_keys($sections);
			$last =& $sections[ $keys[count($sections) - 1] ]['html'];
			$last = preg_replace('@</body>(\s|\n)*</html>(\s|\n)*$@', '', $last);
		}

		return array($sections, $intro, $firstImage);
	}

	// for debugging: disable after finished
	/* private static function walkTree($elem, $in) {
		for ($i = 1; $i < $in; $i++) { print "."; }
		$name = $elem->nodeName;
		if ($name != '#text') {
			$id = ($elem->attributes ? $elem->attributes->getNamedItem('id') : null);
			$id = ($id ? $id->nodeValue : '');

			print $elem->nodeName.$idStr."<br>\n";
			if (!empty($elem->childNodes)) {
				foreach ($elem->childNodes as $node) {
					self::walkTree($node, $in+1);
				}
			}
		} else {
			print $elem->nodeValue."<br>\n";
		}
	} */

	/* private static function strNode($elem) {
		$name = $elem->tagName;
		$str = '[' . $name;
		foreach ($elem->attributes as $name => $node) {
			$str .= ' ' . $name . '="' . $node->nodeValue . '"';
		}
		$str .= ']';
		return $str;
	} */

	private function selectSpotlightFeatured(&$featured) {
		$spotlight = array();
		if ($featured) {
			// grab a random article from the list without replacement
			$r = mt_rand(0, count($featured) - 1);
			$spotlight = $featured[$r];
			unset($featured[$r]);
			$featured = array_values($featured); // re-key array

			$title = Title::newFromURL(urldecode($spotlight['url']));
			if ($title && $title->getArticleID() > 0) {
				$spotlight['img'] = $this->getFeatureArticleImage($title, 290, 194);
				$spotlight['intro'] = $this->getFeaturedArticleIntro($title);
			}
		}
		return $spotlight;
	}

	private function getFeatureArticleImage(&$title, $width, $height) {
		global $wgUser;
		$skin = $wgUser->getSkin();

		// The next line was taken from:
		//   SkinWikihowskin::featuredArticlesLineWide()
		$img = $skin->getGalleryImage($title, $width, $height);
		return wfGetPad($img);
	}

	private function getFeaturedArticles($num) {
		global $IP;
		$NUM_DAYS = 15; // enough days to make sure we get $num articles

		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$featured = FeaturedArticles::getFeaturedArticles($NUM_DAYS);

		$fas = array();
		$n = 1;
		foreach($featured as $f) {
			$partUrl = preg_replace('@^http://(\w|\.)+\.wikihow\.com/@', '', $f[0]);
			$title = Title::newFromURL(urldecode($partUrl));
			if ($title) {
				$name = $title->getText();

				$fa = array(
					'name' => $name,
					'url' => $partUrl,
					'img' => $this->getFeatureArticleImage($title, 90, 54),
				);
				$fas[] = $fa;

				if (++$n > $num) break;
			}
		}

		return $fas;
	}

	private function getFeaturedArticleIntro(&$title) {
		// use public methods from the RSS feed that do the same thing
		$article = Generatefeed::getLastPatrolledRevision($title);
		$summary = Generatefeed::getArticleSummary($article, $title);
		return $summary;
	}

	private static $jsScripts = array();
	private static $jsScriptsCombine = array();
	public static function addJS($script, $combine) {
		if ($combine) {
			self::$jsScriptsCombine[] = $script;
		} else {
			self::$jsScripts[] = $script;
		}
	}

	public static function showDeferredJS() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			$vars = array(
				'scripts' => self::$jsScripts,
				'scriptsCombine' => self::$jsScriptsCombine,
			);
			return EasyTemplate::html('include-js', $vars);
		} else {
			return '';
		}
	}

	public static function showBootStrapScript() {
		static $displayed = false;
		if (!$displayed) {
			$displayed = true;
			return '<script>mobileWikihow.startup();</script>';
		} else {
			return '';
		}
	}

	private static function getLanguages() {
		$ccedil = htmlspecialchars_decode('&ccedil;');
		$ntilde = htmlspecialchars_decode('&ntilde;');
		$ecirc = htmlspecialchars_decode('&ecirc;');
		$langs = array(
			array(
				'code' => 'en', 
				'name' => 'English',
				'url'  => 'http://m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_england.gif',
			),
			array(
				'code' => 'es', 
				'name' => "Espa{$ntilde}ol",
				'url'  => 'http://es.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_spain.gif',
			),
			array(
				'code' => 'de', 
				'name' => 'Deutsch',
				'url'  => 'http://de.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_germany.gif',
			),
			array(
				'code' => 'pt', 
				'name' => "Portugu{$ecirc}s",
				'url'  => 'http://pt.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_portugal.gif',
			),
			array(
				'code' => 'fr', 
				'name' => "Fran${ccedil}ais",
				'url'  => 'http://fr.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_france.gif',
			),
			array(
				'code' => 'it', 
				'name' => 'Italiano',
				'url'  => 'http://it.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_italy.gif',
			),
			array(
				'code' => 'nl', 
				'name' => 'Nederlands',
				'url'  => 'http://nl.m.wikihow.com/',
				'img'  => '/extensions/wikihow/mobile/images/flag_netherlands.gif',
			),
		);
		return $langs;
	}

	private static function isMobileViewable() {
		global $wgTitle, $wgRequest;
		$validMobileActions = array('view', 'view-languages');

		$action = $wgRequest->getVal('action', 'view');
		$isArticlePage = $wgTitle
			&& ($wgTitle->getNamespace() == NS_MAIN
				|| $wgTitle->getText() == wfMsg('MobileWikihow'))
			&& in_array($action, $validMobileActions);

		return $isArticlePage;
	}

	public static function isUserAgentMobile() {
		$uagent = @$_SERVER['HTTP_USER_AGENT'];
		return preg_match('@iphone|ipod|blackberry|palm|android|windows ce@i', $uagent) > 0;
	}

	private static function isMobileDomain() {
		global $wgServerName;
		return preg_match('@(^m\.|\.m\.)@', $wgServerName) > 0;
	}

	private static function getMobileSite() {
		global $wgServerName;
		if ($wgServerName == 'www.wikihow.com') {
			return 'm.wikihow.com';
		} else {
			return preg_replace('@^(.*)\.(\w+\.com)$@', '$1.m.$2', $wgServerName);
		}
	}
	
	private function getSiteLanguage() {
		global $wgServerName;
		if (!empty($this->language)) {
			return $this->language;
		} else {
			if (preg_match('@^([a-z]{2})\.m\.wikihow\.com$@i', $wgServerName, $m)) {
				$this->language = strtolower($m[1]);
			} else {
				$this->language = 'en';
			}
		}
	}

	private static function getNonMobileSite() {
		global $wgServerName;
		if ($wgServerName == 'm.wikihow.com') {
			return 'www.wikihow.com';
		} else {
			return str_replace('.m.', '.', $wgServerName);
		}
	}

	private static function hasNonMobileCookie() {
		$cookie = @$_COOKIE[ self::NON_MOBILE_COOKIE_NAME ];
		return !empty($cookie) && $cookie == '1';
	}

	private static function setNonMobileCookie($useNonMobileSite) {
		global $wgCookieDomain;
		$cookieValue = ($useNonMobileSite ? '1' : false);
		$expires = time() + 2*60*60; // 2 hours from now -- specified by Jack
		setcookie(self::NON_MOBILE_COOKIE_NAME, $cookieValue, $expires, '/', $wgCookieDomain);
	}

	const DEFAULT_DEVICE = 'iphone'; // works fine for android phones too

	/**
	 * Returns properties of device that we need to abstract in certain causes.
	 * Sort of functions like a poor man's WURFL database.
	 */
	private static function getDevice() {
		global $wgRequest;

		$platforms = array(
			'iphone' => array(
				'name' => 'iphone',
				'screen-width' => 320,
				'screen-height' => 480,
				'image-zoom-width' => 270,
				'image-zoom-height' => 430,
				'max-image-width' => 100,
				'max-video-width' => 300,
				'intro-image-format' => 'conditional',
				'show-only-steps-tab' => true,
				'show-header-footer' => true,
			),
			'ipad' => array(
				'name' => 'ipad',
				'screen-width' => 768,
				'screen-height' => 1024,
				'image-zoom-width' => 500,
				'image-zoom-height' => 600,
				'max-image-width' => 250,
				'max-video-width' => 600,
				'intro-image-format' => 'right',
				'show-only-steps-tab' => true,
				'show-header-footer' => false,
			),
			'chromestore' => array(
				'name' => 'chromestore',
				'screen-width' => 768,
				'screen-height' => 1024,
				'image-zoom-width' => 500,
				'image-zoom-height' => 600,
				'max-image-width' => 250,
				'max-video-width' => 600,
				'intro-image-format' => 'right',
				'show-only-steps-tab' => false,
				'show-header-footer' => true,
			),
			// add 'iphone-4g' here when it's time
		);
		$platform = $wgRequest->getVal('platform', self::DEFAULT_DEVICE);
		if (!isset($platforms[ $platform ])) {
			$platform = self::DEFAULT_DEVICE;
		}
		return $platforms[ $platform ];
	}

	/**
	 * Set html template path for Easyimageupload actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

}


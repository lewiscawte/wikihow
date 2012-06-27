<?php


class WikihowShare{
	
	public static function getTopShareButtons(){
		global $wgLanguageCode, $wgTitle, $wgUser, $wgServer;
		
		$action = self::getAction();

		if(!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || self::isMainPage($action))
			return "";

		$sk = $wgUser->getSkin();
		
		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		$img = urlencode(self::getPinterestImage($wgTitle));
		$desc = urlencode(wfMsg('howto', $wgTitle->getText()) . " via www.wikiHow.com"); 
				
		$fb = '<div class="like_button"><fb:like href="' . $url . '" send="false" layout="box_count" width="46" show_faces="false"></fb:like></div>';
		$gp1 = '<div class="gplus1_button"><g:plusone size="tall" callback="plusone_vote"></g:plusone></div>';

		$pinterest = '<div id="pinterest"><a href="http://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $img . '&description=' . $desc . '" class="pin-it-button" count-layout="vertical">Pin It</a></div>';

		// German includes "how to " in the title text
		$howto = $wgLanguageCode != 'de' ? wfMsg('howto', htmlspecialchars($wgTitle->getText())) : htmlspecialchars($wgTitle->getText());
		$tb = '<div class="admin_state"><a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="vertical" data-via="wikiHow" data-text="' . $howto . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a></div>';
		
		if ($wgLanguageCode != 'en') {
			return $gp1 . $tb . $fb;
		}
		else {
			return $gp1 . $fb . $pinterest;
		}
		
	}
	
	public static function getBottomShareButtons(){
		
		global $wgLanguageCode, $wgTitle, $wgServer;
		
		$action = self::getAction();
		
		if(!$wgTitle->exists() || $wgTitle->getNamespace() != NS_MAIN || $action != "view" || self::isMainPage($action))
			return "";
		
		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
				
		$fb_share = '<div class="like_button like_tools"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		// German includes "how to " in the title text
		$howto = $wgLanguageCode != 'de' ? wfMsg('howto', htmlspecialchars($wgTitle->getText())) : htmlspecialchars($wgTitle->getText());
		$tb_share = '<a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="' . $howto. '" data-related="JackHerrick:Founder of wikiHow">Tweet</a>';

		
		if ($wgLanguageCode != 'en') {
			return $fb_share . $tb_share;
		}
		else {
			return $fb_share . $tb_share;
		}
	}
	
	function getAction() {
		global $wgRequest;
		
		$action = $wgRequest->getVal("action", "view");
		if ($wgRequest->getVal("diff", "") != "")
			$action = "diff";
		
		return $action;
	}
	
	function isMainPage($action) {
		global $wgTitle;
		
		return ($wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() == wfMsg('mainpage')
			&& $action == 'view');
	}
	
	function getPinterestImage($title) {
		global $wgMemc, $wgLanguageCode, $wgContLang, $wgUser;

		$key = wfMemcKey("pinterest:{$title->getArticleID()}");

		$val = $wgMemc->get($key);
		if ($val) {
			return $val;
		}
		
		$sk = $wgUser->getSkin();

		if (($title->getNamespace() == NS_MAIN) || ($title->getNamespace() == NS_CATEGORY) ) {
			if ($title->getNamespace() == NS_MAIN) {
				
				$file = $sk->getTitleImage($title);
				if($file && isset($file)) {
					$url = wfGetPad("/images/" . $file->getRel());
					$wgMemc->set($key, $url, 2* 3600); // 2 hours
					return $url;
				}
				
			}

			$catmap = array(
				wfMsg("arts-and-entertainment") => "Image:Category_arts.jpg",
				wfMsg("health") => "Image:Category_health.jpg",
				wfMsg("relationships") => "Image:Category_relationships.jpg",
				wfMsg("cars-&-other-vehicles") => "Image:Category_cars.jpg",
				wfMsg("hobbies-and-crafts") => "Image:Category_hobbies.jpg",
				wfMsg("sports-and-fitness") => "Image:Category_sports.jpg",
				wfMsg("computers-and-electronics") => "Image:Category_computers.jpg",
				wfMsg("holidays-and-traditions") => "Image:Category_holidays.jpg",
				wfMsg("travel") => "Image:Category_travel.jpg",
				wfMsg("education-and-communications") => "Image:Category_education.jpg",
				wfMsg("home-and-garden") => "Image:Category_home.jpg",
				wfMsg("work-world") => "Image:Category_work.jpg",
				wfMsg("family-life") => "Image:Category_family.jpg",
				wfMsg("personal-care-and-style") => "Image:Category_personal.jpg",
				wfMsg("youth") => "Image:Category_youth.jpg",
				wfMsg("finance-and-legal") => "Image:Category_finance.jpg",
				wfMsg("finance-and-business") => "Image:Category_finance.jpg",
				wfMsg("pets-and-animals") => "Image:Category_pets.jpg",
				wfMsg("food-and-entertaining") => "Image:Category_food.jpg",
				wfMsg("philosophy-and-religion") => "Image:Category_philosophy.jpg",
			);
			// still here? use default categoryimage

			// if page is a top category itself otherwise get top
			if (isset($catmap[urldecode($title->getPartialURL())])) {
				$cat = urldecode($title->getPartialURL());
			} else {
				$cat = $sk->getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial URL for self::getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					if($title != null)
						$cat = $title->getPartialURL();
				}
			}

			if (isset($catmap[$cat])) {
				$image = Title::newFromText($catmap[$cat]);
				$file = wfFindFile($image, false);
				$url = wfGetPad("/images/" . $file->getRel());
				if ($url) {
					$wgMemc->set($key, $url,  2 * 3600); // 2 hours
					return $url;
				}
			} else {
				$url = wfGetPad("/skins/WikiHow/images/wikihow_large.jpg");
				if ($url) {
					$wgMemc->set($key, $url, 2 * 3600); // 2 hours
					return $url;
				}
			}
		}
	}
	
	public static function getPinterestTitleInfo() {
		$whow = WikiHow::newFromCurrent();
		if (!$whow) return '';

		$text = $whow->mLoadText;
		$num_steps = 0;
		if (preg_match("/^(.*?)==\s*".wfMsg('tips')."/ms", $text, $sectionmatch)) {
			// has tips, let's assume valid candidate for detailed title
			$num_steps = preg_match_all('/^#[^*]/im', $sectionmatch[1], $matches);
		}

		if ($num_steps >= 5 && $num_steps <= 12) {
				$titleDetail = " in $num_steps Steps";
		} else {
			$titleDetail = '';
		}
		
		return $titleDetail;
	}
	
}

?>

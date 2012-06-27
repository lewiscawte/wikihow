<?php

class wikihowAds {
	
	var $adsLoaded = false;
	var $ads;
	
	function wikihowAds() {
		$this->ads = array();
	}
	
	public static function getSetup() {
		global $wgUser;
		
		//$sk = $wgUser->getSkin();
		
		//return wfMsg('Ad_setup', wikihowAds::isHhm());
		return wfMsg('Wikihowads_setup', wikihowAds::isHhm());
	}

	public static function getAdUnitPlaceholder($num, $isLinkUnit = false, $postLoad = true) {
		global $wgSingleLoadAllAds, $wgEnableLateLoadingAds, $wgUser, $wgTitle;
		$sk = $wgUser->getSkin();

		if(self::adExclusions($wgTitle->getFullText()))
			return "";

		if($num == 4)
			$unit = !$isLinkUnit ? self::getAdUnit($num) : self::getLinkUnit($num);
		else{
			//$unit = !$isLinkUnit ? self::getAdUnit($num) : self::getLinkUnit($num);
			$unit = !$isLinkUnit ? self::getWikihowAdUnit($num) : self::getLinkUnit($num);
		}
		$adID = !$isLinkUnit ? 'au' . $num : 'lu' . $num;
		
		if($wgSingleLoadAllAds && !$isLinkUnit && $num != 4) {
			
		}

		static $postLoadTest = null;
		if (!$wgEnableLateLoadingAds) {
			$postLoad = false;
		}

		if ($postLoadTest == null) {
			$postLoadTest = mt_rand(1,2);
			if ($postLoadTest == 1)
				// no post load
				$sk->mGlobalChannels[] = "2490795108";
			else
				// yes post load
				$sk->mGlobalChannels[] = "7974857016";
		}

		// test
		//$postLoad = $postLoadTest == 2;
		$postLoad = false;

		if ($postLoad && $adID === 'au4') {
			return getPostLoadAd($adID, $unit);
		} else {
			return $unit;
		}
	}
	
	function getPostLoadAd($adID, $unit) {
		$loadingStr = '';

		$loadingHtml = <<<EOHTML
			<div id="wh_ad_loading_{$adID}" class='wh_ad'>
				{$loadingStr}
			</div>
EOHTML;

	$postLoadHtml = <<<EOHTML
	<div id="wh_ad_tmp_{$adID}" class='wh_ad' style="display: none;">
		{$unit}
	</div>
	<script>
		var whSrcAdDiv = document.getElementById('wh_ad_tmp_{$adID}');
		var whDestAdDiv = document.getElementById('wh_ad_loading_{$adID}');
		if (whSrcAdDiv && whDestAdDiv) {
			whDestAdDiv.innerHTML = whSrcAdDiv.innerHTML;
			whSrcAdDiv.innerHTML = '';
		}
	</script>
EOHTML;

			self::addPostLoadedAdHTML($postLoadHtml);
			return $loadingHtml;
	}
	
	function adExclusions($title){
		switch($title){
			case "Clear Your Browser's Cache":
				return true;
			default:
				return false;
		}
	}
	
	function getLinkUnit($num) {
		global $wgUser;
		//$sk = $wgUser->getSkin();
		$channels = self::getCustomGoogleChannels('linkunit' . $num, false);
		$s = wfMsg('linkunit' . $num, $channels[0]);
		$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		return $s;
	}

	function getAdUnit($num) {
		global $wgUser, $wgLanguageCode;
		//$sk = $wgUser->getSkin();
		if($wgLanguageCode == "en") {
			$channels = self::getCustomGoogleChannels('adunit' . $num, false);
			$s = wfMsg('adunit' . $num, $channels[0]);
		}
		else {
			$channels = self::getInternationalChannels();
			$s = wfMsg('adunit' . $num, $channels);
		}
		$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		return $s;
	}
	
	function getWikihowAdUnit($num) {
		global $wgUser, $wgLanguageCode;
		//$sk = $wgUser->getSkin();
		if ($wgLanguageCode == "en") { 
			$channelArray = self::getCustomGoogleChannels('adunit' . $num, false);
			$channels = $channelArray[0];
		}
		else
			$channels = self::getInternationalChannels();
		
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'adId' => $num,
			'channels' => $channels
		));
		$s = $tmpl->execute('wikihowAd.tmpl.php');
		
		$s = "<div class='wh_ad'>" . $s . "</div>";
		return $s;
	}
	
	function getIatestAd() {
		global $wgTitle;
		
        if ($wgTitle->getNamespace() == NS_MAIN) {
			$titleUrl = $wgTitle->getFullURL();
			
			$msg = wfMsg('IAtest');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					return wikihowAds::getAdUnitPlaceholder(4);
				}
			}
		}
	}
	
	public static function getGlobalChannels() {
		global $wgTitle, $wgUser;

		$sk = $wgUser->getSkin();

		$sk->mGlobalChannels[] = "1640266093";
		$sk->mGlobalComments[] = "page wide track";

        // track WRM articles in Google AdSense
		// but not if they're included in the
		// tech buckets above
        if ($wgTitle->getNamespace() == NS_MAIN) {
            $dbr = wfGetDB(DB_MASTER);
            $minrev = $dbr->selectField('revision', 'min(rev_id)', array('rev_page'=>$wgTitle->getArticleID()), __METHOD__);
			$details = $dbr->selectRow('revision', array('rev_user_text', 'rev_timestamp'), array('rev_id'=>$minrev), __METHOD__);
			$fe = $details->rev_user_text;

			//Tech buckets (no longer only WRM)
			$foundTech = false;
			$title = $wgTitle->getFullURL();
			$titleUrl = $wgTitle->getFullURL();
			$msg = ConfigStorage::dbGetConfig('T_bin1'); //popular companies
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $title){
					$foundTech = true;
					$ts = $details->rev_timestamp;
					if (preg_match("@^201106@", $ts)){
						$sk->mGlobalChannels[] = "5265927225";
					} else if (preg_match("@^201105@", $ts)){
						$sk->mGlobalChannels[] = "2621163941";
					} else if (preg_match("@^201104@", $ts)){
						$sk->mGlobalChannels[] = "6703830173";
					} else if (preg_match("@^201103@", $ts)){
						$sk->mGlobalChannels[] = "7428198201";
					} else if (preg_match("@^201102@", $ts)){
						$sk->mGlobalChannels[] = "6027428251";
					} else if (preg_match("@^201101@", $ts)){
						$sk->mGlobalChannels[] = "3564919246";
					}
					break;
				}
			}

			if (!$foundTech) {
				$msg = ConfigStorage::dbGetConfig('T_bin2'); //startup companies
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$foundTech = true;
						$ts = $details->rev_timestamp;
						if (preg_match("@^201112@", $ts)){
							$sk->mGlobalChannels[] = "4113109859";
						} else if (preg_match("@^201111@", $ts)){
							$sk->mGlobalChannels[] = "1967209400";
						} else if (preg_match("@^201110@", $ts)){
							$sk->mGlobalChannels[] = "0168911685";
						} else if (preg_match("@^201109@", $ts)){
							$sk->mGlobalChannels[] = "5356416885";
						} else if (preg_match("@^201108@", $ts)){
							$sk->mGlobalChannels[] = "3273638668";
						} else if (preg_match("@^201107@", $ts)){
							$sk->mGlobalChannels[] = "9892808753";
						} else if (preg_match("@^201106@", $ts)){
							$sk->mGlobalChannels[] = "3519312489";
						} else if (preg_match("@^201105@", $ts)){
							$sk->mGlobalChannels[] = "2958013308";
						} else if (preg_match("@^201104@", $ts)){
							$sk->mGlobalChannels[] = "2240499801";
						} else if (preg_match("@^201103@", $ts)){
							$sk->mGlobalChannels[] = "9688666159";
						} else if (preg_match("@^201102@", $ts)){
							$sk->mGlobalChannels[] = "2421515764";
						} else if (preg_match("@^201101@", $ts)){
							$sk->mGlobalChannels[] = "8503617448";
						}
						break;
					}
				}
			}

            if ($fe == 'WRM' && !$foundTech) { //only care if we didn't put into a tech bucket
				$sk->mGlobalComments[] = "wrm";
				$ts = $details->rev_timestamp;
				
				if (preg_match("@^201112@", $ts)){
					$sk->mGlobalChannels[] = "6155290251";
				} else if (preg_match("@^201111@", $ts)){
					$sk->mGlobalChannels[] = "6049972339";
				} else if (preg_match("@^201110@", $ts)){
					$sk->mGlobalChannels[] = "0763990979";
				} else if (preg_match("@^201109@", $ts)){
					$sk->mGlobalChannels[] = "4358291042";
				} else if (preg_match("@^201108@", $ts)){
					$sk->mGlobalChannels[] = "0148835175";
				} else if (preg_match("@^201107@", $ts)){
					$sk->mGlobalChannels[] = "2390612184";
				} else if (preg_match("@^201106@", $ts)){
					$sk->mGlobalChannels[] = "1532661106";
				} else if (preg_match("@^201105@", $ts)){
					$sk->mGlobalChannels[] = "6709519645";
				} else if (preg_match("@^201104@", $ts)){
					$sk->mGlobalChannels[] = "8239478166";
				} else if (preg_match("@^201103@", $ts)){
					$sk->mGlobalChannels[] = "1255784003";
				} else if (preg_match("@^201102@", $ts)){
					$sk->mGlobalChannels[] = "7120312529";
				} else if (preg_match("@^201101@", $ts)){
					$sk->mGlobalChannels[] = "7890650737";
				} else if (preg_match("@^201012@", $ts)){
					$sk->mGlobalChannels[] = "9742218152";
				} else if(preg_match("@^201011@", $ts)){
					$sk->mGlobalChannels[] = "8485440130";
				} else if(preg_match("@^201010@", $ts)){
					$sk->mGlobalChannels[] = "7771792733";
				} else if(preg_match("@^201009@", $ts)) {
				   $sk->mGlobalChannels[] = "8422911943";
				} else if (preg_match("@^201008@", $ts)) {
				   $sk->mGlobalChannels[] = "3379176477";
				} 
            } else if (in_array($fe, array('Burntheelastic', 'CeeZee', 'Claricea', 'EssAy', 'JasonArton', 'Nperry302', 'Sugarcoat'))) {
                $sk->mGlobalChannels[] = "8537392489";
                $sk->mGlobalComments[] = "mt";
            } else {
                $sk->mGlobalChannels[] = "5860073694";
                $sk->mGlobalComments[] = "!wrm && !mt";
            }
			
			//Original WRM bucket
			$msg = ConfigStorage::dbGetConfig('Dec2010_bin0');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$sk->mGlobalChannels[] = "8110356115"; //original wrm channels
					break;
				}
			}
			

			//WRM buckets
			$found = false;
			$title = $wgTitle->getFullText();
			$msg = ConfigStorage::dbGetConfig('Dec2010_bin1');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $title){
					$found = true;
					$sk->mGlobalChannels[] = "8052511407";
					break;
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin2');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "8301953346";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin3');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "7249784941";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin4');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "8122486186";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin5');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "8278846457";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin6');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "1245159133";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin7');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "7399043796";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin8');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "6371049270";
						break;
					}
				}
			}
			if(!$found){
				$msg = ConfigStorage::dbGetConfig('Dec2010_bin9');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "9638019760"; //WRM Bucket: WRG-selected
						break;
					}
				}
			}
			if(!$found && $fe == 'WRM'){
				$r = Revision::newFromTitle($wgTitle);
				if ($r != null){
					$text1 = $r->getText();
					$num_steps = 0;
					if (preg_match("/^(.*?)== ".wfMsg('tips')."/ms", $text1, $sectionmatch)) {
						$num_steps = preg_match_all ('/^#[^*]/im', $sectionmatch[1], $matches);

						$num_step_photos = preg_match_all('/\[\[Image:/im', $text1, $matches);
						if($num_steps > 0 && $num_step_photos/$num_steps >= .75){
							//this has enough step-by-step photos, so add the channel to it
							$found = true;
							$sk->mGlobalChannels[] = "8143840778"; //WRM Bucket: wikiPhoto
						}
					}
				}
			}

			$msg = ConfigStorage::dbGetConfig('Dec2010_e1');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$sk->mGlobalChannels[] = "8107511392"; //WRM Bucket: E1
					break;
				}
			}

			$msg = ConfigStorage::dbGetConfig('Dec2010_e2');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$sk->mGlobalChannels[] = "3119976353"; //WRM Bucket: E2
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('DrawTest'); //drawing articles
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "4881792894"; //WRM Bucket: E2
					break;
				}
			}

			if ($sk->mCategories['Recipes'] != null) {
				$sk->mGlobalChannels[] = "5820473342"; //Recipe articles
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_a'); //content strategy A
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "8989984079"; //Content Strategy A
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_b'); //content strategy B
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3833770891"; //Content Strategy B
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_c'); //content strategy C
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "5080980738"; //Content Strategy C
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_d'); //content strategy D
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3747905129"; //Content Strategy D
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_e'); //content strategy E
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "0499166168"; //Content Strategy E
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_f'); //content strategy F
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3782603124"; //Content Strategy F
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_g'); //content strategy G
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "2169636267"; //Content Strategy G
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_h') . "\n" . wfMsg('CS_h1'); //content strategy H
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "6341255402"; //Content Strategy H
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_i'); //content strategy I
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "5819170825"; //Content Strategy I
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_j'); //content strategy J
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "7694072995"; //Content Strategy J
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_k'); //content strategy K
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "5982569583"; //Content Strategy K
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_l'); //content strategy L
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "7774283315"; //Content Strategy L
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_m'); //content strategy M
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "6128624756"; //Content Strategy M
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_n'); //content strategy N
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "2682008177"; //Content Strategy N
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_o'); //content strategy O
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "4294279486"; //Content Strategy O
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_p'); //content strategy P
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "8749396082"; //Content Strategy P
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_q'); //content strategy Q
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "0856671147"; //Content Strategy Q
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_r'); //content strategy R
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "4560446682"; //Content Strategy R
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_s'); //content strategy S
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3657316725"; //Content Strategy S
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_t'); //content strategy T
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "9924756626"; //Content Strategy T
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('CS_u'); //content strategy U
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "8414472671"; //Content Strategy U
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('WRM_2012Q1'); 
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "4126436138"; 
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('WRM_2012Q2'); 
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3130480452"; 
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('WRM_2012Q3');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "5929918148";
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('WRM_2012Q4'); 
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "5980804200"; 
					break;
				}
			}
			
			if (wikihowAds::isHhm()) {
				$sk->mGlobalChannels[] = "5905062452"; //is an HHM page
			}
			
			$msg = ConfigStorage::dbGetConfig('redesign_control'); 
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "2876223637"; //test off
					break;
				}
			}
			
			$msg = ConfigStorage::dbGetConfig('redesign_test'); 
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $titleUrl){
					$ts = $details->rev_timestamp;
					$sk->mGlobalChannels[] = "3857858648";  //redesign test off
					break;
				}
			}
			
        }
	}
	
	function getCustomGoogleChannels($type, $use_chikita_sky) {

		global $wgTitle, $wgLang, $IP, $wgUser;
		
		$sk = $wgUser->getSkin();

		$channels = array();
		$comments = array();

		$ad = array();
		$ad['adunitintro'] 			= '0206790666';
		$ad['horizontal_search'] 	= '9965311755';
		$ad['rad_bottom'] 			= '0403699914';
		$ad['ad_section'] 			= '7604775144';
		$ad['rad_left'] 			= '3496690692';
		$ad['rad_left_custom']		= '3371204857';
		$ad['rad_video'] 			= '8650928363';
		$ad['skyscraper']			= '5907135026';
		$ad['vertical_search']		= '8241181057';
		$ad['embedded_ads']			= '5613791162';
		$ad['embedded_ads_top']		= '9198246414';
		$ad['embedded_ads_mid']		= '1183596086';
		$ad['embedded_ads_vid']		= '7812294912';
		$ad['side_ads_vid']			= '5407720054';
		$ad['adunit0']				= '2748203808';
		$ad['adunit1']				= '4065666674';
		$ad['adunit2']				= '7690275023';
		$ad['adunit2a']				= '9206048113';
		$ad['adunit3']				= '9884951390';
		$ad['adunit4']				= '7732285575';
		$ad['adunit5']				= '7950773090';
		$ad['adunit6']				= '';
		$ad['adunit7']				= '8714426702';
		$ad['linkunit1']			= '2612765588';
		$ad['linkunit2']          	= '5047600031';
		$ad['linkunit3']            = '5464626340';


		$namespace = array();
		$namespace[NS_MAIN]             = '7122150828';
		$namespace[NS_TALK]             = '1042310409';
		$namespace[NS_USER]             = '2363423385';
		$namespace[NS_USER_TALK]        = '3096603178';
		$namespace[NS_PROJECT]          = '6343282066';
		$namespace[NS_PROJECT_TALK]     = '6343282066';
		$namespace[NS_IMAGE]            = '9759364975';
		$namespace[NS_IMAGE_TALK]       = '9759364975';
		$namespace[NS_MEDIAWIKI]        = '9174599168';
		$namespace[NS_MEDIAWIKI_TALK]   = '9174599168';
		$namespace[NS_TEMPLATE]         = '3822500466';
		$namespace[NS_TEMPLATE_TALK]    = '3822500466';
		$namespace[NS_HELP]             = '3948790425';
		$namespace[NS_HELP_TALK]        = '3948790425';
		$namespace[NS_CATEGORY]         = '2831745908';
		$namespace[NS_CATEGORY_TALK]    = '2831745908';
		$namespace[NS_USER_KUDOS]       = '3105174400';
		$namespace[NS_USER_KUDOS_TALK]  = '3105174400';

		$channels[] = $ad[$type];
		$comments[] = $type;

		if ($use_chikita_sky) {
			$channels[] = "7697985842";
			$comments[] = "chikita sky";
		} else {
			$channels[] = "7733764704";
			$comments[] = "google sky";
		}

		foreach ($sk->mGlobalChannels as $c) {
			$channels[] = $c;
		}
		foreach ($sk->mGlobalComments as $c) {
			$comments[] = $c;
		}

		// Video
		if ($wgTitle->getNamespace() ==  NS_SPECIAL && $wgTitle->getText() == "Video") {
			$channels[] = "9155858053";
			$comments[] = "video";
		}

		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$fas = FeaturedArticles::getFeaturedArticles(3);
		foreach ($fas as $fa) {
			if ($fa[0] == $wgTitle->getFullURL()) {
				$comments[] = 'FA';
				$channels[] = '6235263906';
			}
		}

		// do the categories
		$tree = WikiHow::getCurrentParentCategoryTree();
		$tree = self::flattenCategoryTree($tree);
		$tree = self::cleanUpCategoryTree($tree);

		$map = self::getCategoryChannelMap();
		foreach ($tree as $cat) {
			if (isset($map[$cat])) {
				$channels[] = $map[$cat];
				$comments[] = $cat;
			}
		}

		if ($wgTitle->getNamespace() == NS_SPECIAL)
			$channels[] = "9363314463";
		else
			$channels[] = $namespace[$wgTitle->getNamespace()];
		if ($wgTitle->getNamespace() == NS_MAIN) {
			$comments[] = "Main namespace";
		} else {
			$comments[] = $wgLang->getNsText($wgTitle->getNamespace());
		}

		// TEST CHANNELS
		//if ($wgTitle->getNamespace() == NS_MAIN && $id % 2 == 0) {
		if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Search") {
			$channels[]  = '8241181057';
			$comments[]  = 'Search page';
		}

		$result = array(implode("+", $channels), implode(", ", $comments));
		return $result;
	}
	
	function getInternationalChannels() {
		global $wgTitle, $wgUser;
		
		$channels = array();

		//$sk = $wgUser->getSkin();

		if ($wgTitle->getNamespace() == NS_MAIN) {
            $dbr = wfGetDB(DB_MASTER);
            $minrev = $dbr->selectField('revision', 'min(rev_id)', array('rev_page'=>$wgTitle->getArticleID()), __METHOD__);
			$details = $dbr->selectRow('revision', array('rev_user_text', 'rev_timestamp'), array('rev_id'=>$minrev), __METHOD__);
			$fe = $details->rev_user_text;

            if (in_array($fe, array('Wilfredor', 'WikiHow Traduce')) ){
               	$channels[] = "3957522669";
			} else if($fe == "WikiHow Übersetzungen"){
               	$channels[] = "6309209598";
            } else if($fe == "Traduções wikiHow"){
                $channels[] = "3705134139";
            }
		}
		
		$channelString = implode("+", $channels);
			
		return $channelString;
	}
	
	function isJSTest() {
		global $wgTitle;
		
		$msg = wfMsg('Js_control'); //JS test
		$articles = split("\n", $msg);
		
		if(in_array($wgTitle->getDBkey(), $articles) ) 
			return true;
		else
			return false;
		
	}
	
	function isJSControl() {
		global $wgTitle;
		
		$msg = wfMsg('Js_test'); //JS test
		$articles = split("\n", $msg);
		
		if(in_array($wgTitle->getDBkey(), $articles) ) 
			return true;
		else
			return false;
	}
	
	function isMtv() {
		global $wgTitle;
		
		$titleText = $wgTitle->getDBkey();
		
		if($titleText == "Confess-to-an-Online-Lover-That-You-Are-Hiding-a-Secret")
			return true;
		return false;
	}
	
	function getMtv() {
		$s = "";
			
		$s = "<div class='wh_ad'><div class='side_ad'>"; 
		$s .= "<a href='http://mtvcasting.wufoo.com/forms/mtvs-online-relationship-show-now-casting' target='_blank'>";
		$s .= "<img src='" . wfGetPad('/skins/WikiHow/images/mtv_ad.jpg?1') . "' alt='MTV' /></a>";
		$s .= "</div></div>";
		
		return $s;
	}

	public static function cleanUpCategoryTree($tree) {
		$results = array();
		if (!is_array($tree)) return $results;
		foreach ($tree as $cat) {
			$t = Title::newFromText($cat);
			if ($t)
				$results[]= $t->getText();
		}
		return $results;
	}

	public static function flattenCategoryTree($tree) {
		if (is_array($tree)) {
			$results = array();
			foreach ($tree as $key => $value) {
				$results[] = $key;
				$x = self::flattenCategoryTree($value);
				if (is_array($x))
					return array_merge($results, $x);
				else
					return $results;
			}
		} else {
			$results = array();
			$results[] = $tree;
			return $results;
		}
	}

	public static function getCategoryChannelMap() {
		global $wgMemc;
		$key = wfMemcKey('googlechannel', 'category', 'tree');
		$tree = $wgMemc->get( $key );
		if (!$tree) {
			$tree = array();
			$content = wfMsgForContent('category_ad_channel_map');
			preg_match_all("/^#.*/im", $content, $matches);
			foreach ($matches[0] as $match) {
				$match = str_replace("#", "", $match);
				$cats = split(",", $match);
				$channel= trim(array_pop($cats));
				foreach($cats as $c) {
					$c = trim($c);
					if (isset($tree[$c]))
						$tree[$c] .= ",$channel";
					else
						$tree[$c] = $channel;
				}
			}
			$wgMemc->set($key, $tree, time() + 3600);
		}
		return $tree;

	}
	
	function isHHM() {
		global $wgTitle, $wgUser;

        $sk = $wgUser->getSkin();

		if ( $wgTitle->getNamespace() == NS_CATEGORY && $wgTitle->getPartialURL() == "Home-and-Garden") {
			return true;
		}
		else {
			if($sk->mCategories['Home-and-Garden'] != null) {
				return true;
			}
		}
		
		return false;
	}
	
	function getHhmAd(){
		$s = "";

		if(wikihowAds::isHHM()) {
			$catString = "diy.misc";
			$catNumber = "4777";
			
			$s = wfMsg('adunit-hhm', $catString, $catNumber);
			$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		}
		
		return $s;
	}
 
}

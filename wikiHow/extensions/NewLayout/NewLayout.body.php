<?php

if (!defined('MEDIAWIKI')) die();

class NewLayout extends UnlistedSpecialPage {

	const ARTICLE_LAYOUT = '02';

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('NewLayout');
		EasyTemplate::set_path( dirname(__FILE__) );
	}

	public function execute() {
		$this->go();
	}
	
	public function isNewLayoutPage() {
		global $wgTitle, $wgRequest, $wgOut;
		
		//our test articles, ladies and gentlemen...
		$newlayout_array = array(
			'Cheat-a-Polygraph-Test-(Lie-Detector)',
			'Be-Cool',
			'Become-a-CIA-Agent',
			'Lose-Weight',
			'Get-out-of-a-Cellular-Service-Contract',
			'Get-Rid-of-a-Pimple',
			'Be-a-Good-Girlfriend',
			'Be-a-Hipster',
			'Say-"I-Love-You"',
			'Write-a-Script',
			'Get-Rid-of-Scars-and-Cuts-Left-by-Acne',
			'Have-Sex-During-Your-Period',
			'Gauge-Your-Ears',
			'Copy-Your-DVDs-With-Mac-OS-X',
			'Make-a-Guy-Jealous',
			'Tell-if-an-Egg-is-Bad',
			'Take-Erotic-Photos-of-Yourself',
			'Talk-to-a-Guy-You-Like',
			'Tune-a-Guitar',
			'Tell-a-Guy-You-Like-Him',
			'Eat-Healthy',
			'Improve-WiFi-Reception',
			'Write-an-Essay',
			'Have-Soft-Shiny-Hair-Inexpensively',
			'Be-a-Ninja',
			'Get-Bigger-Chest-Muscles-(Pecs)',
			'Treat-a-Sunburn',
			'Remove-Mildew-Smell-from-Towels',
			'Print-from-Your-iPhone',
			'Make-3D-Photos',
			'Send-Pictures-from-Your-Cell-Phone-to-Your-Computer',
			'See-in-the-Dark',
			'Stop-Sweet-Cravings',
			'Take-Action-to-Reduce-Global-Warming',
			'Recharge-the-Air-Conditioner-in-a-Car',
			'Make-a-Bong',
			'Wear-a-Mini-Skirt',
			'Make-a-Box-Styled-Gimp',
			'Make-Yourself-Sneeze',
			'Cook-Rice-in-a-Microwave',
			'Make-a-Soda-Bottle-Volcano',
			'Speed-Up-a-Slow-Windows-Computer-for-Free',
			"Get-Rid-of-a-Wasp's-Nest",
			'Be-a-Good-Listener',
			'Paint-Your-Nails',
			'Make-the-Chinese-Staircase-Bracelet',
			'Win-at-Rock,-Paper,-Scissors',
			'Make-Eyes-Look-Bigger',
			'Play-Beer-Pong',
			'Be-a-Singer',
			'Build-with-Steel-Studs',
			'Watch-Movies-and-TV-Online-for-Free'
		);
		
		if ($wgTitle->getNamespace() == NS_MAIN &&
			in_array($wgTitle->getDBkey(),$newlayout_array) &&
			$wgRequest->getVal('oldid') == '' &&
			($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view')) {
				return true;
		}
		else {
			return false;
		}
	}
	
	public function go() {
		$this->displayHtml();
	}
	
	public function displayHtml() {
		global $IP, $wgTitle, $wgOut, $wgUser, $wgRequest, $wgContLanguageCode;
		global $wgLang, $wgContLang, $wgXhtmlDefaultNamespace;
		
		$sk = new SkinWikihowskin();

		$articleName = $wgTitle->getText();
		$partialUrl = $wgTitle->getPartialURL();
		$isMainPage = ( $articleName == wfMsg('mainpage') );
		$action = $wgRequest->getVal('action', 'view');
		//$lang = $this->getSiteLanguage();
		//$deviceOpts = $this->getDevice();
		$pageExists = $wgTitle->exists();

		$randomUrl = '/' . wfMsg('special-randomizer');
		$titleBar = wfMsg('pagetitle', $articleName);
		$canonicalUrl = 'http://' . $IP . '/' . $wgTitle->getPartialURL();
	
		$rtl = $wgContLang->isRTL() ? " dir='RTL'" : '';		
		$head_element = "<html xmlns:fb=\"https://www.facebook.com/2008/fbml\" xmlns=\"{$wgXhtmlDefaultNamespace}\" xml:lang=\"$wgContLanguageCode\" lang=\"$wgContLanguageCode\" $rtl>\n";
		

		$css = '/extensions/min/f/skins/WikiHow/new.css,extensions/wikihow/common/jquery-ui-themes/jquery-ui.css,extensions/wikihow/gallery/prettyPhoto-3.12/src/prettyPhoto.css,extensions/wikihow/NewLayout/NewLayout.css';
		if ($wgUser->getID() > 0) $css .= ',/skins/WikiHow/loggedin.css';
		$css .= '?'.WH_SITEREV;
		
		if ($wgIsDomainTest) {
			$base_href = '<base href="http://www.wikihow.com/" />';
		}
		else {
			$base_href = '';
		}
		
		$out = new OutputPage;
		$headlinks = $out->getHeadLinks();
		
		if (!$wgIsDomainTest) {
			$canonicalUrl = '<link rel="canonical" href="'.$wgTitle->getFullURL().'" />';
		}
		
		//get login/sign up stuff
		$login = "";

		$li = $wgLang->specialPage("Userlogin");
		$lo = $wgLang->specialPage("Userlogout");
		$rt = $wgTitle->getPrefixedURL();
		if ( 0 == strcasecmp( urlencode( $lo ), $rt ) ) {
			$q = "";
		} else {
			$q = "returnto={$rt}";
		}

		if ( $wgUser->getID() ) {
			$uname = $wgUser->getName();
			if (strlen($uname) > 16) { $uname = substr($uname,0,16) . "..."; }
			$login = wfMsg('welcome_back', $wgUser->getUserPage()->getFullURL(), $uname );

			if ($wgLanguageCode == 'en' && $wgUser->isFacebookUser())
				$login =  wfMsg('welcome_back_fb', $wgUser->getUserPage()->getFullURL() ,$wgUser->getName() );
		} else {
			$login =  wfMsg('signup_or_login', $q) . " " . wfMsg('facebook_connect_header', wfGetPad("/skins/WikiHow/images/facebook_share_icon.gif")) ;
		}

		if($wgUser->getID() > 0) { 
			$helplink = $sk->makeLinkObj (Title::makeTitle(NS_CATEGORY, wfMsg('help')) ,  wfMsg('help'));
			$logoutlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Userlogout'), wfMsg('logout'));
			$login .= " | " . $helplink . " | " . $logoutlink;
		}

		$headerVars = array(
			'title' => $titleBar,
			'head_element' => $head_element,
			'base_href' => $base_href,
			'globalvar_script' => Skin::makeGlobalVariablesScript( $this->data ),
			'css' => $css,
			'headlinks' => $headlinks,
			'canon' => $canonicalUrl,
			'headitems' => $wgOut->getHeadItems(),
			'login' => $login,
		);
		
		if($wgUser->getID() > 0) {
			$footer_links = wfMsgExt('site_footer_new', 'parse');
		}
		else {
			$footer_links = wfMsgExt('site_footer_new_anon', 'parse');
		}
		
		if($wgUser->getID() > 0 || $isMainPage) {
			$sub_foot = wfMsg('sub_footer_new', wfGetPad(), wfGetPad());
		}
		else {
			$sub_foot = wfMsg('sub_footer_new_anon', wfGetPad(), wfGetPad());
		}
		
		$footerVars = array(
			'footer_links' => $footer_links,
			'search' => GoogSearch::getSearchBox("cse-search-box-footer").'<br />',
			'cat_list' => $sk->getCategoryList(),
			'sub_foot' => $sub_foot,
			'footertail' => $this->getFooterTail(),
		);

		$article = $wgOut->getHTML();
		$wgOut->clearHTML();
		
		//parse that article text
		$article = call_user_func( 'self::parseArticle_'.self::ARTICLE_LAYOUT, $article );
		
		$wgOut->addHTML( EasyTemplate::html('header_'.self::ARTICLE_LAYOUT.'.tmpl.php', $headerVars) );
		$wgOut->addHTML($article);
		$wgOut->addHTML( EasyTemplate::html('footer_'.self::ARTICLE_LAYOUT.'.tmpl.php', $footerVars) );			
		
		print $wgOut->getHTML();
	
	}
	
	public function parseArticle_01($article) {
		global $wgWikiHowSections, $wgTitle, $wgUser;
		
		$ads = $wgUser->getID() == 0;
		
		$sk = new SkinWikihowskin();
		
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
		
		foreach ($wgWikiHowSections as $section) {
			$reverse_msgs[wfMsg($section)] = $section;
		}

		$parts = preg_split("@(<h2.*</h2>)@im", $article, 0, PREG_SPLIT_DELIM_CAPTURE);
		$body= '';
		$section_menu = '';
		$intro_img = '';
		for ($i = 0; $i < sizeof($parts); $i++) {
			if ($i == 0) {
				//intro
				preg_match("/Image:(.*)\">/", $parts[$i], $matches);
				if (count($matches) > 0) {
					$img = $matches[1];
					$img = preg_replace('@%27@',"'",$img);
					$image = Title::makeTitle(NS_IMAGE, $img);

					if ($image) {
						$file = wfFindFile($image);

						if ($file) {
							$thumb = $file->getThumbnail(200, -1, true, true);
							$intro_img = '<a href="'.$image->getFullUrl().'"><img border="0" width="200" class="mwimage101" src="'.wfGetPad($thumb->url).'" alt="" /></a>';
						}
					}
				}
				if ($intro_img == '') {
						$intro_img = '<img border="0" width="200" class="mwimage101" src="'.wfGetPad('/skins/WikiHow/images/wikihow_sq_200.png').'" alt="" />';
				}
				
				$r = Revision::newFromTitle($wgTitle);
				$intro_text = Wikitext::getIntro($r->getText());
				$intro_text = trim(Wikitext::flatten($intro_text));
				
				$body .= '<br /><div id="color_div"></div><br />';
				
				$body .= '<div id="article_intro">'.$intro_text.'</div>';
				
				if ($ads) {
					$body .= '<div class="ad_noimage intro_ad">' . WikiHowTemplate::getAdUnitPlaceholder('intro') . '</div>';
				}
				
				
				$section_menu .= '<li><a href="#">Summary</a></li>';
			}			
			else if (stripos($parts[$i], "<h2") === 0 && $i < sizeof($parts) - 1) {
				preg_match("@<span>.*</span>@", $parts[$i], $matches);
				$rev = "";
				if (sizeof($matches) > 0) {
					$h2 =  trim(strip_tags($matches[0]));
					$rev = isset($reverse_msgs[$h2]) ? $reverse_msgs[$h2] : "";
				}
				
				if ($rev !== 'steps') {
					$body .= $parts[$i];
				}
				
				$i++;
				if ($rev == "steps") {
					$body .= "\n<div id=\"steps\" class='editable'>{$parts[$i]}</div>\n";
				} else if ($rev != "") {
					$body .= "\n<div id=\"{$rev}\" class='article_inner editable'>{$parts[$i]}</div>\n";
				} else {
					$body .= "\n<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
				
				$section_menu .= '<li><a href="#'.$rev.'">'.$h2.'</a></li>';
			} 
			else {
				$body .= $parts[$i];
			}

		}

		$punct = "!\.\?\:"; # valid ways of ending a sentence for bolding
		$i = strpos($body, '<div id="steps"');
		if ($i !== false) $j = strpos($body, '<div id=', $i+5);
		if ($j === false) $j = strlen($body);
		if ($j !== false && $i !== false) {
			$steps = substr($body, $i, $j - $i);
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE  | PREG_SPLIT_NO_EMPTY);
			$numsteps = preg_match_all('/<li>/m',$steps, $matches );
			$level = 0;
			$steps = "";
			$upper_tag = "";
			$levelstack = array();
			$tagstack = array();
			$current_tag = "";
			$current_li = 1;
			$donefirst = false; // used for ads to tell when we've put the ad after the first step

			if ($numsteps < 100) {

				while ($p = array_shift($parts)) {
					switch (strtolower($p)) {
						case "<ol>":
							$level++;
							if ($level == 1)  {
								$p = '<ol class="steps_list">';
								$upper_tag = "ol";
							} else {
								$p = "&nbsp;<div class='listbody'>{$p}";
							}
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ol";
							$levelstack[] = $current_li;
							$current_li = 1;
							break;
						case "<ul>":
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ul";
							$levelstack[] = $current_li;
							$level++;
							break;
						case "</ol>":
						case "</ul>":
							$level--;
							if ($level == 0) $upper_tag = "";
							$current_tag = array_pop($tagstack);
							$current_li = array_pop($levelstack);
							break;
						case "<li>":
							$closecount = 0;
							if ($level == 1 && $upper_tag == "ol") {
								$li_number = $current_li++;
								//$p = '<li>'. str_pad($li_number,2,'0',STR_PAD_LEFT);
								$p = '<li>';
								# this is where things get interesting. Want to make first sentence bold!
								# but we need to handle cases where there are tags in the first sentence
								# split based on HTML tags
								$next = array_shift($parts);
								$htmlparts = preg_split("@(<[^>]*>)@im", $next,
									0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
								$dummy = 0;
								$incaption = false;
								$apply_b = false;
								$the_big_step = $next;
								while ($x = array_shift($htmlparts)) {
									# if it's a tag, just append it and keep going
									if (preg_match("@(<[^>]*>)@im", $x)) {
										//tag
										$p .= $x;
										if ($x == "<span class='caption'>")
											$incaption = true;
										else if ($x == "</span>" && $incaption)
											$incaption = false;
										continue;
									}
									# put the closing </b> in if we hit the end of the sentence
									if (!$incaption) {
										if (!$apply_b && trim($x) != "") {
											$p .= '<p class="step_head"><span>'. str_pad($li_number,2,'0',STR_PAD_LEFT).'</span>';
											$apply_b = true;
										}
										if ($apply_b) {
											$x = preg_replace("@([{$punct}])@im", "$1</p>", $x, 1, &$closecount);
										}
									}
									$p .= $x;
										
									if ($closecount > 0) {
										break;
									} else {
										#echo "\n\n-----$x----\n\n";
									}
									$dummy++;
								}
																
								# get anything left over
								$p .= implode("", $htmlparts);
								
								if ($closecount == 0) $p .= "</b>"; // close the bold tag if we didn't already
								if ($level == 1 && $current_li == 2 && $ads && !$donefirst) {
									$p .= '<br class="clearall" />'.WikiHowTemplate::getAdUnitPlaceholder(0);
									$donefirst = true;
								}								

							}
							break;
						case "</li>":
							$p = "<div class='clearall'></div>{$p}"; //changed BR to DIV b/c IE doesn't work with the BR clear tag
							break;
					} // switch
					$steps .= $p;
				} // while
			} else {
				$steps = substr($body, $i, $j - $i);
				$steps = "<div id='steps_notmunged'>\n" . $steps . "\n</div>\n";
			}
			
						
			// we have to put the final_li in the last OL LI step, so reverse the walk of the tokens
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE);
			$parts = array_reverse($parts);
			$steps = "";
			$level = 0;
			$gotit = false;
			$donelast = false;
			foreach ($parts as $p) {
				$lp = strtolower($p);
				if ($lp == "</ol>" ) {
					$level++;
					$gotit= false;
				}else if($lp == "</ul>" ){
					$level++;
				} else if (strpos($lp, "<li") !== false && $level == 1 && !$gotit) {
					/// last OL step list fucker
					//$p = preg_replace("@<li[^>]*>@i", '<li class="steps_li final_li">', $p);
					
					$gotit = true;
				} else if (strpos($lp, "<ul") !== false ){
					$level--;
				} else if (strpos($lp, "<ol") !== false ) {
					$level--;
				} else if ($lp == "</li>" && !$donelast) {
					// ads after the last step
					if ($ads){
						if(substr($body, $j) == ""){
							$p = "<script>missing_last_ads = true;</script>" . WikiHowTemplate::getAdUnitPlaceholder(1) . $p;
							$no_third_ad = true;
						}
						else {
							$p = '<br />'.WikiHowTemplate::getAdUnitPlaceholder(2) . $p;
						}
					}
					$donelast = true;
				}
				$steps = $p . $steps;
			}

			$body = substr($body, 0, $i) . $steps . substr($body, $j);
			
		} /// if numsteps == 100?
		
				/// ads below tips, walk the sections and put them after the tips
		if ($ads) {
			$foundtips = false;
			$anchorTag = "";
			foreach ($wgWikiHowSections as $s) {
				$isAtEnd = false;
				if ($s == "ingredients" || $s == "steps")
					continue; // we skip these two top sections
				$i = strpos($body, '<div id="' . $s. '"');
			    if ($i !== false) {
					$j = strpos($body, '<h2>', $i + strlen($s));
				} else {
					continue; // we didnt' find this section
				}
	    		if ($j === false){
					$j = strlen($body); // go to the end
					$isAtEnd = true;
				}
	    		if ($j !== false && $i !== false) {
	        		$section  = substr($body, $i, $j - $i);
					if ($s == "video") {
						// special case for video
						$newsection = "<div id='video'><center>{$section}</center></div>";
						$body = str_replace($section, $newsection, $body);
						continue;
					} else if ($s == "tips") {
						//tip ad is now at the bottom of the tips section
						//need to account for the possibility of no sections below this and therefor
						//no anchor tag
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . WikiHowTemplate::getAdUnitPlaceholder('2a') .'<p><br /></p>' , $body);
						$foundtips = true;
						break;
					} else {
						$foundtips = true;
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . WikiHowTemplate::getAdUnitPlaceholder(2) , $body);
						break;
					}
				}
			}
			if (!$foundtips && !$no_third_ad) { //must be the video section
				//need to put in the empty <p> tag since all the other sections have them for the anchor tags.
				$body .= "<p class='video_spacing'></p>" . WikiHowTemplate::getAdUnitPlaceholder(2);
			}
		}

		$catlinks = $sk->getCategoryLinks($false);
		$authors = $sk->getAuthorFooter();
		if ($authors != "" || is_array($this->data['language_urls']) || $catlinks != "") {
		
		//k, now grab the bottom stuff
		$article_bottom .= '<br />'.wfGetSuggestedTitles($wgTitle).'<br />
							<h2 class="section_head" id="article_info_header">'.wfMsg('article_info').'</h2>
							<div id="article_info" class="article_inner">
								<p>'.$sk->getLastEdited().'</p>
								<p>'. wfMsg('categories') . ':<br/>'.$catlinks.'</p>
								<p>'.$authors.'</p>
							</div><!--end article_info-->';
		}

		if( $wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getNamespace() == NS_MAIN) {
			$article_bottom .= '<div class="final_ad">'. WikiHowTemplate::getAdUnitPlaceholder(7). '</div>';
		}
		$article_bottom .= '
						<div id="final_question">
								'.$userstats.'
								<p><b>'.$sk->pageStats().'</b></p>
								<div id="page_rating">'.RateArticle::showForm().'</div>
								<p></p>
					   </div>  <!--end last_question-->
					</div> <!-- article -->';
					
		//share buttons
		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		$fb = '<div class="like_button"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		$gp1 = '<div class="gplus1_button"><g:plusone size="medium" callback="plusone_vote"></g:plusone></div>';

//		$fb_share = '<div class="like_button like_tools"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		$tb_admin = '<div class="admin_state"><a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a></div>';
		$tb = '<a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a>';	
		
		$the_buttons = '<div id="share_buttons_top">'.$fb;
		if ($wgUser->isSysop() && $wgTitle->userCan('delete')) {
			$the_buttons .= $tb_admin;
		}
		else {
			$the_buttons .= $tb;
		}
		$the_buttons .= $gp1.'</div>';
		
   
		$title = '<h1>How to '.$wgTitle->getText().'</h1>';
		$edited = $sk->getAuthorHeader();
		$section_menu = '<ul>'.$section_menu.'</ul>';

		$sidebar = '<div id="sidenav">'.$intro_img.$section_menu.'</div>';		
		$main = '<div id="article_main">'.$title.$the_buttons.$edited.$body.$article_bottom.'</div>';	
		$article = '<div id="article_layout_'.self::ARTICLE_LAYOUT.'">'.$sidebar.$main.'</div>';
		
		return $article;
	}
	
	public function parseArticle_02($article) {
		global $wgWikiHowSections, $wgTitle, $wgUser;
		
		$ads = $wgUser->getID() == 0;
		
		$sk = new SkinWikihowskin();
		
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
		
		foreach ($wgWikiHowSections as $section) {
			$reverse_msgs[wfMsg($section)] = $section;
		}

		$parts = preg_split("@(<h2.*</h2>)@im", $article, 0, PREG_SPLIT_DELIM_CAPTURE);
		$body= '';
		$intro_img = '';
		for ($i = 0; $i < sizeof($parts); $i++) {
			if ($i == 0) {
				//intro
				preg_match("/Image:(.*)\">/", $parts[$i], $matches);
				if (count($matches) > 0) {
					$img = $matches[1];
					$img = preg_replace('@%27@',"'",$img);
					$image = Title::makeTitle(NS_IMAGE, $img);

					if ($image) {
						$file = wfFindFile($image);

						if ($file) {
							$thumb = $file->getThumbnail(200, -1, true, true);
							$intro_img = '<a href="'.$image->getFullUrl().'"><img border="0" width="200" class="mwimage101" src="'.wfGetPad($thumb->url).'" alt="" /></a>';
						}
					}
				}
				if ($intro_img == '') {
						$intro_img = '<img border="0" width="200" class="mwimage101" src="'.wfGetPad('/skins/WikiHow/images/wikihow_sq_200.png').'" alt="" />';
				}
				
				$r = Revision::newFromTitle($wgTitle);
				$intro_text = Wikitext::getIntro($r->getText());
				$intro_text = trim(Wikitext::flatten($intro_text));
				
				$body .= '<br /><div id="color_div"></div><br />';
				
				$body .= '<div id="article_intro">'.$intro_text.'</div>';
				
				if ($ads) {
					$body .= '<div class="ad_noimage intro_ad">' . WikiHowTemplate::getAdUnitPlaceholder('intro') . '</div>';
				}
			}			
			else if (stripos($parts[$i], "<h2") === 0 && $i < sizeof($parts) - 1) {
				preg_match("@<span>.*</span>@", $parts[$i], $matches);
				$rev = "";
				if (sizeof($matches) > 0) {
					$h2 =  trim(strip_tags($matches[0]));
					$rev = isset($reverse_msgs[$h2]) ? $reverse_msgs[$h2] : "";
				}
				
				if ($rev !== 'steps') {
					$body .= $parts[$i];
				}
				
				$i++;
				if ($rev == "steps") {
					$body .= "\n<div id=\"steps\" class='editable'>{$parts[$i]}</div>\n";
				} else if ($rev != "") {
					$body .= "\n<div id=\"{$rev}\" class='article_inner editable'>{$parts[$i]}</div>\n";
				} else {
					$body .= "\n<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
			} 
			else {
				$body .= $parts[$i];
			}

		}

		$punct = "!\.\?\:"; # valid ways of ending a sentence for bolding
		$i = strpos($body, '<div id="steps"');
		if ($i !== false) $j = strpos($body, '<div id=', $i+5);
		if ($j === false) $j = strlen($body);
		if ($j !== false && $i !== false) {
			$steps = substr($body, $i, $j - $i);
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE  | PREG_SPLIT_NO_EMPTY);
			$numsteps = preg_match_all('/<li>/m',$steps, $matches );
			$level = 0;
			$steps = "";
			$upper_tag = "";
			$levelstack = array();
			$tagstack = array();
			$current_tag = "";
			$current_li = 1;
			$donefirst = false; // used for ads to tell when we've put the ad after the first step

			if ($numsteps < 100) {

				while ($p = array_shift($parts)) {
					switch (strtolower($p)) {
						case "<ol>":
							$level++;
							if ($level == 1)  {
								$p = '<ol class="steps_list">';
								$upper_tag = "ol";
							} else {
								$p = "&nbsp;<div class='listbody'>{$p}";
							}
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ol";
							$levelstack[] = $current_li;
							$current_li = 1;
							break;
						case "<ul>":
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ul";
							$levelstack[] = $current_li;
							$level++;
							break;
						case "</ol>":
							$p .= '<div id="steps_end"></div>';
						case "</ul>":
							$level--;
							if ($level == 0) $upper_tag = "";
							$current_tag = array_pop($tagstack);
							$current_li = array_pop($levelstack);
							break;
						case "<li>":
							$closecount = 0;
							if ($level == 1 && $upper_tag == "ol") {
								$li_number = $current_li++;
								//$p = '<li>'. str_pad($li_number,2,'0',STR_PAD_LEFT);
								$p = '<li>';
								# this is where things get interesting. Want to make first sentence bold!
								# but we need to handle cases where there are tags in the first sentence
								# split based on HTML tags
								$next = array_shift($parts);
								$htmlparts = preg_split("@(<[^>]*>)@im", $next,
									0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
								$dummy = 0;
								$incaption = false;
								$apply_b = false;
								while ($x = array_shift($htmlparts)) {
									# if it's a tag, just append it and keep going
									if (preg_match("@(<[^>]*>)@im", $x)) {
										//tag
										$p .= $x;
										if ($x == "<span class='caption'>")
											$incaption = true;
										else if ($x == "</span>" && $incaption)
											$incaption = false;
										continue;
									}
									# put the closing </b> in if we hit the end of the sentence
									if (!$incaption) {
										if (!$apply_b && trim($x) != "") {
											$p .= '<p class="step_head"><span>'. str_pad($li_number,2,'0',STR_PAD_LEFT).'</span>';
											$apply_b = true;
										}
										if ($apply_b) {
											$x = preg_replace("@([{$punct}])@im", "$1</p>", $x, 1, &$closecount);
										}
									}
									$p .= $x;
										
									if ($closecount > 0) {
										break;
									} else {
										#echo "\n\n-----$x----\n\n";
									}
									$dummy++;
								}
																
								# get anything left over
								$p .= implode("", $htmlparts);
								
								if ($closecount == 0) $p .= "</b>"; // close the bold tag if we didn't already
								if ($level == 1 && $current_li == 2 && $ads && !$donefirst) {
									$p .= '<br class="clearall" />'.WikiHowTemplate::getAdUnitPlaceholder(0);
									$donefirst = true;
								}

							}
							break;
						case "</li>":
							$p = "<div class='clearall'></div>{$p}"; //changed BR to DIV b/c IE doesn't work with the BR clear tag
							break;
					} // switch
					$steps .= $p;
				} // while
			} else {
				$steps = substr($body, $i, $j - $i);
				$steps = "<div id='steps_notmunged'>\n" . $steps . "\n</div>\n";
			}
			
						
			// we have to put the final_li in the last OL LI step, so reverse the walk of the tokens
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE);
			$parts = array_reverse($parts);
			$steps = "";
			$level = 0;
			$gotit = false;
			$donelast = false;
			foreach ($parts as $p) {
				$lp = strtolower($p);
				if ($lp == "</ol>" ) {
					$level++;
					$gotit= false;
				}else if($lp == "</ul>" ){
					$level++;
				} else if (strpos($lp, "<li") !== false && $level == 1 && !$gotit) {
					/// last OL step list fucker
					//$p = preg_replace("@<li[^>]*>@i", '<li class="steps_li final_li">', $p);
					
					$gotit = true;
				} else if (strpos($lp, "<ul") !== false ){
					$level--;
				} else if (strpos($lp, "<ol") !== false ) {
					$level--;
				} else if ($lp == "</li>" && !$donelast) {
					// ads after the last step
					if ($ads){
						if(substr($body, $j) == ""){
							$p = "<script>missing_last_ads = true;</script>" . WikiHowTemplate::getAdUnitPlaceholder(1) . $p;
							$no_third_ad = true;
						}
						else {
							$p = '<br />'.WikiHowTemplate::getAdUnitPlaceholder(2) . $p;
						}
					}
					$donelast = true;
				}
				$steps = $p . $steps;
			}

			$body = substr($body, 0, $i) . $steps . substr($body, $j);
			
		} /// if numsteps == 100?
		
				/// ads below tips, walk the sections and put them after the tips
		if ($ads) {
			$foundtips = false;
			$anchorTag = "";
			foreach ($wgWikiHowSections as $s) {
				$isAtEnd = false;
				if ($s == "ingredients" || $s == "steps")
					continue; // we skip these two top sections
				$i = strpos($body, '<div id="' . $s. '"');
			    if ($i !== false) {
					$j = strpos($body, '<h2>', $i + strlen($s));
				} else {
					continue; // we didnt' find this section
				}
	    		if ($j === false){
					$j = strlen($body); // go to the end
					$isAtEnd = true;
				}
	    		if ($j !== false && $i !== false) {
	        		$section  = substr($body, $i, $j - $i);
					if ($s == "video") {
						// special case for video
						$newsection = "<div id='video'><center>{$section}</center></div>";
						$body = str_replace($section, $newsection, $body);
						continue;
					} else if ($s == "tips") {
						//tip ad is now at the bottom of the tips section
						//need to account for the possibility of no sections below this and therefor
						//no anchor tag
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . WikiHowTemplate::getAdUnitPlaceholder('2a') .'<p><br /></p>' , $body);
						$foundtips = true;
						break;
					} else {
						$foundtips = true;
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . WikiHowTemplate::getAdUnitPlaceholder(2) , $body);
						break;
					}
				}
			}
			if (!$foundtips && !$no_third_ad) { //must be the video section
				//need to put in the empty <p> tag since all the other sections have them for the anchor tags.
				$body .= "<p class='video_spacing'></p>" . WikiHowTemplate::getAdUnitPlaceholder(2);
			}
		}

		$catlinks = $sk->getCategoryLinks($false);
		$authors = $sk->getAuthorFooter();
		if ($authors != "" || is_array($this->data['language_urls']) || $catlinks != "") {
		
		//k, now grab the bottom stuff
		$article_bottom .= '<br />'.wfGetSuggestedTitles($wgTitle).'<br />
							<h2 class="section_head" id="article_info_header">'.wfMsg('article_info').'</h2>
							<div id="article_info" class="article_inner">
								<p>'.$sk->getLastEdited().'</p>
								<p>'. wfMsg('categories') . ':<br/>'.$catlinks.'</p>
								<p>'.$authors.'</p>
							</div><!--end article_info-->';
		}

		if( $wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getNamespace() == NS_MAIN) {
			$article_bottom .= '<div class="final_ad">'. WikiHowTemplate::getAdUnitPlaceholder(7). '</div>';
		}
		$article_bottom .= '
						<div id="final_question">
								'.$userstats.'
								<p><b>'.$sk->pageStats().'</b></p>
								<div id="page_rating">'.RateArticle::showForm().'</div>
								<p></p>
					   </div>  <!--end last_question-->
					</div> <!-- article -->';
					
		//share buttons
		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		$fb = '<div class="like_button"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		$gp1 = '<div class="gplus1_button"><g:plusone size="medium" callback="plusone_vote"></g:plusone></div>';

//		$fb_share = '<div class="like_button like_tools"><fb:like href="' . $url . '" send="false" layout="button_count" width="86" show_faces="false"></fb:like></div>';
		$tb_admin = '<div class="admin_state"><a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a></div>';
		$tb = '<a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a>';	
		
		$the_buttons = '<div id="share_buttons_top">'.$fb;
		if ($wgUser->isSysop() && $wgTitle->userCan('delete')) {
			$the_buttons .= $tb_admin;
		}
		else {
			$the_buttons .= $tb;
		}
		$the_buttons .= $gp1.'</div>';
		
   
		$title = '<h1>How to '.$wgTitle->getText().'</h1>';
		$edited = $sk->getAuthorHeader();

		$sidebar = '<div id="sidenav"><div id="showslideshow"></div><div id="pp_big_space">'.$intro_img.'</div></div>';		
		$main = '<div id="article_main">'.$title.$the_buttons.$edited.$body.$article_bottom.'</div>';	
		$article = '<div id="article_layout_'.self::ARTICLE_LAYOUT.'">'.$sidebar.$main.'</div>';
		
		return $article;
	}
	
	
	public function getFooterTail() {
		global $wgUser, $wgTitle, $wgRequest;
		
		$sk = new SkinWikihowskin();
		
		$footertail = WikiHowTemplate::getPostLoadedAdsHTML();
		
		$trackData = array();
		// Data analysis tracker

		if (class_exists('CTALinks') && /*CTALinks::isArticlePageTarget() &&*/ trim(wfMsgForContent('data_analysis_feature')) == "on" && !CTALinks::isLoggedIn() && $wgTitle->getNamespace() == NS_MAIN ) {
			// Ads test for logged out users on article pages
			$footertail .= wfMsg('data_analysis');
		}

		$footertail .= wfMsg('client_data_analysis');

		// Intro image on/off
		if (class_exists('CTALinks') && CTALinks::isArticlePageTarget()) {
			$trackData[] = ($sk->hasIntroImage()) ? "introimg:yes" : "introimg:no";
		}

		// Account type
		global $wgCookiePrefix;
		if (isset($_COOKIE[$wgCookiePrefix . 'acctTypeA'])) {
			// cookie value is "<userid>|<acct class>"
			$cookieVal =  explode("|", $_COOKIE[$wgCookiePrefix . 'acctTypeA']);
			// Only track if user is logged in with same account the cookie was created for
			if ($wgUser->getID() == $cookieVal[0]) {
				$trackData[] = "accttype:class{$cookieVal[1]}";
			}
		}

		// Another Cohort test. Only track cohorts after they return from initial account creation session
		if (isset($_COOKIE[$wgCookiePrefix . 'acctTypeB']) && !isset($_COOKIE[$wgCookiePrefix . 'acctSes'])) {
			// cookie value is "<userid>|<acct class>"
			$cookieVal =  explode("|", $_COOKIE[$wgCookiePrefix . 'acctTypeB']);
			// Only track if user is logged in with same account the cookie was created for
			if ($wgUser->getID() == $cookieVal[0]) {
				$trackData[] = "acctret:{$cookieVal[1]}";
			}
		}

		// Logged in/out
		$trackData[] = ($wgUser->getId() > 0) ? "usertype:loggedin" : "usertype:loggedout";

		$nsURLs = array(NS_USER => "/User", NS_USER_TALK => "/User_talk", NS_IMAGE => "/Image");
		$gaqPage = $nsURLs[$wgTitle->getNamespace()];
		$trackUrl = sizeof($gaqPage) ? $gaqPage : $wgTitle->getFullUrl();
		$trackUrl = str_replace("$wgServer$wgScriptPath", "", $trackUrl);
		$trackUrl = str_replace("http://www.wikihow.com", "", $trackUrl);
		$trackUrl .= '::';
		$trackUrl .= "," . implode(",", $trackData) . ",";
		
		$footertail .= "
<script type=\"text/javascript\">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2375655-1']);
  _gaq.push(['_setDomainName', '.wikihow.com']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_trackPageLoadTime']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = \"http://pad1.whstatic.com/skins/common/ga.js?<?=WH_SITEREV?>\";
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<!-- Google Analytics Event Track -->
<? //merged with other JS above: <script type=\"text/javascript\" src=\"".wfGetPad('/skins/WikiHow/gaWHTracker.js')."\"></script>?>
<script type=\"text/javascript\">
if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
	jQuery(window).load(gatStartObservers);
} else {
	Event.observe(window, 'load', gatStartObservers);
}
</script>
<!-- END Google Analytics Event Track -->";

		if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
			$footertail .= CTALinks::getGoogleControlTrackingScript();
			$footertail .= CTALinks::getGoogleConversionScript();
		}

		$footertail .= '<!-- LOAD EVENT LISTENERS -->';
		
		if ($wgTitle->getPrefixedURL() == wfMsg('mainpage') && $wgLanguageCode == 'en') {
			$footertail .= "
				<script type=\"text/javascript\">
				if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
					jQuery(window).load(initSA);
				} else {
					Event.observe(window, 'load', initSA);
				}
				</script>";
		}

		$footertail .= "<!-- LOAD EVENT LISTENERS ALL PAGES -->
		<div id='img-box'></div>";

		if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
			$footertail .= CTALinks::getBlankCTA();
		}

		// QuickBounce test
		if (false && $sk->isQuickBounceUrl('ryo_urls')) {

		$footertail .= '<!-- Begin W3Counter Secure Tracking Code -->
		<script type="text/javascript" src="https://www.w3counter.com/securetracker.js"></script>
		<script type="text/javascript">
		w3counter(55901);
		</script>
		<noscript>
		<div><a href="http://www.w3counter.com"><img src="https://www.w3counter.com/tracker.php?id=55901" style="border: 0" alt="W3Counter" /></a></div>
		</noscript>
		<!-- End W3Counter Secure Tracking Code-->';

		}

		$footertail .= '</body>';
		
		if (($wgRequest->getVal("action") == "edit" || $wgRequest->getVal("action") == "submit2") && $wgRequest->getVal('advanced', null) != 'true') {
			$footertail .= "<script type=\"text/javascript\">
			if (document.getElementById('steps') && document.getElementById('wpTextbox1') == null) {
					InstallAC(document.editform,document.editform.q,document.editform.btnG,\"./".$wgLang->getNsText(NS_SPECIAL).":TitleSearch"."\",\"en\");
			}
			</script>";
		}
	}
}

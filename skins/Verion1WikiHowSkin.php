<?php
/**
 * WikiHow nouveau
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die();

/** */
require_once('includes/SkinTemplate.php');

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinWikihowskin extends SkinTemplate {
	/** Using WikiHow. */


	// For google adsense
	public $mGlobalChannels	= array();
	public $mGlobalComments	= array();
	public $mAuthors;

	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'WikiHow';
		$this->stylename = 'WikiHow';
		$this->template  = 'WikiHowTemplate';
	}

	function getCategoryList() {
		global $wgOut;
		$t = Title::makeTitle(NS_PROJECT, "Top Categories List");
		if (!$t) return '';
		$r = Revision::newFromTitle($t);
		if (!$r) return '';
		return $wgOut->parse($r->getText());	
	}
	function getLastEdited() {
		global $wgTitle;
		if (!$wgTitle || $wgTitle->getNamespace() != NS_MAIN) return '';
		$dbr = wfGetDB(DB_SLAVE);
		$maxts =  $dbr->selectField("revision", array("max(rev_timestamp)"), array("rev_page"=>$wgTitle->getArticleID()));
		$user_text =  $dbr->selectField("revision", array("rev_user_text"), array("rev_page"=>$wgTitle->getArticleID(), "rev_timestamp" => $maxts));
		$user_id=  $dbr->selectField("revision", array("rev_user"), array("rev_page"=>$wgTitle->getArticleID(), "rev_timestamp" => $maxts));
		$u = User::newFromName($user_text);
		$ts = wfTimestamp(TS_UNIX, $maxts);
		if ($user_id == 0 || !$u) {
			$t = "/wikiHow:Anonymous";
			return wfMsg('last_edited_by', date("F j, Y", $ts), wfMsg('anonymous'), $t);
		} else {
			return wfMsg('last_edited_by', date("F j, Y", $ts), $u->getName() , $u->getUserPage()->getFullURL());
		}
	}	
	function pageStats() {
		global $wgOut, $wgLang, $wgArticle, $wgRequest;
		global $wgDisableCounters, $wgMaxCredits, $wgShowCreditsIfMax;

		extract( $wgRequest->getValues( 'oldid', 'diff' ) );
		if ( ! $wgOut->isArticle() ) { return ''; }
		if ( isset( $oldid ) || isset( $diff ) ) { return ''; }
		if ( $wgArticle == null || 0 == $wgArticle->getID() ) { return ''; }

		$s = '';
		if ( !$wgDisableCounters ) {
			$count = $wgLang->formatNum( $wgArticle->getCount() );
			if ( $count ) {
				$s = wfMsg( 'viewcount', $count );
			}
		}

		return $s;
	}

	function userTalkLink( $userId, $userText ) {
		global $wgLang;
		$talkname = wfMsg('talk'); //$wgLang->getNsText( NS_TALK ); # use the shorter name

		$userTalkPage = Title::makeTitle( NS_USER_TALK, $userText );
		$userTalkLink = $this->makeLinkObj( $userTalkPage, $talkname );
		return $userTalkLink;
	}

	/**
	 * @param $userId Integer: user id in database.
	 * @param $userText String: user name in database.
	 * @return string HTML fragment with talk and/or block links
	 * @private
	 */
	function userToolLinks( $userId, $userText ) {
		global $wgUser, $wgDisableAnonTalk, $wgSysopUserBans, $wgTitle, $wgLanguageCode, $wgRequest, $wgServer;
		$talkable = !( $wgDisableAnonTalk && 0 == $userId );
		$blockable = ( $wgSysopUserBans || 0 == $userId );

		$items = array();
		if( $talkable ) {
			$items[] = $this->userTalkLink( $userId, $userText );
		}

		//XXMOD Added for quick note feature
		if (($wgTitle->getNamespace() != NS_SPECIAL) && 
			 ($wgLanguageCode =='en') &&
			 ($wgRequest->getVal("diff", "") != "")) {  

			$editor = User::newFromId( $userId );
			$editor->loadFromId();
			$regdate = $editor->getRegistration();
			if ($regdate != "") {
				$datetime = new DateTime("".substr($regdate,0,4)."-".substr($regdate,4,2)."-".substr($regdate,6,2)." ".
							substr($regdate,8,2).":".substr($regdate,10,2).":". substr($regdate,12,4) ) ;
				$regdate = $datetime->format('M j, Y');
			}

			$contrib = number_format(User::getAuthorStats($userText), 0, "", ",");
 			$items[] = "<a href=\"#\"  onclick=\"initQuickNote('".urlencode($wgTitle->getPrefixedText())."','".$userText."','".$contrib."','".$regdate."') ;\">quick note</a>";

			//XX QUICK edit removed and placed on line above
			//$editURL = $wgServer . '/Special:Newarticleboost?type=editform&target=' . urlencode($wgTitle->getFullText());
 			//$items[] = "<a href=\"#\"  onclick=\"initPopupEdit('".$editURL."') ;\">edit</a>";
		}

			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $userText );
			$items[] = $this->makeKnownLinkObj( $contribsPage ,
				wfMsgHtml( 'contribslink' ) );
				
			if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Recentchanges" && $wgUser->isAllowed('patrol') ) {
				$contribsPage = SpecialPage::getTitleFor( 'Bunchpatrol', $userText );
				$items[] = $this->makeKnownLinkObj( $contribsPage , 'bunch' );
			}
		if( $blockable && $wgUser->isAllowed( 'block' ) ) {
			$items[] = $this->blockLink( $userId, $userText );
		}

		if( $items ) {
			return ' (' . implode( ' | ', $items ) . ')';
		} else {
			return '';
		}
	}

	function generateRollback( $rev ) {
                global $wgUser, $wgRequest, $wgTitle;
                $title = $rev->getTitle();
        
                $extraRollback = $wgRequest->getBool( 'bot' ) ? '&bot=1' : '';
                $extraRollback .= '&token=' . urlencode(
                        $wgUser->editToken( array( $title->getPrefixedText(), $rev->getUserText() ) ) );

				if ($wgTitle->getNamespace() == NS_SPECIAL)
					return Skin::generateRollback($rev);
	
		$url = $title->getFullURL() . "?action=rollback&from=" . urlencode( $rev->getUserText() ). str_replace("%", "\\\\%", $extraRollback);
		$url = $title->getFullURL() . "?action=rollback&from=" . urlencode( $rev->getUserText() ).  $extraRollback;
		$s  = "<script type='text/javascript'>
				var gRollbackurl = \"{$url}\";
			</script>
			<script type='text/javascript' src='/extensions/wikihow/rollback.js'></script>
			<span class='mw-rollback-link' id='rollback-link'>
			<script type='text/javascript'>
				document.write(\"[<a href='javascript:rollback(true);'>" . wfMsg('rollbacklink') . "</a>]\"); 
			</script>
			<noscript>" . Skin::generateRollback($rev) . "
			</noscript>
			</span>";
		return $s;

	}	
    function makeHeadline( $level, $attribs, $anchor, $text, $link ) {
		if ($level == '2') {
        	return "<a name=\"$anchor\"></a><h$level$attribs $link<span>$text</span></h$level>";
		}
       	return "<a name=\"$anchor\"></a><h$level$attribs <span>$text</span></h$level>";
    }	

    public function editSectionLink( $nt, $section, $hint='' ) {
        global $wgContLang;
            
        $editurl = '&section='.$section;
        $hint = ( $hint=='' ) ? '' : ' title="' . wfMsgHtml( 'editsectionhint', htmlspecialchars( $hint ) ) . '"';
        $url = $this->makeKnownLinkObj( $nt, wfMsg('editsection'), 'action=edit'.$editurl, '', '', 'class="button button50" onmouseover="button_swap(this);" onmouseout="button_unswap(this);"',  $hint );
   		return $url;         
    }

    /** @todo document */
    function makeExternalLink( $url, $text, $escape = true, $linktype = '', $ns = null ) {
        $style = $this->getExternalLinkAttributes( $url, $text, 'external ' . $linktype );
        global $wgNoFollowLinks, $wgNoFollowNsExceptions;
        if( $wgNoFollowLinks && !(isset($ns) && in_array($ns, $wgNoFollowNsExceptions)) && strpos(strtolower($url), "http://www.ehow.com") === false) {
            $style .= ' rel="nofollow"';
        }
        $url = htmlspecialchars( $url );
        if( $escape ) {
            $text = htmlspecialchars( $text );
        }
        return '<a href="'.$url.'"'.$style.'>'.$text.'</a>';
    }
      

	function makeBrokenLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) {

		# Fail gracefully
		if ( ! isset($nt) ) {
			# wfDebugDieBacktrace();
			return "<!-- ERROR -->{$prefix}{$text}{$trail}";
		}

		$fname = 'Skin::makeBrokenLinkObj';
		wfProfileIn( $fname );

		$u = $nt->getLocalURL();

		if ( '' == $text ) {
			$text = htmlspecialchars( $nt->getPrefixedText() );
		}
		if ($nt->getNamespace() >= 0) 	
			$style = $this->getInternalLinkAttributesObj( $nt, $text, "new" );
		else
			$style = $this->getInternalLinkAttributesObj( $nt, $text, "" );

		$inside = '';
		if ( '' != $trail ) {
			if ( preg_match( '/^([a-z]+)(.*)$$/sD', $trail, $m ) ) {
				$inside = $m[1];
				$trail = $m[2];
			}
		}
		$s = "<a href=\"{$u}\"{$style}>{$prefix}{$text}{$inside}</a>{$trail}";
		/*
		if ( $this->mOptions['highlightbroken'] ) {
			$s = "<a href=\"{$u}\"{$style}>{$prefix}{$text}{$inside}</a>{$trail}";
		} else {
			$s = "{$prefix}{$text}{$inside}<a href=\"{$u}\"{$style}>?</a>{$trail}";
		}
         */
		wfProfileOut( $fname );
		return $s;
	}


	/***
		User links feature, users can get a list of their own links by speciffying a list in User:username/Mylinks
	****/
	function getUserLinks() {
		global $wgUser, $wgParser, $wgTitle;
		$ret = "";
		if ($wgUser->getID() > 0) {
			$t = Title::makeTitle(NS_USER, $wgUser->getName() . "/Mylinks");
			if ($t->getArticleID() > 0) {
				$r = Revision::newFromTitle($t);
				$text = $r->getText();
				if ($text != "") {
					$ret = "<h3>" . wfMsg('mylinks') . "</h3>";
					$options = new ParserOptions();
					$output = $wgParser->parse($text, $wgTitle, $options);
					$ret .= $output->getText();
				}
			}
		}
		return $ret;
	}	

	function getRelatedWikihowsFromSource($num) {
		global $wgTitle, $wgParser;
		$r = Revision::newFromTitle($wgTitle);
		$text = $r->getText();
		$whow = new WikiHow();
		$whow->loadFromText($text);
		$related = $whow->getSection('related wikihows');
		$preg = "/\\|[^\\]]*/";
		$related = preg_replace($preg, "", $related);
		//splice and dice
		$rarray = split("\n", $related);
		$related = implode("\n", array_splice($rarray, 0, $num));
		$options = new ParserOptions();
		$output = $wgParser->parse($related, $wgTitle, $options);
		return $output->getText();
	}

	function hasMajorityPhotos() {
		global $wgTitle;
		$r = Revision::newFromTitle($wgTitle);
		if ($r == null) return false;
		$section = Article::getSection($r->getText(), 1);
	 	$num_steps = preg_match_all ('/^#/im', $section, $matches);
    	$num_step_photos = preg_match_all('/\[\[Image:/', $section, $matches);
		if ($num_steps > 0 && $num_step_photos / $num_steps > 0.5) return true;
		return false;
	}

	function getMetaSubcategories($limit = 3) {
		global $wgTitle;
		$results = array();
		$dbr = wfGetDB(DB_SLAVE); 
		$res = $dbr->select(array('categorylinks', 'page'),
				array('page_namespace', 'page_title'), 
				array('page_id=cl_from', 'page_namespace=' . NS_CATEGORY, 'cl_to'=>$wgTitle->getDBKey()),
				"WikiHowSkin::getMetaSubcategories",
				array('ORDER BY' => 'page_counter desc', 'LIMIT' => ($limit + 1) )
			);
		$requests = wfMsg('requests');
		$count = 0;
		while ($row = $dbr->fetchObject($res)) {
			if ($count == $limit) break;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if (strpos($t->getText(), $requests) === false) {
				$results[] = $t->getText();	
			}	
			$count++;
		}
		$dbr->freeResult($res);
		return $results;	
	}
	function getMetaDescription() {
		global $wgTitle;

		$return = "";
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() == wfMsg('mainpage')) {
            $return = wfMsg('mainpage_meta_description');
		} else if ($wgTitle->getNamespace() == NS_MAIN) {
            if ($this->hasMajorityPhotos())
            	$return = wfMsg('article_meta_description_withphotos', $wgTitle->getText());
			$return = wfMsg('article_meta_description', $wgTitle->getText() );
		} else if ($wgTitle->getNamespace() == NS_CATEGORY) {
			//get keywords
			$subcats = $this->getMetaSubcategories(3);
			$keywords = implode(", ", $subcats);
			if ($keywords != "") 
            	$return = wfMsg('category_meta_description', $wgTitle->getText(), $keywords);
            else
				$return = wfMsg('subcategory_meta_description', $wgTitle->getText(), $keywords);
		} else if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Popularpages"){
			return wfMsg('popularpages_meta_description');
		}
		return $return;

	}
    function getMetaKeywords() {
        global $wgTitle;
            
        $return = "";
        if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() == wfMsg('mainpage')) {
            $return = wfMsg('mainpage_meta_keywords');
        } else if ($wgTitle->getNamespace() == NS_MAIN ) {
            $return = wfMsg('article_meta_keywords', $wgTitle->getText() );
        } else if ($wgTitle->getNamespace() == NS_CATEGORY) {
			$subcats = $this->getMetaSubcategories(10);
            $return = implode(", ", $subcats);
			if (trim($return == "")) {
				$return = wfMsg('category_meta_keywords_default', $wgTitle->getText() );
			}
		} else if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Popularpages"){
			return wfMsg('popularpages_meta_keywords');
		}
        return $return;
   	}         
    
	function getRelatedArticlesBox($e) {
		global $wgTitle, $wgContLang, $wgUser, $wgRequest;

		if ($wgTitle->getNamespace() != NS_MAIN || $wgTitle->getFullText() == wfMsg('mainpage') || $wgRequest->getVal('action') != '') return '';

		$html = $e->data['bodytext'];
		$find = '<div id="relatedwikihows">';
		$i = strpos($html, '<div id="relatedwikihows">');
		$result = "";

		$num = intval(wfMsgForContent('num_related_articles_to_display'));
		if ($num == 0 || $num > 10 || $num < 0)	
			$num = 5;

		$sk = $wgUser->getSkin();
		
		if ($i !== false) {
			$result = "<h3>" . wfMsg('relatedwikihows') . "</h3>\n" . $this->getRelatedWikihowsFromSource($num) ;
		} else {
			$cats = ($wgTitle->getParentCategories());
			$cat1 = '';
			if (is_array($cats) && sizeof($cats) > 0) {
				$keys = array_keys($cats);
				$cat1 = '';
				$found = false;
        		$templates = wfMsgForContent('templates_further_editing');
        		$templates = split("\n", $templates);
        		$templates = array_flip($templates); // make the array associateive. 
				for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
					$t = Title::newFromText($keys[$i]);
					if (isset($templates[$t->getText()]) ) {
						continue;
					}
					$cat1 = $t->getDBKey();
					$found = true;	
					break;
				}
			}
			if ($cat1 != '') {
		     	$sql = "SELECT cl_from FROM $categorylinks"
             			." WHERE cl_from='$cat1'"
             			." AND cl_from <> '0'"
             			." ORDER BY rand limit $num";
				$sk = $wgUser->getSkin();
 				$dbr =& wfGetDB( DB_SLAVE );
				$categorylinks = $dbr->tableName( 'categorylinks' );
				$page = $dbr->tableName( 'page' );
				$res = $dbr->select(array ($categorylinks, $page), 
					'cl_from',
					array ('cl_to' => $cat1, 	
						'cl_from = page_id', 
						'page_namespace' => NS_MAIN, 
						'page_id != ' . $wgTitle->getArticleID()	
					),
					"WikiHowSkin:getRelatedArticlesBox",
					array ('ORDER BY' => 'rand()', 'LIMIT' => $num)
					);
				while ($row = $dbr->fetchObject($res)) {
					$t = Title::newFromID($row->cl_from);
					$result .=  "<li>" . $sk->makeLinkObj($t, $t->getFullText()) . "</li>";
				}
				if ($result != '') {
					$result = "<h3>" . wfMsg('relatedarticles') . "</h3><ul>$result\n</ul>";
				}
			
			}
		}
		return $result;
	} 
	   
	function getUsernameFromTitle () {
    		global $wgTitle;
		$username = $wgTitle->getText();
                $username = ereg_replace("/.*", "", $username);
                $id = User::idFromName($username);
                $real_name = User::whoIsReal($id);
                if ($real_name == "") $real_name = $username;
		return $real_name;
	}
    function getGalleryImage($title, $width, $height) {
        if ($title->getNamespace() == NS_MAIN) {
            $r = Revision::newFromTitle($title);
            $text = $r->getText();
            preg_match("@\[\[Image[^\]]*\]\]@im", $text, $matches);
			foreach($matches as $i) {
				$i = preg_replace("@\|.*@", "", $i);
				$i = preg_replace("@^\[\[@", "", $i);
				$i = preg_replace("@\]\]$@", "", $i);
				$image = Title::newFromText($i);
				$file = wfFindFile($image);
				$ratio = $file->width / $file->height;
				$thumb = $file->getThumbnail($width, $height, true, true);
				//print_r($thumb);
				#echo "from the skin: echo {$thumb->url}\n"; exit;
				return $thumb->url;
			}
        }   
            
    } 	

	function featuredArticlesLine($t, $msg) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$link = $sk->makeKnownLinkObj( $t, $msg);
		$img = $this->getGalleryImage($t, 44, 33);
       	$html .= "<tr><td><span class='rounders2 rounders2_sm rounders2_tan'>
                <a href='{$t->getFullURL()}'><img class='rounders2_img' alt='' src='{$img}' />
                <img class='rounders2_sprite' alt='' src='/skins/WikiHow/images/corner_sprite.png'/>
				</a>
            	</span>
        		</td>
        		<td>{$link}</td></tr>\n";                
		return $html;
	}
	function getFeaturedArticlesBox($dayslimit = 11, $linkslimit = 4 ) {
	    global $wgStylePath, $wgUser, $wgServer, $wgScriptPath, $wgTitle, $wgLang;
	    $sk = $wgUser->getSkin();
		require_once('FeaturedArticles.php');
		$feeds = FeaturedArticles::getFeaturedArticles($dayslimit);
                
        $html = "<h3>" . wfMsg('featuredarticles') . "</h3>\n<table>";
		$now = time(); 
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$count = 0;
        foreach ($feeds as $item) {
			$url = $item[0];
			$d = $item[1];
			if ($d > $now) continue;
          	$url = str_replace("$wgServer$wgScriptPath/", "", $url);
            $url = str_replace("http://www.wikihow.com/", "", $url);
            $f = Title::newFromURL(urldecode($url));
			if ($f)
				$html .= $this->featuredArticlesLine($f, $f->getText() );
			$count++;
			if ($count >= $linkslimit) break;
        }    
		// main page stuff
		if ($dayslimit > 8) {
			$html .= $this->featuredArticlesLine($popular, wfMsg('populararticles'));
			$html .= $this->featuredArticlesLine(Title::makeTitle(NS_SPECIAL, "Randomizer"), wfMsg('or_a_random_article') ) ;
		}
		$html .= "</table>";
        return $html;
	}

	function getRADLinks($use_chikita_sky) {
		global $wgTitle;
		$x = rand(0,1);
		if ($x == 1) {
			//custom rad links landing page
			$rl = Title::makeTitle(NS_SPECIAL, "Radlinks")->getFullURL();
			$channels = $this->getCustomGoogleChannels('rad_left_custom', $use_chikita_sky);
	    	$links = wfMsg('rad_links_link_units_custom', $channels[0], $channels[1], $rl);
		} else {
			$channels = $this->getCustomGoogleChannels('rad_left', $use_chikita_sky);
	    	$links = wfMsg('rad_links_link_units_200x90', $channels[0], $channels[1]);
		}
        $links = preg_replace('/\<[\/]?pre\>/', '', $links);
		return $links;
	}

	// NOT IN USE	
	function getBottomGoogleAds() {
        $links = wfMsg('rad_links_link_units_468x15');
	    $links = preg_replace('/\<[\/]?pre\>/', '', $links);
   	    return $links;
	}

	function getTopCategory() {
		global $wgTitle;
        $parenttree = $wgTitle->getParentCategoryTree();
		$a = $parenttree;
		$last = $a;
		while (sizeof($a) > 0 && $a = array_shift($a) ) {
			$last = $a;
		}
		$keys = array_keys($last);	
		return	str_replace("Category:", "", $keys[0]);

	}	

	function flattenCategoryTree($tree) {
		if (is_array($tree)) {
			$results = array();
			foreach ($tree as $key=>$value) {
				$results[] = $key;
				$x = $this->flattenCategoryTree($value);
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

	function cleanUpCategoryTree($tree) {
		$results = array();
		if (!is_array($tree)) return $results;
		foreach ($tree as $cat) {
			$t = Title::newFromText($cat);
			if ($t) 
				$results[]= $t->getText();
		}
		return $results;
	}

	function getCategoryChannelMap() {
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
	function getCustomGoogleChannels($type, $use_chikita_sky) {

		global $wgTitle, $wgLang;
	
		$channels = array();
		$comments = array();

		$ad = array();
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
        $namespace[NS_ARTICLE_REQUEST]  = '3704957970';
        $namespace[NS_ARTICLE_REQUEST_TALK] = '3704957970';
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

		foreach ($this->mGlobalChannels as $c) {
			$channels[] = $c;
		}
		foreach ($this->mGlobalComments as $c) {
			$comments[] = $c;
		}
	
		# VIdeo
		if ($wgTitle->getNamespace() ==  NS_SPECIAL && $wgTitle->getText() == "Video") {
			$channels[] = "9155858053";
			$comments[] = "video";
		}

		require_once('FeaturedArticles.php');
		$fas = FeaturedArticles::getFeaturedArticles(3);
		foreach ($fas as $fa) {
			if ($fa[0] == $wgTitle->getFullURL()) {
				$comments[] = 'FA';
				$channels[] = '6235263906';
			}
		}

		// do the categories
		$tree = $wgTitle->getParentCategoryTree();
		$tree = $this->flattenCategoryTree($tree);
		$tree = $this->cleanUpCategoryTree($tree);
		
		$map = $this->getCategoryChannelMap();	
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

	function getGoogleAds($use_chikita_sky, $has_related_videos) {
		global $wgTitle, $wgLang,$wgRequest;
	 
		$id = $wgTitle->getArticleID();
		$channels = $this->getCustomGoogleChannels('skyscraper', $use_chikita_sky);
		$kw = "";
		if ($wgTitle->getNamespace() == NS_SPECIAL 
			&& ($wgTitle->getText() == "Search"||$wgTitle->getText() == "LSearch") )
			$kw .= "\ngoogle_kw_type = \"broad\";\ngoogle_kw = \"". htmlspecialchars($_GET['search']) . "\";\n";
		$extra = $has_related_videos ? "+8524756816+" : "+9183940762+";
		$s = wfMsgForContent('custom_ads_5', $channels[0] . $extra, $channels[1], $kw);
		$s = preg_replace('/\<[\/]?pre\>/', '', $s);
		return $s;
	}
	
    function drawCategoryBrowser($tree, &$skin) {
        $return = '';
        foreach ($tree as $element => $parent) {
            $eltitle = Title::NewFromText($element);
			$start = "  /  ";
            if (empty($parent)) {
                # element start a new list
                $return .= "\n";
            } else {
                # grab the others elements
                $return .= $this->drawCategoryBrowser($parent, $skin) ;
            }
            # add our current element to the list
            $return .=  "<li>$start " . $skin->makeLinkObj( $eltitle, $eltitle->getText() )  . "</li>\n" ;
        }
        return $return;
    }

	function getCategoryLinks ($usebrowser) {
		global $wgOut, $wgTitle, $wgUseCategoryBrowser, $wgUser;
		global $wgContLang;

		if( !$usebrowser && count( $wgOut->mCategoryLinks ) == 0 ) return '';
		# Separator
		$sep = "/"; //wfMsgHtml( 'catseparator' );

		// Use Unicode bidi embedding override characters,
		// to make sure links don't smash each other up in ugly ways.
		$dir = $wgContLang->isRTL() ? 'rtl' : 'ltr';
		$embed = "<span dir='$dir'>";
		$pop = '</span>';
		$t = $embed . implode ( "{$pop} | {$embed}" , $wgOut->mCategoryLinks ) . $pop;
		if (!$usebrowser) 
			return $t;
			

		$mainPageObj = Title::newMainPage();
		$sk = $wgUser->getSkin();		

		$categories = $sk->makeLinkObj(Title::newFromText(wfMsg('categories')), wfMsg('categories'));
		$s = "<li class='home'>" . $sk->makeLinkObj($mainPageObj, wfMsg('home')) . "</li> <li> $sep $categories </li>"  ;
		
		# optional 'dmoz-like' category browser. Will be shown under the list
		# of categories an article belong to
		if($usebrowser) {
			$s .= ' ';

			# get a big array of the parents tree
			$parenttree = $wgTitle->getParentCategoryTree();
			if (is_array($parenttree)) {
				$parenttree = array_reverse($wgTitle->getParentCategoryTree());
			} else {
				return $s;
			}

			# Skin object passed by reference cause it can not be
			# accessed under the method subfunction drawCategoryBrowser
			$tempout = explode("\n", $this->drawCategoryBrowser($parenttree, $this) );
			$newarray = array();
			foreach ($tempout as $t) {
				if (trim($t) != "") $newarray[] = $t;
			}
			$tempout = $newarray;
			# Clean out bogus first entry and sort them
			unset($tempout[0]);
			asort($tempout);
			# Output one per line
			//$s .= implode("<br />\n", $tempout);
			$olds = $s;
			$s .= $tempout[1 ]; // this usually works
			if (strpos($s, "/Category:WikiHow") !== false
					|| strpos($tempout[$i], "/Category:Featured") == false
					|| strpos($tempout[$i], "/Category:Nomination") == false
				) {
				for ($i = 1; $i <= sizeof($tempout); $i++) {
					if (strpos($tempout[$i], "/Category:WikiHow") === false
						&& strpos($tempout[$i], "/Category:Featured") == false
						&& strpos($tempout[$i], "/Category:Nomination") == false
					) {
						if ($i == sizeof($tempout))  {
							$link = str_replace("<li>", "<li><b>", $tempout[$i]);
							$link = str_replace("</li>", "</b></li>", $link);
							$s .= $link;
						} else  {
							$s .= $tempout[$i];	
						}
					}
				}
			}
		}
		return $s;
	}	

	function loadAuthors() {
		global $wgUser, $wgTitle;
		if (is_array($this->mAuthors)) 
			return;
		$this->mAuthors = array();
		$dbr = wfGetDB(DB_SLAVE); 
		$res = $dbr->select('revision', 
				array('rev_user', 'rev_user_text'), 
				array('rev_page'=> $wgTitle->getArticleID()),
				"wikihowskin::loadAuthors",
				array('ORDER BY' => 'rev_timestamp')
			);
		while ($row = $dbr->fetchObject($res)) {
			if ($row->rev_user == 0) {
				$this->mAuthors['anonymous'] = 1;
			} else if (!isset($this->mAuthors[$row->user_text]))  {
				$this->mAuthors[$row->rev_user_text] = 1;
			}	
		}		
	}

	function getAuthorHeader() {
		global $wgTitle, $wgRequest; 
		if (!$wgTitle  || $wgTitle->getNamespace() != NS_MAIN || $wgRequest->getVal('action', 'view') != 'view' 
			|| $wgRequest->getVal('diff') != '') return "";
		$this->loadAuthors();
		$users =  array_slice($this->mAuthors, 0, min(sizeof($this->mAuthors), 4));
		return "<p id='originators'>" . wfMsg('originated_by') . "<span>"
			. $this->formatAuthorList($users) 
			. "</span></p>";
	}

    function getAuthorFooter() {
		global $wgUser;
        $this->loadAuthors();
		if ($wgUser->getID() > 0) {	
			$users = $this->mAuthors;
			$users =  array_slice($users, 0, min(sizeof($users), 100) );
        	return wfMsg('thanks_to_authors') . " " . $this->formatAuthorList($users);
		} else {
			$users = array_reverse($this->mAuthors);
			$users =  array_slice($users, 1, min(sizeof($users) - 1, 3));
        	return wfMsg('most_recent_authors') . " " . $this->formatAuthorList($users);
		}
    }
	
	function formatAuthorList($authors) {
		global $wgTitle, $wgRequest;
		if (!$wgTitle || $wgTitle->getNamespace() != NS_MAIN) return '';
		$action = $wgRequest->getVal('action', 'view');
		if ($action != 'view') return '';
		$links = array();
		foreach ($authors as $u => $p) {
			if ($u == 'anonymous') {
				$links[] = "<a href='/wikiHow:Anonymous'>" .wfMsg('anonymous') . "</a>";
			} else {
				$user = User::newFromName($u);
				if (!$user)  {
					//echo "no user for $u";			
					continue;
				}
				$name = $user->getRealName() != "" ? $user->getRealName() : $user->getName();
				$links[] = "<a href='{$user->getUserPage()->getFullUrl()}'>{$name}</a>";
			}
		}
		$html = implode(", ", $links) .  " (" . $this->makeLinkObj($wgTitle, wfMsg('see_all'), "action=credits")  . ")";
		return $html;	
	}
	
	function outputPage( &$out ) {
		global $wgTitle, $wgArticle, $wgUser, $wgLang, $wgContLang, $wgOut;
		global $wgScript, $wgStylePath, $wgLanguageCode, $wgContLanguageCode, $wgUseNewInterlanguage;
		global $wgMimeType, $wgOutputEncoding, $wgUseDatabaseMessages, $wgRequest;
		global $wgDisableCounters, $wgLogo, $action, $wgFeedClasses;
		global $wgMaxCredits, $wgShowCreditsIfMax, $wgSquidMaxage;

		$fname = 'SkinTemplate::outputPage';
		wfProfileIn( $fname );

// --------------- Kaltura --------------------
#wfRunHooks( 'KalturaBeforePageDisplay', array( &$wgOut ) );
// --------------- Kaltura --------------------

		wfRunHooks( 'BeforePageDisplay', array( &$wgOut ) );
		$this->mTitle = $wgTitle;
		
		extract( $wgRequest->getValues( 'oldid', 'diff' ) );

		$isPrintable = $wgRequest->getVal("printable", "") == "yes";
		if ($isPrintable || $wgTitle->getArticleID() == 0) {
		    if ($wgTitle->getNamespace() != NS_SPECIAL) {	
				$wgOut->setRobotpolicy( "noindex,nofollow" );
			}
		} 	
		
		wfProfileIn( "$fname-init" );
		$this->initPage( $out );
		$tpl =& $this->setupTemplate( $this->template, 'skins' );

		#if ( $wgUseDatabaseMessages ) { // uncomment this to fall back to GetText
		$tpl->setTranslator(new MediaWiki_I18N());
		#}
		wfProfileOut( "$fname-init" );

		wfProfileIn( "$fname-stuff" );
		$this->thispage = $wgTitle->getPrefixedDbKey();
		$this->thisurl = $wgTitle->getPrefixedURL();
		$this->loggedin = $wgUser->getID() != 0;
		$this->iscontent = ($wgTitle->getNamespace() != NS_SPECIAL );
		$this->iseditable = ($this->iscontent and !($action == 'edit' or $action == 'submit'));
		$this->username = $wgUser->getName();
		$this->userpage = $wgContLang->getNsText(NS_USER) . ":" . $wgUser->getName();
		$this->userpageUrlDetails = $this->makeUrlDetails($this->userpage);

		$this->usercss =  $this->userjs = $this->userjsprev = false;
		$this->setupUserCss();
		$this->setupUserJs(false);
		$this->titletxt = $wgTitle->getPrefixedText();
		wfProfileOut( "$fname-stuff" );

		// add utm
		
		wfProfileIn( "$fname-stuff2" );
		$tpl->set( 'title', $wgOut->getPageTitle() );
		$tpl->set( 'pagetitle', $wgOut->getHTMLTitle() );

		$tpl->setRef( "thispage", $this->thispage );
		$subpagestr = $this->subPageSubtitle();
		$tpl->set(
			'subtitle',  !empty($subpagestr)?
			'<span class="subpages">'.$subpagestr.'</span>'.$out->getSubtitle():
			$out->getSubtitle()
		);
		$undelete = $this->getUndeleteLink();
		$tpl->set(
			"undelete", !empty($undelete)?
			'<span class="subpages">'.$undelete.'</span>':
			''
		);

		$description = $this->getMetaDescription();
		if ($description != "") {
			$wgOut->addMeta('description', $description);
		}
		$keywords = $this->getMetaKeywords();
		if ($keywords != "") {
			$wgOut->mKeywords = array();
			$wgOut->addMeta('keywords', $keywords);
		}
		//$tpl->set( 'catlinks', $this->getCategories());
		if( $wgOut->isSyndicated() ) {
			$feeds = array();
			foreach( $wgFeedClasses as $format => $class ) {
				$feeds[$format] = array(
					'text' => $format,
					'href' => $wgRequest->appendQuery( "feed=$format" ),
					'ttip' => wfMsg('tooltip-'.$format)
				);
			}
			$tpl->setRef( 'feeds', $feeds );
		} else {
			$tpl->set( 'feeds', false );
		}
		$tpl->setRef( 'mimetype', $wgMimeType );
		$tpl->setRef( 'charset', $wgOutputEncoding );
		$tpl->set( 'headlinks', $out->getHeadLinks() );
		$tpl->setRef( 'wgScript', $wgScript );
		$tpl->setRef( 'skinname', $this->skinname );
		$tpl->setRef( 'stylename', $this->stylename );
		$tpl->setRef( 'loggedin', $this->loggedin );
		$tpl->set('nsclass', 'ns-'.$wgTitle->getNamespace());
		$tpl->set('notspecialpage', $wgTitle->getNamespace() != NS_SPECIAL);
		/* XXX currently unused, might get useful later
		$tpl->set( "editable", ($wgTitle->getNamespace() != NS_SPECIAL ) );
		$tpl->set( "exists", $wgTitle->getArticleID() != 0 );
		$tpl->set( "watch", $wgTitle->userIsWatching() ? "unwatch" : "watch" );
		$tpl->set( "protect", count($wgTitle->isProtected()) ? "unprotect" : "protect" );
		$tpl->set( "helppage", wfMsg('helppage'));
		*/
		$tpl->set( 'searchaction', $this->escapeSearchLink() );
		$tpl->set( 'search', trim( $wgRequest->getVal( 'search' ) ) );
		$tpl->setRef( 'stylepath', $wgStylePath );
		$tpl->setRef( 'logopath', $wgLogo );
		$tpl->setRef( "lang", $wgContLanguageCode );
		$tpl->set( 'dir', $wgContLang->isRTL() ? "rtl" : "ltr" );
		$tpl->set( 'rtl', $wgContLang->isRTL() );
		$tpl->set( 'langname', $wgContLang->getLanguageName( $wgContLanguageCode ) );
		$tpl->setRef( 'username', $this->username );
		$tpl->setRef( 'userpage', $this->userpage);
		$tpl->setRef( 'userpageurl', $this->userpageUrlDetails['href']);
		$tpl->setRef( 'usercss', $this->usercss);
		$tpl->setRef( 'userjs', $this->userjs);
		$tpl->setRef( 'userjsprev', $this->userjsprev);

	    if( $this->iseditable && $wgUser->getOption( 'editsectiononrightclick' ) ) {
            $tpl->set( 'body_onload', 'setupRightClickEdit()' );
        } else {
            $tpl->set( 'body_onload', false );
        }
		global $wgUseSiteJs;
		if ($wgUseSiteJs) {
			if($this->loggedin) {
				$tpl->set( 'jsvarurl', $this->makeUrl($this->userpage.'/-','action=raw&gen=js&maxage=' . $wgSquidMaxage) );
			} else {
				$tpl->set( 'jsvarurl', $this->makeUrl('-','action=raw&gen=js') );
			}
		} else {
			$tpl->set('jsvarurl', false);
		}
		
		wfProfileOut( "$fname-stuff2" );

		wfProfileIn( "$fname-stuff3" );
		$tpl->setRef( 'newtalk', $ntl );
		$tpl->setRef( 'skin', $this);
		$tpl->set( 'logo', $this->logoText() );
		if ( $wgOut->isArticle() and (!isset( $oldid ) or isset( $diff )) and ($wgArticle != null && 0 != $wgArticle->getID() )) {
			if ( !$wgDisableCounters ) {
				$viewcount =  $wgArticle->getCount() ;
				if ( $viewcount ) {
					$tpl->set('viewcount', wfMsg( "viewcount", $viewcount ));
				} else {
					$tpl->set('viewcount', false);
				}
			} else {
				$tpl->set('viewcount', false);
			}
			$tpl->set('lastmod', $this->lastModified());
			$tpl->set('copyright',$this->getCopyright());

			$this->credits = false;

			if (isset($wgMaxCredits) && $wgMaxCredits != 0) {
				require_once("Credits.php");
				$this->credits = getCredits($wgArticle, $wgMaxCredits, $wgShowCreditsIfMax);
			}

			$tpl->setRef( 'credits', $this->credits );

		} elseif ( isset( $oldid ) && !isset( $diff ) ) {
			$tpl->set('copyright', $this->getCopyright());
			$tpl->set('viewcount', false);
			$tpl->set('lastmod', false);
			$tpl->set('credits', false);
		} else {
			$tpl->set('copyright', false);
			$tpl->set('viewcount', false);
			$tpl->set('lastmod', false);
			$tpl->set('credits', false);
		}
		wfProfileOut( "$fname-stuff3" );

		wfProfileIn( "$fname-stuff4" );
		$tpl->set( 'copyrightico', $this->getCopyrightIcon() );
		$tpl->set( 'poweredbyico', $this->getPoweredBy() );
		$tpl->set( 'disclaimer', $this->disclaimerLink() );
		$tpl->set( 'about', $this->aboutLink() );

		$tpl->setRef( 'debug', $out->mDebugtext );
		$tpl->set( 'reporttime', $out->reportTime() );
		$tpl->set( 'sitenotice', wfGetSiteNotice() );

		//$out->mBodytext .= $printfooter ;
		$tpl->setRef( 'bodytext', $out->mBodytext );

		# Language links
		        # Language links
        $language_urls = array();
        if ( !$wgHideInterlanguageLinks ) {
            foreach( $wgOut->getLanguageLinks() as $l ) {
                $tmp = explode( ':', $l, 2 );
                $class = 'interwiki-' . $tmp[0];
                unset($tmp);
                $nt = Title::newFromText( $l );
                $language_urls[] = array(
                    'href' => $nt->getFullURL(),
                    'text' => ($wgContLang->getLanguageName( $nt->getInterwiki()) != ''?$wgContLang->getLanguageName( $nt->getInterwiki()) : $l),
                    'class' => $class
                );
            }
        }
        if(count($language_urls)) {
            $tpl->setRef( 'language_urls', $language_urls);
        } else {
            $tpl->set('language_urls', false);
        }
		wfProfileOut( "$fname-stuff4" );

		# Personal toolbar
		$tpl->set('personal_urls', $this->buildPersonalUrls());
		$content_actions = $this->buildContentActionUrls();
		$tpl->setRef('content_actions', $content_actions);

		// XXX: attach this from javascript, same with section editing
		if($this->iseditable &&	$wgUser->getOption("editondblclick") )
		{
			$tpl->set('body_ondblclick', 'document.location = "' .$content_actions['edit']['href'] .'";');
		} else {
			$tpl->set('body_ondblclick', false);
		}
		//$tpl->set( 'navigation_urls', $this->buildNavigationUrls() );
		//$tpl->set( 'nav_urls', $this->buildNavUrls() );

		// execute template
		wfProfileIn( "$fname-execute" );
		$res = $tpl->execute();
		wfProfileOut( "$fname-execute" );
	
		// result may be an error
		$this->printOrError( $res );
		wfProfileOut( $fname );
	}
	
	/**
	 * @access public
	 */
	function suppressUrlExpansion() {
		return true;
	}
		
	function suppressH1Tag () {
		global $wgTitle, $wgLang;
		$titleText = $wgTitle->getFullText();
		
		if ($titleText == wfMsg('mainpage'))
			return true;
		if ($titleText == $wgLang->specialPage("Userlogin"))
			return true;
		if ($wgTitle->getNamespace() == NS_ARTICLE_REQUEST)
			return true;
			
		return false;
	}
	
	function generateBulletLink ($title, $msg, $options = null) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		return "<li>" . $sk->makeLinkObj($title, wfMsg($msg), $options) . "</li>";
	}
}
	
class WikiHowTemplate extends QuickTemplate {
	

	//ADDED
	function findCategory($cat, $ptree) {
		if (is_array($ptree)) {
			foreach (array_keys($ptree) as $key) {
				# CODE TO MATCH ON ALL CAT LEVELS
				#if ($cat == str_replace("Category:", "", $key)) {
				#	wfDebug("Vooo - found match for ".$cat."\n");
				#	return true;
				#} else if (is_array($ptree[$key])) {
				#	if ($this->findCategory($cat, $ptree[$key])) {
				#		return true;
				#	}
				#}
	
				$a = $ptree[$key];
				if (is_array($a)) {
					$last = $a;
					while (sizeof($a) > 0 && $a = array_shift($a) ) {
						$last = $a;
					}
					$keys = array_keys($last);	
					if (array_search(str_replace("Category:", "", $keys[0]), $cat)) {
						return true;
					}
				} else {
					if (array_search(str_replace("Category:", "", $key), $cat)) {
						return true;
					}
				}
			}
		} else {
			if (array_search(str_replace("Category:", "", $ptree), $cat)) {
				return true;
			}
		}
		return false;;
	}

	//ADDED
	function getDetailedTitle() {
		global $wgTitle, $wgOut;

		if ($wgTitle->getNamespace() == NS_MAIN) {

			$r = Revision::newFromTitle($wgTitle);
			if ($r == null) return false;

			$text1 = $r->getText();
			if (preg_match("/^(.*?)== ".wfMsg('tips')."/ms", $text1, $sectionmatch)) {
				# has tips, let's assume valid candidate for detailed title
				$num_steps = preg_match_all ('/^#[^*]/im', $sectionmatch[1], $matches);
			}
			$num_step_photos = preg_match_all('/\[\[Image:/im', $text1, $matches);
			$num_step_videos = preg_match_all('/\{\{Video:/', $text1, $matches);

			#$text1 = Article::getSection($r->getText(), 2);
			#$num_step_videos = preg_match_all('/\{\{Video:/', $text1, $matches);


			if (($num_steps < 13) && ($num_steps > 0)) {
				if ($num_step_videos) {
					$titleDetail = ': '.$num_steps.' steps (with video) - wikiHow';
				} else if ($num_step_photos > ($num_steps/2)) {
					$titleDetail = ': '.$num_steps.' steps (with pictures) - wikiHow';
				} else {
					$titleDetail = ': '.$num_steps.' steps - wikiHow';
				}
			} else {
				if ($num_step_videos) {
					$titleDetail = ' (with video) - wikiHow';
				} else if ($num_step_photos > ($num_steps/2)) {
					$titleDetail = ' (with pictures) - wikiHow';
				} else {
					$titleDetail = ' - wikiHow';
				}
			}
			
			# ADD TO CATEGORY LIST HERE
			//$categories = array("STUB","Hobbies-and-Crafts");
			$categories = array("STUB","Hobbies-and-Crafts","Computers-and-Electronics","Personal-Care-and-Style","Food-and-Entertaining");

			if ($this->findCategory($categories,$wgTitle->getParentCategoryTree())) {
				return $titleDetail;
			} else {
				return " - wikiHow";
			}
		}
		return;
	}
	
	//ADDED for QUICKNOTE
	function getQNTemplates() {

		$tb1 = "{{subst:Quicknote_Button1|[[ARTICLE]]}}";
		$tb2 = "{{subst:Quicknote_Button2|[[ARTICLE]]}}";
		$tb3 = "{{subst:Quicknote_Button3|[[ARTICLE]]}}";
	
		$tb1_ary = array();
		$tb2_ary = array();
		$tb3_ary = array();
	
		$tmpl = wfMsg('Quicknote_Templates');
		$tmpls = preg_split('/\n/', $tmpl);
		foreach ($tmpls as $item) {
			if ( preg_match('/^qnButton1=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb1_ary, $value ) ;
			} else if ( preg_match('/^qnButton2=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb2_ary, $value ) ;
			} else if ( preg_match('/^qnButton3=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb3_ary, $value ) ;
			}
		}
	
		if (count($tb1_ary) > 0 ){ $tb1 = $tb1_ary[rand(0,(count($tb1_ary) - 1) )]; }
		if (count($tb2_ary) > 0 ){ $tb2 = $tb2_ary[rand(0,(count($tb2_ary) - 1) )]; }
		if (count($tb3_ary) > 0 ){ $tb3 = $tb3_ary[rand(0,(count($tb3_ary) - 1) )]; }
	
		return array($tb1, $tb2, $tb3);

	}//function getQNTemplates


	/**
	 * Template filter callback for WikiHow skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	 
	function execute() {
		global $wgArticle, $wgScriptPath, $wgUser, $wgLang, $wgTitle, $wgRequest;
		global $wgOut, $wgScript, $wgStylePath, $wgLanguageCode, $wgForumLink;
		global $wgContLang, $wgXhtmlDefaultNamespace, $wgContLanguageCode;	
		$prefix = "";

		$sk = $wgUser->getSkin();
		$cp = Title::newFromText("CreatePage", NS_SPECIAL);

		$sk->mGlobalChannels[] = "1640266093";
		$sk->mGlobalComments[] = "page wide track";
	
		$isWikiHow = false;
		if ($wgArticle != null && $wgTitle->getNamespace() == NS_MAIN)  {
			require_once('WikiHow.php');
			$isWikiHow = WikiHow::articleIsWikiHow($wgArticle);
		}
		$action = $wgRequest->getVal("action", "view");
		if ($wgRequest->getVal('diff') != '') $action = $diff;
		if ($wgRequest->getVal("diff", "") != "") 
			$action = "diff";
		
		$isPrintable = $wgRequest->getVal("printable", "") == "yes";
		
		$contentStyle = "content";
		$bodyStyle = "body_style";
		  
		// set the title and what not
		if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {			
			$real_name = User::whoIsReal(User::idFromName($wgTitle->getDBKey()));
			$name = $wgTitle->getDBKey();
			if (($wgTitle->getNamespace() == NS_USER ||  $wgTitle->getNamespace() == NS_USER_TALK)
			 && $real_name != "") {
				$this->set("pagetitle", $real_name);
				$name = $real_name;
			}
			if ($real_name == "")
			 $real_name = $name;
			 
			if ($wgTitle->getNamespace() == NS_USER_TALK) {
			    $name = $wgLang->getNsText(NS_USER_TALK) . ": $name";
			    $this->set("pagetitle", $name);
			}
			$name .= "&nbsp;<a href=\"$wgScriptPath/".$wgLang->specialPage('Emailuser')."?target=" .
				$wgTitle->getDBKey() . "\"><img src='$wgStylePath/common/images/envelope.png' border='0' alt='".wfMsg('alt_emailuser')."'></a>";
			$this->set("title", $name);
		}
		$title = $this->data['pagetitle'];

		if ($isWikiHow && $action == "view")  {
			if ($wgLanguageCode == 'en') {
				$title = wfMsg('howto', $this->data['title']) . $this->getDetailedTitle();
			} else {
				$title = wfMsg('howto', $this->data['title']) . " - wikiHow";
			}
		}
	
		if ($wgTitle->getFullText() == wfMsg('mainpage'))
			$title = 'wikiHow - '.wfMsg('main_title');

		if ($wgTitle->getNamespace() == NS_CATEGORY) {
			$title = wfMsg('category_title_tag', $wgTitle->getText());
		}
		$talk_namespace = Namespace::getCanonicalName(Namespace::getTalk($wgTitle->getNamespace()));

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
			$login =  wfMsg('welcome_back', $wgUser->getUserPage()->getFullURL(), $wgUser->getName() ); 
		} else	{
			$login = wfMsg('signup_or_login');
		}
	
	
		if (! $sk->suppressH1Tag()) {
			if ($isWikiHow && $action == "view") {
				$heading = "<h1 class=\"firstHeading\"><a href=\"" . $wgTitle->getFullURL() . "\">" . wfMsg('howto', $this->data['title']) . "</a></h1>";
				#$heading = "<h1 class=\"firstHeading\"><a href=\"" . $wgTitle->getFullURL() . "\">" . wfMsg('howto', $wgTitle->getText()) . "</a></h1>";

			} else {
			
				$heading = "<h1 class=\"firstHeading\">" . $this->data['title'] . "</h1>";
			}
		}

		// don't show special options on diff pages, etc.	
		$isDiffPage = $wgRequest->getVal( "diff" ) != null;
		$show_related = true;
		if ($wgArticle != null && 
			($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_TALK)
			&& ! $isDiffPage
			//&& $wgTitle->getPrefixedURL() != "Main-Page" 
			) {
		}  else if ($wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":Search"
				|| $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch"){
			$show_related = false;
		}  else {
			$show_related = false;
			$articleStyle = "article2";
			$bodyContentStyle = "bodyContent2";
		}
		
		$ads = "";
		$featuredBox = "";
			$mainPageRightPanel = "";
		$spell = "";
		$rad_links = "";
		$bottom_ads = "";
		$top_ads = "";

        if ($wgLanguageCode == 'en'
            && $wgTitle->getNamespace() == NS_MAIN
            && $wgTitle->getText() != wfMsg('mainpage')
            && $action == 'view'
            && $wgUser->getID() == 0
        ) {
			// TRACK INTRO PHOTO
			$a = new Article($wgTitle);
			$section = $a->getSection($a->getContent(), 0);
			if (strpos($section, "[[Image") !== false) {
				$sk->mGlobalChannels[] = "9503394424";
				$sk->mGlobalComments[] = "intro photo";	
			} else {
				$sk->mGlobalChannels[] = "9911500640";
				$sk->mGlobalComments[] = "no intro photo";	
			}
	
			// TRACK RATINGS
			$rating = wfGetRatingForArticle($wgTitle->getArticleID(), 20);
			if ($rating < 0) {
			} else if ($rating < 0.25) {
				$sk->mGlobalChannels[] = "0247639506";
				$sk->mGlobalComments[] = "RQ1";	
			} else if ($rating < 0.50) {
				$sk->mGlobalChannels[] = "2974691118";
				$sk->mGlobalComments[] = "RQ2";	
			} else if ($rating < 0.75){
				$sk->mGlobalChannels[] = "5009222491";
				$sk->mGlobalComments[] = "RQ3";	
			} else {
				$sk->mGlobalChannels[] = "9665908481";
				$sk->mGlobalComments[] = "RQ4";
			}

			$height = wfGetPageHeightForArticle($wgTitle);
			if ($height < 0) {
			} else if ($height < 1000) {
				$sk->mGlobalChannels[] = "6529619144";
				$sk->mGlobalComments[] = "PH < 1000";
			} else if ($height < 2000) {
				$sk->mGlobalChannels[] = "3235113743";
				$sk->mGlobalComments[] = "PH 1000-2000";
			} else if ($height < 3000) {
				$sk->mGlobalChannels[] = "3322788282";
				$sk->mGlobalComments[] = "PH 2000-3000";
			} else if ($height < 4000) {
				$sk->mGlobalChannels[] = "3311607322";
				$sk->mGlobalComments[] = "PH 3000-4000";
			} else if ($height < 5000) {
				$sk->mGlobalChannels[] = "0073361934";
				$sk->mGlobalComments[] = "PH 4000-5000";
			} else if ($height < 6000) {
				$sk->mGlobalChannels[] = "6995600621";
				$sk->mGlobalComments[] = "PH 5000-6000";
			} else if ($height < 7000) {
				$sk->mGlobalChannels[] = "6995600621";
				$sk->mGlobalComments[] = "PH 6000-7000";
			} else if ($height < 8000) {
				$sk->mGlobalChannels[] = "8731584958";
				$sk->mGlobalComments[] = "PH 7000-8000";
			} else if ($height < 9000) {
				$sk->mGlobalChannels[] = "2768495332";
				$sk->mGlobalComments[] = "PH 8000-9000";
			} else  {
				$sk->mGlobalChannels[] = "0526258312";
				$sk->mGlobalComments[] = "PH > 10000";
			}


			$dbr = wfGetDB(DB_SLAVE);
			$revisions = $dbr->selectField('revision', array('count(*)'), 
							array('rev_page' => $wgTitle->getArticleID() ));

			if ($revisions <= 3) {
				$sk->mGlobalChannels[] = "9483187321";
				$sk->mGlobalComments[] = "0-3 edits";	
			} elseif ($revisions <= 5) {
				$sk->mGlobalChannels[] = "1730213812";
				$sk->mGlobalComments[] = "3-5 edits";	
			} elseif ($revisions <= 10) {
				$sk->mGlobalChannels[] = "4989269770";
				$sk->mGlobalComments[] = "5-10 edits";	
			} elseif ($revisions <= 20) {
				$sk->mGlobalChannels[] = "7416705818";
				$sk->mGlobalComments[] = "10-20 edits";	
			} elseif ($revisions <= 50) {
				$sk->mGlobalChannels[] = "9016233241";
				$sk->mGlobalComments[] = "20-50 edits";	
			} else {
				$sk->mGlobalChannels[] = "3607312525";
				$sk->mGlobalComments[] = "50+ edits";	
			}	
		}	

		$use_chikita_sky = false;  //rand(0, 4) == 3;
		#$use_chikita_sky = true;

        $rad_links_top = true;
        if (rand(0, 1) == 1) {
            $sk->mGlobalChannels[] =  "8962074949";
            $sk->mGlobalComments[] = "RL Top";
        } else {
            $sk->mGlobalChannels[] =  "8388126455";
            $sk->mGlobalComments[] = "RL Below";
            $rad_links_top = false;
        }
		
        // RELATED VIDEOS 
        $related_vids = "";
        if ($wgTitle->getNamespace() == NS_MAIN && $wgLanguageCode == 'en') {
            $rv = new RelatedVideos($wgTitle, 'wonderhowto');
            if ($rv->hasResults()) {
                $sk->mGlobalChannels[] = "1162261192";
                $sk->mGlobalComments[] = "has related vids";
                $vids = $rv->getResults();
                $related_vids = "<h3>Related Videos</h3><table><tr valign='top'>\n";
				$count = 0;
                foreach ($vids as $v) {
                    $url = "/video/wht/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']);
                    $related_vids  .= "<td><a href='{$url}' class='rounders2 rounders2_ml rounders2_white'><img class='rounders2_img' src='{$v['thumb']}' height='35' width='45'><img class='rounders2_sprite' alt='' src='skins/WikiHow/images/corner_sprite.png'/></a><a href='{$url}'>{$v['title']}</a></td>\n";
					$count++;
					if ($count % 2 == 0)
						$related_vids .= "</tr><tr>";
					if ($count == 4) break;
                }
                $related_vids .= "</tr></table><p id='side_related_videos_more'><a href=''>More related videos</a> <img src='/skins/WikiHow/images/arrow_right.png' id='side_related_videos_arrow' width='11' height='16' alt='&gt;' /></p>";
            } else {
                $sk->mGlobalChannels[] = "8941458308";
                $sk->mGlobalComments[] = "no related vids";
            }
        }
		// do the tool bar/ quick bar
		 if ($wgTitle->getFullText() ==$wgLang->getNsText(NS_SPECIAL).":Search"                
			|| $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch"
			|| $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":GoogSearch") {
            if ($wgUser->getID() == 0) {
                $rad_links = "";
            }
            $show_related = false;
		} else if ( ! $isDiffPage 
				&& $wgTitle->getFullText() != wfMsg('mainpage')
				&& ! $isPrintable 
				&& $wgUser->getId() == 0 )  {
		
				if (!$use_chikita_sky) {
					$ads = $sk->getGoogleAds($use_chikita_sky, $related_vids != "") 	
						. "<a href='javascript:hideads()'>" . wfMsg('hidetheseads') . "</a> - <a href='/wikiHow:Why-Hide-Ads'>Why?</a>";
				} else {
					$cats = $wgTitle->getParentCategories();
					$query = "";
					foreach($cats as $c=>$x) {
						$query = $c;
						break;
					}
					$ads = wfMsg('chikita_ads_skyscraper', $query);
        			$ads = preg_replace('/\<[\/]?pre\>/', '', $ads);
				}
				// SHOW RAD LINKS AFTER SKYSCRAPER
				$rad_links = $sk->getRADLinks($use_chikita_sky);
                $ads = "{$rad_links}$ads";
                //$ads .= "<br/><br/>{$rad_links}";
		} else if ($wgTitle->getFullText() == wfMsg('mainpage') && $action == "view") {
		      $show_related = false;
		} else {
			//$s .= "<td>\n";
			
		}	

		if ($action != "view" || $isPrintable) $show_related = false;
		
		// get the breadcrumbs / category links at the top of the page
		$catlinkstop = $sk->getCategoryLinks(true);
		$catlinksbottom = $sk->getCategoryLinks(false);
		$mainPageObj = Title::newMainPage();
	
		if ($wgTitle->getNamespace() == NS_ARTICLE_REQUEST) {	
			require_once('Request.php');
			$requestTop = Request::getArticleRequestTop();
			$requestBottom = Request::getArticleRequestBottom();
		}
				
		$talk_post_form = "";
		if (Namespace::isTalk($wgTitle->getNamespace()) && $action == "view")
			
	   	if ($isPrintable) {
	   	    // override all of these values for printable versions
		  $contentStyle = "content_printable";
		  $bodyStyle = "body_style_printable";
		  $toolbox = "";  
		  $subTabMenu = "";
		  $box = "";		  
		}
		
		$return_to_article = "";
	
		if (Namespace::isTalk($wgTitle->getNamespace()) && $action == "view") {
			$subject = Namespace::getCanonicalName(Namespace::getSubject($wgTitle->getNamespace()));
			if ($subject != "") {
				$subject .= ":";
			}
			if ($wgTitle->getNamespace() == NS_USER_TALK) {
				//$link = "<a href=\"$wgScriptPath/$subject" . $wgTitle->getDBKey() . "\">".wfMsg('authorpage', $sk->getUsernameFromTitle())."</a>";
				//$return_to_article.= "<br/>".wfMsg('returnto',$link);
				$return_to_article = "<br/>" . $sk->makeLinkObj($wgTitle->getSubjectPage(), wfMsg('returnto', wfMsg('authorpage', $wgTitle->getText())));
			} else {
				$return_to_article = "<br/>" . $sk->makeLinkObj($wgTitle->getSubjectPage(), wfMsg('returnto', $wgTitle->getText()));
			}
		}
	
		
		$related_articles = $sk->getRelatedArticlesBox($this);
	
		$search = "";
		if (isset($_GET['search']) && 
			($wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":Search" || $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch" || $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":GoogSearch")) {
			$search = htmlspecialchars($_GET['search']);
		} else {
		    $search = wfMsg('type_here');
		}

		// QWER links for everyone on all pages
		$cp = Title::makeTitle(NS_PROJECT, wfMsg('communityportal'));
		$cplink = $sk->makeLinkObj ($cp, wfMsg('communityportal') );
		$helplink = $sk->makeLinkObj (Title::makeTitle(NS_CATEGORY, wfMsg('help')) ,  wfMsg('help'));
		$forumlink = '';
		if ($wgForumLink !='') 
			$forumlink = "<a href='$wgForumLink'>" . wfMsg('forums') . "</a>";
		$tourlink = "";
		if ($wgLanguageCode =='en') 
			$tourlink = $sk->makeLinkObj(Title::makeTitle(NS_PROJECT, "Tour"), wfMsg('wikihow_tour')) ;
		$splink = "";
		$splink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Specialpages"), wfMsg('specialpages'));

		$rclink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Recentchanges"), wfMsg('recentchanges'));
		$requestlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RequestTopic"), wfMsg('requesttopic'));
		$listrequestlink = $sk->makeLinkObj( Title::makeTitle(NS_SPECIAL, "ListRequestedTopics"), wfMsg('listrequtestedtopics'));
		$rsslink = "<a href='" . $wgServer . "/feed.rss'>" . wfMsg('rss') . "</a>";
		$rplink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Randompage"), wfMsg('randompage') ) ;

		// For articles only
		$wlhlink = "";
		$statslink = "";
		if ($wgTitle->getNamespace() != NS_SPECIAL && $wgTitle->getFullText() != wfMsg('mainpage'))
			$wlhlink = "<a href='" . Title::makeTitle(NS_SPECIAL, "Whatlinkshere")->getFullURL() . "/" . $wgTitle->getPrefixedURL() . "'>" . wfMsg('whatlinkshere') . "</a>";
			
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage') && $wgLanguageCode == 'en')
			$statslink = "<li>+ " . $sk->makeLinkObj(SpecialPage::getTitleFor("Articlestats", $wgTitle->getText()), wfMsg('articlestats')) . "</li>";
		$mralink = "";
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage')
		 && $wgTitle->userCanEdit() && $wgLanguageCode == 'en')
			$mralink = "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RelatedArticle"), wfMsg('manage_related_articles'), "target=" . $wgTitle->getPrefixedURL()) . "</li>";
		
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage') && $wgTitle->userCanEdit())
		
			$links[] = array (Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL(), wfMsg('recentchangeslinked') );
			
	
		//user stats	
		$userstats = "";
        if ($wgTitle->getNamespace() == NS_USER) {
            $userstats .= "<p id='userstats'>";
            $real_name = $sk->getUsernameFromTitle();
            $username = $wgTitle->getText();
                    $username = ereg_replace("/.*", "", $username);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );
            $userstats .= wfMsg('contributions-made', $real_name, number_format(User::getAuthorStats($username), 0, "", ","), $contribsPage->getFullURL());
            $userstats .= "<br/>";
            $userstats .= "</p>";
        }

		
			
		//Editing Tools
		$uploadlink = "";
		$freephotoslink = "";
		$uploadlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Upload"), wfMsg('upload'));
        $videolink = "";
        if ($wgTitle->getNamespace() == NS_MAIN && $wgUser->getID() > 0 
          && $wgTitle->userCanEdit() && $wgTitle->getText() != wfMsg('mainpage'))  {
	        $videolink = "<li>+ " . $sk->makeLinkObj( SpecialPage::getTitleFor( 'Importvideo', $wgTitle->getText() ), wfMsg('importvideo')) . "</li>";
       }
		$freephotoslink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "ImportFreeImages"), wfMsg('imageimport'));
		$relatedchangeslink = "";
		if ($wgArticle != null)
			$relatedchangeslink = "<a href='" . 
				Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL() . "'>" 
				. wfMsg('recentchangeslinked') . "</a>";

			
		//search
		$searchTitle = Title::makeTitle(NS_SPECIAL, "LSearch");
		// authors
		$authors = $sk->getAuthorFooter();
		
		$createLink = Title::makeTitle(NS_SPECIAL, "CreatePage")->getFullURL();
		
		//anncouncements

		$announcement = "";
		$sitenotice = "";
		if($this->data['sitenotice'])
			$sitenotice = $this->data['sitenotice'];

		$sitenotice = preg_replace("@<[\/]?p>@", "", $sitenotice);
        if( $wgUser->getNewtalk() ) {
            $usertalktitle = $wgUser->getTalkPage();
           	if($usertalktitle->getPrefixedDbKey() != $this->thispage){
				$trail = "";
				if ($wgUser->getOption('scrolltalk')) 
					$trail = "#post";
                $announcement = wfMsg( 'newmessages', $wgUser->getName(),
                			$sk->makeKnownLinkObj(
                    		$usertalktitle,
                    		wfMsg('newmessageslink'), $trail )
                );
            }
        }   
        
        if( $wgUser->getNewkudos() && !$wgUser->getOption('ignorefanmail')) {
            $userkudostitle = $wgUser->getKudosPage();
            if($userkudostitle->getPrefixedDbKey() != $this->thispage) {
                if ($announcement != '') $announcement .= "<br/>";
                $announcement .= wfMsg( 'newfanmail', $wgUser->getName(), $userkudostitle->getPrefixedURL());                # Disable Cache
            }
        }
     
		if ($announcement != "") {
			$announcement = "<div id='message_box'><p class='little_header'>PERSONAL MESSAGE</p>{$announcement}</div>";
			$wgOut->addScript("<script type='text/javascript'>scroll_open('message_box',-52,0);</script>");
		}
		$userlinks = $sk->getUserLinks();
 
		$rtl = $wgContLang->isRTL() ? " dir='RTL'" : '';
                $head_element = "<html xmlns=\"{$wgXhtmlDefaultNamespace}\" xml:lang=\"$wgContLanguageCode\" lang=\"$wgContLanguageCode\" $rtl>\n";
		$rtl_css = "";
		if ($wgContLang->isRTL()) {
			$rtl_css = "<style type=\"text/css\" media=\"all\">/*<![CDATA[*/ @import \"{$this->data['stylepath']}/{$this->data['stylename']}/rtl.css\"; /*]]>*/</style>";
			$rtl_css .= "
   <!--[if IE]>
   <style type=\"text/css\">
   BODY { margin: 25px; }
   </style>
   <![endif]-->";

		}
		$printable_media = "print";
		if ($wgRequest->getVal('printable') == 'yes') 
			$printable_media = "all";

		$featured = false; 
		if (false && $wgTitle->getNamespace() == NS_MAIN) {
			$dbr = wfGetDB(DB_SLAVE);
			$page_isfeatured = $dbr->selectField('page', 'page_is_featured', array("page_id={$wgTitle->getArticleID()}"));
			$featured = ($page_isfeatured == 1);
		}
		//VU Changed back cause it looks funny why i did cvs diff
		//$show_ad_text1 = fale;
		$show_ad_section = false;
		if ($wgLanguageCode == 'en' 
        	&& $wgTitle->getNamespace() == NS_MAIN
            && $wgTitle->getText() != wfMsg('mainpage')
            && $action == 'view'
			&& $wgUser->getID() == 0
		) {

			$channels = $sk->getCustomGoogleChannels('ad_section', $use_chikita_sky);
			$ad_section = wfMsg('ads_by_google_section', $channels[0], $channels[1]);
        	$ad_section = preg_replace('/\<[\/]?pre\>/', '', $ad_section);
			$show_ad_section = true;
		} else {
		}

		$cpimageslink = "";
		global $wgSpecialPages;
		if ($wgSpecialPages['Copyimages'] == 'Copyimages' && $wgLanguageCode != 'en' && $wgTitle->getNamespace() == NS_MAIN) {
			$cpimages = SpecialPage::getTitleFor( 'Copyimages', $wgTitle->getText() );
			$cpimageslink = "<li> " . $sk->makeLinkObj($cpimages, wfMsg('copyimages')) . "</li>";
		}

		$search_results = "";
		if ($wgLanguageCode == 'en'
			&& $wgTitle->getNamespace() == NS_MAIN && $wgTitle->getArticleID() == 0 
			&& $action=="view") {
			require_once('SpecialLSearch.body.php');
			$l = new LSearch();
			$hits = $l->googleSearchResultTitles($wgTitle->getText(), 0, 2);
			$count = 0;
			if (sizeof($hits) > 0) {
				$search_results = "<div style='margin-top:10px; border: 1px solid #ccc; padding: 5px;'>Were you looking for:<ol>";
				foreach  ($hits as $hit) {
					 $t1 = $hit;
                	if ($count == 10) break;
                    if ($t1 == null) continue;
                    $search_results .= "<li style='margin-bottom: 0px'>" . $sk->makeLinkObj($t1, wfMsg('howto', $t1->getText() )) . "</li>\n";
                    $count++;
				}
				$search_results .= "</ol></div>";
			}
		}
		if ($wgLanguageCode == 'en' && $wgTitle->getNamespace() == NS_MAIN && $action == 'view'
			&& $wgTitle->getArticleId() > 0 
			&& $wgTitle->getText() != wfMsg('mainpage')
		) {
			//$bottom_site_notice = Republish::getRepublishFooter($wgTitle);
            //$bottom_site_notice=wfMsgExt('bottomsitenotice', 'parse', $wgTitle->getPrefixedURL());
            //if ($bottom_site_notice == "-") $bottom_site_notice = "";
		}

       $search_box = "";
       if ($wgLanguageCode == 'en') {
			if ($wgUser->getID() == 0) {
            	$search_box = GoogSearch::getSearchBox();
            	$search_box = preg_replace('/\<[\/]?pre\>/', '', $search_box);
			} else {
				$x = rand(0, 100);
				$search_box  = '
				<form style="text-align: right; padding: 31px 11px;" name="search_site' . $x . '" id="search_site" action="' . $searchTitle->getFullURL() . '" method="get">
			<input type="submit" value="GO" class="go_button" id="go_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" /> 				
			Search <input type="text" class="search_box" id="keywords" name="search"/>
			</form>';
			}
       	}
 
		if ($action == "view" && $wgTitle->getNamespace() == NS_MAIN && $wgUser->getID() == 0 && $wgTitle->getText() != wfMsg('mainpage')) {
			$text = $this->data['bodytext'];
			$channels = $sk->getCustomGoogleChannels('embedded_ads', $use_chikita_sky);
			$embed_ads = wfMsg('embedded_ads', $channels[0], $channels[1] );
       		$embed_ads = preg_replace('/\<[\/]?pre\>/', '', $embed_ads);
			if (strpos($text, '<div id="ingredients">') !== false) {
				$text = str_replace('<div id="ingredients">', $embed_ads . "\n" . '<div id="ingredients">', $text);
			} else {
				$text = str_replace('<div id="steps">', $embed_ads . "\n" . '<div id="steps">', $text);
			}	
			// CHIKITA ADS
			$x = rand(0, 1);
			if ($x == 0 || true) {
				$chikita_ads = wfMsg('chikita_ads');
       			$chikita_ads = preg_replace('/\<[\/]?pre\>/', '', $chikita_ads);
			} else {
				$channels = $sk->getCustomGoogleChannels('embedded_ads_mid', $use_chikita_sky);
				$chikita_ads = wfMsg('embedded_ads_middle', $channels[0], $channels[1]);
       			$chikita_ads = preg_replace('/\<[\/]?pre\>/', '', $chikita_ads);
			}
			$sections = array("warnings", "thingsyoullneed", "relatedwikihows", "sources");
			$found = false;
			foreach ($sections as $x) {
				if (strpos($text, "<div id=\"{$x}\">") !== false) {
					$text = str_replace("<div id=\"{$x}\">", $chikita_ads . "\n" . "<div id=\"{$x}\">", $text);
					$found = true;
					break;
				}
			}
			if (!$found) {
				$text .= $chikita_ads;
			}	
			// CHIKITA

			// VIDEO RAD LINKS
			if (strpos($text, '<div id="video">') !== false && strpos($text, "<object") !== false) {
				$channels = $sk->getCustomGoogleChannels('rad_video', $use_chikita_sky);
				$rad_links_video = 	wfMsg('rad_links_video', $channels[0], $channels[1]);
				$rad_links_video = preg_replace('/\<[\/]?pre\>/', '', $rad_links_video);
				$text = str_replace("<object", "<table width='100%'><tr><td valign='top' align='center'><object", $text);
				$text = str_replace("</object>", "</object></td><td valign='top'>{$rad_links_video} </td></tr></table>", $text);
			} 
			$this->data['bodytext'] = $text;
		}

		$ad_setup = wfMsg('ad_setup', $wgTitle ? htmlspecialchars($wgTitle->getText()) : "");
        $ad_setup = preg_replace('/\<[\/]?pre\>/', '', $ad_setup);

		
		// show suggested titles
        if ($wgLanguageCode == 'en'
            && $wgTitle->getNamespace() == NS_MAIN
            && $wgTitle->getText() != wfMsg('mainpage') 
			&& $action == "view"
			&& $wgTitle->getArticleID() > 0
		) {
			$text = $this->data['bodytext'];
			#$text .= wfGetTitlesToImprove($wgTitle);
			$text .= wfGetSuggestedTitles($wgTitle);
			$this->data['bodytext'] = $text;
		}

		if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Video") {
			#$related_vids = Vide-
			$related_articles 	= Video::getRelatedArticlesList();
			$related_vids 		= Video::getRelatedVideosList();
		}

		// hack to get the FA template working, remove after we go live
		$fa = '';
		if (strpos($this->data['bodytext'], 'featurestar') !== false) {
			$fa = '<p id="feature_star"><img src="/skins/WikiHow/images/star.gif" width="15" height="14" alt="*" /> Featured Article</p>';
			$this->data['bodytext'] = preg_replace("@<div id=\"featurestar\">(.|\n)*<div style=\"clear:both\"></div>@mU", '', $this->data['bodytext']);
		}

		// munge the steps HTML to get the numbers working
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getText() != wfMsg('mainpage')) {
			$body = $this->data['bodytext'];

			$i = strpos($body, '<div id="steps">');
			if ($i !== false) $j = strpos($body, '<div id=', $i+5);
			if ($j === false) $j = strlen($body);
			if ($j !== false && $i !== false) {
				$steps = substr($body, $i, $j - $i);
				$steps = preg_replace("@<ol>@", '<ol id="steps_list">', $steps, 1);
				$steps = preg_replace("@<li>@", '<li>&nbsp;<div class="list_body">', $steps);
				$steps = preg_replace("@</li>@", '</div><br class="clearall" /></li>', $steps);
				
				$replace = '</ol>';
				$steps = substr_replace($steps, '</ol><div id="no_last_line"></div>', strrpos($steps,$replace) -1, strlen($replace) + 1); 

				$body = substr($body, 0, $i) . $steps . substr($body, $j);
				$this->data['bodytext'] = $body;
			}
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php echo $head_element;?>
<head>	
	<title><?php echo $title ?></title>
	<?php $this->html('headlinks') ?>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="verify-v1" content="/Ur0RE4/QGQIq9F46KZyKIyL0ZnS96N5x1DwQJa7bR8=" /> 
	<meta name="msvalidate.01" content="72AE896A31E239FAAF7EB0B95FB80ECC" />
	<META name="y_key" content="1b3ab4fc6fba3ab3">
    <style type="text/css" media="all">/*<![CDATA[*/ @import "<?echo wfGetPad();?>/extensions/min/f/skins/WikiHow/new.css"; /*]]>*/</style>
    <style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/new.css"; /*]]>*/</style>
    <style type="text/css" media="<?php echo $printable_media;?>">/*<![CDATA[*/ @import "<?echo wfGetPad();?><?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/printable.css"; /*]]>*/</style>
<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>
	<script type="<?php $this->text('jsmimetype') ?>" src="<?echo wfGetPad();?><?php $this->text('stylepath' ) ?>/common/wikibits.js"><!-- wikibits js --></script>
	<?php if ($wgUser->getID() > 0) { ?>
		<style type="text/css" media="all">/*<![CDATA[*/ @import "<?echo wfGetPad();?>/skins/WikiHow/loggedin.css"; /*]]>*/</style>
	<?php } ?>
	<?php if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getText() == wfMsg('mainpage')) { ?> 
	<style type="text/css">
   DIV#main DIV#article {
       border-right: none;
       border-bottom: none;
       border-left: none;
       padding: 0;
   }
    </style>
<?php } ?>
<!--[if lte IE 6]>

<style type="text/css">
BODY { color: #C00; }
IMG#wikiHow {
    width: 189px;
    height: 44px;
    position: relative;
	filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='images/wikihow.png', sizingMethod='scale');
}

#go_button {
    width: 30px;
    height: 29px;
    background: transparent;
	filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='images/button_square.png', sizingMethod='scale');
}
</style>
<![endif]-->
	<!--[if !IE]>-->
	<link media="only screen and (max-device-width: 480px)" href="<?echo wfGetPad();?>/skins/WikiHow/iphone.css" type="text/css" rel="stylesheet" />
	<!--<![endif]-->
	<?php echo $rtl_css; ?>
 	<script language="javascript" src="<?echo wfGetPad();?>/extensions/wikihow/prototype1.8.2/prototype.js"></script>
 	<script language="javascript" src="<?echo wfGetPad();?>/extensions/wikihow/prototype1.8.2/effects.js"></script>
 	<script language="javascript" src="<?echo wfGetPad();?>/extensions/wikihow/prototype1.8.2/controls.js"></script>
 	<?php if (($wgTitle->getPrefixedURL() == "Main-Page") && ($wgLanguageCode == 'en')) { ?>
 	<script type="text/javascript" src="<?echo wfGetPad();?><?php $this->text('stylepath' ) ?>/WikiHow/spotlightrotate.js"></script>
 	<?php } ?>
	<link rel="alternate" type="application/rss+xml" title="wikiHow: How-to of the Day" href="http://wwww.wikihow.com/feed.rss">
	<link rel="apple-touch-icon" href="http://www.wikihow.com/skins/WikiHow/apple_touch_icon.png" /> 
</head>
<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload'    ]) { ?>onload="<?php     $this->text('body_onload')     ?>"<?php } ?>
 id='body'>
<div id="header">
    <div id="logo">
		<a href='<?php echo $mainPageObj->getFullURL();?>'><img src="<?php echo wfGetPad();?>/skins/WikiHow/wikihow.png" id="wikiHow" alt="<?php echo 'wikiHow - '.wfMsg('main_title');?>" style="margin-bottom: -4px;" width="189" height="44"/><br />
		<p>the how-to manual that you can edit</p></a>
 <?php if ($wgLanguageCode != 'en' && $wgTitle->getArticleID() > 0 && $action == 'view' ) {
               echo "<img src='/imagecounter.gif?id=" . $wgTitle->getArticleID() . "' width='1' height='1' border='0'/>";
 } ?>
    </p>
 	</div>
 
    <p id="balloon">
		<?php echo $sitenotice;?>
    </p>

    <p style="text-align:right;padding:10px;">
    <span class="login"><? echo $login; ?><br/>[<a href=''>Sign out</a>]</span>
	<?  echo $search_box; ?>
    </p>

	</div>
	<?php echo $announcement;?>

<div id="main">
    <div id="upper_tabs">
        <a href="<?php echo $mainPageObj->getFullURL();?>" id="nav_home" title="Home" onmousedown="button_click(this)">Home</a>
        <a href="" id="nav_articles" title="Articles" class="on" onmousedown="button_click(this)">Articles</a>
        <a href="<?echo $cp->getFullURL();?>" id="nav_community" title="Community" onmousedown="button_click(this)"><?echo wfMsg('community');?></a>
		<?if ($wgUser->getID() >0) { ?>
        	<a href="<? echo $wgUser->getUserPage()->getFullURL(); ?>" id="nav_profile" title="My Profile" onmousedown="button_click(this)">My Profile</a>
		<? } else{ ?>
        	<a href="/Special:Userlogin" id="nav_profile" title="My Profile" onmousedown="button_click(this)">My Profile</a>
		<? } ?>
    </div>

    <div id="article_shell">
        <img src="/skins/WikiHow/images/article_top.png" />
        <div id="article">

		<?if ($wgTitle->userCanEdit() ) { ?>
			<a href="<?echo $wgTitle->getEditURL();?>" class="button edit_article_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">Edit</a>
		<? } ?>

	<ul id="breadcrumb">
		<?php echo $catlinkstop; ?>
	</ul>

	<?php echo $ad_setup; ?>     
	<?php echo $heading?>

    <?php echo $sk->getAuthorHeader();?>
	
	<?=$fa?>
	<div id="article_tabs">
		<a href="<? if ($wgTitle->isTalkPage()) echo $wgTitle->getSubjectPage()->getFullURL(); else echo $wgTitle->getFullURL(); ?>" 
			id="tab_article" title="Article" <?php if ($wgTitle->getNamespace() == NS_MAIN && $action != "edit" && $action != "history") echo 'class="on"'; ?> onmousedown="button_click(this);">Article</a>
	<? if ($wgTitle->getNamespace() != NS_SPECIAL) { ?>
     	<a href="<?echo $wgTitle->getEditUrl();?>" id="tab_edit" title="Edit" onmousedown="button_click(this);"
			 <?php if ($action == "edit") echo 'class="on"'; ?> 
		>
		<img src="/skins/WikiHow/images/pencil.gif" width="10" height="11" alt="pencil" class="tab_pencil"/> <?echo wfMsg('edit');?></a>
		<? if ($action =='view' && Namespace::isTalk($wgTitle->getNamespace())) { 
                $talklink = '#postcomment';
			} else {
			 	$talklink = $wgTitle->getTalkPage()->getFullURL();
			}
           if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {
           		$msg = wfMsg('talk');
           } else {
				$msg = wfMsg('discuss');
           }
		?>
        <a href="<? echo $talklink; ?>"  <?php if ($wgTitle->isTalkPage() && $action != "edit" && $action != "history") echo 'class="on"'; ?> id="tab_discuss" title="<?php echo $msg ?>" onmousedown="button_click(this);"><?php echo $msg; ?></a>
        <a href="<? echo $wgTitle->getLocalURL( 'action=history' ); ?>" id="tab_history"  <?php if ($action == "history") echo 'class="on"'; ?>  title="<?php echo wfMsg('history'); ?>" onmousedown="button_click(this);"><?php echo wfMsg('history'); ?></a>
	<? } ?>
	   </div>
		    <div id="article_tabs_line"></div>
			<?php echo $requestTop;?>
		    <?php $this->html('bodytext') ?>
			<? if (!$show_ad_section) { 
    			echo "<div id='lower_ads'>{$bottom_ads}</div>";
			 }
				if ($show_ad_section) {
					echo $ad_section;	
				}
			?>
			<?php echo $requestBottom;?>
			<?php echo $search_results;?>
			<?php echo $bottom_site_notice; ?>
			<?php Postcomment::getForm(); ?> 

	<? if ($wgTitle->getNamespace() == NS_MAIN && $action == 'view') { ?>
<h2><?php echo wfMsg('article_info'); ?></h2>
    <div id="article_info">        
        <p style="float: right;">
			<?echo wfMsg('categories') . ":<br/>" .  $sk->getCategoryLinks($false); ?>

        </p>
        <p>
			<?php if ($action == 'view') {
            	echo wfMsg('last_edited') . "<br/>" . $sk->getLastEdited();
			} ?>
        </p>
        <p>
			<?php echo $sk->getAuthorFooter(); ?>
        </p>    
       

        <?php if (is_array($this->data['language_urls'])) { ?>
        <p>
            <?php $this->msg('otherlanguages') ?><br />
            <?php
                $links = array();
                foreach($this->data['language_urls'] as $langlink) { 
                    $links[] = '<a href="' .  htmlspecialchars($langlink['href']) . '">' .  $langlink['text'] . "</a>";
                }
                echo implode(", ", $links);
            ?>
        </p> 
        <? } ?>
    <? } ?>
    </div>
	<? if ($wgTitle->getNamespace() == NS_MAIN && $action=='view') { ?>
<h2><?php echo wfMsg('article_tools'); ?></h2>

<div id="share_icons">
    <div><b>Share this Article</b></div>
    <a href="" id="share_twitter"></a>
    <a href="" id="share_stumbleupon"></a>
    <a href="" id="share_facebook"></a>
    <a href="" id="share_blogger"></a>
    <a href="" id="share_digg"></a>
    <a href="" id="share_google"></a>
    <a href="" id="share_delicious"></a>
    <br class="clearall" />
</div>
    <ul id="end_options">
        <li id="endop_discuss"><a href="<?echo $talklink;?>">Discuss</a></li>
        <li id="endop_print"><a href="<?echo $wgTitle->getLocalUrl('print');?>"><?echo wfMsg('print');?></a></li>
        <li id="endop_email"><a href="/Special:EmailLink/<?echo $wgTitle->getPrefixedURL();?>">Email</a></li>
        <li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=watch');?>">Watch</a></li>
        <li id="endop_edit"><a href="<?echo $wgTitle->getEditUrl();?>"><?echo wfMsg('edit');?></a></li>
        <li id="endop_fanmail"><a href="/Special:ThankAuthors/<?echo $wgTitle->getPrefixedURL();?>">Send fan mail to authors</a></li>
    </ul>

	<div id="embed_this"><span>+</span> <a href="/Special:Republish/<?php echo $wgTitle->getDBKey(); ?>">Embed this: Republish this entire article on your blog or website.</a></div>
</div>
        <div id="last_question"> 
            <p id="article_line"></p>           
            <p><b><?php echo $sk->pageStats(); ?></b></p>
          
			<div id='page_rating'> 
			<?echo RateArticle::showForm();?> 
           	</div>
 
            <p></p>
        </div>  
        <img src="/skins/WikiHow/article_bottom.png" width="683" height="14" alt="" />
	<? } ?>
    </div>

    <div id="sidebar">
        <div id="top_links">
            <a href="/Special:Createpage" class="button button137" style="float: left;" onmouseover="button_swap(this);" onmouseout="button_unswap(this);"><?echo wfMsg('writearticle');?></a>
            <a href="/Special:Randomizer" class="reverse_link" accesskey='x'><b><?echo wfMsg('randompage'); ?></b></a>
        </div>

	<? 
			$related_articles = $sk->getRelatedArticlesBox($this);
			if ($action == 'view' && $related_articles != "") {
	?>
        <img src="/skins/WikiHow/images/sidebar_top.png" />

        <div id="side_related_articles" class="sidebox">
      		<?echo  $sk->getRelatedArticlesBox($this); ?> 
        </div>
        <img src="/skins/WikiHow/images/sidebar_bottom.png" />
    <? } ?>  
		<? if ($related_vids != "") { ?> 
        	<img src="/skins/WikiHow/images/sidebar_top.png" />
        	<div id="side_related_videos" class="sidebox">
			<?php echo $related_vids; ?>
        	</div>
        <img src="/skins/WikiHow/images/sidebar_bottom.png" />
		<? } ?>
        
       <?php if ($ads != '') { ?> 
        <img src="/skins/WikiHow/images/sidebar_top.png" />
        <div id="side_ads_by_google" class="sidebox">
		<?echo $ads;?>
        </div>
       <?php } ?> 

        <?php
        // GOOGLE SIDE BAR
        if ($wgUser->getID() > 0 && $wgTitle->getNamespace()== NS_SPECIAL && $wgTitle->getText()=="LSearch" && $wgLanguageCode=='en') {
		?>
        	<img src="/skins/WikiHow/images/sidebar_top.png" />
        	<div id="side_ads_by_google" class="sidebox">
		<?
            require_once('GoogleAPIResults.body.php');
            echo GoogleAPIResults::getSideBar();
		?>
        </div>
        	<img src="/skins/WikiHow/images/sidebar_bottom.png" />
		<?
        }
        ?>

	<? if ($action == "view") { ?>
        <img src="/skins/WikiHow/images/sidebar_top.png" />
        <div id="side_featured_articles" class="sidebox">
			<?php if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getText() == wfMsg('mainpage'))
					echo $sk->getFeaturedArticlesBox (15, 100); 
				else
					echo $sk->getFeaturedArticlesBox (4, 4); 
				?>
        </div>
        <img src="/skins/WikiHow/images/sidebar_bottom.png" />
	<? } ?>

    <?php if  ($wgLanguageCode == 'en' && ($wgUser->getID() == 0 || $wgUser->getOption('recent_changes_widget_show') != '0' )) { ?>  
        <img src="/skins/WikiHow/images/sidebar_top.png" />
        <div id="side_recent_changes" class="sidebox">
            <?RCWidget::showWidget();  ?>
			<p class="bottom_link">
			<? if ($wgUser->getID() > 0) { ?>
            	<?php echo wfMsg('welcome', $wgUser->getName(), $wgUser->getUserPage()->getFullURL()); ?>
			<? } else { ?>
            	<a href="/Special:Userlogin">Want to join in?</a>
			<? } ?>
			<a href="javascript:rcTransport(this);" id="play_pause_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" onmousedown="button_click(this);" ></a>
        </div>
        <img src="/skins/WikiHow/images/sidebar_bottom.png" />
	<?php } ?>

        <div id="side_nav_top">
            <h3><a href="javascript:sidenav_toggle('navigation_list',this);" id="href_navigation_list">- collapse</a> <?echo wfMsg('navigation'); ?></h3>
        </div>
        <div class="sidebox" id="side_nav">

            <ul id="navigation_list" style="margin-top: 0;">
                <li>+ <?echo $cplink; ?></a></li>
                <li>+ <?echo $rclink; ?></li>
                <li>+ <?echo $forumlink; ?></a></li>
                <li>+ <?echo $splink; ?></li>
                <li>+ <?echo $requestlink;?></li>
                <li>+ <?echo $listrequestlink;?></li>
            </ul>
            
            <h3><a href="javascript:sidenav_toggle('editing_list',this);" id="href_editing_list">+ expand</a> <?echo wfMsg('editing_tools');?></h3>
            <ul id="editing_list" style="display:none;">

                <li>+ <?echo $freephotoslink;?></li>
                <li id='gatImageUpload'>+ <?echo $uploadlink;?></li>
				<?php echo $videolink; ?>
				<?php echo $mralink; ?>
				<li>+ <?echo $wlhlink; ?></li>
				<?echo $statslink;?> 
                <li>+ <?echo $relatedchangeslink; ?></li>
            </ul>
            
            <h3><a href="javascript:sidenav_toggle('my_pages_list',this);" id="href_my_pages_list">+ expand</a> <?echo wfMsg('my_pages'); ?></h3>
            <ul id="my_pages_list" style="display:none;">
        <?php
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Watchlist"), wfMsg('watchlist') ). "</li>";
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Mypage"), wfMsg('myauthorpage') ). "</li>";
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Mytalk"), wfMsg('mytalkpage') ). "</li>";
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Drafts"), wfMsg('mydrafts') ). "</li>";
            echo "<li>+ " . $sk->makeLinkObj(SpecialPage::getTitleFor("Mypages", "Contributions"),  wfMsg ('mycontris'));
            echo "<li>+ " . $sk->makeLinkObj(SpecialPage::getTitleFor("Mypages", "Fanmail"),  wfMsg ('myfanmail'));
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Preferences"), wfMsg('mypreferences') ). "</li>";
            echo "<li>+ " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Userlogout"), wfMsg('logout') ) . "</li>";
        ?>
            </ul>
        </div>
        <img src="/skins/WikiHow/images/sidebarfade_bottom.png" />        
       
		<?php echo $user_links; ?>
 
        <img src="/skins/WikiHow/images/sidebarline_top.png" />
        <div class="sideboxline">
			<ul id="social_box">
            	<li id='social_fb'><a href="http://www.facebook.com/wikiHow">Join our Facebook Group</a></li>
            	<li id='social_tw'><a href="http://twitter.com/wikihow">Follow us on Twitter</a></p>
			</ul>
        </div>
        <img src="/skins/WikiHow/images/sidebarline_bottom.png" />
        
    </div>
</div>

</div>
<div id="footer_top"></div>
<div id="footer_shell">
    <div id="footer">
    
        <div id="footer_side">

            <img src="/skins/WikiHow/images/wikihow_footer.gif" width="113" height="24" alt="wikiHow - the how-to manual that you can edit" />
			 <p id="footer_tag">the how-to manual that you can edit</p>

			<? echo wfMsgExt('site_footer_new', 'parse'); ?>
        </div>
        
        <div id="footer_main">        
        <?php echo $search_box; ?>
            
            <h3><?php echo wfMsg('explore_categories'); ?></h3>
        
			<?echo $sk->getCategoryList(); ?>    
        </div>
        
        <br class="clearall" />

	    <div id="sub_footer">
			 <?php echo wfMsg('sub_footer_new', wfGetPad(), wfGetPad());?>

            <br class="clearall" />
        </div>
    </div>
</div>
</body>
</html>	
    

<?php $this->html('reporttime') ?>

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));    
</script>   
<script type="text/javascript">
try {       
var pageTracker = _gat._getTracker("UA-2375655-1"); 
pageTracker._setDomainName(".wikihow.com");
pageTracker._trackPageview();} catch(err) {}
</script>   

<!-- Google Analytics Event Track -->
<script type="text/javascript" src="<?php echo wfGetPad();?><?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/gaWHTracker.js"></script> 
<script type="text/javascript">
Event.observe(window, 'load', gatStartObservers);
</script>   
<!-- END Google Analytics Event Track -->

<!-- LOAD EVENT LISTENERS -->
<?php if (($wgTitle->getPrefixedURL() == "Main-Page") && ($wgLanguageCode == 'en')) { ?>
<script type="text/javascript">
Event.observe(window, 'load', initSA);
</script>
<?php } ?>


  </body><?php	if (($wgRequest->getVal("action") == "edit" || $wgRequest->getVal("action") == "submit2") && $wgRequest->getVal('advanced', null) != 'true') { ?>
	<SCRIPT>
		if (document.getElementById('steps') && document.getElementById('wpTextbox1') == null) {
	            InstallAC(document.editform,document.editform.q,document.editform.btnG,"./<?php echo $wgLang->getNsText(NS_SPECIAL).":TitleSearch";?>","en");
		}
        </SCRIPT>
<?php } ?>


<?php 
	if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "CreatePage") {
?>
<SCRIPT>
	            InstallAC(document.createform,document.createform.target,document.createform.btnG,"./Special:SuggestionSearch","en");
</SCRIPT>
<?php 
	}
?>
	<?php echo $wgOut->getScript() ?>
</html>
 
	<?php
}

	
}

?>

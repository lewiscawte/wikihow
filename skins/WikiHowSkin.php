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
global $IP;
require_once("$IP/includes/SkinTemplate.php");

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
    public $mCategories = array();

	public $mAuthors;
	public $mSidebarWidgets	= array();

	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'WikiHow';
		$this->stylename = 'WikiHow';
		$this->template  = 'WikiHowTemplate';
	}

	function addWidget($html) {
		$display = "
	<div class='sidebox_shell'>
		<div class='sidebar_top'></div>
		<div class='sidebox'> ". $html ." </div>
		<div class='sidebar_bottom_fold'></div>
	</div>\n";

		array_push($this->mSidebarWidgets, $display);
		return;
	}

	/*
	* A mild hack to allow for the language appropriate 'How to' to be added to interwiki link titles.
	* Note German (de) is a straight pass-through since the 'How to' is already stored in the de database
	*/
	function getInterWikiLinkText(&$linkText, &$langCode) {
		static $formatting = array( 
			"ar" => "$1 كيفية", 
			"de" => "$1", 
			"es" => "Como $1", 
			"en" => "How to $1", 
			"fa" => "$1 چگونه", 
			"fr" => "Comment $1", 
			"he" => "$1 איך", 
			"it" => "Come $1", 
			"ja" => "$1（する）方法", 
			"nl" => "Hoe moet je $1", 
			"pt" => "Como $1", 
		);

		$result = $linkText;
		$format = $formatting[$langCode];
		if(!empty($format)) {
			$result = preg_replace("@(\\$1)@", $linkText, $format);
		}
		return $result;
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
		if (!$wgTitle || !($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT)) {
			return '';
		}

		$row = self::getLastEditedInfo();

		$html = '';
		$u = User::newFromName($row->rev_user_text);
		if ($row && $row->rev_user != 0 && $u) {
			$ts = wfTimestamp(TS_UNIX, $row->rev_timestamp);
			$html = wfMsg('last_edited') . "<br/>";
			$html .= wfMsg('last_edited_by', date("F j, Y", $ts), $u->getName() , $u->getUserPage()->getLocalURL());
		}
		return $html;
	}

	function getLastEditedInfo() {
		global $wgTitle;

		$dbr = wfGetDB(DB_SLAVE);

		$bad = User::getBotIDs();
		$bad[] = 0;  // filter out anons too, as per Jack

		$row = $dbr->selectRow('revision', array('rev_user', 'rev_user_text', 'rev_timestamp'),
				array('rev_user NOT IN (' . $dbr->makeList($bad) . ")", "rev_page"=>$wgTitle->getArticleID()),
				__METHOD__,
				array("ORDER BY" => "rev_id DESC", "LIMIT"=>1)
			);

		$info->rev_user_text = $row->rev_user_text;
		$info->rev_user = $row->rev_user;
		$info->rev_timestamp = $row->rev_timestamp;

		return $info;
	}

	function pageStats() {
		global $wgOut, $wgLang, $wgArticle, $wgRequest, $wgTitle;
		global $wgDisableCounters, $wgMaxCredits, $wgShowCreditsIfMax;

		extract( $wgRequest->getValues( 'oldid', 'diff' ) );
		if ( ! $wgOut->isArticle() ) { return ''; }
		if ( isset( $oldid ) || isset( $diff ) ) { return ''; }
		if ( $wgArticle == null || 0 == $wgArticle->getID() ) { return ''; }

		$s = '';
		if ( !$wgDisableCounters ) {
			$count = $wgLang->formatNum( $wgArticle->getCount() );
			if ( $count ) {
				if ($wgTitle->getNamespace() == NS_USER)
					$s = wfMsg( 'viewcountuser', $count );
				else
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

			$items[] = QuickNoteEdit::getQuickNoteLink($wgTitle, $userId, $userText);

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

		$url = $title->getFullURL() . "?action=rollback&from=" . urlencode( $rev->getUserText() ).  $extraRollback . "&useajax=true";
		$s  = "<script type='text/javascript'>
				var gRollbackurl = \"{$url}\";

			</script>
			<script type='text/javascript' src='".wfGetPad('/extensions/min/f/extensions/wikihow/rollback.js?') . WH_SITEREV ."'></script>
			<span class='mw-rollback-link' id='rollback-link'>
			<script type='text/javascript'>
				var msg_rollback_complete = \"" . htmlspecialchars(wfMsg('rollback_complete')) . "\";
				var msg_rollback_fail = \"" . htmlspecialchars(wfMsg('rollback_fail')) . "\";
				var msg_rollback_inprogress = \"" . htmlspecialchars(wfMsg('rollback_inprogress')) . "\";
				var msg_rollback_confirm= \"" . htmlspecialchars(wfMsg('rollback_confirm')) . "\";
			</script>
				[<a href='' id='rollback-link' onclick='return rollback();'>" . wfMsg('rollbacklink') . "</a>]
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
		global $wgContLang, $wgLanguageCode;

		$editurl = '&section='.$section;
		$hint = ( $hint=='' ) ? '' : ' title="' . wfMsgHtml( 'editsectionhint', htmlspecialchars( $hint ) ) . '"';

		//INTL: Edit section buttons need to be bigger for intl sites
		$editSectionButtonClass = ($wgLanguageCode == 'en') ? "button button52 editsection" : "button button52_intl editsection";

		$url = $this->makeKnownLinkObj( $nt, wfMsg('editsection'), 'action=edit'.$editurl, '', '', 'id="gatEditSection" class="' . $editSectionButtonClass . '" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" onclick="gatTrack(gatUser,\'Edit\',\'Edit_section\');" ',  $hint );
		return $url;
	}

	/** @todo document */
	function makeExternalLink( $url, $text, $escape = true, $linktype = '', $ns = null ) {
		$style = $this->getExternalLinkAttributes( $url, $text, 'external ' . $linktype );
		global $wgNoFollowLinks, $wgNoFollowNsExceptions;
		if( $wgNoFollowLinks && !(isset($ns) && in_array($ns, $wgNoFollowNsExceptions)) ) {
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
					$ret = "<h3>" . wfMsg('mylinks') . "<a id='href_my_links_list' onclick='return sidenav_toggle(\"my_links_list\",this);' href='#'>" . wfMsg('navlist_collapse') . "</a></h3>";
					$ret .= "<div id='my_links_list'>";
					$options = new ParserOptions();
					$output = $wgParser->parse($text, $wgTitle, $options);
					$ret .= $output->getText();
					$ret .= "</div>";
				}
			}
		}
		return $ret;
	}

	function getRelatedWikihowsFromSource($num) {
		$whow = WikiHow::newFromCurrent();
		$related = preg_replace("@^==.*@m", "", $whow->getSection('related wikihows'));

		$related = preg_replace("/\\|[^\\]]*/", "", $related);
		$rarray = split("\n", $related);
		$result = "<table>";
		$count = 0;
		foreach($rarray as $related) {
			preg_match("/\[\[(.*)\]\]/", $related, $rmatch);
			$t = Title::newFromText($rmatch[1]);
			if($t && $t->exists()) {
				$result .= $this->featuredArticlesLine($t, $t->getFullText());
				if (++$count == $num) break;
			}
		}
		$result .= "</table>";
		return $result;
	}

	/*function hasMajorityPhotos() {
		global $wgTitle;
		$r = Revision::newFromTitle($wgTitle);
		if ($r == null) return false;
		$section = Article::getSection($r->getText(), 1);
		$num_steps = preg_match_all ('/^#/im', $section, $matches);
		$num_step_photos = preg_match_all('/\[\[Image:/', $section, $matches);
		if ($num_steps > 0 && $num_step_photos / $num_steps > 0.5) return true;
		return false;
	}*/

	function hasIntroImage() {
		$whow = WikiHow::newFromCurrent();
		$intro = Article::getSection($whow->mLoadText, 0);
		$num_photos = preg_match_all('/\[\[Image:/', $intro, $matches);
		if ($num_photos > 0) return true;
		return false;
	}

	function getMetaSubcategories($limit = 3) {
		global $wgTitle;
		$results = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			array('categorylinks', 'page'),
			array('page_namespace', 'page_title'),
			array('page_id=cl_from', 'page_namespace=' . NS_CATEGORY, 'cl_to'=>$wgTitle->getDBKey()),
			__METHOD__,
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

	// Add these meta properties that the Facebook graph protocol wants
	// https://developers.facebook.com/docs/opengraph/
	function addFacebookMetaProperties($title) {
		global $wgOut, $wgTitle, $wgRequest, $wgServer;

		$action = $wgRequest->getVal('action', '');
		if ($wgTitle->getNamespace() != NS_MAIN
			|| $wgTitle->getText() == wfMsg('mainpage')
			|| (!empty($action) && $action != 'view'))
		{
			return;
		}

		$url = $wgTitle->getFullURL();

		if (!$this->ami) {
			$this->ami = new ArticleMetaInfo($wgTitle);
		}
		$fbDesc = $this->ami->getFacebookDescription();

		$img = $this->ami->getImage();

		// if this was shared via thumbs up, we want a different description.
		// url will look like this, for example:
		// http://www.wikihow.com/Kiss?fb=t
		if ($wgRequest->getVal('fb', '') == 't') {
			$fbDesc = wfMsg('article_meta_description_facebook', $wgTitle->getText());
			$url .= "?fb=t";
		}


		// If this url isn't a facebook action, make sure the url is formatted appropriately 
		if ($wgRequest->getVal('fba','') == 't') {
			$url .= "?fba=t";
		} else {
			// If this url isn't a facebook action, add 'How to ' to the title
			$title = wfMsg('howto', $title);
		}	

		$props = array(
			array( 'property' => 'og:title', 'content' => $title ),
			array( 'property' => 'og:type', 'content' => 'article' ),
			array( 'property' => 'og:url', 'content' => $url ),
			array( 'property' => 'og:site_name', 'content' => 'wikiHow' ),
			array( 'property' => 'og:description', 'content' => $fbDesc ),
		);
		if ($img) {
			// Note: we can add multiple copies of this meta tag at some point
			// Note 2: we don't want to use pad*.whstatic.com because we want
			//   these imgs to refresh reasonably often as the page refreshes
			$img = $wgServer . $img;
			$props[] = array( 'property' => 'og:image', 'content' => $img );
		}

		foreach ($props as $prop) {
			$wgOut->addHeadItem($prop['property'], '<meta property="' . $prop['property'] . '" content="' . htmlentities($prop['content']) . '"/>' . "\n");
		}
	}

	function getMetaDescription() {
		global $wgTitle, $wgRequest, $IP;

		$return = '';
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() == wfMsg('mainpage')) {
			$return = wfMsg('mainpage_meta_description');
		} else if ($wgTitle->getNamespace() == NS_MAIN) {
			$desc = '';
			if (!$this->titleTest) {
				$this->titleTest = TitleTests::newFromTitle($wgTitle);
				if ($this->titleTest) {
					$desc = $this->titleTest->getMetaDescription();
				}
			}
			if (!$desc) {
				if (!$this->ami) {
					$this->ami = new ArticleMetaInfo($wgTitle);
				}
				$desc = $this->ami->getDescription();
			}
			if (!$desc) {
				//if ($this->hasMajorityPhotos())
				//	$return = wfMsg('article_meta_description_withphotos', $wgTitle->getText());
				$return = wfMsg('article_meta_description', $wgTitle->getText() );
			} else {
				$return = htmlspecialchars($desc);
			}
		} else if ($wgTitle->getNamespace() == NS_CATEGORY) {
			// get keywords
			$subcats = $this->getMetaSubcategories(3);
			$keywords = implode(", ", $subcats);
			if ($keywords != "")
				$return = wfMsg('category_meta_description', $wgTitle->getText(), $keywords);
			else
				$return = wfMsg('subcategory_meta_description', $wgTitle->getText(), $keywords);
		} else if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Popularpages") {
			$return = wfMsg('popularpages_meta_description');
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

	function needsFurtherEditing(&$title) {
		$cats = $title->getParentCategories();
		if (is_array($cats) && sizeof($cats) > 0) {
			$keys = array_keys($cats);
			$templates = wfMsgForContent('templates_further_editing');
			$templates = split("\n", $templates);
			$templates = array_flip($templates); // switch all key/value pairs
			for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
				$t = Title::newFromText($keys[$i]);
				if (isset($templates[$t->getText()]) ) {
					return true;
				}
			}
		}
		return false;
	}
		
	function getRelatedArticlesBox($e) {
		global $wgTitle, $wgContLang, $wgUser, $wgRequest, $wgMemc;

		if (!$wgTitle 
			|| $wgTitle->getNamespace() != NS_MAIN
			|| $wgTitle->getFullText() == wfMsg('mainpage')
			|| $wgRequest->getVal('action') != '')
		{
			return '';
		}

		$key = wfMemcKey('relarticles_box1', $wgTitle->getArticleID());
		$val = $wgMemc->get($key);
		if ($val) return $val;
		
		$cats = WikiHow::getCurrentParentCategories();
		$cat = '';
		if (is_array($cats) && sizeof($cats) > 0) {
			$keys = array_keys($cats);
			$templates = wfMsgForContent('categories_to_ignore');
			$templates = split("\n", $templates);
			$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
			$templates = array_flip($templates); // make the array associative.
			for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
				$t = Title::newFromText($keys[$i]);
				if (isset($templates[urldecode($t->getPartialURL())]) ) {
					continue;
				}
				$cat = $t->getDBKey();
				break;
			}
		}
		// Populate related articles box with other articles in the category, 
		// displaying the featured articles first
		$result = "";
		if (!empty($cat)) {
			$dbr = wfGetDB(DB_SLAVE);
			$num = intval(wfMsgForContent('num_related_articles_to_display'));
			$res = $dbr->select(array('categorylinks', 'page'),
				array('cl_from', 'page_is_featured, page_title'), 
				array(
					'cl_from = page_id',
					'cl_to' => $cat,
					'page_namespace' => 0,
					'page_is_redirect' => 0,
					'(page_is_featured = 1 OR page_random > ' . wfRandom() . ')'
				),
				__METHOD__,
				array('ORDER BY' => 'page_is_featured DESC'));

			$count = 0;
			while (($row = $dbr->fetchObject($res)) && $count < $num) {
				if ($row->cl_from == $wgTitle->getArticleID()) {
					continue;
				}
				$t = Title::newFromDBkey($row->page_title);
				if (!$t || $this->needsFurtherEditing($t)) {
					continue;
				}

				$result .= $this->featuredArticlesLine($t, $t->getFullText());
				
				$count++;
			}

			if (!empty($result)) {
				$result = "<h3>" . wfMsg('relatedarticles') . "</h3><table>$result\n</table>";
			}
		}
		$wgMemc->set($key, $result, 3600);
		return $result;
	}

	function getUsernameFromTitle() {
		global $wgTitle;
		$real_name = '';
		$username = $wgTitle->getText();
		$username = ereg_replace("/.*", "", $username);
		$user = User::newFromName($username);
		if ($user) {
			$real_name = $user->getRealName();
			if (!$real_name) $real_name = $username;
		}
		return $real_name;
	}

	// returns the first line of the article, safely
	function getFirstLine($title) {
		$r = Revision::newFromTitle($title);
		if (!$r)
			return "";
		$text = $r->getText();
		$intro = Article::getSection($text, 0);	 // grab just a small chunk to work with
        $punct = "!\.\?\:";
		// replace images, external links, templates, categories
		$intro = preg_replace("@\[\[Image:[^\]]*\]\]@im", "", $intro);
		$intro = preg_replace("@\[\[[^\|]*\|([^\]]*)\]\]@im", "$1", $intro);
		$intro = preg_replace("@\[\[[^\]]*\]\]@im", "", $intro);
		$intro = preg_replace("@\{\{[^\}]*\}\}@im", "", $intro);
		#$firstline = strip_tags(preg_replace("@([{$punct}])(.|\n)*@im", "$1", $intro));
		preg_match("@([^!.?:]|\n)*[!.?]@im", $intro, $matches);
		if (sizeof($matches) > 0) $firstline = $matches[0];
		return trim($firstline);
	}
	
	function getTitleImage($title) {
		global $wgContLang;
		
		$r = Revision::newFromTitle($title);
		if (!$r) return "";
		$text = $r->getText();
		if (preg_match("/^#REDIRECT \[\[(.*?)\]\]/", $text, $matches)) {
			if ($matches[1]) {
				$title = Title::newFromText($matches[1]);
				$r = Revision::newFromTitle($title);
				$text = $r->getText();
			}
		}

		//first check the intro
		$intro = Wikitext::getIntro($text);
		
		// Make sure to look for an appropriately namespaced image. Always check for "Image"
		// as a lot of files are in the english image repository
		$nsTxt = "(Image|" . $wgContLang->getNsText(NS_IMAGE) . ")";
		if(preg_match("@\[\[" . $nsTxt . ":([^\|]+)[^\]]*\]\]@im", $intro, $matches)) {
			$matches[2] = str_replace(" ", "-", $matches[2]);

			$file = wfFindFile($matches[2]);
			if ($file && isset($file)) {
				return $file;
			}
		}

		//now check the steps
		$steps = Wikitext::getStepsSection($text, true);
		
		if(preg_match_all("@\[\[" . $nsTxt . ":([^\|]+)[^\]]*\]\]@im", $steps[0], $matches)) {

			//grab the last image that appears in the steps section
			$last = count($matches[2]) - 1;
			$imageName = str_replace(" ", "-", $matches[2][$last]);
			$file = wfFindFile($imageName);
			
			if ($file && isset($file)) {
				return $file;
			}
		}
	}
	
	function getGalleryImage($title, $width, $height) {
		global $wgMemc, $wgLanguageCode, $wgContLang;

		$key = wfMemcKey('gallery1', $title->getArticleID(), $width, $height);

		$val = $wgMemc->get($key);
		if ($val) {
			return $val;
		}

		if (($title->getNamespace() == NS_MAIN) || ($title->getNamespace() == NS_CATEGORY) ) {
			if ($title->getNamespace() == NS_MAIN) {
				$file = self::getTitleImage($title);
				
				if ($file && isset($file)) {
					$thumb = $file->getThumbnail($width, $height, true, true);
					if ($thumb instanceof MediaTransformError) {
						// we got problems!
						print_r($thumb);
						exit;
					} else {
						$wgMemc->set($key, wfGetPad($thumb->url), 2* 3600); // 2 hours
						return wfGetPad($thumb->url);
					}
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
				$cat = self::getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial 
				// URL for self::getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					if ($title) {
						$cat = $title->getPartialURL();
					}
				}
			}

			if (isset($catmap[$cat])) {
				$image = Title::newFromText($catmap[$cat]);
				$file = wfFindFile($image, false);
				$thumb = $file->getThumbnail($width, $height, true, true);
				if ($thumb) {
					$wgMemc->set($key, wfGetPad($thumb->url),  2 * 3600); // 2 hours
					return wfGetPad($thumb->url);
				}
			} else {
				$image = Title::makeTitle(NS_IMAGE, "Book_266.png");
				$file = wfFindFile($image, false);
				$thumb = $file->getThumbnail($width, $height, true, true);
				if ($thumb) {
					$wgMemc->set($key, wfGetPad($thumb->url), 2 * 3600); // 2 hours
					return wfGetPad($thumb->url);
				}
			}
		}
	}

	function relatedArticlesLine(&$t, &$msg) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$link = $sk->makeKnownLinkObj( $t, $msg);
		$img = $this->getGalleryImage($t, 103, 68);

		$html = "<td style='vertical-align:top;width:50%;display:table-cell;border:0px;'>
				  <div style='margin-left:auto;margin-right:auto;text-align:center;width:103px;'>
				  <a href='{$t->getFullURL()}' class='rounders2 rounders2_lg rounders2_tan'>
					<img src='{$img}' alt='' width='102' height='68' class='rounders2_img' />
					<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
		  		  </a>
				  <a href='{$t->getFullURL()}'>{$t->getText()}</a>
				  </div>
			  	</td>";

		return $html;
	}

	function featuredArticlesLineWide($t, $msg) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$link = $sk->makeKnownLinkObj( $t, $msg);
		$img = $this->getGalleryImage($t, 103, 80);

		$html = "<td>
				<div>
				  <a href='{$t->getFullURL()}' class='rounders2 rounders2_tl rounders2_white'>
					<img src='{$img}' alt='' width='103' height='80' class='rounders2_img' />
					<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
		  </a>
				  <a href='{$t->getFullURL()}'>{$t->getText()}</a>
				</div>
			  </td>";

		return $html;
	}

	function featuredArticlesLine($t, $msg) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$link = $sk->makeKnownLinkObj( $t, $msg);
		$img = $this->getGalleryImage($t, 44, 33);
		$html .= "<tr><td id='thumb'><span class='rounders2 rounders2_sm rounders2_tan'>
				<a href='{$t->getFullURL()}'><img class='rounders2_img' alt='' src='{$img}' />
				<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
				</a>
				</span>
				</td>
				<td>{$link}</td></tr>\n";
		return $html;
	}
		
	function getNewArticlesBox() {
		global $wgMemc;
		$cached = $wgMemc->get(wfMemcKey('newarticlesbox'));
		if ($cached)  {
			return $cached;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('pagelist',
			'pl_page',
			array('pl_list'=>'risingstar'),
			__METHOD__,
			array('ORDER BY' => 'pl_page desc', 'LIMIT' => 5));
		while($row = $dbr->fetchObject($res)) {
			$ids[] = $row->pl_page;
		}
        $html = "<div id='side_new_articles'><h3>" . wfMsg('newarticles') . "</h3>\n<table>";
		$res = $dbr->select(array('page'),
			array('page_namespace', 'page_title'),
			array('page_id IN (' . implode(",", $ids) . ")"),
			__METHOD__,
			array('ORDER BY' => 'page_id desc', 'LIMIT' => 5));
		while($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle(NS_MAIN, $row->page_title);
			if (!$t) continue;
			$html .= $this->featuredArticlesLine($t, $t->getText());
		}
		$html .=  "</table></div>";
		$wgMemc->set($key, $html, 3600);
		return $html;
	}

	function getFeaturedArticlesBox($dayslimit = 11, $linkslimit = 4 ) {
		global $wgUser, $wgServer, $wgTitle, $IP, $wgProdServer, $wgMemc;

		$sk = $wgUser->getSkin();

		$cachekey = wfMemcKey('featuredbox', $dayslimit, $linkslimit);
		$result = $wgMemc->get($cachekey);
		if ($result) return $result;

		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$feeds = FeaturedArticles::getFeaturedArticles($dayslimit);

		$html = "<h3><span onclick=\"location='" . wfMsg('featuredarticles_url') . "';\" style=\"cursor:pointer;\">" . wfMsg('featuredarticles') . "</span></h3>\n<table>";

		$now = time();
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$count = 0;
		foreach ($feeds as $item) {
			$url = $item[0];
			$d = $item[1];
			if ($d > $now) continue;
			$url = str_replace("$wgServer/", "", $url);
			if ($wgServer != 'http://www.wikihow.com') {
				$url = str_replace("http://www.wikihow.com/", "", $url);
			}
			if (isset($wgProdServer)) {
				$url = str_replace($wgProdServer, "", $url);
			}
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

		// expires every 5 minutes
		$wgMemc->set($cachekey, $html, 5 * 60);

		return $html;
	}

	function getFeaturedArticlesBoxWide($dayslimit = 11, $linkslimit = 4, $ismainpage = true) {
		global $wgUser, $wgServer, $wgTitle, $IP, $wgProdServer;

		$sk = $wgUser->getSkin();
		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		$feeds = FeaturedArticles::getFeaturedArticles($dayslimit);
		$html = '';

		if ($ismainpage) {
			$html .= "
	<div class='featured_articles_header' id='featuredArticles_header'>
	  	  <h1>" . wfMsg('featured_articles') . "</h1><a href='/feed.rss'><img src='".wfGetPad('/skins/WikiHow/images/rssIcon.png')."' alt='' class='rss' id='rssIcon' name='rssIcon' /> " . wfMsg('rss') . "</a>
		</div>\n";
		}

		$html .= "<div class='featured_articles_inner' id='featuredArticles'>
		  <table class='featuredArticle_Table'><tr>";

		$hidden = "<div id='hiddenFA' style='display:none; zoom:1;'><div>
	<table class='featuredArticle_Table'><tr>";

		$now = time();
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$count = 0;
		foreach ($feeds as $item) {
			$url = $item[0];
			$d = $item[1];
			if ($d > $now) continue;
			$url = str_replace("$wgServer/", "", $url);
			if ($wgServer != 'http://www.wikihow.com') {
				$url = str_replace("http://www.wikihow.com/", "", $url);
			}
			if (isset($wgProdServer)) {
				$url = str_replace($wgProdServer, "", $url);
			}
			$f = Title::newFromURL(urldecode($url));

			if ($f) {
				if ($count < $linkslimit)
					$html .= $this->featuredArticlesLineWide($f, $f->getText() );
				else
					$hidden .= $this->featuredArticlesLineWide($f, $f->getText() );
			}
			$count++;
			if ($count >= 2 * $linkslimit) {
				break;
			}
			if ($count % 5  == 0){
				if ($count < $linkslimit)
					$html .= "</tr><tr>";
				else
					$hidden .= "</tr><tr>";
			}
		}
		$html .= "</tr></table>";
		$hidden .= "</tr></table></div></div>";

		if ($ismainpage) {
		# nav stuff

			$langKeys = array('mainpage_fewer_featured_articles', 'mainpage_more_featured_articles');
			$js = WikiHow_i18n::genJSMsgs($langKeys);
			$html .= "{$js}{$hidden}
			<div id='featuredNav'><a href='{$popular->getFullURL()}'>" . wfMsg('mainpage_view_popular_articles') . "</a>
			<img src='".wfGetPad('/skins/WikiHow/images/actionArrow.png')."' alt='' />
				 <a href='/Special:Randomizer' accesskey='x'>" . wfMsg('mainpage_view_random_article') . "</a>
			<img src='".wfGetPad('/skins/WikiHow/images/actionArrow.png')."' alt='' />
				<span id='more'>
			<a href='#' onclick='mainPageFAToggle();return false;' id='toggle' name='toggle' style='display:inline;'>" . wfMsg('mainpage_more_featured_articles') ."</a>
			<img src='".wfGetPad('/skins/WikiHow/images/arrowMore.png')."' id='moreOrLess' alt='' name='moreOrLess' />
		</span>
		  </div>";
		}

		$html .= '</div></div>';

		if ($ismainpage) {
			$html .= "<div class='article_bottom_white'></div>";
		}
		return $html;
	}

	function getRADLinks($use_chikita_sky) {
		$channels = wikihowAds::getCustomGoogleChannels('rad_left', $use_chikita_sky);
		$links = wfMsg('rad_links_new', $channels[0], $channels[1]);
		$links = preg_replace('/\<[\/]?pre\>/', '', $links);
		return $links;
	}

	function getTopCategory($title = null) {
		global $wgContLang;
		if (!$title) {
			// an optimization because memcache is hit
			$parenttree = WikiHow::getCurrentParentCategoryTree();
		} else {
			$parenttree = $title->getParentCategoryTree();
		}
		$catNamespace = $wgContLang->getNSText(NS_CATEGORY) . ":";
		$parenttree_tier1 = $parenttree;

		$result = null;
		while ((!$result || $result == "WikiHow") && is_array($parenttree)) {
			$a = array_shift($parenttree);
			if (!$a) {
				$keys = array_keys($parenttree_tier1);
				$result = str_replace($catNamespace, "", $keys[0]);
				break;
			}
			$last = $a;
			while (sizeof($a) > 0 && $a = array_shift($a) ) {
				$last = $a;
			}
			$keys = array_keys($last);
			$result = str_replace($catNamespace, "", $keys[0]);
		}
		return $result;
	}

	function getGoogleAds($use_chikita_sky) {
		global $wgTitle, $wgLang,$wgRequest;

		$id = $wgTitle->getArticleID();
		$channels = wikihowAds::getCustomGoogleChannels('skyscraper', $use_chikita_sky);
		$kw = "";
		if ($wgTitle->getNamespace() == NS_SPECIAL
			&& ($wgTitle->getText() == "Search"||$wgTitle->getText() == "LSearch") )
			$kw .= "\ngoogle_kw_type = \"broad\";\ngoogle_kw = \"". htmlspecialchars($_GET['search']) . "\";\n";
		$extra = "+9183940762+";
		$s = wfMsgForContent('skyscraper_ads_new', $channels[0] . $extra, $channels[1], $kw);
		$s = preg_replace('/\<[\/]?pre\>/', '', $s);
		return $s;
	}

	function drawCategoryBrowser($tree, &$skin) {
		$return = '';
		foreach ($tree as $element => $parent) {
			$eltitle = Title::NewFromText($element);
			$start = ' ' . self::BREADCRUMB_SEPARATOR;
			if (empty($parent)) {
				# element start a new list
				$return .= "\n";
			} else {
				# grab the others elements
				$return .= $this->drawCategoryBrowser($parent, $skin) ;
			}
			# add our current element to the list
			$return .=  "<li>$start " . $skin->makeLinkObj( $eltitle, $eltitle->getText() )  . "</li>" ;
		}
		return $return;
	}

	const BREADCRUMB_SEPARATOR = '&raquo;';

	function getCategoryLinks($usebrowser) {
		global $wgOut, $wgUser;
		global $wgContLang;

		if( !$usebrowser && count( $wgOut->mCategoryLinks ) == 0 ) return '';

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

		$sep = self::BREADCRUMB_SEPARATOR;

		$categories = $sk->makeLinkObj(Title::newFromText('Special:Categorylisting'), wfMsg('categories'));
		$s = "<li class='home'>" . $sk->makeLinkObj($mainPageObj, wfMsg('home')) . "</li> <li>$sep $categories</li>";

		# optional 'dmoz-like' category browser. Will be shown under the list
		# of categories an article belong to
		if($usebrowser) {
			$s .= ' ';

			# get a big array of the parents tree
			$parenttree = WikiHow::getCurrentParentCategoryTree();
			if (is_array($parenttree)) {
				$parenttree = array_reverse($parenttree);
			} else {
				return $s;
			}
			# Skin object passed by reference cause it can not be
			# accessed under the method subfunction drawCategoryBrowser
			$tempout = explode("\n", $this->drawCategoryBrowser($parenttree, $this) );
			$newarray = array();
			foreach ($tempout as $t) {
				if (trim($t) != "") { $newarray[] = $t; }
			}
			$tempout = $newarray;
//print_r($tempout);
			# Clean out bogus first entry and sort them
			//unset($tempout[0]);
			asort($tempout);
			# Output one per line
			//$s .= implode("<br />\n", $tempout);
			$olds = $s;
			$s .= $tempout[0]; // this usually works

			if (strpos($s, "/Category:WikiHow") !== false
				|| strpos($s, "/Category:Featured") !== false
				|| strpos($s, "/Category:Nomination") !== false
			) {
				for ($i = 1; $i <= sizeof($tempout); $i++) {
					if (strpos($tempout[$i], "/Category:WikiHow") === false
					&& strpos($tempout[$i], "/Category:Featured") == false
					&& strpos($tempout[$i], "/Category:Nomination") == false
					) {
						$s = $olds;
						$s .= $tempout[$i];
						break;
					}
				}
			}

		}
		return $s;
	}
	
	public static function getLoadAuthorsCachekey($articleID) {
		return wfMemcKey('loadauthors', $articleID);
	}

	function loadAuthors() {
		global $wgTitle, $wgMemc;

		if (is_array($this->mAuthors)) return;

		$articleID = $wgTitle->getArticleID();
		$cachekey = self::getLoadAuthorsCachekey($articleID);
		$this->mAuthors = $wgMemc->get($cachekey);
		if (is_array($this->mAuthors)) return;

		$this->mAuthors = array();
		$dbr = wfGetDB(DB_SLAVE);
		// filter out bots
		$bad = User::getBotIDs();
		$bad[] = 0;  // filter out anons too, as per Jack
		$opts = array('rev_page'=> $articleID);
		if (sizeof($bad) > 0) {
			$opts[]  = 'rev_user NOT IN (' . $dbr->makeList($bad) . ')';
		}
		$res = $dbr->select('revision',
			array('rev_user', 'rev_user_text'),
			$opts,
			__METHOD__,
			array('ORDER BY' => 'rev_timestamp')
		);
		while ($row = $dbr->fetchObject($res)) {
			if ($row->rev_user == 0) {
				$this->mAuthors['anonymous'] = 1;
			} elseif (!isset($this->mAuthors[$row->user_text]))  {
				$this->mAuthors[$row->rev_user_text] = 1;
			}
		}

		if ($this->mAuthors) {
			$wgMemc->set($cachekey, $this->mAuthors);
		}
	}
	
	function isQuickBounceUrl($mwMsg = 'clicky_urls') {
		global $wgTitle, $wgRequest;

		if($wgTitle->getNamespace() == NS_MAIN && $wgRequest->getVal('action') != 'edit') {
			$clicky_urls = urldecode(wfMsgForContent($mwMsg));
			$clicky_urls = split("\n", $clicky_urls);
			return false !== array_search("/" . urldecode($wgTitle->getPartialURL()), $clicky_urls);
		}
		return false;
	}

	function addRelatedImagesWidget() {
		global $wgTitle;

		$dbr = wfGetDB(DB_SLAVE);
		$wikitext = Wikitext::getWikitext($dbr, $wgTitle);
		if (preg_match('@\[\[Image:([^\]|]*)(\|[^\]]*)?\]\]@s', $wikitext, $m)) {
			$t = Title::newFromText($m[1], NS_IMAGE);
			$t->getArticleID();
			$html = ImageHelper::getRelatedImagesWidget($t);
			if ($html) {
				$this->addWidget($html);
			}
		}
	}

	function getAuthorHeader() {
		global $wgTitle, $wgRequest, $wgUser, $wgLanguageCode;
		if (!$wgTitle  || !($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT) || $wgRequest->getVal('action', 'view') != 'view'
			|| $wgRequest->getVal('diff') != '') return "";
		$this->loadAuthors();
		$html = "";
		// Logged in and intl users see this
		if($wgUser->getID() > 0 || $wgLanguageCode != 'en'){
			$users =  array_slice($this->mAuthors, 0, min(sizeof($this->mAuthors), 4));
			if (!empty($users)) {
				$html = wfMsg('originated_by') . "<span>" .  $this->formatAuthorList($users) . "</span>";
			}
		}
		else{
			$users =  array_slice($this->mAuthors, 0, min(sizeof($this->mAuthors), 1));
			if (!empty($users)) {
				$userCount = sizeof($this->mAuthors) - 1;
				$html = wfMsg('originated_by_anon') . "<span>" . $this->formatAuthorList($users, false) . "</span>";
				$others = $userCount > 1 ? "others" : "other";
				$html .= " and " . $this->makeLinkObj($wgTitle, "$userCount $others", "action=credits");
			}
		}

		if (empty($users))  {
			$html = "<span>&nbsp;</span>";
		}

		$html = "<p id='originators'>$html</p>";
		return $html;
	}

	function getAuthorFooter() {
		global $wgUser;
		$this->loadAuthors();
		if (sizeof($this->mAuthors) == 0) {
			return '';
		}
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

	function formatAuthorList($authors, $showAllLink = true) {
		global $wgTitle, $wgRequest, $wgMemc;

		if (!$wgTitle
			|| ($wgTitle->getNamespace() != NS_MAIN
				&& $wgTitle->getNamespace() != NS_PROJECT))
		{
			return '';
		}

		$action = $wgRequest->getVal('action', 'view');
		if ($action != 'view') return '';

		$articleID = $wgTitle->getArticleId();
		$authors_hash = md5( print_r($authors, true) );
		$cachekey = wfMemcKey('authors', $articleID, $authors_hash);
		$val = $wgMemc->get($cachekey);
		if ($val !== null) return $val;

		$links = array();
		foreach ($authors as $u => $p) {
			if ($u == 'anonymous') {
				$links[] = "<a href='/wikiHow:Anonymous'>" .wfMsg('anonymous') . "</a>";
			} else {
				$user = User::newFromName($u);
				if (!$user) continue;
				$name = $user->getRealName();
				if (!$name) $name = $user->getName();
				$links[] = "<a href='{$user->getUserPage()->getLocalURL()}'>{$name}</a>";
			}
		}
		$html = implode(", ", $links);
		if ($showAllLink) {
			$html .=  " (" . $this->makeLinkObj($wgTitle, wfMsg('see_all'), "action=credits")  . ")";
		}

		$wgMemc->set($cachekey, $html);

		return $html;
	}

	function getFacebookLocaleCode() {
		global $wgLanguageCode;
		// INTL: Facebook requires a locale code to display the appropriate language.
		// Since we're just updating spanish and german, create quick and dirty locale strings here
		$localeCode = "";
		if ($wgLanguageCode != 'en') {
			$localeCode = $wgLanguageCode . "_" . strtoupper($wgLanguageCode);
		}
		return $localeCode;
	}

	function outputPage(&$out) {
		global $wgTitle, $wgArticle, $wgUser, $wgLang, $wgContLang, $wgOut;
		global $wgScript, $wgStylePath, $wgLanguageCode, $wgContLanguageCode;
		global $wgMimeType, $wgOutputEncoding, $wgUseDatabaseMessages;
		global $wgRequest, $wgUseNewInterlanguage;
		global $wgDisableCounters, $wgLogo, $action, $wgFeedClasses;
		global $wgMaxCredits, $wgShowCreditsIfMax, $wgSquidMaxage, $IP;
		global $wgServer;

		$fname = 'SkinTemplate::outputPage';
		wfProfileIn( $fname );

		wfRunHooks( 'BeforePageDisplay', array(&$wgOut) );
		$this->mTitle = $wgTitle;

		extract( $wgRequest->getValues( 'oldid', 'diff' ) );

		wfProfileIn( "$fname-init" );
		$this->initPage( $out );
		$tpl =& $this->setupTemplate( $this->template, 'skins' );

		$tpl->setTranslator(new MediaWiki_I18N());
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

		$this->addFacebookMetaProperties($tpl->data['title']);
		$title = wfMsg('howto', $tpl->data['title']);

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
				require_once("$IP/includes/Credits.php");
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

		//$out->addHTML($printfooter);
		$tpl->setRef( 'bodytext', $out->getHTML() );

		# Language links
		# Language links
		$language_urls = array();
		if ( !$wgHideInterlanguageLinks ) {
			foreach( $wgOut->getLanguageLinks() as $l ) {
				$tmp = explode( ':', $l, 2 );
				$class = 'interwiki-' . $tmp[0];
				$lTitle = $tmp[1];
				unset($tmp);
				$nt = Title::newFromText( $l );
				$language_urls[] = array(
					'href' => $nt->getFullURL(),
					'text' =>  $lTitle,
					'class' => $class,
					'language' => ($wgContLang->getLanguageName( $nt->getInterwiki()) != ''?$wgContLang->getLanguageName( $nt->getInterwiki()) : $l) . ": "
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
		if($this->iseditable && $wgUser->getOption("editondblclick") ) {
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

		return false;
	}

	function generateBulletLink ($title, $msg, $options = null) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		return "<li>" . $sk->makeLinkObj($title, wfMsg($msg), $options) . "</li>";
	}
}

class WikiHowTemplate extends QuickTemplate {

	function findCategory($cat, $ptree) {
		if (is_array($ptree)) {
			foreach (array_keys($ptree) as $key) {
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

	private static function getDetailedTitle() {
		global $wgTitle, $wgOut;

		if ($wgTitle->getNamespace() == NS_MAIN) {
			// TODO from reuben: I temporarily remove all "(with pictures)" type
			// additions from article titles
			return wfMsg('pagetitle', '');

			// add to category list here
			$categories = array("STUB","Hobbies-and-Crafts","Food-and-Entertaining");
			$parentCategories = WikiHow::getCurrentParentCategoryTree();
			if (!$this->findCategory($categories, $parentCategories)) {
				return wfMsg('pagetitle', '');
			}

			$dbr = wfGetDB(DB_SLAVE);
			$wikitext = Wikitext::getWikitext($wgTitle);
			if ($wikitext) {
				list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
				list($details, ) = self::getTitleExtraInfo($wikitext, $stepsText);
			} else {
				$details = '';
			}

			return wfMsg('pagetitle', $details);
		}
	}
	
	public static function getTitleExtraInfo($wikitext, $stepsText) {
		$num_steps = Wikitext::countSteps($stepsText);
		$num_photos = Wikitext::countImages($wikitext);

		$stepsDetail = '';
		if ($num_steps >= 5 && $num_steps <= 25) {
			if ($num_photos > ($num_steps / 2) || $num_photos >= 6) {
				$titleDetail = ": $num_steps steps (with pictures)";
				$stepsDetail = ": $num_steps steps";
			} else {
				$titleDetail = ": $num_steps steps";
			}
		} else {
			if ($num_photos > ($num_steps / 2)) {
				$titleDetail = ' (with pictures)';
			} else {
				$titleDetail = '';
			}
		}
		
		return array($titleDetail, $stepsDetail);
	}


	// Added for quicknote
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

	}

	static $postLoadHtml = '';
	function addPostLoadedAdHTML($html) {
		self::$postLoadHtml .= $html;
	}

	function getPostLoadedAdsHTML() {
		$html = self::$postLoadHtml;
		self::$postLoadHtml = '';
		return $html;
	}

	/**
	 * Calls the MobileWikihow class to determine whether or
	 * not a browser's User-Agent string is that of a mobile browser.
	 */
	private static function isUserAgentMobile() {
		if (class_exists('MobileWikihow')) {
			return MobileWikihow::isUserAgentMobile();
		} else {
			return false;
		}
	}

	/**
	 * Calls the WikihowCSSDisplay class to determine whether or
	 * not to display a "special" background.
	 */
	private static function isSpecialBackground() {
		if (class_exists('WikihowCSSDisplay')) {
			return WikihowCSSDisplay::isSpecialBackground();
		} else {
			return false;
		}
	}

	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * right rail on the site shouldn't be displayed.
	 */
	static private function showSideBar() {
		$result = true;
		wfRunHooks('ShowSideBar', array(&$result));
		return $result;
	}

	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * bread crumb (category) links at the top of the article shouldn't
	 * be displayed.
	 */
	static private function showBreadCrumbs() {
		$result = true;
		wfRunHooks('ShowBreadCrumbs', array(&$result));
		return $result;
	}
	
	static $showRecipeTags = false;
	static $showhRecipeTags = false;
	
	//gotta be in the Recipes category and have an ingredients section
	public function checkForRecipeMicrodata() {
		global $wgTitle, $wgUser, $wgRequest, $wgArticle;
		if ($wgTitle->getNamespace() == NS_MAIN &&
			$wgTitle->exists() &&
			$wgRequest->getVal('oldid') == '' &&
			($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view')) {
			
			$sk = $wgUser->getSkin();
			if ($sk->mCategories['Recipes'] != null) {
				$wikihow = WikiHow::newFromCurrent();
				$index = $wikihow->getSectionNumber('ingredients');
				if ($index != -1) {
					self::$showRecipeTags = true;
					
					//our hRecipe subset
					if (stripos($wgTitle->getText(),'muffin') > 0) {
						self::$showhRecipeTags = true;
					}
				}
			}
		}
	}

	static $preptime_array = array(
		'Make-Gluten-Free-Chocolate-Coconut-Macaroons',
		'Make-Gluten-Free-Cheesy-Spinach-Quesadillas',
		'Make-Gluten-Free-Apple-Buckwheat-Cereal',
		'Make-Gluten-Free-Pancakes',
		'Make-Gluten-Free-Peanut-Butter-Cookies'
	);
	
	//grabs the html for adding CSS watermarks for certain articles
	//TODO: switch to exclude list if this list is over 50% of our articles
	public function getWatermark() {
		global $wgTitle;
		$html = '';
		$list = ConfigStorage::dbGetConfig('wikihow-watermark-article-list');
		$lines = preg_split('@[\r\n]+@', $list);
		if (in_array($wgTitle->getArticleID(),$lines)) {
			$html = '<div class="wikihow_watermark"></div>';;
		}
		return $html;
	}
	
	static $novideo_array = array(
		'Make-Onigiri',
		'Apply-False-Eyelashes',
		'Choose-a-Secure-Password',
		'Make-Martinis',
		'Sell-Original-Artwork-for-Profit',
		'Make-a-Spiral-Wire-Bead-Ring',
		'Make-Iced-Tea',
		'Build-a-Loft-Bed',
		'Bump-Fire',
		'Construct-a-Raised-Planting-Bed',
		'Fold-a-Traditional-Origami-Swan',
		'Paint-with-a-Compressed-Air-Sprayer',
		'Have-a-Photo-Shoot-at-Home',
		'Witness-the-Summer-Solstice',
		'Make-Aloo-Chat',
		'Grow-a-Moringa-Tree',
		'Make-a-Balloon-Stress-Ball',
		'Make-Brownies',
		'Make-a-Cartesian-Diver-with-a-Ketchup-Packet',
		"Pull-a-Horse's-Mane",
		'Sharpen-a-Pencil-With-a-Knife',
		'Teach-a-Child-Bilingual-Reading',
		'Organize-Your-Dresser',
		'Prevent-Excess-Gas',
		'Add-Power-to-Your-Baseball-Swing',
		'Seduce-an-Older-Woman',
		'Get-Movies-on-Your-PSP',
		'Make-Yema-Candy',
		'Swim-Free-Style-Correctly',
		'Remove-Your-Mustache-(for-Girls)',
		'Haggle',
		'Make-Rangoli',
		'Get-Your-Ex-Back',
		'Make-a-Manhattan',
		'Create-an-Animated-Movie-(Using-Windows-Movie-Maker)',
		'Convert-Between-Fahrenheit-and-Celsius',
		'Make-Your-Hair-Wavy-Easily',
		'Overcome-Feelings-of-Guilt',
		'Press-a-Shirt',
		'Grow-an-Herbal-Tea-Garden',
		'Make-a-Fake-Belly-Button-Piercing',
		'Make-Peppermint-Tea',
		'Care-for-Gerbils',
		'Make-a-"No-Kill"-Mouse-Trap',
		'Shave-With-an-Electric-Shaver',
		'Play-the-Didgeridoo',
		'Build-a-Pirate-Bar',
		'Blow-Dry-Hair-With-Natural-Waves',
		'Bleach-Your-Hair-With-Hydrogen-Peroxide',
		'Make-a-Scented-Candle-in-a-Glass',
		'Make-an-Ice-Cream-Soda',
		'Cover-a-Book',
		'Play-Speed',
		'Catch-a-Snake',
		'Do-a-Roundhouse-Kick',
		'Play-Urban-Golf',
		'Animate-With-Pivot-Stickfigure-Animator',
		'Make-a-Good-Confession-in-the-Catholic-Church',
		'Buy-a-Good-Used-Camera-Lens',
		'Win-a-Pie-Eating-Contest',
		'Convince-Anyone-of-Anything',
		'Care-for-a-Red-Eyed-Tree-Frog',
		'Become-a-Pro-Wrestler',
		'Intimidate-Opponents',
		'Grill-Fish',
		'Make-a-Trading-Card-Game',
		'Make-a-Living-Wall',
		'Impress-People-with-a-Quote',
		'Take-Care-of-a-Hedgehog',
		'Get-Your-Boyfriend-to-Hang-Out-With-You',
		'Shop-Well-for-Clothes-in-a-Thrift-Store',
		'Tie-a-Rethreaded-Figure-of-8-Climbing-Knot',
		'Make-Turkey-Soup',
		'Make-a-Homemade-Burrito',
		'Analyze-Poetry',
		'Make-Frozen-Hot-Chocolate',
		'Make-Felt',
		'Make-Candy-Sushi',
		'Look-Busy-at-Work-Without-Really-Working',
		'Use-Gparted',
		'Make-Sushi-Rice',
		'Do-a-Burnout',
		'Ship-a-Car',
		'Make-a-Lot-of-Bells-(Money)-in-Animal-Crossing:-Wild-World',
		'Prevent-Bed-Bugs',
		'Treat-Poison-Ivy-and-Poison-Oak',
		'Make-a-Fake-Wall-You-Can-Crash-Through',
		'Apply-Eyeliner-(Men)',
		'Make-a-Red-Pasta-Sauce',
		'Configure-Conky',
		'Improve-Your-Poker-Playing-Skills',
		'Brush-Teeth-Without-Toothpaste',
		'Teach-Your-Dog-to-Heel',
		'Free-Pour',
		'Apply-Makeup-(for-Teen-Girls)',
		'Start-up-a-Nitro-RC-Vehicle',
		'Avoid-the-Pow-Block-in-Mario-Kart-Wii',
		'Play-the-Flute',
		'Frontside-180-on-a-Snowboard',
		'Make-Paper-Plate-Jellyfish',
		'Throw-a-Sweet-Sixteen-Party',
		'Upgrade-an-Airsoft-AEG',
		'Design-a-Website',
		'Make-a-Rubber-Band-Guitar',
		'Choose-a-Bicycle',
		'Be-Creative',
		'Make-a-Machinima',
		'Win-in-Dodgeball',
		'Become-an-Excellent-Student',
		'Make-a-Strawberry-Banana-Smoothie',
		'Reuse-Empty-Pill-Bottles',
		'Make-an-Eiffel-Tower-Cake',
		'Get-More-Testosterone',
		'Get-Rid-of-the-Smell-of-Vomit-in-a-Carpet',
		'Fix-Grand-Am-Turn-Signals',
		'Create-a-Realistic-Fiction-Character',
		'Start-a-Charity',
		'Apply-Self-Tanner',
		'Get-Rid-of-Chapped-Lips-Without-Lip-Balm',
		'Do-Abdominal-Breathing',
		'Survive-Job-Layoffs',
		'Find-a-Low-Airfare',
		'Ride-a-Motorcycle',
		'Make-Slime-Without-Borax',
		'Get-Colored-Contacts-to-Change-Your-Eye-Color',
		'Cook-Without-a-Food-Processor',
		'Make-Rabbit-Fricassee',
		'Draw-a-Realistic-Dog',
		'Clean-a-House',
		'Teach-a-Dog-to-Smile',
		'Make-a-Haunted-House-in-Your-Front-Yard',
		'Make-Your-Relationship-Work',
		'Bring-out-Natural-Red-and-Blond-Highlights',
		'Make-a-Toy-Parachute',
		'Create-a-Marie-Antoinette-Costume',
		'Sew-in-a-Zipper',
		'Speak-Simple-German',
		'Kiss-With-Braces',
		'Change-a-Lock',
		'Make-Mango-Shakes',
		'Play-Dungeons-and-Dragons',
		'Overcome-Failure',
		'Inflate-Bike-Tires',
		'Curl-Hair-with-a-Curling-Iron',
		'Convince-People-to-Go-Skinny-Dipping',
		'Make-Your-Own-Deodorant-Spray',
		'Clone-a-Partition',
		'Make-a-Baby-Guinness',
		'Make-a-Laceâ€“Up-"Corset"-Tâ€“Shirt',
		'Make-Old-Fashioned-Hard-Candy',
		'Look-Pretty-in-Glasses',
		'Look-Good-Naked-(Girls-Version)',
		'Stop-Swearing',
		'Cut-a-Mango',
		'Look-Like-Hannah-Montana',
		'Shell-Pecans',
		'Stop-Eye-Twitching',
		'Use-a-Wood-Lathe',
		'Use-an-Abacus',
		'Bake-Chocolate-Chip-Cookie-Dough-Cheesecake',
		'Calculate-the-Volume-of-a-Cone',
		'Look-Like-Selena-Gomez',
		'Travel-with-a-Cat',
		'Chop-Onions-Without-Tears',
		'Emulate-a-Remote-Linux-Desktop-from-Microsoft-Windows',
		'Choose-a-Camera-Shutter-Speed',
		'Build-a-Big-Chest',
		'Get-99-Magic-in-Runescape',
		'Deal-With-an-Annoying-Manager',
		'Create-a-Family-in-Sims',
		'Kill-Your-Sim-in-the-Sims-2',
		'Scrunch-Hair',
		'Clean-the-Steam-Iron-and-Its-Base-Plate',
		'Create-a-Photo-Slideshow-with-PowerPoint',
		'Make-Pasta-Sauce',
		'Make-a-Gravity-Bong-in-10-Minutes',
		'Make-Money-As-a-Teen-by-Working-for-Yourself',
		'Create-a-Classic-Wardrobe',
		'Make-Scrambled-Eggs',
		'Fold-Bath-Towels-for-Quick-Hanging-at-Home',
		'Bake-a-Winter-Squash',
		'Use-the-Google-Earth-Flight-Simulator',
		'Do-a-Handstand-and-Stay-Up',
		'Clean-Silver',
		'Stretch-for-Ballet',
		'Choose-Your-Prom-Dress',
		'Make-Glowing-Bottles-for-a-Blacklight',
		'Dress-Cool',
		'Stick-to-Your-Diet-During-the-Holidays',
		'Flip-a-House',
		'Make-a-Cootie-Catcher',
		'Push-Start-a-Car',
		'Cast-a-Love-Spell',
		'Make-Spanakopita',
		'Choose-Knitting-Yarn',
		'Use-a-Gophone-Plan-With-an-iPhone',
		'Choose-a-Sewing-Machine',
		'Create-Your-Acting-Resume',
		'Know-if-You-are-Pregnant',
		'Make-Friends-in-Middle-School',
		'Buff-Your-Nails',
		'Make-Pin-Straight-Hair',
		'Flirt-With-a-Girl-on-Facebook',
		'Get-a-Good-Work-out-with-Punching-Bag',
		'Make-Balloon-Animals',
		'Play-Egyptian-Rat-Screw',
		'Slalom-Ski-(Water-Ski-on-One-Ski)',
		'Make-Kettle-Corn',
		'Apply-Rainbow-Eyeshadow',
		'Make--a-Slurpee',
		'Revise-for-Your-GCSEs',
		'Adjust-a-Front-Bicycle-Derailleur',
		'Do-the-Cat-Daddy',
		'Repaint-a-Chair',
		'Remove-a-Sticker-from-Plastic',
		'Make-Soft-Serve-Ice-Cream',
		'Make-a-Rain-Stick',
		'Make-Lemonade',
		'Lose-Weight-in-Less-Than-7-Days',
		'Speak-Basic-French',
		'Care-for-Guppies',
		'Find-out-Your-IP-Address',
		'Clean-Your-Computer-System',
		'Do-Really-Good-Pushups',
		'Start-Living-Frugally',
		'Afford-an-Expensive-Guitar',
		'Make-Fried-Pickles',
		'Tell-a-Guy-You-Love-Him',
		'Avoid-Mosquito-Bites',
		'Multiply-Square-Roots',
		'Make-Cotton-Candy',
		'Remove-and-Prevent-Split-Ends',
		'Build-a-Fast-Shelter-in-the-Wilderness',
		'Audition-For-a-Musical',
		'Cook-Deep-Fried-Beer',
		'Use-a-Trangia-Camping-Stove',
		'Disassemble-a-Wireless-Xbox-360-Controller-for-Painting',
		'Overcome-Sadness',
		'Be-a-Fast-Runner',
		'Eat-Sushi',
		'Spread-a-Sense-of-Humour',
		'Curl-Hair-with-Straighteners',
		'Make-Money-on-Nintendogs',
		'Get-Rid-of-Pigeons',
		'Treat-Menopause-Symptoms',
		'Register-Friends-on-Your-Wii',
		'Improve-Your-Email-Etiquette',
		'Fight-Fair-in-Relationships',
		'Make-a-Duct-Tape-Cell-Phone-Case',
		'Cure-Scabies',
		'Knit-the-Purl-Stitch',
		'Use-the-Law-of-Attraction',
		'Use-a-Semicolon',
		'Make-Cheap-Vodka-Taste-Better',
		'Save-the-Environment-at-Home',
		'Use-a-Meat-Thermometer',
		'Memorize-a-List-in-Order',
		'Hack-on-Dragonfable',
		'Get-a-Cat-out-of-a-Tree',
		'Write-a-Good-Guitar-Solo',
		'Make-a-Crystal-Radio',
		'Fold-an-Origami-Star-(Shuriken)',
		'Hack-Windows',
		'Make-a-Pisco-Sour',
		'Throw-a-Dropball',
		'Learn-All-About-Polymer-Clay',
		'Wash-Dishes',
		"Draw-an-Anime-Girl's-Face",
		'Skimboard',
		'Choose-Olive-Oil',
		'Give-a-Performance-Review-of-an-Employee',
		'Make-Chili-Con-Carne',
		'Stir-Fry',
		'Have-Fun-at-a-Sleepover-(for-Teen-Girls)',
		'Do-a-Round-off-Back-Handspring-Back-Tuck-on-the-Floor',
		'Understand-Cuts-of-Beef',
		'Instantly-Freeze-a-Beer-or-Other-Bottled-Drink',
		'Make-Denim-Cut-off-Shorts',
		'Deal-With-the-Discomfort-when-Meeting-an-Ex-Lover',
		'Create-an-Origami-Puppy-Finger-Puppet',
		'Make-Weapons-out-of-Sticks',
		'Make-Apple-Chips',
		'Pan-Sear-a-Steak',
		'Have-a-God-Centered-Dating-Relationship',
		'Be-a-Metal-Chick',
		'Get-Rid-of-Dandruff-(Natural-Methods)',
		'Play-the-Saw',
		'Arc-Weld',
		'Make-Lotion',
		'Cope-with-Loss-and-Pain',
		'Make-a-Didgeridoo-out-of-PVC-Pipe',
		'Find-the-Degree-of-a-Polynomial',
		'Make-an-Airsoft-Smoke-Grenade',
		'Play-Paper-Football',
		'Create-a-Community-on-Orkut',
		'Change-a-Duvet-Cover',
		'Draw-a-Template-Manga-Head',
		'Jailbreak-an-Ipod-Touch-1.1.5-or-Lower',
		"Dress-to-Meet-Your-Boyfriend's-Parents",
		'Get-Rid-of-Chapped-Lips',
		"Get-the-Biggoron's-Sword-in-the-Legend-of-Zelda,-Ocarina-of-Time",
		'Get-on-a-Reality-TV-Show',
		'Make-Tortilla-de-Patatas',
		'Open-Two-Beer-Bottles-With-Each-Other',
		'Say-Most-Common-Words-in-Urdu',
		'Breakdance',
		'Prevent-Herpes',
		'Quit-Chewing-Tobacco',
		'Install-Plastic-Lawn-Edging',
		'Tame-a-Rat',
		'Overcome-Laziness',
		'Draw-an-Anime-Face',
		'Make-Coffee',
		'Set-up-a-Marine-Reef-Aquarium',
		'Increase-Your-Fertility',
		'Rescue-an-Avalanche-Victim',
		'Build-a-Cajon',
		'Have-Fun-While-Studying',
		'Change-a-Bicycle-Brake-Cable',
		'Make-a-Choker',
		'Create-Pivot-Tables-in-Excel',
		'Eat-a-Rambutan',
		'Calibrate-Your-Sprinklers',
		'Fold-a-Towel-Elephant',
		'Get-the-Most-Benefit-from-Laser-Hair-Removal',
		'Make-Elephant-Toothpaste',
		'Dance-Emo',
		'Wear-Leg-Warmers',
		'Make-Skinny-Jeans',
		'Fix-Your-iPod-Jack',
		'Bake-Potatoes',
		'Resign-from-a-Job',
		'Learn-American-Sign-Language',
		'Do-a-Back-Bend',
		'Make-a-Fake-Wound',
		'Pump-Gas',
		'Reupholster-a-Dining-Chair-Seat',
		'Pig-Squeal-(Bree)',
		'Configure-CD-and-DVD-Autoplay-in-Windows-XP',
		'Be-a-Teenage-Hipster(Girls)',
		'Discipline-Yourself',
		'Organize-Receipts',
		'Make-Chinese-Dumplings',
		'Develop-Persuasive-Speech-Topics',
		'Find-Out-if-Your-Ex-Still-Likes-You',
		'Go-Clubbing',
		'Catch-Trout',
		'Be-a-Corpse-Bride-for-Halloween',
		'Make-a-Vodka-and-Tonic',
		'Differentiate-Pressed-from-Cut-Glass',
		'Run-Faster',
		'Have-a-Zen-Attitude',
		'Call-a-Mobile-Phone-Overseas',
		'Make-Mochi',
		'Create-a-Simple-Web-Page-With-HTML',
		'Make-Pesto',
		'Keep-a-Pet-Praying-Mantis-Without-a-Cage',
		'Save-Money-on-Auto-Insurance',
		'Make-Limeade',
		'Limit-Your-Mistakes-During-a-Job-Interview',
		'Care-for-an-Autistic-Child',
		'Use-Dowsing-or-Divining-Rods',
		'Bring-Out-the-Natural-Curl-in-Your-Hair',
		'Embed-YouTube-Flash-Videos-in-Your-PowerPoint-Presentations',
		'Take-Care-of-a-Lip--Piercing',
		'Speak-Mandarin-Chinese-in-a-Day',
		'Make-a-Gothic-Fairy-Costume',
		'Bellydance-Like-Shakira',
		'Get-Rid-of-a-Mosquito-Bite',
		'Play-RuneScape',
		'Roll-a-Burrito',
		'Overcome-a-Chocolate-Addiction',
		'Decline-an-Invitation-to-Dinner-or-Other-Social-Event',
		'Hack-Minesweeper',
		'Salute-Like-a-Soldier',
		'Look-Taller',
		'Hardcore-Dance',
		'Build-a-Cornhole-Game',
		'Deal-With-Difficult-Relatives',
		"Do-Your-Hair-Like-Alice-Cullen's-Hair",
		'Make-a-Magic-Staff',
		'Choose-an-Electric-Guitar',
		'Apply-Hair-Wax',
		'Make-Ice-Cream-with-Snow',
		'Make-Popcorn-on-the-Stove',
		'Stretch-a-Canvas',
		'Write-a-Horror-Story',
		'Make-Chicken-Soup',
		'Deal-with-a-Bipolar-Family-Member',
		'Make-an-Egg-Wash',
		'Join-Up-With-a-Horse',
		'Create-Programs-on-a-Ti-83-Graphing-Calculator',
		'Repair-Your-Credit',
		'Act-Like-Sasuke',
		'Cut-Your-Own-Bangs',
		'Change-Windows-XP-Home-to-Windows-XP-Professional',
		'Play-Quarters',
		'Get-Over-a-Guy',
		'Shock-Your-Swimming-Pool',
		'Say-Common-Words-in-Bengali',
		'Take-Better-Photographs',
		'Study-for-an-Approaching-Exam',
		'Teach-Your-Dog-Tricks',
		'Cure-a-Fever-at-Home',
		'Calculate-Overhead',
		'Pick-out-an-Outfit',
		'React-to-a-Gift-You-Do-Not-Like',
		'Make-a-Button-in-Flash-Cs4',
		'Get-Relaxed-Before-Bed',
		'Be-a-Popular-Girl',
		'Delete-Video-from-an-iPod',
		'Ask-a-Guy-to-Homecoming',
		'Bleach-Hair-Blonde',
		'Walk-in-High-Heels',
		'Install-a-DRIcore-Subfloor-in-Your-Basement',
		'Be-a-Good-Boss',
		'Save-a-Friendship',
		'Use-a-Neti-Pot',
		'Throw-a-Cut-Fastball',
		'Make-Your-Own-Clove-Cigarettes/Kretek',
		'Care-for-a-Neglected-Dog',
		'Tell-the-Difference-Between-a-King-Snake-and-a-Coral-Snake',
		'Survive-a-Dirty-Bomb-(Radiological-Dispersion-Device)',
		'Sing-Like-a-Professional',
		'Serve-a-Tennis-Ball',
		'Care-for-an-American-Toad',
		'Handle-College-Rejection-Letters',
		'Grow-out-Bangs',
		'Buy-Cheap-Airline-Tickets',
		'Serve-a-Volleyball-Overhand',
		'Cut-Good-Layered-Bangs',
		'Make-a-Thanksgiving-Centerpiece',
		'Make-Soy-Yogurt',
		'Use-These-and-Those',
		'Persuade-People-with-Subconscious-Techniques',
		'Become-a-Star-Wars-Fan',
		'Block-Someone-in-Facebook-Chat',
		'Organize-Your-Desktop',
		'Identify-if-You-Are-in-an-Abusive-Relationship',
		'Organize-Your-School-Supplies',
		'Recycle',
		'Create-a-Paper-Bag-Book-Cover',
		'Be-Romantic',
		'Have-a-Long-Passionate-Kiss-With-Your-Girlfriend/Boyfriend',
		'Get-a-Haircut-You-Will-Like',
		'Tie-a-Prusik-Knot',
		'PurÃ©e-Carrots',
		'Crack-Your-Neck',
		'Make-Worm-Castings-Tea',
		'Start-a-Relationship',
		'Create-an-iPhone-Alarm-That-Will-Vibrate-Without-Ringing',
		'Locate-a-Book-in-a-Library',
		'Catch-a-Crayfish',
		'Kayak',
		'Delete-Friends-on-MySpace',
		'Make-Green-Beer',
		'Have-a-Balanced-Lifestyle',
		'Write-a-Limerick',
		'Calculate-the-Circumference-of-a-Circle',
		'Apply-Eyeshadow-That-Lasts',
		'Snowboard',
		'Make-a-Google-Account',
		'Build-Wooden-Bookshelves',
		'Prepare-for-a-Behavioral-Interview',
		'Build-a-Sandbox',
		'Change-Your-Desktop-Background-in-Windows',
		'Get-a-Car-Rental-Discount-Code',
		'Peel-a-Pomelo',
		'Make-a-DMG-File-on-a-Mac',
		'Play-the-Cello',
		'Separate-an-Image-from-Its-Background-(Photoshop)',
		'Do-Envelope-Budgeting',
		'Make-a-Baggy-T-Shirt-Fitted',
		'Infuse-Vodka-with-Flavor',
		'Restore-Factory-Settings-in-Microsoft-Word',
		'Make-a-Messy-Bun',
		'Foundation-Piece-a-Quilt-Block',
		"Opt-out-of-Facebook's-Open-Graph-Personalization'",
		'Move-from-Windows-to-Linux',
		'Exfoliate-Lips',
		'Store-Fresh-Basil',
		'Bridle-a-Horse',
		'Meditate-for-Beginners',
		'Buy-a-New-Car-Through-Fleet-Sales',
		'Say-Greetings-and-Goodbyes-in-Spanish',
		'Open-a-Bottle-of-Wine',
		'Pick-Up-a-Girl-in-a-Club',
		'Make-Homemade-Onion-Rings',
		'Create-a-Provisioning-Profile-for-iPhone',
		'Make-Non-Slip-Socks',
		'Draw-in-8-Bit',
		'Cool-Your-Cat-Down-in-the-Summer',
		'Learn-Morse-Code',
		'Eat-Chocolate',
		'Treat-Your-Sick-Hamster',
		'Buy-a-New-Car',
		'Make-Cheese-at-Home',
		'Deal-With-a-Moody-Boss',
		'Get-Rid-of-Mold-Smell-in-Front-Loader-Washing-Machine',
		'Understand-Offside-in-Soccer-(Football)',
		'Make-Garlic-Bread',
		'Make-Potato-Pancakes',
		'Prevent-Identity-Theft',
		'Set-up-a-Fireworks-Show',
		'Practice-Mindfulness-(Buddhism)',
		'Call-CQ-on-Amateur-Radio',
		'Pack-Your-Carry-on-Bag',
		'Make-a-Temporary-Tattoo-with-Nail-Polish',
		'Overcome-Stage-Fright',
		'Defend-Yourself-in-an-Extreme-Street-Fight',
		'Grind',
		'Make-Cornbread',
		'Play-Risk',
		'Make-a-Baked-Cheesecake',
		'Be-a-Sixties-Girl',
		'Prepare-and-Use-Strawberries',
		'Write-a-Novel',
		'Save-Electricity',
		'Make-a-Punching-Bag',
		"Tie-a-Quick-Release-Knot-(Highwayman's-Hitch)",
		'Read-Faces',
		'Make-Tater-Tot-Hotdish',
		'Enjoy-a-Beer-Festival',
		'Grow-Medical-Marijuana',
		'Go-Through-Airport-Security-Smoothly',
		"Know-if-You-Like-Someone-or-if-You're-Just-Lonely",
		'Make-Delicious-Pretzel-Rolo-Cookies',
		'Make-Friends-in-College',
		'Create-a-Google-Map-With-Excel-Data-and-Fusion-Tables',
		'Free-up-Hard-Disk-Space-on-Windows-Vista',
		'Get-a-Song-Out-of-Your-Head',
		'Get-Rid-of-Acne-if-You-Have-Fair-Skin',
		'Accessorize',
		'Make-a-Yarn-Octopus',
		'Cook-Like-a-Roman',
		'Recover-from-Back-to-Work-Blues',
		'Teach-Kids-About-Money',
		'Sharpen-a-Chainsaw',
		'Give-a-Small-Dog-a-Bath',
		'Grow-Strawberries',
		'Care-for-a-Pet-Rat',
		'Paint-Fire',
		'Influence-Your-Dreams',
		'Do-a-Scratch-Spin',
		'Make-Fish-Curry',
		'Get-Rid-of-an-Inferiority-Complex',
		'Plan-Your-Wedding',
		'Get-Rid-of-Toe-Fungus',
		'Get-Rid-of-Poison-Ivy-Plants',
		'Make-Fake-Rocks-for-Your-Pond',
		'Make-Khasta-Kachori',
		'Build-a-Small-Rocket',
		'Make-a-Doggie-Birthday-Cake',
		'Make-a-Motorcycle-out-of-Old-Watches',
		'Get-to-El-Nath-in-Maplestory',
		'Make-a-Hot-Soothing-Lemon-Drink',
		'Look-Like-a-Zombie',
		'Delegate',
		'Make-a-Lesson-Plan',
		'Change-the-Oil-in-Your-Car',
		'Make-Home-Made-Fly-Trap',
		'Write-a-Critical-Essay',
		'Lock-iPad-Screen-Orientation',
		'Balance-a-Soccer-Ball-on-Your-Foot',
		'Propose-to-a-Woman',
		'Get-Your-Ex-Boyfriend-Back-with-Your-Looks',
		'Ask-Out-a-Girl-at-School',
		'Avoid-Becoming-a-Weeaboo',
		'Keep-a-Career-Log',
		'Change-your-IP-Address-(Windows)',
		'Conceal-the-Common-Signs-of-Aging',
		'Make-a-Kaleidoscope',
		'Take-Derivatives-in-Calculus',
		'Remove-Sweat-Stains-With-Aspirin',
		'Use-an-Epipen',
		'Enjoy-a-Museum',
		'Solve-Two-Step-Algebraic-Equations',
		'Act-Like-an-Anime-or-Manga-Character',
		'Remember-Things-You-Study-Better',
		'Subtract-Binary-Numbers',
		'Plant-a-Herb-Pot',
		'Shave-Chest-Hair',
		'Play-as-Luigi-in-New-Super-Mario-Bros.-DS',
		'Send-Files-to-a-Cell/Mobile-Phone-Using-Bluetooth-Technology',
		'Beat-Anxiety-About-Speaking',
		'Order-Coffee',
		'Put-a-Fancy-Edge-on-a-Pie-Crust',
		'Use-the-Slope-Intercept-Form-(in-Algebra)',
		'Dive-Off-a-Cliff',
		'Create-an-RSS-Feed',
		'Drastically-Reduce-the-Cost-to-Heat-Your-Swimming-Pool',
		'Make-a-Tangram',
		'Do-The-Box-(Front)-Splits',
		'Write-a-Eulogy',
		'Draw-a-Whale',
		'Build-Your-Personal-Brand',
		'Make-a-Three-Pendulum-Rotary-Harmonograph',
		'Do-the-Michael-Jackson-Side-Slide',
		'Change-The-Brake-Pads-in-Your-Car',
		'Predict-the-Weather-Without-a-Forecast',
		'Hit-a-Normal-Pitch-Shot',
		'Freeze-Cauliflower',
		'Dress-in-a-Kimono',
		'Be-a-Hippie',
		'Become-an-Atheist',
		'Make-an-Origami-Bunny',
		'Install-a-Flat-Panel-TV-on-a-Wall-With-No-Wires-Showing',
		'Clean-Your-Room-Fast',
		'Choose-a-Portable-Air-Compressor',
		'Make-a-California-Roll',
		'Create-a-Simple-Program-in-C++',
		'Shoot-a-Basketball',
		'Use-a-Circular-Saw',
		'BBQ-or-Grill-a-Whole-Fish-Without-Burning',
		'Follow-the-Curly-Girl-Method-for-Curly-Hair',
		'Keep-a-Man-Interested',
		'Do-Facebook-Nails',
		'Create-Animated-GIFs-Using-Photoshop-CS3',
		'Make-Risotto',
		'Format-a-Hard-Drive',
		'Make-a-Ninja-Mask',
		'Become-a-Voice-Actor/Voiceover-Artist',
		'Cook-Grasshoppers',
		'Select-Shoes-to-Wear-with-an-Outfit',
		'Kickflip-on-a-Tech-Deck',
		'Give-a-Subcutaneous-Injection',
		'Do-the-Chicken-Dance',
		'Travel-With-Children-on-Long-Trips',
		'Add-a-Layer-Mask-in-Photoshop',
		'Become-a-US-Citizen',
		'Accessorize-With-Jewelry',
		'Stay-Friends-with-Your-Ex',
		'Make-Your-Own-Vinegar',
		'Kill-Time-During-a-Long-Layover-in-San-Juan,-Puerto-Rico',
		'Escape-a-Stranded-Elevator',
		'Make-a-Cloak',
		'Make-Asparagus-Wrapped-in-Bacon',
		'Make-a-Paper-Boat',
		'Pack-a-Suit-Into-a-Suitcase',
		'Book-a-Hotel-Room',
		'Reuse-Old-Wine-Corks',
		'Tell-if-an-Egg-is-Bad',
		'Wave-Your-Hair-With-a-Straight-Iron',
		'Look-Like-Agyness-Deyn',
		'Treat-a-Wound',
		'Get-a-Job',
		'Dress-for-a-First-Date',
		'Circular-Breathe',
		'Shift-in-a-Drag-Race',
		'Bridge-an-Amplifier',
		'Spin-a-Basketball-on-Your-Finger',
		'Play-Disk-Golf',
		"Plan-the-Perfect-Valentine's-Day-for-Your-Husband",
		'Play-Softball',
		'Survive-the-First-Month-of-New-Motherhood',
		'Deal-With-and-Recover-From-Complete-Knee-Replacement-Surgery',
		'Clean-Chocolate-from-a-Carpet',
		'Sterilize-Water-With-Sunlight',
		'Care-for-a-Christmas-Tree',
		'Play-Hopscotch',
		'Make-a-Cat-Scratching-Post',
		'Learn-Japanese',
		'Make-a-Cigarette-Disappear',
		'Handle-Long-Layovers-at-an-Airport',
		'Develop-Healthy-Eating-Habits',
		'Cut-a-Cigar',
		'Find-Your-WEP-Code',
		'Count-to-10-in-Korean',
		'Spot-a-Wall-Flip',
		'Make-a-Simple-Remedy-for-Sore-Throat',
		'Throw-a-Super-Bowl-Party',
		'Make-Mint-Tea',
		'Make-Banana-Pudding',
		'Make-Cookie-Cutters-from-Regular-Aluminum-Foil',
		'Make-a-Cute-Duct-Tape-Bracelet',
		'Start-a-Personal-Development-Plan',
		'Self-Publish-a-Book',
		'French-Knit',
		'Light-a-Pumpkin-for-Halloween',
		'Preheat-an-Oven',
		'Blanch-Asparagus',
		'Be-Like-Cassie-from-Skins',
		'Make-a-Dutch-Braid',
		'Set-a-Mousetrap',
		'Make-a-3D-Cube',
		'Set-up-Port-Forwarding-on-a-Router',
		'Make-Cocoa-Lip-Balm',
		'Look-Good-in-a-School-Uniform-(Girls)',
		'Reuse-Styrofoam',
		"Deal-With-a-Boyfriend-Who-Has-Asperger's-Syndrome",
		'Personalize-Your-Locker',
		'Find-the-Least-Common-Multiple-of-Two-Numbers',
		'Change-Xbox-Live-Gamertag',
		'Get-Started-with-IRC-(Internet-Relay-Chat)',
		'Call-in-Sick',
		'Get-Your-Song-on-the-Radio',
		"Get-the-Perfect-Valentine's-Gift-for-Your-Boyfriend",
		'Dance-at-High-School-Dances',
		'Draw-a-Puppy',
		'Get-to-Sleep-on-Christmas-Eve'
	);

	static $NoVideo = false;

	public function checkForNoVideo() {
		global $wgTitle, $wgUser, $wgRequest;
		if ($wgTitle->getNamespace() == NS_MAIN &&
			in_array($wgTitle->getDBkey(),self::$novideo_array) &&
			$wgRequest->getVal('oldid') == '' &&
			($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view')) {
				self::$NoVideo = true;
		}
	}
	
	/**
		
	 * Insert ad codes into the body of the article
	 */
	public static function mungeSteps($body, $opts = array()) {
		global $wgWikiHowSections, $wgTitle, $wgUser;
		$ads = $wgUser->getID() == 0 && !@$opts['no-ads'];
		$parts = preg_split("@(<h2.*</h2>)@im", $body, 0, PREG_SPLIT_DELIM_CAPTURE);
		$reverse_msgs = array();
		$no_third_ad = false;
		foreach ($wgWikiHowSections as $section) {
			$reverse_msgs[wfMsg($section)] = $section;
		}
		$charcount = strlen($body);
		$body= "";
		for ($i = 0; $i < sizeof($parts); $i++) {
			if ($i == 0) {

				if ($body == "") {
					// if there is no alt tag for the intro image, so it to be the title of the page
					preg_match("@<img.*mwimage101[^>]*>@", $parts[$i], $matches);
					if (sizeof($matches) > 0) {
						$m = $matches[0];
						$newm = str_replace('alt=""', 'alt="' . htmlspecialchars($wgTitle->getText()) . '"', $m);
						if ($m != $newm) {
							$parts[$i] = str_replace($m, $newm, $parts[$i]);
						}
						
						//if it's a recipe, add microdata
						if (self::$showRecipeTags) {
							if (self::$showhRecipeTags) {
								$parts[$i] = preg_replace('/mwimage101"/','mwimage101 photo"',$parts[$i], 1);
							}
							else {
								$parts[$i] = preg_replace('/mwimage101"/','mwimage101" itemprop="image"',$parts[$i], 1);
							}
						}
					}
					
					//if it's a recipe, add microdata
					if (self::$showRecipeTags) {
						if (self::$showhRecipeTags) {
							$parts[$i] = preg_replace('/\<p\>/','<p class="summary">',$parts[$i], 1);
						}
						else {
							$parts[$i] = preg_replace('/\<p\>/','<p itemprop="description">',$parts[$i], 1);
						}
					}
					
					// done alt test
					$anchorPos = stripos($parts[$i], "<a name=");
					if($anchorPos > 0 && $ads){
						$content = substr($parts[$i], 0, $anchorPos);
						$count = preg_match_all('@</p>@', $parts[$i], $matches);
						
						if($count == 1) //this intro only has one paragraph tag
							$class = 'low';
						else {
							$endVar = "<p><br /></p>\n<p>";
							$end = substr($content, -1*strlen($endVar));

							if($end == $endVar) {
								$class = 'high'; //this intro has two paragraphs at the end, move ads higher
							}
							else{
								$class = 'mid'; //this intro has no extra paragraphs at the end.
							}
						}
						
						
						if(stripos($parts[$i], "mwimg") != false){
							$body = "<div class='article_inner editable'>" . $content . "<div class='ad_image " . $class . "'>" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
						}else{
							$body = "<div class='article_inner editable'>" . $content . "<div class='ad_noimage " . $class . "'>" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
						}
					}
					else if($anchorPos == 0 && $ads){
						$body = "<div class='article_inner editable'>{$parts[$i]}" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>\n";
					}
					else
						$body = "<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
				continue;
			}
			
			if (stripos($parts[$i], "<h2") === 0 && $i < sizeof($parts) - 1) {
				preg_match("@<span>.*</span>@", $parts[$i], $matches);
				$rev = "";
				if (sizeof($matches) > 0) {
					$h2 =  trim(strip_tags($matches[0]));
					$rev = isset($reverse_msgs[$h2]) ? $reverse_msgs[$h2] : "";
				}
				
				if ($rev == "video" && self::$NoVideo) {
					//nothing
				}
				else {
					$body .= $parts[$i];
				}
				
				$i++;
				if ($rev == "steps") {
					if (self::$showRecipeTags) {
						if (self::$showhRecipeTags) {
							$recipe_tag = " instructions'";
						}
						else {
							$recipe_tag = "' itemprop='recipeInstructions'";
						}
					}
					else {
						$recipe_tag = "'";
					}
					$body .= "\n<div id=\"steps\" class='editable{$recipe_tag}>{$parts[$i]}</div>\n";
				} else if ($rev == "video" && self::$NoVideo) {
					//nothing
				} else if ($rev != "") {
					$body .= "\n<div id=\"{$rev}\" class='article_inner editable'>{$parts[$i]}</div>\n";
				} else {
					$body .= "\n<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
			} else {
				$body .= $parts[$i];
			}
		}
		
		#echo $body; exit;
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
			// for the redesign we need some extra formatting for the OL, etc
	#print_r($parts); exit;
			$levelstack = array();
			$tagstack = array();
			$current_tag = "";
			$current_li = 1;
			$donefirst = false; // used for ads to tell when we've put the ad after the first step
			$bImgFound = false;
			$the_last_picture = '';
			$final_pic = array();
			$alt_link = array();
			
			#foreach ($parts as $p) {
			//XX Limit steps to 100 or it will timeout

			if ($numsteps < 300) {

				while ($p = array_shift($parts)) {
					switch (strtolower($p)) {
						case "<ol>":
							$level++;
							if ($level == 1)  {
								$p = '<ol class="steps_list_2">';
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
								$p = '<li><div class="step_num">' . $li_number . '</div>';
									
								
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
											$p .= "<b class='whb'>";
											$apply_b = true;
										}
										if ($apply_b) {
											$x = preg_replace("@([{$punct}])@im", "</b>$1", $x, 1, $closecount);
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
									$p .= wikihowAds::getAdUnitPlaceholder(0);
									$donefirst = true;
								}

							} else if ($current_tag == "ol") {
								//$p = '<li><div class="step_num">'. $current_li++ . '</div>';
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
					$p = preg_replace("@<li[^>]*>@i", '<li class="steps_li final_li">', $p);
					$gotit = true;
				} else if (strpos($lp, "<ul") !== false ){
					$level--;
				} else if (strpos($lp, "<ol") !== false ) {
					$level--;
				} else if ($lp == "</li>" && !$donelast) {
					// ads after the last step
					if ($ads){
						if(substr($body, $j) == ""){
							$p = "<script>missing_last_ads = true;</script>" . wikihowAds::getAdUnitPlaceholder(1) . $p;
							$no_third_ad = true;
						}
						else {
							$p = wikihowAds::getAdUnitPlaceholder(1) . $p;
						}
					}
					$donelast = true;
				}
				$steps = $p . $steps;
			}
			
			$body = substr($body, 0, $i) . $steps . substr($body, $j);
			
		} /// if numsteps == 100?
		
		//prep time test
		if (self::$showRecipeTags &&  in_array($wgTitle->getDBkey(),self::$preptime_array)) {
			$dbkey = $wgTitle->getDBkey();
			foreach ($wgWikiHowSections as $s) {
				if ($s == "ingredients") {
					if ($dbkey == 'Make-Gluten-Free-Chocolate-Coconut-Macaroons') { 
						$preptime = 'PT10M';
						$cooktime = 'PT20M';
					}
					else if ($dbkey == 'Make-Gluten-Free-Cheesy-Spinach-Quesadillas') {
						$preptime = 'PT5M';
						$cooktime = 'PT8M';
					}
					else if ($dbkey == 'Make-Gluten-Free-Apple-Buckwheat-Cereal') {
						$preptime = 'PT5M';
						$cooktime = 'PT10M';
					}
					else if ($dbkey == 'Make-Gluten-Free-Pancakes') {
						$preptime = 'PT5M';
						$cooktime = 'PT10M';
					}
					else if ($dbkey == 'Make-Gluten-Free-Peanut-Butter-Cookies') {
						$preptime = 'PT10M';
						$cooktime = 'PT8M';
					}
					/*preg_match('@Prep Time:(.*?)<br@',$body,$matches);
					$preptime = preg_replace('@Prep Time:</b>@','',$matches[0]);
					$preptime = preg_replace('@minutes<br@','',$preptime);
					$preptime = 'PT'.trim($preptime).'M';*/
					
					$body = preg_replace('@Prep Time:</b> @','Prep Time:</b> <meta itemprop="prepTime" content="'.$preptime.'" />',$body);
					$body = preg_replace('@Cook Time:</b> @','Cook Time:</b> <meta itemprop="cookTime" content="'.$cooktime.'" />',$body);
					break;
				}		
			}
		}
		
		//add watermarks
		$watermark_div = self::getWatermark();
		if ($watermark_div) {
			$body = preg_replace("@(<div class=[\"|']corner bottom_right[\"|']></div>)@im",'$1'.$watermark_div,$body);
		}

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
						$body = str_replace($section, $section . $anchorTag . wikihowAds::getAdUnitPlaceholder('2a') , $body);
						$foundtips = true;
						break;
					} else {
						$foundtips = true;
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . wikihowAds::getAdUnitPlaceholder(2) , $body);
						break;
					}
				}
			}
			if (!$foundtips && !$no_third_ad) { //must be the video section
				//need to put in the empty <p> tag since all the other sections have them for the anchor tags.
				$body .= "<p class='video_spacing'></p>" . wikihowAds::getAdUnitPlaceholder(2);
			}

		}	

		return $body;
	}

	function logTopCat() {
		global $wgTitle, $wgUser;
		$sk = $wgUser->getSkin();
		$cat = $sk->getTopCategory();
		if (!$cat)
			return;
		$dbw = wfGetDB(DB_MASTER);
		$sql = "INSERT LOW_PRIORITY INTO cat_views (cv_user, cv_cat, cv_views) values ({$wgUser->getID()}, "
			. $dbw->addQuotes($cat) . ", 1) ON DUPLICATE KEY UPDATE cv_views=cv_views +1";
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Displays CSS or HTML to remove the sidebar by expanding the article
	 * part of the page.
	 */
	static private function displayRemoveSidebarCSS() {

	}

	/**
	 * Display the block of HTML that makes fixes to CSS and PNG images
	 * which are needed for IE6 or IE7.
	 */
	static private function displayOldIEFixes() {
?>
<!--[if lte IE 8]>
	<? // This deals with the spacing in ads ?>
	<style>
		.wh_ad h4 {padding-right:5px;}
		#article #steps .ad1 h4, #article #steps .ad2 h4, #article #steps .ad3 h4 {padding-right:5px;}
	</style>
<![endif]-->
<!--[if lte IE 7]>
	<? // We need these rules specific for IE 7 and 6 because these browsers
	   // don't understand data:image/ ... style URLs (a front-end
	   // optimization in other browsers), so this CSS rule (which must be
	   // placed afterwards) redefines these attributes.  All these original
	   // CSS rules are defined in new.css. ?>
	<style>
		.edit_pencil { background-image: url(<?= wfGetPad('/skins/WikiHow/images/pencil.gif') ?>); }
		#article UL { list-style: outside url(<?= wfGetPad('/skins/WikiHow/images/bullet_wh.gif') ?>) none; }
		#sidebar UL { list-style: url(<?= wfGetPad('/skins/WikiHow/images/bullet_tan.gif') ?>); }
		.rcw-help-icon { background-image: url(<?= wfGetPad('/skins/WikiHow/images/icon_help_tan.jpg') ?>); }
		.corner { background-image: url(<?= wfGetPad('/skins/WikiHow/images/corners.png') ?>); }
	</style>
<![endif]-->
<!--[if lte IE 6]>
	<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/skins/WikiHow/ie6.css&' . WH_SITEREV) ?>"; /*]]>*/</style>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/skins/WikiHow/DD_belatedPNG.js') ?>"></script>
	<script type="text/javascript">
      /* EXAMPLE */

      DD_belatedPNG.fix('#bonus_bubble p');
      DD_belatedPNG.fix('#bonus_bubble p a');
      DD_belatedPNG.fix('#bonus_bubble_left');
      DD_belatedPNG.fix('#bonus_bubble_right');
      DD_belatedPNG.fix('#actions');
      DD_belatedPNG.fix('#article');
      DD_belatedPNG.fix('.sidebox');
      DD_belatedPNG.fix('#top_links');
      DD_belatedPNG.fix('#worldWide');
      DD_belatedPNG.fix('#category');
      DD_belatedPNG.fix('.module_cap');
      DD_belatedPNG.fix('#article h2');
      DD_belatedPNG.fix('#head_bubble');
      DD_belatedPNG.fix('#head_bubble a');
      DD_belatedPNG.fix('#last_question');
      DD_belatedPNG.fix('#actions blockquote');
      DD_belatedPNG.fix('#actions blockquote p');
      DD_belatedPNG.fix('#actions #learn');
      DD_belatedPNG.fix('#actions #write');
      DD_belatedPNG.fix('#actions #collaborate');
      DD_belatedPNG.fix('.actionLink img');
      DD_belatedPNG.fix('#preferences_tabs a');
      DD_belatedPNG.fix('#featuredNav img');
      DD_belatedPNG.fix(' .button');
      DD_belatedPNG.fix('#wikiHow');
      DD_belatedPNG.fix('#bonus_bubble img');
      DD_belatedPNG.fix('.category_Table img');
      DD_belatedPNG.fix('.search_button');
      DD_belatedPNG.fix('.internal img');
      DD_belatedPNG.fix('#actionImg_book');
      DD_belatedPNG.fix('#actionImg_pen');
      DD_belatedPNG.fix('#actionImg_pencils');
      DD_belatedPNG.fix('.article_top');
      DD_belatedPNG.fix('.article_bottom_white');
      DD_belatedPNG.fix('.article_bottom');
      DD_belatedPNG.fix('.sidebar_top');
      DD_belatedPNG.fix('.sidebar_bottom_fold');
      DD_belatedPNG.fix('#RES_ID_fb_login_text');
      DD_belatedPNG.fix('.search_box_loggedout');
      DD_belatedPNG.fix('.step_num');

      /* string argument can be any CSS selector */
      /* .png_bg example is unnecessary */
      /* change it to what suits you! */
    </script>
<![endif]-->
<!--[if IE 9]>
	<style>
		table.diff {
			table-layout: auto;
		}
	</style>
<![endif]-->
<?
	}

        function setCategories() {
            global $wgUser;

            $sk = $wgUser->getSkin();

            $tree = WikiHow::getCurrentParentCategoryTree();
            if ($tree != null) {
                foreach($tree as $key => $path) {
                    $catString = str_replace("Category:", "", $key);
                    $sk->mCategories[$catString] = $catString;

                    $subtree = wikihowAds::flattenCategoryTree($path);
                    for ($i = 0; $i < count($subtree); $i++) {
                        $catString = str_replace("Category:", "", $subtree[$i]);
                        $sk->mCategories[$catString] = $catString;
                    }
                }
            }
        }
        
	

	/**
	 * Template filter callback for WikiHow skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgArticle, $wgUser, $wgLang, $wgTitle, $wgRequest, $wgParser, $wgGoogleSiteVerification;
		global $wgOut, $wgScript, $wgStylePath, $wgLanguageCode, $wgForumLink;
		global $wgContLang, $wgXhtmlDefaultNamespace, $wgContLanguageCode;
		global $wgWikiHowSections, $IP, $wgServer, $wgServerName, $wgIsDomainTest;
		$prefix = ""; 
		
		if (class_exists('NewLayout') && !self::isUserAgentMobile()) {
			$newLayout = new NewLayout();
			if ($newLayout->isNewLayoutPage()) {
				$newLayout->go();
				return;
			}
		}
		
		if (class_exists('MobileWikihow')) {
			$mobileWikihow = new MobileWikihow();
			$result = $mobileWikihow->controller();
			// false means we stop processing template
			if (!$result) return;
		}
		
		$action = $wgRequest->getVal("action", "view");
		if ($wgRequest->getVal("diff", "") != "")
			$action = "diff";

		$isMainPage = false;
		if ($wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() == wfMsg('mainpage')
			&& $action == 'view'
		) {
			$isMainPage = true;
		}
		$sk = $wgUser->getSkin();
		$cp = Title::newFromText("CreatePage", NS_SPECIAL);

		$this->setCategories();
		wikihowAds::getGlobalChannels();

		if ($action == "view" && $wgUser->getID() > 0) {
			$this->logTopCat();
		}
		
		//adding recipe microdata tags?
		self::checkForRecipeMicrodata();
		self::checkForNoVideo();
		
		$isWikiHow = false;
		if ($wgArticle != null && $wgTitle->getNamespace() == NS_MAIN)  {
			$whow = WikiHow::newFromCurrent();
			$isWikiHow = $whow->isWikihow();
		}

		$isPrintable = $wgRequest->getVal("printable", "") == "yes";

		$contentStyle = "content";
		$bodyStyle = "body_style";

		// set the title and what not
		$avatar = '';
		if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {
			$username = $wgTitle->getText();
			$usernameKey = $wgTitle->getDBKey();
			$avatar = ($wgLanguageCode == 'en') ? Avatar::getPicture($usernameKey) : "";

			$pagetitle = $username;
			$this->set("pagetitle", $wgLang->getNsText(NS_USER) . ": $pagetitle - wikiHow");

			if ($wgTitle->getNamespace() == NS_USER_TALK) {
			    $pagetitle = $wgLang->getNsText(NS_USER_TALK) . ": $pagetitle";
			    $this->set("pagetitle", "$pagetitle - wikiHow");
			}
			$h1 = $pagetitle . "&nbsp;" .
				"<a href='/".$wgLang->specialPage('Emailuser')."?target=" .
				urlencode($usernameKey) . "'>" .
				"<img src='".wfGetPad('/skins/common/images/envelope.png')."' border='0' alt='".wfMsg('alt_emailuser')."'></a>";
			$this->set("title", $h1);
		}
		$title = $this->data['pagetitle'];

		if ($isWikiHow && $action == "view")  {
			if ($wgLanguageCode == 'en') {
				if (!$this->titleTest) {
					$this->titleTest = TitleTests::newFromTitle($wgTitle);
				}
				if ($this->titleTest) {
					$title = $this->titleTest->getTitle();
				}
			} else {
				$howto = wfMsg('howto', $this->data['title']);
				$title = wfMsg('pagetitle', $howto);
			}
		}

		if ($wgTitle->getFullText() == wfMsg('mainpage'))
			$title = 'wikiHow - '.wfMsg('main_title');

		if ($wgTitle->getNamespace() == NS_CATEGORY) {
			$title = wfMsg('category_title_tag', $wgTitle->getText());
		}
		$talk_namespace = MWNamespace::getCanonicalName(MWNamespace::getTalk($wgTitle->getNamespace()));

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

		//XX PROFILE EDIT/CREAT/DEL BOX DATE - need to check for pb flag in order to display this.
		$pbDate = "";
		$pbDateFlag = 0;
		$profilebox_name = wfMsg('profilebox-name');
		if ( $wgTitle->getNamespace() == NS_USER ) {
			if ($u = User::newFromName($wgTitle->getDBKey())) {
				$pbDate = ProfileBox::getPageTop($u);
				$pbDateFlag = true;
			}
		}

		if (! $sk->suppressH1Tag()) {
			if ($isWikiHow && $action == "view") {
				if (WikiHowTemplate::$showRecipeTags) {
					if (WikiHowTemplate::$showhRecipeTags) {
						$recipe_tag = " fn'";
					}
					else {
						$recipe_tag = "' itemprop='name'";
					}
				}
				else {
					$recipe_tag = "'";
				}
				
				$heading = "<h1 class='firstHeading".$recipe_tag."><a href=\"" . $wgTitle->getFullURL() . "\">" . wfMsg('howto', $this->data['title']) . "</a></h1>";
				#$heading = "<h1 class=\"firstHeading\"><a href=\"" . $wgTitle->getFullURL() . "\">" . wfMsg('howto', $wgTitle->getText()) . "</a></h1>";

			} else {

				if (($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) && ($avatar != "") ) {
					$heading = $avatar . "<div id='avatarNameWrap'><h1 class=\"firstHeading\" >" . $this->data['title'] . "</h1>  ".$pbDate."</div><div style='clear: both;'> </div>";
				} else {
					$heading = "<h1 class='firstHeading'>" . $this->data['title'] . "</h1>";
				}
			}
		}

		//XX AVATAR PROFILEBOX
		$profileBox = "";
		if ($wgTitle->getNamespace() == NS_USER &&
			$wgRequest->getVal('action') != 'edit' &&
			$wgRequest->getVal('action') != 'protect' &&
			$wgRequest->getVal('action') != 'history' &&
			$wgRequest->getVal('action') != 'delete')
		{
			$name = $wgTitle->getDBKey();
			if ($u = User::newFromName($wgTitle->getDBKey())) {
				if ($u->getOption('profilebox_display') == 1 && $wgLanguageCode == 'en') {
					//$profileBox = "<div class='article_inner'>" . ProfileBox::displayBox($u) . "</div>";
					$profileBox = ProfileBox::displayBox($u) ;
				}
				$fbLinked = "";
				if (class_exists('FBLogin') && class_exists('FBLink') && $u->getID() == $wgUser->getId()) {
					$fbLinked = !$wgUser->isFacebookUser() ? FBLink::showCTAHtml() : FBLink::showCTAHtml('FBLink_linked');
				}
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
				|| $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch") {
			$show_related = false;
		}  else {
			$show_related = false;
			$articleStyle = "article2";
			$bodyContentStyle = "bodyContent2";
		}

		$ads = "";
		$mainPageRightPanel = "";
		$spell = "";
		$rad_links = "";
		$bottom_ads = "";
		$top_ads = "";

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
					$ads = $sk->getGoogleAds($use_chikita_sky);
				} else {
					$cats = WikiHow::getCurrentParentCategories();
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

		$talk_post_form = "";
		if (MWNamespace::isTalk($wgTitle->getNamespace()) && $action == "view")

	   	if ($isPrintable) {
	   	    // override all of these values for printable versions
		  $contentStyle = "content_printable";
		  $bodyStyle = "body_style_printable";
		  $toolbox = "";
		  $subTabMenu = "";
		  $box = "";
		}

		$return_to_article = "";

		if (MWNamespace::isTalk($wgTitle->getNamespace()) && $action == "view") {
			$subject = MWNamespace::getCanonicalName(MWNamespace::getSubject($wgTitle->getNamespace()));
			if ($subject != "") {
				$subject .= ":";
			}
			if ($wgTitle->getNamespace() == NS_USER_TALK) {
				//$link = "<a href=\"/$subject" . $wgTitle->getDBKey() . "\">".wfMsg('authorpage', $sk->getUsernameFromTitle())."</a>";
				//$return_to_article.= "<br/>".wfMsg('returnto',$link);
				$return_to_article = "<br/>" . $sk->makeLinkObj($wgTitle->getSubjectPage(), wfMsg('returnto', wfMsg('authorpage', $wgTitle->getText())));
			} else {
				$return_to_article = "<br/>" . $sk->makeLinkObj($wgTitle->getSubjectPage(), wfMsg('returnto', $wgTitle->getText()));
			}
		}


		$search = "";
		if (isset($_GET['search']) &&
			is_string($_GET['search']) &&
			($wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":Search" || $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch" || $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":GoogSearch")) {
			$search = htmlspecialchars($_GET['search']);
		} else {
		    $search = wfMsg('type_here');
		}

		// QWER links for everyone on all pages
		$cp = Title::makeTitle(NS_PROJECT, "Community-Portal");
		$cptab = Title::makeTitle(NS_PROJECT, "Community");

		$helplink = $sk->makeLinkObj (Title::makeTitle(NS_CATEGORY, wfMsg('help')) ,  wfMsg('help'));
		$logoutlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Userlogout'), wfMsg('logout'));
		$forumlink = '';
		if ($wgForumLink !='')
			$forumlink = "<a href='$wgForumLink'>" . wfMsg('forums') . "</a>";
		$tourlink = "";
		if ($wgLanguageCode =='en')
			$tourlink = $sk->makeLinkObj(Title::makeTitle(NS_PROJECT, "Tour"), wfMsg('wikihow_tour')) ;
		$splink = "";

		if($wgUser->getID() != 0)
			$splink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Specialpages"), wfMsg('specialpages')) . "</li>";

		$rclink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Recentchanges"), wfMsg('recentchanges'));
		$requestlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RequestTopic"), wfMsg('requesttopic'));
		$listrequestlink = $sk->makeLinkObj( Title::makeTitle(NS_SPECIAL, "ListRequestedTopics"), wfMsg('listrequtestedtopics'));
		$rsslink = "<a href='" . $wgServer . "/feed.rss'>" . wfMsg('rss') . "</a>";
		$rplink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Randompage"), wfMsg('randompage') ) ;


		//For logged out only
		if($wgUser->getID() == 0){
			$loginlink =  "<li>" . wfMsg('Anon_login', $q) . "</li>";
			$cplink = "<li>" . $sk->makeLinkObj ($cptab, wfMsg('communityportal') ) . "</li>";
		}
		else{
			$rcpatrollink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RCPatrol"), wfMsg('RCPatrol')) . "</li>";
			if (class_exists('IntroImageAdder')) {
			$imagepicklink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_PROJECT, "IntroImageAdderStartPage"), wfMsg('IntroImageAdder')) . "</li>";
			}
			if ($wgLanguageCode == 'en') {
				$moreideaslink = "<li><a href='/Special:CommunityDashboard'>" . wfMsg('more-ideas') . "</a></li>";
				$categorypickerlink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Categorizer"), wfMsg('UncategorizedPages')) . "</li>";
			} else {
				$moreideaslink = "<li><a href='/Contribute-to-wikiHow'>" . wfMsg('more-ideas') . "</a></li>";
				$categorypickerlink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Uncategorizedpages"), wfMsg('UncategorizedPages')) . "</li>";
			}
		}

		// For articles only
		$wlhlink = "";
		$statslink = "";
		if ($wgTitle->getNamespace() != NS_SPECIAL && $wgTitle->getFullText() != wfMsg('mainpage') && $wgTitle->getNamespace() != NS_IMAGE)
 			$wlhlink = "<li> <a href='" . Title::makeTitle(NS_SPECIAL, "Whatlinkshere")->getLocalUrl() . "/" . $wgTitle->getPrefixedURL() . "'>" . wfMsg('whatlinkshere') . "</a></li>";

		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage') && $wgLanguageCode == 'en')
			$statslink = "<li> " . $sk->makeLinkObj(SpecialPage::getTitleFor("Articlestats", $wgTitle->getText()), wfMsg('articlestats')) . "</li>";
		$mralink = "";
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage')
		 && $wgTitle->userCanEdit() && $wgLanguageCode == 'en')
			$mralink = "<li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RelatedArticle"), wfMsg('manage_related_articles'), "target=" . $wgTitle->getPrefixedURL()) . "</li>";

		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage') && $wgTitle->userCanEdit())

			$links[] = array (Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL(), wfMsg('recentchangeslinked') );
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() != wfMsg('mainpage')
		 && $wgTitle->userCanEdit() && $wgLanguageCode == 'en') {
				$editlink = "<li>" . " <a href='" . $wgTitle->escapeLocalURL($sk->editUrlOptions()) . "'>" . wfMsg('edit-this-article') . "</a>" . "</li>";
		 }


		//user stats
		$userstats = "";
        if ($wgTitle->getNamespace() == NS_USER) {
            $userstats .= "<p id='userstats'>";
            $real_name = $sk->getUsernameFromTitle();
            $username = $wgTitle->getText();
            $username = ereg_replace("/.*", "", $username);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );
			$u = User::newFromName($username);
			if ($u && $u->getID() != 0)
            	$userstats .= wfMsg('contributions-made', $real_name, number_format(User::getAuthorStats($username), 0, "", ","), $contribsPage->getFullURL());
			else
            	$userstats .= wfMsg('contributions-link', $real_name, number_format(User::getAuthorStats($username), 0, "", ","), $contribsPage->getFullURL());
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
	        $videolink = "<li id='gatVideoImport' > " . $sk->makeLinkObj( SpecialPage::getTitleFor( 'Importvideo', $wgTitle->getText() ), wfMsg('importvideo')) . "</li>";
       }
		$freephotoslink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "ImportFreeImages"), wfMsg('imageimport'));
		$relatedchangeslink = "";
		if ($wgArticle != null)
			$relatedchangeslink = "<li> <a href='" .
				Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL() . "'>"
				. wfMsg('recentchangeslinked') . "</a></li>";


		//search
		$searchTitle = Title::makeTitle(NS_SPECIAL, "LSearch");
		// authors
		$authors = $sk->getAuthorFooter();

		$createLink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "CreatePage"), wfMsg('Write-an-article'));

		//anncouncements

		$announcement = "";
		$sitenotice = "";
		if ($wgUser->getID() != 0 && wfMsg('sitenotice_loggedin') != '-') {
			$this->data['sitenotice'] = wfMsgExt('sitenotice_loggedin', 'parse');
		}
		if($this->data['sitenotice']) {
			$bubbleicon = "<img src=\"".wfGetPad('/skins/WikiHow/images/message_icon.png')."\" style='margin-top:9px;margin-right:5px;' width='17' height='14'/>";
			$sitenotice = preg_replace('/<p>/', "<p>",  $this->data['sitenotice']);
		}

        if( $wgUser->getNewtalk() ) {
            $usertalktitle = $wgUser->getTalkPage();
           	if($usertalktitle->getPrefixedDbKey() != $this->thispage){
				$trail = "";
				if ($wgUser->getOption('scrolltalk'))
					$trail = "#post";
                $announcement = wfMsg( 'newmessages2', $wgUser->getName(), $usertalktitle . $trail );
            }
        }

        if( $wgUser->getNewkudos() && !$wgUser->getOption('ignorefanmail')) {
            $userkudostitle = $wgUser->getKudosPage();
            if($userkudostitle->getPrefixedDbKey() != $this->thispage) {
                if ($announcement != '') {
			$announcement .= " and ";
			$announcement .= wfMsg( 'newfanmail2', $wgUser->getName(), $userkudostitle->getPrefixedURL());                # Disable Cache
		} else {
			$announcement = wfMsg( 'newfanmail2', $wgUser->getName(), $userkudostitle->getPrefixedURL());
		}
            }
        }

		if ($announcement != "") {
			$announcement = "<span id='gatNewMessage'><div class='message_box'>$announcement</div></span>";
		}

		$showThumbsUp = class_exists('ThumbsNotifications');

		if ($showThumbsUp && $wgUser->getNewThumbsUp()) {
			$thumbsNotifications = ThumbsNotifications::getNotificationsHTML();
			if (strlen($thumbsNotifications)) {
				$announcement .= $thumbsNotifications;
			}
		}

		$userlinks = $sk->getUserLinks();

		$rtl = $wgContLang->isRTL() ? " dir='RTL'" : '';
		$head_element = "<html xmlns:fb=\"https://www.facebook.com/2008/fbml\" xmlns=\"{$wgXhtmlDefaultNamespace}\" xml:lang=\"$wgContLanguageCode\" lang=\"$wgContLanguageCode\" $rtl>\n";

		$rtl_css = "";
		if ($wgContLang->isRTL()) {
			$rtl_css = "<style type=\"text/css\" media=\"all\">/*<![CDATA[*/ @import \"".wfGetPad("{$this->data['stylepath']}/{$this->data['stylename']}/rtl.css")."\"; /*]]>*/</style>";
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
			$page_isfeatured = $dbr->selectField('page', 'page_is_featured', array("page_id={$wgTitle->getArticleID()}"), __METHOD__);
			$featured = ($page_isfeatured == 1);
		}

		$show_ad_section = false;
		
		$cpimageslink = "";
		global $wgSpecialPages;
		if ($wgSpecialPages['Copyimages'] == 'Copyimages' && $wgLanguageCode != 'en' && $wgTitle->getNamespace() == NS_MAIN) {
			$cpimages = SpecialPage::getTitleFor( 'Copyimages', $wgTitle->getText() );
			$cpimageslink = "<li> " . $sk->makeLinkObj($cpimages, wfMsg('copyimages')) . "</li>";
		}
		$top_search = "";
		$footer_search = "";
		//INTL: Search options for the english site are a bit more complex
		if ($wgLanguageCode == 'en') {
			if ($wgUser->getID() == 0) {
            	$top_search = GoogSearch::getSearchBox("cse-search-box");
            	$footer_search = GoogSearch::getSearchBox("cse-search-box-footer");
            	$footer_search = $footer_search . "<br />";
				/*
				$top_search  = '
				<form id="bubble_search" name="search_site" action="/wikiHowTo" method="get">
				<input type="text" class="search_box" name="search"/>
				<input type="hidden" name="lo" value="1"/>
				<input type="submit" value="Search" id="search_site_bubble" class="search_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" />
				</form>';
				*/
				$footer_search = str_replace("bubble_search", "footer_search", $top_search);
				$footer_search = str_replace("search_site_bubble", "search_site_footer", $top_search);
			} else {
				$top_search  = '
				<form id="bubble_search" name="search_site" action="' . $searchTitle->getFullURL() . '" method="get">
				<input type="text" class="search_box" name="search"/>
				<input type="submit" value="Search" id="search_site_bubble" class="search_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" />
				</form>';
				$footer_search = str_replace("bubble_search", "footer_search", $top_search);
			}
       	}
	else {
		//INTL: International search just uses Google custom search
		$top_search = GoogSearch::getSearchBox("cse-search-box");
	       	$footer_search = GoogSearch::getSearchBox("cse-search-box-footer");
        	$footer_search = $footer_search . "<br />";
	}

	$text = $this->data['bodytext'];
	if($wgUser->getID() == 0)

		//Remove stray table under video section. Probably should eventually do it at the source, but then have to go through all articles.
		$text = $this->data['bodytext'];
		if (strpos($text, '<a name="Video">') !== false) {
			$vidpattern="<p><br /></p>\n<center>\n<table width=\"375px\">\n<tr>\n<td><br /></td>\n<td align=\"left\"></td>\n</tr>\n</table>\n</center>\n<p><br /></p>";
			$text = str_replace($vidpattern, "", $text);
		}
		$this->data['bodytext'] = $text;


		// show suggested titles
		$suggested_titles = "";
        if ($wgLanguageCode == 'en'
            && $wgTitle->getNamespace() == NS_MAIN
			&& !$isMainPage
			&& $action == "view"
			&& $wgTitle->getArticleID() > 0
		) {
			$suggested_titles = "<div id='st_wrap'><div class='article_inner'>" . wfGetSuggestedTitles($wgTitle) . "</div></div>";
            if ($wgUser->getID() == 0) {
                //$suggested_titles = WikiHowTemplate::getAdUnitPlaceholder(3, true) . $suggested_titles;
			}
		}

		// hack to get the FA template working, remove after we go live
		$fa = '';
		if (strpos($this->data['bodytext'], 'featurestar') !== false) {
			$fa = '<p id="feature_star">' . wfMsg('featured_article') . '</p>';
			$this->data['bodytext'] = preg_replace("@<div id=\"featurestar\">(.|\n)*<div style=\"clear:both\"></div>@mU", '', $this->data['bodytext']);
		}
				
		// munge the steps HTML to get the numbers working
		if ($wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() != wfMsg('mainpage')
			&& ($action=='view' || $action == 'purge')
		) {
			// on view. for preview, you have to munge the steps of the previewHTML manually
			$body = $this->data['bodytext'];
			$this->data['bodytext'] = self::mungeSteps($body);
		} else if ($wgUser->getID() == 0 && MWNamespace::isTalk($wgTitle->getNamespace()) && ($action=='view' || $action == 'purge')) {
			// insert ads into talk page
			$body = $this->data['bodytext'];
			$tag = '<div id="discussion_entry">|<div class="de">';
			$ads = wikihowAds::getAdUnitPlaceholder(5);
			// break it apart because preg_replace fails us
			$msgs = preg_split('@(<div id="discussion_entry">|<div class="de">)@m', $body, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$first = false;
			$foundit = false;
			$newbody = "";
			while (sizeof($msgs) > 0) {
				$x = array_shift($msgs);
				if (in_array($x, array('div id="discussion_entry">', '<div class="de">'))) {
					if (!$first) {
						$first = true;
					} else {
						$newbody .=  $ads . $x;
						$foundit = true;
						break;
					}
				}
				$newbody .= $x;
			}
			// remaining ones
			$newbody .= implode("", $msgs);
			$body = $newbody;

			$this->data['bodytext'] = $body;
			if (!$foundit) {
				$this->data['bodytext'] = $body . $ads;
		}
		}
		if(MWNamespace::isTalk($wgTitle->getNamespace())){
			$this->data['bodytext'] = Avatar::insertAvatarIntoDiscussion($this->data['bodytext']);
		}

		if (class_exists('Html5editor')) {
			// If the article is not found, and the ?create-new-article=true
			// param is present, we might load the HTML5 editor
			$articleExists = $wgTitle->getArticleID() > 0;
			if (!$articleExists
				&& $wgRequest->getVal('create-new-article', '') == 'true'
				&& isHtml5Editable())
			{
				$this->data['bodytext'] = Html5DefaultContent();
			}
		}

		$navigation = "
	<div class='sidebox_shell'>
		<div class='sidebar_top'></div>
        <div class='sidebox' id='side_nav'>
            	<h3 id='navigation_list_title' >
			<a href=\"#\" onclick=\"return sidenav_toggle('navigation_list',this);\" id='href_navigation_list'>" . wfMsg('navlist_collapse') . "</a>
			<span onclick=\"return sidenav_toggle('navigation_list',this);\" style=\"cursor:pointer;\"> " . wfMsg('navigation') . "</span></h3>
            <ul id='navigation_list' style='margin-top: 0;'>
				<li> {$createLink}</li>
				{$editlink}";

				if ($wgLanguageCode == 'en') {
					$navigation .= "<li> {$requestlink}</li><li> {$listrequestlink}</li>";
				}

				$navigation .=
					"{$imagepicklink}
				{$rcpatrollink}
				{$categorypickerlink}
				{$moreideaslink}
				{$loginlink}
            </ul>

			<h3>
			<a href=\"#\" onclick=\"return sidenav_toggle('visit_list',this);\" id='href_visit_list'>" . wfMsg('navlist_expand') . "</a>
			<span onclick=\"return sidenav_toggle('visit_list',this);\" style=\"cursor:pointer;\"> " . wfMsg('places_to_visit') . "</span></h3>
				<ul id='visit_list' style='display:none;'>
					<li> {$rclink}</li>
					<li> {$forumlink}</li>
					{$cplink}
					{$splink}
				</ul>";

		if ($wgTitle->getNamespace() == NS_MAIN && $wgUser->getID() > 0
          && $wgTitle->userCanEdit() && $wgTitle->getText() != wfMsg('mainpage'))  {
			$navigation .= "<h3>
			<a href=\"#\" onclick=\"return sidenav_toggle('editing_list',this);\" id='href_editing_list'>" . wfMsg('navlist_expand') . "</a>
			<span onclick=\"return sidenav_toggle('editing_list',this);\" style=\"cursor:pointer;\"> " . wfMsg('editing_tools') . "</span></h3>
				<ul id='editing_list' style='display:none;'>
					{$videolink}
					{$mralink}
					{$statslink}
					{$wlhlink}
				</ul>";
		}

		if($wgUser->getID() > 0 && ($wgTitle->getNamespace() == NS_IMAGE || $wgTitle->getNamespace() == NS_TEMPLATE || $wgTitle->getNamespace() == NS_TALK || $wgTitle->getNamespace() == NS_PROJECT)){
			$navigation .= "<h3>
			<a href=\"#\" onclick=\"return sidenav_toggle('editing_list',this);\" id='href_editing_list'>" . wfMsg('navlist_expand') . "</a>
			<span onclick=\"return sidenav_toggle('editing_list',this);\" style=\"cursor:pointer;\"> " . wfMsg('editing_tools') . "</span></h3>
				<ul id='editing_list' style='display:none;'>
					{$wlhlink}
				</ul>";
		}


		if($wgUser->getID() > 0){

            $navigation .= "<h3><a href=\"#\" onclick=\"return sidenav_toggle('my_pages_list',this);\" id='href_my_pages_list'>" . wfMsg('navlist_expand') . "</a>
		<span onclick=\"return sidenav_toggle('my_pages_list',this);\" style=\"cursor:pointer;\"> " . wfMsg('my_pages') . "</span></h3>
            <ul id='my_pages_list' style='display:none;'>
            <li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Mytalk'), wfMsg('mytalkpage') ). "</li>
            <li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Mypage'), wfMsg('myauthorpage') ). "</li>
			<li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Watchlist'), wfMsg('watchlist') ). "</li>
            <li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Drafts'), wfMsg('mydrafts') ). "</li>
            <li> " . $sk->makeLinkObj(SpecialPage::getTitleFor('Mypages', 'Contributions'),  wfMsg ('mycontris')) . "</li>
            <li> " . $sk->makeLinkObj(SpecialPage::getTitleFor('Mypages', 'Fanmail'),  wfMsg ('myfanmail')) . "</li>
            <li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Preferences'), wfMsg('mypreferences') ). "</li>
            <li> " . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Userlogout'), wfMsg('logout') ) . "</li>
            </ul>";
		}

		$navigation .= "   {$userlinks}";

        $navigation .= "</div>
		<div class='sidebar_bottom_fold'></div>
	</div>
	";


/*
            <a id="nav_home" href="<?=$mainPageObj->getFullURL();?>" id="nav_home" title="Home" onmousedown="button_click(this)" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">Home</a>            <a id="nav_articles" href="" title="Articles" class="on" onmousedown="button_click(this)" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">Articles</a>            <a id="nav_community" href="<?=$cp->getFullURL();?>" id="nav_community" title="Community" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" onmousedown="button_click(this)"><?=wfMsg('community');?></a>            <?if ($wgUser->getID() >0) { ?>            <a id="nav_profile" href="<?=$wgUser->getUserPage()->getFullURL(); ?>" id="nav_profile" title="My Profile" onmousedown="button_click(this)" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">My Profile</a>            <? } else{ ?>            <a id="nav_profile" href="/Special:Userlogin" id="nav_profile" title="My Profile" onmousedown="button_click(this)" onmouseover="button_swap(this);" onmouseout="button_unswap(this);">My Profile</a>            <? } ?>
*/
	$lpage = Title::makeTitle(NS_SPECIAL, "Userlogin");
	$dpage = $wgLanguageCode == 'en' ? Title::makeTitle(NS_SPECIAL, "CommunityDashboard") : Title::makeTitle(NS_PROJECT, wfMsg("community"));
	$nav_tabs = array(
				'nav_home'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array($mainPageObj->getLocalURL()), 'link' => $mainPageObj->getLocalURL(), 'text' => wfMsg('navbar_home')),
				'nav_articles'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array("/Special:Categorylisting"), 'link' => "/Special:Categorylisting", 'text' => wfMsg('navbar_articles')),
				'nav_community'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array($cptab->getLocalURL(), $dpage->getLocalURL()), 'link'=> $dpage->getLocalURL(), 'text'=> wfMsg('navbar_community')),
				'nav_profile'	=> array('status' => '', 'mouseevents' => '',
						'possibleurls' => array($wgUser->getID() > 0 ? $wgUser->getUserPage()->getLocalURL() : $lpage->getLocalURL()),
						'link'=> $wgUser->getID() > 0 ? $wgUser->getUserPage()->getLocalURL() : $lpage->getLocalURL(),
						'text' => wfMsg('navbar_profile'))
			);
	$articles_page = true;
	foreach ($nav_tabs as $n=>$v) {
		if (in_array($wgTitle->getLocalURL(), $v['possibleurls']) ){
			$v['status'] = "on";
			$articles_page = false;
		} else {
			$v['mouseevents'] = 'onmouseover="button_swap(this);" onmouseout="button_unswap(this);"';
		}
		$nav_tabs[$n]= $v;
	}
	//default
	if ($articles_page) {
		$nav_tabs['nav_articles']['status'] = 'on';
		$nav_tabs['nav_articles']['mouseevents'] = '';
	}

	//XX TALK/DISCUSSION SUBMENU
//XXVU ADD REMOVE ADD... sheesh.  REMOVE THIS BEFORE LAUNCH
/*
	$subtalklinks = "";
	if ($wgTitle->isTalkPage()) {
		$subeditselected = '';
		$subhistoryselected = '';
		$subtalkselected = '';
		if ($action == 'edit') {
			$subeditselected = "class='selected'";
		} else if ($action == 'history') {
			$subhistoryselected = "class='selected'";
		} else {
			$subtalkselected = "class='selected'";
		}

		$subtalklinks = "\n<table id='discuss_icons'><tr>
			<td><a href='".$wgTitle->getFullURL()."' {$subtalkselected} >Discuss</a></td>
			<td><a href='".$wgTitle->escapeLocalURL($sk->editUrlOptions())."' id='discuss_edit' {$subeditselected} >Edit</a></td>
			<td><a href='".$wgTitle->getLocalURL( 'action=history' )."' id='discuss_history' {$subhistoryselected} >History</a></td>
			</tr></table>\n";
	}

*/
	// add article_inner if it's not already there, CSS needs it
	if (strpos( $this->data['bodytext'], "article_inner" ) === false
		&& wfRunHooks('WrapBodyWithArticleInner', array()))
	{
		$this->data['bodytext'] = "<div class='article_inner'>{$this->data['bodytext']}</div>";
	}

	// set up the main page
	$mpActions = "";
	$mpWorldwide = "";
	$mpFAs = "";
	$mpCategories = "";
	if ($isMainPage) {

		$options = new ParserOptions();
		$output = $wgParser->parse( "{{NUMBEROFARTICLES}}", $wgTitle, $options );
		$numberofarticles = $output->getText();
		$mpActions = wfMsg('main_page_actions', strip_tags($numberofarticles), wfGetPad(), $fb);

		$mpActions = preg_replace('/\<[\/]?pre\>/', '', $mpActions);
		$mpWorldwide = wfMsg('main_page_worldwide', wfGetPad());
		$mpWorldwide = preg_replace('/\<[\/]?pre\>/', '', $mpWorldwide);
		// FAs only for De, En, Es
		$mpFAs = "";
		$mpFAs = $sk->getFeaturedArticlesBoxWide(15, 10);
		$mpCategories = wfMsg('main_page_categories', wfGetPad());
		$mpCategories = preg_replace('/\<[\/]?pre\>/', '', $mpCategories);

		$this->data['bodytext'] = ""; // ignore whatever is in there
	}

	// determine whether or not to show RCWidget
	$profilebox_condition = false;
	if ($wgUser->getID() > 0) {
		$name = $wgTitle->getDBKey();
		$pbu = User::newFromName($name);
		if (isset($pbu)) {
			if (($wgTitle->getNamespace() == NS_USER) &&
				($wgUser->getID() == $pbu->getID()) ) {
				$profilebox_condition = true;
			}
		}
	}

	$isLoggedIn = $wgUser->getID() > 0;

 	$showSpotlightRotate =
		$wgTitle->getPrefixedURL() == wfMsg('mainpage') &&
		$wgLanguageCode == 'en';

	$showBreadCrumbs = self::showBreadCrumbs();
	$showSideBar = self::showSideBar();

    $showRCWidget =
		class_exists('RCWidget') &&
		!$profilebox_condition &&
		($wgUser->getID() == 0 || $wgUser->getOption('recent_changes_widget_show') != '0' ) &&
		$wgTitle->getPrefixedText() != 'Special:Avatar' &&
		$wgTitle->getPrefixedText() != 'Special:ProfileBox' &&
		$wgTitle->getPrefixedText() != 'Special:IntroImageAdder' &&
		$action != 'edit';

	$showFollowWidget = class_exists('FollowWidget');

	$showSliderWidget =
		class_exists('Slider') &&
		$wgTitle->exists() &&
		$wgTitle->getNamespace() == NS_MAIN &&
		$wgTitle->getFullText() != wfMsg('mainpage') &&
		$wgRequest->getVal('oldid') == '' &&
		$wgRequest->getVal('create-new-article') == '' &&
		($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view');
				
/*		
$slideshow_array = array('Recover-from-a-Strained-or-Pulled-Muscle'
,'Drive-Manual'
,'Recycle-Plastic-Bottles'
,'Make-a-Tuna-Sandwich'
,'Begin-Running'
,'Do-Sit-Ups'
,'Create-a-Hotmail-Account'
,'Peel-a-Difficult-Hard-Boiled-Egg'
,'Write-a-Harry-Potter-Acceptance-Letter'
,'Make-Animation-or-Movies-with-Microsoft-PowerPoint'
,'Erase-a-Pimple-Scar'
,'Clean-up-Oil-Spills-in-a-Garage'
,'Get-Wider-Shoulders'
,'Develop-Psychic-Abilities'
,'Include-References-on-a-Resume'
,'Crack-Your-Neck'
,'Download-Fonts-for-Windows'
,'Write-a-Birthday-Invitation'
,'Play-Mp4-Videos-on-a-PC'
,'Convert-Within-Metric-Measurements'
,'Create-a-Personalized-Signature'
,'Create-a-Cool-Club-Name'
,'Play-Hearts'
,'Write-an-Agenda-for-a-Meeting'
,'Convert-PDF-to-Image-Files'
,'Write-a-Postcard'
,'Make--Bubble-Solution'
,'Make-Caramel'
,'Type-Emoticons'
,'Create-an-Orkut-Account'
,'Pry-off-a-Watch-Backing-Without-Proper-Tools'
,'Clean-Battery-Leaks/Spills'
,'Clean-Mold-from-Leather'
,'Transfer-Data-Between-a-Cell-Phone-and-Computer'
,'Meditate-for-Beginners'
,'Clean-a-Fish-Tank'
,'Make-Your-Own-Whipped-Cream'
,'Edit-a-Mp3-File'
,'Move-Heavy-Furniture'
,'Address-an-Envelope-in-Care-of-Someone-Else'
,'Tell-if-Gold-Is-Real'
,'Calculate-Your-Target-Heart-Rate'
,'Make-a-Custom-Music-Mix-(for-Cheer-or-Dance)'
,'Make-Dried-Fruit'
,'Do-String-Figures'
,'Eliminate-Body-Odor'
,'Make-Your-Computer-Say-Everything-You-Type'
,'Text-Your-Crush-and-Start-a-Conversation'
,'Find-Your-Lucky-Numbers-in-Numerology'
,'Figure-Out-Your-Yearly-Salary');
		
	$showSlideShow =
		class_exists('GallerySlide') &&
		$wgTitle->getNamespace() == NS_MAIN &&
		in_array($wgTitle->getDBkey(),$slideshow_array) &&
		$wgTitle->getFullText() != wfMsg('mainpage') &&
		$wgRequest->getVal('oldid') == '' &&
		($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view');*/
	$showSlideShow = false;

	/*
	$showFBBar =
		$wgTitle->getNamespace() == NS_MAIN &&
		$wgTitle->exists() &&
		$wgTitle->getFullText() != wfMsg('mainpage') &&
		$wgRequest->getVal('oldid') == '' &&
		($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view') &&
		!in_array($wgTitle->getDBkey(), self::$fbIgnore);
	*/

	$showFBBar = false;	
	
// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?= $head_element ?><head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
	<title><?= $title ?></title>
 	<?if ($wgIsDomainTest) {?>
 	<base href="http://www.wikihow.com/" />
 	<?}?>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="verify-v1" content="/Ur0RE4/QGQIq9F46KZyKIyL0ZnS96N5x1DwQJa7bR8=" />
    <meta name="google-site-verification" content="Jb3uMWyKPQ3B9lzp5hZvJjITDKG8xI8mnEpWifGXUb0" />
	<meta name="msvalidate.01" content="CFD80128CAD3E726220D4C2420D539BE" />
	<meta name="y_key" content="1b3ab4fc6fba3ab3" />
<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>
	<? // add CSS files to extensions/min/groupsConfig.php ?>
	<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/?g=whcss' . ($isLoggedIn ? ',li' : '') . ($showSliderWidget ? ',slc' : '') . ($showSlideShow ? ',ppc' : '') . '&') . WH_SITEREV ?>"; /*]]>*/</style>
	<? if ($isPrintable): ?>
		<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/skins/WikiHow/printable.css') . '?2' ?>";  /*]]>*/</style>
	<? endif; ?>
	<? // add present JS files to extensions/min/groupsConfig.php ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/?g=whjs' . ($showRCWidget ? ',rcw' : '') . ($showSpotlightRotate ? ',sp' : '') . ($showFollowWidget ? ',fl' : '') . ($showSliderWidget ? ',slj' : '') . ($showSlideShow ? ',ppj' : '') . (!$isLoggedIn ? ',ads' : '') . ($showThumbsUp ? ',thm' : '') . '&') . WH_SITEREV ?>"></script>
	<? if ($wgLanguageCode == 'en'): ?>
	<script>
		if (WH.ExitTimer) {
			WH.ExitTimer.start(false);
		}
	</script>
	<? endif; ?>

	<? $this->html('headlinks') ?>
	<? self::displayOldIEFixes() ?>
	<? if (!$showSideBar) self::displayRemoveSidebarCSS(); ?>
	<? if (!$wgIsDomainTest) { ?>
	<link rel='canonical' href='<?=$wgTitle->getFullURL()?>'/>
	<link href="https://plus.google.com/102818024478962731382" rel="publisher" />
	<? } ?>
	<? if (self::isUserAgentMobile()): ?>
		<link media="only screen and (max-device-width: 480px)" href="<?= wfGetPad('/extensions/min/f/skins/WikiHow/iphone.css') ?>" type="text/css" rel="stylesheet" />
	<? else: ?>
		<!-- not mobile -->
	<? endif; ?>
	<!--<![endif]-->
	<?= $rtl_css ?>
	<link rel="alternate" type="application/rss+xml" title="wikiHow: How-to of the Day" href="http://www.wikihow.com/feed.rss"/>
	<link rel="apple-touch-icon" href="<?= wfGetPad('/skins/WikiHow/safari-large-icon.png') ?>" />
	<? if (class_exists('Html5editor') && isHtml5Editable()): ?>
		<?= Html5EditButtonBootstrap() ?>
	<? endif; ?>
	<?=wfMsg('Test_setup')?>
	<?
	if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
	       echo CTALinks::getGoogleControlScript();
	}
	?>
	<?= $wgOut->getHeadItems() ?>
</head>
<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload']) { ?>onload="<?php $this->text('body_onload') ?>"<?php } ?>
>
<div id="header">
    <div id="logo">
		<a href='<?=$mainPageObj->getLocalURL();?>'>
		<img src="<?= wfGetPad('/skins/WikiHow/images/wikihow.png') ?>" id="wikiHow" alt="<?='wikiHow - '.wfMsg('main_title');?>" width="216" height="37"/></a><p><a href='<?=$mainPageObj->getFullURL();?>'><?=wfMsg('main_logo_title')?></a></p>
 <?php if ($wgLanguageCode != 'en' && $wgTitle->getArticleID() > 0 && $action == 'view' ) {
               echo "<img src='/imagecounter.gif?id=" . $wgTitle->getArticleID() . "' width='1' height='1' border='0'/>";
 } ?>
 	</div><!--end logo-->

	<div id="bubbles">
		<div id="login"><?=$login?> <?php if($wgUser->getID() > 0) { echo "| " . $helplink . " | $logoutlink"; }?> </div>
		<? if ($sitenotice != "") { ?>
    	<div id="bonus_bubble">
        	<div id="bonus_bubble_left"></div>
			<?=$sitenotice;?>
            <div id="bonus_bubble_right"></div>
		</div><!--end bonus_bubble-->
		<? } ?>

		<div id="head_bubble">
			<? foreach ($nav_tabs as $n=>$v) {
				echo "<a id='{$n}' href='{$v['link']}' title='{$v['text']}' {$v['mouseevents']} class='{$v['status']}'>{$v['text']}</a>";
			}
			?>
			<?= $top_search ?>
		</div><!--end head_bubble-->
	</div><!--end bubbles-->
</div><!--end header-->

<div id="main">
	<?= $announcement ?>
	<?= $mpActions ?>
	<?php
		if(!$showSideBar)
			$sidebar = 'no_sidebar';
		else
			$sidebar = '';

		// INTL: load mediawiki messages for sidebar expand and collapse for later use in sidebar boxes
		$langKeys = array('navlist_collapse', 'navlist_expand');
		echo WikiHow_i18n::genJSMsgs($langKeys);
	?>
    <div id="article_shell" class="<?= $sidebar ?>">
    	<div class="article_top"></div>
		<? if($wgUser->getID() == 0)
			echo wikihowAds::getSetup();
			
			if (WikiHowTemplate::$showRecipeTags) {
				if (WikiHowTemplate::$showhRecipeTags) {
					$recipe_hdr = 'class="hrecipe"';
				}
				else {
					$recipe_hdr = 'itemscope itemtype="http://schema.org/Recipe"';
				}
			}
			else {
				$recipe_hdr = '';
			}
		?>
		
        <div id="article" <?=$recipe_hdr?>>

		<? if ($isMainPage) { ?>
			<?=$mpFAs;?>
			<?=$mpCategories;?>
			<? if ($isMainPage && $wgLanguageCode == 'en'){
					//INTL: Only show new article for the English site
					if(class_exists('NewHowtoArticles')){
						$newArticles = new NewHowtoArticles();
						echo $newArticles->getNewArticlesBox();
					}
				}
			?>

		<? } else { ?>
        	<div class="article_inner">
				<?
				if ($wgTitle->userCanEdit() && $action != 'edit' && $action != 'diff') {
					//INTL: Need bigger buttons for non-english sites
					$editArticleButtonClass = ($wgLanguageCode == 'en') ? "button edit_article_button" : "button edit_article_button_intl";
				?>
				<a href="<?=$wgTitle->escapeLocalURL($sk->editUrlOptions())?>" class="<?=$editArticleButtonClass?>" onmouseover="button_swap(this);" onmouseout="button_unswap(this);"><?=wfMsg('edit')?></a>
				<? } ?>

			<? if ($showBreadCrumbs): ?>
			<div id="gatBreadCrumb">
			<ul id="breadcrumb" class="Breadcrumbs">
				<?=$catlinkstop; ?>
			</ul>
			</div>
			<? endif; ?>
			<?=$heading?>
    		<?=$sk->getAuthorHeader();?>
			</div><!--end article_inner-->


	<? if ($wgTitle->getNamespace() != NS_SPECIAL) { ?>
	<?=$fbLinked?>
	<div id="article_tabs">
		<div id="share_buttons_top">
			<? 
				if(class_exists('WikihowShare'))
					echo WikihowShare::getTopShareButtons();
			?>
		</div>
		<a href="<? if ($wgTitle->isTalkPage()) echo $wgTitle->getSubjectPage()->getFullURL(); else echo $wgTitle->getFullURL(); ?>"
			id="tab_article" title="Article" <?php if (!MWNamespace::isTalk($wgTitle->getNamespace()) && $action != "edit" && $action != "history") echo 'class="on"'; ?> onmousedown="button_click(this);"><?php if ($wgTitle->getSubjectPage()->getNamespace() == NS_USER) echo wfMsg("user"); else echo wfMsg("article"); ?></a>
     	<span id="gatEdit"><a href="<?=$wgTitle->escapeLocalURL($sk->editUrlOptions())?>" id="tab_edit" title="Edit" onmousedown="button_click(this);"
			 <?php if ($action == "edit") echo 'class="on"'; ?>
		>
		<? if ('en' == $wgLanguageCode) { ?><div class="tab_pencil edit_pencil"></div><? } ?> <?= wfMsg('edit') ?></a></span>
		<? if ($action =='view' && MWNamespace::isTalk($wgTitle->getNamespace())) {
                $talklink = '#postcomment';
			} else {
			 	$talklink = $wgTitle->getTalkPage()->getLocalURL();
			}
           if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {
           		$msg = wfMsg('talk');
           } else {
				$msg = wfMsg('discuss');
           }
		?>
        <span id="gatDiscussionTab"><a href="<? echo $talklink; ?>"  <?php if ($wgTitle->isTalkPage() && $action != "edit" && $action != "history") echo 'class="on"'; ?> id="tab_discuss" title="<?= $msg ?>" onmousedown="button_click(this);"><?= $msg ?></a></span>
        <a href="<?=$wgTitle->getLocalURL( 'action=history' ); ?>" id="tab_history"  <?php if ($action == "history") echo 'class="on"'; ?>  title="<?= wfMsg('history') ?>" onmousedown="button_click(this);"><?= wfMsg('history') ?></a>


	<? if ($wgUser->isSysop() && $wgTitle->userCan('delete')) { ?>
		 <a href="" id="tab_admin" title="Admin" onmouseover="button_swap(this);AdminTab(this,true);" onmouseout="button_unswap(this);AdminTab(this,true);" onmousedown="button_click(this);"><?=wfMsg('admin_admin')?> <img src="<?= wfGetPad('/skins/WikiHow/images/admin_arrow.gif') ?>" width="6" height="6" alt="" /></a>
                <ul id="AdminOptions" onmouseover="button_swap(this);AdminTab(this,false);" onmouseout="button_unswap(this);AdminTab(this,false);">
                    <li><a href="<?= $wgTitle->getLocalURL( 'action=protect' ); ?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?= !$wgTitle->isProtected() ? wfMsg('protect') : wfMsg('unprotect') ?></a></li>
                    <li><a href="<?= SpecialPage::getTitleFor("Movepage", $wgTitle)->getLocalURL() ?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?= wfMsg('admin_move') ?></a></li>
                    <li><a href="<?= $wgTitle->getLocalURL( 'action=delete' ) ?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?= wfMsg('admin_delete') ?></a></li>

                </ul>
	<? } ?>
	   </div><!--end article_tabs-->
<? } // no article tabs for special pages ?>

	   <?php wfRunHooks( 'BeforeTabsLine', array( &$wgOut ) ); ?>
		    <div id="article_tabs_line"></div>
		<? } // Featured articls for main page mpFAs ?>
			<?= $profileBox ?>
			<div id='bodycontents'>
			
			<? if ($showSlideShow) { 
				echo "<div id='showslideshow'></div>";
			} ?>

			<? if ($showFBBar) { 
				echo "<div class='fb_bar_outer'>I want to do this: <div id='fb_action_wants_to' class='fb_bar_img'></div></div>";
			} ?>
			

		    <?php $this->html('bodytext') ?>
			</div>
			<?=$suggested_titles?>
			<? if (!$show_ad_section) {
    			echo "<div id='lower_ads'>{$bottom_ads}</div>";
			 }
				if ($show_ad_section) {
					echo $ad_section;
				}
			?>
			<?= $bottom_site_notice ?>
	 		<? if ($wgTitle->isTalkPage()) {
				if ($wgTitle->getFullURL() != $wgUser->getUserPage()->getTalkPage()->getFullURL()) { ?>
 				<div class="article_inner">
 				<?Postcomment::getForm(); ?>
 				</div>
				<? } else { ?>
						<a name='postcomment'></a>
						<a name='post'></a>
			<? 		}
				} ?>


	<?
		if (($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT)  && $action == 'view' && !$isMainPage) {
			$catlinks = $sk->getCategoryLinks(false);
			$authors = $sk->getAuthorFooter();
			if ($authors != "" || is_array($this->data['language_urls']) || $catlinks != "") {
	?>
<h2 class="section_head" id="article_info_header"><?= wfMsg('article_info') ?></h2>
    <div id="article_info" class="article_inner">
		<?=$fa?>
        <p><?=$sk->getLastEdited();?></p>
		<p>
			<?echo wfMsg('categories') . ":<br/>{$catlinks}"; ?>

        </p>
			<p><?=$authors?></p>

        <?php if (is_array($this->data['language_urls'])) { ?>
        <p>
            <?php $this->msg('otherlanguages') ?><br /><?php
                $links = array();
                foreach($this->data['language_urls'] as $langlink) {
					$linkText = $langlink['text'];
					preg_match("@interwiki-(..)@", $langlink['class'], $langCode);
					if (!empty($langCode[1])) {
						$sk = $wgUser->getSkin();
						$linkText = $sk->getInterWikiLinkText($linkText, $langCode[1]);
					}
                    $links[] = htmlspecialchars(trim($langlink['language'])) . '&nbsp;<span><a href="' .  htmlspecialchars($langlink['href']) . '">' .  $linkText . "</a><span>";
                }
                echo implode("&#44;&nbsp;", $links);
            ?>
        </p>
        <? } ?>
    </div><!--end article_info-->
	<? 		}
		}
		if (($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT || $wgTitle->getNamespace() == NS_CATEGORY) && $action == 'view' && !$isMainPage) {
	?>
<div id='article_tools_header'>
<h2 class="section_head"><?= wfMsg('article_tools') ?></h2>
</div> <!-- article_tools_header -->
	<div class="article_inner">
		<? if (!$wgIsDomainTest && ($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_CATEGORY)) { ?>
		<div id="share_icons">
		    <div><?=wfMsg('at_share')?></div>
		    <span id="gatSharingTwitter" ><a onclick="javascript:share_article('twitter');" id="share_twitter"></a></span>
		    <span id="gatSharingStumbleupon"> <a onclick="javascript:share_article('stumbleupon');" id="share_stumbleupon"></a></span>
		    <span id="gatSharingFacebook"> <a onclick="javascript:share_article('facebook');" id="share_facebook"></a></span>
		    <span id="gatSharingBlogger"> <a onclick="javascript:share_article('blogger');" id="share_blogger"></a></span>
		    <span id="gatSharingGoogleBookmarks"> <a onclick="javascript:share_article('google');" id="share_google"></a></span>
		    <? 
				if(class_exists('WikihowShare'))
					echo WikihowShare::getBottomShareButtons();
			?>
		    <br class="clearall" />
		</div><!--end share_icons-->
		<? } ?>
	    <ul id="end_options">
	        <li id="endop_discuss"><a href="<?echo $talklink;?>" id="gatDiscussionFooter"><?=wfMsg('at_discuss')?></a></li>
	        <li id="endop_print"><a href="<?echo $wgTitle->getLocalUrl('printable=yes');?>" id="gatPrintView"><?echo wfMsg('print');?></a></li>
			<li id="endop_email"><a href="#" onclick="return emailLink();" id="gatSharingEmail"><?=wfMsg('at_email')?></a></li>
			<? if($wgUser->getID() > 0): ?>
				<? if ($wgTitle->userIsWatching()) { ?>
					<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=unwatch');?>"><?=wfMsg('at_remove_watch')?></a></li>
				<? } else { ?>
					<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=watch');?>"><?=wfMsg('at_watch')?></a></li>
				<? } ?>
			<? endif; ?>
	        <li id="endop_edit"><a href="<?echo $wgTitle->getEditUrl();?>" id="gatEditFooter"><?echo wfMsg('edit');?></a></li>
			<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
	        	<li id="endop_fanmail"><a href="/Special:ThankAuthors?target=<?echo $wgTitle->getPrefixedURL();?>" id="gatThankAuthors"><?=wfMsg('at_fanmail')?></a></li>
			<? } ?>
	    </ul>


		<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
			<div id="embed_this"><span>+</span> <a href="/Special:Republish/<?= $wgTitle->getDBKey() ?>" id="gatSharingEmbedding" rel="nofollow"><?=wfMsg('at_embed')?></a></div>
		<? } ?>
		<?php if( $wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getNamespace() == NS_MAIN) {
			echo wikihowAds::getAdUnitPlaceholder(7);
		} ?>
	</div><!--end article_inner-->
</div> <!-- article -->
    <div id="last_question">
            <p id="article_line"></p>
			<?=$userstats;?>
            <p><b><?=$sk->pageStats(); ?></b></p>


			<div id='page_rating'>
			<?echo RateArticle::showForm();?>
           	</div>
            <p></p>
	<? } ?>
   </div>  <!--end last_question-->

	<? if ($isMainPage) {
		//DO NOTHING
	} else if ((self::isSpecialBackground() ||
		$wgTitle->getNamespace() == NS_USER ||
		$wgTitle->getNamespace() == NS_PROJECT ||
		$wgTitle->getNamespace() == NS_CATEGORY ||
		$wgTitle->getNamespace() == NS_MAIN ) &&
		$action != 'edit') {?>
        <div class='article_bottom'></div>
	<? } else { ?>
    	<div class='article_bottom_white'></div>
	<? } ?>
	
	<? if (!$isMainPage) { ?>

</div>  <!--end article_shell-->
	<? } ?>
	<? if ($showSideBar): ?>
    <div id="sidebar">
        <div id="top_links">
            <a href="/Special:Createpage" class="button button136" style="float: left;" id="gatWriteAnArticle" onmouseover="button_swap(this);" onmouseout="button_unswap(this);"><?=wfMsg('writearticle');?></a>
            <a href="/Special:Randomizer" id="gatRandom" accesskey='x'><b><?=wfMsg('randompage'); ?></b></a>
			<? if (class_exists('Randomizer') && Randomizer::DEBUG && $wgTitle && $wgTitle->getNamespace() == NS_MAIN && $wgTitle->getArticleId()): ?>
				<?= Randomizer::getReason($wgTitle) ?>
			<? endif; ?>
		</div><!--end top_links-->
		<?php
			if($wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getText() != 'Userlogin' && $wgTitle->getNamespace() == NS_MAIN){
				//comment out next line to turn off HHM ad
				if(wikihowAds::isMtv() && ($wgLanguageCode =='en'))
					echo wikihowAds::getMtv();
				else if( wikihowAds::isHHM() && ($wgLanguageCode =='en'))
					echo wikihowAds::getHhmAd();
				else
					echo wikihowAds::getAdUnitPlaceholder(4);
			}
			//<!-- <a href="#"><img src="/skins/WikiHow/images/imgad.jpg" /></a> -->
		?>
	<?
		$likeDiv = "";

		if (class_exists('CTALinks') && CTALinks::isArticlePageTarget()) {
			$fb_wikiHow_iframe = <<<EOHTML
<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2Fwikihow&amp;layout=standard&amp;show_faces=false&amp;width=215&amp;action=like&amp;colorscheme=light&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:215px; height:40px;" allowTransparency="true"></iframe>
EOHTML;
			$likeDivBottom = $wgUser->getID() > 0 ? "_bottom" : "";
			$cdnBase = wfGetPad('');
			$likeDiv = <<<EOHTML
				<div id="fb_sidebar_shell$likeDivBottom">
					<div><img class="module_cap" alt="" src="{$cdnBase}/skins/WikiHow/images/fblike/LikeOffWhite_Top.png"></div>
					<div id="fb_sidebar">
						<span id ="fb_icon"><img src="{$cdnBase}/skins/WikiHow/images/fblike/facebook_icon.png"></span>
						<div id="follow_facebook"><span><a href="http://www.facebook.com/wikiHow">Follow wikiHow</a></span> on facebook</div>
						<div id="fb_sidebar_content"></div>
					</div>
					<div><img class="module_cap" alt="" src="$cdnBase/skins/WikiHow/images/fblike/LikeOffWhite_Bottom.png"></div>
				</div>
EOHTML;

			//$likeDiv = "";
			if ($wgUser->getId() == 0 || $wgRequest->getVal('likeDiv')) {
				echo $likeDiv;
				echo wfMsg('like_test', $likeDivBottom);
			}
		}
	?>
		<?if ($mpWorldwide !== "") { ?>
			<?=$mpWorldwide;?>
		<? }  ?>

				<!--
				<div class="sidebox_shell">
					<div class='sidebar_top'></div>
					<div id="side_fb_timeline" class="sidebox">
					</div>
					<div class='sidebar_bottom_fold'></div>
				</div>
				-->
				<!--end sidebox_shell-->
	<?
			$related_articles = $sk->getRelatedArticlesBox($this);
            //disable custom link units
			//  if ($wgUser->getID() == 0 && $wgTitle->getNamespace() == NS_MAIN && !$isMainPage)
            //if ($related_articles != "")
				//$related_articles .= WikiHowTemplate::getAdUnitPlaceholder(2, true);
			if ($action == 'view' && $related_articles != "") {
	?>
				<div class="sidebox_shell">
					<div class='sidebar_top'></div>
					<div id="side_related_articles" class="sidebox">
						<?=$related_articles?>
					</div><!--end side_related_articles-->
					<div class='sidebar_bottom_fold'></div>
				</div><!--end sidebox_shell-->
				<?
			}

			if ($wgUser->getID() == 0 && !$isMainPage && $action == 'view')
				echo wikihowAds::getAdUnitPlaceholder(2, true);

	?>

         <!-- Sidebar Widgets -->
		<? foreach ($sk->mSidebarWidgets as $sbWidget) { ?>
  	      <?= $sbWidget ?>
		<? } ?>
         <!-- END Sidebar Widgets -->

		<? if ($wgUser->getID() > 0) echo $navigation; ?>


	<? if ($action == "view" && !$isMainPage) { ?>
	<div class="sidebox_shell">
        <div class='sidebar_top'></div>
        <div id="side_featured_articles" class="sidebox">
			<?php if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getText() == wfMsg('mainpage'))
					echo $sk->getFeaturedArticlesBox(15, 100);
				else
					echo $sk->getFeaturedArticlesBox(4, 4);
				?>
        </div>
        <div class='sidebar_bottom_fold'></div>
	</div>
	<? } ?>

    <?php if ($showRCWidget) { ?>
	<div class="sidebox_shell" id="side_rc_widget">
        <div class='sidebar_top'></div>
        <div id="side_recent_changes" class="sidebox">
            <? RCWidget::showWidget(); ?>
			<p class="bottom_link">
			<? if ($wgUser->getID() > 0) { ?>
            	<?= wfMsg('welcome', $wgUser->getName(), $wgUser->getUserPage()->getLocalURL()); ?>
			<? } else { ?>
            	<a href="/Special:Userlogin" id="gatWidgetBottom"><?=wfMsg('rcwidget_join_in')?></a>
			<? } ?>
			<a href="" id="play_pause_button" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" onclick="rcTransport(this); return false;" ></a>
            </p>
        </div><!--end side_recent_changes-->
        <div class='sidebar_bottom_fold'></div>
	</div><!--end sidebox_shell-->
	<?php } ?>

	<?php if (class_exists('FeaturedContributor') && $wgTitle->getNamespace() == NS_MAIN && !$isMainPage) { ?>
	<div class="sidebox_shell">
        <div class='sidebar_top'></div>
        <div id="side_featured_contributor" class="sidebox">
        <?  FeaturedContributor::showWidget();  ?>
			<? if ($wgUser->getID() == 0) { ?>
        <p class="bottom_link">
           <a href="/Special:Userlogin" id="gatFCWidgetBottom" onclick='gatTrack("Browsing","Feat_contrib_cta","Feat_contrib_wgt");'><? echo wfMsg('fc_action') ?></a>
        </p>
			<? } ?>
        </div><!--end side_featured_contributor-->
        <div class='sidebar_bottom_fold'></div>
	</div><!--end sidebox_shell-->
	<?php } ?>

      	<? if ($wgUser->getID() == 0) echo $navigation; ?>

		<?=$user_links; ?>
	<?
	if (class_exists('CTALinks') && CTALinks::isArticlePageTarget() && CTALinks::isLoggedIn()) {
		echo $likeDiv;
		echo wfMsg('like_test', $likeDivBottom);
	}
	?>
	<? if ($showFollowWidget): ?>
	<div class="sidebox_shell">
        <div class='sidebar_top'></div>
        <div class="sidebox">
			<? FollowWidget::showWidget(); ?>
        </div>
        <div class='sidebar_bottom_fold'></div>
	</div>
	<? endif; ?>
	<?php if(class_exists('IheartwikiHow') && !$isMainPage && $wgUser->getID() > 0): ?>
	<div class="sidebox_shell">
        <div class='sidebar_top'></div>
        <div class="sidebox">
		<?php echo IheartwikiHow::addIheartwikiHowWidget(); ?>
        </div>
        <div class='sidebar_bottom_fold'></div>
	</div>
	<? endif; ?>
</div><!--end sidebar-->
<? endif; // end if $showSideBar ?>
</div><!--end main-->
<br class="clearall" />

<div id="footer_shell">
    <div id="footer">

        <div id="footer_side">
			<div class="footer_logo footer_sprite"></div>
			 <p id="footer_tag"><?=wfMsg('main_logo_title')?></p>
			 <? if($wgUser->getID() > 0): ?>
				<?=wfMsgExt('site_footer_new', 'parse'); ?>
			<? else: ?>
				<?=wfMsgExt('site_footer_new_anon', 'parse'); ?>
			 <? endif; ?>
        </div><!--end footer_side-->

        <div id="footer_main">
		<?= $footer_search ?>
			<br class="clearall" />
			<h3><?= wfMsg('explore_categories') ?></h3>

			<span id="gatFooterCategories">
				<?= $sk->getCategoryList() ?>
			</span>
			
	    	<div id="sub_footer">
				<?php if($wgUser->getID() > 0 || $isMainPage): ?>
					<?= wfMsg('sub_footer_new', wfGetPad(), wfGetPad()) ?>
				<?php else: ?>
					<?= wfMsg('sub_footer_new_anon', wfGetPad(), wfGetPad()) ?>
				<? endif; ?>
        	</div>
        </div><!--end footer_main-->
        <br class="clearall" />
    </div><!--end footer-->
</div><!--end footer_shell-->
<div id="dialog-box" title=""></div>
<?php
//XXADDED QUICK NOTE/EDIT POPUP
if (($action == 'diff' ) && ($wgLanguageCode =='en')) {

	echo QuickNoteEdit::displayQuicknote();
	echo QuickNoteEdit::displayQuickedit();
	//echo QuickNoteEdit::display();
}

//Slider box
//for non-logged in users on articles only
if ($showSliderWidget) {
	echo Slider::getBox();
	echo '<div id="slideshowdetect"></div>';
	//if ($wgTitle->getFullText() == wfMsg('mainpage')) {
	//	echo '<div id="slideshowdetect_mainpage"></div>';
	//}
}
?>


<?= WikiHowTemplate::getPostLoadedAdsHTML() ?>

<?
	// This temporary "Crazy Egg" code is a script that should be removed
	// within a day of being published.  Contact Reuben on Dec. 20, 2010 if
	// it's not gone.
	/*$pageName = $wgTitle->getPartialURL();
	if ($isMainPage || $pageName == 'Make-a-New-Facebook-Account' || $pageName == 'Ask-a-Girl-Out' || $pageName == 'Clear-Your-Browser%27s-Cache') { ?> <script type="text/javascript" src="http://dnn506yrbagrg.cloudfront.net/pages/scripts/0010/4192.js"></script> <? }*/ ?>

<?
$trackData = array();
// Data analysis tracker

if (class_exists('CTALinks') && /*CTALinks::isArticlePageTarget() &&*/ trim(wfMsgForContent('data_analysis_feature')) == "on" && !CTALinks::isLoggedIn() && $wgTitle->getNamespace() == NS_MAIN ) {
	// Ads test for logged out users on article pages
	echo wfMsg('data_analysis');
}

echo wfMsg('client_data_analysis');

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
$trackUrl = str_replace("$wgServer", "", $trackUrl);
if ($wgServer != 'http://www.wikihow.com') {
	$trackUrl = str_replace("http://www.wikihow.com", "", $trackUrl);
}
$trackUrl .= '::';
$trackUrl .= "," . implode(",", $trackData) . ",";
?>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2375655-1']);
  _gaq.push(['_setDomainName', '.wikihow.com']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_trackPageLoadTime']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = "<?= wfGetPad('/skins/common/ga.js') ?>?<?=WH_SITEREV?>";
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<!-- Google Analytics Event Track -->
<? //merged with other JS above: <script type="text/javascript" src="<?= wfGetPad('/skins/WikiHow/gaWHTracker.js') ? >"></script>?>
<script type="text/javascript">
if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
	jQuery(window).load(gatStartObservers);
} else {
	Event.observe(window, 'load', gatStartObservers);
}
</script>
<!-- END Google Analytics Event Track -->
<?
if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
	echo CTALinks::getGoogleControlTrackingScript();
	echo CTALinks::getGoogleConversionScript();
}
?>
<!-- LOAD EVENT LISTENERS -->
<?php if ($wgTitle->getPrefixedURL() == wfMsg('mainpage') && $wgLanguageCode == 'en') { ?>
<script type="text/javascript">
if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
	jQuery(window).load(initSA);
} else {
	Event.observe(window, 'load', initSA);
}
</script>
<?php } ?>

<!-- LOAD EVENT LISTENERS ALL PAGES -->
	<div id='img-box'></div>
<?
if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
	echo CTALinks::getBlankCTA();
}
?>
<!-- div needed for FB login -->
<div id="fb-root" ></div>

<?
// QuickBounce test
if (false && $sk->isQuickBounceUrl('ryo_urls')) {
?>
<!-- Begin W3Counter Secure Tracking Code -->
<script type="text/javascript" src="https://www.w3counter.com/securetracker.js"></script>
<script type="text/javascript">
w3counter(55901);
</script>
<noscript>
<div><a href="http://www.w3counter.com"><img src="https://www.w3counter.com/tracker.php?id=55901" style="border: 0" alt="W3Counter" /></a></div>
</noscript>
<!-- End W3Counter Secure Tracking Code-->
<?
}
?>
  </body><?php if (($wgRequest->getVal("action") == "edit" || $wgRequest->getVal("action") == "submit2") && $wgRequest->getVal('advanced', null) != 'true') { ?>
	<script type="text/javascript">
		if (document.getElementById('steps') && document.getElementById('wpTextbox1') == null) {
	            InstallAC(document.editform,document.editform.q,document.editform.btnG,"./<?= $wgLang->getNsText(NS_SPECIAL).":TitleSearch" ?>","en");
		}
        </script>
<?php } ?>

<script type="text/javascript">
	(function ($) {
		// fired on DOM ready event
		$(document).ready(function() {
			WH.addScrollEffectToTOC();
		});

		$(window).load(function() {
			
			if ($('.twitter-share-button').length) {

				// Load twitter script
				$.getScript("http://platform.twitter.com/widgets.js", function() {
					twttr.events.bind('tweet', function(event) {
						if (event) {
							var targetUrl;
							if (event.target && event.target.nodeName == 'IFRAME') {
							  targetUrl = extractParamFromUri(event.target.src, 'url');
							}
							_gaq.push(['_trackSocial', 'twitter', 'tweet', targetUrl]);
						}
					});

				});
			}

			if ($('#fb_sidebar_content').length) {
				$('#fb_sidebar_content').html('<?= trim($fb_wikiHow_iframe) ?>');
			}
			if( isiPhone < 0 && isiPad < 0 && $('.gplus1_button').length){
				WH.setGooglePlusOneLangCode();
				var node2 = document.createElement('script');
				node2.type = 'text/javascript';
				node2.src = 'http://apis.google.com/js/plusone.js';
				$('body').append(node2);
			}
			// Init Facebook components
			WH.FB.init('new');	
			
			if($('#pinterest').length) {
				var node3 = document.createElement('script');
				node3.type = 'text/javascript';
				node3.src = 'http://assets.pinterest.com/js/pinit.js';
				$('body').append(node3);
			}
		});
	})(jQuery);
</script>

	<?= $wgOut->getScript() ?>

<? if (class_exists('GoodRevision')): ?>
	<? $grevid = GoodRevision::getUsedRev( $wgTitle->getArticleID() ); ?>
	<? if ($grevid): ?>
		<!-- displaying patrolled oldid: <?= $grevid ?> -->
	<? endif; ?>
<? endif; ?>
<?php $this->html('reporttime') ?>

</html>

	<?php
}


}


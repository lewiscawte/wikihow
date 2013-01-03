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
	public $mAuthors;
	public $mSidebarWidgets	= array();

	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'WikiHow';
		$this->stylename = 'WikiHow';
		$this->template  = 'WikiHowTemplate';
	}

	function addWidget($html) {
		$display="
	<div class='sidebox_shell'>
		<div class='sidebar_top'></div>
		<div class='sidebox'> ". $html ." </div>
		<div class='sidebar_bottom_fold'></div>
	</div>\n";

		array_push($this->mSidebarWidgets,$display);
		return;
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

		$dbr = wfGetDB(DB_SLAVE);

		$bad = User::getBotIDs();
		$bad[] = 0;  // filter out anons too, as per Jack

		$row = $dbr->selectRow('revision', array('rev_user', 'rev_user_text', 'rev_timestamp'),
				array('rev_user NOT IN (' . $dbr->makeList($bad) . ")", "rev_page"=>$wgTitle->getArticleID()),
				"SkinWikihowskin::getLastEdited",
				array("ORDER BY" => "rev_id DESC", "LIMIT"=>1)
			);
		$html = '';
		$u = User::newFromName($row->rev_user_text);
		if ($row && $row->rev_user != 0 && $u) {
			$ts = wfTimestamp(TS_UNIX, $row->rev_timestamp);
			$html = wfMsg('last_edited') . "<br/>";
			$html .= wfMsg('last_edited_by', date("F j, Y", $ts), $u->getName() , $u->getUserPage()->getFullURL());
		}
		return $html;
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
		global $wgTitle, $wgParser;
		$r = Revision::newFromTitle($wgTitle);
		$text = $r->getText();
		$whow = new WikiHow();
		$whow->loadFromText($text);
		$related = preg_replace("@^==.*@m", "", $whow->getSection('related wikihows'));

		$preg = "/\\|[^\\]]*/";
		$related = preg_replace($preg, "", $related);
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
		return $result . "</table>";
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

	function hasIntroImage() {
		global $wgTitle;
		$r = Revision::newFromTitle($wgTitle);
		if ($r == null) return false;
		$intro = Article::getSection($r->getText(), 0);
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
		global $wgTitle, $wgContLang, $wgUser, $wgRequest, $wgMemc;

		if ($wgTitle->getNamespace() != NS_MAIN || $wgTitle->getFullText() == wfMsg('mainpage') || $wgRequest->getVal('action') != '') return '';
		$key = wfMemcKey("related_articles_box_1_" . $wgTitle->getArticleID());
		if ($wgMemc->get($key)) {
			return $wgMemc->get($key);
		}
		$html = $e->data['bodytext'];
		$find = '<div id="relatedwikihows" ';
		$i = strpos($html, '<div id="relatedwikihows" ');
		$createNewArticle = $wgRequest->getVal('create-new-article', '') == 'true';
		$result = "";

		$num = intval(wfMsgForContent('num_related_articles_to_display'));
		if ($num == 0 || $num > 10 || $num < 0)
			$num = 5;

		$sk = $wgUser->getSkin();

		if ($i !== false && !$createNewArticle) {
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
				$sk = $wgUser->getSkin();
				$dbr = wfGetDB( DB_SLAVE );
				$res = $dbr->select('categorylinks', 'cl_from', array ('cl_to' => $cat1),
					"WikiHowSkin:getRelatedArticlesBox",
					array ('ORDER BY' => 'rand()', 'LIMIT' => $num*2));
				$count = 0;
				while (($row = $dbr->fetchObject($res)) && $count < $num) {
					if ($row->cl_from == $wgTitle->getArticleID()) {
						continue;
					}
					$t = Title::newFromID($row->cl_from);
					if (!$t) {
						continue;
					}
					if ($t->getNamespace() != NS_MAIN) {
						continue;
					}
					$result .= $this->featuredArticlesLine($t, $t->getFullText());
					$count++;
				}
				if ($result != '') {
					$result = "<h3>" . wfMsg('relatedarticles') . "</h3><table>$result\n</table>";
				}

			}
		}
		$wgMemc->set($key, $result, 3600);
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
	function getGalleryImage($title, $width, $height) {

		global $wgMemc, $wgLanguageCode;

		$key = wfMemcKey("gallery1:{$title->getArticleID()}:$width:$height");

		if ($wgMemc->get($key)) {
			return $wgMemc->get($key);
		}

		if (($title->getNamespace() == NS_MAIN) || ($title->getNamespace() == NS_CATEGORY) ) {
			if ($title->getNamespace() == NS_MAIN) {
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
				preg_match("@\[\[Image[^\]]*\]\]@im", $text, $matches);
				foreach($matches as $i) {
					$i = preg_replace("@\|.*@", "", $i);
					$i = preg_replace("@^\[\[@", "", $i);
					$i = preg_replace("@\]\]$@", "", $i);
					$i = urldecode($i);
					$image = Title::newFromText($i);
					if ($image && $image->getArticleID() > 0) {
						$file = wfFindFile($image);
						if ($file && isset($file)) {
							$thumb = $file->getThumbnail($width, $height, true, true);
							#$thumb = "";
							//print_r($thumb);
							//echo "from the skin: echo {$thumb->url} <br>\n";
							if ($thumb instanceof MediaTransformError) {
								// we got problems!
								print_r($thumb); exit;
							} else {
								$wgMemc->set($key, wfGetPad($thumb->url), 2* 3600); // 2 hours
								return wfGetPad($thumb->url);
							}
						} else {
							echo "couldn't find file $image\n";
							wfDebug("SKIN gallery can't find image $i \n");
						}
					} else {
						wfDebug("SKIN gallery can't find image title $i \n");

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
			if (isset($catmap[$title->getPartialURL()])) {
				$cat = $title->getPartialURL();
			} else {
				$cat = self::getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial URL for self::getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					$cat = $title->getPartialURL();
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
		$key = "wikihowskin_newarticlesbox";
		$cached = $wgMemc->get(wfMemcKey($key));
		if ($cached)  {
			return $cached;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('pagelist', 'pl_page', array('pl_list'=>'risingstar'),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'pl_page desc', 'LIMIT'=>5)
			);
		while($row = $dbr->fetchObject($res)) {
			$ids[] = $row->pl_page;
		}
        $html = "<h3>" . wfMsg('newarticles') . "</h3>\n<table>";
		$res = $dbr->select(array('page'),
			array('page_namespace', 'page_title'),
			array('page_id IN (' . implode(",", $ids) . ")"),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'page_id desc', 'LIMIT'=>5)
			);
		while($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle(NS_MAIN, $row->page_title);
			if (!$t)
				continue;
			$html .= $this->featuredArticlesLine($t, $t->getText());
		}
		$html .=  "</table>";
		$wgMemc->set($key, $html, 3600);
		return $html;
	}
	function getFeaturedArticlesBox($dayslimit = 11, $linkslimit = 4 ) {
		global $wgStylePath, $wgUser, $wgServer, $wgScriptPath, $wgTitle, $wgLang, $IP, $wgProdServer;
		$sk = $wgUser->getSkin();
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
			$url = str_replace("$wgServer$wgScriptPath/", "", $url);
			$url = str_replace("http://www.wikihow.com/", "", $url);
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
		return $html;
	}

	function getFeaturedArticlesBoxWide($dayslimit = 11, $linkslimit = 4, $ismainpage = true ) {
		global $wgStylePath, $wgUser, $wgServer, $wgScriptPath, $wgTitle, $wgLang, $IP, $wgProdServer;
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

		$hidden = "<div id='hiddenFA' style='display:none'><div>
	<table class='featuredArticle_Table'>";

		$now = time();
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$count = 0;
		foreach ($feeds as $item) {
			$url = $item[0];
			$d = $item[1];
			if ($d > $now) continue;
			$url = str_replace("$wgServer$wgScriptPath/", "", $url);
			$url = str_replace("http://www.wikihow.com/", "", $url);
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
#echo $html; exit;
		return $html;
	}

	function getRADLinks($use_chikita_sky) {
		global $wgTitle;
		$channels = $this->getCustomGoogleChannels('rad_left', $use_chikita_sky);
		$links = wfMsg('rad_links_new', $channels[0], $channels[1]);
		$links = preg_replace('/\<[\/]?pre\>/', '', $links);
		return $links;
	}

	// NOT IN USE
	function getBottomGoogleAds() {
		$links = wfMsg('rad_links_link_units_468x15');
		$links = preg_replace('/\<[\/]?pre\>/', '', $links);
		return $links;
	}

	function getTopCategory($title = null) {
		global $wgTitle;
		if (!$title)
			$title = $wgTitle;
		$parenttree = $title->getParentCategoryTree();
		$parenttree_tier1 = $parenttree;

		$result = null;
		while ((!$result || $result == "WikiHow") && is_array($parenttree)) {
			$a = array_shift($parenttree);
			if (!$a) {
				$keys = array_keys($parenttree_tier1);
				$result = str_replace("Category:", "", $keys[0]);
				break;
			}
			$last = $a;
			while (sizeof($a) > 0 && $a = array_shift($a) ) {
				$last = $a;
			}
			$keys = array_keys($last);
			$result = str_replace("Category:", "", $keys[0]);
		}
		return $result;
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

		global $wgTitle, $wgLang, $IP;

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
		$ad['adunit4']				= '2662333532';
		$ad['adunit5']				= '7950773090';
		$ad['adunit6']				= '7732285575';
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

		foreach ($this->mGlobalChannels as $c) {
			$channels[] = $c;
		}
		foreach ($this->mGlobalComments as $c) {
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
		global $wgOut, $wgTitle, $wgUseCategoryBrowser, $wgUser;
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

	function loadAuthors() {
		global $wgUser, $wgTitle;
		if (is_array($this->mAuthors)) {
			return;
		}
		$this->mAuthors = array();
		$dbr = wfGetDB(DB_SLAVE);
		// filter out bots
		$bad = User::getBotIDs();
		$bad[] = 0;  // filter out anons too, as per Jack
		$opts = array('rev_page'=> $wgTitle->getArticleID());
		if (sizeof($bad) > 0) {
			$opts[]  = 'rev_user NOT IN (' . $dbr->makeList($bad) . ')';
		}
		$res = $dbr->select('revision',
			array('rev_user', 'rev_user_text'),
			$opts,
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
		if (!$wgTitle  || !($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT) || $wgRequest->getVal('action', 'view') != 'view'
			|| $wgRequest->getVal('diff') != '') return "";
		$this->loadAuthors();
		$users =  array_slice($this->mAuthors, 0, min(sizeof($this->mAuthors), 4));
		$html = "";
		if (sizeof($users) > 0) {
			$html = "<p id='originators'>" . wfMsg('originated_by') . "<span>"
				. $this->formatAuthorList($users) . "</span></p>";
		}
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

	function formatAuthorList($authors) {
		global $wgTitle, $wgRequest;
		if (!$wgTitle || !($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT)) return '';
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

	function outputPage( &$out ) {
		global $wgTitle, $wgArticle, $wgUser, $wgLang, $wgContLang, $wgOut;
		global $wgScript, $wgStylePath, $wgLanguageCode, $wgContLanguageCode, $wgUseNewInterlanguage;
		global $wgMimeType, $wgOutputEncoding, $wgUseDatabaseMessages, $wgRequest;
		global $wgDisableCounters, $wgLogo, $action, $wgFeedClasses;
		global $wgMaxCredits, $wgShowCreditsIfMax, $wgSquidMaxage, $IP;

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
		if($this->iseditable && $wgUser->getOption("editondblclick") )
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
			$categories = array("STUB","Hobbies-and-Crafts","Food-and-Entertaining");

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

	}

	function getLinkUnit($num) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$channels = $sk->getCustomGoogleChannels('linkunit' . $num, false);
		$s = wfMsg('linkunit' . $num, $channels[0]);
		$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		return $s;
	}

	function getAdUnit($num) {
		global $wgUser, $wgLanguageCode;
		$sk = $wgUser->getSkin();
		$channels = $sk->getCustomGoogleChannels('adunit' . $num, false);
		$s = wfMsg('adunit' . $num, $channels[0]);
		$s = "<div class='wh_ad'>" . preg_replace('/\<[\/]?pre[^>]*>/', '', $s) . "</div>";
		return $s;
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

	function adExclusions($title){
		switch($title){
			default:
				return false;
		}
	}

	function getAdUnitPlaceholder($num, $isLinkUnit = false, $postLoad = true) {
		global $wgEnableLateLoadingAds, $wgUser, $wgTitle;
		$sk = $wgUser->getSkin();

		if(self::adExclusions($wgTitle->getFullText()))
			return "";

		$unit = !$isLinkUnit ? self::getAdUnit($num) : self::getLinkUnit($num);
		$adID = !$isLinkUnit ? 'au' . $num : 'lu' . $num;

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
		$postLoad = $postLoadTest == 2;

		if ($postLoad && $adID === 'au4') {

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
		} else {
			return $unit;
		}
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
		$body= "";
		for ($i = 0; $i < sizeof($parts); $i++) {
			if ($i == 0) {
			// if there is no alt tag for the intro image, so it to be the title of the page
					preg_match("@<img.*mwimage101[^>]*>@", $parts[$i], $matches);
					if (sizeof($matches) > 0) {
						$m = $matches[0];
						$newm = str_replace('alt=""', 'alt="' . htmlspecialchars($wgTitle->getText()) . '"', $m);
				if ($m != $newm) {
						$parts[$i] = str_replace($m, $newm, $parts[$i]);
					}
				}
				// done alt test
				$anchorPos = stripos($parts[$i], "<a name=");
				if($anchorPos > 0 && $ads){
					if(stripos($parts[$i], "mwimg") != false){
						$body = "<div class='article_inner editable'>" . substr($parts[$i], 0, $anchorPos) . "<div class='ad_image'>" . WikiHowTemplate::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
					}else{
						$body = "<div class='article_inner editable'>" . substr($parts[$i], 0, $anchorPos) . "<div class='ad_noimage'>" . WikiHowTemplate::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
					}
				}
				else if($anchorPos == 0 && $ads)
					$body = "<div class='article_inner editable'>{$parts[$i]}" . WikiHowTemplate::getAdUnitPlaceholder('intro') . "</div>\n";
				else
					$body = "<div class='article_inner editable'>{$parts[$i]}</div>\n";
				continue;
			}
			if (stripos($parts[$i], "<h2") === 0 && $i < sizeof($parts) - 1) {
				preg_match("@<span>.*</span>@", $parts[$i], $matches);
				$rev = "";
				if (sizeof($matches) > 0) {
					$h2 =  trim(strip_tags($matches[0]));
					$rev = isset($reverse_msgs[$h2]) ? $reverse_msgs[$h2] : "";
				}
				$body .= $parts[$i];
				$i++;
				if ($rev == "steps") {
					$body .= "\n<div id=\"steps\" class='editable'>{$parts[$i]}</div>\n";
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

			#foreach ($parts as $p) {
			//XX Limit steps to 100 or it will timeout

			if ($numsteps < 100) {

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
								$p = '<li><div class="step_num">' . $current_li++ . '</div>';
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
											$p .= "<b class='whb'>";
											$apply_b = true;
										}
										if ($apply_b)
											$x = preg_replace("@([{$punct}])@im", "</b>$1", $x, 1, &$closecount);
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
									$p .= WikiHowTemplate::getAdUnitPlaceholder(0);
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
				//} else if (strpos($lp, "<li") !== false && $level == 1 && !$gotit) {
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
							$p = "<script>missing_last_ads = true;</script>" . WikiHowTemplate::getAdUnitPlaceholder(1) . $p;
							$no_third_ad = true;
						}
						else
						$p = WikiHowTemplate::getAdUnitPlaceholder(1) . $p;
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
						$newsection = "<table style='width: 645px;' id='video_table'>
							<tr><td>{$section}</td>
							<td style='width: 20%; vertical-align: top; padding: 12px 0 0 5px;'> &nbsp;"
							//. WikiHowTemplate::getAdUnitPlaceholder(1, true)
							. "</td></tr></table>";
						$body = str_replace($section, $newsection, $body);
						continue;
					} else if ($s == "tips") {
						//tip ad is now at the bottom of the tips section
						//need to account for the possibility of no sections below this and therefor
						//no anchor tag
						if($isAtEnd)
							$anchorTag = "<p></p>";
						$body = str_replace($section, $section . $anchorTag . WikiHowTemplate::getAdUnitPlaceholder('2a') , $body);
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

			// ads below whatever is the last section (Warnings, Things You'll Need, or Tips)
/*
			$foundlast = false;
			$sections = array_reverse($wgWikiHowSections);
			foreach ($sections as $section) {
				if ($section == "relatedwikihows" || $section ==  "sources")
					continue; // we skip these two bottom sections
				$i = strpos($body, '<div id="' . $section . '"');
				if ($i !== false)
					$j = strpos($body, '<h2>', $i + strlen($section));
				else
					continue; // we didnt' find this section
				if ($j === false) $j = strlen($body); // go to the end
				if ($j !== false && $i !== false) {
					$section  = substr($body, $i, $j - $i);
					$foundlast = true;
					$body = str_replace($section, $section . WikiHowTemplate::getAdUnitPlaceholder(3), $body);
					break;
				}
			}
			if (!$foundlast) {
				$body .= WikiHowTemplate::getAdUnitPlaceholder(3);
			}
	*/
		}

		return $body;
	}

	function logTopCat() {
		global $wgTitle, $wgUser;
		$sk = $wgUser->getSkin();
		$cat = $sk->getTopCategory($wgTitle);
		if (!$cat)
			return;
		$dbw = wfGetDB(DB_MASTER);
		$sql = "INSERT LOW_PRIORITY INTO cat_views (cv_user, cv_cat, cv_views) values ({$wgUser->getID()}, "
			. $dbw->addQuotes($cat) . ", 1) ON DUPLICATE KEY UPDATE cv_views=cv_views +1";
		$dbw->query($sql);
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
<?
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
		global $wgArticle, $wgScriptPath, $wgUser, $wgLang, $wgTitle, $wgRequest, $wgParser;
		global $wgOut, $wgScript, $wgStylePath, $wgLanguageCode, $wgForumLink;
		global $wgContLang, $wgXhtmlDefaultNamespace, $wgContLanguageCode;
		global $wgWikiHowSections, $IP, $wgServer;
		$prefix = "";

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
//print_r($wgUser);
		$cp = Title::newFromText("CreatePage", NS_SPECIAL);

		$sk->mGlobalChannels[] = "1640266093";
		$sk->mGlobalComments[] = "page wide track";

        // track WRM articles in Google AdSense
		// but not if they're included in the
		// tech buckets above
        if ($wgTitle->getNamespace() == NS_MAIN) {
            $dbr = wfGetDB(DB_MASTER);
            $minrev = $dbr->selectField('revision', 'min(rev_id)', array('rev_page'=>$wgTitle->getArticleID()));
			$details = $dbr->selectRow('revision', array('rev_user_text', 'rev_timestamp'), array('rev_id'=>$minrev));
			$fe = $details->rev_user_text;

			//Tech buckets (no longer only WRM)
			$foundTech = false;
			$title = $wgTitle->getFullURL();
			$msg = wfMsg('T_bin1'); //popular companies
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

			if(!$foundTech){
				$msg = wfMsg('T_bin2'); //startup companies
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$foundTech = true;
						$ts = $details->rev_timestamp;
						if (preg_match("@^201106@", $ts)){
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
				if (preg_match("@^201108@", $ts)){
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
				} else {
				   $sk->mGlobalChannels[] = "8110356115";
				}
            } else if (in_array($fe, array('Burntheelastic', 'CeeZee', 'Claricea', 'EssAy', 'JasonArton', 'Nperry302', 'Sugarcoat'))) {
                $sk->mGlobalChannels[] = "8537392489";
                $sk->mGlobalComments[] = "mt";
            } else if (in_array($fe, array('Wilfredor', 'WikiHow-Traduce')) ){
				$sk->mGlobalComments[] = "sp-trans";
				$ts = $details->rev_timestamp;
               	if (preg_match("@^201011@", $ts)){
					$sk->mGlobalChannels[] = "8127647116";
				} else if(preg_match("@^201012@", $ts)){
					$sk->mGlobalChannels[] = "7597356646";
				} else if(preg_match("@^201101@", $ts)){
					$sk->mGlobalChannels[] = "6910212794";
				} else if(preg_match("@^201102@", $ts)) {
                   $sk->mGlobalChannels[] = "2295248648";
               	} else if(preg_match("@^201103@", $ts)) {
                   $sk->mGlobalChannels[] = "9506496435";
               	} else if(preg_match("@^201104@", $ts)) {
                   $sk->mGlobalChannels[] = "364426392";
               	} else if(preg_match("@^201105@", $ts)) {
                   $sk->mGlobalChannels[] = "7608111817";
               	} else if(preg_match("@^201106@", $ts)) {
                   $sk->mGlobalChannels[] = "8068108984";
               	} else if(preg_match("@^201107@", $ts)) {
                   $sk->mGlobalChannels[] = "1934107964";
               	} else if(preg_match("@^201108@", $ts)) {
                   $sk->mGlobalChannels[] = "1863422820";
               	}
			} else if($fe == "WikiHow-%C3%9Cbersetzungen"){
				$sk->mGlobalComments[] = "de-trans";
				$ts = $details->rev_timestamp;
               	if (preg_match("@^201011@", $ts)){
					$sk->mGlobalChannels[] = "2772340565";
				} else if(preg_match("@^201012@", $ts)){
					$sk->mGlobalChannels[] = "9469327012";
				} else if(preg_match("@^201101@", $ts)){
					$sk->mGlobalChannels[] = "3374375696";
				} else if(preg_match("@^201102@", $ts)) {
                   $sk->mGlobalChannels[] = "2752192450";
               	} else if(preg_match("@^201103@", $ts)) {
                   $sk->mGlobalChannels[] = "9438535335";
               	} else if(preg_match("@^201104@", $ts)) {
                   $sk->mGlobalChannels[] = "1787780370";
               	} else if(preg_match("@^201105@", $ts)) {
                   $sk->mGlobalChannels[] = "7980159483";
               	} else if(preg_match("@^201106@", $ts)) {
                   $sk->mGlobalChannels[] = "8068108984";
               	} else if(preg_match("@^201107@", $ts)) {
                   $sk->mGlobalChannels[] = "9128058744";
               	} else if(preg_match("@^201108@", $ts)) {
                   $sk->mGlobalChannels[] = "6440318852";
               	}
            } else {
                $sk->mGlobalChannels[] = "5860073694";
                $sk->mGlobalComments[] = "!wrm && !mt";
            }

			//WRM buckets
			$found = false;
			$title = $wgTitle->getFullText();
			$msg = wfMsg('Dec2010_bin1');
			$articles = split("\n", $msg);
			foreach ($articles as $article) {
				if($article == $title){
					$found = true;
					$sk->mGlobalChannels[] = "8052511407";
					break;
				}
			}
			if(!$found){
				$msg = wfMsg('Dec2010_bin2');
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
				$msg = wfMsg('Dec2010_bin3');
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
				$msg = wfMsg('Dec2010_bin4');
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
				$msg = wfMsg('Dec2010_bin5');
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
				$msg = wfMsg('Dec2010_bin6');
				$articles = split("\n", $msg);
				foreach ($articles as $article) {
					if($article == $title){
						$found = true;
						$sk->mGlobalChannels[] = "1245159133";
						break;
					}
				}
			}

        }
		if ($action == "view" && $wgUser->getID() > 0) {
			$this->logTopCat();
		}

		$isWikiHow = false;
		if ($wgArticle != null && $wgTitle->getNamespace() == NS_MAIN)  {
			require_once("$IP/extensions/wikihow/WikiHow.php");
			$isWikiHow = WikiHow::articleIsWikiHow($wgArticle);
		}

		$isPrintable = $wgRequest->getVal("printable", "") == "yes";

		$contentStyle = "content";
		$bodyStyle = "body_style";

		// set the title and what not
		$avatar = '';
		if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {
			$real_name = User::whoIsReal(User::idFromName($wgTitle->getDBKey()));
			$name = $wgTitle->getDBKey();
			//XX AVATAR CODE
			$avatar = ($wgLanguageCode == 'en') ? Avatar::getPicture($name) : "";

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
				$wgTitle->getDBKey() . "\"><img src='".wfGetPad('/skins/common/images/envelope.png')."' border='0' alt='".wfMsg('alt_emailuser')."'></a>";
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
				$heading = "<h1 class='firstHeading'><a href=\"" . $wgTitle->getFullURL() . "\">" . wfMsg('howto', $this->data['title']) . "</a></h1>";
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
		if (($wgTitle->getNamespace() == NS_USER ) &&
			($wgRequest->getVal('action') != 'edit') &&
			($wgRequest->getVal('action') != 'protect') &&
			($wgRequest->getVal('action') != 'delete')) {
			$name = $wgTitle->getDBKey();
			if ($u = User::newFromName($wgTitle->getDBKey())) {
				if ($u->getOption('profilebox_display') == 1 && $wgLanguageCode == 'en') {
					//$profileBox = "<div class='article_inner'>" . ProfileBox::displayBox($u) . "</div>";
					$profileBox = ProfileBox::displayBox($u) ;
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
				|| $wgTitle->getFullText() == $wgLang->getNsText(NS_SPECIAL).":LSearch"){
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
			} else if ($rating < 0.75) {
				$sk->mGlobalChannels[] = "5009222491";
				$sk->mGlobalComments[] = "RQ3";
			} else {
				$sk->mGlobalChannels[] = "9665908481";
				$sk->mGlobalComments[] = "RQ4";
			}

/*
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
*/

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
            $rv = new RelatedVideos($wgTitle, array('wonderhowto', 'fivemin'));
            if ($rv->hasResults()) {
                $sk->mGlobalChannels[] = "1162261192";
                $sk->mGlobalComments[] = "has related vids";
                $vids = $rv->getResults();
                $related_vids .= RelatedVideoApi::getRelatedVideoPanel($vids, 4);
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
					$ads = $sk->getGoogleAds($use_chikita_sky, $related_vids != "")	;
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
				//$link = "<a href=\"$wgScriptPath/$subject" . $wgTitle->getDBKey() . "\">".wfMsg('authorpage', $sk->getUsernameFromTitle())."</a>";
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
			$imagepicklink = "<li><a href='" . $wgServer . "/wikiHow:IntroImageAdderStartPage'>" . wfMsg('IntroImageAdder') . "</a></li>";
			}
			$categorypickerlink = "<li>" . $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Uncategorizedpages"), wfMsg('UncategorizedPages')) . "</li>";
			$moreideaslink = "<li><a href='/Special:CommunityDashboard'>" . wfMsg('more-ideas') . "</a></li>";
			//$moreideaslink = "<li>" . $sk->makeLinkObj ($cp, wfMsg('more-ideas') ) . "</li>";
		}

		// For articles only
		$wlhlink = "";
		$statslink = "";
		if ($wgTitle->getNamespace() != NS_SPECIAL && $wgTitle->getFullText() != wfMsg('mainpage'))
			$wlhlink = "<li> <a href='" . Title::makeTitle(NS_SPECIAL, "Whatlinkshere")->getFullURL() . "/" . $wgTitle->getPrefixedURL() . "'>" . wfMsg('whatlinkshere') . "</a></li>";

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
			$announcement = "<span id='gatNewMessage'><div id='message_box'>Messages: {$announcement}</div></span>";
		}
		$userlinks = $sk->getUserLinks();

		$rtl = $wgContLang->isRTL() ? " dir='RTL'" : '';
       	$head_element = "<html xmlns=\"{$wgXhtmlDefaultNamespace}\" xml:lang=\"$wgContLanguageCode\" lang=\"$wgContLanguageCode\" $rtl>\n";
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
			$page_isfeatured = $dbr->selectField('page', 'page_is_featured', array("page_id={$wgTitle->getArticleID()}"));
			$featured = ($page_isfeatured == 1);
		}

		$show_ad_section = false;
/*
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
*/
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
			require_once("$IP/extensions/wikihow/SpecialLSearch.body.php");
			$l = new LSearch();
			$hits = $l->googleSearchResultTitles($wgTitle->getText(), 0, 2);
			$count = 0;
			if (sizeof($hits) > 0) {
				$search_results = "<div class='search_results_article_page' style='margin-top:10px; border: 1px solid #ccc; padding: 5px;'>" . wfMsg('search_results_looking_for') . "<ol>";
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

		$top_search = "";
		$footer_search = "";
		//INTL: Search options for the english site are a bit more complex
		if ($wgLanguageCode == 'en') {
			if ($wgUser->getID() == 0) {
            	$top_search = GoogSearch::getSearchBox("cse-search-box");
            	//$top_search = preg_replace('/\<[\/]?pre\>/', '', $top_search);
            	$footer_search = GoogSearch::getSearchBox("cse-search-box-footer");
            	//$footer_search = preg_replace('/\<[\/]?pre\>/', '', $footer_search) . "<br />";
            	$footer_search = $footer_search . "<br />";
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

		if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Video") {
			#$related_vids = Vide-
			$related_articles 	= Video::getRelatedArticlesList();
			$related_vids 		= Video::getRelatedVideosList();
		}

		// hack to get the FA template working, remove after we go live
		$fa = '';
		if (strpos($this->data['bodytext'], 'featurestar') !== false) {
			$fa = '<p id="feature_star">Featured Article</p>';
			$this->data['bodytext'] = preg_replace("@<div id=\"featurestar\">(.|\n)*<div style=\"clear:both\"></div>@mU", '', $this->data['bodytext']);
		}
		$fb = "";
		$tb = "";
		if ($wgTitle->getNamespace() == 0 && $action == 'view')  {
				$localeCode = $sk->getFacebookLocaleCode();
			if ($isMainPage) {
				$fb_iframe = <<<EOHTML
					<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2FwikiHow&amp;layout=button_count&amp;show_faces=false&amp;width=450&amp;action=like&amp;colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:100px; height:20px; padding-right:20px; float: right;"></iframe>
EOHTML;
			} else {

				$fb = '<div class="like_button"></div>';
				$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
				$fb_iframe = <<<EOHTML
				<iframe src="http://www.facebook.com/plugins/like.php?href=$url&locale=$localeCode&layout=button_count&show_faces=false&width=150&action=like&font=arial&colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:83px; height:25px"></iframe>
EOHTML;
				$fb_share = '<div class="like_button like_tools"></div>';
				$tb_admin = '<div class="admin_state"><a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a></div>';
				$tb = '<a href="http://twitter.com/share" data-lang="' . $wgLanguageCode . '" style="display:none; background-image: none; color: #ffffff;" class="twitter-share-button" data-count="horizontal" data-via="wikiHow" data-text="How to ' . htmlspecialchars($wgTitle->getText()) . '" data-related="JackHerrick:Founder of wikiHow">Tweet</a>';
			}
		}
		// munge the steps HTML to get the numbers working
		if ($wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() != wfMsg('mainpage')
			&& ($action=='view' || $action == 'purge')
		) {
			// on view. for preview, you have to munge the steps of the previewHTML manually
			$body = $this->data['bodytext'];
			$this->data['bodytext'] = self::mungeSteps($body);
			if($wgUser->getID() == 0 && $wgLanguageCode == "en")
				$this->data['bodytext'] .= WikihowTemplate::getAdUnitPlaceholder(4);
		} else if ($wgUser->getID() == 0 && MWNamespace::isTalk($wgTitle->getNamespace()) && ($action=='view' || $action == 'purge')) {
			// insert ads into talk page
			$body = $this->data['bodytext'];
			$tag = '<div id="discussion_entry">|<div class="de">';
			$ads = WikiHowTemplate::getAdUnitPlaceholder(5);
			$this->data['bodytext'] = preg_replace("@(($tag)(.|\n)*)($tag)@Um", '$1' .$ads  . '$2', $body, 1, $count);
			if ($count == 0)
				$this->data['bodytext'] = $body . $ads;
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
	$dpage = Title::makeTitle(NS_SPECIAL, "CommunityDashboard");
	$nav_tabs = array(
				'nav_home'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array($mainPageObj->getFullURL()), 'link' => $mainPageObj->getFullURL(), 'text' => wfMsg('navbar_home')),
				'nav_articles'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array("/Special:Categorylisting"), 'link' => "/Special:Categorylisting", 'text' => wfMsg('navbar_articles')),
				'nav_community'	=> array('status' => '', 'mouseevents' => '', 'possibleurls' => array($cptab->getFullURL(), $dpage->getFullURL()), 'link'=> $dpage->getFullURL(), 'text'=> wfMsg('navbar_community')),
				'nav_profile'	=> array('status' => '', 'mouseevents' => '',
						'possibleurls' => array($wgUser->getID() > 0 ? $wgUser->getUserPage()->getFullURL() : $lpage->getFullURL()),
						'link'=> $wgUser->getID() > 0 ? $wgUser->getUserPage()->getFullURL() : $lpage->getFullURL(),
						'text' => wfMsg('navbar_profile'))
			);
	$articles_page = true;
	foreach ($nav_tabs as $n=>$v) {
		if (in_array($wgTitle->getFullURL(), $v['possibleurls']) ){
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
		if (in_array($wgLanguageCode, array('en','de','es')))
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
		$wgTitle->getNamespace() == NS_MAIN &&
		$wgTitle->getFullText() != wfMsg('mainpage') &&
		$wgUser->getId() == 0;

// <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?= $head_element ?><head>
	<title><?= $title ?></title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="verify-v1" content="/Ur0RE4/QGQIq9F46KZyKIyL0ZnS96N5x1DwQJa7bR8=" />
	<meta name="msvalidate.01" content="CFD80128CAD3E726220D4C2420D539BE" />
	<meta name="y_key" content="1b3ab4fc6fba3ab3" />
<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>
	<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/skins/WikiHow/new.css,/extensions/wikihow/common/jquery-ui-themes/jquery-ui.css' . ($isLoggedIn ? ',/skins/WikiHow/loggedin.css' : '') . ($showSliderWidget ? ',/extensions/wikihow/slider/slider.css' : '') . '&') . WH_SITEREV ?>"; /*]]>*/</style>
	<? if ($isPrintable): ?>
		<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/skins/WikiHow/printable.css') . '?2' ?>";  /*]]>*/</style>
	<? endif; ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/skins/common/highlighter-0.6.js,/skins/common/wikibits.js,/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.1.custom.min.js' . ($showRCWidget ? ',/extensions/wikihow/rcwidget.js' : '') . ($showSpotlightRotate ? ',/skins/WikiHow/spotlightrotate.js' : '') . ',/skins/WikiHow/google_cse_search_box.js,/skins/common/mixpanel.js,/skins/WikiHow/gaWHTracker.js' . ($showFollowWidget ? ',/extensions/wikihow/FollowWidget.js' : '') . ($showSliderWidget ? ',/extensions/wikihow/slider/slider.js' : '') . '&') . WH_SITEREV ?>"></script>
	<? $this->html('headlinks') ?>
	<? self::displayOldIEFixes() ?>
	<? if (!$showSideBar) self::displayRemoveSidebarCSS(); ?>
	<link rel='canonical' href='<?=$wgTitle->getFullURL()?>'/>
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
</head>
<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload']) { ?>onload="<?php $this->text('body_onload') ?>"<?php } ?>
>
<div id="header">
    <div id="logo">
		<a href='<?=$mainPageObj->getFullURL();?>'>
		<img src="<?= wfGetPad('/skins/WikiHow/images/wikihow.png') ?>" id="wikiHow" alt="<?='wikiHow - '.wfMsg('main_title');?>" width="216" height="37"/></a><p><a href='<?=$mainPageObj->getFullURL();?>'><?=wfMsg('main_logo_title')?></a></p>
 <?php if ($wgLanguageCode != 'en' && $wgTitle->getArticleID() > 0 && $action == 'view' ) {
               echo "<img src='/imagecounter.gif?id=" . $wgTitle->getArticleID() . "' width='1' height='1' border='0'/>";
 } ?>
 	</div><!--end logo-->

	<div id="bubbles">
		<div id="login"><?=$login?> | <?=$helplink?> <?php if ($wgUser->getID() > 0) { echo "| $logoutlink"; }?> </div>
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

<div id="iphone_notice">
</div>


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
			echo wfMsg('Ad_setup');
		?>
        <div id="article">

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
	<div id="article_tabs">

		<a href="<? if ($wgTitle->isTalkPage()) echo $wgTitle->getSubjectPage()->getFullURL(); else echo $wgTitle->getFullURL(); ?>"
			id="tab_article" title="Article" <?php if (!MWNamespace::isTalk($wgTitle->getNamespace()) && $action != "edit" && $action != "history") echo 'class="on"'; ?> onmousedown="button_click(this);"><?php if ($wgTitle->getSubjectPage()->getNamespace() == NS_USER) echo "User"; else echo "Article"; ?></a>
     	<span id="gatEdit"><a href="<?=$wgTitle->escapeLocalURL($sk->editUrlOptions())?>" id="tab_edit" title="Edit" onmousedown="button_click(this);"
			 <?php if ($action == "edit") echo 'class="on"'; ?>
		>
		<? if ('en' == $wgLanguageCode) { ?><div class="tab_pencil edit_pencil"></div><? } ?> <?= wfMsg('edit') ?></a></span>
		<? if ($action =='view' && MWNamespace::isTalk($wgTitle->getNamespace())) {
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
        <span id="gatDiscussionTab"><a href="<? echo $talklink; ?>"  <?php if ($wgTitle->isTalkPage() && $action != "edit" && $action != "history") echo 'class="on"'; ?> id="tab_discuss" title="<?= $msg ?>" onmousedown="button_click(this);"><?= $msg ?></a></span>
        <a href="<?=$wgTitle->getLocalURL( 'action=history' ); ?>" id="tab_history"  <?php if ($action == "history") echo 'class="on"'; ?>  title="<?= wfMsg('history') ?>" onmousedown="button_click(this);"><?= wfMsg('history') ?></a>


	<? if ($wgUser->isSysop() && $wgTitle->userCan('delete')) { ?>
		 <a href="" id="tab_admin" title="Admin" onmouseover="button_swap(this);AdminTab(this,true);" onmouseout="button_unswap(this);AdminTab(this,true);" onmousedown="button_click(this);"><?=wfMsg('admin_admin')?> <img src="<?= wfGetPad('/skins/WikiHow/images/admin_arrow.gif') ?>" width="6" height="6" alt="" /></a>
                <ul id="AdminOptions" onmouseover="button_swap(this);AdminTab(this,false);" onmouseout="button_unswap(this);AdminTab(this,false);">
                    <li><a href="<?=$wgTitle->getLocalURL( 'action=protect' ); ?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?=wfMsg('admin_protect')?></a></li>
                    <li><a href="<?=SpecialPage::getTitleFor("Movepage", $wgTitle)->getFullURL(); ?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?=wfMsg('admin_move')?></a></li>
                    <li><a href="<?=$wgTitle->getLocalURL( 'action=delete' );?>" onmouseover="AdminCheck(this,true);" onmouseout="AdminCheck(this,false);"><?=wfMsg('admin_delete')?></a></li>

                </ul>
			<?=$tb_admin?>
	<? }
		else {
			echo $tb;
		} ?>
		<?=$fb?>
	   </div><!--end article_tabs-->
<? } // no article tabs for special pages ?>
	   <?php wfRunHooks( 'BeforeTabsLine', array( &$wgOut ) ); ?>
		    <div id="article_tabs_line"></div>
		<? } // Featured articls for main page mpFAs ?>



			<?= $profileBox ?>
			<div id='bodycontents'>
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
			<?= $search_results ?>
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
			$catlinks = $sk->getCategoryLinks($false);
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
    </div><!--end article_info-->
	<? 		}
		}
		if (($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_PROJECT || $wgTitle->getNamespace() == NS_CATEGORY) && $action == 'view' && !$isMainPage) {
	?>
<div id='article_tools_header'>
<h2 class="section_head"><?= wfMsg('article_tools') ?></h2>
</div> <!-- article_tools_header -->
	<div class="article_inner">
		<? if ($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_CATEGORY) { ?>
		<div id="share_icons">
		    <div><?=wfMsg('at_share')?></div>
		    <span id="gatSharingTwitter" ><a onclick="javascript:share_article('twitter');" id="share_twitter"></a></span>
		    <span id="gatSharingStumbleupon"> <a onclick="javascript:share_article('stumbleupon');" id="share_stumbleupon"></a></span>
		    <span id="gatSharingFacebook"> <a onclick="javascript:share_article('facebook');" id="share_facebook"></a></span>
		    <span id="gatSharingBlogger"> <a onclick="javascript:share_article('blogger');" id="share_blogger"></a></span>
		    <span id="gatSharingDigg"> <a onclick="javascript:share_article('digg');" id="share_digg"></a></span>
		    <span id="gatSharingGoogleBookmarks"> <a onclick="javascript:share_article('google');" id="share_google"></a></span>
		    <span id="gatSharingDelicious"> <a onclick="javascript:share_article('delicious');" id="share_delicious"></a></span>
			<?=$fb_share?>
			<?=$tb?>
		    <br class="clearall" />
		</div><!--end share_icons-->
		<? } ?>
	    <ul id="end_options">
	        <li id="endop_discuss"><a href="<?echo $talklink;?>" id="gatDiscussionFooter"><?=wfMsg('at_discuss')?></a></li>
	        <li id="endop_print"><a href="<?echo $wgTitle->getLocalUrl('printable=yes');?>" id="gatPrintView"><?echo wfMsg('print');?></a></li>
			<li id="endop_email"><a href="/Special:EmailLink?target=<?echo $wgTitle->getPrefixedURL();?>" id="gatSharingEmail"><?=wfMsg('at_email')?></a></li>
			<? if ($wgTitle->userIsWatching()) { ?>
	        	<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=unwatch');?>"><?=wfMsg('at_remove_watch')?></a></li>
	        <? } else { ?>
				<li id="endop_watch"><a href="<?echo $wgTitle->getLocalURL('action=watch');?>"><?=wfMsg('at_watch')?></a></li>
			<? } ?>
	        <li id="endop_edit"><a href="<?echo $wgTitle->getEditUrl();?>" id="gatEditFooter"><?echo wfMsg('edit');?></a></li>
			<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
	        	<li id="endop_fanmail"><a href="/Special:ThankAuthors?target=<?echo $wgTitle->getPrefixedURL();?>" id="gatThankAuthors"><?=wfMsg('at_fanmail')?></a></li>
			<? } ?>
	    </ul>


		<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
			<div id="embed_this"><span>+</span> <a href="/Special:Republish/<?= $wgTitle->getDBKey() ?>" id="gatSharingEmbedding" rel="nofollow"><?=wfMsg('at_embed')?></a></div>
		<? } ?>
		<?php
			$categories = $wgTitle->getTopLevelCategories();
			$found = false;
			foreach($categories as $cat){
				if($cat->getPartialURL() == 'Home-and-Garden'){
					$found = true;
					break;
				}
			}
			if($found || ($wgTitle->getNamespace() == NS_CATEGORY && $wgTitle->getPartialURL() == "Home-and-Garden"))
				$showDFP = true;
			else
				$showDFP = false;
		?>
		<?php if( $wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getNamespace() == NS_MAIN) {
			if(!$showDFP)
				echo WikiHowTemplate::getAdUnitPlaceholder(7);
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
        </div><!--end top_links-->
		<?php
			if($wgUser->getID() == 0 && !$isMainPage && $action != 'edit' && $wgTitle->getText() != 'Userlogin'){
				if($showDFP)
					echo WikiHowTemplate::getAdUnitPlaceholder(6);
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
			$likeDiv = <<<EOHTML
				<div id="fb_sidebar_shell$likeDivBottom">
					<div><img class="module_cap" alt="" src="http://pad1.whstatic.com/skins/WikiHow/images/fblike/LikeOffWhite_Top.png"></div>
					<div id="fb_sidebar">
						<span id ="fb_icon"><img src="http://pad1.whstatic.com/skins/WikiHow/images/fblike/facebook_icon.png"></span>
						<div id="follow_facebook"><span id="follow_facebook"><a href="http://www.wikihow.com/">Follow wikiHow</a></span> on facebook</div>
						<div id="fb_sidebar_content"></div>
					</div>
					<div><img class="module_cap" alt="" src="http://pad1.whstatic.com/skins/WikiHow/images/fblike/LikeOffWhite_Bottom.png"></div>
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

			if($wgUser->getID() == 0 && !$isMainPage && $action == 'view')
					echo WikiHowTemplate::getAdUnitPlaceholder(2, true);

	?>

         <!-- Sidebar Widgets -->
		<? foreach ( $sk->mSidebarWidgets as $sbWidget) { ?>
  	      <?= $sbWidget ?>
		<? } ?>
         <!-- END Sidebar Widgets -->

		<? if ($wgUser->getID() > 0) echo $navigation; ?>

		<? if ($related_vids != "") { ?>
        	<div class='sidebar_top'></div>
        	<div id="side_related_videos" class="sidebox">
			<?=$related_vids; ?>
        	</div><!--end side_related_videos-->
        	<div class='sidebar_bottom_fold'></div>
		<? } ?>

        <?php
        // GOOGLE SIDE BAR
        if ($wgUser->getID() > 0 && $wgTitle->getNamespace()== NS_SPECIAL && $wgTitle->getText()=="LSearch" && $wgLanguageCode=='en') {
		?>
        	<div class="sidebox_shell">
        	<div class='sidebar_top'></div>
        	<div id="side_ads_by_google" class="sidebox">
		<?
            require_once("$IP/extensions/wikihow/GoogleAPIResults.body.php");
            echo GoogleAPIResults::getSideBar();
		?>
        </div><!--end side_ads_by_google-->
        	<div class='sidebar_bottom_fold'></div>
        </div><!--end sidebox_shell-->
		<?  } ?>

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
            	<?= wfMsg('welcome', $wgUser->getName(), $wgUser->getUserPage()->getFullURL()); ?>
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
        <div id="side_recent_changes" class="sidebox">
        <?  FeaturedContributor::showWidget();  ?>
			<? if ($wgUser->getID() == 0) { ?>
        <p class="bottom_link">
           <a href="/Special:Userlogin" id="gatFCWidgetBottom" onclick='gatTrack("Browsing","Feat_contrib_cta","Feat_contrib_wgt");'><? echo wfMsg('fc_action') ?></a>
        </p>
			<? } ?>
        </div><!--end side_recent_changes-->
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
	<?php if(class_exists('IheartwikiHow') && !$isMainPage): ?>
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
			<?=wfMsgExt('site_footer_new', 'parse'); ?>
        </div><!--end footer_side-->

        <div id="footer_main">
		<?= $footer_search ?>

			<h3><?= wfMsg('explore_categories') ?></h3>

			<?= $sk->getCategoryList() ?>

	    	<div id="sub_footer">
				 <?= wfMsg('sub_footer_new', wfGetPad(), wfGetPad()) ?>
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
$trackUrl = str_replace("$wgServer$wgScriptPath", "", $trackUrl);
$trackUrl = str_replace("http://www.wikihow.com", "", $trackUrl);
$trackUrl .= '::';
$trackUrl .= "," . implode(",", $trackData) . ",";
?>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2375655-1']);
  _gaq.push(['_setDomainName', '.wikihow.com']);
  _gaq.push(['_trackPageview']);

  var clientData = clientData || '';
  _gaq.push(['da._setAccount', 'UA-21326566-1']);
  _gaq.push(['da._setDomainName', '.wikihow.com']);
  _gaq.push(['da._trackPageview', '<?=$trackUrl?>' + clientData]);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = "http://pad1.whstatic.com/skins/common/ga.js?<?=WH_SITEREV?>";
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
<script type="text/javascript">
if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
	jQuery(window).load(checkIphone);
} else {
	Event.observe(window, 'load', checkIphone);
}
</script>

	<div id='img-box'></div>
<?
if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
	echo CTALinks::getBlankCTA();
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
		$(window).load(function() {
			if ($('.twitter-share-button').length) {
				var node = document.createElement('script');
				node.type = 'text/javascript';
				node.src = 'http://platform.twitter.com/widgets.js';
				$('body').append(node);
			}
			if ($('.like_button').length) {
				$('.like_button').html('<?= trim($fb_iframe) ?>');
			}

			if ($('#fb_sidebar_content').length) {
				$('#fb_sidebar_content').html('<?= trim($fb_wikiHow_iframe) ?>');
			}
		});
	})(jQuery);
</script>

	<?= $wgOut->getScript() ?>
<?php $this->html('reporttime') ?>

</html>

	<?php
}


}


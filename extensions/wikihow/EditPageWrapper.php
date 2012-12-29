<?php

# Splitting edit page/HTML interface from Article...
# The actual database and text munging is still in Article,
# but it should get easier to call those from alternate
# interfaces.
require_once('WikiHow.php');
require_once('Request.php');
require_once('EditPage.php');

class EditPageWrapper extends EditPage {

	var $whow = null;

	function getCategoryOptions($defalt = "", $cats) {
		wfGetCategoryOptionsForm($default, $cats);
	}
	function getCategoryOptions2($default = "") {
		global $wgUser;

		// only do this for logged in users
		if ($wgUser->getID() <= 0) return "";

		$t = Title::makeTitle(NS_PROJECT,"Categories");
		$r = Revision::newFromTitle($t);
		if (!$r)
			return '';
		$cat_array = split("\n", $r->getText());
		$s = "";
		foreach($cat_array as $line) {
			$line = trim($line);
			if ($line == "" || strpos($line, "[[") === 0) continue;
			$top = false;
			if (strpos($line, "*") !== 0) continue;
			$line = substr($line, 1);
			if (strpos($line, "*") !== 0) $top = true;
			$val = trim(str_replace("*", "", $line));
			$display = str_replace("*", "&nbsp;&nbsp;&nbsp;&nbsp;", $line);
			$s .= "<OPTION " ;
			if ($top) $s .= " style='font-weight: bold;'";
			$s .= " VALUE=\"" . $val . "\">" . $display . "</OPTION>\n";
		}
		$s = str_replace("\"$default\"", "\"$default\" SELECTED", $s);
		return $s;
	}

	function EditPageWrapper( $article ) {
		$this->mArticle =& $article;
		global $wgTitle;
		$this->mTitle =& $wgTitle;
		$this->mGuided = true;
	}

	# Old version
	function edit()
	{
		global $wgRequest;
		$this->importFormData($wgRequest);
		EditPage::edit();
	}

	function importFormData( &$request ) {
		# These fields need to be checked for encoding.
		# Also remove trailing whitespace, but don't remove _initial_
		# whitespace from the text boxes. This may be significant formatting.
		EditPage::importFormData($request);

		// create the wikiHow wrapper object
		if( $request->wasPosted() ) {
			$whow = WikiHow::loadFromRequest($request);
			$whow->mIsNew = false;
			$this->whow = $whow;
			$this->textbox1 = $this->whow->formatWikiText();
		}


	}

	# Since there is only one text field on the edit form,
	# pressing <enter> will cause the form to be submitted, but
	# the submit button value won't appear in the query, so we
	# Fake it here before going back to edit().  This is kind of
	# ugly, but it helps some old URLs to still work.

	function submit2()
	{
		if( !$this->preview ) $this->save = true;
		$this->easy();
	}

	# Extend showEditForm. Make most of conflict handling, etc of Editpage::showEditForm
	# but use our own display
	function showEditForm( $formCallback=null ) {
		global $wgOut, $wgLanguageCode, $wgRequest, $wgTitle, $wgUser, $wgLang;

		$whow = null;


		// conflict resolution
		if (!$wgRequest->wasPosted()) {
			EditPage::showEditForm();
		}
		$wgOut->clearHTML();

		//echo $this->textbox1; exit;
		wfRunHooks( 'EditPage::showEditForm:initial', array( &$this ) ) ;

		// are we called with just action=edit and no title?
		$newArticle = false;
		if ( ($wgRequest->getVal( "title" ) == "" || $wgTitle->getArticleID() == 0)
				&& !$this->preview) {
			$newArticle = true;
		}

		$sk = $wgUser->getSkin();
		if(!$this->mTitle->getArticleID() && !$this->preview) { # new article
			$wgOut->addHTML(wfMsg("newarticletext"));
		}


		// do we have a new article? if so, format the title if it's English
		$wgRequest->getVal("new_article");
		if ($new_article && $wgLanguageCode == "en") {
			$title = $this->mTitle->getText();
			$old_title = $title;
			$title = $this->formatTitle($title);
			$titleObj = Title::newFromText($title);
			$this->mTitle = $titleObj;
			$this->mArticle = new Article($titleObj);
		}

/***this might not be needed anymore

		if ( "initial" == $formtype && $this->mArticle != null ) {
			// load last edit WikiHow::loadFromArticle actually sets the wrong time stamp
			// hack work around for now. holy shit.
			$this->mArticle->mUser = -1;
			$this->mArticle->loadLastEdit();

			$this->edittime = $this->mArticle->getTimestamp();
			$this->textbox1 = $this->mArticle->getContent( true, true );
			$this->summary = "";
			$this->proxyCheck();
		}
		**/

		$conflictWikiHow = null;
		$conflictTitle = false;
		if ( $this->isConflict ) {
			$s = wfMsg( "editconflict", $this->mTitle->getPrefixedText() );
			$wgOut->setPageTitle( $s );
			if ($new_article) {
				$wgOut->addHTML("<b><font color=red>".wfMsg('page-name-exists')."</b></font><br/><br/>");
				$conflictTitle = true;
			} else {
				$this->edittime = $this->mArticle->getTimestamp();
			    $wgOut->addHTML( wfMsg( "explainconflict" ) );
				// let the advanced editor handle the situation
				if ($this->isConflict)  {
					EditPage::showEditForm();
					return;
				}
			}

			$this->textbox2 = $this->textbox1;
			$conflictWikiHow = new WikiHow();
			$conflictWikiHow->loadFromText($this->textbox1);
			$this->textbox1 = $this->mArticle->getContent( true, true );
			$this->edittime = $this->mArticle->getTimestamp();
		} else {
			if ($this->mTitle->getArticleID() == 0)
				$s = wfMsg('creating',"\"" . wfMsg('howto',$this->mTitle->getPrefixedText()) . "\"");
			else
				$s = wfMsg('editing',"\"" . wfMsg('howto',$this->mTitle->getPrefixedText()) . "\"");
			if( $this->section != "" ) {
				if( $this->section == "new" ) {
					$s.=wfMsg("commentedit");
				} else {
					$s.=wfMsg("sectionedit");
				}
				if(!$this->preview) {
					$sectitle=preg_match("/^=+(.*?)=+/mi",
					$this->textbox1,
					$matches);
					if( !empty( $matches[1] ) ) {
						$this->summary = "/* ". trim($matches[1])." */ ";
					}
				}
			}
			$wgOut->setPageTitle( $s );
			if ( $this->oldid ) {
				$this->mArticle->setOldSubtitle($this->oldid);
				$wgOut->addHTML( wfMsg( "editingold" ) );
			}
		}


		if( wfReadOnly() ) {
			$wgOut->addHTML( "<strong>" .
			wfMsg( "readonlywarning" ) .
			"</strong>" );
		} else if ( $isCssJsSubpage and "preview" != $formtype) {
			$wgOut->addHTML( wfMsg( "usercssjsyoucanpreview" ));
		}

		if( !$newArticle && $this->mTitle->isProtected( 'edit' ) ) {
			if( $this->mTitle->isSemiProtected() ) {
				$notice = wfMsg( 'semiprotectedpagewarning' );
				if( wfEmptyMsg( 'semiprotectedpagewarning', $notice ) || $notice == '-' ) {
					$notice = '';
				}
			} else {
				$notice = wfMsg( 'protectedpagewarning' );
			}
			$wgOut->addHTML( "<div class='article_inner'>\n " );
			$wgOut->addWikiText( $notice );
			$wgOut->addHTML( "</div>\n" );
		}



		$q = "action=submit2&override=yes";
		#if ( "no" == $redirect ) { $q .= "&redirect=no"; }
		$action = $this->mTitle->escapeLocalURL( $q );
		if ($newArticle) {
			$action = str_replace("&title=Main-Page", "", $action);
		}

		$summary = wfMsg( "summary" );
		$subject = wfMsg("subject");
		$minor = wfMsg( "minoredit" );
		$watchthis = wfMsg ("watchthis");
		$save = wfMsg( "savearticle" );
		$prev = wfMsg( "showpreview" );

		$cancel = $sk->makeKnownLink( $this->mTitle->getPrefixedText(),
		  wfMsg( "cancel" ) );
		$edithelpurl = Skin::makeInternalOrExternalUrl( wfMsgForContent( 'edithelppage' ));
		$edithelp = '<a target="helpwindow" href="'.$edithelpurl.'">'.
			htmlspecialchars( wfMsg( 'edithelp' ) ).'</a> '.
			htmlspecialchars( wfMsg( 'newwindow' ) );
		$copywarn = wfMsg( "copyrightwarning", $sk->makeKnownLink(
		  wfMsg( "copyrightpage" ) ) );


		$minoredithtml = '';

		if ( $wgUser->isAllowed('minoredit') ) {
			$minoredithtml =
				"<input tabindex='11' type='checkbox' value='1' name='wpMinoredit'".($this->minoredit?" checked='checked'":"").
				" accesskey='".wfMsg('accesskey-minoredit')."' id='wpMinoredit' />\n".
				"<label for='wpMinoredit' title='".wfMsg('tooltip-minoredit')."'>{$minor}</label>\n";
		}

		$watchhtml = '';

		if ( $wgUser->isLoggedIn() ) {
			$watchhtml = "<input tabindex='12' type='checkbox' name='wpWatchthis'".
				($this->watchthis?" checked='checked'":"").
				" accesskey=\"".htmlspecialchars(wfMsg('accesskey-watch'))."\" id='wpWatchthis'  />\n".
				"<label for='wpWatchthis' title=\"" .
					htmlspecialchars(wfMsg('tooltip-watch'))."\">{$watchthis}</label>\n";
		}

		$checkboxhtml = $minoredithtml . $watchhtml;

		$tabindex = 14;
		$buttons = $this->getEditButtons( $tabindex );

		$footerbuttons = "";
		if ($wgUser->getOption('hidepersistantsavebar',0) == 0) {
			$footerbuttons .= "<span id='gatPSBSave'>{$buttons['save']}</span>";
			$footerbuttons .= "<span id='gatPSBPreview'>{$buttons['preview']}</span>";
		}
		$buttons['save'] = "<span id='gatGuidedSave'>{$buttons['save']}</span>";
		$buttons['preview'] = "<span id='gatGuidedPreview'>{$buttons['preview']}</span>";

		$buttonshtml = implode( $buttons, "\n" );

		# if this is a comment, show a subject line at the top, which is also the edit summary.
		# Otherwise, show a summary field at the bottom
		$summarytext = htmlspecialchars( $wgLang->recodeForEdit( $this->summary ) ); # FIXME
		$editsummary1 = "";
		if ($wgRequest->getVal('suggestion')) {
			$summarytext .= ($summarytext == "" ? "" : ", ") .  wfMsg('suggestion_edit_summary');
		}
		if( $this->section == "new" ) {
			$commentsubject="{$subject}: <input tabindex='1' type='text' value=\"$summarytext\" name=\"wpSummary\" id='wpSummary' maxlength='200' size='60' />";
			$editsummary = "";
		} else {
			$commentsubject = "";
			if ($wgTitle->getArticleID() == 0 && $wgTitle->getNamespace() == NS_MAIN && $summarytext == "")
				$summarytext = wfMsg('creating_new_article');
			$editsummary="<input tabindex='10' type='text' value=\"$summarytext\" name=\"wpSummary\" id='wpSummary' maxlength='200' size='60' /><br />";
			$editsummary1="<input tabindex='10' type='text' value=\"$summarytext\" name=\"wpSummary1\" id='wpSummary1' maxlength='200' size='60' /><br />";
		}

		// create the wikiHow
		//echo "textbox 1 " . $this->textbox1;
		$whow = new WikiHow();
		if ($conflictWikiHow == null) {
			if ($this->textbox1 != "") {
				$whow->loadFromText($this->textbox1);
			} else {
				$whow->loadFromArticle($this->mArticle);
			}
		} else {
			$whow = $conflictWikiHow;
		}

	//print __FILE__ . " " . __LINE__;

//********** SETTING UP THE FORM
//
//
//
//
		$confirm = "window.onbeforeunload = confirmExit;";
		if ($wgUser->getOption('disablewarning') == '1') {
			$confirm = "";
		}
		$wgOut->addHTML("<script language=\"JavaScript\">
				var isGuided = true;
				var needToConfirm = true;
				var checkMinLength = true;
				{$confirm}
				function confirmExit() {
					if (needToConfirm)
						return \"".wfMsg('all-changes-lost')."\";
				}
				function addrows (element) {
					if (element.rows < 32)  {
						element.rows += 4;
					}
				}
				function removerows (element) {
					if (element.rows > 4)  {
						element.rows -= 4;
					} else {
						element.rows = 4;
					}
				}
			</script>
			<script type=\"text/javascript\" src=\"{$wgScriptPath}/skins/common/clientscript.js\"></script>
			<script type=\"text/javascript\" src=\"{$wgScriptPath}/skins/common/ac.js\"></script>
			<script type='text/javascript' src='/extensions/wikihow/importvideo.js'> </script>
			<!--
			function sf(){document.editform.title.focus}
			function rwt(el,ct,cd,sg){el.href=\"/url?sa=t&ct=\"+escape(ct)+\"&cd=\"+escape(cd)+\"&url=\"+escape(el.href).replace(/\+/g,\"%2B\")+\"&ei=5E36Qo7iBsXAiwH-xsinAw\"+sg;el.onmousedown=\"\";return true;}
	</script>
			// -->

		");

		if( !$this->preview ) {
			# Don't select the edit box on preview; this interferes with seeing what's going on.
			$wgOut->setOnloadHandler( "document.editform.title.focus(); load_cats();" );
		}
		$title = "";
		//$wgOut->setOnloadHandler( "' onbeforeunload='return confirm(\"Are you sure you want to navigate away from this page? All changes will be lost!\");" );

		$suggested_title = "";
		if (isset($_GET["requested"])) {
			$t = Title::makeTitle(NS_MAIN, $_GET["requested"] );
			$suggested_title = $t->getText();
		}


		if ($wgRequest->getVal('title',null) == null || $conflictTitle || $suggested_title != "") {
			$title = "<div id='title'><h3>".wfMsg('title')."</h3><br/>" . wfMsg('howto','')." &nbsp;&nbsp;&nbsp;
			<input autocomplete=\"off\" size=60 type=\"text\" name=\"title\" id=category tabindex=\"1\" value=\"$suggested_title\"></div>";
		}


		$steps = htmlspecialchars( $wgLang->recodeForEdit( $whow->getSteps(true) ) );
		$video = htmlspecialchars( $wgLang->recodeForEdit( $whow->getSection('video') ) );
		$tips = htmlspecialchars( $wgLang->recodeForEdit( $whow->getTips() ) );
		$warns = htmlspecialchars( $wgLang->recodeForEdit( $whow->getWarnings() ) );

		$related_text = htmlspecialchars( $wgLang->recodeForEdit( $whow->getSection(wfMsg('relatedwikihows')) ) );

		$summary = htmlspecialchars( $wgLang->recodeForEdit($whow->getSummary()) );

		if ($newArticle || $whow->mIsNew) {
			if ($steps == "") $steps = "#  ";
			if ($tips == "") $tips = "*  ";
			if ($warns == "") $warns = "*  ";
		}

		$cat = $whow->getCategoryString();

		$advanced = "";

		$cat_array = explode("|", $whow->getCategoryString());
		$i = 0;
		$cat_string = "";
		foreach ($cat_array as $cat) {
			if ($cat == "")
				continue;
			if ($i != 0)
				$cat_string .= "," . $cat;
			else
				$cat_string = $cat;
			$i++;
		}
		$removeButton = "";
		$cat_advisory = "";
		if ($cat_string != "") {
			$removeButton = "<input type=\"button\" name=\"change_cats\" onclick=\"removeCategories();\" value=\"".wfMsg('remove-categories')."\">";
		} else {
			$cat_advisory = wfMsg('categorization-optional');
		}

		//$cat_string = str_replace("|", ", ", $whow->getCategoryString());
		//$cat_string = implode(", ", $raa);
		if (!$newArticle && !$whow->mIsNew && !$conflictTitle) {
			$oldparameters = "";
			if ($wgRequest->getVal("oldid") != "") {
				$oldparameters = "&oldid=" . $wgRequest->getVal("oldid");
			}
			if (!$this->preview)
			 $advanced = "<a class='button white_button_150' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'  href='{$wgScript}?title=" . $wgTitle->getPrefixedURL() . "&action=edit&advanced=true$oldparameters'>".wfMsg('advanced-editing')."</a>";
		} else if ($newArticle && $wgRequest->getVal('title', null) != null) {
			$t = Title::newFromText("CreatePage", NS_SPECIAL);
			 //$advanced = str_replace("href=", "class='guided-button' href=", $sk->makeLinkObj($t, wfMsg('advanced-editing'))) . " |";
			 //$advanced = "<a href='{$wgScript}?title=" . $wgTitle->getPrefixedURL() . "&action=edit&advanced=true$oldparameters';\">".wfMsg('advanced-editing')."</a>";
			 $advanced = "<a class='button white_button_150' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'  href='{$wgScript}?title=" . $wgTitle->getPrefixedURL() . "&action=edit&advanced=true$oldparameters'>".wfMsg('advanced-editing')."</a>";
		}

		// MODIFIED FOR POPUP
		$categoryHTML = "";
//		if ($wgUser->getID() > 0 &&( $wgLanguageCode=='en' || $wgLanguageCode== 'fr' || $wgLanguageCode=='es' || $wgLanguageCode == 'nl')) {
//			$categoryHTML = "        <div>
 //           <h3>".wfMsg('categories')."<div class='subheader'>".wfMsg('categorizeyourarticle')." <a href=\"{$wgScriptPath}/Writer%27s-Guide?section=2#" . wfMsg('more-info-categorization') . "\" target=\"new\">" . wfMsg('moreinfo') . "</a></font></span></h3>
//								" . Categoryhelper::getCategoryOptionsForm($cat_string, $whow->mCategories) . "
//				</div>";
//		}

		if ($wgUser->getID() > 0 &&( $wgLanguageCode=='en' || $wgLanguageCode== 'fr' || $wgLanguageCode=='es' || $wgLanguageCode == 'nl' || $wgLanguageCode== 'he' )) {
			$ctitle = $this->mTitle->getText();
			$categoryHTML = "
			   <style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/categoriespopup.css'; /*]]>*/</style>
				<div id='categories'>
					<h3>".wfMsg('add-optional-categories') . "</h3>
					<div class='article_inner'>
						<strong><a href=\"#\" onclick=\"document.getElementById('modalPage').style.display = 'block'; window.frames['dlogBody'].initToCategory(); \">[".wfMsg('editcategory')."]</a></strong>&nbsp;&nbsp;<a href=\"{$wgScriptPath}/Writer%27s-Guide?section=2#" . wfMsg('more-info-categorization') . "\" target=\"new\">" . wfMsg('moreinfo') ."</a>"
						. Categoryhelper::getCategoryOptionsForm2($cat_string, $whow->mCategories)
				.
					"</div>
			</div>";

		}


		$requested = "";
		if (isset($_GET['requested'])) {
			$requested = $_GET['requested'];
		}

		$related_vis = "hide";
		$related_checked = "";
		$relatedHTML = "";
		if ($whow->getSection(wfMsg('relatedwikihows')) != "") {
			$related_vis = "show";
			$relatedHTML = $whow->getSection(wfMsg('relatedwikihows'));
			$relatedHTML = str_replace("*", "", $relatedHTML);
			$relatedHTML = str_replace("[[", "", $relatedHTML);
			$relatedHTML = str_replace("]]", "", $relatedHTML);
			$lines = split("\n", $relatedHTML);
			$relatedHTML = "";
			foreach ($lines as $line) {
				$xx = strpos($line, "|");
				if ($xx !== false)
					$line = substr($line, 0, $xx);
				$line = trim(urldecode($line));
				if ($line == "") continue;
				$relatedHTML .= "<OPTION VALUE=\"" . htmlspecialchars($line) . "\">$line</OPTION>\n";
			}
			$related_checked = " CHECKED ";
		}

		$vidpreview_vis = "hide";
		$vidbtn_vis = "show";
		$vidpreview = "<img src='/extensions/wikihow/rotate.gif'/>";
		if ($whow->getSection(wfMsg('video')) != "") {
			$vidpreview_vis = "show";
			$vidbtn_vis = "hide";
			try {
				#$vt = Title::makeTitle(NS_VIDEO, $this->mTitle->getText());
				#$r = Revision::newFromTitle($vt);
				$vidtext = $whow->getSection(wfMsg('video'));
				$vidpreview = $wgOut->parse($vidtext);
			} catch (Exception $e) {
				$vidpreview = "Sorry, preview is currently not available.";
			}
		}  else {
			$vidpreview = wfMsg('video_novideoyet');
		}
		$video_disabled = "";
		$vid_alt = "";
		$video_msg = "";
		$video_button ="<a id='gatVideoImportEdit' type='button' onclick=\"changeVideo('". urlencode($wgTitle->getDBKey()) . "'); $('winpop_outer').style.position = 'absolute'; window.scroll(0,0); return false;\" href='#' id='show_preview_button' class='button white_button_150' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' >" . wfMsg('video_change') . "</a>";
		if ($wgUser->getID() == 0) {
			$video_disabled = "disabled";
			$video_alt = "<input type='hidden' name='video' value=\"" . htmlspecialchars($video) . "\"/>";
			$video_msg = wfMsg('video_loggedin');
			$video_button = "";
		}


		$things_vis = "hide";
		$things = "*  ";
		$things_checked = "";
		$tyn = $whow->getSection(wfMsg("thingsyoullneed"));
		if ($tyn != '') {
			$things_vis = "show";
			$things = $tyn;
			$things_checked = " CHECKED ";
		}
		$ingredients_vis = "hide";
		$section = $whow->getSection(wfMsg("ingredients"));
		$ingredients_checked = "";
		if ($section != '') {
			$ingredients_vis = "show";
			$ingredients = $section;
			$ingredients_checked = " CHECKED ";
		}

		$sources_vis = "hide";
		$sources = "*  ";
		$sources_checked = "";
		$sources = $whow->getSection(wfMsg("sources"));
		$sources = str_replace('<div class="references-small"><references/></div>', '', $sources);
		$sources = str_replace('{{reflist}}', '', $sources);
		if ($sources != "") {
			$sources_vis = "show";
			$sources_checked = " CHECKED ";
		}
		$new_field = "";
		if ($newArticle || $new_article) {
			$new_field="<input type=hidden name=new_article value=true>";
		}

		$lang_links = htmlspecialchars($whow->getLangLinks());
		$vt = Title::makeTitle(NS_VIDEO, $this->mTitle->getText());
		$vp = SpecialPage::getTitleFor("Previewvideo", $vt->getFullText());

		$imgBtn = "";
		$relBtn = "";
		$relHTML = "";
		$newArticleWarn = '<script type="text/javascript" language="javascript" src="/extensions/wikihow/winpop.js"></script>';
		$popup = Title::newFromText("UploadPopup", NS_SPECIAL);
			//$popup2 = Title::newFromText("ImportFreeImages", NS_SPECIAL);
		//$imgBtn = "<a class='button white_button_150 " . ($wgUser->getID() == 0 ? " disabled" : "") . "' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' id='gatImagePopup' type='button' href=\"javascript:imagePopup ('" . $popup->getFullURL() . "', document.editform.summary);\"" . ($wgUser->getID() == 0 ? " disabled=\"disabled\" " : "") . ">".wfMsg('add-photo')."</a>";

		$relBtn = PopBox::getGuidedEditorButton();
		$relHTML = PopBox::getPopBoxJSGuided() . PopBox::getPopBoxDiv() . PopBox::getPopBoxCSS();

		if ( $this->formtype == 'preview' ) {
			$previewOutput = $this->getPreviewText();
		}

		if ( $wgUser->isLoggedIn() )
			$token = htmlspecialchars( $wgUser->editToken() );
		else
			$token = EDIT_TOKEN_SUFFIX;

		if ( $wgUser->getOption( 'previewontop' ) ) {
			if ( 'preview' == $this->formtype ) {
				$this->showPreview( $previewOutput );
			} else {
				$wgOut->addHTML( '<div id="wikiPreview"></div>' );
			}

		   if ( 'diff' == $this->formtype ) {
			$this->showDiff();
			}
		}

		$undo = '';
		if ($wgRequest->getVal('undo', null) != null) {
			$undo_id = $wgRequest->getVal('undo', null);
			$undo =  "\n<input type='hidden' value=\"$undo_id\" name=\"wpUndoEdit\" />\n";
		}
		$wgOut->addHTML( Easyimageupload::getUploadBoxJS() );
		$wgOut->addHTML( "
	$relHTML
	<div class='editpage_buttons'>
		{$advanced} {$imgBtn} {$relBtn}
	</div>
	{$newArticleWarn}

<div id='editpage'>
<form id=\"editform\" name=\"editform\" method=\"post\" action=\"$action\"
enctype=\"application/x-www-form-urlencoded\"  onSubmit=\"return checkForm();\">		");

		if( is_callable( $formCallback ) ) {
			call_user_func_array( $formCallback, array( &$wgOut ) );
		}

		$hidden_cats = "";
		if (!$wgUser->isLoggedIn())
			$hidden_cats = "<input type=\"hidden\" name=\"categories22\" value=\"{$cat_string}\">";

		$token1 = md5($wgUser->getName() . $this->mTitle->getArticleID() . time());
		wfTrackEditToken($wgUser, $token1, $this->mTitle, $this instanceof EditPageWrapper);

		$wgOut->addHTML ("
		{$new_field}
		{$hidden_cats}
		<input type='hidden' value=\"{$this->starttime}\" name=\"wpStarttime\" />\n
		<input type=\"hidden\" name=\"requested\" value=\"{$requested}\">
		<input type=\"hidden\" name=\"langlinks\" value=\"{$lang_links}\">
		<input type='hidden' value=\"{$this->edittime}\" name=\"wpEdittime\" />\n

		{$commentsubject}
		{$title}
		<br clear='all'/>

<script language='javascript' src='extensions/wikihow/expandtextarea.js'></script>
<script language='javascript'>
	var vp_URL = '{$vp->getFullURL()}';
</script>
<script language='javascript' src='extensions/wikihow/previewvideo.js'></script>
<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/expandtextarea.css'; /*]]>*/</style>
<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/editpagewrapper.css?3'; /*]]>*/</style>
<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/winpop.css'; /*]]>*/</style>
<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/importvideo.css'; /*]]>*/</style>
	<div id='introduction'>
		<h2>" . wfMsg('introduction') . "
			<div class='subheader'>" . wfMsg('summaryinfo') . "
				<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('introduction-url')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<textarea rows='4' cols='100' name='summary' id='summary' tabindex=\"2\" wrap=virtual>{$summary}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"intro\"); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-introduction')."</a><br />

	</div>


	<div id='ingredients' class='{$ingredients_vis}'>
		<h2>" . wfMsg('ingredients') . "
			<div class='subheader'>" . wfMsg('ingredients_tooltip') .
				"<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('ingredients')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<textarea name='ingredients' rows='4' cols='100' onKeyUp=\"addStars(event, document.editform.ingredients);\" tabindex='3' id='ingredients_text'>{$ingredients}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"ingredients\"); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-ingredients')."</a><br />
	</div>

	<div id='steps'>
		<h2>" . wfMsg('steps') . "
			<div class='subheader'>" . wfMsg('stepsinfo') . "
				<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('steps')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<div class='taButtons'>
				<p><a onclick='javascript:expandtext(\"steps_text\");' class='button expand_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
				<p><a onclick='javascript:compresstext(\"steps_text\");' class='button contract_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
			</div>
			<textarea name='steps' rows='{$wgRequest->getVal('txtarea_steps_text', 12)}' cols='100' wrap='virtual' onKeyUp=\"addNumToSteps(event);\" tabindex='4' id='steps_text'>{$steps}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"steps\", 0); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-steps')."</a><br />

	</div>

	<div id='video'>
		<h2>" . wfMsg('video') . "
			<div class='subheader'>" . wfMsg('videoinfo') . "
				<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('video')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>{$video_alt}
			<input name='video{$video_disabled}' size='60' id='video_text' style='float:left;' value=\"{$video}\" {$video_disabled}/>{$video_button}
			<a href='javascript:showHideVideoPreview();' id='show_preview_button' class='button white_button_150 {$vidbtn_vis}' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'>" . wfMsg('show_preview') . "</a>
			{$video_msg}
		</div>
	</div>
	<div id='viewpreview' class='{$vidpreview_vis}' style='text-align: center; margin-top: 5px;'>
		<center><a onclick='showHideVideoPreview();'>Hide Preview</a></center><br/>
		<div id='viewpreview_innards'>{$vidpreview}</div>
	</div>

	<div id='tips'>
		<h2>" . wfMsg('tips') . "
			<div class='subheader'>" . wfMsg('listhints') . "
				<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('tips')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<div class='taButtons'>
				<p><a onclick='javascript:expandtext(\"tips_text\");' class='button expand_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
				<p><a onclick='javascript:compresstext(\"tips_text\");' class='button contract_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
			</div>
			<textarea name='tips' rows='{$wgRequest->getVal('txtarea_tips_text', 12)}' cols='100' wrap='virtual' onKeyUp='addStars(event, document.editform.tips);' tabindex='5' id='tips_text'>{$tips}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"tips\"); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-tips')."</a><br />

	</div>

	<div id='warnings'>
		<h2>" . wfMsg('warnings') . "
			<div class='subheader'>". wfMsg('optionallist') . "<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=3#".wfMsg('warnings')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<div class='taButtons'>
				<p><a onclick='javascript:expandtext(\"warnings_text\");' class='button expand_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
				<p><a onclick='javascript:compresstext(\"warnings_text\");' class='button contract_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'></a></p>
			</div>
			<textarea name='warnings' rows='{$wgRequest->getVal('txtarea_warnings_text', 4)}' cols='100' wrap='virtual' onKeyUp='addStars(event, document.editform.warnings);' id='warnings_text' tabindex=\"6\" id='warnings_text'>{$warns}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"warnings\"); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-warnings')."</a><br />
	</div>

	<div id='thingsyoullneed' class='{$things_vis}'>
		<h2>" . wfMsg('thingsyoullneed') ."
			<div class='subheader'>". wfMsg('items') . "<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=4#" . wfMsg('thingsyoullneed') . "\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<textarea name='thingsyoullneed' rows='4' cols='65' wrap='virtual' onKeyUp='addStars(event, document.editform.thingsyoullneed);' tabindex='7' id='thingsyoullneed_text'>{$things}</textarea>
		</div>
		<a href='#' class='button white_button_200 add_image_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' onclick='easyImageUpload.doEIUModal(\"thingsyoullneed\"); return false;'><img src='/skins/WikiHow/images/upload_image.png'/>".wfMsg('eiu-add-image-to-thingsyoullneed')."</a><br />

	</div>

	<div id='relatedwikihows' class='{$related_vis}'>
		<h2>" . wfMsg('relatedarticlestext') . "
			<div class='subheader'>" . wfMsg('relatedlist') . "<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=5#".wfMsg('related-wikihows-url')."\" target=\"new\">" . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<div id='related_buttons'>
				<a href='#'  class='button white_button_100' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' onclick='moveRelated(true);return false;' >Move Up</a>
				<a href='#' class='button white_button_100' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' onclick='moveRelated(false);return false;'>Move Down</a>
				<a href='#' class='button white_button_100' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' onclick='removeRelated(); return false;'>Remove</a>
			</div>
			<input type=hidden value=\"\" name=\"related_list\">
			<select size='4' name='related' id='related_select' ondblclick='viewRelated();'>
				{$relatedHTML}
			</select>
			<br />
			<br />
			<br class='clearall'/>
			<div style='float: left;'>
				<b>" . wfMsg('addtitle') . "</b>
				<input autocomplete=\"off\" maxLength='256' size='60%' name='q' value='' onKeyPress=\"return keyxxx(event);\" tabindex='8'>
			</div>
			<a href='#' id='add_button' class='button white_button' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' onclick='add_related();return false;'>Add</a>
			<br class='clearall'/>
		</div>
	</div>

<script language=\"JavaScript\">
	var js_enabled = document.getElementById('related');
		 if (js_enabled != null) {
				 js_enabled.className = 'display';
			}
	</script>
	<noscript>
		<input type='hidden' name='no_js' value='true'>
		<div id='related'>
			<textarea name='related_no_js' rows='4' cols='65' wrap='virtual' onKeyUp='addStars(event, document.editform.related_no_js);' id='related_no_js' tabindex='8'>{$related_text}</textarea>
		</div>
	</noscript>

	<div id='sources' class='$sources_vis'>
		<h2>" . wfMsg('sources') . "
			<div class='subheader'>" . wfMsg('linkstosites') . "<a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('sources-links-url')."\" target=\"new\"> " . wfMsg('moreinfo') . "</a>
			</div>
		</h2>
		<div class='article_inner'>
			<textarea name='sources' rows='3' cols='100' wrap='virtual' onKeyUp='addStars(event, document.editform.sources);' id='sources' tabindex='9'>{$sources}</textarea>
		</div>
	</div>

	{$categoryHTML}

	<div id='optional_sections'>
		<h3>" . wfMsg('optionalsections') . "</h3>
		<ul>
			<li><input type='checkbox' id='thingsyoullneed_checkbox' name='thingsyoullneed_checkbox' onclick='showhiderow(\"thingsyoullneed\", \"thingsyoullneed_checkbox\");' {$things_checked} /> " . wfMsg('thingsyoullneed') . "</li>
			<li><input type='checkbox' id='related_checkbox' name='related_checkbox' onclick='showhiderow(\"relatedwikihows\", \"related_checkbox\");' {$related_checked} > " . wfMsg('relatedwikihows') . " </li>
			<li><input type='checkbox' id='sources_checkbox' name='sources_checkbox' onclick='showhiderow(\"sources\", \"sources_checkbox\");' {$sources_checked} > " . wfMsg('sources') . "</li>
			<li><input type='checkbox' id='ingredients_checkbox' name='ingredients_checkbox' onclick='showhiderow(\"ingredients\", \"ingredients_checkbox\");' {$ingredients_checked} > " . wfMsg('ingredients_checkbox') . "</li>
		</ul>
	</div>

	<div class='article_inner'>
		<h3>" . wfMsg('editdetails') . "</h3><span class='subheader'>" . wfMsg('summaryedit') . " <a href=\"{$wgScriptPath}/".wfMsg('writers-guide-url')."?section=2#".wfMsg('summary')."\" target=\"new\"> " . wfMsg('moreinfo') . "</a></span>
		{$editsummary}
		<br/>$checkboxhtml
	</div>

{$undo}
<input type='hidden' value=\"$token\" name=\"wpEditToken\" />
<input type='hidden' value=\"$token1\" name=\"wpEditTokenTrack\" />
<div class='editButtons'>
{$buttonshtml}

<a href=\"javascript:history.back()\" id=\"wpCancel\">".wfMsg('cancel')."</a></div>
<span class='editHelp'><img src='/skins/WikiHow/images/icon_help.jpg' /> {$edithelp}</span>
<br /><br /><div id=\"editpage-copywarn\">".wfMsg('copyrightwarning')."</div>
<input type='hidden' value=\"" . htmlspecialchars( $this->section ) . "\" name=\"wpSection\" />
<input type='hidden' value=\"{$this->edittime}\" name=\"wpEdittime\" />\n" );

		if ( $this->isConflict ) {
			require_once( "DifferenceEngine.php" );
			$wgOut->addHTML( "<h2>" . wfMsg( "yourdiff" ) . "</h2>\n" );
				DifferenceEngine::showDiff( $this->textbox2, $this->textbox1,
			  wfMsg( "yourtext" ), wfMsg( "storedversion" ) );
		}

		if ($wgUser->getOption('hidepersistantsavebar',0) == 0) {
			$wgOut->addHTML(" <div id='edit_page_footer'>
				<table class='edit_footer'><tr><td class='summary'>
				" . wfMsg('editsummary') . ": &nbsp; {$editsummary1}</td>
				<td class='buttons'>{$footerbuttons}</td></tr></table>
				</div> ");
		}

		$wgOut->addHTML( "</form></div>\n" );

		if ( !$wgUser->getOption( 'previewontop' ) ) {
			if ( $this->formtype == 'preview') {
				$this->showPreview( $previewOutput );
			} else {
				$wgOut->addHTML( '<div id="wikiPreview"></div>' );
			}

			if ( $this->formtype == 'diff') {
				$wgOut->addHTML( $this->getDiff() );
			}
		}

		if ($wgUser->getID() > 0 &&( $wgLanguageCode=='en' || $wgLanguageCode== 'fr' || $wgLanguageCode=='es' || $wgLanguageCode == 'nl' || $wgLanguageCode== 'he' )) {
		$wgOut->addHTML( "
			<div id=\"modalPage\">
			  <div class=\"modalBackground\" id=\"modalBackground\"></div>
			   <div class=\"modalContainer\" id=\"modalContainer\">
			    <div class=\"modalTitle\"><a onclick=\"document.getElementById('modalPage').style.display = 'none';\">X</a></div>
			    <div class=\"modalBody\">".$this->getHTMLForDlog($ctitle)."
			    </div>
			   </div>
			 </div>

\n");
		}

	}



	/**
    *
	 **/
	function getHTMLForDlog($ctitle) {
		$display = "";

		$display .= "
<script type=\"text/javascript\">
if (screen.height < 701) {
	document.getElementById(\"modalContainer\").style.top = \"1%\";
}
</script>

			";
if ($ctitle != "") {
	if (strlen($ctitle) < 20) {
		$display .= "<strong><ABBR title=\"".$ctitle."\">".wfMsg('Categorypopup_title')." ".$ctitle."</ABBR> </strong><br /><br />\n";
	} else {
		$display .=  "<strong><ABBR title=\"".$ctitle."\">".wfMsg('Categorypopup_title')." ".substr($ctitle,0,20)." ...</ABBR></strong><br /><br />\n";
	}
}

	$display .= "
<iframe id=\"dlogBody\" name=\"dlogBody\" src=\"/Special:Categoryhelper?type=categorypopup\"  frameborder=\"0\" vspace=\"0\" hspace=\"0\" marginwidth=\"0\" marginheight=\"0\" width=\"470\" height=\"400\" scrolling=\"no\" stype=\"overflow:visible\"></iframe>
		";

		return $display;
	}

	function formatTitle($title) {
		// cut off extra ?'s or whatever
		while (preg_match("/[[:punct:]]$/u", $title)
				&& !preg_match("/[\")]$/u", $title) && strlen($title) > 2) {
			$title = substr($title, 0, strlen($title) - 1);
		}

		// check for high ascii
		for ($i = 0; $i < strlen($title); $i++) {
			if (ord(substr($title, $i, 1))  > 128)
				return $title;
		}

		$upper = 
			Array("Of ","A ","The ","And ","An ", "Or ", "Nor ","But ","If ",
				"Then ","Else ","When ","Up ","At ","From ","By ","On ",
				"Off ","For ","In ","Out ","Over ","To ");

		$lower = 
			Array("of ","a ","the ","and ","an ","or ","nor ","but ","if ",
				"then ","else ","when ","up ","at ","from ","by ","on ","off ",
				"for ","in ","out ","over ","to ");
		$title = strtolower( trim($title) );
		$pos = strpos($title, "to ");
		if ($pos !== false && $pos === 0)
			$title = substr($title, $pos + 3);
		$pos = strpos($title, "how to");
		if ($pos !== false && $pos === 0)
			$title = substr($title, $pos + 6);

		//$title = ucwords(strtolower(" " . $title . " "));
		$title = preg_replace('/([^a-z\']|^)([a-z])/e', '"$1".strtoupper("$2")',
                       strtolower($title));
		$title = trim( str_replace($upper, $lower, $title));
		return $title;
	}

	function getEditButtons ($tabindex) {
		global $wgLivePreview, $wgUser;

		$buttons = array();

		$temp = array(
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMsg('savearticle'),
			'accesskey' => wfMsg('accesskey-save'),
			'title'     => wfMsg( 'tooltip-save' ).' ['.wfMsg( 'accesskey-save' ).']',
			//XXCHANGED
			'onclick'   => 'needToConfirm = false',
			'class'     => 'button button100 submit_button',
			'onmouseout' => 'button_unswap(this);',
			'onmouseover' => 'button_swap(this);',
			'style'     => 'float: left; background-position: 0pt 0pt; ',
		);
		$buttons['save'] = wfElement('input', $temp, '');

		$temp = array(
			'id'        => 'wpPreview',
			'name'      => 'wpPreview',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMsg('showpreview'),
			'accesskey' => wfMsg('accesskey-preview'),
			'title'     => wfMsg( 'tooltip-preview' ).' ['.wfMsg( 'accesskey-preview' ).']',
			//XXCHANGED
			'onclick'   => 'needToConfirm = false; checkMinLength = false; checkSummary();',
			'class'     => 'button white_button_100 submit_button',
			'onmouseout' => 'button_unswap(this);',
			'onmouseover' => 'button_swap(this);',
			'style'     => 'float: left; background-position: 0pt 0pt;',
		);
		$buttons['preview'] = wfElement('input', $temp, '');
		$buttons['live'] = '';

		$temp = array(
			'id'        => 'wpDiff',
			'name'      => 'wpDiff',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMsg('showdiff'),
			'accesskey' => wfMsg('accesskey-diff'),
			'title'     => wfMsg( 'tooltip-diff' ).' ['.wfMsg( 'accesskey-diff' ).']',
			//XXCHANGED
			'onclick'   => 'needToConfirm = false; checkMinLength = false; checkSummary();',
			'class'     => 'button white_button_150 submit_button',
			'onmouseout' => 'button_unswap(this);',
			'onmouseover' => 'button_swap(this);',
			'style'     => 'float: left; background-position: 0pt 0pt;',
		);
		$buttons['diff'] = wfElement('input', $temp, '');

		wfRunHooks( 'EditPageBeforeEditButtons', array( &$this, &$buttons ) );
		return $buttons;
	}
}

?>

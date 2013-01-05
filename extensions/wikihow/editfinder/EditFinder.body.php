<?
require_once('WikiHow.php');

$efType;

class EditFinder extends UnlistedSpecialPage {
	var $topicMode = false;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'EditFinder' );
	}
	
	/**
	 * Set html template path for EditFinder actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	public static function getUnfinishedCount(&$dbr, $type){
		switch($type){
			case 'Stub':
				$count = $dbr->selectField(array('page', 'templatelinks'),
						'count(*) as count',
						array('tl_title' => 'Stub', 'tl_from=page_id') );
				return $count;
				
			case 'Format':
				$count = $dbr->selectField(array('page', 'templatelinks'),
						'count(*) as count',
						array('tl_title' => 'Format', 'tl_from=page_id') );
				return $count;
			case 'Topic':
				// No real unfinished count for Greenhouse by Topic
				return 0;
		}

		return 0;
	}
	
	function getNextArticle() {
		global $wgRequest;
		
		//skipping something?
		$skip_article = $wgRequest->getVal('skip');
		
		//flip through a few times in case we run into problem articles
		for ($i=0;$i<10;$i++) {
			$pageid = $this->topicMode ? $this->getNextByInterest($skip_article) : $this->getNext($skip_article);
			if (!empty($pageid))
				return $this->returnNext($pageid);
		}
		return $this->returnNext('');
	}


	function getNextByInterest($skip_article) {
		global $wgRequest, $wgUser;

		$fname = "EditFinder::getNextByInterest";
		wfProfileIn($fname); 


		$dbm = wfGetDB(DB_MASTER);

		// mark skipped 
		if (!empty($skip_article)) {
			$t = Title::newFromText($skip_article);
			$id = $t->getArticleID();
			
			//mark the db for this user
			if (!empty($id))
				$dbm->insert('editfinder_skip', array('efs_page'=>$id,'efs_user'=>$wgUser->getID(),'efs_timestamp'=>wfTimestampNow() ));
		}
	
		$aid = $wgRequest->getVal('id');
		
		if ($aid) {
			//get a specific article
			$sql = "SELECT page_id from page WHERE 
				page_id = $aid LIMIT 1;";
		} 
		else {
			$timediff = date("YmdHis", strtotime("-1 day"));
			$sql = "SELECT page_id from page p INNER JOIN categorylinks c ON c.cl_from = page_id WHERE page_namespace = 0 ";
			$sql .= $this->getSkippedArticles('page_id');
			$sql .= $this->getUserInterests();
			
			//teen filter
			if ($wgUser->getOption('contentfilter') != 0) {
				$filter = $wgUser->getOption('contentfilter');
				if ($filter == 1) $sql .= " AND p.page_catinfo & " . CAT_TEEN . " = " . CAT_TEEN;
				if ($filter == 2) $sql .= " AND p.page_catinfo & " . CAT_TEEN . " = 0 ";
			}
			
			$sql .= " ORDER BY p.page_random LIMIT 1;";
		}
		
		$res = $dbm->query($sql); 

		while ($row = $dbm->fetchObject($res)) {
			$pageid = $row->page_id;
		}
		
		if ($pageid) {
			//not a specified an article, right?
			if (empty($aid)) {
				//is the article {{in use}}?
				if ($this->articleInUse($pageid)) {
					//mark it as viewed
					$pageid = '';
				}
			}
		}
		return $pageid;
		wfProfileOut($fname);
	}

	function getNext($skip_article) {
		global $wgRequest, $wgUser;

		$dbm = wfGetDB(DB_MASTER);

		// mark skipped 
		if (!empty($skip_article)) {
			$t = Title::newFromText($skip_article);
			$id = $t->getArticleID();
			
			//mark the db for this user
			if (!empty($id))
				$dbm->insert('editfinder_skip', array('efs_page'=>$id,'efs_user'=>$wgUser->getID(),'efs_timestamp'=>wfTimestampNow() ));
		}
	
		$aid = $wgRequest->getVal('id');
		
		if ($aid) {
			//get a specific article
			$sql = "SELECT ef_edittype, ef_page from editfinder WHERE 
				ef_page = $aid LIMIT 1;";
		} 
		else {
			$edittype = strtolower($wgRequest->getVal( 'edittype' ));

			$timediff = date("YmdHis", strtotime("-1 day"));
			$sql = "SELECT ef_edittype, ef_page from editfinder 
					INNER JOIN page p ON p.page_id = ef_page
					INNER JOIN templatelinks t ON t.tl_from = ef_page
					INNER JOIN categorylinks c ON c.cl_from = ef_page
					WHERE ef_last_viewed < ". $dbm->addQuotes($timediff) ."
					AND lower(t.tl_title) = ". $dbm->addQuotes($edittype) ."
					AND lower(ef_edittype) = ".$dbm->addQuotes($edittype)
					.$this->getSkippedArticles();

			$sql .= $this->getUserCats() . " ";
			
			//teen filter
			if ($wgUser->getOption('contentfilter') != 0) {
				$filter = $wgUser->getOption('contentfilter');
				if ($filter == 1) $sql .= " AND p.page_catinfo & " . CAT_TEEN . " = " . CAT_TEEN;
				if ($filter == 2) $sql .= " AND p.page_catinfo & " . CAT_TEEN . " = 0 ";
			}
			
			$sql .= " ORDER BY p.page_counter DESC LIMIT 1;";
		}
		
		$res = $dbm->query($sql); 

		while ($row = $dbm->fetchObject($res)) {
			$pageid = $row->ef_page;
		}
		
		if ($pageid) {
			//not a specified an article, right?
			if (empty($aid)) {
				//is the article {{in use}}?
				if ($this->articleInUse($pageid)) {
					//mark it as viewed
					$dbm->update('editfinder', array('ef_last_viewed'=>wfTimestampNow()), array('ef_page'=>$pageid));
					$pageid = '';
				}
			}
		}
		return $pageid;
	}
	
	function returnNext($pageid) {
		global $wgOut, $Title;
		
		if (empty($pageid)) {
			//nothing?  Ugh.
			$a['aid'] = '';
		}
		else {
			if (!$this->topicMode) {
				//touch db
				$dbm = wfGetDB(DB_MASTER);
				$dbm->update('editfinder', array('ef_last_viewed'=>wfTimestampNow()),array('ef_page'=>$pageid));
			}

			$a = array();
			
			$t = Title::newFromID($pageid);
			
			$a['aid'] = $pageid;
			$a['title'] = $t->getText();
			$a['url'] = $t->getLocalURL();
		}
			
		//return array
		return( $a );	 
	}	
	
	function confirmationModal($type,$id) {
		global $wgOut, $Title;

		$fname = "EditFinder::confirmationModal";
		wfProfileIn($fname); 

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|How to ".$t->getText()."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Thanks for your edits to <a href='".$t->getLocalURL()."'>How to ".$t->getText()."</a>.</p>
			<p>Would it be appropriate to remove the <span class='template_type'>".strtoupper($type)."</span> from this article?</p>
			<div style='clear:both'></div>
			<span style='float:right'>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_yes')."' onclick='editFinder.closeConfirmation(true);return false;' >
			<input class='button white_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_no')."' onclick='editFinder.closeConfirmation(false);return false;' >
			</span>
			</div>";
		$wgOut->addHTML( $content );
		wfProfileOut($fname); 
	}
	
	function cancelConfirmationModal($id) {
		global $wgOut, $Title;

		$fname = "EditFinder::cancelConfirmationModal";
		wfProfileIn($fname); 

		$t = Title::newFromID($id);
		$titletag = "[[".$t->getText()."|How to ".$t->getText()."]]";
		$content = 	"
			<div class='editfinder_modal'>
			<p>Are you sure you want to stop editing <a href='".$t->getLocalURL()."'>How to ".$t->getText()."</a>?</p>
			<div style='clear:both'></div>
			<p id='efcc_choices'>
			<a href='#' id='efcc_yes'>".wfMsg('editfinder_cancel_yes')."</a>
			<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='".wfMsg('editfinder_confirmation_no')."' id='efcc_no'>
			</p>
			</div>";
		$wgOut->addHTML( $content );
		wfProfileOut($fname); 
	}

	
	/**	
	 * articleInUse
	 * check to see if {{inuse}} or {{in use}} is in the article
	 * returns boolean
	 **/
	function articleInUse($aid) {
		$dbr = wfGetDB(DB_SLAVE);
		$r = Revision::loadFromPageId( $dbr, $aid );
		
		if (strpos($r->getText(),'{{inuse') === false)
			$result = false;
		else
			$result = true;	
		return $result;
	}
		
	function getUserInterests() {
		$interests = CategoryInterests::getCategoryInterests();	
		$interests = array_merge($interests, CategoryInterests::getSubCategoryInterests($interests));
		$interests = array_values(array_unique($interests));

		$fn = function(&$value) {
			$dbr = wfGetDB(DB_SLAVE);
			$value = $dbr->strencode($value);
		};
		array_walk($interests, $fn);
		$sql = " AND c.cl_to IN ('" . implode("','", $interests) . "') ";
		return $sql;
	}

	/**	
	 * getUserCats
	 * grab categories specified by the user
	 * returns sql string
	 **/
	function getUserCats() {
		global $wgUser, $wgCategoryNames;
		$cats = array();
		$catsql = '';
		$bitcat = 0;

		$dbr = wfGetDB(DB_SLAVE);

		$row = $dbr->selectRow('suggest_cats', array('*'), array('sc_user'=>$wgUser->getID()));

		if ($row) {
			$field = $row->sc_cats;
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);			
		}
		
		$topcats = array_flip($wgCategoryNames);
		
		foreach ($cats as $key => $cat) {
			foreach ($topcats as $keytop => $cattop) {
				$cat = str_replace('-',' ',$cat);
				if (strtolower($keytop) == $cat) {
					$bitcat |= $cattop;
					break;
				}
			}
		}
		if ($bitcat > 0) {
			$catsql = ' AND p.page_catinfo & '.$bitcat.' <> 0';
		}
		return $catsql;
	}
	
	/**	
	 * getSkippedArticles
	 * grab articles that were already "skipped" by the user
	 * returns sql string
	 **/
	function getSkippedArticles($column = 'ef_page') {
		global $wgUser;
		$skipped = '';
		$dbm = wfGetDB(DB_MASTER);
		$res = $dbm->select('editfinder_skip', array('efs_page'), array('efs_user'=>$wgUser->getID()));

		while ($row = $dbm->fetchObject($res)) {
			$skipped_ary[] = $row->efs_page;
		}
		if (count($skipped_ary) > 0)
			$skipped = ' AND ' . $column . ' NOT IN ('. implode(',',$skipped_ary) .') ';

		return $skipped;
	}
	
	/**
	 * EXECUTE
	 **/
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang, $wgParser, $efType, $wgTitle;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		wfLoadExtensionMessages('EditFinder');
		
		self::setTemplatePath();

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->topicMode = strtolower($par) == 'topic' || strtolower($wgRequest->getVal('edittype')) == 'topic';

		if ($wgRequest->getVal( 'fetchArticle' )) {
			$wgOut->setArticleBodyOnly(true);
			echo json_encode($this->getNextArticle());
			return;
			
		} else if ($wgRequest->getVal( 'show-article' )) {
			$wgOut->setArticleBodyOnly(true);
			
			if ($wgRequest->getVal('aid') == '') {
				$catsJs = $this->topicMode ? "editFinder.getThoseInterests();" : "editFinder.getThoseCats();";
				$catsTxt = $this->topicMode ? "interests" : "categories";
				$wgOut->addHTML('<div class="article_inner">No articles found.  <a href="#" onclick="' . $catsJs . '">Select more ' . $catsTxt . '</a> and try again.</div>');
				return;
			}
			
			$t = Title::newFromID($wgRequest->getVal('aid'));
			
			$articleTitleLink = $t->getLocalURL();
			$articleTitle = $t->getText();
			//$edittype = $a['edittype'];
						
			//get article
			$a = new Article($t);
			
            $r = Revision::newFromTitle($t);
            $popts = $wgOut->parserOptions();
            $popts->setTidy(true);
            $popts->enableLimitReport();
            $parserOutput = $wgParser->parse( $r->getText(), $t, $popts, true, true, $a->getRevIdFetched() );
            $popts->setTidy(false);
            $popts->enableLimitReport( false );
            $html = WikiHowTemplate::mungeSteps($parserOutput->getText(), array('no-ads'));
			$wgOut->addHTML($html);
			return;
			
		} else if ($wgRequest->getVal( 'edit-article' )) {
			// SHOW THE EDIT FORM
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('aid'));
			$a = new Article($t);
			$editor = new EditPage( $a );
			$editor->edit();
			return;
			
		} else if ($wgRequest->getVal( 'action' ) == 'submit') {
			$wgOut->setArticleBodyOnly(true);
			
			$efType = strtolower($wgRequest->getVal('type'));
			
			$t = Title::newFromID($wgRequest->getVal('aid'));
			$a = new Article($t);
			
			//log it
			$params = array($efType);            
			$log = new LogPage( 'EF_'.$efType, false ); // false - dont show in recentchanges

			$log->addEntry('', $t, 'Repaired an article -- '.strtoupper($efType).'.', $params);
			
			$text = $wgRequest->getVal('wpTextbox1');
			$sum = $wgRequest->getVal('wpSummary');
		
			//save the edit
			$a->doEdit($text,$sum,EDIT_UPDATE);
			wfRunHooks("EditFinderArticleSaveComplete", array($a, $text, $sum, $wgUser, $efType));
			return;
			
		} else if ($wgRequest->getVal( 'confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('type'),$wgRequest->getVal('aid')) ;
        	wfProfileOut($fname);
			return;
			
		} else if ($wgRequest->getVal( 'cancel-confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->cancelConfirmationModal($wgRequest->getVal('aid')) ;
        	wfProfileOut($fname);
			return;
			
		} else { //default view (same as most of the views)
			$sk = $wgUser->getSkin();
			$wgOut->setArticleBodyOnly(false);
		
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('clientscript.js', 'preview.js'), 'skins/common', false));
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('jquery.cookie.js'), 'extensions/wikihow/common', false));
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('editfinder.js'), 'extensions/wikihow/editfinder', false));
		
			$efType = strtolower($target);
			if (strpos($efType,'/') !== false) {
				$efType = substr($efType,0,strpos($efType,'/'));
			}
			if ($efType == '') {
				//no type specified?  send 'em to format...
				$wgOut->redirect('/Special:EditFinder/Format');
			}
			$wgOut->addHTML('<script>var g_eftype = "' . $target . '";</script>');
			
			//add main article info
			$vars = array('pagetitle' => wfMsg('app-name').': '.wfMsg($efType),'question' => wfMsg('editfinder-question'),
				'yep' => wfMsg('editfinder_yes'),'nope' => wfMsg('editfinder_no'),'helparticle' => wfMsg('help_'.$efType));
			$vars['uc_categories'] = $this->topicMode ? 'Interests' : 'Categories';
			$vars['lc_categories'] = $this->topicMode ? 'interests' : 'categories';
			$vars['css'] = HtmlSnips::makeUrlTags('css', array('editfinder.css'), 'extensions/wikihow/editfinder', false);
			$vars['css'] .= HtmlSnips::makeUrlTags('css', array('suggestedtopics.css'), 'extensions/wikihow', false);

			$html = EasyTemplate::html('editfinder_main',$vars);
			$wgOut->addHTML($html);
			
			$wgOut->setHTMLTitle(wfMsg('app-name').': '.wfMsg($efType).' - wikiHow');
		}
		
		$stats = new EditFinderStandingsIndividual($efType);
        $stats->addStatsWidget();
		$standings = new EditFinderStandingsGroup($efType);
		$standings->addStandingsWidget();
	}
}

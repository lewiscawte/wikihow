<?
class WikiHow {

	/*private*/
	 var $mSteps, $mWarnings, $mTips, $mTitle, $mSummary, $mCategory, $mCategories, $mLangLinks;
	 var $section_array;
	 
	/*private */
	 var $mTitleObj, $mArticle;
	
	/*private*/
	 var $mIsWikiHow, $mIsNew;
	
	function WikiHow() {
		$this->mSteps = "";
		$this->mWarnings = "";
		$this->mTips = "";
		$this->mTitle = "";
		$this->mSummary = "";
		$this->mCategory = "";		
		$this->mIsWikiHow = true;	
		$this->mIsNew = true;	
		$this->mCategories = array();
		$this->section_array = array();
		$this->mLangLinks = "";		
	}
	
	/** 
		Private
		*/

	function formatBulletList($text) {
		$result = "";
		if ($text == null || $text == "") return $result;
		$lines = split("\n", $text);
		if (!is_array($lines)) return $result;
		foreach($lines as $line) {
			if (strpos($line, "*") === 0) 
				$line = substr($line, 1);
			$line = trim($line);
			if ($line != "") 
				$result .= "*$line\n";
		}
		return $result;	
	}		
	function removeExtraLines($str) {
		$step_array = split("\n", $str);
		$step_final = array();
		foreach ($step_array as $step) {
			if (trim($step) != "") {
				$step_final[] = $step;
			}
		}
		$str = join("\n", $step_final);
		return $str;
	}
	
	function loadFromArticle($article) {
		
		$this->mArticle = $article;
		$this->mTitleObj = $article->getTitle();
		
		// parse the article;
		$text = $this->mArticle->getContent(true);
		$this->loadFromText($text);

		// set the title
		$this->mTitle = $this->mTitleObj->getText();

	}
	function loadFromText ($text) {
		global $wgContLang;
		
		//echo "<BR>$text<BR>";
		
		// extract the category if there is one
		// TODO: make this an array
		
		$this->mCategories = array();		
		
		// just extract 1 category for now
		//while ($index !== false && $index >= 0) { // fix for multiple categories		
		preg_match_all("/\[\[" .  $wgContLang->getNSText ( NS_CATEGORY ) . ":[^\]]*\]\]/im", $text, $matches);
		foreach($matches[0] as $cat) {
			$cat = str_replace("[[" . $wgContLang->getNSText ( NS_CATEGORY ) . ":", "", $cat);
			$cat = trim(str_replace("]]", "", $cat));
			$this->mCategories[] = $cat;
			$text = str_replace("[[Category:" . $cat . "]]", "", $text);
		}
		// extract interlanguage links
		$matches = array();
		if ( preg_match_all('/\[\[[a-z][a-z]:.*\]\]/', $text, $matches) ) {
			foreach ($matches[0] as $match) {
				$text = str_replace($match, "", $text);
				$this->mLangLinks .= "\n" . $match;
			}
		}
		$this->mLangLinks = trim($this->mLangLinks);

		// get the number of sections
		
		$sectionCount = WikiHow::getSectionCount($text);


		$found_summary = false;
		for ($i = 0; $i < $sectionCount; $i++) {
			$section = Article::getSection($text, $i);
			$title = WikiHow::getSectionTitle($section);
			$section = WikiHow::stripSectionofTitle($section);
			$title = strtolower($title);
		    $title = trim($title);
			if ($title == "" && !$found_summary) {
				$this->section_array["summary"] = $section;
				$found_summary = true;
			} else {				
				$orig = $title;
				$counter = 0;
				while (isset($section_array[$title])) {
					$title = $orig + $counter;
				}			
				$title = trim($title);				
				$this->section_array[$title] = $section;	
			}
		}
	
		// set the steps
		// AA $index = strpos($text, "== Steps ==");
		// AA if (!$index) {
		if ($this->hasSection("steps") == false) {
			$this->mIsWikiHow = false;
			return;
		} 
		
		
		$this->mSummary = $this->getSection("summary");
		$this->mTips = $this->getSection(wfMsg('tips'));
		$this->mWarnings = $this->getSection(wfMsg('warnings'));
		$this->mSteps = $this->getSection(wfMsg('steps'));
	
		// TODO: get we get tips and warnings from getSection?
		$this->mIsNew = false;
		
	}
	
	function load($title) {
		$this->mTitleObj = Title::newFromText( $title );
		$article = new Article($this->mTitleObj);
		$this->loadFromArticle($article);
	}
	
		
	/* SET */
	function setArticle($article) {
		$this->mArticle = $article;
	}

	function setSteps($steps) {
		$this->mSteps = $steps;
	}
	
	function setWarnings($warnings) {
		$this->mWarnings = $warnings;
	}

	function setTips($tips) {
		$this->mTips = $tips;
	}

	function setTitle($title) {
		$this->mTitle = $title;
	}

	function setSummary($summary) {
		$this->mSummary = $summary;
	}	
	
	function setCategory($category) {
		$this->mCategory = $category;
	}
	
	function setCategoryString($categories) {
		$this->mCategories = split(",", $categories);
	}

	function getLangLinks () {
		return $this->mLangLinks;
	}

	function setLangLinks ($links) {
		$this->mLangLinks = $links;
	}

	/* GET */
	function getSteps($forEditing = false) {
		if (!$forEditing) {
			return str_replace("\n\n", "\n", $this->mSteps);
		} else {
		    $s = ereg_replace("^==[ ]*" . wfMsg('steps') . "[ ]*==", "", $this->mSteps);
			$steps = split("\n", $s); 			
			$step_str = "";
			$i = 1;
			return $s;
			foreach ($steps as $step) {
				$step = trim($step);
				if ($step == "")
					continue;
				$index = strpos($step, "#");
				if ($index !== false && $index == 0) {
					$step = "$i.  " . trim(substr($step, 1));
				} else {
					//$step = "$i.  " . $step;
				}
				$step_str .= $step . "\n";
				$i++;
			}
			return trim($step_str);
		}
	}
	
	function getWarnings() {
		return $this->mWarnings;
	}

	function getTips() {
		return $this->mTips;
	}

	function getTitle() {
		return $this->mTitle;
	}

	function getSummary() {
		return $this->mSummary;
	}	
	
	function getCategory() {
		return $this->mCategory;
	}
	
	function getCategoryString() {
		$s = "";
		foreach ($this->mCategories as $cat) {
			$s .= $cat . "|";
		}
		return $s;
	}	
	
	function formatWikiText() {
		$text = $this->mSummary . "\n";
		
		if (isset($this->section_array["ingredients"])) {
			$ingredients = $this->section_array["ingredients"];
			if ($ingredients != null && $ingredients != "") {
			$text .= "\n== " . wfMsg('ingredients') . " ==\n" 
					. $ingredients;
			}
		}
				
		$step = split("\n", $this->mSteps);
		$steps = "";
		foreach ($step as $s) {
			$s = ereg_replace("^[0-9]*", "", $s);
			$index = strpos($s, ".");
			if ($index !== false &&  $index == 0) {
				$s = substr($s, 1);
			}
			if (trim($s) == "") continue;
			$s = trim($s);
			if (strpos($s, "#") === 0) {
				$steps .= $s . "\n";
			} else {
				$steps .= "#" . $s . "\n";
			}
		}
		$this->mSteps = $steps;
		
		
		// move all categories to the top
		
		$text = trim($text);	
		foreach ($this->mCategories as $cat) {
			$cat = trim($cat);
			if ($cat != "") {
				$text .= "\n[[Category:$cat]]";					
			}
		}
				
		$text .= "\n== "  . wfMsg('steps') .  " ==\n" . $this->mSteps;
	
        $tmp = $this->getSection("video");
       	if ($tmp != "")
          $text .= "\n== "  . wfMsg('video') .  " ==\n" . trim($tmp) . "\n";

		$tmp = $this->formatBulletList($this->mTips);		
		if ($tmp != "") {
			$text .= "\n== "  . wfMsg('tips') .  " ==\n" . $tmp;
		}
		
		$tmp = $this->formatBulletList($this->mWarnings);
		if ($tmp != "") {
			$text .= "\n== "  . wfMsg('warnings') .  " ==\n" . $tmp;
		}
		
		$tmp = $this->formatBulletList($this->getSection("thingsyoullneed"));		    
	    	if ($tmp != "")
		  $text .= "\n== "  . wfMsg('thingsyoullneed') .  " ==\n" . $tmp;

		$tmp = $this->formatBulletList($this->getSection("related"));		    
	    	if ($tmp != "")
		  $text .= "\n== "  . wfMsg('relatedwikihows') .  " ==\n" . $tmp;

		$tmp = $this->formatBulletList($this->getSection("sources"));		    
	    	if ($tmp != "")
     	  $text .= "\n== "  . wfMsg('sources') .  " ==\n" . $tmp;
		          	    
		$text .= $this->mLangLinks;

		/// add the references div if necessary
		if (strpos($text, "<ref>") !== false) {
			$rdiv = '{{reflist}}';
			$headline = "== "  . wfMsg('sources') .  " ==";
			if (strpos($text, $headline) !== false) {
				$text = trim($text) . "\n$rdiv\n";
				//str_replace($headline . "\n", $headline . "\n" . $rdiv . "\n", $text);
			} else {
				$text .=  "\n== "  . wfMsg('sources') .  " ==\n" . $rdiv . "\n";
			}
		}	
		return $text;
	}
	
	/**
	  * INSERTS A NEW WIKI ARTICLE WITH THE WIKIHOW WRAPPER
	  * 
	  */
	function insertNew() {
		$text = $this->formatWikiText();
		$this->mTitleObj = Title::newFromText( $this->mTitle );
		$this->mArticle = new Article($this->mTitleObj);		
		$ret = $this->mArticle->insertNewArticle($text, "", false, false);		
		$this->updateLinks();
		return true;
	}
	
	function update() {
		$text = $this->formatWikiText();
		$this->mArticle = new Article($this->mTitleObj);
		$ret = $this->mArticle->updateArticle($text, "", false, false);						
		$this->updateLinks();
		return true;
	}
	
	/*
	 *
	 */
	 function getFullURL() {
	 	return $this->mTitleObj->getFullURL();
	 }
	 
	 function getDBKey() {
	 	return $this->mTitleObj->getDBKey();
	 }
	 
	 function isWikiHow() {
	 	return $this->mIsWikiHow;
	 }
	 
	 function getTimestamp() {
	 	if ($this->mArticle) {
	 		return $this->mArticle->getTimestamp();
	 	} else {
	 		return "";
	 	}
	 }

	 function updateLinks() {	
	 	global $wgEnablePersistentLC; 	
		$text = $this->mArticle->getContent( true );
		$wgLinkCache = new LinkCache;
		//$wgOut->addWikiText( $text );

		if ( $wgEnablePersistentLC ) {
			$wgLinkCache->saveToLinkscc( $this->mArticle->getID(), wfStrencode( $this->mTitleObj->getPrefixedDBkey() ) );
		}

		$linksUpdate = new LinksUpdate( $this->mArticle->getID(), $this->mTitleObj->getPrefixedDBkey() );
		$linksUpdate->doDumbUpdate();
		$linksUpdate->fixBrokenLinks();
	 }
	 /* might want to update this function later to be more comprehensive
	 	for now, if it has == Steps == in it, it's a WikiHow article */
	 	
	 /* static */ 
	 function articleIsWikiHow( $article ) {
		if (!$article instanceof Article) return false;
		if (!$article->mTitle instanceof Title) return false;
		if ($article->getTitle()->getNamespace() != NS_MAIN) return false;
		$text = $article->getContent(true);
		$index = preg_match('/^==[ ]*' . wfMsg('steps') . '[ ]*==/mi', $text);
		//$index = strpos($article->getContent(true), "== " . wfMsg('steps') ." ==");
		if ($index == 0) {
			return false;
		}
	 	return true;
	 	
	 }
	 
	 
	 function useWrapperForEdit ($article) {
	 	$index = 0;
	 	$foundSteps = 0;
	 	$text = $article->getContent(true);
	 
	//	$mw =& MagicWord::get( MAG_FORCEADV );
		$mw =& MagicWord::get( 'forceadv' );
        if ($mw->match( $text ) ) {
			return false;
        }
		$count = WikiHow::getSectionCount($text);	
	 	while (true && $index < $count) {	
	 		$section = $article->getSection($text, $index); 
	 	
	 		$title = WikiHow::getSectionTitle($section);

	 		if ($title == wfMsg('steps')) {
	 			$foundSteps = true;
	 		} else if ($title == "" && $index == 0) { // summary
	 		} else if ($title == wfMsg('tips'))  {
	 		} else if ($title == wfMsg('warnings')) {
	 		} else if ($title == wfMsg('ingredients')) {
	 		} else if ($title == wfMsg('relatedwikihows'))  {
	 		} else if ($title == wfMsg('thingsyoullneed'))  {
	 		} else if ($title == wfMsg('video'))  {
	 	   	 } else if ($title == wfMsg('sources'))  {
	 		} else {
	 			return false;
	 		}
	 		if (!$section) {
	 			break;
	 		}
	 		
	 		//echo "hello...$index <br/><br/>$section";
	 		if (trim($section) == "") {
	 			//"echo sectionis nothing.";
	 		}
	 		//echo "<br>$index length: " . strlen($section) . "<BR>";
	 		$index++;
	 		//echo strpos($section, "== Ingredients ==") . ": $section<br><br>";
	 	}


	 	//echo "hello...";
	 	if ($index <= 8) {
	 		#echo "returning true. $index foundSteps $foundSteps";
		
	 		return $foundSteps;
	 	} else {
	 		return false;
	 	}
	 }
	 
	 function getSectionCount($text) {
		// would this be better? :)
		$matches = array();
		preg_match_all( '/^(=+).+?=+|^<h([1-6]).*?>.*?<\/h[1-6].*?>(?!\S)/mi',$text, $matches);
		return sizeof($matches[0])+1;
	 }
	 
	 /***
	  *   Given a MediaWiki section, such as 
	  *   == Steps ===
	  *   1. This is the first step.
	  *   2. This is the second step.
	  *
	  *   This function returns 'Steps'.
	  */
	 function getSectionTitle($section) {
	 	$title = "";
	 	$index = strpos(trim($section), "==");
	 	if ($index !== false && $index == 0) {
	 		$index2 = strpos($section, "==", $index+2);
	 		if ($index2 !== false && $index2 > $index) {
	 			$index += 2;
	 			$title = substr($section, $index, $index2-$index);
	 			$title = trim($title);
	 		}
	 	} else {
	 	}
	 	return $title;
	 }
	 
	 /***
	  *   Given a MediaWiki section, such as 
	  *   == Steps ===
	  *   1. This is the first step.
	  *   2. This is the second step.
	  *
	  *   This function returns :
	  *   1. This is the first step.
	  *   2. This is the second step.
	  */
	 function stripSectionOfTitle($section) {
	 	if (strpos($section, "==") !== false) {
	 		$newLine = strpos($section, "\n");
	 		if ($newLine !== false && $newLine >= 0) {
	 			$section = substr($section, $newLine+1);
	 		}
	 	}
	 	return $section;
	 }
	 
	 function hasSection ($title) {
	 	$ret = isset($this->section_array[strtolower(wfMsg($title))]);
	 	if (!$ret) $ret = isset($this->section_array[$title]);
	 	return $ret;
	 }
	 
	 function getSection ($title) {
	     $title = strtolower($title);
	 	if ($this->hasSection($title)) {
	 		return $this->section_array[$title];
	 	} else {
	 		return "";
	 	}
	 }
	 
	 function setSection ($title, $section) {
		$this->section_array[$title] = $section;	 	
	 }


	 function setRelatedString($related) {
	     $r_array = split("\|", $related);
	     $result = "";
	     foreach ($r_array as $r) {	         
	         $r = trim($r);
	         if ($r == "") continue;
	         //$t = Title::newFromText($r);
	         //$result .= "*  [[" . $t->getDBKey() . "]]\n";
	         $result .= "*  [[" . $r . "|" . wfMsg('howto', $r) . "]]\n";
	     }
	     $this->setSection("related", $result);	     
	 }

	function loadFromRequest ($request) {
		$whow = new WikiHow();
		$steps = $request->getText("steps");
		$tips  = $request->getText("tips");
		$warnings = $request->getText("warnings");		
		$summary =	$request->getText("summary");
	
		$category = ""; 
		$categories = ""; 
		for ($i = 0; $i < 2; $i++) {
			if ($request->getVal("category" . $i, null) != null) {
				if ($categories != "") $categories .= ", ";
				$categories .= $request->getVal("category" . $i);
			} else if ($request->getVal('topcategory' . $i, null) != null && $request->getVal('TopLevelCategoryOk') == 'true') {
				if ($categories != "") $categories .= ", ";
				$categories .= $request->getVal("topcategory" . $i);
			}
		}

		$hidden_cats = $request->getText("categories22");
		if ($categories == "" && $hidden_cats != "") 
			$categories = $hidden_cats;
		
		$ingredients = $request->getText("ingredients");
		
		$whow->setSection("ingredients", $ingredients);
		$whow->setSteps($steps);
		$whow->setTips($tips);
		$whow->setWarnings($warnings);		
		$whow->setSummary($summary);
		$whow->setSection("thingsyoullneed", $request->getVal("thingsyoullneed"));
		$whow->setLangLinks($request->getVal('langlinks'));

		$related_no_js = $request->getVal('related_no_js');
		$no_js = $request->getVal('no_js');

		if ($no_js != null && $no_js == true) {
			$whow->setSection("related", $related_no_js);
		
		} else {
			// user has javascript
			$whow->setRelatedString($request->getVal("related_list"));		
		}
		$whow->setSection("sources", $request->getVal("sources"));
		$whow->setSection("video", $request->getVal("video"));
		$whow->setCategoryString($categories);
		return $whow;
	}
}
?>

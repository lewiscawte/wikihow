<?
class Categoryhelper extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Categoryhelper' );
	}

	function getCategoryDropDownTree() {
		global $wgRequest, $wgMemc;

		$key = wfMemcKey('category', 'tree', 'wikihow');
		$options = $wgMemc->get( $key );
		if (!$options || true) {
			$t = Title::makeTitle(NS_PROJECT, wfMsg('categories'));
			$r = Revision::newFromTitle($t);
			if (!$r) return array(); 
			$text = $r->getText();
		
			$lines = split("\n", $text);
			$bucket = array();
			$result = array();
			$bucketname = '';
			foreach ($lines as $line) {
				if (strlen($line) > 1 
					&& strpos($line, "*") == 0 
					&& strpos($line, "*", 1) === false) {
					$result [$bucketname] = $bucket; 
					$bucket = array();
					$bucketname = trim(str_replace("*", "", $line));
				} else if (trim($line) != "") {
						$bucket[] = trim($line);
				}
			}
	
			$wgMemc->set($key, $options, time() + 3600);
		}
		return $result;
	}


	/**
	 *
	 **/
	function makeCategoryArray($current_lvl, &$lines) {
		$pattern = '/^(\*+)/';
		$bucket2 = array();
	
		while (count($lines)>0) {
			$line = array_shift($lines);
			preg_match($pattern, $line, $matches);
			$lvl = strlen($matches[0]);
			$prevcat = $cat;
			$cat = trim(str_replace("*", "", $line));

			if ($current_lvl == $lvl) {
				//array_push($bucket2,$cat);
				$bucket2[$cat] = $cat;
			} else if ($lvl > $current_lvl) {
				array_unshift($lines, $line);
				$bucket2[$prevcat] = $this->makeCategoryArray(($current_lvl+1), $lines);
			} else {
				array_unshift($lines, $line);
				return $bucket2;
			}
		}
		return $bucket2;
	}

	/**
	 *
	 **/
	function getCategoryTreeArray() {
		global $wgRequest, $wgMemc;
	
		$key = wfMemcKey('category', 'tree', 'wikihow');
		$options = $wgMemc->get( $key );
		if (!$options || true) {
			$t = Title::makeTitle(NS_PROJECT, wfMsg('categories'));
			$r = Revision::newFromTitle($t);
			if (!$r) return array(); 
			$text = $r->getText();
			$text = preg_replace('/^\n/m', '', $text);

			$lines = split("\n", $text);
			$result = $this->makeCategoryArray(1, $lines);
	
			$wgMemc->set($key, $options, time() + 3600);
		}
		return $result;
	}

	/**
	 *
	 **/
	function displayCategoryArray($lvl, $catary, &$display, $toplevel) {
		$indent = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	
		if (is_array($catary)) {
			foreach(array_keys($catary) as $cat) {
				if ($lvl == 0) { $toplevel = $cat; }
					
				$fmt = "";
				for($i=0;$i<$lvl;$i++) {
					$fmt .= $indent;
				}
				$display .= "<a name=\"".urlencode(strtoupper($cat))."\" id=\"".urlencode(strtoupper($cat))."\" ></a>\n";
				$display .= $fmt;
				if (is_array($catary[$cat])) {
					$display .= "<img id=\"img_".urlencode($cat)."\" src=\"/skins/WikiHow/topics-arrow-off.gif\" height=\"10\" width=\"10\" border=\"0\" onClick=\"toggleImg(this);Effect.toggle('toggle_".urlencode(strtoupper($cat))."', 'slide', {delay:0.0,duration:0.0}); return false;\" /> ";
				} else {
  	          	$display .= "<img src=\"/skins/WikiHow/blank.gif\" height=\"10\" width=\"10\" border=\"0\"  /> ";
				}

				if ($lvl == 0) {
					$display .= "$cat <br />\n";	
				}else {
					$display .= "<INPUT TYPE=CHECKBOX NAME=\"".$toplevel.",".$cat."\" >  " . $cat . "<br />\n";
				}
		
				$display .= "<div id=\"toggle_".urlencode(strtoupper($cat)) ."\" style=\"display:none\">\n";
				$display .= "   <div>\n";
				if ($lvl > 0) {
	
				}
				$this->displayCategoryArray(($lvl+1), $catary[$cat], $display, $toplevel);

				$display .= "   </div>\n</div>\n";
			}
		}
	}


	/**
	 *
	 **/
	function flattenary(&$bucket, $lines) {
		foreach (array_keys($lines) as $line) {
			if (is_array($lines[$line])) {
				array_push($bucket, $line);
				$this->flattenary($bucket, $lines[$line]);
			} else {
				array_push($bucket, $lines[$line]);
			}
		}
	}

	function json2Array() {
		global $wgRequest;
		$val = array();

		$wgary = $wgRequest->getValues();
		if (is_array($wgary)) {
			foreach (array_keys($wgary) as $wgarykeys) {
				$jsonstring = preg_replace('/_/m', ' ', stripslashes($wgarykeys));
				$val = json_decode($jsonstring, true);
					
				if ($val['json'] == "true") { return $val; }
			}
		}
		return $val;
	}

    function execute($par) {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);	
		if ($wgRequest->getVal('cat')) {
			$category = $wgRequest->getVal('cat');
			$options = $this->getCategoryDropDownTree();
			foreach($options[$category] as $sub) {
				echo $this->getHTMLForCategoryOption($sub, '', true);
			}
		}

		if ($wgRequest->getVal('type') == "categorypopup") {
			$options2 = $this->getCategoryTreeArray();
			echo $this->getHTMLForPopup($options2);
		}

		$jsonAry = $this->json2Array();
		if ($jsonAry['type'] == "supSubmit") {
			$jsonAry['ctitle'] = preg_replace('/-whPERIOD-/m',".",$jsonAry['ctitle']);
			$jsonAry['ctitle'] = preg_replace('/-whDOUBLEQUOTE-/m',"\"",$jsonAry['ctitle']);
			echo $this->getHTMLsupSubmit($jsonAry);
		}

		return;	
	}
	
	function getTopLevelCategoriesForDropDown() {
		$results = array();
		$options = Categoryhelper::getCategoryDropDownTree();
		foreach ($options as $key=>$value) {
			$results[] = $key;
		}
		return $results;
	}
	
	function modifiedParentCategoryTree($parents = array(), $children = array() ) {
	        if($parents != '') {
	            foreach($parents as $parent => $current) {
	                if ( array_key_exists( $parent, $children ) ) {
	                    # Circular reference
	                    $stack[$parent] = array();
	                } else {
	                    $nt = Title::newFromText($parent);
	                    if ( $nt ) {
	                        $stack[$parent] = $nt->getParentCategoryTree( $children + array($parent => 1) );
	                    }
	                }
	            }
	            return $stack;
	        } else {
	            return array();
	        }
	}
	function getCategoryOptionsForm($default, $cats = null) {
		global $wgUser, $wgMaxCategories, $wgRequest;
		
		if (!$wgUser->isLoggedIn())
			return "";
	
		// get the top and bottom categories
		$valid_cats = array();
		if (is_array($cats)) {
			$valid_cats = array_flip($cats);
		}

		if ($wgRequest->getVal('oldid') != null && $default != "") {
			$fakeparent = array();
			$fakeparent[Title::makeTitle(NS_CATEGORY, $default)->getFullText()] = array();
			$tree = Categoryhelper::modifiedParentCategoryTree($fakeparent);
		} else {
			$tree = WikiHow::getCurrentParentCategoryTree();
		}
		if (!$tree) $tree = array();
		$toplevel = array();
		$bottomlevel = array();
	
		if ($wgRequest->getVal('topcategory0', null) != null) {
			// user has already submitted form, could be a preview, just set it to what they posted
			for ($i = 0; $i < $wgMaxCategories; $i++) {
				if ($wgRequest->getVal('topcategory' . $i, null) != null) {
					$toplevel[] = $wgRequest->getVal('topcategory' . $i);
					$bottomlevel[] = $wgRequest->getVal('category' . $i);
				}
			}
		} else {
			// fresh new form from existing article
			foreach ($tree as $k=>$v) {	
				$keys = array_keys($tree);
				$bottomleveltext = $k;
				$child = $v;
				$topleveltext = $k;
				while (is_array($child) && sizeof($child) > 0) {
					$keys = array_keys($child);
					$topleveltext = $keys[0];
					$child = $child[$topleveltext];
				}
				$tl_title = Title::newFromText($topleveltext);
				$bl_title = Title::newFromText($bottomleveltext);
				if (isset($valid_cats[$bl_title->getText()])) {
					if ($tl_title != null) {
						$toplevel[] = $tl_title->getText();
						$bottomlevel[] =  $bl_title->getText();
					} else {
						$toplevel[] = $bl_title->getText();
					}
				} else {
					#print_r($tree);
					#echo "shit! <b>{$bl_title->getText()}</b><br/><br/>"; print_r($bl_title); print_r($valid_cats);
				}
			}
		}
	
		$helper = Title::makeTitle(NS_SPECIAL, "Categoryhelper");
	
		$toplevels = Categoryhelper::getTopLevelCategoriesForDropDown();
	  	$options = Categoryhelper::getCategoryDropDownTree();
		
		$html = "<script type='text/javascript' src='/extensions/wikihow/categories.js'></script>";
		$html .= '<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/categories.css"; /*]]>*/</style>';
		$html .= " <script type='text/javascript'>
					var gCatHelperUrl = \"{$helper->getFullURL()}\";
					var gCatHelperSMsg = \"" .wfMsg('selectsubcategory') . "\";
					var gMaxCats = {$wgMaxCategories};
					var gCatMsg = '" . wfMsg('categoryhelper_summarymsg') . "';
				</script>
					<input type='hidden' name='TopLevelCategoryOk' value='" . (sizeof($toplevel) == sizeof($bottomlevel) ? "false" : "true") . "'/>
				<noscript>" . wfMsg('categoryhelper_javascript') . "<br/></noscript>
				";
		$i = 0;
print_r($bottomlevel);

		$max = 1;
		if (sizeof($toplevel) > 0) $max = sizeof($toplevel);
		for ($i = 0; $i < $max || $i < $wgMaxCategories; $i++) {
			$top = $bot = '';
			$style = ' style="display:none;" ';	
			if ($i < sizeof($toplevel) || $i == 0) {
				$top = $toplevel[$i];
				$bot = $bottomlevel[$i];
				$style = '';
			}
	
			if ($i > 0) $html .= "<br/>";
			
			$html .= "<SELECT class='topcategory_dropdown' name='topcategory{$i}' id='topcategory{$i}' onchange='updateCategories({$i});' $style> 
					<OPTION VALUE=''>".wfMsg('selectcategory')."</OPTION>";

			foreach ($toplevels as $c) {
				$c = trim($c);
				if ($c== "") continue;
				$html .= "<OPTION VALUE=\"$c\" " . ($c == $top ? "SELECTED": "") ." >$c</OPTION>\n";
			}
			$html .= "</SELECT>   <span id='category_div{$i}'><SELECT onchange='catHelperUpdateSummary();' class='subcategory_dropdown' name='category{$i}' id='category{$i}'  $style>";
				if (is_array($options[$top])) {
					if ($bot == "") {
						 $html .= "<OPTION VALUE=''>".wfMsg('selectcategory')."</OPTION>";
					}
					foreach($options[$top] as $sub) {
	   					$html .= Categoryhelper::getHTMLForCategoryOption($sub, $bot);
	   				}
				}
			$html .= "</SELECT> </span> ";
		}
		if ($i >= sizeof($toplevel)) {
			$html .= "<a onclick='javascript:showanother();' id='showmorecats'>" . wfMsg('addanothercategory') . "</a>";
		}
	
		return $html;
	}
	

	/**
	 *
	 **/
	function getCategoryOptionsForm2($default, $cats = null) {
		global $wgUser, $wgMaxCategories, $wgRequest;
		
		if (!$wgUser->isLoggedIn())
			return "";
	
		// get the top and bottom categories
		$valid_cats = array();
		if (is_array($cats)) {
			$valid_cats = array_flip($cats);
		}

		if ($wgRequest->getVal('oldid') != null && $default != "") {
			$fakeparent = array();
			$fakeparent[Title::makeTitle(NS_CATEGORY, $default)->getFullText()] = array();
			$tree = Categoryhelper::modifiedParentCategoryTree($fakeparent);
		} else {
			$tree = WikiHow::getCurrentParentCategoryTree();
		}
		if (!$tree) $tree = array();
		$toplevel = array();
		$bottomlevel = array();
	
		if ($wgRequest->getVal('topcategory0', null) != null) {
			// user has already submitted form, could be a preview, just set it to what they posted
			for ($i = 0; $i < $wgMaxCategories; $i++) {
				if ($wgRequest->getVal('topcategory' . $i, null) != null) {
					$toplevel[] = $wgRequest->getVal('topcategory' . $i);
					$bottomlevel[] = $wgRequest->getVal('category' . $i);
				}
			}
		} else {
			// fresh new form from existing article
			foreach ($tree as $k=>$v) {	
				$keys = array_keys($tree);
				$bottomleveltext = $k;
				$child = $v;
				$topleveltext = $k;
				while (is_array($child) && sizeof($child) > 0) {
					$keys = array_keys($child);
					$topleveltext = $keys[0];
					$child = $child[$topleveltext];
				}
				$tl_title = Title::newFromText($topleveltext);
				$bl_title = Title::newFromText($bottomleveltext);
				if (isset($valid_cats[$bl_title->getText()])) {
					if ($tl_title != null) {
						$toplevel[] = $tl_title->getText();
						$bottomlevel[] =  $bl_title->getText();
					} else {
						$toplevel[] = $bl_title->getText();
					}
				} else {
					#print_r($tree);
					#echo "shit! <b>{$bl_title->getText()}</b><br/><br/>"; print_r($bl_title); print_r($valid_cats);
				}
			}
		}

		$html = "\n";
		$catlist = "";

		for ($i = 0; $i < $wgMaxCategories; $i++) {
			if ($toplevel[$i] != "") {
				//$html .= "<a href=\"/Category:".$bottomlevel[$i]."\">".$toplevel[$i].":".$bottomlevel[$i]."</a><br>\n";
				$html .= "<input type=hidden readonly size=40 name=\"topcategory".$i."\" value=\"".$toplevel[$i]."\" />";         
				$html .= "<input type=hidden readonly size=60 name=\"category".$i."\" value=\"".$bottomlevel[$i]."\" />\n";         
				if ($i == 0) {
					$catlist = $bottomlevel[$i];
				} else {
					$catlist .= ", ".$bottomlevel[$i];
				}
			} else {
				$html .= "<input type=hidden readonly size=40 name=\"topcategory".$i."\" value=\"\" />";         
				$html .= "<input type=hidden readonly size=60 name=\"category".$i."\" value=\"\" />\n";         
			}
		}

		if ($catlist == "" ) {
			$html .= "<div id=\"catdiv\">Article has not been categorized.</div>\n";
		} else {
			$html .= "<div id=\"catdiv\">$catlist</div>\n";
		}
	
		return $html;
	}
	/**
	 *
	 **/
	function getHTMLForCategoryOption($sub, $default, $for_js = false) {
		$style = "";
		if (strpos($sub, "**") !== false && strpos($sub, "***") === false) 
			$style = 'style="font-weight: bold;"';
		$sub = substr($sub, 2);
		$value = trim(str_replace("*", "", $sub));
		$display = str_replace("*", "&nbsp;&nbsp;&nbsp;&nbsp;", $sub);
		return "<OPTION VALUE=\"{$value}\" " . ($default == $value ? "SELECTED" : "") . " $style>$display</OPTION>\n";
	}


	/**
	 *
	 **/
	function getHTMLForPopup($treearray) {

		$css = HtmlSnips::makeUrlTags('css', array('categoriespopup.css'), 'extensions/wikihow', false);
		$style = "";
		$display = "";
		$indent = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		$display = '
<html>
<head>

<title>Categories</title>

<style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/newskin.css"; /*]]>*/</style>' .  $css . '
<script language="javascript" src="/extensions/wikihow/prototype1.8.2/prototype.js"></script>
<script language="javascript" src="/extensions/wikihow/prototype1.8.2/effects.js"></script>
<script language="javascript" src="/extensions/wikihow/prototype1.8.2/controls.js"></script>
<script language="javascript" src="/extensions/wikihow/categoriespopup.js"></script>
<script type="text/javascript">/*<![CDATA[*/
var Category_list = [
			';

		$completeCatList = array();
		$this->flattenary($completeCatList, $treearray);
		foreach ($completeCatList as $cat) {
			if ($cat != '') {
				$cat = preg_replace('/\'/', '\\\'', $cat);
				$display .= "'$cat',";
			}
		}
		$display .= "''];\n";

		$display .= '
/*]]>*/</script>
</head>
<body >

<div id="article">
<form name="catsearchform" action="#" onSubmit="return searchCategory();">
<input id="category_search" autocomplete="off" size="40" type="text" value="" onkeyup="return checkCategory();" />
<input type="button" value="'.wfMsg('Categorypopup_search').'" onclick="return searchCategory();" />

<div class="autocomplete" id="cat_search" style="display:none"></div>

<script type="text/javascript">/*<![CDATA[*/
new Autocompleter.Local(\'category_search\', \'cat_search\', Category_list, {fullSearch: true});
/*]]>*/</script>
</form><br />
			';

		$display .= "<strong>".wfMsg('Categorypopup_selected').": </strong><br />\n";
		$display .= "<div id=\"selectdiv\">";
		$display .= "<p>Loading...</p>";
		$display .= "</div><br />\n";

		$display .= '
<script type="text/javascript">showSelected();</script>

<strong>'.wfMsg('Categorypopup_browse').':</strong>  <a href="#" onclick="return collapseAll();">['.wfMsg('Categorypopup_collapse').']</a>
<a name="form_top" id="form_top" ></a>
<div id="categoriesPop" style="width:470;height:215px;overflow:auto">
<form name="category">
			';

		$this->displayCategoryArray(0,$treearray,$display, "TOP");

		$display .= '
		<script type="text/javascript"> checkSelected(); </script>
			';

		$display .= '
	</div>
</div>
	<br />

	<input type="button" value="   '.wfMsg('Categorypopup_save').'   " onclick="handleSAVE(this.form)" />
	<input type="button" value="'.wfMsg('Categorypopup_close').'" onclick="handleCancel()" />
</form>

</body>
</html>
			';
		return $display . "\n";
	}


	function getLastPatrolledRevision (&$title) {
		$a = null;
		$dbr =& wfGetDB( DB_SLAVE );
		$page_id = $title->getArticleID();
		$sql =	"SELECT max(rc_this_oldid) as A from recentchanges
							WHERE rc_cur_id = $page_id and rc_patrolled = 1";
		$res = $dbr->query($sql);
		if ( false !== $res && $dbr->numRows( $res ) > 0 && $row = $dbr->fetchObject( $res ) )  {
					if ($row->A) $a = new Article($title, $row->A);	
		}
		$dbr->freeResult( $res );
		// if that didn't work, get the last edit that's not in recentchanges	
		if ($a == null) {
			$sql = "select max(rev_id) as A from revision where rev_page = $page_id and rev_id 
				NOT IN (select rc_this_oldid from recentchanges where rc_cur_id = $page_id and rc_patrolled = 0 );";
			$res = $dbr->query ( $sql );
			if ( false !== $res ) {
						if ($row = $dbr->fetchObject( $res ) ) { 
							// why does this work in the line above? $row->A > 0 ????
							if ($row->A > 0) $a = new Article($title, $row->A);
						}	
			}
		}
		if ($a == null) {
			$a = new Article($title);
		}
		return $a;	
	}

	/**
	 * processSupSubmit - process SpecialUncategorizedpages Submit to set category.  AJAX call.
	 **/
	function getHTMLsupSubmit($jsonAry) {
		global $wgUser;

		$category = "";
		$textnew = "";

      if ($wgUser->getID() <= 0) { 
			echo "User not logged in";
			return false;
		}


		//echo "\nThe How-to Manual That You Can Edit\n";
		//echo "ctitle == ".$jsonAry["ctitle"] . "\n";
		//echo "topcateogry0 == ".$jsonAry["topcategory0"] . "\n";
		//echo "category0 == ".$jsonAry["category0"] . "\n";
		//echo "topcateogry1 == ".$jsonAry["topcategory1"] . "\n";
		//echo "cateogry1 == ".$jsonAry["category1"] . "\n";

		$ctitle = $jsonAry["ctitle"];
		if ($jsonAry["topcategory0"] != "") {
			$category0 = urldecode($jsonAry["category0"]);
			$category .= "[[Category:".$category0."]]\n";
			if ($jsonAry["topcategory1"] != "") {
				$category1 = urldecode($jsonAry["category1"]);
				$category .= "[[Category:".$category1."]]\n";
			}

			$title = Title::newFromURL(urldecode($ctitle));
			if ($title == null) {
				echo "ERROR: title is null for $url";
				exit;
			}

			if ($title->getArticleID() > 0) {
				// we want the most recent version, don't want to overwrite changes
				$a = new Article($title); 
				$text = $a->getContent();

				$pattern = '/== .*? ==/';
				if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {

					$textnew = substr($text,0,$matches[0][1]) . "\n";
					$textnew .= $category ;
					$textnew .= substr($text,$matches[0][1]) . "\n";

					$summary = "categorization";
					$minoredit = "";
					$watchthis = "";
					$bot = true;

					# update the article here
					if( $a->doEdit( $textnew, $summary, $minoredit, $watchthis ) ) {
							wfRunHooks("CategoryHelperSuccess", array());
							echo "Category Successfully Saved.\n";
							return true;
					} else {
							echo "ERROR: Category could not be saved.\n";
					}
				} else {
					echo "ERROR: Category section could not be located.\n";
				}
			} else {
				echo "ERROR: Article could not be found. [$url]\n";
			}
		} else {
			echo "No Category selected\n";
		}
		return false;
	}
}

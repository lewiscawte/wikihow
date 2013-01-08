<?
class RelatedArticle extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'RelatedArticle' );
    }


    function execute ($par) {
	    global $wgRequest, $wgSitename, $wgLanguageCode;
	    global $wgDeferredUpdateList, $wgOut, $wgUser, $wgServer;

		$this->setHeaders();

		require_once('WikiHow.php');
	
	   $fname = "wfRelatedArticle";
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}
	
		$t = $wgRequest->getVal('target', null);
		if ($t == null) {
			$wgOut->addHTML(wfMsg('notarget') );
			return;
		}	
		$titleObj = Title::newFromDBKey($t);
		$a = new Article($titleObj);
		$whow = new WikiHow();
		$whow->loadFromArticle($a);
		$r = Revision::newFromTitle($titleObj);
		$text = $r->getText();
	
	    // TODO if post
	  	if ($wgRequest->wasPosted()) {
			// protect from users who can't edit
	        if ( ! $titleObj->userCanEdit() ) {
	            $wgOut->readOnlyPage( $a->getContent(), true );
	            wfProfileOut( $fname );
	            return;
	        }
	
			// construct the related wikihow section
	         $r_array = split("\|", $wgRequest->getVal('related_list'));
	         $result = "";
	         foreach ($r_array as $r) {
	             $r = urldecode(trim($r));
	             if ($r == "") continue;
	             $result .= "*  [[" . $r . "|" . wfMsg('howto', $r) . "]]\n";
	         }
	
			if (strpos($text, "\n== "  . wfMsg('relatedwikihows') .  " ==\n") !== false)
				// no newline neeeded to start with
				$result = "== "  . wfMsg('relatedwikihows') .  " ==\n" . $result;
			else
				$result = "\n== "  . wfMsg('relatedwikihows') .  " ==\n" . $result;
	
			$text = "";
			$index = 0;  
			$content = $a->getContent();
			$last_heading = "";
			$inserted = false;
	
			$section = -1;
			$ext_links_section = -1;
	
			if ($a->getSection($content, $index) == null) $index++; // weird where there's no summary
			while  ( ($sectiontext = $a->getSection($content, $index)) != null) {
				$i = strpos($sectiontext, "\n");
				if ($i > 0) {
					$heading = substr($sectiontext, 0, $i);
					$heading = trim(str_replace("==", "", $heading));
					if ($heading == wfMsg('relatedwikihows') ) {
						$section = $index;
						break;
					}
					if ($heading == wfMsg('sources'))
						$ext_links_section = $index;
				}	
				$index++;
			}
	
	
		//	if (!$inserted)
		//		$text .= $result;
			
		$text = $result;
		$tail = '';
		$text = $a->getContent();
		
	
		// figure out which section to replace if related wikihows don't exist
		$just_append = false;
		if ($section <= 0) {
			if ($ext_links_section > 0) {
				// related wikihows have to go before external links 
				$section = $ext_links_section;
				// glue external links and related wikihows together and replace external links
				$result = $result . "\n" . $a->getSection($content, $section); 
			} else {
				$section = $index; 
				$result = "\n" . $result; // make it a bit prettier
				$just_append = true;
			}
		} else {
	        $s = $a->getSection($content, $section);
	        $lines = split("\n", $s);
	        for ($i = 1; $i < sizeof($lines); $i++) {
	            $line = $lines[$i];
	             if (strpos($line, "*") !== 0) {
	             // not a list item
	                $tail .= "\n" . $line ;
	             }
	         }
		} 
	
		$result .= $tail;
	
		$summary = '';//wfMsg('relatedwikihows'); // summary is already set
		if (!$just_append) {
			$text = $a->replaceSection($section, $result, $summary);
		} else {
			$text = $text . $result;
		}
			
	    $watch = false;
		$minor = false;
		$forceBot = false;
	    if ($wgUser->getID() > 0) 
			$watch = $wgUser->isWatched($titleObj);
		$summary = wfMsg('relatedwikihows'); // summary for the edit
		
	//	 	$text = $a->replaceSection($section, $result, $summary);
	
	      	$a->updateArticle( $text, $summary, $minor, $watch, $forceBot);
		} else { 
		}
	
	
	//MW should handle editing extensions better, duplication of code sucks	
	
	   if( $titleObj->isProtected( 'edit' ) ) {
	   		if( $titleObj->isSemiProtected() ) {
	        	$notice = wfMsg( 'semiprotectedpagewarning' );
	           if( wfEmptyMsg( 'semiprotectedpagewarning', $notice ) || $notice == '-' ) {
	        	} 
			} else {
	       		$notice = wfMsg( 'protectedpagewarning' );
	      	}
	    	$wgOut->addWikiText( $notice );
	    }   
		 
		$relatedHTML = "";
		$text = $a->getContent();
	/* 
	error detecing?
		$re = '/^== ' . wfMsg('relatedwikihows') . ' ==[ ]*$/m';
		if ($whow->getSection("related wikihows") == "" 
				&& preg_match ($re, $text) ) {
			$wgOut->addHTML(wfMsg('relatedarticle_error_loading'));
			return;	
		}
	*/
	    if ($whow->getSection("related wikihows") != "") {
	        $related_vis = "show";
	        $relatedHTML = $whow->getSection("related wikihows");
	          $relatedHTML = str_replace("*", "", $relatedHTML);
	            $relatedHTML = str_replace("[[", "", $relatedHTML);
	            $relatedHTML = str_replace("]]", "", $relatedHTML);
	            $lines = split("\n", $relatedHTML);
	            $relatedHTML = "";
	            foreach ($lines as $line) {            
	                $xx = strpos($line, "|");
	                if ($xx !== false) 
	                    $line = substr($line, 0, $xx);
	                $line = trim($line);
	                if ($line == "") continue;
	
	                $relatedHTML .= "<OPTION VALUE=\"" . str_replace("\"", "&quote", $line)
	                  . "\">$line</OPTION>\n";
	            }
	        }
		
			$me = Title::makeTitle(NS_SPECIAL, "RelatedArticle");
	
		$wgOut->addHTML("
	<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/managerelated.css'; /*]]>*/</style>
	<script type='text/javascript'>
			var wgServer = \"{$wgServer}\";	
	</script>
	<script type='text/javascript' src='/extensions/wikihow/managerelated.js'></script>
	
	<form method='POST' action='{$me->getFullURL()}' name='temp' onsubmit='return check();'>
	
	You are currently editing related wikiHows for the article 
	<a href='" . $titleObj->getFullURL() . "' target='new'>How to " . $titleObj->getFullText() . "</a>.<br/>
	
	<table cellpadding=10>
	<tr><td valign='top'>
	1. Enter some search terms to find related wikiHows and press 'Search:'.<br/><br/>
	<input type='hidden' name='target' value=\"" . htmlspecialchars($t) . "\">
	<input type='hidden' name='related_list'>
	<input type='text' name='q'>
	<input type=button class='btn' onclick='check();'  value='Search'/>
	</td>
	<td valign=top>
	<div style='width: 175px; float: left;'>
	Related wikiHows:
	</div>
	<div style='width: 175px; float: right; text-align: right; margin-bottom:5px;'>
	Move: <input type=button value='Up' class='btn' onclick='moveRelated(true);'/> <input type=button value='Down' class='btn' onclick='moveRelated(false);'/>
	</div>
	<select size=\"5\" id=\"related\" ondblclick='viewRelated();' >$relatedHTML
	              </select>
	<br/><br/>
	<div style='width: 205px; float: left; text-align: left; font-size: xx-small; font-style: italic;'>
	(double click item to open wikiHow in new window)
	</div>
	<div style='width: 175px; float: right; text-align: right;'>
	<input type=button onclick='remove_related();' value='Remove' class='btn'/>
	<input type=button value='Save' onclick='submitform();' class='btn'/>
	</div>
	</td></tr>
	<tr>
		<td id='lucene_results' colspan='2' valign='top' class='lucene_results'></td>
	</tr><tr>
		<td id='previewold' colspan='2' valign='top' ></td>
	</tr></table>
	
	</form>

	<div id='preview'></div>
	
	");
	
	}
}	
class PreviewPage extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'PreviewPage' );
    }

	function execute( $par ) {
		global $wgRequest, $wgParser, $wgUser, $wgOut;
		$t = Title::newFromDBKey($wgRequest->getVal('target'));
		$a = new Article (&$t);
		$text = $a->getContent(true);
		$snippet = $a->getSection($text, 0) . "\n" . $a->getSection($text, 1);
		$sk = $wgUser->getSkin();
		$html = $wgOut->parse($snippet) ;
		$wgOut->disable();
		echo $html;
	}
}

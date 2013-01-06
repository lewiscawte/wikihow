<?php
class GoogleAPIResults extends UnlistedSpecialPage {
	
	    function __construct() {
	        UnlistedSpecialPage::UnlistedSpecialPage( 'GoogleAPIResults' );
	    }
	
	
		function getSideBar () {
			global $wgRequest;
			require_once("extensions/wikihow/GoogleCSEAPI.php");
			$results = GoogleCSEAPI::query($wgRequest->getVal('search'));
			$s .= "More results from Google<ul>";
			if (!is_array($results->items)) {
				$s.= "</ul> .. no results.";
				return $s;
			}
			foreach ($results->items as $r) {
				$t = str_replace(" - wikiHow", "",$r->title);
				$s .= "<li><a href='{$r->link}'>{$t}</a></li>\n";
			}
			$s .= "</ul>";
			return $s;
		}

		function getSideBar2 () {
		global $wgRequest; 
	
		
		if ($wgRequest->getVal('search', null) == null) {
			return "";
		}
		$s = "
	
	<script type='text/javascript'>	
		window.onload = requestGoogleResults;
		
		var requester;
		
		function loadGoogleResults() {
		    if (requester.readyState == 4) {
				if (requester.status == 200) {
					var string = requester.responseText;
					// replace links
					var results = document.getElementById('gSearch');
					if (string == '') {
						results.innerHTML = 'Sorry - no results for Google search.<br/><br/><i><font size=\'xxx-small\'>Perhaps the Google server is busy, or your search returned no results.</i></font>';
					} else {
						results.innerHTML = string;
					}
				}
			}
		}
		
		function requestGoogleResults(){
		
			try {
				requester = new XMLHttpRequest();
			} catch (error) {
				try {
					requester = new ActiveXObject('Microsoft.XMLHTTP');
				} catch (error) {
					return false;
				}
			}
			requester.onreadystatechange = loadGoogleResults;
			requester.open('GET', '" . $wgServer . "/Special:GoogleAPIResults?q=" . urlencode($wgRequest->getVal('search')) . "');
			requester.send(null);		
	}
	</script>
	
			<div id=\"gSearch\">Loading Google results...</div>";
			
			
	
		return $s;
		
	}
	
	function execute($par) {
			global $wgRequest, $wgOut, $IP;
			$terms = $wgRequest->getVal("q", "");
			if ($terms == "") return "";
			require_once("$IP/extensions/wikihow/GoogleSearch.php");
			$results = gSearch::query($terms);
			$wgOut->disable();
							
			$spell = $results[1];
			if (is_array($spell)) $spell = null; //if array its an error
			$items = $results[0];
			$result = array();
			$result[0] = "";
			$result[1] = $spell;
	
			if (!is_array($items)) {
				return $result;
			}
				
			$s = "<h3>" . wfMsg('more-search-results')."</h3>";
			$s .= "<ol>";
			foreach ($items as $r) {
				$title = $r['title'];
				$title = trim(str_ireplace(" - WikiHow", "", $title));
				$stripped_title = str_replace("<b>", "", $title);
				$stripped_title = str_replace("</b>", "", $stripped_title);
				$snippet = $r['snippet'];
				$snippet = str_replace($stripped_title, "", $snippet);
				$snippet = str_replace($title, "", $snippet);
				$snippet = str_replace("<br>", "", $snippet);
				$s .= "<li><a href=\"{$r['URL']}\">" . strip_tags($title) . "</a></li>"; 
			}	
			$s .= "</ol>";
			echo $s;
	}
}
	

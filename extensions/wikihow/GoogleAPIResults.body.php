<?php
class GoogleAPIResults extends UnlistedSpecialPage {
	
	    function __construct() {
	        UnlistedSpecialPage::UnlistedSpecialPage( 'GoogleAPIResults' );
	    }
	
	
		function getSideBar () {
			global $wgGoogleJSAPIKey;
			$s =  <<<END
		<style type='text/css'>

			div.gsc-results {
				width: 160px;
				white-space: wrap;
				border: 1px solid #ccc;
				padding: 2px;
			}
			A.gs-title  {
    			color: #006398 !important;
    			text-decoration: none;
			}
			A.gsc-trailing-more-results  {
    			color: #006398 !important;
    			text-decoration: none;
			}
			A.gs-title B {
    			color: #006398 !important;
    			text-decoration: none;
			}

			div.gs-title {
				width: 160px;
				white-space: normal;
				text-decoration : none;
				height:auto !important;
			}
			table.gsc-search-box, table.gsc-branding, table.gsc-resultsHeader, div.gsc-tabsArea, div.gs-snippet {
				display: none;
			}
			div.gs-visibleUrl {
				display: none;
			}
			div.gs-watermark { display: none; } 
		</style>

    <script src="http://www.google.com/jsapi?key={$wgGoogleJSAPIKey}" type="text/javascript"></script>
    <script language="Javascript" type="text/javascript">
    //<![CDATA[

google.load('search', '1');

function OnLoad() {

  // create a search control
  var searchControl = new google.search.SearchControl();

  // Set the Search Control to get the most number of results
  searchControl.setResultSetSize(google.search.Search.LARGE_RESULTSET);

  var ws = new google.search.WebSearch();
  ws.setSiteRestriction("wikihow.com");
  // Create 2 searchers and add them to the control
  searchControl.addSearcher(ws);

  // Set the options to draw the control in tabbed mode
  var drawOptions = new google.search.DrawOptions();
  drawOptions.setDrawMode(google.search.SearchControl.DRAW_MODE_TABBED);

  // Draw the control onto the page
  searchControl.draw(document.getElementById("searchcontrol"), drawOptions);

		var q = "";
    	var params = window.location.href;
    	if (params.indexOf("?") > 0 ) {
        	params = params.substring(params.indexOf("?") + 1);
    	} else {
        	params = "";
    	}
    	var parts = params.split("&");
    	for (var i = 0; i < parts.length; i++) {
        	var x = parts[i].split("=");
        	if (x[0] == "search") q= x[1];
    	}

      // Execute an inital search
	  q = q.replace(/\+/, " ");
      searchControl.execute(q);
    }
    google.setOnLoadCallback(OnLoad);

    //]]>
    </script>
	More results from Google:
	<div id="searchcontrol" style='width: 160px;'>Loading...</div>
END
	;
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
	

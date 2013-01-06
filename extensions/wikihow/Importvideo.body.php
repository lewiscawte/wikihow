<?


define(IV_RESULTS_PER_PAGE, 10); 

class Importvideo extends SpecialPage {

	// youtube, 5min, etc.
	public $mSource;

	public $mResponseData = array(), $mCurrentNode, $mResults, $mCurrentTag = array();
 
    function __construct($source = null) {
        SpecialPage::SpecialPage( 'Importvideo' );
		$this->mSource = $source;
    }

	/****
	 *
	 *  Returns a title of a newly created article that needs a video
	 */
	function getNewArticleWithoutVideo(){
		global $wgRequest;
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		$t = null;
		$dbr = wfGetDB(DB_SLAVE);
		$vidns = NS_VIDEO;
		$skip= "";
		if ($wgRequest->getVal('skip') != null) {
			$skip = " AND nap_page < {$wgRequest->getVal('skip')}";
			setcookie( $wgCookiePrefix.'SkipNewVideo', $wgRequest->getVal('skip'), time() + 86400, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );	
		} else if ( isset( $_COOKIE["{$wgCookiePrefix}SkipNewVideo"] ) ) {
			$skip = " AND nap_page < " . $_COOKIE["{$wgCookiePrefix}SkipNewVideo"];
		}
		$sql = "SELECT nap_page
				FROM newarticlepatrol 
					LEFT JOIN templatelinks t1 ON t1.tl_from = nap_page and t1.tl_namespace = {$vidns}
					LEFT JOIN templatelinks t2 on t2.tl_from =  nap_page and t2.tl_title IN ('Nfd', 'Copyvio', 'Merge', 'Speedy')
					LEFT JOIN page on  nap_page = page_id
			WHERE nap_patrolled =1 AND t1.tl_title is NULL AND nap_page != 0  AND t2.tl_title is null AND page_is_redirect = 0 {$skip}
			ORDER BY nap_page desc LIMIT 1;";
		$res = $dbr->query($sql);
		if ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromID($row->nap_page);
		}
		return $t;
	}

    /****
     *
     *  Returns an article from a specific category that requires a video
     */
	function getTitleFromCategory($category) {
		$cat = Title::makeTitle(NS_CATGEGORY, $category);
		$t	 = null;
		$dbr = wfGetDB(DB_MASTER);
		$sql = "SELECT page_title from page left join templatelinks on tl_from=page_id and tl_namespace=" . NS_VIDEO . "
											left join categorylinks on cl_from = page_id
				WHERE
						tl_title is NULL
					AND	cl_to = " . $dbr->addQuotes($cat->getDBKey()) . 
			" ORDER BY rand() LIMIT 1;";
		$res = $dbr->query($sql);
		if ($row = $dbr->fetchObject($res)) 
			$t = Title::newFromText($row->page_title);
		return $t;
	}

    /****
     *
     *  Processes a search for users who are looking for an article to add a video to
     */
	function doSearch($target, $orderby, $query, $search) {
		global $wgOut, $wgRequest;
		$wgOut->addHTML(wfMsg('importvideo_searchinstructions') . 
			"<br/><br/><form action='{$me->getFullURL()}'>
            		<input type='hidden' name='target' value='" . htmlspecialchars($target) . "'/>
            		<input type='hidden' name='orderby' value='{$orderby}'/>
            		<input type='hidden' name='popup' value='{$wgRequest->getVal('popup')}'/>
            		<input type='hidden' name='q' value='" . htmlspecialchars($query) . "' >
            		<input type='text' name='dosearch' value='" . ($search != "1" ? htmlspecialchars($search) : "") . "' size='40'/>
            		<input type='submit' value='" . wfMsg('importvideo_search') . "'/>
            	</form>
            	<br/>");
		if ($search != "1") {
			$l = new LSearch();
			$results = $l->googleSearchResultTitles($search);
			$base_url = $me->getFullURL() . "?&q=" . urlencode($query) . "&source={$source}";
			if (sizeof($results) == 0) {
				$wgOut->addHTML(wfMsg('importvideo_noarticlehits'));	
				return;		
			}
			#output the results
			$wgOut->addHTML(wfMsg("importvideo_articlesearchresults") . "<ul>");
			foreach ($results as $t) {
			$wgOut->addHTML("<li><a href='" . $base_url . "&target=" . urlencode($t->getText()) . "'>" 
					. wfMsg('howto', $t->getText() . "</a></li>"));
			}
			$wgOut->addHTML("</ul>");
		}
	}

	/*** 
	 *
	 * Maintain modes through URL parameters
	 */
	function getURLExtras() {
		global $wgRequest;
		$popup 		= $wgRequest->getVal('popup') == 'true' ? "&popup=true" : "";
       	$rand   	= $wgRequest->getVal('new') || $wgRequest->getVal('wasnew')
                    	? "&wasnew=1" : "";
		$bycat 		= $wgRequest->getVal('category') ? "&category=" . urlencode($wgRequest->getVal('category')) : "";
		$orderby 	= $wgRequest->getVal('orderby') ? "&orderby=" . $wgRequest->getVal('orderby') : "";
		return $popup . $rand. $bycat . $orderby;
	}

    /****
     *
     *   The main function 
     */
    function execute ($par) {
		global $wgRequest, $wgUser, $wgOut, $wgImportVideoSources;

		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		wfLoadExtensionMessages('Importvideo');		
		if ($wgRequest->getVal('popup') == 'true') {
			$wgOut->setArticleBodyOnly(true);
		}
		$this->setHeaders();
		$source = $this->mSource = $wgRequest->getVal('source', 'wonderhowto');
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
        $query = $wgRequest->getVal('q');
		$me = Title::makeTitle(NS_SPECIAL, "Importvideo");

		$wgOut->addHTML('<script type="text/javascript" language="javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.js?rev=') . WH_SITEREV . '"></script>
    						<link rel="stylesheet" href="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.css?rev=') . WH_SITEREV . '" type="text/css" />');


		$wgOut->addHTML("<div id='importvideo'>");	
		# changing target article feature	
		$search = $wgRequest->getVal("dosearch", null);
		if ($search != null) {
			$this->doSearch($target, $orderby, $query, $search);
			return;
		}
		$sp = null;
		switch ($source) {
			case '5min':
				$sp = new ImportvideoFivemin($source);
				break;
			case 'videojug':
				$sp = new ImportvideoVideojug($source);
				break;
			case 'howcast':
				$sp = new ImportvideoHowcast($source);
				break;
			case 'youtube':
				$sp = new ImportvideoYoutube($source);
				break;
			case 'wonderhowto':
			default:
				$sp = new ImportvideoWonderhowto($source);
				break;
		}



		// handle special cases where user is adding a video to a new article or by category
		if ($wgRequest->getVal('new') || $wgRequest->getVal('wasnew')) {
			if ($wgRequest->getVal('new')) {
				$t = $this->getNewArticleWithoutVideo();
				$target = $t->getText();
			} else {
				$t = Title::newFromText($target);
			}
			$wgRequest->setVal('target', $target);
		} else if ($wgRequest->getVal('category') && $target == '') {
			$t = $this->getTitleFromCategory($wgRequest->getVal('category'));
			$target = $t->getText();
			$wgRequest->setVal('target', $target);
		}

		// construct base url to switch between sources
		$url = $me->getFullURL() . "?target=" . urlencode($target) . "&q=" . urlencode($query) . $this->getURLExtras() . "&source=";

		$t = Title::newFromText($target);
		if (!$t) {
			$wgOut->addHTML("Error: no target specified.");
			return;
		}

		$target = $t->getText();
	
		//get the steps and intro to show to the user	
		$r = Revision::newFromTitle($t);
		$text = "";
	    if ($r) 
			$text = $r->getText();
        $a = new Article($t);
        $extra  = $a->getSection($text, 0);
		$steps = "";
		for ($i = 1; $i < 3; $i++) {
			$xx = $a->getSection($text, $i);
			if (preg_match("/^==[ ]+" . wfMsg('steps') . "/", $xx)) {
				$steps = $xx;	
				break;
			}
		}
        $extra = preg_replace("/{{[^}]*}}/", "", $extra);
        $extra = $wgOut->parse($extra);
        $steps = $wgOut->parse($steps);
		$cancel = "";

		$nextlink = "/Special:Importvideo?new=1&skip={$t->getArticleID()}";
		if ($wgRequest->getVal('category')) 
			$nextlink = "/Special:Importvideo?category=" . urlencode($wgRequest->getVal('category'));

		if (!$wgRequest->getVal('popup')) {
			$wgOut->addHTML("<div class='article_title'>
				" . wfMsg('importvideo_article') . "- <a href='{$t->getFullURL()}' target='new'>" . wfMsg('howto', $t->getText()) . "</a>");
			$wgOut->addHTML("<spanid='showhide' style='font-size: 80%; text-align:right; font-weight: normal;'>
					(<a href='{$nextlink}' accesskey='s'>next article</a> |
					<a href='$url&dosearch=1' accesskey='s'>" . wfMsg('importvideo_searchforarticle') . "</a> {$cancel} )
				</span>");
			if ($wgRequest->getVal('category')) {
				$wgOut->addHTML("You are adding videos to articles from the \"{$wgRequest->getVal('category')}\" category.
					(<a href='#'>change</a>)");
			}
			$wgOut->addHTML("</div>");
	
			
			$wgOut->addHTML("<div class='video_related'>
				<div>
					<div class='article_intro'>
						Introduction
					</div>
					{$extra}
					<br clear='all'/>
					<div id='showhide' style='font-size: 80%; text-align:right;'>
						<span id='showsteps'><a onclick='javascript:showhidesteps();'>" . wfMsg('importvideo_showsteps' ) . "</a></span>
						<span id='hidesteps' style='display: none;'><a onclick='javascript:showhidesteps();'>" . wfMsg('importvideo_hidesteps' ) . "</a></span>
					</div>
					<div id='stepsarea' style='display: none;'>
					{$steps}
					</div>
					<br clear='all'/>
				</div>
				</div>
			");
		} 
		$wgOut->addHTML("<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/importvideo.js?rev=') . WH_SITEREV . "'> </script>	");
		$wgOut->addHTML('<style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/importvideo.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addHTML("<script type='text/javascript'>
			var isPopUp = " . ($wgRequest->getVal('popup') ?  "true" : "false") . ";
			</script>");
		
		if (!$wgRequest->wasPosted()) {
			# HEADER for import page
            $url = $me->getFullURL() . "?target=" . urlencode($target) . "&q=" . urlencode($query) . $this->getURLExtras(). "&source=";
		
			// refine form	
			$orderby = $wgRequest->getVal('orderby', 'relevance');
			$wgOut->addHTML($this->refineForm($me, $target, $wgRequest->getVal('popup') == 'true', $query, $orderby));

			// sources tab	
			$wgOut->addHTML("<div class='source_tab_container'>");
            foreach ($wgImportVideoSources as $s) {
                if ($s == $source) 
                    $wgOut->addHTML("<div class='source_tab' style='background: #FFF;'>" . wfMsg('importvideo_source_' . $s) . "</div>");
                else
                    $wgOut->addHTML("<div class='source_tab'><a href='{$url}{$s}'>" . wfMsg('importvideo_source_' . $s) . "</a></div>");
            } 
			$wgOut->addHTML("</div>");

			$vt = Title::makeTitle(NS_VIDEO, $target);
			if ($vt->getArticleID() > 0 && !$wgRequest->getVal('popup')) {
				$wgOut->addHTML(wfMsgExt('importvideo_videoexists', 'parse', $vt->getFullText()));
			}
		} 
		$sp->execute($par);	

		$wgOut->addHTML("</div>");	//Bebeth: took out extra closing div
/*

	this was messing the popup up
		//XXCHANGED VU Added for GA Event tracking.
		$wgOut->addHTML("
<script type='text/javascript'>
var gaJsHost = (('https:' == document.location.protocol) ? 'https://ssl.' : 'http://www.');
document.write(unescape('%3Cscript src=\'' + gaJsHost + 'google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E'));    

try {       
var pageTracker = _gat._getTracker('UA-2375655-1'); 
pageTracker._setDomainName('.wikihow.com');} catch(err) {}
</script>

<script type='text/javascript'> 
</script>   
		");
*/
		
	}

	function refineForm($me, $target, $popup, $query, $orderby = '') {
		global $wgRequest;
		$p 		= $popup ? "true" : "false";
		$rand 	= $wgRequest->getVal('new') || $wgRequest->getVal('wasnew') 
					? "<input type='hidden' name='wasnew' value='1'/>" : "";
        $cat   	= $wgRequest->getVal('category') != "" 
					? "<input type='hidden' name='category' value=\"" . htmlspecialchars($wgRequest->getVal('category')) . "\"/>" : "";
		if ($query == '') $query = $target;
       	return "<div style='text-align:center; margin-top: 5px; padding: 3px;'>
			<form action='{$me->getFullURL()}' name='refineSearch' method='GET'>
            <input type='hidden' name='target' value=\"" . htmlspecialchars($target) . "\"/>
          	<input type='hidden' name='popup' value='{$p}'/>
			{$rand}
            <input type='hidden' name='orderby' value='{$orderby}'/>
            <input type='hidden' name='source' value='{$this->mSource}'/>
			{$cat}
			" . wfMsg('importvideo_videosearchterms') . ": <input type='text' name='q' value=\"" . htmlspecialchars($query) . "\" size='30' style='font-size: 120%;'/>
            <input type='submit' class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' style='float:none; display:inline;' value='" . wfMsg('importvideo_refine') . "'/>
            </form></div>
            <br/>"; 
	}

	function getPostForm($target) {
		global $wgRequest;
		$me = Title::makeTitle(NS_SPECIAL, "Importvideo");
        $tar_es = htmlspecialchars($target);
        $query = $wgRequest->getVal('q');
        $popup = $wgRequest->getVal('popup') == "true" ?  "true" : "false" ;
        $rand   = $wgRequest->getVal('new') || $wgRequest->getVal('wasnew')
                    ? "<input type='hidden' name='wasnew' value='1'/>" : "";
        $cat   	= $wgRequest->getVal('category') != "" 
					? "<input type='hidden' name='category' value=\"" . htmlspecialchars($wgRequest->getVal('category')) . "\"/>" : "";
        return "<form method='POST' action='{$me->getFullURL()}' name='videouploadform' id='videouploadform'>
                <input type='hidden' name='description' value='' />
                <input type='hidden' name='url' id='url' value='/Special:Importvideo?{$_SERVER['QUERY_STRING']}'/>
                <input type='hidden' name='popup' value='{$wgRequest->getVal('popup')}'/>
                {$rand}
				{$cat}
                <input type='hidden' name='video_id' value=''/>
                <input type='hidden' name='target' value=\"{$tar_es}\"/>  
                <input type='hidden' name='source' value='{$this->mSource}'/>   </form>
        ";
	}


	function getPreviousNextButtons($maxResults = -1) {
		global $wgRequest;
		$query = $wgRequest->getVal('q');
		$start = $wgRequest->getVal('start', 1);
		$target = $wgRequest->getVal('target');
		$me = Title::makeTitle(NS_SPECIAL, "Importvideo");

        // Previous, Next buttons if necessary
        $s = "<table width='100%'><tr><td>";
        $url = $me->getFullURL() . "/$target?source={$this->mSource}" . $this->getURLExtras();
        $perpage = 10;

        if ($start > 1) {
            $nstart = $start - $perpage;
            $nurl =  $url ."&start=" . $nstart . "&q=" . urlencode($query);
            $s .= "<a href='$nurl'>" . wfMsg('importvideo_previous_results', 10) . "</a>";
        }

        $s .= "</td><td align='right'>";
		// no point offering a next button if there are less than 10 results
		if (sizeof($this->mResults) >= IV_RESULTS_PER_PAGE) {
			if ($maxResults < 0 || $start + IV_RESULTS_PER_PAGE < $maxResults) {
        		$nstart = $start + $perpage;
        		$nurl = $url . "&start=" . $nstart . "&q=" . urlencode($query);
        		$s .= "<a href='$nurl'>" . wfMsg('importvideo_next_results', 10) . "</a>";
			}
       	} 
		$s .= "</td></tr></table>";
		return $s;
	}

	function getResults($url) {
    	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$contents = curl_exec($ch);
		if (curl_errno($ch)) {
			# error
			echo "curl error {$url}: " . curl_errno($ch);
		} else {

		}
		curl_close($ch);
		return $contents;	
	}

	function updateVideoArticle($title, $text, $updateMessage = 'importvideo_addingvideo_summary') {
		$a = new Article($title);	
	    if ($title->getArticleID() > 0) {
        	$a->updateArticle($text, $updateMessage, false, false);
        } else {
            $a->insertNewArticle($text, $updateMessage, false, false);
        }
	}
	function updateMainArticle($target, $updateMessage = 'importvideo_addingvideo_summary') {
		global $wgOut, $wgRequest;
    	$title = Title::makeTitle(NS_MAIN, $target);
    	$vid = Title::makeTitle(NS_VIDEO, $target);
		$r = Revision::newFromTitle($title);
		$update = true;
		if (!$r) {
			$update = false;
			$text = "";
		} else {
			$text = $r->getText();
		}

		$tag = "{{" . $vid->getFullText() . "|}}";
		if ($wgRequest->getVal('description') != '') {
			$tag = "{{" . $vid->getFullText() . "|" . $wgRequest->getVal('description') . "}}";
		}
		$newsection .= "\n\n== " . wfMsg('video') . " ==\n{$tag}\n\n";
		$a = new Article($title);

		$newtext = "";

		// Check for existing video section in the target article
		preg_match("/^==[ ]*" . wfMsg('video') . "/im", $text, $matches, PREG_OFFSET_CAPTURE);
		if (sizeof($matches) > 0 ) {
			// There is an existing video section, replace it
			$i = $matches[0][1];
			preg_match("/^==/im", $text, $matches, PREG_OFFSET_CAPTURE, $i+1);
			if (sizeof($matches) > 0) {
				$j = $matches[0][1];
				// == Video == was not the last section	
				$newtext = trim(substr($text, 0, $i)) . $newsection . substr($text, $j, strlen($text));
			} else {
				// == Video == was the last section append it
				$newtext = trim($text) . $newsection;
			}
			// existing section, change it. 
		} else {
			// There is not an existng video section, insert it after steps
			// This section could be cleaned up to handle it if there was an existing video section too I guess
			$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			$found = false;
			for ($i =0 ; $i < sizeof($arr); $i++) {
				if (preg_match("/^==[ ]+" . wfMsg('steps') . "/", $arr[$i])) {
					$newtext .= $arr[$i];
					$i++;
					if ($i < sizeof($arr)) 
						$newtext .= $arr[$i];
					$newtext = trim($newtext) . $newsection;	
					$found = true;
				} else {
					$newtext .= $arr[$i];
				}
			}
			if (!$found) {
            	$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
				$newtext = "";
				$newtext = trim($arr[0]) . $newsection;
            	for ($i =1 ; $i < sizeof($arr); $i++) {
                    $newtext .= $arr[$i];
                }
			}
		}
		if ($newtext == "")
			$newtext = $newsection;
		$watch = $title->userIsWatching();	
		if ($update)
			$a->updateArticle($newtext,  wfMsg($updateMessage), false, $watch);
		else 
			$a->insertNewArticle($newtext,  wfMsg($updateMessage), false, $watch);

		if ($wgRequest->getVal("popup") == "true") {
			$wgOut->clearHTML();
			$wgOut->disable();
			echo "<script type='text/javascript'>
			function onLoad() {
				var e = document.getElementById('video_text');
				e.value = \"" . htmlspecialchars($tag) . "\";
				pv_Preview();
				var summary = document.getElementById('wpSummary');
				if (summary.value != '') 
					summary.value += ',  " . ($update ? wfMsg('importvideo_changingvideo_summary') :  wfMsg($updateMessage)) . "';
				else
					summary.value = '" . ($update ? wfMsg('importvideo_changingvideo_summary') :  wfMsg($updateMessage)). "';
				closeModal();
			}
			onLoad();
				</script>
				";
		}
		$me = Title::makeTitle(NS_SPECIAL, "Importvideo");
		if ($wgRequest->getVal('wasnew') || $wgRequest->getVal('new')) {
			// log it, we track when someone uploads a video for a new article
			$params = array($title->getArticleID());
			$log = new LogPage( 'vidsfornew', false );
			$log->addEntry('added', $title, 'added');

			$wgOut->redirect($me->getFullURL() . "?new=1&skip=" . $title->getArticleID());
			return;
		} else if ($wgRequest->getVal('category')) {
			// they added a video to a category, keep them in the category mode
			$wgOut->redirect($me->getFullURL() . "?category=" . urlencode($wgRequest->getVal('category')));
			return;
		}
	}

	/***
	 * Parser setup functions, subclasses over ride parseStartElement and parseEndElement 
	 */	
    function parseDefaultHandler ($parser, $data) {
        if ($this->mCurrentTag) {
            if (is_array($this->mCurrentNode)) {
                if (isset($this->mCurrentNode[$this->mCurrentTag])) {
                    $this->mCurrentNode[$this->mCurrentTag] .= $data;
                } else {
                    $this->mCurrentNode[$this->mCurrentTag] = $data;
                }
            } else {
                $this->mResponseData[$this->mCurrentTag] = $data;
            }
        }
	}
    function parseResults($results) {
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, array($this, "parseStartElement"), array($this, "parseEndElement"));
        xml_set_default_handler($xml_parser, array($this, "parseDefaultHandler"));
        xml_parse($xml_parser, $results);
        xml_parser_free($xml_parser);
    }
}

/******
 *  This class is used to grab a description from the user when they insert their video
 */
class ImportvideoPopup extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'ImportvideoPopup' );
	}
	function execute($par) {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		wfLoadExtensionMessages('Importvideo');
        $wgOut->addHTML('<div> <h1>' . wfMsg('importvideo_add_desc') . '</h1>'); 
		$wgOut->addWikiText(wfMsg('importvideo_add_desc_details'));
		if ($wgRequest->wasPosted()) {
			$iv = Title::makeTitle(NS_SPECIAL, "Importvideo");
			$wgOut->addHTML("<form method='POST' name='importvideofrompopup' action='{$iv->getFullURL()}'>");
			$vals = $wgRequest->getValues();
			foreach($vals as $key=>$val) {
				if ($key != "title") {
					$wgOut->addHTML("<input type='hidden' name='{$key}' value=\"" . htmlspecialchars($val) . "\"/>");
				}
			}
      		$wgOut->addHTML(' <p><center><textarea id="importvideo_comment" name="description" style="width:550px; height: 50px;"></textarea></p>
                <br/><br/>
                <p><input type="submit" class="button button100 submit_button" value="' . wfMsg('importvideo_popup_add_desc') . '" style="font-weight:bold; font-size: 1.2em; display:inline; float:none;" /> 
				</p>
            </center>
            </div></form>');
		} else {
			$wgOut->addHTML('<br /><center><p><textarea id="importvideo_comment" style="width:550px; height: 50px;"></textarea></p>
				<br/><br/>
				<input type="button" class="button button100 submit_button" value="' . wfMsg('importvideo_popup_add_desc') . '" style="font-weight:bold; font-size: 1.2em; display:inline; float:none;" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" onclick="throwit()" /> - <a onclick="closeit();">' . wfMsg('importvideo_popup_changearticle') . '</a>
			</center>
			</div></form>
			');
		}
    }
}


/******
 *  This page is used for processing ajax requests to show a video preview in the guided editor
 */
class Previewvideo extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Previewvideo' );
    }

    function execute( $par ) {
        global $wgRequest, $wgParser, $wgUser, $wgOut;


		#$opts = ParserOptions::newFromUser( $wgUser );
		#$opts->setMaxTemplateDepth(10);
        $target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
        $vt = Title::newFromURL($target);
        $t = Title::makeTItle(NS_MAIN, $vt->getText());
		
		# can we parse from the main naemspace article to include the comment?
		$r = Revision::newFromTitle($t);
		$text = $r->getText();
		preg_match("/{{Video:[^}]*}}/", $text, $matches);
		if (sizeof($matches) > 0) {
			$comment = preg_replace("/.*\|/", "", $matches[0]);
			$comment = preg_replace("/}}/", "", $comment);
		}
		$rv = Revision::newFromTitle($vt);
        $text = $rv->getText();
		$text = str_replace("{{{1}}}", $comment, $text);
		#$wgOut->setParserOptions($opts);
        $html = $wgOut->parse($text, true, true) ;
        $wgOut->disable();
        echo $html;
    }
}
     
/******
 * This is a leaderboard for users who are adding videos to new articles
 */
class Newvideoboard extends SpecialPage {


    function __construct() {
        SpecialPage::SpecialPage( 'Newvideoboard' );
    }
    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$sk = $wgUser->getSkin();
		$dbr = &wfGetDB(DB_SLAVE);

		$this->setHeaders();
	
		$wgOut->addHTML('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/Patrolcount.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');	
		
		$me = Title::makeTitle(NS_SPECIAL, "Newvideoboard");
		$now = wfTimestamp(TS_UNIX);

		// allow the user to grab the local patrol count relative to their own timezone	
		if ($wgRequest->getVal('window', 'day') == 'day') {
			$links = "[" . $sk->makeLinkObj($me, wfMsg('videoboard_week'), "window=week") . "] [" . wfMsg('videoboard_day'). "]";
			$date1 = substr(wfTimestamp(TS_MW, $now - 24*3600*7), 0, 8) . "000000";
			$date2 = substr(wfTimestamp(TS_MW, $now + 24*3600), 0, 8) . "000000";
		} else {
			$links = "[" . wfMsg('videoboard_week') . "] [" . $sk->makeLinkObj($me, wfMsg('videoboard_day'), "window=day") . "]";
			$date1 = substr(wfTimestamp(TS_MW), 0, 8) . "000000";
			$date2 = substr(wfTimestamp(TS_MW, $now + 24*3600), 0, 8) . "000000";
		}
	
		$wgOut->addHTML($links);
		$wgOut->addHTML("<br/><br/><table width='500px' align='center' class='status'>" );
	
		$sql = "select log_user, count(*) as C 
				from logging where log_type='vidsfornew' and log_timestamp > '$date1' and log_timestamp < '$date2' 
				group by log_user order by C desc limit 20;";
		$res = $dbr->query($sql);
		$index = 1;
	        $wgOut->addHTML("<tr>
	                       <td></td>
	                        <td>User</td>
	                        <td  align='right'>" . wfMsg('videoboard_numberofvidsadded') . "</td>
	                        </tr>
	        ");
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$u = User::newFromID($row->log_user);
			$count = number_format($row->C, 0, "", ',');
			$class = "";
			if ($index % 2 == 1)
				$class = 'class="odd"';
			$log = $sk->makeLinkObj(Title::makeTitle( NS_SPECIAL, 'Log'), $count, 'type=vidsfornew&user=' .  $u->getName());
			$wgOut->addHTML("<tr $class>
				<td>$index</td>
				<td>" . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td>
				<td  align='right'>{$log}</td>
				</tr>
			");
			$index++;	
		}
		$wgOut->addHTML("</table></center>");
		if ($wgUser->getOption('patrolcountlocal', "GMT") != "GMT")  {
			$wgOut->addHTML("<br/><br/><i><font size='-2'>" . wfMsgWikiHtml('patrolcount_viewlocal_info') . "</font></i>");
		}
	}
}

<?
class ImportvideoYoutube extends Importvideo {

	function addResult($v) {
		//$id, $title, $author_id, $author_name, $keywords) {
		global $wgOut, $wgRequest, $wgImportVideoBadUsers;

            $id = str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $v['ID']);
            $min = min(strlen($v['CONTENT']), 255);
            $snippet = substr($v['CONTENT'], 0, $min);
            if ($min == 255) $snippet .= "...";
            $views = number_format($v['VIEWCOUNT'], 0);

		$keywords = $v['MEDIA:KEYWORDS'];
		$title = $v['TITLE'];
		$author = $v['NAME'];
		$length = $v['LENGTH'];
		$rating = number_format($v['AVGRATERS'], 2);
		$numvotes = $v['NUMRATERS'];	

		
		if ($v['YT:NOEMBED'] == 1 || in_array(strtolower($v['NAME']), $wgImportVideoBadUsers) )  {
			$importOption = wfMsg('importvideo_noimportpossible');
		} else {
			$importOption = "<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='Embed It!' onclick='importvideo(\"{$id}\"); gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>";
		}
		
		
        $wgOut->addHTML("
        <div class='video_result' style='width: 630px;'>
            <div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>Video: {$title}</div>
            <table width='100%'>
                <tr>
                    <td style='text-align:center' rowspan='2'>
						<object width='200' height='200'>
						<param name='movie' value='http://www.youtube.com/v/{$id}&hl=en'></param>
						<param name='wmode' value='transparent' />
						<embed src='http://www.youtube.com/v/{$id}&hl=en' type='application/x-shockwave-flash' wmode='transparent' width='425' height='350'</embed> </object>            
					</td>
				</tr>
				<tr>
                    <td>
                        <b>" . wfMsg('importvideo_rating') . ": </b>{$rating}" . wfMsg('importvideo_votes', $numvotes ) . " <br/><br/>
                        <b>" . wfMsg('importvideo_views') . ": </b>{$views}  <br/><br/>
                        <b>" . wfMsg('importvideo_description') . ": </b>{$snippet}<br /><br />
						{$importOption}
                    </td>
                </tr>
                ");
	   
		$wgOut->addHTML(" </table></div> ");

	}	

    function execute ($par) {
		global $wgRequest, $wgOut;

		#wfLoadExtensionMessages('Importvideo');
		
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ($wgRequest->wasPosted()) {
			// IMPORTING THE VIDEO NOW
			$id = $wgRequest->getVal('video_id');
			$desc = $wgRequest->getVal('description');
			$target = $wgRequest->getVal('target');
        	$url = "http://gdata.youtube.com/feeds/api/videos/$id";
        	$results = $this->getResults($url);
			if ($results == null) {
				$wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
				return;
			}
			$this->parseResults($results);
			$title = Title::makeTitle(NS_VIDEO, $target);
			$v = $this->mResults[0];

			$author = $v['NAME'];
			$badauthors = split("\n", wfMsg('Block_Youtube_Accounts'));;
			if ( in_array( $author, $badauthors) ) {
				$wgOut->addHTML(wfMsg('importvideo_youtubeblocked', $author));
				return;
			}	
			$text = "{{Curatevideo|youtube|$id|{$v['TITLE']}|{$v['MEDIA:KEYWORDS']}|{$v['CONTENT']}|{$v['MEDIA:CATEGORY']}|{$desc}}}
{{VideoDescription|{{{1}}} }}";
			$this->updateVideoArticle($title, $text);
			$this->updateMainArticle($target);
			return;
		}

		$orderby = $wgRequest->getVal('orderby', 'relevance');
		$start = $wgRequest->getVal('start', 1);
	
		if ($target == '') {
			$wgOut->addHTML(wfMsg("importvideo_notarget"));
			return;
		}
		$t = Title::newFromText($target);
		$target = $t->getText();

		$query = $wgRequest->getVal('q');
		if ($query == '') {
			$query = wfMsg('howto', $target);
		}
		$vq = urlencode($query);

		
		$perpage = 10;
		if ($orderby =='howto') 
			$url = "http://gdata.youtube.com/feeds/api/videos/-/Howto?vq=" . urlencode($target) . "&start-index={$start}&max-results=$perpage&format=5";
		else
			$url = "http://gdata.youtube.com/feeds/api/videos?vq=$vq+-expertvillage+-ehow&orderby={$orderby}&start-index={$start}&max-results=$perpage&format=5";
		
		$wgOut->addHTML($this->getPostForm($target));
		#ORDER BY 
		$wgOut->addHTML(" <br/>
			Sort by <select name='orderby' id='orderby' onchange='changeUrl();'>
				<OPTION value='relevance' " . ($orderby == 'relevance' ? "SELECTED" : "") . "> " . wfMSg('importvideo_youtubesort_rel') . "</OPTION>
				<OPTION value='howto' " . ($orderby == 'howto' ? "SELECTED" : "") . "> " . wfMsg('importvideo_youtubesort_howto') . "</OPTION>
				<OPTION value='rating' " . ($orderby == 'rating' ? "SELECTED" : "") . "> " . wfMsg('importvideo_youtubesort_rating') . "</OPTION>
				<OPTION value='published' " . ($orderby == 'published' ? "SELECTED" : "") . "> " . wfMsg('importvideo_youtubesort_rel') . "</OPTION>
				<OPTION value='viewCount' " . ($orderby == 'viewCount' ? "SELECTED" : "") . "> " . wfMsg('importvideo_youtubesort_views') . "</OPTION>
			</select>
			<br/><br/>
			");
		$results = $this->getResults($url);
        if ($results == null) {
        	$wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
			return;
        }
   		$this->parseResults($results);     

	
		#print_r($this->mResults);
		if (sizeof($this->mResults) == 0) {
			#$wgOut->addHTML(wfMsg('importvideo_noresults', $target) . htmlspecialchars($results) );
			$wgOut->addHTML(wfMsg('importvideo_noresults', $query));
			$wgOut->addHTML("</form>");	
			return;
		}


		$wgOut->addHTML(wfMsg('importvideo_results', $query) );

#print_r($this->mResults);

		foreach ($this->mResults as $v) {
			$id = str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $v['ID']);
			$min = min(strlen($v['CONTENT']), 255);
			$snippet = substr($v['CONTENT'], 0, $min);
			if ($min == 255) $snippet .= "...";
			$views = number_format($v['VIEWCOUNT'], 0);
			$this->addResult($v);
		}

	
		$wgOut->addHTML("</form>");	

		$num = $this->mResponseData['OPENSEARCH:TOTALRESULTS'];
		$wgOut->addHTML($this->getPreviousNextButtons($num));
	}

	function parseStartElement ($parser, $name, $attrs) {
		switch ($name) {
			case "MEDIA:THUMBNAIL":
				$this->mCurrentNode['MEDIA:THUMBNAIL'] = $attrs['URL'];
				break;
			case "YT:STATISTICS":
				$this->mCurrentNode['VIEWCOUNT'] = $attrs['VIEWCOUNT'];
				$this->mCurrentNode['FAVORITECOUNT'] = $attrs['FAVORITECOUNT'];
				break;
			case "GD:RATING":
				$this->mCurrentNode['NUMRATERS'] = $attrs['NUMRATERS'];
				$this->mCurrentNode['AVGRATERS'] = $attrs['AVERAGE'];
				break;
			case "YT:DURATION": 
				$this->mCurrentNode['LENGTH'] = $attrs['SECONDS'];
				break;
			case "YT:NOEMBED":
				$this->mCurrentNode['YT:NOEMBED'] = 1;
		}
		if ($name == 'ENTRY') {
			$this->mCurrentNode = array();
		}
		$this->mCurrentTag = $name;
	}

	function parseEndElement ($parser, $name) {
		if ($name == "ENTRY") {
       		$this->mResults[] = $this->mCurrentNode;
            $this->mCurrentNode = null;
		}
		
	}

}


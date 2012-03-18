<?
class ImportvideoHowcast extends Importvideo {


    function parseStartElement ($parser, $name, $attrs) {
       switch ($name) {
            #case "MEDIA:PLAYER":
            #    $this->mCurrentNode['MEDIA:URL'] = $attrs['URL'];
            #    break;
        }
        if ($name == 'VIDEO') {
            $this->mCurrentNode = array();
        }
        $this->mCurrentTag = $name;
    }

    function parseEndElement ($parser, $name) {
        if ($name == "VIDEO") {
            $this->mResults[] = $this->mCurrentNode;
            $this->mCurrentNode = null; 
        }       
    }    
	function execute($par) {
		global $wgOut, $wgRequest, $wgHowcastAPIKey;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

        if ($wgRequest->wasPosted()) {
            // IMPORTING THE VIDEO NOW
            $id = $wgRequest->getVal('video_id');
			$desc = $wgRequest->getVal('desc');
            $url = "http://www.howcast.com/videos/{$id}.xml?api_key={$wgHowcastAPIKey}";
            $results = $this->getResults($url);
            if ($results == null) {
                $wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
                return;
            }

			# this is retarded, lets remove the related-videos for shits and giggles 
			$bad = array("markers", "related-videos");
			foreach ($bad as $b) {
				$parts = preg_split("@(<[/]?$b>)@i", $results, 0, PREG_SPLIT_DELIM_CAPTURE  | PREG_SPLIT_NO_EMPTY);
				$newresults = "";

				while (sizeof($parts) > 0) {
					$p = array_shift($parts);
					if ($p == "<$b>") {
						while ($p != "</$b>" && sizeof($parts) > 0) {
							$p = array_shift($parts);
						}
						break;
					}
					$newresults .= $p;
				}
				$newresults .= implode($parts);
				$results = $newresults; 
			}

            $this->parseResults($results);
            $title = Title::makeTitle(NS_VIDEO, $target);
			foreach ($this->mResults as $r) {
				if (trim($r['ID']) == $id) {
					$v = $r;
				}
			}
			#echo $v['TITLE']; exit; echo $id; print_r($this->mResults); print_r($v); exit;
			$titletext = trim($v['TITLE']);
			$tags = trim($v['TAGS']);
            $text = "{{Curatevideo|howcast|$id|{$titletext}|{$tags}|{$v['DESCRIPTION']}|{$v['CATEGORY']}|{$desc}}}
{{VideoDescription|{{{1}}} }}";
			$this->updateVideoArticle($title, $text);
            $this->updateMainArticle($target);
            return;
        }

		$t = Title::newFromText($target);
		$target = $t->getText();
        $tar_es = urlencode($target);
		$query = $wgRequest->getVal('q');
		if ($query == '') $query = $tar_es; 
		else $query = urlencode($query);
		
		$url = "http://www.howcast.com/search.xml?q={$query}&view=video&api_key={$wgHowcastAPIKey}";
        $results = $this->getResults($url);
        $this->parseResults($results);
        if ($results == null) {
            $wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
            return;
         }
		#print_r($this->mResults );
		#echo sizeof($this->mResults);

		$wgOut->addHTML($this->getPostForm($target));

        if (!is_array($this->mResults) || sizeof($this->mResults) == 0) {
            $wgOut->addHTML(wfMsg('importvideo_noarticlehits'));
			$wgOut->addHTML("</form>");
            return;
        } 
		$resultsShown = false;
     	foreach ($this->mResults as $v) {
			if (!$this->isValid($v['CREATED-AT'])) {
				continue;
			}
			$resultsShown = true;
            $this->addResult($v);
        }
        if (!$resultsShown) {
            $wgOut->addHTML(wfMsg('importvideo_noarticlehits'));
			$wgOut->addHTML("</form>");
            return;
        } 
		$wgOut->addHTML("</form>");
	}

    function addResult($v) {
        //$id, $title, $author_id, $author_name, $keywords) {
        global $wgOut, $wgRequest;

        $id = trim($v['ID']);
        $min = min(strlen($v['DESCRIPTION']), 255);
        $snippet = substr($v['DESCRIPTION'], 0, $min);
        if ($min == 255) $snippet .= "...";
        $views = number_format($v['VIEWCOUNT'], 0);
        $views = $v['VIEWS'];
	$title = $v['TITLE'];
	$vid	= $v['EMBED'];
	$vid = preg_replace("/<embed/","<param value=\"transparent\" name=\"wmode\"/><embed wmode=\"transparent\" ", $vid);


        $wgOut->addHTML("
        <div class='video_result'>
            <div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>" . wfMsg('video') . ": {$title}</div>
            <table width='100%'>
                <tr>
                    <td style='text-align:center'>{$vid}</td>
				</tr>
				<tr>
                    <td>
                        <b>" . wfMsg('importvideo_views') . ": </b>{$views}  <br/><br/>
                        <b>" . wfMsg('importvideo_description') . ": </b>{$snippet}<br /><br />
						<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='" . wfMsg('importvideo_embedit') . "' onclick='importvideo(\"{$id}\"); gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>
                    </td>
                </tr>
            </table>
        </div>
            ");
    }

}

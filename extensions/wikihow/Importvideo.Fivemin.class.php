<?
class ImportvideoFivemin extends Importvideo {

	function execute($par) {
		global $wgRequest, $wgOut;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$t = Title::newFromText($target);
		$target = $t->getText();
        $query = $wgRequest->getVal('q');
        if ($query == '') {
            $query = wfMsg('howto', $target);
        }

        if ($wgRequest->wasPosted()) {
            // IMPORTING THE VIDEO NOW
            $id = $wgRequest->getVal('video_id');
			# the description entered by the user
			$desc = $wgRequest->getVal('desc');
            $target = $wgRequest->getVal('target');
            $url = "http://www.5min.com/rss/video/{$id}?restriction=no_html";
            $results = $this->getResults($url);
            if ($results == null) {
                $wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
                return;
            }
            $this->parseResults($results);
            $title = Title::makeTitle(NS_VIDEO, $target);
            $v = $this->mResults[0];
			$tt = trim(preg_replace("/\n.*/", "", $v['TITLE']));
			$description = trim(preg_replace("/\n.*/", "", $v['DESCRIPTION']));
			$kw = trim($v['MEDIA:KEYWORDS']);
			$cat = trim($v['MEDIA:CATEGORY']);
            $text = "{{Curatevideo|5min|$id|{$tt}|{$kw}|{$description}|{$cat}|{$desc}}}
{{VideoDescription|{{{1}}} }}";
			$this->updateVideoArticle($title, $text);
			$this->updateMainArticle($target);
            return;
        }

        $vq = urlencode($query);

		$start = $wgRequest->getVal('start', 1);
		$page = $start > 1 ? "&page=" . ((($start -1 ) / 10) + 1) : "";
		$url = "http://api.5min.com/search/{$vq}/videos.xml?num_of_videos=10{$page}&sid=102";
		$tar_es = htmlspecialchars($target);
		$results = $this->getResults($url);
		$this->parseResults($results);
        if ($results == null) {
        	$wgOut->addHTML(wfMsg("importvideo_error_geting_results"));
            return;
         }
		
		$wgOut->addHTML($this->getPostForm($target));

		$new1 = array();
		if (is_array($this->mResults)) {
			foreach ($this->mResults as $v) {
				$title = trim(preg_replace("/\n.*/", "", $v['TITLE']));
				if (strpos($title, "There are no items in this feed for now") === false) 	
					$new1[]  = $v;
			}
		}
		$this->mResults = $new1;
		if (sizeof($this->mResults) == 0) {
			$wgOut->addHTML(wfMsg('importvideo_noresults', $query));
			$wgOut->addHTML("</form>");
			return;
		}
		foreach ($this->mResults as $v) {
			$id = $url = $v['MEDIA:URL'];
			$credit = htmlspecialchars_decode($v['MEDIA:CREDIT']);
			$keywords = str_replace(",", ", ", $v['MEDIA:KEYWORDS']);
			$title = trim(preg_replace("/\n.*/", "", $v['TITLE']));
			$id = preg_replace("/.*-([0-9]+)/im", "$1", $id);
			$snippet = strip_tags(htmlspecialchars_decode($v['MEDIA:DESCRIPTION']));
			$vid = $v['MEDIA:PLAYER'];
			$vid = preg_replace("/<embed/","<param value=\"transparent\" name=\"wmode\"/><embed wmode=\"transparent\" ", $vid);
        	$wgOut->addHTML("
        <div class='video_result'>
            <div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>" .wfMsg('video') . ": {$title}</div>
            <table width='100%'>
                <tr>
                    <td colspan='2' style='text-align:center'>{$vid}
					</td>
				</tr>
				<tr>
                    <td valign='top' style='padding-left: 4px; text-align: justify;'>
                        <b>" . wfMsg('importvideo_description') . ": </b>{$snippet}<br /><br />
						<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='" . wfMsg('importvideo_embedit') . "' onclick='importvideo(\"{$id}\");gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>
                    </td>
                </tr>
            </table>
        </div>
            ");
		}

		$wgOut->addHTML("</form>");
       
		$wgOut->addHTML($this->getPreviousNextButtons());
	}

	function parseStartElement ($parser, $name, $attrs) {
       switch ($name) {
            case "MEDIA:PLAYER":
                $this->mCurrentNode['MEDIA:URL'] = $attrs['URL'];
                break;
		}
		if ($name == 'ITEM') {
			$this->mCurrentNode = array();
		}
		$this->mCurrentTag = $name;
	}

	function parseEndElement ($parser, $name) {
		if ($name == "ITEM") {
       		$this->mResults[] = $this->mCurrentNode;
            $this->mCurrentNode = null;
		}
	}

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
}


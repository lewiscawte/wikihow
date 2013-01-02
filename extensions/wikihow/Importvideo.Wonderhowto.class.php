<?
class ImportvideoWonderhowto extends Importvideo {


    function parseStartElement ($parser, $name, $attrs) {
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
	function execute($par) {
		global $wgOut, $wgRequest;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );


        if ($wgRequest->wasPosted()) {
            // IMPORTING THE VIDEO NOW
            $id = $wgRequest->getVal('video_id');
			$desc = $wgRequest->getVal('desc');
            $url = "http://www.wonderhowto.com/search.aspx?vid={$id}&t=mrss&m=v";
            $results = $this->getResults($url);
            if ($results == null) {
            	$wgOut->addHTML(wfMsg("importvideo_error_geting_results") . "<br/><br/>{$url}");
                return;
            }
            $this->parseResults($results);
            $title = Title::makeTitle(NS_VIDEO, $target);
            $v = $this->mResults[0];
			$safe = str_replace("=", "&61;", $v['MEDIA:TEXT']);
			$safe = preg_replace('%height&61;[\'"][0-9]+[\'"]%', 'height&61;"350"', $safe); 
			$safe = preg_replace('%width&61;[\'"][0-9]+[\'"]%', 'width&61;"425"', $safe); 
            $text = "{{Curatevideo|wonderhowto|{$safe}|{$v['TITLE']}|{$v['TAGS']}|{$v['DESCRIPTION']}|{$v['CATEGORY']}|{$desc}}}
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

		$start = $wgRequest->getVal('start', 1); 	
		$url = "http://www.wonderhowto.com/search.aspx?t=mrss&m=v&q={$query}&rpp=20&p=" . ($start -1) / 10;
        $results = $this->getResults($url);
        $this->parseResults($results);
        if ($results == null) {
            $wgOut->addHTML(wfMsg("importvideo_error_geting_results") . "<br/><br/>{$url}");
            return;
         }
		
		$wgOut->addHTML($this->getPostForm($target)); 

        if (!is_array($this->mResults) || sizeof($this->mResults) == 0) {
            $wgOut->addHTML(wfMsg('importvideo_noarticlehits'));
			$wgOut->addHTML("</form>");
            return;
        } 
		$count = 0;
     	foreach ($this->mResults as $v) {
            $this->addResult($v);
			$count++;
			if ($count == 10) break;
        }
		$wgOut->addHTML("</form>");
        $wgOut->addHTML($this->getPreviousNextButtons());
	}

    function addResult($v) {
        //$id, $title, $author_id, $author_name, $keywords) {
        global $wgOut, $wgRequest;

        $id = preg_replace("/.*-/", "", trim($v['GUID']));
        $id = preg_replace("/\//", "", $id);
        $min = min(strlen($v['MEDIA:DESCRIPTION']), 255);
        $snippet = substr($v['MEDIA:DESCRIPTION'], 0, $min);
        if ($min == 255) $snippet .= "...";
        $views = number_format($v['FEEDBACK:VIEWS'], 0);
		$grade = trim($v['FEEDBACK:GRADE']) . "%";

		$title = $v['TITLE'];
		$vid = $v['MEDIA:TEXT'];
		$vid = preg_replace('%height=[\'"][0-9]+[\'"]%', 'height="350"', $vid); 
		$vid = preg_replace('%width=[\'"][0-9]+[\'"]%', 'width="425"', $vid); 

        $wgOut->addHTML("
        <div class='video_result'>
			<div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>Video: {$title}</div>
			<table width='100%'>
				<tr>
					<td style='text-align:center'>{$vid}</td>
				</tr>
				<tr>
					<td>
						<b>" . wfMsg('importvideo_rating') . ": </b>{$grade}  " . wfMsg('importvideo_rating_wonderhowtodesc') . " <br/><br/>
						<b>" . wfMsg('importvideo_views') . ": </b>{$views}	<br/><br/>
						<b>" . wfMsg('importvideo_description') . ": </b>{$snippet}<br /><br />
						<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='" . wfMsg('importvideo_embedit') . "' onclick='importvideo(\"{$id}\"); gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>
					</td>
				</tr>
			</table>
		</div>
            ");
	}
}

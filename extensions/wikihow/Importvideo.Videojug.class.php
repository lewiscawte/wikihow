<?
class ImportvideoVideojug  extends Importvideo {

	function addResult($v) {
		global $wgOut;
		$id 		= $v['ID'];
		$url 		= $v['ABSOLUTEURL'];
		$snippet 	= $v['DESCRIPTION'];
		$title 		= $v['TITLE'];
		$views 		= number_format($v['VIEWINGCOUNT'], 0, "", ",");	

        $wgOut->addHTML("
        <div class='video_result'>
            <div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>" . wfMsg('video') . ": {$title}</div>
            <table width='100%'>
                <tr>
                    <td style='text-align:center'>
						<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0' id='vjplayer03092008' width='425' height='350' align='middle' allowFullScreen='true'>
					<param name='movie' value='http://www.videojug.com/film/player?id={$id}' />
					<PARAM value='true' name='allowFullScreen' />
					<PARAM value='always' name='allowScriptAccess' />
					<PARAM value='transparent' name='wmode' />
					<embed src='http://www.videojug.com/film/player?id={$id}' quality='high' width='425' height='350' wmode='transparent' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' allowscriptaccess='always' allowfullscreen='true'>
				</embed>
</object>
				</td>
				</tr>
				<tr>
                    <td>
                        <b>" . wfMsg('importvideo_views') . ": </b>{$views}  <br/><br/>
                        <b>" . wfMsg('importvideo_description') . ": </b>{$snippet}<br /><br />
						<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='" . wfMsg('importvideo_embedit') . "' onclick='importvideo(\"{$id}\");gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>
                    </td>
                </tr>
            </table>
        </div>
            ");

	}

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
            $url = "http://www.videojug.com/Services/ContentDiscovery.svc/ContentById?Id={$id}";
            $results = $this->getResults($url);
            $this->parseResults($results);

            $v = $this->mResults[0];
            $url        = $v['ABSOLUTEURL'];
            $image      = $v['ABSOLUTEMASTERIMAGEURL'];
            $snippet    = $v['DESCRIPTION'];
            $t		      = $v['TITLE'];
            $views      = number_format($v['VIEWINGCOUNT'], 0, "", ",");

            $title = Title::makeTitle(NS_VIDEO, $target);
            $text = "{{Curatevideo|videojug|{$id}|{$t}||{$snippet}|{$url}|{$desc}}}
{{VideoDescription|{{{1}}} }}";
			$this->updateVideoArticle($title, $text);
            $this->updateMainArticle($target);
            return;
		}

		$orderby = $wgRequest->getVal('orderby', 'relevance');
		$start = $wgRequest->getVal('start', 1);
		$page = floor($start / 5);
		$url= "http://www.videojug.com/Services/ContentDiscovery.svc/Search?keywords=" . urlencode($query) . "&tag=vj-home"
				."&contentType=Film&edition=US%20edition&sortBy=Relevance&ascending=false&pageSize=10&pageNumber=$page"	;
		$results = $this->getResults($url);
   		$this->parseResults($results);     
	
		$wgOut->addHTML($this->getPostForm($target));	

        if (!is_array($this->mResults) || sizeof($this->mResults) == 0) {
        	$wgOut->addHTML(wfMsg('importvideo_noarticlehits'));
			$wgOut->addHTML("</form>");
            return;
        }
        foreach ($this->mResults as $v) {
			$this->addResult($v);
		}

		// Previous, Next buttons if necessary
		$num_url = "http://www.videojug.com/Services/ContentDiscovery.svc/SearchCount?keywords=" . urlencode($query) . "&tag=vj-home&contentType=Film&edition=US%20edition" ;
        $num = strip_tags($this->getResults($num_url));
		$wgOut->addHTML("</form>");
		$wgOut->addHTML($this->getPreviousNextButtons($num));
	}

    function parseStartElement ($parser, $name, $attrs) {
       switch ($name) {
        }
        if ($name == 'TAGGEDCONTENTVIEW') {
            $this->mCurrentNode = array();
        }
        $this->mCurrentTag = $name;
    }

    function parseEndElement ($parser, $name) {
        if ($name == "TAGGEDCONTENTVIEW") {
            $this->mResults[] = $this->mCurrentNode;
            $this->mCurrentNode = null;
        }
    }

}


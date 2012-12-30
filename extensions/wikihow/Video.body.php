<?

class RelatedVideoApi {

	var $mContent		= null;
	var $mNumResults	= null;
	var $mTitle			= null;

	var $mResponseData	= array();
	var $mCurrentNode	= null;
	var $mResults		= array();
	var $mCurrentTag	= array();
	var $mNodeStack		= array();

	function __construct($t) {
		$this->mTitle = $t;
	}
	function getVideoSrc (){
		return null;
	}

	function loadFromDB() {
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow('video_data',
				array('vd_data'),
				array('vd_id' => $this->mId)
			);
		$this->mContent = $row->vd_data;
		$this->parseResults($this->mContent);
	}
	function get($key) {
		return $this->mResults[0][strtoupper($key)];
	}

	function getResults($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$contents = curl_exec($ch);
		if (curl_errno($ch)) {
			# error
		} else {
		}
		curl_close($ch);
		return $contents;
	}

	function getRelatedVideoPanel($vids, $limit = 0) {
		$related_vids = "<h3>Related Videos</h3><table class='related_vids_display'><tr valign='top'>\n";
		$count = 0;
		foreach ($vids as $v) {
			$thumb = "<img class='rounders2_img' src='{$v['thumb']}' height='35' width='45'>";
			switch ($v['source']) {
				case 'fivemin':
					$url = "/video/5min/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']);
					break;
				default:
					$url = "/video/wht/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']);
			}
			$related_vids  .= "<td><a href='{$url}' class='rounders2 rounders2_ml rounders2_white'>
	{$thumb}
	<img src='" . wfGetPad('/skins/WikiHow/images/play_thumb.png') . "' class='video_thumb_play'/>
	<img class='rounders2_sprite' alt='' src='" . wfGetPad('/skins/WikiHow/images/corner_sprite.png') . "'/></a>
	<a href='{$url}'>{$v['title']}</a></td>\n";
			$count++;
			if ($count % 2 == 0)
				$related_vids .= "</tr><tr>";
				if ($limit > 0 && $count >= $limit) break;
		}
		$related_vids .= "</tr></table>";
		return $related_vids;
	}


	function parseResults($results) {
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this,"parseStartElement"), array($this,"parseEndElement"));
		xml_set_default_handler($xml_parser, array($this,"parseDefaultHandler"));
		xml_parse($xml_parser, $results);
		xml_parser_free($xml_parser);
	}

	function parseDefaultHandler ($parser, $data) {
		if ($this->mCurrentTag) {
			if (is_array($this->mCurrentNode)) {
				if (isset($this->mCurrentNode[$this->mCurrentTag])) {
					$this->mCurrentNode[$this->mCurrentTag].= $data;
				} else {
					$this->mCurrentNode[$this->mCurrentTag] = $data;
				}
			} else {
				$this->mResponseData[$this->mCurrentTag] = $data;
			}
		}
	}

	function deleteVideoLinks($article_id, $video_source) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query("delete from video_links where vl_page={$article_id} and vl_source='{$video_source}'");
	}

	function insertVideoTitle($id, $title, $data, $source, $thumb = '') {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query('INSERT INTO video_titles (vt_id, vt_title, vt_source, vt_thumb) VALUES ('
				. "'$id', "
				. $dbw->addQuotes(trim($title))
				. ", '$source' "
				. ", " . $dbw->addQuotes($thumb)
				. ") ON DUPLICATE KEY UPDATE vt_title= " . $dbw->addQuotes(trim($title))
				.";"
		);
		$dbw->query("DELETE from video_data where vd_id='{$id}' and vd_source='{$source}';");
		$dbw->query("INSERT INTO video_data (vd_id, vd_source, vd_data) VALUES ('{$id}', '{$source}', " . $dbw->addQuotes($data) .");"
		);
	}

	function insertVideoLinks($article_id, $video_id, $index, $source, $title, $thumb) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('video_links',
			array(
				'vl_page'   => $article_id,
				'vl_id'     => $video_id,
				'vl_result' => $index,
				'vl_source' => $source,
				'vl_thumb'  => $thumb,
				'vl_title'  => $title,
			 )
		);
	}
}

class VideojugApi extends RelatedVideoApi {

	function execute() {
		$target = wfMsg('howto', $this->mTitle->getText());
		$url = "http://www.videojug.com/Services/ContentDiscovery.svc/Search?keywords=" . urlencode($target) . "&tag=vj-home"
				."&contentType=Film&edition=US%20edition&sortBy=Relevance&ascending=false&pageSize=10";
		$results = $this->getResults($url);
		$this->parseResults($results);
		$this->mNumResults = sizeof($this->mResults);
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

	function storeResults() {
		if ($this->mNumResults < 0) {
			echo "Error: error retrieving results for {$this->mTitle->getFullText()}\n";
			return;
		}
		$dbw = wfGetDB(DB_MASTER);
		$xml = $this->mNumResults == 0 ?  "" : $this->mContent;
		#$xml = "Testing..";
		if ($this->mResults == 0) return;

		$this->deleteVideoLinks($this->mTitle->getArticleID(), 'videojug');

		$index = 1;
		foreach ($this->mResults as $r) {
			$url = $r['LINK'];
			$id = trim(substr($url, strrpos($url, "-") + 1));
			$id = str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $r['ID']);
			$video = new VideojugVideo($id);
			$video->getData();

			$this->insertVideoTitle ($id, trim($r['TITLE']), $video->mContent, 'videojug');
			$this->insertVideoLinks ($this->mTitle->getArticleID(), $id, $index, 'videojug');
			$index++;
		}
	}

}

class VideojugVideo extends FiveMinApi {

	public $mId;

	function __construct($id, $content = null) {
		$this->mId = $id;
		$this->mContent = $content;
		if ($this->mContent != null) {
			$this->parseResults($this->mContent);
		}
	}

	function getData() {
		$url = "http://www.videojug.com/Services/ContentDiscovery.svc/ContentById?Id={$this->mId}";
		$this->mContent = $this->getResults($url);
		#$this->parseResults();
	}

	function getURL($title) {
		return urlencode(strtolower(str_replace(" ", "-", $title)));
	}

	function getDescription() {
		#$desc  = strip_tags(htmlspecialchars_decode($desc));
		$desc = $this->mResponseData['DESCRIPTION'];
		return $desc;
	}

	function getThumb() {
		$thumb = null;
		preg_match_all('@<media:thumbnail[^>]*>@im', $this->mContent, $matches);
		if (sizeof($matches[0]) > 0) {
			$ix = sizeof($matches[0]) - 1;
			$match= $matches[0][$ix];
			$thumb = "<img src=" . preg_replace("@<media:thumbnail url=@im", "", $match);
		}
		return $thumb;
	}

	function getVideo () {
		return '<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' . $this->mId . '" />
<param name="allowfullscreen" value="true" />
<param name="wmode" value="transparent" />
<embed src="http://www.youtube.com/v/' . $this->mId . '" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350" /></object>';
	}

}


class YoutubeApi extends RelatedVideoApi {

	function execute() {
		$target = wfMsg('howto', $this->mTitle->getText());
		#$url = "http://gdata.youtube.com/feeds/api/videos/-/Howto?vq=" . urlencode($target) . "&start-index=1&max-results=10&format=5";
		$url = "http://gdata.youtube.com/feeds/api/videos?vq=" . urlencode($target) . "&orderby=relevance&start-index=1&max-results=5&format=5" ;
		$results = $this->getResults($url);
		$this->parseResults($results);
		$this->mNumResults = sizeof($this->mResults);
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

	function storeResults() {
		if ($this->mNumResults < 0) {
			echo "Error: error retrieving results for {$this->mTitle->getFullText()}\n";
			return;
		}
		$dbw = wfGetDB(DB_MASTER);
		$xml = $this->mNumResults == 0 ?  "" : $this->mContent;
		#$xml = "Testing..";
		if ($this->mResults == 0) return;

		$this->deleteVideoLinks($this->mTitle->getArticleID(), 'youtube');

		$index = 1;
		foreach ($this->mResults as $r) {
			$url = $r['LINK'];
			$id = trim(substr($url, strrpos($url, "-") + 1));
			if ($r['TITLE'] == null) {
				echo "error -------- {$this->mTitle->getFullText()} ---\n\n";
				print_r($r);
				echo "error -------- {$this->mTitle->getFullText()} ---\n\n";
				continue;
			}
			if ($r['AVGRATERS'] < 4.0) {
				echo "Got a video with {$r['AVGRATERS']} rating..skipping...\n";
				continue;
			}
			$id = str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $r['ID']);
			$video = new YoutubeApiVideo($id);
			$video->getData();

			$this->insertVideoTitle($id, trim($r['TITLE']), $video->mContent, 'youtube');
			$this->insertVideoLinks ($this->mTitle->getArticleID(), $id, $index, 'youtube');

			$index++;
		}
	}

}


class YoutubeApiVideo extends FiveMinApi {

	public $mId;

	function __construct($id, $content = null) {
		$this->mId = $id;
		$this->mContent = $content;
		if ($this->mContent != null) {
			$this->parseResults($this->mContent);
		}
	}

	function getData() {
		$url = "http://gdata.youtube.com/feeds/api/videos/{$this->mId}";
		$this->mContent = $this->getResults($url);
		#$this->parseResults();
	}

	function getURL($title) {
		return urlencode(strtolower(str_replace(" ", "-", $title)));
	}

	function getDescription() {
		#$desc = strip_tags(htmlspecialchars_decode($desc));
		$desc = $this->mResponseData['MEDIA:DESCRIPTION'];
		return $desc;
	}

	function getThumb() {
		$thumb = null;
		preg_match_all('@<media:thumbnail[^>]*>@im', $this->mContent, $matches);
		if (sizeof($matches[0]) > 0) {
			$ix = sizeof($matches[0]) - 1;
			$match= $matches[0][$ix];
			$thumb = "<img src=" . preg_replace("@<media:thumbnail url=@im", "", $match);
		}
		return $thumb;
	}

	function getVideo () {
		return '<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' . $this->mId . '" />
<param name="allowfullscreen" value="true" />
<param name="wmode" value="transparent" />
<embed src="http://www.youtube.com/v/' . $this->mId . '" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350" /></object>';
	}

}

class FiveMinApi extends RelatedVideoApi {

	function recordNumResults($t, $num) {
		$f = array_shift($this->mResults);
		$wgOut->addHTML("<center><div><b>{$f['TITLE']}</b><br/>{$f['MEDIA:PLAYER']}</div></center>");

		$wgOut->addHTML("<table width='100%'>");
		foreach ($this->mResults as $v) {
			$wgOut->addHTML("<tr><td>" . htmlspecialchars_decode($v['MEDIA:DESCRIPTION']) . "</td></tr>");
		}
		$wgOut->addHTML("</table>");
	}

	function parseStartElement ($parser, $name, $attrs) {
		switch ($name) {
			case "MEDIA:THUMBNAIL":
				$this->mCurrentNode['MEDIA:THUMBNAIL'] = $attrs['URL'];
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

	function formatLeaf ($leaf) {
		$t = Title::newFromText($leaf);
		return $t->getText();
	}

	function flattenCategoryTree($tree) {
		if (is_array($tree)) {
			$results = array();
			foreach ($tree as $key=>$value) {
				if (trim($key) != '') $results[] = $this->formatLeaf($key);
				$x = $this->flattenCategoryTree($value);
				if (is_array($x))
					$results =  array_merge($results, $x);
			}
		} else {
			#$results = array();
			if (trim($tree) != '') $results[] = $this->formatLeaf($tree);
			return $results;
		}
		return $results;
	}

	function execute($t) {
		global $wgFiveMinCategoryMap;
		$tree = $t->getParentCategoryTree();
		$tree = $this->flattenCategoryTree($tree);

		$cats = "";
		foreach ($tree as $cat) {
			if (isset($wgFiveMinCategoryMap[$cat])) {
				$cats = "&categories_list=" . $wgFiveMinCategoryMap[$cat];
				break;
			}
		}
		$url = "http://api.5min.com/videoseed/videos.xml?url=http://www.wikihow.com/{$t->getPrefixedURL()}&sid=102{$cats}&num_of_videos=4&restriction=no_html&sid=102&autoStart=false";
		$this->mContent = $this->getResults($url);
		$this->mContent = preg_replace('@<image>(.|\n)*</image>@mU', "",$this->mContent);
		$this->mNumResults = -1;
		$this->mTitle = $t;
		$this->parseResults($this->mContent);
		if (sizeof($this->mResults) == 1 && trim($this->mResults[0]['DESCRIPTION']) == 'There are no items in this feed for now') {
			$this->mNumResults = 0;
			return;
		} else {
			$this->mNumResults = sizeof($this->mResults);
		}
	}

	function loadResults() {
		$dbr = wfGetDB(DB_SLAVE);
		$this->mContent = $dbr->selectField('fivemin_api', array('vt_xml'), array('vl_page' => $this->mTitle->getArticleID()));
		$this->mContent = preg_replace('@<image>(.|\n)*</image>@mU', "",$this->mContent);
		$this->parseResults($this->mContent);
	}

	function storeResults() {
		if ($this->mNumResults < 0) {
			echo "Error: error retrieving results for {$this->mTitle->getFullText()}\n";
			return;
		}
		$dbw = wfGetDB(DB_MASTER);
		$xml = $this->mNumResults == 0 ?  "" : $this->mContent;
		#$xml = "Testing..";
		if (sizeof($this->mResults) == 1 && trim($this->mResults[0]['DESCRIPTION']) == 'There are no items in this feed for now') {
			$this->mNumResults = 0;
			return;
		}
		if ($this->mResults == 0) return;

		$this->deleteVideoLinks($this->mTitle->getArticleID(), 'fivemin');

		$index = 1;
		foreach ($this->mResults as $r) {
			$url = $r['LINK'];
			$id = trim(substr($url, strrpos($url, "-") + 1));
			if ($r['TITLE'] == null) {
				echo "error -------- {$this->mTitle->getFullText()} ---\n\n"; print_r($r);
				continue;
			}
			$video = new FiveMinApiVideo(trim($r['ID']));
			$video->getData();

			$this->insertVideoTitle($id, trim($r['TITLE']), $video->mContent, 'fivemin', $r['MEDIA:THUMBNAIL']);
			$this->insertVideoLinks ($this->mTitle->getArticleID(), $id, $index, 'fivemin', trim($r['TITLE']), $r['MEDIA:THUMBNAIL']);
			$index++;
		}
	}

}

class FiveMinApiVideo extends FiveMinApi {

	public $mId;

	function __construct($id, $content = null) {
		$this->mId = $id;
		$this->mContent = $content;
	}



	function getData() {
		$url = "http://api.5min.com/video/{$this->mId}/info.xml?sid=102";
		$this->mContent = $this->getResults($url);
		#$this->parseResults();
	}

	function getURL($title) {
		return urlencode(strtolower(str_replace(" ", "-", trim($title))));
	}

	function getDescription () {
		$desc	= preg_replace("@(.|\n)*<description>@im", "", $this->mContent);
		$desc	= preg_replace("@</description>(\n|.)*@im", "", $desc);
		$desc	= strip_tags(htmlspecialchars_decode($desc));
		return $desc;
	}

	function getThumbURL() {
		return trim($this->mResults[0]['MEDIA:THUMBNAIL']);
		return $thumb;
	}
	function getThumb() {
		$thumb	= preg_replace("@<media:thumbnail url=\"@im", "", $this->mContent);
		$thumb	= preg_replace("@\".*@im", "", $thumb);
		return "<img src='$thumb' width='150' height='100'>";
	}

	function getVideo() {
		preg_match('@<media:player.*</media:player>@m', $this->mContent, $matches);
		$data = $matches[0];
		$data = preg_replace("@<media:player [^>]*><\!\[CDATA\[@", "", $data);
		$data = preg_replace("@\]\]></media:player>$@", "", $data);
		$data = preg_replace("@<@m", "\n<", $data);
		$data = preg_replace("@<a href=.*>(.|\n)*</a>@imU", "", $data);
		$data = preg_replace("@<param name='movie' value='http://www.5min.com/Embeded/([0-9]+)/'/>@",
					"<param name='movie' value='http://www.5min.com/Embeded/$1/&sid=102'/>",
					$data);
		return $data;
	}

}

class RelatedVideos {

	var $mTitle;
	var $mResults = array();
	var $mLoaded = false;
	var $mSource = null;

	function __construct($title, $source = null) {
		$this->mTitle = $title;
		$this->mSource = $source;
	}

	function hasResults() {
		$this->getResults();
		return sizeof($this->mResults) > 0;
	}

	function getResults() {
		global $wgMemc;
		wfProfileIn("Relatedvideos::getResults");

		if ($this->mLoaded) return $this->mResults;

		$key = "Relatedvideos:" . $this->mTitle->getArticleId();
		if ($this->mSource)
			$key = "Relatedvideos:{$this->mSource}{$this->mTitle->getArticleId()}";

		if ( $wgMemc->get($key) && false) {
			$this->mLoaded = true;
			$this->mResults =   $wgMemc->get($key);
			return $this->mResults;
		}

		$dbr = wfGetDB(DB_SLAVE);

		$src = $this->mSource != null ? " AND vl_source ='{$this->mSource}' " : "";
		if (is_array($this->mSource)) {
			$src = " AND vl_source IN ('" . implode("','", $this->mSource) . "') ";
		}

		$sql = "SELECT vl_title, vl_id , vl_thumb, vl_source
				FROM video_links WHERE
				vl_page = {$this->mTitle->getArticleId()}
				{$src} "
				#. " AND vb_id is null and vr_id is null "
				. " ORDER BY vl_result LIMIT 5";

		$res = $dbr->query($sql, "Relatedvideos::getResults");
		while ($row = $dbr->fetchObject($res)) {
			$hit = array();
			$hit['id'] = $row->vl_id;
			$hit['title']   = $row->vl_title;
			$hit['thumb']   = $row->vl_thumb;
			$hit['source']	= $row->vl_source;
			$this->mResults[] = $hit;
		}
		$this->mLoaded = true;

		$wgMemc->set($key, $this->mResults, time() + 3600);

		wfProfileOut("Relatedvideos::getResults");
		return $this->mResults;
	}

	function remove($title, $id) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('video_removedlinks',
				array (
					'vr_page'	=> $title->getArticleID(),
					'vr_id'		=> $id,
					'vr_user'	=> $wgUser->getId(),
					'vr_user_text' => $wgUser->getName(),
					'vr_timestamp' => wfTimestampNow(TS_MW),
				)
			);
	}

}

class Video extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Video' );
	}

	function getTarget() {
		global $wgRequest;
		$target = str_replace("Special:Video/", "", $wgRequest->getVal('title'));
		if (strpos($target, "wht/") === 0) {
			$target = substr($target, 4);
		}
		if (strpos($target, "/") > 0) {
			$len = strlen($target);
			$target = substr($target, 0, strpos($target, "/"));
		}
		return $target;
	}

	function getRelatedVideos($target = null) {
		global $wgMemc;

		if (!$target)
			$target = Video::getTarget();
		$key = "Related_videos_video_id_{$target}";

		if ( $wgMemc->get($key) && false) {
			return $wgMemc->get($key);
		}

		$dbr = wfGetDB(DB_SLAVE);

		/// RELATED VIDEOS
		$related_videos = array();
		$related_articles = Video::getRelatedArticles(10);
		$related_ids = array();
		foreach ($related_articles as $r) {
			$related_ids[] = $r->getArticleID();
		}
		$ids = array();
		if (sizeof($related_ids) > 0) {
			$sql = "SELECT vt_id, vt_title, vt_source, vt_thumb, vd_data
				FROM video_titles left join video_links on vl_id = vt_id
				LEFT JOIN video_data on vt_id = vd_id
				LEFT JOIN video_blacklist ON vt_id=vb_id WHERE vt_id != '{$target}'
				AND vl_page IN ("  . implode(", ", $related_ids) . ") and vl_source in ('wonderhowto', 'fivemin') and vb_id is null ORDER BY vl_result LIMIT 10;";
			$res = $dbr->query($sql, "Video::execute-relatedvids");
			while ($row = $dbr->fetchObject($res)) {
				if (isset($ids[$row->vt_id]))
					continue;
				$x = array();
				$x['id']	= $row->vt_id;
				$x['title'] = $row->vt_title;
				$x['data']	= $row->vd_data;
				$x['source'] = $row->vt_source;
				$x['thumb'] = $row->vt_thumb;
				$related_videos[] = $x;
				$ids[$row->vt_id] = 1;
			}
		}
		$wgMemc->set($key, $this->mResults, time()+3600);
		return $related_videos;
	}

	function getRelatedVideosList() {
		$vids = Video::getRelatedVideos();
		$related_vids = RelatedVideoApi::getRelatedVideoPanel($vids, 0);
		return $related_vids;
	}

	function getRelatedArticles($limit = 5) {
		//// RELATED ARTICLES
		$target = Video::getTarget();
		$related_articles = array();

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			array('video_titles', 'video_links', 'page'),
			array('page_title', 'page_namespace', 'page_id'),
			array('vt_id' => $target, 'vl_id=vt_id', 'vl_page=page_id'),
			"Video::execute",
			array('LIMIT' => $limit)
		 );
		while ($row = $dbr->fetchObject($res)) {
			$related_articles[] = Title::makeTitle($row->page_namespace, $row->page_title);
		}
		return $related_articles;
	}

	function getRelatedArticlesList($limit = 5) {
		global $wgUser;
		$sk = $wgUser->getSkin();

		$related_articles = Video::getRelatedArticles($limit);
		if (sizeof($related_articles) == 0) return '';

		$s = "<h3>" . wfMsg('relatedwikihows') . "</h3><ul>\n";
		foreach ($related_articles as $r) {
			$s .=  "<li>" . $sk->makeLinkObj($r, $r->getFullText()) . "</li>";
		}
		$s .= "</ul>";
		return $s;
	}

	function execute ($par) {
		global $wgRequest, $wgOut, $wgServer, $wgUser, $wgSquidMaxage;

		wfLoadExtensionMessages('Video');
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ($target == '') {
			$t = Title::makeTitle(NS_PROJECT, "Videos");
			$wgOut->redirect($t->getFullURL());
			return;
		}

		$redirect = false;
		$src = null;

		if (strpos($target, "how-to-") === false && !preg_match("@[0-9]$@", $target)) {
			//echo preg_replace("@([0-9]*)/@", "$1/how-to-", $target) . "<br/>";
			$target = $wgServer . "/video/" . preg_replace("@([0-9]+)/@", "$1/how-to-", $target);
			//echo $target; exit;
			$wgOut->redirect($target, 301);
			return;
		} else if (strpos($target, "wht/") === 0) {
			$src= " AND vt_source='wonderhowto' ";
			$target = substr($target, 4);
		} else if (strpos($target, "5min/") === 0) {
			$src= " AND vt_source='fivemin' ";
			$target = preg_replace("@.*5min/([0-9]*)/.*@", "$1", $target);
		}
		if (strpos($target, "/") > 0) {
			$len = strlen($target);
			if (substr($target, $len -1, 1) == "/")
				$redirect = true;
			$target = substr($target, 0, strpos($target, "/"));
		} else {
			// no target
			$redirect = true;
		}


		$dbr = wfGetDB(DB_SLAVE);

		$sql = "SELECT  vt_title, vt_id , vd_data, vt_source
				FROM video_titles
					left join video_data on vt_id = vd_id
					left join video_blacklist ON vt_id = vb_id
				WHERE
				vt_id = " . $dbr->addQuotes($target) . "
				{$src}
				AND vb_id is null";

		$res = $dbr->query($sql, "Video::execute");

		$results= array();
		while ($row = $dbr->fetchObject($res)) {
			$x = array();
			$x['data'] = $row->vd_data;
			$x['title'] = $row->vt_title;
			$x['source'] = $row->vt_source;
			$x['id']	= $row->vt_id;
			$results[] = $x;
		}

		if ($redirect) {
			$url= "{$wgServer}/video/{$x['id']}/" . FiveMinApiVideo::getURL($x['title']);
			$wgOut->redirect($url, 301);
			return;
		}
		if (sizeof($results) == 0) {
			$wgOut->addHTML("video_novideo");
			return;
		}

		$wgOut->setSquidMaxAge($wgSquidMaxage);

		$showdesc = $target < 38354574;
		$addchan = $showdesc ? "8465343679" : "4253433214";

		if ($wgUser->getID() == 0) {
			$sk = $wgUser->getSkin();
			if ($x['source'] == 'fivemin' && $x['id'] % 2 == 0) {
				$sk->mGlobalChannels[] = "2189181294";
				$sk->mGlobalComments[] = "site map vid";
			} else {
				$sk->mGlobalChannels[] = "9616405090";
				$sk->mGlobalComments[] = "not a site map vid";
			}
				$channels = $sk->getCustomGoogleChannels('embedded_ads_vid', false);
				$embed_ads = wfMsg('embedded_ads_video_new', $channels[0] . "+$addchan", $channels[1] );
				$embed_ads = preg_replace('/\<[\/]?pre\>/', '', $embed_ads);
			/* below ads
				only show ads below video
			$channels = $sk->getCustomGoogleChannels('side_ads_vid', false);
			$side_ads = wfMsg('side_ads_vid', $channels[0] . "+$addchan", $channels[1] );
			$side_ads = preg_replace('/\<[\/]?pre\>/', '', $side_ads);
			*/
		}

		$this->setHeaders();
		$wgOut->setRobotpolicy("index,follow");
		$wgOut->setPageTitle(wfMsg('video_title', $results[0]['title']));

		$flag = SpecialPage::getTitleFor("Flagvideo", $target);
		$wgOut->addHTML('<script type="text/javascript">
					var flagUrl = "'.$flag->getFullURL() . '";
					</script>
				<script type="text/javascript" language="javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/video_display.js?rev=') . WH_SITEREV . '"></script>
							<link rel="stylesheet" href="' . wfGetPad('/extensions/min/f/extensions/wikihow/video_display.css?rev=') . WH_SITEREV . '" type="text/css" />');

		$vid = null;
		if ($results[0]['source'] == 'fivemin') {
			$vid = new FiveMinApiVideo($results[0]['id'], $results[0]['data']);
		} else if ($results[0]['source'] == 'wonderhowto') {
			$vid = new WonderhowtoVideo($results[0]['id'], $results[0]['data']);
		} else {
			$vid = new YoutubeApiVideo($results[0]['id'], $results[0]['data']);
		}
		$description = "";
		if ($showdesc) {
			$description = $vid->getDescription();
		}
		$vidsrc = $vid->getVideoSrc();
		$meta = "";
		$title = $results[0]['title'];
		if (stripos($title, "How to") === false)
			$title = "How to " . $title;
		$wgOut->addMeta("description", "wikiHow video about {$title}");
		if ($vidsrc) {
			$meta = "
				<meta name='title' content=\"" . htmlspecialchars($title) . "\" />
				<meta name='description' content=\"" . htmlspecialchars(trim($vid->getDescription())) . "\"/>
				<link rel='image_src' href='{$vid->getThumb()}' />
				<link rel='video_src' href='{$vidsrc}'/>
				<meta name='video_height' content='{$vid->mResults[0]['MEDIA:PLAYER:HEIGHT']}' />
				<meta name='video_width' content='{$vid->mResults[0]['MEDIA:PLAYER:WIDTH']}' />
				<meta name='video_type' content='application/x-shockwave-flash' />
			";
		}
//xxxx
		$wgOut->addHTML($meta);


		$wgOut->addHTML("
					<table width='100%'>
					<tr><td width='500' valign='top' align='center'>{$vid->getVideo()}");

		//ADS
		if ($wgUser->getID() == 0) {
			$wgOut->addHTML( "<br/><br/>" . $embed_ads );
		}

		$wgOut->addHTML("</td></tr><tr><td>");
		if ($results[0]['source'] == 'wonderhowto') {
			$credit = preg_replace("@\n.*@im", "", $vid->mResults[0]['MEDIA:CREDIT']);
			if ($credit != 'howcast.com')
				$wgOut->addHTML("{$description}");
			$wgOut->addHTML("<br/><br/>This <a href='" . trim($vid->mResults[0]['LINK']) . "'>{$results[0]['title']}</a> video is hosted by <a href='http://{$credit}' rel='nofollow'>{$credit}</a>. Curated for wikiHow, courtesy of <a href='http://www.wonderhowto.com'>WonderHowTo</a>, the world's largest how-to video website.
				");
		} else  if ($results[0]['source'] == 'fivemin') {
			$wgOut->addHTML("{$description}");
			$wgOut->addHTML("</td></tr><tr><td>{$description}");
			preg_match_all("@<link>.*</link>@", $vid->mContent, $matches);
			$link = strip_tags(array_pop($matches[0]));
			$wgOut->addHTML("<br/><br/>This <a href='$link' class='external'>{$results[0]['title']}</a> video is hosted by <a href='http://www.5min.com' class='external'>5min.com</a>. ");
		} else {
			$wgOut->addHTML("{$description}");
		}

		$wgOut->addHTML("
						</td>
					</tr>
				</table>
			");

		$wgOut->addHTML("<table class='video_footer'>
				<tr>
					<td id='flagbutton'><a href='javascript:flagVideo();'><img src='" . wfGetPad('/extensions/wikihow/dialog-warning.png') . "' height='10px'> "
						. wfMsg('video_flag') . "</a></td>
					<td id='backbutton' style='display: none;'><a href='javascript:back()'>
						<img src='" . wfGetPad('/extensions/wikihow/go-previous.png') . "' height='10px'> ". wfMsg('video_returntoarticle') . "</a></td>
				</table>
			");



		$related_videos = Video::getRelatedVideos($target);
		/// DISPLAY RELATED VIDEOS AND ARTICLES
		if (sizeof($related_videos) > 0) {
			$wgOut->addHTML("<div class='video_related_vids1'><h2>" . wfMsg("video_relatedvideos") . "</h2>
				<table class='related_vids' ><tr>");
			$count = 0;
			foreach($related_videos as $v) {

				if ($v['source'] == 'fivemin') {
					$x = new FiveMinApiVideo($v['id'], $v['data']);
					$desc = $x->getDescription();
					$thumb = "<img src='{$v['thumb']}'>";
					$link = "<a class='rounders2 rounders2_ml rounders2_white' href='/video/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."'>";
				} else if ($v['source'] == 'youtube') {
					$x = new YoutubeApiVideo($v['id'], $v['data']);
					$desc = $x->getDescription();
					$thumb = $x->getThumb();
					$link = "<a class='rounders2 rounders2_ml rounders2_white' href='/video/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."'>";
				} else if ($v['source'] == 'wonderhowto') {
					$x = new WonderhowtoVideo($v['id'], $v['data']);
					$desc = $x->getDescription();
					$thumb = "<img class='rounders2_img' src='{$x->getThumb()}'/>";
					$link = "<a class='rounders2 rounders2_ml rounders2_white' href='/video/wht/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."'>";
					$link2 = "<a href='/video/wht/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."'>";
					if (strpos(FiveMinApiVideo::getURL($v['title']), "how-to") !==0)
						$link = "<a href='/video/wht/{$v['id']}/how-to-" . FiveMinApiVideo::getURL($v['title']) ."' class='rounders2 rounders2_ml rounders2_white'>";
					else
						$link = "<a href='/video/wht/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."' class='rounders2 rounders2_ml rounders2_white'>";
				}
				$wgOut->addHTML("
					<td>
					{$link}{$thumb}
					<img src='" . wfGetPad('/skins/WikiHow/images/play_thumb.png') . "' class='video_thumb_play'/>
					<img class='rounders2_sprite' alt='' src='" . wfGetPad('/skins/WikiHow/images/corner_sprite.png') . "'/>
					</a>{$link2}{$v['title']}</a></td>
					\n");
				$count++;
				if ($count % 6 == 0)
					$wgOut->addHTML("</tr><tr>");
			}
			$wgOut->addHTML("</tr></table></div>");
		}

/*
		if (sizeof($related_articles) > 0) {
			$wgOut->addHTML("<div class='video_related_vids'><h3>" . wfMsg("video_relatedarticles") . "</h3>");
			foreach ($related_articles as $t) {
				if (!$t) continue;
				$wgOut->addHTML("<div class='video_related'><div class='video_relate_title'>
					<a href='{$t->getFullURL()}'>" . wfMsg('howto', $t->getText()) . "</a></div>");

				$extra = "";
				$r = Revision::newFromTitle($t);
				if ($r) {
					$text = $r->getText();
					$a = new Article($t);
					$text = $a->getSection($text, 0);
					$text = preg_replace("/{{[^}]*}}/", "", $text);
					$extra = $wgOut->parse($text);
				}
				$wgOut->addHTML("$extra\n<br clear='both'/></div>");
			}
			$wgOut->addHTML("</div>");
		}
*/

		$wgOut->addHTML("<script type='text/javascript'>
						var refer = document.referrer;
						if (refer && refer.indexOf(wgServer) == 0) {
							var e = document.getElementById('backbutton');
							if (e) e.setAttribute('style', 'display:inline');
						}
				</script>");
		return;
	}

}

class Flagvideo extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Flagvideo' );
	}

	function execute ($par) {
		global $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$dbr = wfGetDB(DB_MASTER);
		// check if video exists
		$count = $dbr->selectField('video_titles', array('count(*)'), array('vt_id' => $target));
		if ($count > 0) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('video_flag',
				array('vf_id' => $target,
					'vf_user' => $wgUser->getID(),
					'vf_user_text' => $wgUser->getName(),
					'vf_timestamp' => wfTimestamp( TS_MW )
					)
				);
		}
		return;
	}

}

class ManageFlaggedVideos extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ManageFlaggedVideos' );
	}

	function removeVideo($id) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('video_blacklist',
			array('vb_id'	=> $id,
				'vb_user'	=> $wgUser->getId(),
				'vb_user_text' => $wgUser->getName(),
				'vb_timestamp' => wfTimestamp( TS_MW )
			)
		);
		$this->clearFlags($id);
	}

	function clearFlags($id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query("delete from video_flag where vf_id={$id}");
	}

	function execute ($par) {
		global $wgOut, $wgUser, $wgRequest;

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->setHeaders();
		$wgOut->addHTML('<link rel="stylesheet" href="' . wfGetPad('/extensions/min/f/extensions/wikihow/video_display.css?rev=') . WH_SITEREV . '" type="text/css" />');

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if ($target) {
			$action = $wgRequest->getVal('do');
			switch($action) {
				case 'clear':
					$this->clearFlags($target);
					$wgOut->addHTML("The flags have been cleared for this video (id $target).<br/>");
					break;
				case 'remove':
					$this->removeVideo($target);
					$wgOut->addHTML("The video has been blacklisted and won't be shown again on the site (id $target).<br/>");
					break;
			}

		}
		$results = array();
		$dbr = wfGetDB(DB_MASTER);
		$sql = "select count(distinct(vf_user_text)) as C, vf_id, vt_title
				from video_flag left join video_titles on vf_id=vt_id group by vt_id having C >= 3;";
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchObject($res)) {
			$x = array();
			$x['title']		= $row->vt_title;
			$x['id']		= $row->vf_id;
			$x['count']		= $row->C;
			$results[]		= $x;
		}

		if (sizeof($results) == 0) {
			$wgOut->addHTML("There are currently no flagged videos at this time.");
			return;
		}

		$wgOut->addHTML("<table class='manage_flagged_table'>");
		$wgOut->addHTML("<tr><td>Video</td><td># of times flagged</td><td colspan='2'>Actions</td></tr>");
		foreach ($results as $v) {
			$url = SpecialPage::getTitleFor("ManageFlaggedVideos", $v['id'])->getFullURL();
			$wgOut->addHTML("<tr><td><a href='/videos/{$v['id']}/" . FiveMinApiVideo::getURL($v['title']) ."' target='new'>{$v['title']}</td><td>{$v['count']}</td>
				<td><a href='{$url}?do=clear'>Clear flags</a></td>
				<td><a href='{$url}?do=remove'>Remove video</a></td>
				</tr>");
		}
		$wgOut->addHTML("</table>");
	}

}

class HowcastApi extends RelatedVideoApi {

	function execute() {
		$query = urlencode($this->mTitle->getText());
		$url = "http://www.howcast.com/search.xml?q={$query}&view=video&api_key={$wgHowcastAPIKey}";
		$results = $this->getResults($url);
		$this->parseResults($results);
		$this->mNumResults = sizeof($this->mResults);
	}


	function parseStartElement ($parser, $name, $attrs) {
	   switch ($name) {
		}
		if ($name == 'VIDEO') {
			$this->mCurrentNode = array();
		}
		$this->mCurrentTag = $name;
	}

	function parseEndElement ($parser, $name) {
		if ($name == "VIDEO") {
			$this->mResponseData[] = $this->mCurrentNode;
			$this->mCurrentNode = null;
		}
	}

	function storeResults() {
		if ($this->mNumResults < 0) {
			echo "Error: error retrieving results for {$this->mTitle->getFullText()}\n";
			return;
		}
		$dbw = wfGetDB(DB_MASTER);
		$xml = $this->mNumResults == 0 ?  "" : $this->mContent;
		#$xml = "Testing..";
		if ($this->mResults == 0) return;

		$this->deleteVideoLinks($this->mTitle->getArticleID(), 'videojug');

		$index = 1;
		foreach ($this->mResults as $r) {
			$id = $r['ID'];
			$video = new HowcastVideo($id);
			$video->getData();

			$this->insertVideoTitle ($id, trim($r['TITLE']), $video->mContent, 'howcast');
			$this->insertVideoLinks ($this->mTitle->getArticleID(), $id, $index, 'howcast');
			$index++;
		}
	}

}

class HowcastVideo extends HowcastApi {

	public $mId;

	function __construct($id, $content = null) {
		$this->mId = $id;
		$this->mContent = $content;
		if ($this->mContent != null) {
			$this->parseResults($this->mContent);
		}
	}

	function getData() {
		global $wgHowcastAPIKey;
		$url = "http://www.howcast.com/videos/{$this->mId}.xml?api_key={$wgHowcastAPIKey}";
		$this->mContent = $this->getResults($url);
		$this->parseResults($this->mContent);
	}

	function getURL($title) {
		return urlencode(strtolower(str_replace(" ", "-", $title)));
	}

	function getDescription() {
		#$desc  = strip_tags(htmlspecialchars_decode($desc));
		$desc = $this->mResponseData['DESCRIPTION'];
		return $desc;
	}
	function getThumb() {
		$thumb  = $this->mResponseData['THUMBNAIL-URL'];
		return $thumb;
	}

	function getVideo () {
		return $this->mResponseData[0]['EMBED'];
	}

}

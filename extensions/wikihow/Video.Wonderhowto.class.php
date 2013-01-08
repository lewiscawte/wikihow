<?
class WonderhowtoApi extends RelatedVideoApi {

  function array_flatten($a) {
    foreach($a as $k=>$v) $a[$k]=(array)$v;
    return call_user_func_array(array_merge,$a);
  }
    function getTopCategory($t) {
        $parenttree = $t->getParentCategoryTree();
		if (!is_array($parenttree)) return "NONE";
        $a = array_reverse($parenttree);
		foreach ($a  as $p) {
        	$last = $p;
        	while (sizeof($p) > 0 && $p = array_shift($p) ) {
            	$last = $p;
        	}
        	$keys = array_keys($last);
			$cat = str_replace("Category:", "", $keys[0]);
			if ($cat != "WikiHow") return $cat;
		}
       	return ""; 
    }  

	function execute() {
		$c = Title::makeTitle(NS_CATEGORY, $this->getTopCategory($this->mTitle));
		$query = urlencode($this->mTitle->getText());
		$url = "http://www.wonderhowto.com/search.aspx?t=mrss&m=v&q={$query}";
		$results = $this->getResults($url);
		$this->parseResults($results);
		$this->mNumResults = sizeof($this->mResults);
	}

	
    function parseStartElement ($parser, $name, $attrs) {
        switch ($name) {
            case "MEDIA:THUMBNAIL":
                $this->mCurrentNode['MEDIA:THUMBNAIL'] = $attrs['URL'];
                break;
			case "MEDIA:PLAYER":
				$this->mCurrentNode['MEDIA:PLAYER:WIDTH'] = $attrs['WIDTH'];
				$this->mCurrentNode['MEDIA:PLAYER:HEIGHT'] = $attrs['HEIGHT'];
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

	function getMap($wikihow, $wht) {
		$map = array(
            "Arts and Entertainment" => "/movies-film-theater /music-&-instruments 	 /fine-art 	/music-instruments	/dance /video-games ",
            "Health" => "/diet-&-health /motivation-self-help",
            "Relationships" => "/dating-&-relationships ",
            "Cars & Other Vehicles" => "/autos,-motorcycles-&-planes",
            "Hobbies and Crafts" => "/arts-&-crafts /gambling  	/games  	/hobbies-toys  	/video-games  	 /magic-&-parlor-tricks /fine-art",
            "Sports and Fitness" => "/fitness /sports ",
            "Computers and Electronics" => "/electronics /computers-&-programming /software /video-games ",
            "Holidays and Traditions" => "/hosting-entertaining ",
            "Travel" => "/travel /outdoor-recreation",
            "Education and Communications" => "/education /language-lessons",
            "Home and Garden" => "/home-&-garden /food",
            "Work World " => "/business-money",
            "Family Life" => "/family",
            "Personal Care and Style" => "/beauty-&-style /motivation-self-help",
            "Youth " => "/dating-relationships /education",
            "Finance and Business" => "/business-money",
            "Pets and Animals" => "/pets-animals",
            "Food and Entertaining" => "/food /alcohol ",
            "Philosophy & Religion" => "/spirituality /hosting-entertaining",
		);

		if (strpos($map[$wikihow], $wht) !== false) {
			#echo "$wikihow does map to $wht\n";
			return true;
		} else {
			#echo "$wikihow does NOT map to $wht\n";
			return false;
		}
	}
	
	function storeResults($usecatmap = true, $debug = false) {

        if ($this->mNumResults < 0) {
            echo "Error: error retrieving results for {$this->mTitle->getFullText()}\n";
            return;
        }
        $dbw = wfGetDB(DB_MASTER);
        $xml = $this->mNumResults == 0 ?  "" : $this->mContent;
        #$xml = "Testing..";
        if ($this->mResults == 0) return;
		
		if ($this->mTitle) $this->deleteVideoLinks($this->mTitle->getArticleID(), 'wonderhowto');

        $index = 1;

		$stored = 0;
        foreach ($this->mResults as $r) {
        	$id = preg_replace("/.*-/", "", trim($r['GUID']));
        	$id = preg_replace("/\//", "", $id);
		
			$title = trim($r['TITLE']);

			if ($usecatmap) {
				$cat = Title::makeTitle(NS_CATEGORY, WonderHowtoApi::getTopCategory($this->mTitle));
	        	$c = $r['MEDIA:CATEGORY'];
	        	if (strpos($c, "/") !== false)
	            	$c = trim(substr($c, 0, strpos($c, "/")));
	        	$c = str_replace(" ", "-", strtolower($c));
	        	$c = str_replace("&amp;", "&", $c);
	        	$c = "/" . trim($c);
	        
				#echo "Comparing {$cat->getText()} to {$c}\n";	
				if (!$this->getMap($cat->getText(), $c)) {
					echo "Skipping adding link from \"{$this->mTitle->getFullText()}\" to \"{$title}\", cats dont line up ({$cat->getText()}, {$c})\n";
					continue;
				}
			}
 
            $video = new WonderhowtoVideo($id);
            $video->getData();
			$link = trim($video->mResults[0]['LINK']);
			if (!$debug) {
				$this->insertVideoTitle ($id, trim($r['TITLE']), $video->mContent, 'wonderhowto', $video->getThumb());
				if ($this->mTitle) 
						$this->insertVideoLinks ($this->mTitle->getArticleID(), $id, $index, 'wonderhowto', $title, $video->getThumb());
			} else {
				echo "inserting link from article \"{$this->mTitle->getFullText()}\" to \"{$title}\" {$link}\n";
			}
            $index++;
			$stored++;
			if ($stored >= 5) 
				break;
        }
    }

}

class WonderhowtoVideo extends WonderhowtoApi {
      
    public $mId;
	public $mCategories; 
    
    function __construct($id, $content = null) {
        $this->mId = $id;
        $this->mContent = $content;
        if ($this->mContent != null) {
            $this->parseResults($this->mContent);
        }
    }           
            
    function getData() {
		$url = "http://www.wonderhowto.com/search.aspx?vid={$this->mId}&t=mrss&m=v";
        $this->mContent = $this->getResults($url);
        $this->parseResults($this->mContent); 
    }           
            
    function getURL() {
		$t = urlencode(strtolower(str_replace(" ", "-", trim($this->mResults[0]['TITLE']))));
        return "/video/wht/{$this->mId}/how-to-{$t}";
    }       
            
    function getDescription() {
        #$desc  = strip_tags(htmlspecialchars_decode($desc));
        $desc = $this->mResults[0]['MEDIA:DESCRIPTION'];
        return $desc;
    }   

	function getVideoSrc()  {
        $vendor = preg_replace("@\n.*@im", "", $this->mResults[0]['MEDIA:CREDIT']);
		$src = null;
        switch ($vendor) {
            case "youtube.com":
            case "metacafe.com":
            case "howcast.com":
            case "blip.tv":
            case "monkeysee.com":
                $src  = trim(preg_replace("@.*src=\"([^\"]*)\".*@im", "$1", $this->mResults[0]['MEDIA:TEXT']));
                //echo id->mResults[0]['MEDIA:TEXT'] . "\n"; print_r($params); exit;
                break;
            case "revver.com":
                $player = trim(preg_replace("@.*src=\"([^\"]*)\".*@im", "$1", $vid->mResults[0]['MEDIA:TEXT']));
                $player_params = trim(preg_replace("@.*flashvars=\"([^\"]*)\".*@im", "$1", $this->mResults[0]['MEDIA:TEXT']));
                $src  = "$player?$player_params";
                break;
            #case "monkeysee.com":
                #$params['video:player_loc'] = trim(preg_replace("@.*src=\"([^\"]*)\".*@im", "$1", $vid->mResults[0]['MEDIA:TEXT']));
            default:
                //unsupported vendor
                #echo "Skipping $vendor\n";
        }
		return $src;
	}

    function getThumb() {
		$thumb = $this->mResults[0]['DESCRIPTION'];
	    preg_match('@img src=[\'"]http://.*[\'"]@U', $thumb, $matches);
    	$thumb = "";
    	if (sizeof($matches) > 0) $thumb = $matches[0];
    	$thumb = str_replace("img src=", "", $thumb);
    	$thumb = str_replace("'", "", $thumb);
    	$thumb = str_replace("\"", "", $thumb);
        return $thumb;
    }   
        
    function getVideo () {
		return $this->mResults[0]['MEDIA:TEXT'];
    }

}
class VideoTest extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'VideoTest' );
    } 
function execute($par) {
	global $wgRequest, $wgOut;

	if ($wgRequest->wasPosted()) {

	}

	$wgOut->setArticleBodyOnly(true);
	$t =	Randomizer::getRandomTitle();
	$t = Title::newFromURL('Wire-a-Light-Switch-From-a-Receptacle');
	$cat = Title::makeTitle(NS_CATEGORY, WonderHowtoApi::getTopCategory($t));
	$wgOut->addHTML("<h1><a href='http://www.wikihow.com/{$t->getPrefixedURL()}' target='new'>How to {$t->getText()}</a> - $cat</h1>");
	$wgOut->addHTML("<form method='POST' action='VideoTest'><table width='100%' border='1'>
			<input type='hidden' name='page_id' value='{$t->getArticleID()}'>
					<tr>
					<td>Choice A</td>
					<td>Choice B</td>
					<td>Choice C</td>
			</tr>");

		// normal
	$wh = new WonderHowtoApi($t);
	$wh->execute();

	$wgOut->addHTML("<tr>");	
	$wgOut->addHTML("<td style='width: 300px;'><ol>");	
	foreach($wh->mResults as $w) {
        	$wgOut->addHTML("<li><a href='{$w['LINK']} target='new'>{$w['TITLE']}</a> - {$w['MEDIA:CATEGORY']}<br/>{$w['DESCRIPTION']}</li>");
	}

        // normal
    $wh = new WonderHowtoApi($t);
    $wh->execute();

    $wgOut->addHTML("<td style='width: 300px;' valign='top'><ol>");
    foreach($wh->mResults as $w) {
		$c = $w['MEDIA:CATEGORY'];
		if (strpos($c, "/") !== false) 
			$c = trim(substr($c, 0, strpos($c, "/")));
		$c = str_replace(" ", "-", strtolower($c));	
		$c = str_replace("&amp;", "&", $c);
		$c = "/" . trim($c);
		#echo "checking ". trim($w['TITLE']) . " - $c\n";
		if ($this->getMap($cat->getText(), $c)) 
        	$wgOut->addHTML("<li><a href='{$w['LINK']} target='new'>{$w['TITLE']}</a> - {$w['MEDIA:CATEGORY']}<br/>{$w['DESCRIPTION']}</li>");
    }
	
}


	function storeResults() {
   	    $id = preg_replace("/.*-/", "", trim($this->mResults[0]['GUID']));
   	    $id = preg_replace("/\//", "", $id);
		$this->insertVideoTitle ($id, trim($this->mResults[0]['TITLE']), $this->mContent, 'wonderhowto', $this->getThumb());
	}
}

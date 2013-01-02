<?
class YTThumb extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'YTThumb' );
    }

	function getStepDropdown ($file, $numsteps) {
		$html = "Upload to step # <SELECT name='{$file}'><OPTION val=''></OPTION>";
		for ($i = 0; $i < $numsteps; $i++) {
			$html .= "<OPTION val='$i'>" . ($i + 1) . "</OPTION>";
		}
		$html .= "</SELECT>";
		return $html;
	}


	public static function hasThumbnails($t) {
		global $wgUser;
	    
		if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
	        return false;
	    }

		if (!$t) {
			return false;
		}

		// check if there are youtube thumbs available
		$tv = Revision::newFromTitle($t);
		if (!$tv) {
			return false;
		}
		$text = $tv->getText();
		$v = Title::makeTitle(NS_VIDEO, $t->getText());
		$rv = Revision::newFromTitle($v); 
		if (!$rv || !preg_match("@\{\{Video:@", $text)) {
			return false;
		}

		// grab the video id for the youtube video,is it a youtube? 
		$params = split("\|", $rv->getText());
		if ($params[1] != "youtube") {
			return false;
		}

		$id = $params[2];
		$dir = "/var/www/images_en/yt/{$id}";
		
		// does the thumbs directory exist for this id? if so we have thumbs
		if (!is_dir($dir)) {
			return false;
		}

		return true;
	}


    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

	    if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
	    	$wgOut->setArticleRelated( false );
	        $wgOut->setRobotpolicy( 'noindex,nofollow' );
	        $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
	        return;
	    }

		$wgOut->setArticleBodyOnly(true); 
		$t = Title::makeTitle(NS_MAIN, $target); 

		if ($wgRequest->wasPosted()) {
			$id = $wgRequest->getVal("videoid");
			$dir = "/var/www/images_en/yt/{$id}";
			foreach ($wgRequest->getValues() as $key=>$val) {
				// double negative to get a number
				if (!preg_match("@[^0-9]@", $val) && $val) {
					$filename = $dir. "/" . preg_replace("@_jpg@", ".jpg", $key); 
					$wgOut->addHTML("Would upload $filename to step $val<br/>");
				}
			}
			return;
		}


		if (!$t) {
			$wgOut->addHTML("Couldn't make title out of '{$target}'");
			return;
		}

		// check if there are youtube thumbs available
		$tv = Revision::newFromTitle($t);
		$text = $tv->getText();
		$v = Title::makeTitle(NS_VIDEO, $t->getText());
		$rv = Revision::newFromTitle($v); 
		if (!$rv || !preg_match("@\{\{Video:@", $text)) {
			$wgOut->addHTML("There appears to be no video embedded for this article, oops");
			return;
		}

		// grab the video id for the youtube video,is it a youtube? 
		$params = split("\|", $rv->getText());
		if ($params[1] != "youtube") {
			$wgOut->addHTML("The video embedded for this article is not a youtube video,oops");
			return;
		}

		$id = $params[2];
		$dir = "/var/www/images_en/yt/{$id}";
		
		// does the thumbs directory exist for this id? if so we have thumbs
		if (!is_dir($dir)) {
			$wgOut->addHTML("Sorry, we don't have thumbnails for this video at this time\n"); 
			return;
		}

		// iterate over the files and give the option to input them	
		// how many steps in the article? 
		$num_steps = preg_match_all("@^#[^\*#]@m", $text, $matches); 
		$wgOut->addHTML("<form action='/Special:YTThumb' method='POST'>"); 
		$wgOut->addHTML("<input type='hidden' name='target' value=\"". htmlspecialchars($t->getDBKey()) . "\"/>");	
		$wgOut->addHTML("<input type='hidden' name='videoid' value=\"{$id}\"/>");
		$wgOut->addHTML("<table align='center' width='70%'>");
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match("@.jpg$@", $file)) {
					$url = "http://www.wikihow.com/images/yt/{$id}/{$file}";
					$wgOut->addHTML("<tr><td><a href='{$url}' target='new'><img src='$url' style='width:200px;' border='0px'/></a></td>
						" . "</td><td valign='top'>". self::getStepDropdown($file, $num_steps) . "</tr>");
				
				}
			}
			closedir($handle);
		}
		$wgOut->addHTML("</table><br/><input type='submit' value='Upload' style='position: absolute; bottom: 10px; left: 500px;'/></form>");
	
		return true;	

	}
}


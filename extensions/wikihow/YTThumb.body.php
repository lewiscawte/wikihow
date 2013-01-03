<?
class YTThumb extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'YTThumb' );
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
	    
		if ( !in_array( 'imagecurator', $wgUser->getRights() ) ) {
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

		// do we have at least 1 picture?
		$found = false;
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match("@.jpg$@", $file)) {
					$found = true;
					break;
				}
			}
			closedir($handle);
		}
		return $found;
	}


	function processUpload() {
		global $wgRequest, $wgOut;

		wfLoadExtensionMessages("YTThumb");

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$t = Title::makeTitle (NS_MAIN, $target); 
		$response = array(); 
		foreach ($wgRequest->getValues() as $key=>$val) {
			if (!preg_match("@step[0-9]+@", $key) && $val) {
				continue;
			}
			if (!$vidid){
				$vidid = preg_replace("@.*yt/@", "", $val);
				$vidid = preg_replace("@/.*@", "", $vidid);
				$url = "http://gdata.youtube.com/feeds/api/videos/$vidid";
				$data = simplexml_load_file($url); 
				$vidtitle = $data->title;
			}
			$step = preg_replace("@step@", "", $key); 
			#echo("uploading $val to $key <br/>");
			
			// set up the request for the upload
			$wgRequest->setVal('wpUploadFileURL', $val);
			$wgRequest->setVal('wpIgnoreWarning', 1);
			$wgRequest->setVal('wpUploadDescription', wfMsg('ytthumb_upload_description', $vidid, $vidtitle));
			$img = Title::makeTitle(NS_IMAGE, $t->getText() . " Step " . ($step+1) . ".jpg");
			$images[$step] = $img;
			$wgRequest->setVal('wpDestFile', $img->getText()); 
	
			// upload it
			$u = new UploadForm($wgRequest); 
			$u->initializeFromUrl($wgRequest); 
			$u->processUpload();
			if ($wgOut->getRedirect()) {
				// we can assume it was a success
				$wgOut->redirect(null);
			} else {
				#echo "didn't get a redirect?\n"; print_r($wgOut);
			}
			//print_r($u); exit;
		}

		// can make error messages i18n when if the tool gets more widespread use
		if (!$t) {
			$response['errors'] = "Coudln't make title out of $target";
			return;
		}

		// edit the article!
		$r = Revision::newFromTitle($t); 
		if (!$r) {
			$response['errors'] .= "Couldn't make revision out of {$t->getText()}, so couldn't update it. ";
			return $response;
		}

		$text = $r->getText();
		// find the steps section
		$index = 0;
		$steps = null;
		while ($s = Article::getSection($text, $index)) {
			if (preg_match("@^==[ ]*" . wfMsg('steps') . "@", $s)) {
				$steps = preg_split("@\n@m", $s); //,  0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE); 
				break;
			}
			$index++;
		}

		if (!$s) {
			$response['errors'] .= "Couldn't find the steps in the article (unlikely)";
			return $response;
		}

		if (sizeof($images) == 0) {
			$response['errors'] .= "Didn't get any images to upload";
			return $response;
		}


		// insert the images into the text of the article
		foreach ($images as $i=>$img) {
			$steps[$i + 1] = preg_replace("@^#@m", "#[[Image:{$img->getText()}|thumb]]", $steps[$i+1], 1);
		} 
		$a = new Article(&$t);
		$newtext = $a->replaceSection($index, implode("\n", $steps)); 

		// add sources and citations 
		$sindex = 0;
		$sources = null;
		while ($s = Article::getSection($text, $sindex)) {
			if (preg_match("@^==[ ]*" . wfMsg('sources') . "@", $s)) {
				$sources = $s;
				break;
			}
			$sindex++;
		}

		$ref = wfMsg('ytthumb_sources_entry', $vidid, $vidtitle); 
		
		if ($sources && !preg_match("@{$vidid}@", $sources)) {
			$sources .= "\n" . $ref;
			$newtext = $a->replaceSection($sindex, implode("\n", $sources)); 
		} else if (!$sources) {
			$newtext .= "\n\n== " . wfMsg('sources')  . " ==\n" . $ref; 
		}

		if ($a->doEdit($newtext, wfMsg('ytthumb_edit_summary'))) {
			$response['success'] = $t->getFullURL();
		}
		return $response;
	}


    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

	    if ( !in_array( 'imagecurator', $wgUser->getRights() ) ) {
	    	$wgOut->setArticleRelated( false );
	        $wgOut->setRobotpolicy( 'noindex,nofollow' );
	        $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
	        return;
	    }

		if ($wgRequest->getVal('eaction') == 'notify') {
			$wgOut->disable();
			$dbw = wfGetDB(DB_MASTER); 
			$count = $dbw->selectField("ytnotify", array('count(*)'), array('ytn_user'=>$wgUser->getID(),  'ytn_page' => $wgRequest->getVal('id')));
			if ($count == 0) {
				$dbw->insert('ytnotify', array('ytn_user'=>$wgUser->getID(), 'ytn_user_text'=>$wgUser->getName(), 
					'ytn_page' => $wgRequest->getVal('id'), 'ytn_timestamp' => wfTimestampNow()));
			}	
			return;
		}
		$wgOut->setArticleBodyOnly(true); 
		$t = Title::makeTitle(NS_MAIN, $target); 

		if ($wgRequest->wasPosted()) {
			$wgOut->disable();
			$result = self::processUpload(); 
			print json_encode($result);
			return;
		}

		if (!$t) {
			$wgOut->addHTML("Couldn't make title out of '{$target}'");
			return;
		}

		// test
		if ($wgRequest->getVal('test')) {
			$wgOut->addHTML("
			<form method='POST' action='/Special:YTThumb'>
				<input type='hidden' name='step0' value='http://www.wikihow.com/images/yt/ne-wD_dEe-M/ne-wD_dEe-M-30.jpg'/>
				<input type='hidden' name='step1' value='http://www.wikihow.com/images/yt/ne-wD_dEe-M/ne-wD_dEe-M-12.jpg'/>
				<input type='hidden' name='step2' value='http://www.wikihow.com/images/yt/ne-wD_dEe-M/ne-wD_dEe-M-11.jpg'/>
				<input type='hidden' name='step3' value='http://www.wikihow.com/images/yt/ne-wD_dEe-M/ne-wD_dEe-M-18.jpg'/>
				<input type='hidden' name='step4' value='http://www.wikihow.com/images/yt/ne-wD_dEe-M/ne-wD_dEe-M-19.jpg'/>
				<input type='hidden' name='target' value='Swing-a-Golf-Club'/>
				<input type='submit'/>
			</form>");
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
		//$wgOut->addHTML("<form action='/Special:YTThumb' method='POST'>"); 
		$wgOut->addHTML("<input type='hidden' name='target' value=\"". htmlspecialchars($t->getDBKey()) . "\"/>");	
		$wgOut->addHTML("<input type='hidden' name='videoid' value=\"{$id}\"/>");
		$wgOut->addHTML("<table align='center' width='70%'><tr>");
		$index = 0;
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match("@.jpg$@", $file)) {
					$url = "http://www.wikihow.com/images/yt/{$id}/{$file}";
					$wgOut->addHTML("<td><a href='#' onclick='return ytImage(\"{$url}\");' target='new'><img src='$url' style='width:200px;' border='0px'/></a></td>
						" . "</td><td valign='top'>"
						//. self::getStepDropdown($file, $num_steps) 
						. "</td>");
						$index++; 
						if ($index % 3 == 0) {
							$wgOut->addHTML("</tr><tr>");
						}
				
				}
			}
			closedir($handle);
		}
		$wgOut->addHTML("</tr>");
		$wgOut->addHTML("</table>");
	
		return true;	

	}
}

class YTThumbList extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'YTThumbList' );
    }
    
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgCategoryNames;
	    
		if ( !in_array( 'imagecurator', $wgUser->getRights() ) ) {
	    	$wgOut->setArticleRelated( false );
	        $wgOut->setRobotpolicy( 'noindex,nofollow' );
	        $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
	        return;
	    }

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$dbr = wfGetDB(DB_SLAVE); 

		$res = $dbr->select(array('page', 'ythasthumbs'), array('page_title', 'page_namespace', 'page_catinfo'), 
				array('page_id=yth_page'), "YTThumbList::execute", array('ORDER BY' => 'page_catinfo'));

		$cats = array(); 
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			foreach ($wgCategoryNames as $val=>$name) {
				if ($val & $row->page_catinfo) {
					if (!isset($cats[$name])) {
						$cats[$name] = array();
					}
					$cats[$name][] = $t;
					break;
				}
			}
		}
		
		foreach ($cats as $c=>$titles) {
			$wgOut->addHTML("<h2>{$c}</h2><ul>");
			foreach ($titles as $t) {
				$wgOut->addHTML("<li><a href='{$t->getFullURL()}'>{$t->getText()}</a></li>");
			}
			$wgOut->addHTML("</ul>");
		}
	}
}



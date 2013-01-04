<?php

class ImageHelper extends UnlistedSpecialPage {

	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ImageHelper' );
	}

	function getRelatedWikiHows($title){
		global $wgUser, $wgOut;

		wfLoadExtensionMessages('ImageHelper');
		
		$articles = ImageHelper::getLinkedArticles($title);
		$relatedArticles = array();
		foreach($articles as $article){
			$t = Title::newFromText($article['title']);
			$related = ImageHelper::setRelatedWikiHows($t);
			foreach($related as $titleString){
				$relatedArticles[$titleString] = $titleString;
			}
		}

		$section = '';
		$section .= "<h2>" . wfMsg('ih_relatedArticles') . "</h2>";
		$section .= "<table class='featuredArticle_Table'>";

		$count = 0;
		foreach($relatedArticles as $titleString){
			$t = Title::newFromText($titleString);
			if($t && $t->exists()) {
				$sk = $wgUser->getSkin();
				$msg = $t->getFullText();
				$link = $sk->makeKnownLinkObj( $t, $msg);
				$img = $sk->getGalleryImage($t, 103, 80);

				$section .= "<td><div>
					<a href='{$t->getFullURL()}' class='rounders2 rounders2_tl rounders2_white'>
						<img src='{$img}' alt='' width='103' height='80' class='rounders2_img' />
						<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
					</a>
					<a href='{$t->getFullURL()}'>{$t->getText()}</a>
					</div></td>";
				
				if (++$count == 5) break;
			}
		}
		$section .= "</table>";

		$wgOut->addHTML($section);
	}

	function setRelatedWikiHows($title){
		global $wgTitle, $wgParser, $wgMemc;

		$key = wfMemcKey("ImageHelper_related" . $title->getArticleID());
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}
		
		$r = Revision::newFromTitle($title);
		$text = $r->getText();
		$whow = new WikiHow();
		$whow->loadFromText($text);
		$related = preg_replace("@^==.*@m", "", $whow->getSection('related wikihows'));

		$preg = "/\\|[^\\]]*/";
		$related = preg_replace($preg, "", $related);
		$rarray = split("\n", $related);
		$relatedTitles = array();
		foreach($rarray as $related) {
			preg_match("/\[\[(.*)\]\]/", $related, $rmatch);
			$relatedTitles[] = $rmatch[1];
		}

		$wgMemc->set(wfMemcKey("ImageHelper_related" . $title->getArticleID()), $relatedTitles);
		
		return $relatedTitles;
		
	}

	function getLinkedArticles($title){
		global $wgMemc;

		$result = $wgMemc->get(wfMemcKey("ImageHelper_linked" . $title->getArticleID()));
		if ($result) {
			return $result;
		}

		$imageTitle = $title->getDBkey();
		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$imagelinks = $dbr->tableName( 'imagelinks' );

		$sql = "SELECT page_namespace,page_title,page_id FROM $imagelinks,$page WHERE il_to=" .
		  $dbr->addQuotes( $imageTitle ) . " AND il_from=page_id";
		$sql = $dbr->limitResult($sql, 500, 0);
		$res = $dbr->query( $sql, "ImageHelper::getLinkedArticles" );

		$articles = array();

		while ( $s = $dbr->fetchObject( $res ) ) {
			if($s->page_title != $imageTitle)
				$articles[] = array('namespace' => $s->page_namespace, 'title' => $s->page_title, 'id' => $s->page_id);
		}

		$wgMemc->set(wfMemcKey("ImageHelper_linked" . $title->getArticleID()), $articles);
		return $articles;
	}

	function getSummaryInfo($image){
		global $wgOut, $wgUser, $wgTitle;

		$sk = $wgUser->getSkin();

		$sizes = ImageHelper::getDisplaySize($image);

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'preview' => $sizes['width'] . "x" . $sizes['height'] . "px",
			'full' => $sizes['full'] == 0?"<a href='" . $image->getFullUrl() . "'>" . $image->getWidth() . "x" . $image->getHeight() . " px </a>":wfMsg( 'file-nohires'),
			'file' => $sk->formatSize($image->getSize()),
			'mime' => $image->getMimeType(),
			'imageCode' => "[[" . $wgTitle->getFullText() . "|thumb|description]]"
		));
		
		$wgOut->addHTML($tmpl->execute('fileInfo.tmpl.php'));

		//for now, don't show these ads
		/*if($articleCount == 0 && $wgUser->getID() == 0){
			$sk = $wgUser->getSkin();
			$channels = $sk->getCustomGoogleChannels('imagead1', false);
			$embed_ads = wfMsg('imagead1', $channels[0], $channels[1] );
			$embed_ads = preg_replace('/\<[\/]?pre\>/', '', $embed_ads);
			$wgOut->addHTML($embed_ads);
		}*/
	}

	function getImages($articleId){
		global $wgMemc;

		$result = $wgMemc->get(wfMemcKey("ImageHelper_getImages" . $articleId));
		if ($result) {
			return $result;
		}
		echo $articleID;

		$dbr = wfGetDB( DB_SLAVE );
		$results = array();
		$res = $dbr->select(array('imagelinks'), '*', array('il_from' => $articleId));
		while($row = $dbr->fetchRow($res)){
			$results[] = $row;
		}

		$wgMemc->set(wfMemcKey("ImageHelper_getImages" . $articleId), $results);

		return $results;
	}

	/**
	 *
	 * This function takes a list of articles and finds other images
	 * that are in those articles.
	 */
	function getConnectedImages($articles, $title){
		global $wgOut, $wgUser;

		wfLoadExtensionMessages('ImageHelper');

		$sk = $wgUser->getSkin();

		$imageName = $title->getDBkey();

		$noImageArray = array();
		foreach($articles as $article){
			$imageUrl = array();
			$thumbUrl = array();
			$imageTitle = array();
			
			$results = ImageHelper::getImages($article['id']);

			$count = 0;
			if(count($results) <= 1){
				$noImageArray[] = $article;
				continue;
			}
			$name = Title::MakeTitle( $article['namespace'], $article['title'] );
			$title = $sk->makeKnownLinkObj( $name, "" );
			foreach($results as $row){
				if($count >= 5)
					break;
				if($row['il_to'] != $imageName && $row['il_to'] != 'LinkFA-star.jpg'){
					$image = Title::newFromText("Image:" . $row['il_to']);
					if ($image && $image->getArticleID() > 0) {
						$file = wfFindFile($image);
						if ($file && isset($file)) {
							$thumb = $file->getThumbnail(103, -1, true, true);
							$imageUrl[] = $image->getFullURL();
							$thumbUrl[] = $thumb->url;
							$imageTitle[] = $row['il_to'];
						}
					}
					$count++;
				}
			}
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array(
				'imageUrl' => $imageUrl,
				'thumbUrl' => $thumbUrl,
				'imageTitle' => $imageTitle,
				'title' => $title,
				'numImages' => count($imageUrl)
			));

			$wgOut->addHTML($tmpl->execute('connectedImages.tmpl.php'));
		}

		if(sizeof($noImageArray) > 0){
			$wgOut->addHTML("<h2>" . wfMsg('ih_otherlinks') . "</h2><ul style='margin-bottom:20px'>");
			foreach($noImageArray as $article){
				$name = Title::MakeTitle( $article['namespace'], $article['title'] );
				$link = $sk->makeKnownLinkObj( $name, "" );
				$wgOut->addHTML( "<li>{$link}</li>\n" );
			}
			$wgOut->addHTML("</ul>");
		}
	}

	function displayBottomAds(){
		global $wgUser, $wgOut;
		
		if($wgUser->getID() == 0){
			$sk = $wgUser->getSkin();
			$channels = $sk->getCustomGoogleChannels('imagead2', false);
			$embed_ads = wfMsg('imagead2', $channels[0], $channels[1] );
			$embed_ads = preg_replace('/\<[\/]?pre\>/', '', $embed_ads);
			$wgOut->addHTML($embed_ads);
		}
	}

	function getDisplaySize($img) {
		global $wgOut, $wgUser, $wgImageLimits, $wgRequest, $wgLang, $wgContLang;

		$sizeSel = intval( $wgUser->getOption( 'imagesize') );
		if( !isset( $wgImageLimits[$sizeSel] ) ) {
			$sizeSel = User::getDefaultOption( 'imagesize' );

			// The user offset might still be incorrect, specially if
			// $wgImageLimits got changed (see bug #8858).
			if( !isset( $wgImageLimits[$sizeSel] ) ) {
				// Default to the first offset in $wgImageLimits
				$sizeSel = 0;
			}
		}
		$max = $wgImageLimits[$sizeSel];
		$maxWidth = $max[0];
		//XXMOD for fixed width new layout.  eventhough 800x600 is default 679 is max article width
		if ($maxWidth > 679)
			$maxWidth = 629;
		$maxHeight = $max[1];

		if ( $img->exists() ) {
			# image
			$page = $wgRequest->getIntOrNull( 'page' );
			if ( is_null( $page ) ) {
				$params = array();
				$page = 1;
			} else {
				$params = array( 'page' => $page );
			}
			$width_orig = $img->getWidth();
			$width = $width_orig;
			$height_orig = $img->getHeight();
			$height = $height_orig;

			if ( $img->allowInlineDisplay() ) {
				# image

				# "Download high res version" link below the image
				#$msgsize = wfMsgHtml('file-info-size', $width_orig, $height_orig, $sk->formatSize( $this->img->getSize() ), $mime );
				# We'll show a thumbnail of this image
				if ( $width > $maxWidth || $height > $maxHeight ) {
					# Calculate the thumbnail size.
					# First case, the limiting factor is the width, not the height.
					if ( $width / $height >= $maxWidth / $maxHeight ) {
						$height = round( $height * $maxWidth / $width);
						$width = $maxWidth;
						# Note that $height <= $maxHeight now.
					} else {
						$newwidth = floor( $width * $maxHeight / $height);
						$height = round( $height * $newwidth / $width );
						$width = $newwidth;
						# Note that $height <= $maxHeight now, but might not be identical
						# because of rounding.
					}
					$size['width'] = $width;
					$size['height'] = $height;
					$size['full'] = 0;
					return $size;
				} else {
					# Image is small enough to show full size on image page
					$size['width'] = $width;
					$size['height'] = $height;
					$size['full'] = 1;
					return $size;
				}

			} else {
				#if direct link is allowed but it's not a renderable image, show an icon.
				if ($img->isSafeFile()) {
					$icon= $img->iconThumb();

					$wgOut->addHTML( '<div class="fullImageLink" id="file">' .
					$icon->toHtml( array( 'desc-link' => true ) ) .
					'</div>' );
				}

				$showLink = true;
			}


			

			if(!$this->img->isLocal()) {
				$this->printSharedImageText();
			}
		} else {
			# Image does not exist
			$size['width'] = -1;
			$size['height'] = -1;
			return $size;
		}
	}

	function showDescription($imageTitle){
		global $wgOut;
		
		$description = "";
		
		$t = Title::newFromText('Image:' . $imageTitle->getPartialURL() . '/description');
		if ($t && $t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$description = $r->getText();
			$wgOut->addHTML("<div style='margin-top:10px; margin-bottom:30px;'>");
			$wgOut->addHTML("<strong>Description: </strong>");
		}
		else
			$wgOut->addHTML("<div style='margin-bottom:30px;'>");
		$wgOut->addHTML($description);
		$wgOut->addHTML("</div>");
	}

	function addSideWidgets($title){
		global $wgUser;
		$skin = $wgUser->getSkin();
		
		//first add related images
		$html = ImageHelper::getRelatedImagesWidget($title);
		if($html != "")
			$skin->addWidget($html);
		$html = ImageHelper::getRelatedWikiHowsWidget($title);
		if($html != "")
			$skin->addWidget($html);
	}

	function getRelatedWikiHowsWidget($title){
		global $wgUser, $wgOut;

		wfLoadExtensionMessages('ImageHelper');

		$articles = ImageHelper::getLinkedArticles($title);
		$relatedArticles = array();
		foreach($articles as $article){
			$t = Title::newFromText($article['title']);
			$related = ImageHelper::setRelatedWikiHows($t);
			foreach($related as $titleString){
				$relatedArticles[$titleString] = $titleString;
			}
		}

		$section = '';
		$section .= "<h3>" . wfMsg('ih_relatedArticles') . "</h3>";
		$section .= "<table id='side_related_articles'>";

		$count = 0;
		foreach($relatedArticles as $titleString){
			$t = Title::newFromText($titleString);
			if($t && $t->exists()) {
				$sk = $wgUser->getSkin();
				$msg = $t->getFullText();
				$link = $sk->makeKnownLinkObj( $t, $msg);
				$img = $sk->getGalleryImage($t, 103, 80);

				$section .= "<tr><td id='thumb'><span class='rounders2 rounders2_sm rounders2_tan'>
				<a href='{$t->getFullURL()}'><img class='rounders2_img' alt='' src='{$img}' />
				<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
				</a>
				</span>
				</td>
				<td>{$link}</td></tr>\n";

				if (++$count == 5) break;
			}
		}
		$section .= "</table>";

		return $section;
	}

	function getRelatedImagesWidget($title){
		global $wgUser;
		
		$articles = ImageHelper::getLinkedArticles($title);
		$images = array();
		foreach($articles as $article){
			$results = ImageHelper::getImages($article['id']);
			if(count($results) <= 1){
				continue;
			}

			$titleDb = $title->getDBkey();
			foreach($results as $row){
				if($row['il_to'] != $titleDb && $row['il_to'] != 'LinkFA-star.jpg'){
					$images[] = $row['il_to'];
				}
			}
		}

		$count = 0;
		$maxLoc = count($images);
		$finalImages = array();
		while($count < 6 && $count < $maxLoc){
			$loc = rand(0, $maxLoc);
			if($images[$loc] != null){
				$finalImages[] = $images[$loc];
				$images[$loc] = null;
				$count++;
			}
		}

		if(count($finalImages) > 0){
			$html = '<div><h3>' . wfMsg('ih_relatedimages_widget') . '</h3><table style="margin-top:10px">';
			$count = 0;
			foreach($finalImages as $imageName){
				$image = Title::newFromText("Image:" . $imageName);
				if ($image && $image->getArticleID() > 0) {
					$file = wfFindFile($image);
					if ($file && isset($file)) {
						if($i % 3 == 0)
							$html .= "<tr>";
						$thumb = $file->getThumbnail(80, 80, true, true);
						$imageUrl = $image->getFullURL();
						$thumbUrl = $thumb->url;
						$imageTitle = $imageName;

						$html .= "<td valign='top' style='width:90px; padding-bottom:10px;'><a href='" . $imageUrl . "'><img src='" . wfGetPad($thumbUrl) . "' alt='" . $imageTitle . "' /></a></td>";
					
						if($i % 3 == 2)
							$html .= "</tr>";

						$i++;
					}
				}
			}
			if($i % 3 != 2)
				$html .= "</tr>";
			$html .= "</table></div>";
			
			return $html;
		}
		
	}

}


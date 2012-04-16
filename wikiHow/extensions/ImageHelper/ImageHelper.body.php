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
		foreach($articles as $t){
			$related = ImageHelper::setRelatedWikiHows($t);
			foreach($related as $titleString){
				$relatedArticles[$titleString] = $titleString;
			}
		}

		$section = '';

		$count = 0;
		$images = '';
		foreach($relatedArticles as $titleString){
			$t = Title::newFromText($titleString);
			if($t && $t->exists()) {
				$sk = $wgUser->getSkin();
				$msg = $t->getFullText();
				$link = $sk->makeKnownLinkObj( $t, $msg);
				$img = $sk->getGalleryImage($t, 103, 80);

				$images .= "<td><div>
					<a href='{$t->getFullURL()}' class='rounders2 rounders2_tl rounders2_white'>
						<img src='{$img}' alt='' width='103' height='80' class='rounders2_img' />
						<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
					</a>
					<a href='{$t->getFullURL()}'>{$t->getText()}</a>
					</div></td>";
				
				if (++$count == 5) break;
			}
		}

		if($count > 0){
			$section .= "<h2>" . wfMsg('ih_relatedArticles') . "</h2>";
			$section .= "<table class='featuredArticle_Table'>";
			$section .= $images;
			$section .= "</table>";
		}

		$wgOut->addHTML($section);
	}

	function setRelatedWikiHows($title){
		global $wgTitle, $wgParser, $wgMemc, $wgUser;

		$key = wfMemcKey("ImageHelper_related" . $title->getArticleID());
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}

		$templates = wfMsgForContent('ih_categories_ignore');
		$templates = split("\n", $templates);
		$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
		$templates = array_flip($templates); // make the array associative.

		$r = Revision::newFromTitle($title);
		$relatedTitles = array();
		if ($r) {
			$text = $r->getText();
			$whow = WikiHow::newFromText($text);
			$related = preg_replace("@^==.*@m", "", $whow->getSection('related wikihows'));

			if($related != ""){
				$preg = "/\\|[^\\]]*/";
				$related = preg_replace($preg, "", $related);
				$rarray = split("\n", $related);
				foreach($rarray as $related) {
					preg_match("/\[\[(.*)\]\]/", $related, $rmatch);

					//check to make sure this article isn't in a category
					//that we don't want to show
					$title = Title::MakeTitle( $s->page_namespace, $rmatch[1] );
					$cats = ($title->getParentCategories());
					if (is_array($cats) && sizeof($cats) > 0) {
						$keys = array_keys($cats);
						$found = false;
						for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
							$t = Title::newFromText($keys[$i]);
							if (isset($templates[urldecode($t->getPartialURL())]) ) {
								//this article is in a category we don't want to show
								$found = true;
								break;
							}
						}
						if($found)
							continue;
					}

					$relatedTitles[] = $rmatch[1];
				}
				
			}
			else{
				$cats = ($title->getParentCategories());
				$cat1 = '';
				if (is_array($cats) && sizeof($cats) > 0) {
					$keys = array_keys($cats);
					$cat1 = '';
					$found = false;
					$templates = wfMsgForContent('ih_categories_ignore');
					$templates = split("\n", $templates);
					$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
					$templates = array_flip($templates); // make the array associative.
					for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
						$t = Title::newFromText($keys[$i]);
						if (isset($templates[urldecode($t->getPartialURL())]) ) {
							continue;
						}
						$cat1 = $t->getDBKey();
						$found = true;
						break;
					}
				}
				if ($cat1 != '') {
					$sk = $wgUser->getSkin();
					$dbr = wfGetDB( DB_SLAVE );
					$num = intval(wfMsgForContent('num_related_articles_to_display'));
					$res = $dbr->select('categorylinks', 'cl_from', array ('cl_to' => $cat1),
						"WikiHowSkin:getRelatedArticlesBox",
						array ('ORDER BY' => 'rand()', 'LIMIT' => $num*2));
					
					$count = 0;
					while (($row = $dbr->fetchObject($res)) && $count < $num) {
						if ($row->cl_from == $title->getArticleID()) {
							continue;
						}
						$t = Title::newFromID($row->cl_from);
						if (!$t) {
							continue;
						}
						if ($t->getNamespace() != NS_MAIN) {
							continue;
						}
						$relatedTitles[] = $t->getText();
						$count++;
					}

				}
			}
		}

		$wgMemc->set(wfMemcKey("ImageHelper_related" . $title->getArticleID()), $relatedTitles);
		
		return $relatedTitles;
		
	}

	/**
	 *
	 * Returns an array of titles that have links to the given
	 * title (presumably an image). All return articles will be in the
	 * NS_MAIN namespace and will also not be in a excluded category.
	 * 
	 */
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

		$templates = wfMsgForContent('ih_categories_ignore');
		$templates = split("\n", $templates);
		$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
		$templates = array_flip($templates); // make the array associative.

		while ( $s = $dbr->fetchObject( $res ) ) {
			//check if in main namespace
			if($s->page_namespace != NS_MAIN)
					continue;

			//check if in category exclusion list
			$title = Title::MakeTitle( $s->page_namespace, $s->page_title );
			$cats = ($title->getParentCategories());
			if (is_array($cats) && sizeof($cats) > 0) {
				$keys = array_keys($cats);
				$found = false;
				for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
					$t = Title::newFromText($keys[$i]);
					if (isset($templates[urldecode($t->getPartialURL())]) ) {
						//this article is in a category we don't want to show
						$found = true;
						break;
					}
				}
				if($found)
					continue;
			}
			if($s->page_title != $imageTitle){
				$articles[] = $title;
			}

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

	}

	function getImages($articleId){
		global $wgMemc;

		$result = $wgMemc->get(wfMemcKey("ImageHelper_getImages" . $articleId));
		if ($result) {
			return $result;
		}

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
	 * This function takes an array of titles and finds other images
	 * that are in those articles.
	 */
	function getConnectedImages($articles, $title){
		global $wgOut, $wgUser, $wgMemc;

		wfLoadExtensionMessages('ImageHelper');

		$exceptions = wfMsg('ih_exceptions');
		$imageExceptions = split("\n", $exceptions);

		$sk = $wgUser->getSkin();

		$result = $wgMemc->get(wfMemcKey("ImageHelper_getConnectedImages" . $imageName));
		if ($result) {
			$wgOut->addHTML($result);
			return;
		}

		$imageName = $title->getDBkey();
		if(in_array($imageName, $imageExceptions)){
			$wgMemc->set(wfMemcKey("ImageHelper_getConnectedImages" . $imageName), "");
			return;
		}
		
		$html = '';

		$noImageArray = array();
		foreach($articles as $title){
			$imageUrl = array();
			$thumbUrl = array();
			$imageTitle = array();
			$imageWidth = array();
			$imageHeight = array();
			
			$results = ImageHelper::getImages($title->getArticleID());

			$count = 0;
			if(count($results) <= 1){
				$noImageArray[] = $title;
				continue;
			}
			
			$titleLink = $sk->makeKnownLinkObj( $title, "" );
			$found = false;
			foreach($results as $row){
				if($count >= 5)
					break;
				if($row['il_to'] != $imageName && !in_array($row['il_to'], $imageExceptions)){
					$image = Title::newFromText("Image:" . $row['il_to']);
					if ($image && $image->getArticleID() > 0) {
						$file = wfFindFile($image);
						if ($file && isset($file)) {
							$thumb = $file->getThumbnail(103, -1, true, true);
							$imageUrl[] = $image->getFullURL();
							$thumbUrl[] = $thumb->url;
							$imageTitle[] = $row['il_to'];
							$imageWidth[] = $thumb->getWidth();
							$imageHeight[] = $thumb->getHeight();
							$count++;
							$found = true;
						}
					}
				}
			}
			if($count > 0){
				$tmpl = new EasyTemplate( dirname(__FILE__) );
				$tmpl->set_vars(array(
					'imageUrl' => $imageUrl,
					'thumbUrl' => $thumbUrl,
					'imageTitle' => $imageTitle,
					'title' => $titleLink,
					'numImages' => count($imageUrl),
					'imageWidth' => $imageWidth,
					'imageHeight' => $imageHeight,
					'imgStrip' => false
				));

				$html .= $tmpl->execute('connectedImages.tmpl.php');
			}
			else
				$noImageArray[] = $title;
		}

		if(sizeof($noImageArray) > 0){
			$html .= "<h2>" . wfMsg('ih_otherlinks') . "</h2><ul class='im-images'>";
			foreach($noImageArray as $title){
				$link = $sk->makeKnownLinkObj( $title, "" );
				$html .= "<li>{$link}</li>\n";
			}
			$html .= "</ul>";
		}
		
		$wgMemc->set(wfMemcKey("ImageHelper_getConnectedImages" . $imageName), $html);

		$wgOut->addHTML($html);
	}

	function displayBottomAds(){
		global $wgUser, $wgOut;
		
		if($wgUser->getID() == 0){
			$sk = $wgUser->getSkin();
			$channels = wikihowAds::getCustomGoogleChannels('imagead2', false);
			$embed_ads = wfMsg('imagead2', $channels[0], $channels[1] );
			$embed_ads = preg_replace('/\<[\/]?pre\>/', '', $embed_ads);
			$wgOut->addHTML($embed_ads);
		}
	}

	/*
	 * All this code is taken from ImagePage.php in includes
	 */
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
			$wgOut->addHTML("<div style='margin-top:10px;' class='im-images'>");
			$wgOut->addHTML("<strong>Description: </strong>");
			$wgOut->addHTML($description);
			$wgOut->addHTML("</div>");
		}
		
	}

	function addSideWidgets($title){
		global $wgUser;
		$skin = $wgUser->getSkin();
		
		//first add related images
		$html = ImageHelper::getRelatedImagesWidget($title);
		if($html != "")
			$skin->addWidget($html);
		//first add image info
		$html = ImageHelper::getImageInfoWidget($title);
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
		foreach($articles as $t){
			$related = ImageHelper::setRelatedWikiHows($t);
			foreach($related as $titleString){
				$relatedArticles[$titleString] = $titleString;
			}
		}


		$section = '';
		$count = 0;
		$images = '';
		foreach($relatedArticles as $titleString){
			$t = Title::newFromText($titleString);
			if($t && $t->exists()) {
				$sk = $wgUser->getSkin();
				$msg = $t->getFullText();
				$link = $sk->makeKnownLinkObj( $t, $msg);
				$img = $sk->getGalleryImage($t, 103, 80);

				$images .= "<tr><td id='thumb'><span class='rounders2 rounders2_sm rounders2_tan'>
				<a href='{$t->getFullURL()}'><img class='rounders2_img' alt='' src='{$img}' />
				<img class='rounders2_sprite' alt='' src='".wfGetPad('/skins/WikiHow/images/corner_sprite.png')."'/>
				</a>
				</span>
				</td>
				<td>{$link}</td></tr>\n";

				if (++$count == 5) break;
			}
		}
		if($count > 0){
			$section .= "<h3>" . wfMsg('ih_relatedArticles') . "</h3>";
			$section .= "<table id='side_related_articles'>";
			$section .= $images;
			$section .= "</table>";
		}
		

		return $section;
	}

	function getImageInfoWidget($title){
		global $wgUser, $wgOut, $wgTitle;

		$sk = $wgUser->getSkin();

		$t = Title::newFromText('Image-Templates', NS_CATEGORY);
		if($t){
			$cv = new CategoryViewer($t);
			$cv->clearCategoryState();
			$cv->doCategoryQuery();

			$templates = array();
			foreach($cv->articles as $article){
				$start = strrpos($article, 'title="Template:');
				if($start > 0){
					$end = strrpos($article, '"', $start + 16 + 1);
					if($end > 0){
						$templates[] = strtolower(str_replace(' ', '-', substr($article, $start + 16, $end - $start - 16)));
					}
				}
				
			}

			$license = '';
			$content = preg_replace_callback(
				'@({{([^}|]+)(\|[^}]*)?}})@',
				function ($m) use ($templates, &$license) {
					$name = trim(strtolower($m[2]));
					$name = str_replace(' ', '-', $name);
					foreach ($templates as $template) {
						if ($name == $template) {
							$license .= $m[0];
							return '';
						}
					}
					return $m[1];
				},
				$this->getContent()
			);
		}

		$lastUser = $this->current->getUser();
		$userLink = $sk->makeLinkObj(Title::makeTitle(NS_USER, $lastUser), $lastUser);

		$html = "<div id='im-info' style='margin-top:-15px; word-wrap: break-word;'>";
		$html .= $wgOut->parse("=== Licensing / Attribution === \n" . $license ) . wfMsg('image_upload', $userLink);


		//now remove old licensing header
		$content = str_replace("== Licensing ==", "", $content);
		$content = str_replace("== Summary ==", "=== Summary ===", $content);
		$content = trim($content);

		if(strlen($content) > 0 && substr($content, 0, 1) != "=")
			$content = "=== Summary === \n" . $content;
		else{

		}

		$html .= $wgOut->parse($content);
		
		$html .= "</div>";

		return $html;
	}

	function getRelatedImagesWidget($title){
		global $wgUser;

		$exceptions = wfMsg('ih_exceptions');
		$imageExceptions = split("\n", $exceptions);
		
		$articles = ImageHelper::getLinkedArticles($title);
		$images = array();
		foreach($articles as $t){
			$results = ImageHelper::getImages($t->getArticleID());
			if(count($results) <= 1){
				continue;
			}

			$titleDb = $title->getDBkey();
			foreach($results as $row){
				if($row['il_to'] != $titleDb && !in_array($row['il_to'], $imageExceptions)){
					$images[] = $row['il_to'];
				}
			}
		}

		$count = 0;
		$maxLoc = count($images);
		$maxImages = $maxLoc;
		$finalImages = array();
		while($count < 6 && $count < $maxImages){
			$loc = rand(0, $maxLoc);
			if($images[$loc] != null){
				$image = Title::newFromText("Image:" . $images[$loc]);
				if ($image && $image->getArticleID() > 0) {
					$file = wfFindFile($image);
					if ($file && isset($file)) {
						$finalImages[] = array('title'=>$image, 'file'=>$file);
						$images[$loc] = null;
						$count++;
					}
					else
						$maxImages--;
				}
				else
					$maxImages--;
				$images[$loc] = null;
			}
		}

		if(count($finalImages) > 0){
			$html = '<div><h3>' . wfMsg('ih_relatedimages_widget') . '</h3><table style="margin-top:10px">';
			$count = 0;
			foreach($finalImages as $imageObject){
				$image = $imageObject['title'];
				$file = $imageObject['file'];
				if($count % 3 == 0)
					$html .= "<tr>";
				$thumb = $file->getThumbnail(80, -1, true, true);
				$imageUrl = $image->getFullURL();
				$thumbUrl = $thumb->url;
				$imageTitle = $imageName;

				$html .= "<td valign='top' style='width:90px; padding-bottom:10px;'>";

				$html .= "<div class='mwimg'>";
				$html .= "<div style='width:" . $thumb->getWidth() . "px;'>";
				$html .= "<div style='width:" . $thumb->getWidth() . "px;height:" . $thumb->getHeight() . "px' class='rounders tan'>";
				$html .= "<a href='" . $imageUrl . "' title='" . $imageTitle . "' class='image'>";
				$html .= "<img border='0' class='mwimage101' src='" . wfGetPad($thumbUrl) ."' alt='" . $imageTitle . "'>";
				$html .= "</a>";
				$html .= "<div class='corner top_left'></div>";
				$html .= "<div class='corner top_right'></div>";
				$html .= "<div class='corner bottom_left'></div>";
				$html .= "<div class='corner bottom_right'></div>";
				$html .= "</div>";
				$html .= "</div>";
				$html .= "</div>";

				$html .= "</td>";
				//$html .= "<td valign='top' style='width:90px; padding-bottom:10px;'><a href='" . $imageUrl . "'><img src='" . wfGetPad($thumbUrl) . "' alt='" . $imageTitle . "' /></a></td>";

				if($count % 3 == 2)
					$html .= "</tr>";

				$count++;
			}
			if($count % 3 != 2)
				$html .= "</tr>";
			$html .= "</table></div>";
			
			return $html;
		}
		
	}

}


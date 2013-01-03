<?php

class ImageHelper extends UnlistedSpecialPage {

	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ImageHelper' );
	}

	function getLinkedArticles($imageTitle){
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
		return $articles;
	}

	function getSummaryInfo($image){
		global $wgOut;

		$sizes = ImageHelper::getDisplaySize($image);

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'preview' => $sizes['width'] . "x" . $sizes['height'] . "px",
			'full' => "<a href='" . $image->getFullUrl() . "'>" . $image->getWidth() . "x" . $image->getHeight() . " px </a>",
			'file' => $sizes['full'] == 0?$sk->formatSize($image->getSize()):wfMsg( 'file-nohires'),
			'mime' => $image->getMimeType()
		));
		
		$wgOut->addHTML($tmpl->execute('fileInfo.tmpl.php'));
	}

	/**
	 *
	 * This function takes a list of articles and finds other images
	 * that are in those articles.
	 */
	function getConnectedImages($articles, $imageName){
		global $wgOut, $wgUser;

		$sk = $wgUser->getSkin();
		$dbr = wfGetDB( DB_SLAVE );

		$noImageArray = array();
		foreach($articles as $article){
			$res = $dbr->select(array('imagelinks'), '*', array('il_from' => $article['id']));

			$count = 0;
			if($dbr->numRows($res) <= 1){
				$noImageArray[] = $article;
				continue;
			}
			$name = Title::MakeTitle( $article['namespace'], $article['title'] );
			$title = $sk->makeKnownLinkObj( $name, "" );
			$wgOut->addHTML("<h2>More images from $title</h2>");
			$wgOut->addHTML("<div class='im-images'>");
			while($row = $dbr->fetchRow($res)){
				if($count >= 5)
					break;
				if($row['il_to'] != $imageName){
					$imageTitle = $row['il_to'];
					$image = Title::newFromText("Image:" . $imageTitle);
					if ($image && $image->getArticleID() > 0) {
						$file = wfFindFile($image);
						if ($file && isset($file)) {
							$thumb = $file->getThumbnail(103, -1, true, true);
							$wgOut->addHTML("<a href='" . $image->getFullURL() . "'><img src='$thumb->url' alt='$imageTitle' /></a>");
						}
					}
					$count++;
				}
			}
			$wgOut->addHTML("<div class='clearall'></div></div>");
		}

		if(sizeof($noImageArray) > 0){
			$wgOut->addHTML("<h2>Links</h2><ul>");
			foreach($noImageArray as $article){
				$name = Title::MakeTitle( $article['namespace'], $article['title'] );
				$link = $sk->makeKnownLinkObj( $name, "" );
				$wgOut->addHTML( "<li>{$link}</li>\n" );
			}
			$wgOut->addHTML("</ul>");
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
			$maxWidth = 640;
		$maxHeight = $max[1];

		if ( $this->img->exists() ) {
			# image
			$page = $wgRequest->getIntOrNull( 'page' );
			if ( is_null( $page ) ) {
				$params = array();
				$page = 1;
			} else {
				$params = array( 'page' => $page );
			}
			$width_orig = $this->img->getWidth();
			$width = $width_orig;
			$height_orig = $this->img->getHeight();
			$height = $height_orig;

			if ( $this->img->allowInlineDisplay() ) {
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
				if ($this->img->isSafeFile()) {
					$icon= $this->img->iconThumb();

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

}


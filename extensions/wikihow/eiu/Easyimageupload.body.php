<?php

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once($IP.'/includes/EasyTemplate.php');
require_once('WikiHow.php');

class Easyimageupload extends UnlistedSpecialPage {

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Easyimageupload');
	}

	/**
	 * Set html template path for Easyimageupload actions
	 */
	public static function setTemplatePath() {
		EasyTemplate::set_path( dirname(__FILE__).'/' );
	}

	/**
	 * Hook into toolbar display on advanced edit or sectional edit page. Adds
	 * the image upload icon to the end of the toolbar html.
	 */
	public static function postDisplayAdvancedToolbarHook(&$toolbar) {
		self::setTemplatePath();
		$html =
			self::getUploadBoxJS() .
			self::getUploadBoxJSAddLoadHook() .
			EasyTemplate::html('eiu_advanced_edit_button');
		$toolbar .= $html;
		return true;
	}

	/**
	 * Hook into the pre-parser article wiki text.  Inserts the wiki text
	 * '{{IntroNeedsImage}}' close to start of intro text if article is
	 * deemed to need it.
	 */
	public static function preParserIntroImageNotFoundHook(&$article, &$text) {
		$validArticle = 
			$article &&
			$article->getTitle() &&
			$article->getTitle()->getNameSpace() == NS_MAIN &&
			$article->getTitle()->getText() != 'Main Page';

		// Make sure the article is typical and there is no IntroNeedsImage 
		// template already in the article
		if ($validArticle &&
			!preg_match('@\{\{IntroNeedsImage\}\}@', $text))
		{
			// grab the intro section
			if (preg_match('@^((.|\n)*)==(.|\n)*$@U', $text, $m)) {
				$intro = $m[1];
			} else {
				$intro = $text;
			}
			// if there's no image in the intro section, add this 
			// IntroNeedsImage template
			if (!preg_match('@\[\[Image:@', $intro)) {
				// add this new template to article after existing templates
				$text = preg_replace('@^(\{\{([^}][^}])+\}\})?@', '$1{{IntroNeedsImage}}', $text);
			}
		}

		// make sure the article continues parsing
		return true;
	}

	const EIU_MAX_THUMB_SIZE = 130;

	/**
	 * Formats the image results from the local search of this mediawiki 
	 * database.
	 *
	 * METHOD NOT USED RIGHT NOW
	 *
	private function formatImageResults($images, $msg, $page, $total) {
		$photos = array();
		foreach ($images as $img) {
			$height = $img->img_height;
			$width = $img->img_width;
			// max dimensions of .. 130x130px (EIU_MAX_THUMB_SIZE defines 130px)
			if ($height > $width && $height > self::EIU_MAX_THUMB_SIZE) {
				$ratio = $height / $width;
				$width = number_format(self::EIU_MAX_THUMB_SIZE / $ratio, 0);
				$height = self::EIU_MAX_THUMB_SIZE;
			} elseif ($width > self::EIU_MAX_THUMB_SIZE) {
				$ratio = $width / $height;
				$height = number_format(self::EIU_MAX_THUMB_SIZE / $ratio, 0);
				$width = self::EIU_MAX_THUMB_SIZE;
			}

			$title = Title::makeTitleSafe(NS_IMAGE, $img->img_name);
			$file = wfFindFile($title);
			if (!$file) {
				$photos[] = array('found' => false, 'name' => $img->img_name);
			}
			elseif ($title) {
				$thumb = $file->getThumbnail($width, $height, true);
				$details = array(
					'name' => $img->img_name,
				);
				$photos[] = array(
					'found' => true, 
					'thumb_url' => $thumb->url, 
					'name' => $img->img_name,
					'details' => json_encode($details),
				);
			}
		}

		$next_available = min(self::RESULTS_PER_PAGE, $total - ($page * self::RESULTS_PER_PAGE));
		$tmpl_vars = array(
			'src' => 'wiki',
			'msg' => $msg,
			'photos' => $photos,
			'page' => $page,
			'next_available' => $next_available,
			'RESULTS_PER_PAGE' => self::RESULTS_PER_PAGE,
		);
		$html = EasyTemplate::html('eiu_list_images', $tmpl_vars);
		return $html;
	}
	 */

	/**
	 * Find images in the current MW DB.
	 *
	 * METHOD NOT USED RIGHT NOW
	 *
	public function findImagesThisWiki($query, $page = 1) {
		$dbr = wfGetDB(DB_SLAVE);

		/// get total
		$res = $dbr->query("SELECT count(*) as count FROM image WHERE lower(img_name) LIKE '%".strtolower($dbr->escapeLike($query))."%'");
		$row = $dbr->fetchRow($res);
		$total = $row['count'];

		$offset = ($page - 1) * self::RESULTS_PER_PAGE;
		$res = $dbr->query("SELECT img_name, img_height, img_width FROM image  WHERE lower(img_name) LIKE '%".strtolower($dbr->escapeLike($query))."%' ORDER BY img_name ASC LIMIT $offset,".self::RESULTS_PER_PAGE.";");
		$images = array();
		while ($row = $dbr->fetchObject($res)) {
			$images[] = $row;
		}
		$html = self::formatImageResults($images, wfMsg('eiu-thiswikiimages', $total), $page, $total);
		return $html;
	}
	 */

	/**
	 * Find images most recently uploaded to local mediawiki DB.
	 *
	 * METHOD NOT USED RIGHT NOW
	 *
	public function getRecentlyUploadedImages($offset = 0) {
		global $wgOut, $wgUser;
		$offset = $offset < 0 ? 0 : intval($offset);
		$sql = "select img_name, img_height, img_width from image where img_user={$wgUser->getID()} order by img_timestamp desc limit ".$offset.",".self::RESULTS_PER_PAGE.';';
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query($sql);
		$next = $offset + self::RESULTS_PER_PAGE;
		$images = array();
		while ($row = $dbr->fetchObject($res)) {
			$images[] = $row;
		}
		$html = self::formatImageResults($images, wfMsg('eiu-recentlyuploaded'), $next);
		return $html;
	}
	 */

	/**
	 * Make a URL to refer to a Flick image. See also:
	 * http://www.flickr.com/services/api/misc.urls.html
	 */
	private static function makeFlickrURL($image, $size) {
		if ($size == 'thumb') {
			$size_token = '_t';
		}
		else {
			$size_token = '';
		}
		return 'http://farm'.$image['farm'].'.static.flickr.com/'.$image['server'].'/'.$image['id'].'_'.$image['secret'].$size_token.'.jpg';
	}

	const RESULTS_PER_PAGE = 8;

	/**
	 * List Flickr images matching search terms and our license requirements.
	 *
	 * This method is used by the Findimages class later in this file.
	 *
	 * @param $query search keywords for flickr search
	 * @return JSON listing flickr images
	 */
	public function findImagesFlickr($query, $page = 1) {
		global $IP, $wgUser;

		require_once($IP.'/extensions/3rdparty/phpFlickr-2.3.1/phpFlickr.php');
		$flickr = new phpFlickr(WH_FLICKR_API_KEY);
		// licence info:
		// http://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html
		// details on selected licences:
		// <license id="4" name="Attribution License" 
		//   url="http://creativecommons.org/licenses/by/2.0/" />
		// <license id="5" name="Attribution-ShareAlike License"
		//   url="http://creativecommons.org/licenses/by-sa/2.0/" />
		$images = $flickr->photos_search(array(
			'text' => $query,
			'tag_mode' => 'all',
			'page' => intval($page),
			'per_page' => self::RESULTS_PER_PAGE,
			'license' => '4,5',
			'sort' => 'relevance'
		));

		if ($images) {
			$total = intval($images['total']);

			$photos = array();
			foreach ($images['photo'] as $image) {
				// remove file extension if there was one in the title
				$title = preg_replace('@\.(jpg|gif|png)$@i', '', $image['title']);
				$details = array(
					'photoid' => @$image['id'],
					'ownerid' => @$image['owner'],
					'name' => $title.'.jpg',
					'url' => self::makeFlickrURL($image, 'normal'),
				);
				$photos[] = array(
					'found' => true,
					'thumb_url' => self::makeFlickrURL($image, 'thumb'),
					'details' => json_encode($details),
				);
			}
		}
		else {
			$total = 0;
		}

		$next_available = min(self::RESULTS_PER_PAGE, $total - ($page * self::RESULTS_PER_PAGE));
		$userid = $wgUser->getID();
		$formattedTotal = number_format($images['total'], 0, '', ',');
		$vars = array(
			//'isLoggedIn' => !empty($userid),
			//'src' => 'flickr',
			'msg' => wfMsg('eiu-flickrresults', $formattedTotal),
			'photos' => $photos,
			'page' => $page,
			'next_available' => $next_available,
			//'RESULTS_PER_PAGE' => self::RESULTS_PER_PAGE,
		);
		//IIA zero results message
		global $wgRequest;
		if ($wgRequest->getVal('intro-image-adder')) {
			if ($formattedTotal == 0) {
				$vars['msg'] = "<span class='iia_results'>". wfMsgWikiHtml('iia-eiu-flickrresults-none') ."</span>\n";
			} else {
				$vars['msg'] = "<span class='iia_results'>". wfMsgWikiHtml('iia-eiu-flickrresults', $formattedTotal) ."</span>\n";
			}
		}
		return json_encode($vars);
	}

	/**
	 * Return html for user selection of which step to add the image
	 */
	private function getCurrentStepBox() {
		return EasyTemplate::html('eiu_current_step_box');
	}

	/**
	 * Return html for find images (via flickr or wikimedia.org) box.
	 */
	private function getFindBox($articleTitle) {
		$vars = array(
			'title' => $articleTitle,
		);
		return EasyTemplate::html('eiu_find_box', $vars);
	}

	/**
	 * Return html for find images (via flickr or wikimedia.org) box.
	 */
	private function iiaGetFindBox($vars) {
		return EasyTemplate::html('iia_eiu_find_box', $vars);
	}

	/**
	 * Return html for image upload JS load hook.
	 */
	public function getUploadBoxJSAddLoadHook() {
		global $wgRequest;
		if ($wgRequest->getVal('subaction', '') === 'add-image-to-intro') {
			self::setTemplatePath();
			$html = EasyTemplate::html('eiu_js_load_hook');
			return $html;
		}
		else {
			return '';
		}
	}

	/**
	 * Return html for image upload and bootstrap JS
	 */
	public function getUploadBoxJS() {
		self::setTemplatePath();
		$vars = array(
			'GOOGLE_SEARCH_API_KEY' => WH_GOOGLE_AJAX_IMAGE_SEARCH_API_KEY, 
		);
		return EasyTemplate::html('eiu_js', $vars) .
			   self::getUploadBoxJSAddLoadHook();
	}

	/**
	 * Return html for user (POST form data) image upload box.
	 */
	private function getUploadBox() {
		$me = Title::makeTitle(NS_SPECIAL, 'Easyimageupload');
		$submitUrl = $me->getFullURL();
		return EasyTemplate::html( 'eiu_upload_box', array('submitUrl' => $submitUrl) );
	}

	/**
	 * Insert an image upload into the mediawiki database tables.  If the
	 * image insert was successful, a page showing the wiki text for their
	 * image is shown.  Otherwise, if the image file name already exists in 
	 * the database, a conflict page is returned to the user.
	 *
	 * @param $type string with either 'overwrite' or blank -- specifies
	 *   whether to force-overwrite an existing image
	 * @param $name filename chosen by user for uploaded image
	 * @param $mwname filename of the file in mediawiki DB
	 * @return outputs either a wikitext results page (if image filename 
	 *   didn't exist or force overwrite was selected) or a conflict page.
	 *   Returns an error string or empty string if no error.
	 */
	private function insertImage($type, $name, $mwname) {
		global $wgRequest, $wgUser, $wgOut, $wgFileExtensions;

		$license = $wgRequest->getVal('wpLicense', '');
		if (!empty($license)) {
			$attrib = $wgRequest->getVal('attribution');
			$comment = '{{' . $license . (!empty($attrib) ? '|' . $attrib : '') . '}}';
		} else {
			$comment = $wgRequest->getVal('ImageAttribution', '');
		}

		if (wfReadOnly()) {
			//header('X-screen-type: error');
			return wfMsg('eiu-readonly');
		}

		if (!empty($mwname) && !empty($name)) {
			$name = urldecode($name);
			$name = preg_replace('/[^'.Title::legalChars().']|[:\/\\\\]|\?/', '-', $name);
			$name = preg_replace('@&amp;@', '&', $name);

			// did they give no extension at all when they changed the name?
			list($first, $ext) = self::splitFilenameExt($name);
			$ext = strtolower($ext);

			$title = Title::makeTitleSafe(NS_IMAGE, $name);
			if (is_null($title) || !in_array($ext, $wgFileExtensions)) {
				//header('X-screen-type: error');
				return wfMsg('eiu-filetype-incorrect');
			}

			$newFile =  true;

			if (!$title->exists()) {
				//
				// DB entry for file doesn't exist. User renamed their 
				// upload or it never existed.
				//

				// is the target protected?
				$permErrors = $title->getUserPermissionsErrors('edit', $wgUser);
				$permErrorsUpload = $title->getUserPermissionsErrors('upload', $wgUser);

				if ($permErrors || $permErrorsUpload) {
					//header('X-screen-type: error');
					return wfMsg('This image is protected');
				}

				$temp_file = new LocalFile(Title::newFromText($mwname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());
				$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());

				$file->upload($temp_file->getPath(), $comment, $comment);
				$temp_file->delete('');

			} elseif ($type == 'overwrite') {
				//
				// DB entry exists and user selected to overwrite it
				//

				$title = Title::newFromText($name, NS_IMAGE);
				// is the target protected?
				$permErrors = $title->getUserPermissionsErrors('edit', $wgUser);
				$permErrorsUpload = $title->getUserPermissionsErrors('upload', $wgUser);
				$permErrorsCreate = ($title->exists() ? array() : $title->getUserPermissionsErrors('create', $wgUser));

				if ($permErrors || $permErrorsUpload || $permErrorsCreate) {
					//header('X-screen-type: error');
					return wfMsg('This image is protected');
				}

				$file_name = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
				$file_mwname = new TempLocalFile(Title::newFromText($mwname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());

				$file_name->upload($file_mwname->getPath(), $comment, $comment);
				$file_mwname->delete('');
				$newFile = false;

			} elseif ($type == 'existing') {
				//
				// DB entry exists and user doesn't want to overwrite or
				// rename, so they use the existing file from the DB.
				//

				//header('X-screen-type: existing');
				$title = Title::newFromText($name, NS_IMAGE);
				//$file = wfFindFile($title);

				/*$props = array(
					'src' => 'upload',
					'is_image' => $file->media_type == 'BITMAP' || $file->media_type == 'DRAWING',
					'width' => $file->width,
					'height' => $file->height,
					'file' => $file,
					'mwname' => $name,
				);
				return $this->detailsPage($props);*/

			} else {
				//
				// There was a conflict with an existing file in the
				// DB.  Title exists and overwrite action not taken yet.
				//

				//header('X-screen-type: conflict');

				$data = array('wpUpload' => 1, 'wpSourceType' => 'web', 'wpUploadFileURL' => '');
				$form = new UploadForm(new FauxRequest($data, true));

				// generate title if current one is taken
				$suggestedName = self::generateNewFilename($name);

				// extensions check
				list($first, $ext) = self::splitFilenameExt($suggestedName);

				$title = Title::newFromText($name, NS_IMAGE);
				$file = wfFindFile($title);

				$vars = array(
					'suggestedFirstPart' => $first,
					'extension' => strtolower($ext),
					'name' => $name,
					'mwname' => $mwname,
					'file' => $file,
					'image_comment' => $comment,
				);
				$wgOut->setStatusCode(200);
				$wgOut->addHTML(EasyTemplate::html('eiu_conflict', $vars));
				// return no error
				return '';
			}

			// add watch to file is user needs it
			if ($wgUser->getOption('watchdefault') || ($newFile && $wgUser->getOption('watchcreations'))) {
				$wgUser->addWatch($title);
			}

			$db =& wfGetDB(DB_MASTER);
			$db->commit();
		} elseif (empty($mwname)) {
			$title = Title::makeTitleSafe(NS_IMAGE, $name);
		} elseif ($name !== null) {
			return WfMsg('eiu-warn3');
		} else { // name === null
			//header('X-screen-type: error');
			$title = Title::newFromText($mwname, NS_IMAGE);
		}

		$file = wfFindFile($title);
		if (!is_object($file)) {
			//header('X-screen-type: error');
			return wfMsg('File not found');
		}

		//header('X-screen-type: summary');

		$details = self::splitValuePairs($wgRequest->getVal('image-details'));
		$tag = self::makeImageWikiTag($title, $file, $details);

		$vars = array(
			'tag' => $tag,
			'file' => $file,
			'width' => $details['chosen-width'],
			'height' => $details['chosen-height'],
			'imageFilename' => $title->getText(),
			'details' => $details,
		);
		$html = EasyTemplate::html('eiu_upload_summary', $vars);
		$wgOut->setStatusCode(200);
		$wgOut->addHTML($html);

		// return no error
		return '';
	}

	/**
	 * Uses image details to return a string like [[Image:foo.jpg|thumb|right|my caption]]
	 *
	 * @param $title Title object for image db entry
	 * @param $file File object for file storage info
	 * @param $details array of image and layout details
	 * @return string of mediawiki text
	 */
	private static function makeImageWikiTag($title, $file, $details) {
		$tag = '[[' . $title->getPrefixedText();
		$hasCaption = ($details['caption'] != '');
		if ($file->getMediaType() == 'BITMAP' || $file->getMediaType() == 'DRAWING')
		{
			$tag .= '|'.$details['layout'];
			$width_percent = intval($details['width']);
			if ($width_percent < 100) {
				$width = intval($details['chosen-width']);
				if ($width > 0) {
					$tag .= '|'.$width.'px';
				}
			}
		}
		if ($hasCaption) {
			$tag .= '|thumb|'.$details['caption'];
		}
		$tag .= ']]';
		return $tag;
	}

	/**
	 * Split a string like "foo=bar&x=1" into an array like:
	 * array('foo'=>'bar','x'=>'1');
	 *
	 * @param $encodedDetails param string
	 * @return key/value pair array
	 */
	private static function splitValuePairs($encodedDetails) {
		$vals = explode('&', $encodedDetails);
		$pairs = array();
		foreach ($vals as $val) {
			list($k, $v) = explode('=', $val);
			list($k, $v) = array(urldecode($k), urldecode($v));
			$pairs[$k] = $v;
		}
		return $pairs;
	}

	/**
	 * Split a file name such as "foo bar.jpg" into array('foo bar', 'jpg')
	 *
	 * @param $name file name string
	 * @return array with key 0 being the first part and key 1 being the
	 *   extension.
	 */
	private static function splitFilenameExt($name) {
		preg_match('@^(.*)(\.([^.]+))?$@U', $name, $m);
		return array($m[1], $m[3]);
	}

	/**
	 * Generate a new file name such as "foobar 2.jpg" if both filenames
	 * "foobar.jpg" and "foobar 1.jpg" exist in the database.
	 *
	 * @param $name original filename
	 * @return new, unique filename
	 */
	private static function generateNewFilename($name) {
		$name = preg_replace('/[^'.Title::legalChars().']|[:\/\\\\]|\?/', '-', $name);
		$newName = $name;
		list($first, $ext) = self::splitFilenameExt($name);
		$i = 1;
		do {
			$title = Title::newFromText($newName, NS_IMAGE);
			if (!$title->exists()) break;
			$newName = $first . ' ' . $i++ . '.' . $ext;
		} while ($i < 1000);
		return $newName;
	}

	/**
	 * Generate a MW tag for a URL scraped from wikimedia.org.
	 *
	 * Note: this code was copied from extensions/ImportFreeImages/ImportFreeImages.body.php
	 */
	private function getWPLicenseTag($imgUrl) {
		$validLicenses = array(
			'cc-by-sa-all', 'PD', 'GFDL', 'cc-by-sa-3.0', 'cc-by-sa-2.5', 
			'FAL', 'cc-by-3.0', 'cc-by-2.5', 'GDL-en', 'cc-by-sa-2.0', 
			'cc-by-2.0', 'attribution');

		$pathOnly = str_replace('http://upload.wikimedia.org/', '', $imgUrl);
		$parts = split('/', $pathOnly);

		$img_title = '';
		if (sizeof($parts) == 7)
			$img_title = $parts[5];
		else if(sizeof($parts) == 5)
			$img_title = $parts[4];

		if (!empty($img_title)) {
			$wpUrl = "http://commons.wikimedia.org/wiki/Image:{$img_title}";
			$license = 'unknown';
			$contents = @file_get_contents("http://commons.wikimedia.org/w/index.php?title=Image:{$img_title}&action=raw");
			foreach ($validLicenses as $lic) {
				if (strpos($contents, "{{$lic}") !== false ||
					strpos($contents, "{{self|{$lic}") !== false ||
					strpos($contents, "{{self2|{$lic}") !== false)
				{
					$license = $lic;
					break;
				}
			}
			$comment = "{{commons|{$imgUrl}|{$wpUrl}|{$license}}}";
		} else {
			$comment = "{{commons|{$imgUrl}}}";
		}

		return $comment;
	}

	/**
	 * Accept a request to upload an image either via POST data (user upload)
	 * or via flickr or google / wikimedia.org search.
	 *
	 * @param $src string with value 'upload', 'flickr' or 'wiki'
	 * @return html outputs image details page
	 */
	private function uploadImage($src) {
		global $wgRequest, $wgUser, $IP;
		if ($src == 'upload') {
			$tempname = TempLocalFile::createTempFilename();
			$file = new TempLocalFile(Title::newFromText($tempname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());
			$name = $wgRequest->getFileName('wpUploadFile');
			$file->upload($wgRequest->getFileTempName('wpUploadFile'), '', '');
			$comment = '';
		//} elseif ($src == 'wiki') {
		//	$details = (array)json_decode($wgRequest->getVal('img-details'));
		//	$name = $details['name'];
		//	$tempname = '';

		//	$title = Title::makeTitleSafe(NS_IMAGE, $name);
		//	$file = wfFindFile($title);
		} elseif ($src == 'flickr' || $src == 'wiki') {
			$tempname = TempLocalFile::createTempFilename();
			$file = new TempLocalFile(Title::newFromText($tempname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());

			$details = (array)json_decode($wgRequest->getVal('img-details'));
			$name = $details['name'];

			// scrape the file using curl
			$filename = '/tmp/tmp-curl-'.mt_rand(0,100000000).'.jpg';
			$ch = curl_init($details['url']);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$fp = fopen($filename, 'w');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			$ret = curl_exec($ch);
			curl_close($ch);
			fclose($fp);

			$url = isset($details['url']) ? $details['url'] : '';
			if ($src == 'flickr' || preg_match('@^http://[^/]*flickr@', $details['url'])) {
				require_once($IP.'/extensions/3rdparty/phpFlickr-2.3.1/phpFlickr.php');
				$flickr = new phpFlickr(WH_FLICKR_API_KEY);
				$photo = $flickr->photos_getInfo($details['photoid']);
				$license = $photo['license'];
				$username = $photo['owner']['username'];
				$comment = '{{flickr'.intval($license).'|'.wfEscapeWikiText($details['photoid']).'|'.wfEscapeWikiText($details['ownerid']).'|'.wfEscapeWikiText($username).'}}';
			} else {
				$comment = self::getWPLicenseTag($details['url']);
			}

			// finish initializing the $file obj
			$file->upload($filename, '', '');
		}
		$props = array(
			'src' => $src,
			'name' => $name,
			'mwname' => $tempname,
			'is_image' => $file->media_type == 'BITMAP' || $file->media_type == 'DRAWING',
			'width' => $file->width,
			'height' => $file->height,
			'upload_file' => $file,
			'image_comment' => $comment,
		);
		$this->detailsPage($props);
	}

	/**
	 * Display the image details page
	 */
	private function detailsPage($props) {
		global $wgOut;
		$html = EasyTemplate::html('eiu_image_details', $props);
		$wgOut->addHTML($html);
	}

	/**
	 * Resize (to max dimensions of 500x500) then output an image for display
	 *
	 * @param $url URL to scrape from uploads.wikimedia.org
	 */
	private function resizeAndDisplayImage($url) {
		global $wgImageMagickConvertCommand;
		$MAX_DIMENSIONS = '500x500';

		// I couldn't find a way to output a JPEG file in binary using
		// the MediaWiki framework, so I just do it myself using php functions

		// scrape image
		$tmpfile = tempnam('/tmp', 'eiu-resize-in-');
		$tmpfile_small = tempnam('/tmp', 'eiu-resize-out-') . '.jpg';
		if (preg_match('@^http://upload.wikimedia.org/@', $url)) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			$fp = fopen($tmpfile, 'w');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			$success = curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		} else {
			$success = false;
		}

		// resize image
		if ($success) {
			$cmd = $wgImageMagickConvertCommand . ' ' . $tmpfile . ' -resize ' . $MAX_DIMENSIONS . ' ' . $tmpfile_small;
		} else {
			$msg = 'Image preview error';
			$cmd = $wgImageMagickConvertCommand . ' -size 320x100 xc:white  -font Bitstream-Charter-Regular -pointsize 24 -fill black -draw "text 25,65 \'' . $msg . '\'" ' . $tmpfile_small;
		}
		exec($cmd);

		// output image
		$img = file_get_contents($tmpfile_small);
		header('Content-type: image/jpeg');
		print $img;

		// cleanup
		@unlink($tmpfile);
		@unlink($tmpfile_small);
	}


	/**
	 * Cloned UploadImage() for IntroImageAdder IIA
	 * Main difference since IntroImageAdder does not require multiple screens 
	 * InsertImage is called at the end of the function
	 * 
	 * Accept a request to upload an image either via POST data (user upload)
	 * or via flickr or google / wikimedia.org search.
	 *
	 * @param $src string with value 'upload', 'flickr' or 'wiki'
	 * @return html outputs image details page
	 */
	private function iiaUploadImage($src) {
		global $wgRequest, $wgUser, $IP;

		$tempname = TempLocalFile::createTempFilename();
		$file = new TempLocalFile(Title::newFromText($tempname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());

		$details = (array)json_decode($wgRequest->getVal('img-details'));
		$name = $details['name'];

		// scrape the file using curl
		$filename = '/tmp/tmp-curl-iia-'.mt_rand(0,100000000).'.jpg';
		$ch = curl_init($details['url']);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$fp = fopen($filename, 'w');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$ret = curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		require_once($IP.'/extensions/3rdparty/phpFlickr-2.3.1/phpFlickr.php');
		$flickr = new phpFlickr(WH_FLICKR_API_KEY);
		$photo = $flickr->photos_getInfo($details['photoid']);
		$license = $photo['license'];
		$username = $photo['owner']['username'];
		$comment = '{{flickr'.intval($license).'|'.wfEscapeWikiText($details['photoid']).'|'.wfEscapeWikiText($details['ownerid']).'|'.wfEscapeWikiText($username).'}}';
		// finish initializing the $file obj
		$file->upload($filename, '', '');

		$props = array(
			'src' => $src,
			'name' => $name,
			'mwname' => $tempname,
			'is_image' => $file->media_type == 'BITMAP' || $file->media_type == 'DRAWING',
			'width' => $file->width,
			'height' => $file->height,
			'upload_file' => $file,
			'image_comment' => $comment,
		);
		//$this->detailsPage($props);
		$type = '';
		$name = $wgRequest->getVal('name');
		$mwname = $wgRequest->getVal('mwname');
		$this->iiaInsertImage($props);
	}

	/**
	 * Cloned insertImage() for IntroImageAdder IIA
	 * Bypasses additional screens for the Image tool and assumes user will use image already uploaded
	 *
	 * Insert an image upload into the mediawiki database tables.  If the
	 * image insert was successful, user will progress to next article needing
	 * an intro image.  Otherwise, if the image file name already exists in <br>
	 * the database, we will using the existing article and user will progress to 
	 * next article.
	 *
	 * @param $type string with either 'overwrite' or blank -- specifies
	 *   whether to force-overwrite an existing image
	 * @param $name filename chosen by user for uploaded image
	 * @param $mwname filename of the file in mediawiki DB
	 * @return outputs either a wikitext results page (if image filename 
	 *   didn't exist or force overwrite was selected) or a conflict page.
	 *   Returns an error string or empty string if no error.
	 */
	private function iiaInsertImage($props) {
		global $wgRequest, $wgUser, $wgOut;
		$name = $props['name'];
		$mwname = $props['mwname'];

		/*
		$license = $wgRequest->getVal('wpLicense', '');
		if (!empty($license)) {
			$attrib = $wgRequest->getVal('attribution');
			$comment = '{{' . $license . (!empty($attrib) ? '|' . $attrib : '') . '}}';
		} else {
			$comment = $wgRequest->getVal('ImageAttribution', '');
		}
		*/
		$comment = $props['image_comment'];

		if (wfReadOnly()) {
			//header('X-screen-type: error');
			return wfMsg('eiu-readonly');
		}

		if (!empty($mwname) && !empty($name)) {
			$name = urldecode($name);
			$name = preg_replace('/[^'.Title::legalChars().']|[:\/\\\\]/', '-', $name);
			$name = preg_replace('@&amp;@', '&', $name);			
			$name = trim($name);
			
			// did they give no extension at all when they changed the name?
			list($first, $ext) = self::splitFilenameExt($name);

			$title = Title::makeTitleSafe(NS_IMAGE, $name);
			if (is_null($title)) {
				//header('X-screen-type: error');
				return wfMsg('eiu-filetype-incorrect');
			}

			$newFile =  true;

			if (!$title->exists()) {
				//
				// DB entry for file doesn't exist. User renamed their 
				// upload or it never existed.
				//

				// is the target protected?
				$permErrors = $title->getUserPermissionsErrors('edit', $wgUser);
				$permErrorsUpload = $title->getUserPermissionsErrors('upload', $wgUser);

				if ($permErrors || $permErrorsUpload) {
					//header('X-screen-type: error');
					return wfMsg('This image is protected');
				}

				$temp_file = new LocalFile(Title::newFromText($mwname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());
				$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());

				$file->upload($temp_file->getPath(), $comment, $comment);
				$temp_file->delete('');
			} else {
				//
				// DB entry for file exists so create a random new name with epoch
				//
				
				$suggestedName = self::generateNewFilename($name);
				$title = Title::makeTitleSafe(NS_IMAGE, $suggestedName);
	
				// is the target protected?
				$permErrors = $title->getUserPermissionsErrors('edit', $wgUser);
				$permErrorsUpload = $title->getUserPermissionsErrors('upload', $wgUser);

				if ($permErrors || $permErrorsUpload) {
					//header('X-screen-type: error');
					return wfMsg('This image is protected');
				}

				$temp_file = new LocalFile(Title::newFromText($mwname, NS_IMAGE), RepoGroup::singleton()->getLocalRepo());
				$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());

				$file->upload($temp_file->getPath(), $comment, $comment);
				$temp_file->delete('');
			}

			// add watch to file is user needs it
			if ($wgUser->getOption('watchdefault') || ($newFile && $wgUser->getOption('watchcreations'))) {
				$wgUser->addWatch($title);
			}

			$db =& wfGetDB(DB_MASTER);
			$db->commit();
		} elseif (empty($mwname)) {
			$title = Title::makeTitleSafe(NS_IMAGE, $name);
		} elseif ($name !== null) {
			return WfMsg('eiu-warn3');
		} else { // name === null
			//header('X-screen-type: error');
			$title = Title::newFromText($mwname, NS_IMAGE);
		}

		$file = wfFindFile($title);
		if (!is_object($file)) {
			//header('X-screen-type: error');
			return wfMsg('File not found');
		}

		//header('X-screen-type: summary');

		$details = self::splitValuePairs($wgRequest->getVal('image-details'));
		$tag = self::makeImageWikiTag($title, $file, $details);

		$vars = array(
			'tag' => $tag,
			'file' => $file,
			'width' => $details['chosen-width'],
			'height' => $details['chosen-height'],
			'imageFilename' => $title->getText(),
		);

		$html = IntroImageAdder::addIntroImage($vars);

		$wgOut->setStatusCode(200);
		$wgOut->addHTML($html);

		// return no error
		return;
	}

	/**
	 * Executes the Easyimageupload special page and all its sub-calls
	 */
	public function execute($par) {
		global $wgRequest, $wgUser, $wgOut, $wgLang, $wgServer;

		wfLoadExtensionMessages('Easyimageupload');

		self::setTemplatePath();

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		$wgOut->setArticleBodyOnly(true);

		if ($wgRequest->getVal('uploadform1')) {
			$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			$this->uploadImage( $wgRequest->getVal('src') );
		} elseif ($wgRequest->getVal('uploadform2'))  {
			$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			$type = $wgRequest->getVal('type');
			$name = $wgRequest->getVal('name');
			$mwname = $wgRequest->getVal('mwname');
			$error = $this->insertImage($type, $name, $mwname);
			$vars = !empty($error) ? array('error' => $error) : array();
			$wgOut->addHTML(EasyTemplate::html('eiu_add_error', $vars));
		} elseif ($wgRequest->getVal('ImageIsConflict'))  {
			$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			if ($wgRequest->getVal('ImageUploadUseExisting')) {
				$name = $wgRequest->getVal('ImageUploadExistingName');
				$wgRequest->setVal('type', 'existing');
			} elseif ($wgRequest->getVal('ImageUploadRename')) {
				$name = $wgRequest->getVal('ImageUploadRenameName').'.'.$wgRequest->getVal('ImageUploadRenameExtension');
				$wgRequest->setVal('type', 'overwrite');
			}
			$wgRequest->setVal('name', $name);
			$type = $wgRequest->getVal('type');
			$name = $wgRequest->getVal('name');
			$mwname = $wgRequest->getVal('mwname');
			$error = $this->insertImage($type, $name, $mwname);
			$vars = !empty($error) ? array('error' => $error) : array();
			$wgOut->addHTML(EasyTemplate::html('eiu_add_error', $vars));
		} elseif ($wgRequest->getVal('preview-resize')) {
			$url = $wgRequest->getVal('url');
			self::resizeAndDisplayImage($url);
		} elseif ($wgRequest->getVal('intro-image-adder')) {
			$separator = EasyTemplate::html('eiu_separator');
			$articleTitle = $wgRequest->getVal('article-title');
			$searchterms = $wgRequest->getVal('searchterms');

			$t = Title::newFromText($articleTitle);
			$who = new WikiHow();
			$who->loadFromArticle(new Article($t));
			$intro = $who->getSection("summary");
			$intro = $who->removeWikitext($intro);

			$articleTitleLink = $t->getLocalURL();
			$vars = array('title' => $articleTitle, 'titlelink' => $articleTitleLink, 'searchterms' => $searchterms, 'intro' => $intro);
			$html = EasyTemplate::html('iia_eiu_header', $vars);
			$wgOut->addHTML($html);
			$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			$wgOut->addHTML(self::iiagetFindBox($vars));
			$wgOut->addHTML($html);
			$html = EasyTemplate::html('eiu_footer');
			$wgOut->addHTML($html);
		} elseif ($wgRequest->getVal('intro-image-adder2'))  {
			//$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			$this->iiaUploadImage( $wgRequest->getVal('src') );
		} else { // initial menu
			$separator = EasyTemplate::html('eiu_separator');
			$articleTitle = $wgRequest->getVal('article-title');
			$vars = array('title' => $articleTitle);
			$html = EasyTemplate::html('eiu_header', $vars);
			$wgOut->addHTML($html);
			$wgOut->addHTML(EasyTemplate::html('eiu_error_box'));
			//$wgOut->addHTML($separator);
			// assert wgRequest->wasPosted() == false;
			$wgOut->addHTML(self::getCurrentStepBox());
			$wgOut->addHTML(self::getUploadBox());
			//$wgOut->addHTML($separator);
			$wgOut->addHTML(self::getFindBox($articleTitle));
			//$wgOut->addHTML($separator);
			$html = EasyTemplate::html('eiu_find_box_end');
			$wgOut->addHTML($html);
			$html = EasyTemplate::html('eiu_footer');
			$wgOut->addHTML($html);
		}
	}
}

/*
 * no longer used
class Recentuploads extends UnlistedSpecialPage {

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Recentuploads');
	}

	public function execute($par) {
		global $wgRequest, $wgOut;
		Easyimageupload::setTemplatePath();
		wfLoadExtensionMessages('Easyimageupload');
		$page = $wgRequest->getVal('page', 1);
		$html = Easyimageupload::getRecentlyUploadedImages($page);
		$wgOut->disable(true);
		print $html;
		return;
	}
}
 */

/**
 * Used to find images on flickr or in the current wiki
 */
class Findimages extends UnlistedSpecialPage {

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Findimages');
	}

	public function execute($par) {
		global $wgRequest, $wgOut;
		Easyimageupload::setTemplatePath();
		wfLoadExtensionMessages('Easyimageupload');
		$page = intval($wgRequest->getVal('page', 1));
		$query = $wgRequest->getVal('q');
		if ($wgRequest->getVal('src') == 'flickr') {
			$json = Easyimageupload::findImagesFlickr($query, $page);
		} else {
			//$html = Easyimageupload::findImagesThisWiki($query, $page);
		}
		$wgOut->disable(true);
		print $json;
		return;
	}
}

/**
 * A placeholder (temporary) file object for an image upload whose real
 * database name has not yet been chosen.
 */
class TempLocalFile extends LocalFile {

	public static function createTempFilename() {
		global $wgUser;
		$tempname = 'Temp_file_'.$wgUser->getID().'_'.rand(0, 1000).'.jpg';
		return $tempname;
	}

	public function recordUpload2($oldver, $comment, $pageText, $props = false, $timestamp = false)
	{
		global $wgUser;
		//$dbw = $this->repo->getMasterDB();
		if (!$props) {
			$virtURL = $this->getVirtualUrl();
			$props = $this->repo->getFileProps($virtURL);
		}
		$this->setProps($props);
		$this->purgeThumbnails();
		$this->saveToCache();
		return true;
	}

	public function upgradeRow() {
	}

	public function doDBInserts() {
	}
}


<?php
/**
 * Runs every hour to process any newly uploaded images.  Adds images to the
 * articles identified by the article ID.
 *
 * Note: this script should only be run by wikiphoto-process-images-hourly.sh.
 *   It needs to have the correct setuid user so that /var/www/images_en
 *   files are created with the correct permissions.
 * 
 * Usage: php wikiphotoProcessImages.php
 */

/*
 * data schema:
 *
CREATE TABLE wikiphoto_article_status (
  article_id INT UNSIGNED PRIMARY KEY,
  creator VARCHAR(32) NOT NULL default '',
  processed VARCHAR(14) NOT NULL default '',
  reviewed TINYINT UNSIGNED NOT NULL default 0,
  retry TINYINT UNSIGNED NOT NULL default 0,
  needs_retry TINYINT UNSIGNED NOT NULL default 0,
  error TEXT NOT NULL,
  url VARCHAR(255) NOT NULL default '',
  images INT UNSIGNED NOT NULL default 0,
  steps INT UNSIGNED NOT NULL default 0,
);

CREATE TABLE wikiphoto_image_names (
  filename VARCHAR(255) NOT NULL,
  wikiname VARCHAR(255) NOT NULL
);
 *
 */

require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/common/S3.php");

class WikiPhotoProcess {
	const PHOTO_LICENSE = 'cc-by-sa-nc-2.5-self';
	const PHOTO_USER = 'Wikiphoto';
	const IMAGES_DIR = '/usr/local/pfn/images';
	const AWS_BUCKET = 'wikiphoto';
	const AWS_BACKUP_BUCKET = 'wikiphoto-backup';
	const STAGING_DIR  = '/usr/local/wikihow/wikiphoto';
	const IMAGE_PORTRAIT_WIDTH = '220px';
	const IMAGE_LANDSCAPE_WIDTH = '300px';

	static $debugArticleID = '',
		$stepsMsg,
		$imageExts = array('png', 'jpg'),
		$excludeUsers = array('old', 'backup'),
		$enlargePhotoUsers = array();

	/**
	 * Generate a string of random characters
	 */
	private static function genRandomString($chars = 20) {
		$str = '';
		$set = array(
			'0','1','2','3','4','5','6','7','8','9',
			'a','b','c','d','e','f','g','h','i','j','k','l','m',
			'n','o','p','q','r','s','t','u','v','w','x','y','z',
			'A','B','C','D','E','F','G','H','I','J','K','L','M',
			'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		);
		for ($i = 0; $i < $chars; $i++) {
			$r = mt_rand(0, count($set) - 1);
			$str .= $set[$r];
		}
		return $str;
	}

	/**
	 * Remove the Steps section from an article, leaving a placeholder
	 */
	private static function cutStepsSection($articleText) {
		$out = array();
		$token = self::genRandomString();
		$steps = '';
		$found = false;

		// look for the steps section, cut it
		$newText = preg_replace_callback(
			'@^(\s*==\s*' . self::$stepsMsg . '\s*==\s*)$((.|\n)*)^(\s*==[^=])@mU',
			function ($m) use ($token, &$steps, &$found) {
				$steps = $m[2];
				$newText = $m[1] . $token . $m[4];
				$found = true;
				return $newText;
			},
			$articleText
		);
		if (!$found) {
			$newText = preg_replace_callback(
				'@^(\s*==\s*' . self::$stepsMsg . '\s*==\s*)$((.|\n)*)(?!^\s*==[^=])@m',
				function ($m) use ($token, &$steps, &$found) {
					$steps = $m[2];
					$newText = $m[1] . $token;
					$found = true;
					return $newText;
				},
				$articleText
			);
		}

		if (!$found) $token = '';
		return array($newText, $steps, $token);
	}

	/**
	 * Removes all of the specified templates from the start of the intro of the
	 * wikitext.
	 *
	 * @param $wikitext a string of wikitext
	 * @param $templates an array of strings identifying the templates, like
	 *   array('pictures', 'illustrations')
	 */
	private static function removeTemplates($wikitext, $templates) {
		global $wgParser;
		$intro = $wgParser->getSection($wikitext, 0);
		$replaced = false;
		foreach ($templates as &$template) {
			$template = strtolower($template);
		}
		$intro = preg_replace_callback(
			'@({{([^}|]+)(\|[^}]*)?}})@',
			function ($m) use ($templates, &$replaced) {
				$name = trim(strtolower($m[2]));
				foreach ($templates as $template) {
					if ($name == $template) {
						$replaced = true;
						return '';
					}
				}
				return $m[1];
			},
			$intro
		);

		if ($replaced) {
			$wikitext = $wgParser->replaceSection($wikitext, 0, $intro);
		}
		return $wikitext;
	}

	/**
	 * Check the database about whether an article needs processing
	 */
	private static function dbImagesNeedProcessing($articleID) {
		$dbr = self::getDB('read');
		$sql = 'SELECT processed, retry FROM wikiphoto_article_status WHERE article_id=' . $dbr->addQuotes($articleID);
		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchRow($res);
		if (!$row || !$row['processed'] || $row['retry']) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set an article as processed in the database
	 */
	private static function dbSetArticleProcessed($articleID, $creator, $error, $url, $numImages, $numSteps) {
		$dbw = self::getDB('write');
		$sql = 'REPLACE INTO wikiphoto_article_status SET article_id=' . $dbw->addQuotes($articleID) . ', processed=' . $dbw->addQuotes(wfTimestampNow(TS_MW)) . ', retry=0, error=' . $dbw->addQuotes($error) . ', url=' . $dbw->addQuotes($url) . ', images=' . $dbw->addQuotes($numImages) . ', creator=' . $dbw->addQuotes($creator) . ', steps=' . $dbw->addQuotes($numSteps) . ', needs_retry=0';
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Place a set of images into an article's wikitext.
	 */
	private static function placeImagesInSteps($articleID, $title, &$images, &$text, &$stepsText, &$numSteps) {
		$errs = '';

		// process the list of images to make sure we can understand all filenames
		foreach ($images as &$img) {
			if (!preg_match('@^((.*)-\s*)?([0-9]+|intro)\.(' . join('|', self::$imageExts) . ')$@i', $img['name'], $m)) {
				$errs .= 'Filename not in format Name-1.jpg: ' . $img['name'] . '. ';
			} else {
				// new: just discard $m[2]
				$img['first'] = $title->getText();
				$img['step'] = strtolower($m[3]);
				$img['ext'] = strtolower($m[4]);

				if ($img['step'] == 'intro') {
					$img['first'] .= ' Intro';
				} else {
					$img['first'] .= ' Step ' . $img['step'];
				}
			}
		}

		// split steps based on ^# then add the '#' character back on
		$steps = preg_split('@^\s*#@m', $stepsText);
		for ($i = 1; $i < count($steps); $i++) {
			$steps[$i] = "#" . $steps[$i];
		}

		// place images in steps
		$stepNum = 1;
		for ($i = 1; $i < count($steps); $i++) {
			if (preg_match('@^(([#*]|\s)+)((.|\n)*)@m', $steps[$i], $m)) {
				$stripped = preg_replace('@\s+@', '', $m[1]);
				$levels = strlen($stripped);
				if ($levels == 1) {
					$stepIdx = false;
					foreach ($images as $j => $image) {
						if ($image['step'] == $stepNum) {
							$stepIdx = $j;
							break;
						}
					}
					if ($stepIdx !== false) {
						$imgToken = 'IMG_' . self::genRandomString() . '_' . $stepNum;
						$steps[$i] = $m[1] . $imgToken . $m[3];
						$images[$stepIdx]['token'] = $imgToken;
					}
					$stepNum++;
				}
			}
		}
		$numSteps = $stepNum - 1;

		// try to place intro image in article, if there is one
		if (!$err) {
			$introIdx = false;
			foreach ($images as $i => $image) {
				if ($image['step'] == 'intro') {
					$introIdx = $i;
				}
			}

			// we have an intro image to place ...
			if ($introIdx !== false) {
				// We have problems with Segmentation faults in this regular
				// expression if this setting is at the default 100k
				$former_lim = ini_set('pcre.recursion_limit', 15000);

				// remove existing image and place new image after templates
				if (preg_match('@^((\s|\n)*({{[^}]*}})?(\s|\n)*)(\[\[Image:[^\]]*\]\])?((.|\n)*)$@m', $text, $m)) {
					$start = $m[1];
					$end = $m[6];
					$token = 'IMG_' . self::genRandomString() . '_intro';
					$newText = $start . $token . $end;
					$images[$introIdx]['token'] = $token;
				} else {
					$err = 'Unable to insert into image into intro for article ID: '. $articleID;
				}

				// reset value
				ini_set('pcre.recursion_limit', $former_lim);
			} else {
				$newText = $text;
			}
		}

		// were we able to place all images in the article?
		$notPlaced = array();
		foreach ($images as $image) {
			if (!isset($image['token'])) {
				$notPlaced[] = $image['name'];
			}
		}
		if ($notPlaced) {
			$err = 'The following images could not be placed in the article wikitext: ' . join(', ', $notPlaced);
		}

		// add all these images to the wikihow mediawiki repos
		if (!$err) {
			foreach ($images as &$img) {
				$success = self::addMediawikiImage($articleID, $img);
				if (!$success) {
					$err = 'Unable to add new image file ' . $img['name'] . ' to wikiHow.';
					break;
				} else {
					$imgTitle = Title::newFromText($img['mediawikiName'], NS_IMAGE);
					if ($imgTitle) {
						$file = wfFindFile($imgTitle);
						if ($file) {
							$img['width'] = $file->getWidth();
							$img['height'] = $file->getHeight();
						}
					}
				}
			}

			if (!$err) {
				$stepsText = join('', $steps);
				if (count($steps) && trim($steps[0]) == '') {
					$stepsText = "\n" . $stepsText;
				}
				$text = $newText;
			}
		}

		return $err;
	}

	/**
	 * Add a new image file into the mediawiki infrastructure so that it can
	 * be accessed as [[Image:filename.jpg]]
	 */
	private static function addMediawikiImage($articleID, &$image) {
		$dbw = self::getDB('write');

		// check if we've already tried to upload this image
		$imgname = $articleID . '/' . $image['name'];
		$sql = 'SELECT wikiname FROM wikiphoto_image_names WHERE filename=' . $dbw->addQuotes($imgname);
		$res = $dbw->query($sql, __METHOD__);
		$row = $res->fetchRow();

		// if we've already uploaded this image, just return that filename
		if ($row) {
			$image['mediawikiName'] = $row['wikiname'];
			return true;
		}

		// find name for image; change filename to Filename 1.jpg if 
		// Filename.jpg already existed
		$regexp = '/[^' . Title::legalChars() . ']+/';
		$first = preg_replace($regexp, '', $image['first']);
		$ext = $image['ext'];
		$newName = $first . '.' . $ext;
		$i = 1;
		do {
			$title = Title::newFromText($newName, NS_IMAGE);
			if ($title && !$title->exists()) break;
			$newName = $first . ' - Version ' . ++$i . '.' . $ext;
		} while ($i <= 1000);

		// insert image into wikihow mediawiki repos
		$comment = '{{' . self::PHOTO_LICENSE . '}}';
		// next 6 lines taken and modified from 
		// extensions/wikihow/eiu/Easyimageupload.body.php
		$title = Title::makeTitleSafe(NS_IMAGE, $newName);
		if (!$title) return false;
		$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
		if (!$file) return false;
		$ret = $file->upload($image['filename'], $comment, $comment);
		if (!$ret->ok) return false;

		// instruct later processing about which mediawiki name was used
		$image['mediawikiName'] = $newName;

		$sql = 'INSERT INTO wikiphoto_image_names SET filename=' . $dbw->addQuotes($imgname) . ', wikiname=' . $dbw->addQuotes($image['mediawikiName']);
		$dbw->query($sql, __METHOD__);

		return true;
	}

	/**
	 * Process all images for an article from the wikiphoto upload dir
	 */
	private static function processImages($articleID, $creator, $imageList) {
		$err = '';
		$numSteps = 0;

		// parse out steps section replacing it with a token, leaving 
		// the above and below wikitext intact
		list($text, $url, $title) = self::getArticleDetails($articleID);
		if (!$text || !$title) $err = 'Could not find article ID ' . $articleID;
if ($articleID == 1251223) $err = 'Reuben forced skipping this article because there was an error processing it';
		if (!$err) {
			list($text, $steps, $stepsToken) = self::cutStepsSection($text);
			if (!$stepsToken) {
				if (preg_match('@^(\s|\n)*#redirect@i', $text)) {
					$err = 'Could not parse Steps section out of article -- article text is #REDIRECT';
				} else {
					$err = 'Could not parse Steps section out of article';
				}
			}
		}

		// try to place images into wikitext, using tokens as placeholders.
		if (!$err) {
			$err = self::placeImagesInSteps($articleID, $title, $imageList, $text, $steps, $numSteps);
		}

		// detect if no photos were to be processed
		if (!$err) {
			if (count($imageList) == 0) {
				$err = 'Found no photos to process';
			}
		}

		// replace the tokens within the image tag
		if (!$err) {
			$text = str_replace($stepsToken, $steps, $text);
			foreach ($imageList as $image) {
				if (!empty($image['width']) && !empty($image['height']) 
					&& $image['width'] > $image['height'])
				{
					$sizeParam = self::IMAGE_LANDSCAPE_WIDTH;
				} else {
					$sizeParam = self::IMAGE_PORTRAIT_WIDTH;
				}
				$imageTag = '[[Image:' . $image['mediawikiName'] . '|right|' . $sizeParam . ']]';
				$text = str_replace($image['token'], $imageTag, $text);
			}
		}

		// remove certain templates from start of wikitext
		if (!$err) {
			$templates = array('illustrations', 'pictures', 'screenshots');
			$text = self::removeTemplates($text, $templates);
		}

		// write wikitext and add/update wikiphoto row
		if (!$err) {
			$err = self::saveArticleText($articleID, $text);
		}

		// try to enlarge the uploaded photos of certain users
		if (!$err) {
			if (!self::$enlargePhotoUsers) {
				$users = ConfigStorage::dbGetConfig('wikiphoto-enlarge-users');
				if ($users) {
					$users = preg_split('@\s+@', $users);
					self::$enlargePhotoUsers = array_filter($users);
				}
			}
			if (in_array($creator, self::$enlargePhotoUsers)) {
				list($err, $numImages) =
					Wikitext::enlargeImages($title, true, AdminEnlargeImages::DEFAULT_CENTER_PIXELS);
			}
		}

		if ($err) {
			self::dbSetArticleProcessed($articleID, $creator, $err, $url, 0, $numSteps);
		} else {
			self::dbSetArticleProcessed($articleID, $creator, '', $url, count($imageList), $numSteps);
		}

		return array($err, $title);
	}

	/**
	 * Process all directories / articles in the wikiphoto upload image dir
	 *
	 * format for a directory path is:
	 *   userid/articleid/photoid.jpg
	 */
	/*private static function processImagesDir() {
		// read all the image/article dirs
		$dh1 = opendir(self::IMAGES_DIR);
		while (false !== ($user = readdir($dh1))) {
			$userDir = self::IMAGES_DIR . '/' . $user;
			if (in_array($user, self::$excludeUsers) // don't process anything in excluded people
				|| !is_dir($userDir) // only process directories
				|| preg_match('@^[0-9]+$@', $user) // don't allow usernames that are all digits
				|| preg_match('@^\.@', $user) // don't allow usernames that start with a '.'
				|| !preg_match('@^[-._0-9a-zA-Z]{1,30}$@', $user)) // specific rules for usernames
			{
				continue;
			}

			$dh2 = opendir($userDir);
			while (false !== ($article = readdir($dh2))) {
				$articleDir = self::IMAGES_DIR . '/' . $user . '/' . $article;
				if (preg_match('@^[0-9]+$@', $article)) {
					$articleID = intval($article);
				} else {
					$articleID = 0;
				}
				// if article needs to be processed, process all images within that dir
				if ($articleID > 0
					&& is_dir($articleDir)
					&& self::dbImagesNeedProcessing($articleID)
					&& (!self::$debugArticleID || self::$debugArticleID == $articleID))
				{
					$subdh = opendir($articleDir);
					$imageList = array();
					while (false !== ($img = readdir($subdh))) {
						if (!is_dir($img)
							&& !preg_match('@^\.@', $img)
							&& preg_match('@\.(' . join('|', self::$imageExts) . ')@i', $img))
						{
							$filename = $articleDir . '/' . $img;
							$imageList[] = array('name' => $img, 'filename' => $filename);
						}
					}
					closedir($subdh);

					if (count($imageList) > 0) {
						list($err, $title) = self::processImages($articleID, $user, $imageList);

						$titleStr = ($title ? ' (' . $title->getText() . ')' : '');
						$errStr = $err ? ' err=' . $err : '';
						$imageCount = count($imageList);
						print date('r') . " processed: $user/$articleID$titleStr images=$imageCount$errStr\n";
					}
				}
			}
			closedir($dh2);
		}
		closedir($dh1);
	}*/

	/**
	 * Grab the status of all articles processed.
	 */
	private static function dbGetArticlesUpdatedAll() {
		$articles = array();
		$dbr = self::getDB('read');
		$res = $dbr->select('wikiphoto_article_status', array('article_id', 'processed', 'error', 'needs_retry', 'retry'), '', __METHOD__);
		while ($row = $res->fetchRow()) {
			// convert MW timestamp to unix timestamp
			$row['processed'] = wfTimestamp(TS_UNIX, $row['processed']);
			$articles[ $row['article_id'] ] = $row;
		}
		return $articles;
	}

	/**
	 * Flag article as needing retry
	 */
	private static function dbFlagNeedsRetry($id) {
		$dbw = self::getDB('write');
		$dbw->update('wikiphoto_article_status', array('needs_retry' => 1), array('article_id' => $id), __METHOD__);
	}

	/**
	 * List articles on S3
	 */
	private static function getS3Articles(&$s3, $bucket) {
		$list = $s3->getBucket($bucket);

		// compile all the articles into a list of files/zips from s3
		$articles = array();
		foreach ($list as $path => $details) {
			// match string: username/(1234.zip or 1234/*.jpg)
			if (!preg_match('@^([a-z][-._0-9a-z]{0,30})/([0-9]+)(\.zip|/.+)$@i', $path, $m))
			{
				continue;
			}

			list(, $user, $id, $ending) = $m;
			$id = intval($id);
			if (!$id) continue;

			if (in_array($user, self::$excludeUsers) // don't process anything in excluded people
				|| preg_match('@^[0-9]+$@', $user)) // don't allow usernames that are all digits
			{
				continue;
			}

			// process the list of images files into a list of articles
			if (!isset($articles[$id])) {
				$articles[$id] = array(
					'user' => $user,
					'time' => $details['time'],
					'files' => array(),
					'zip' => 0,
				);
			}
			if ($articles[$id]['time'] < $details['time']) {
				$articles[$id]['time'] = $details['time'];
			}

			if ('.zip' == $ending) {
				$articles[$id]['zip'] = 1;
			} elseif (preg_match('@^/([^.].*\.(' . join('|', self::$imageExts) . '))$@i', $ending, $m)) {
				$articles[$id]['files'][] = $m[1];
			}

			if ($user != $articles[$id]['user']) {
				$articles[$id]['err'] = "two or more users ($user and {$articles[$id]['user']}) have uploaded the same article ID: $id";
			}
		}

		return $articles;
	}

	/**
 	 * Cleanup and remove all old copies of photos.  If there's a zip file and
	 * a folder, delete the folder.
	 */
	private static function doS3Cleanup() {
		$s3 = new S3(WH_AWS_WIKIPHOTO_ACCESS_KEY, WH_AWS_WIKIPHOTO_SECRET_KEY);
		$src = self::getS3Articles($s3, self::AWS_BUCKET);
		foreach ($src as $id => $details) {
			if ($details['zip'] && $details['files']) {
				$uri = $details['user'] . '/' . $id . '.zip';
				$count = count($details['files']);
				if ($count <= 1) {
					$files = join(',', $details['files']);
					print "not enough files ($count) to delete $uri: $files\n";
				} else {
					print "deleting $uri\n";
					$s3->deleteObject(self::AWS_BUCKET, $uri);
				}
			}
		}
	}

	/**
	 * Copy all our S3
	 */
	private static function doS3Backup() {
		$s3 = new S3(WH_AWS_WIKIPHOTO_ACCESS_KEY, WH_AWS_WIKIPHOTO_SECRET_KEY);
		$s3bkup = new S3(WH_AWS_BACKUP_ACCESS_KEY, WH_AWS_BACKUP_SECRET_KEY);

// for debugging to make it faster to re-run!
//$file = '/tmp/dbg';
//if (!file_exists($file)) {
		$src = self::getS3Articles($s3, self::AWS_BUCKET);
		$dest = self::getS3Articles($s3bkup, self::AWS_BACKUP_BUCKET);
//$out = serialize(array($src, $dest));
//file_put_contents($file, $out);
//} else {
//list($src, $dest) = unserialize(file_get_contents($file));
//}

		foreach ($src as $id => $srcDetails) {
			$destDetails = @$dest[$id];
			$zipFile = $id . '.zip';
			$destZip = $srcDetails['user'] . '/' . $zipFile;

			// if the dest file exists and the source file is older, we don't
			// need to backup again
			if (@$destDetails['time']
				&& $srcDetails['time'] < $destDetails['time']) 
			{
				continue;
			}

			// if we can't read the source for some reason (ie, multiple
			// versions of the same article from different users), we don't
			// try to sync it
			if ($srcDetails['err']) {
				continue;
			}

			// skip empty directories
			if (!$srcDetails['zip'] && !$srcDetails['files']) {
				continue;
			}

			if ($srcDetails['zip']) {
				$prefix = $srcDetails['user'] . '/';
				$files = array($zipFile);
				list($err, $stageDir) = self::pullFiles($id, $s3, $prefix, $files);
			}
			elseif ($srcDetails['files']) { // pull files into staging area
				$prefix = $srcDetails['user'] . '/' . $id . '/';
				list($err, $stageDir) = self::pullFiles($id, $s3, $prefix, $srcDetails['files']);
				if (!$err) {
					echo "zipping $zipFile...";
					$err = self::zip($stageDir, $zipFile);
					if (!$err && filesize($stageDir . '/' . $zipFile) <= 0) {
						$err = "could not create zip $zipFile";
					}
				}
			}

			if (!$err) {
				$err = self::postFile($s3bkup, $stageDir . '/' . $zipFile, $destZip);
				echo "uploaded $stageDir/$zipFile to $destZip\n";
			} else {
				echo "error uploading $destZip: $err\n";
			}

			if ($stageDir) {
				self::safeCleanupDir($stageDir);
			}
		}
	}

	/**
	 * Process images on S3 instead of from the images web server dir
	 */
	private static function processS3Images() {
		$s3 = new S3(WH_AWS_WIKIPHOTO_ACCESS_KEY, WH_AWS_WIKIPHOTO_SECRET_KEY);
		$articles = self::getS3Articles($s3, self::AWS_BUCKET);

		$processed = self::dbGetArticlesUpdatedAll();

		// process all articles
		foreach ($articles as $id => $details) {
			$debug = self::$debugArticleID;
			if ($debug && $debug != $id) continue;
			if (@$details['err']) {
				if (!$processed[$id]) {
					self::dbSetArticleProcessed($id, $details['user'], $details['err'], '', 0, 0);
				}
				continue;
			}

			// if article needs to be processed because new files were
			// uploaded, but article has already been processed, we should
			// just flag as "needs_retry"
			if (!$debug
				&& isset($processed[$id])
				&& !$processed[$id]['retry']
				&& !$processed[$id]['needs_retry']
				&& !$processed[$id]['error']
				&& $processed[$id]['processed'] < $details['time'])
			{
				self::dbFlagNeedsRetry($id);
				continue;
			}

			// if current article is flagged as needing a retry previously
			// by this script (as opposed to being instructed to retry 
			// by a person), ignore the row until a human processes
			if (!$debug
				&& isset($processed[$id])
				&& !$processed[$id]['retry']
				&& $processed[$id]['needs_retry']
				&& !$processed[$id]['error'])
			{
				continue;
			}

			// if the last file was uploaded within the last 10 minutes,
			// we should wait until the next time to process because 
			// another file may still be uploading
			$ten_minutes = 10 * 60;
			if (!$details['zip']
				&& time() + $ten_minutes < $details['time'])
			{
				continue;
			}
	
			// if this article was already processed, and nothing about its
			// images has changes, and it's not set to be retried, don't
			// process it again
			if (!$debug
				&& isset($processed[$id])
				&& !$processed[$id]['retry']
				&& $processed[$id]['processed'] > $details['time'])
			{
				continue;
			}

			// if article is not on Wikiphoto article exclude list
			if (WikiPhoto::checkExcludeList($id)) {
				$err = 'Article was found on Wikiphoto EXCLUDE list';
				self::dbSetArticleProcessed($id, $details['user'], $err, '', 0, 0);
				continue;
			}

			// pull zip file into staging area
			$stageDir = '';
			$imageList = array();
			if ($details['zip']) {
				$prefix = $details['user'] . '/';
				$zipFile = $id . '.zip';
				$files = array($zipFile);
				list($err, $stageDir) = self::pullFiles($id, $s3, $prefix, $files);
				if (!$err) {
					list($err, $files) = self::unzip($stageDir, $zipFile);
				}
				if (!$err) {
					foreach ($files as $file) {
						$imageList[] = array('name' => basename($file), 'filename' => $file);
					}
				}
			}
			elseif ($details['files']) { // pull files into staging area
				$prefix = $details['user'] . '/' . $id . '/';
				list($err, $stageDir) = self::pullFiles($id, $s3, $prefix, $details['files']);
				$imageList = array();
				foreach ($details['files'] as $file) {
					$imageList[] = array('name' => $file, 'filename' => $stageDir . '/' . $file);
				}
			}
			else { // no zip or image files uploaded -- ignore
				continue;
			}

			if (!$err) {
				list($err, $title) = self::processImages($id, $details['user'], $imageList);
			} else {
				self::dbSetArticleProcessed($id, $details['user'], $err, '', 0, 0);
			}

			if ($stageDir) {
				self::safeCleanupDir($stageDir);
			}

			$titleStr = ($title ? ' (' . $title->getText() . ')' : '');
			$errStr = $err ? ' err=' . $err : '';
			$imageCount = count($imageList);
			print date('Y/M/d H:i') . " processed: {$details['user']}/$id$titleStr images=$imageCount$errStr\n";
		}
	}

	/**
	 * Upload a file to S3
	 */
	private static function postFile(&$s3, $file, $uri) {
		$err = '';
		$ret = $s3->putObject(array('file' => $file), self::AWS_BACKUP_BUCKET, $uri);
		if (!$ret) {
			$err = "unable to upload $file to S3";
		}
		return $err;
	}

	/**
	 * Download files from S3
	 */
	private static function pullFiles($id, &$s3, $prefix, &$files) {
		$err = '';
		$dir = self::STAGING_DIR . '/' . $id . '-' . mt_rand();
		$ret = mkdir($dir);
		if (!$ret) {
			$err = 'unable to create dir: ' . $dir;
			return array($err, '');
		}

		foreach ($files as &$file) {
			$aws_file = $prefix . $file;
			$file = preg_replace('@/@', '-', $file);
			$local_file = $dir . '/' . $file;
			$ret = $s3->getObject(self::AWS_BUCKET, $aws_file, $local_file);
			if (!$ret || $ret->error) {
				$err = "problem retrieving file from S3: s3://" . self::AWS_BUCKET . "/$aws_file";
				break;
			}
		}
		return array($err, $dir);
	}

	/**
	 * Unzip a file into a directory.
	 */
	private static function unzip($dir, $zip) {
		$err = '';
		$files = array();
		system("unzip -j -o -qq $dir/$zip -d $dir", $ret);
		if ($ret != 0) {
			$err = "error in unzipping $dir/$zip";
		}
		if (!$err) {
			if (!unlink($dir . '/' . $zip)) {
				$err = "error removing zip file $dir/$zip";
			}
		}
		if (!$err) {
			$upcase = array_map('strtoupper', self::$imageExts);
			$exts = array_merge($upcase, self::$imageExts);
			$ret = glob($dir . '/*.{' . join(',', $exts) . '}', GLOB_BRACE);
			if (false === $ret) {
				$err = 'no files unzipped';
			} else {
				$files = $ret;
			}
		}
		return array($err, $files);
	}

	private static function zip($dir, $zip) {
		$err = '';
		system("(cd $dir; zip -9 -q $zip *)", $ret);
		if ($ret != 0) {
			$err = "problems while executing zip command to create $zip";
		}
		return $err;
	}

	/**
	 * Remove tmp directory.
	 */
	private static function safeCleanupDir($dir) {
		$staging_dir = self::STAGING_DIR;
		if ($dir && $staging_dir && strpos($dir, $staging_dir) === 0) {
			system("rm -rf $dir");
		}
	}

	/**
	 * Load wikitext and get article URL
	 */
	private static function getArticleDetails($id) {
		$dbr = self::getDB('read');
		$rev = Revision::loadFromPageId($dbr, $id);
		if ($rev) {
			$text = $rev->getText();
			$title = $rev->getTitle();
			$url = self::makeWikihowURL($title);
			return array($text, $url, $title);
		} else {
			return array('', '', null);
		}
	}

	private static function makeWikihowURL($title) {
		return 'http://www.wikihow.com/' . $title->getPartialURL();
	}

	/**
	 * Save wikitext for an article
	 */
	private static function saveArticleText($id, $wikitext) {
		$saved = false;
		$title = Title::newFromID($id);
		if ($title) {
			$article = new Article($title);
			$saved = $article->doEdit($wikitext, 'Saving new step-by-step photos');
		}
		if (!$saved) {
			return 'Unable to save wikitext for article ID: ' . $id;
		} else {
			return '';
		}
	}

	/**
	 * Login to MediaWiki as a specific user while running this script
	 */
	private static function loginAsUser($user) {
		global $wgUser;
		// next 2 lines taken from maintenance/deleteDefaultMessages.php
		$wgUser = User::newFromName($user);
		if (!$wgUser->isBot()) {
			$wgUser->addGroup('bot');
		}
	}

	/**
	 * Get database handle for reading or writing
	 */
	private static function getDB($type) {
		static $dbw = null, $dbr = null;
		if ('read' == $type) {
			if (!$dbr) $dbr = wfGetDB(DB_SLAVE);
			return $dbr;
		} elseif ('write' == $type) {
			if (!$dbw) $dbw = wfGetDB(DB_MASTER);
			return $dbw;
		} else {
			throw new Exception('bad db type');
		}
	}

	/**
	 * Get the title of an article (used for debug)
	 */
	private static function getTitleURL($articleID) {
		$title = Title::newFromId($articleID);
		if ($title) {
			return $title->getPartialURL();
		} else {
			return '(unknown)';
		}
	}

	/**
	 * Entry point for main processing loop
	 */
	public static function main() {
		$opts = getopt('bc', array('backup', 'cleanup'));
		$doBackup = isset($opts['b']) || isset($opts['backup']);
		$doCleanup = isset($opts['c']) || isset($opts['cleanup']);

		if ($_ENV['USER'] != 'apache') {
			print "script must be run as part of wikiphoto-process-images-hourly.sh\n";
			exit;
		}

		self::$stepsMsg = wfMsg('steps');

		self::loginAsUser(self::PHOTO_USER);
		if ($doBackup) {
			self::doS3Backup();
		} elseif ($doCleanup) {
			self::doS3Cleanup();
		} else {
			self::processS3Images();
		}
	}

}

WikiPhotoProcess::main();


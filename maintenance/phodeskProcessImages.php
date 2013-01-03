<?php
/**
 * Runs every hour to process any newly uploaded images.  Adds images to the
 * articles identified by the article ID.
 *
 * Note: this script should only be run by process-phodesk-images-hourly.sh.
 *   It needs to have the correct setuid user so that /var/www/images_en
 *   files are created with the correct permissions.
 * 
 * Usage: php processPhodeskImages.php
 */

require_once('commandLine.inc');

define('PHOTO_LICENSE', 'cc-by-sa-nc-2.5-self');
define('PHOTO_USER', 'Wikiphoto');
define('IMAGES_DIR', '/usr/local/pfn/images');
define('DEBUG_ARTICLE_ID', '');

$dbw = wfGetDB(DB_MASTER);
$dbr = wfGetDB(DB_SLAVE);
$stepsMsg = wfMsg('steps');

function genRandomString($chars = 20) {
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

function cutStepsSection($articleText) {
	global $stepsMsg;
	$out = array();
	$token = genRandomString();
	$steps = '';
	$found = false;

	// look for the steps section, cut it
	$newText = preg_replace_callback(
		'@^(\s*==\s*' . $stepsMsg . '\s*==\s*)$((.|\n)*)^(\s*==[^=])@mU',
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
			'@^(\s*==\s*' . $stepsMsg . '\s*==\s*)$((.|\n)*)(?!^\s*==[^=])@m',
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

/*
 * data schema:
 *
CREATE TABLE phodesk_article_status (
  article_id INT UNSIGNED PRIMARY KEY,
  creator VARCHAR(32) DEFAULT '',
  processed VARCHAR(14) DEFAULT '',
  reviewed TINYINT UNSIGNED DEFAULT 0,
  retry TINYINT UNSIGNED DEFAULT 0,
  error TEXT NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '',
  images INT UNSIGNED
);

CREATE TABLE phodesk_image_names (
  filename VARCHAR(255) NOT NULL,
  wikiname VARCHAR(255) NOT NULL
);
 *
 */

function imagesNeedProcessing($articleID) {
	global $dbr;
	$sql = 'SELECT processed, retry FROM phodesk_article_status WHERE article_id=' . $dbr->addQuotes($articleID);
	$res = $dbr->query($sql);
	$row = $dbr->fetchRow($res);
	if (!$row || !$row['processed'] || $row['retry']) {
		return true;
	} else {
		return false;
	}
}

function setArticleProcessed($articleID, $creator, $error, $url, $numImages) {
	global $dbw;
	$sql = 'REPLACE INTO phodesk_article_status SET article_id=' . $dbw->addQuotes($articleID) . ', processed=' . $dbw->addQuotes(wfTimestampNow(TS_MW)) . ', retry=0, error=' . $dbw->addQuotes($error) . ', url=' . $dbw->addQuotes($url) . ', images=' . $dbw->addQuotes($numImages) . ', creator=' . $dbw->addQuotes($creator);
	$dbw->query($sql);
}

function placeImagesInSteps($articleID, &$images, &$text, &$stepsText) {
	$errs = '';

	// process the list of images to make sure we can understand all filenames
	foreach ($images as &$img) {
		if (!preg_match('@^(.*)-\s*([0-9.]+|intro)\.(jpg|png)$@i', $img['name'], $m)) {
			$errs .= 'Filename not in format Name-1.jpg: ' . $img['name'] . '. ';
		} else {
			if (strpos($m[2], '.') !== false) {
				$errs .= 'Alternate method filename formats not accepted yet: ' . $img['name'] . '. ';
			} else {
				$img['first'] = trim($m[1]);
				$img['step'] = strtolower($m[2]);
				$img['ext'] = strtolower($m[3]);

				if ($img['step'] == 'intro') {
					$img['first'] .= ' Intro';
				} else {
					$img['first'] .= ' Step ' . $img['step'];
				}
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
					$imgToken = 'IMG_' . genRandomString() . '_' . $stepNum;
					$steps[$i] = $m[1] . $imgToken . $m[3];
					$images[$stepIdx]['token'] = $imgToken;
				}
				$stepNum++;
			}
		}
	}

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
			// remove existing image and place new image after templates
			if (preg_match('@^((\s|\n)*({{[^}]*}})?(\s|\n)*)(\[\[Image:[^\]]*\]\])?((.|\n)*)$@m', $text, $m)) {
				$start = $m[1];
				$end = $m[6];
				$token = 'IMG_' . genRandomString() . '_intro';
				$newText = $start . $token . $end;
				$images[$introIdx]['token'] = $token;
			} else {
				$err = 'Unable to insert into image into intro for article ID: '. $articleID;
			}
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
			$success = addMediawikiImage($articleID, $img);
			if (!$success) {
				$err = 'Unable to add new image file ' . $img['name'] . ' to wikiHow.';
				break;
			}
		}

		$stepsText = join('', $steps);
		if (count($steps) && trim($steps[0]) == '') {
			$stepsText = "\n" . $stepsText;
		}
		$text = $newText;
	}

	return $err;
}

function addMediawikiImage($articleID, &$image) {
	global $dbw;

	// check if we've already tried to upload this image
	$imgname = $articleID . '/' . $image['name'];
	$sql = 'SELECT wikiname FROM phodesk_image_names WHERE filename=' . $dbw->addQuotes($imgname);
	$res = $dbw->query($sql);
	$row = $res->fetchRow();

	// if we've already uploaded this image, just return that filename
	if ($row) {
		$image['mediawikiName'] = $row['wikiname'];
		return true;
	}

	// find name for image; change filename to Filename 1.jpg if 
	// Filename.jpg already existed
	$first = $image['first'];
	$ext = $image['ext'];
	$newName = $first . '.' . $ext;
	$i = 1;
	do {
		$title = Title::newFromText($newName, NS_IMAGE);
		if (!$title->exists()) break;
		$newName = $first . ' ' . $i++ . '.' . $ext;
	} while ($i < 1000);

	// insert image into wikihow mediawiki repos
	$comment = '{{' . PHOTO_LICENSE . '}}';
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

	$sql = 'INSERT INTO phodesk_image_names SET filename=' . $dbw->addQuotes($imgname) . ', wikiname=' . $dbw->addQuotes($image['mediawikiName']);
	$dbw->query($sql);

	return true;
}

function processImages($articleID, $creator, $imageList) {
	$err = '';

	// parse out steps section replacing it with a token, leaving 
	// the above and below wikitext intact
	list($text, $url) = getArticleDetails($articleID);
	if (!$text) $err = 'Could not find article ID ' . $articleID;
	if (!$err) {
		//if ($articleID != '6515') { // crashes preg_* calls
			list($text, $steps, $stepsToken) = cutStepsSection($text);
		//}
		if (!$stepsToken) $err = 'Could not parse Steps section out of article';
	}

	// check for alternate methods
	if (!$err && preg_match('@^\s*===\s*@m', $steps)) {
		$err = 'Found alternate methods in Steps section of article -- currently unable to handle this';
	}

	// try to place images into wikitext, using tokens as placeholders.
	if (!$err) {
		$err = placeImagesInSteps($articleID, $imageList, $text, $steps);
	}

	// replace the tokens within the image tag
	if (!$err) {
		$text = str_replace($stepsToken, $steps, $text);
		foreach ($imageList as $image) {
			$imageTag = '[[Image:' . $image['mediawikiName'] . '|thumb]]';
			$text = str_replace($image['token'], $imageTag, $text);
		}
	}

	// write wikitext and add/update phodesk row
	if (!$err) {
		$err = saveArticleText($articleID, $text);
	}

	if ($err) {
		setArticleProcessed($articleID, $creator, $err, $url, 0);
	} else {
		setArticleProcessed($articleID, $creator, '', $url, count($imageList));
	}
}

// format for a directory path is:
//   userid/articleid/photoid.jpg
function processImagesDir() {
	// read all the image/article dirs
	$dh1 = opendir(IMAGES_DIR);
	while (false !== ($user = readdir($dh1))) {
		$userDir = IMAGES_DIR . '/' . $user;
		if ($user == 'old' // don't process anything in the "old" directory
			|| !is_dir($userDir) // only process directories
			|| preg_match('@^[0-9]+$@', $user) // don't allow usernames that are all digits
			|| preg_match('@^\.@', $user) // don't allow usernames that start with a '.'
			|| !preg_match('@^[-._0-9a-zA-Z]{1,30}$@', $user)) // specific rules for usernames
		{
			continue;
		}

		$dh2 = opendir($userDir);
		while (false !== ($article = readdir($dh2))) {
			$articleDir = IMAGES_DIR . '/' . $user . '/' . $article;
			if (preg_match('@^[0-9]+$@', $article)) {
				$articleID = intval($article);
			} else {
				$articleID = 0;
			}
			// if article needs to be processed, process all images within that dir
			if ($articleID > 0
				&& is_dir($articleDir)
				&& imagesNeedProcessing($articleID)
				&& (!DEBUG_ARTICLE_ID || DEBUG_ARTICLE_ID == $articleID))
			{
				$subdh = opendir($articleDir);
				$imageList = array();
				while (false !== ($img = readdir($subdh))) {
					if (!is_dir($img) && !preg_match('@^\.@', $img)) {
						$imageList[] = array('name' => $img, 'filename' => $articleDir . '/' . $img);
					}
				}
				closedir($subdh);

				processImages($articleID, $user, $imageList);
			}
		}
		closedir($dh2);
	}
	closedir($dh1);
}

function getArticleDetails($id) {
	global $dbr;
	$rev = Revision::loadFromPageId($dbr, $id);
	if ($rev) {
		$text = $rev->getText();
		$title = $rev->getTitle();
		$url = 'http://www.wikihow.com/' . $title->getPartialURL();
		return array($text, $url);
	} else {
		return array('', '');
	}
}

function saveArticleText($id, $wikitext) {
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

function loginAsUser($user) {
	global $wgUser;
	// next 2 lines taken from maintenance/deleteDefaultMessages.php
	$wgUser = User::newFromName($user);
	$wgUser->addGroup('bot');
}

if ($_ENV['USER'] != 'apache') {
	print "script must be run as part of process-phodesk-images-hourly.sh\n";
	exit;
}

loginAsUser(PHOTO_USER);
processImagesDir();


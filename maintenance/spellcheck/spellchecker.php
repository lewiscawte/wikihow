<?
require_once('../commandLine.inc');
require_once('extensions/wikihow/WikiHow.php');
require_once('extensions/wikihow/spellchecker/Spellchecker.php');

echo "Start: " . $argv[0] . " " . date('G:i:s:u') . "\n";
switch ($argv[0]) {
	case "all":
		checkAllArticles();
		break;
	case "dirty":
		checkDirtyArticles();
		break;
	case "addWords":
		wikiHowDictionary::batchAddWordsToDictionary();
		break;
	case "populateWhitelistTable":
		populateWhitelistTable();
		break;
	case "addWordFile":
		addWordFile($argv[1]);
		break;
}
echo "Finish: " . $argv[0] . " " . date('G:i:s:u') . "\n\n";

/**
 * Checks all articles in the db for spelling mistakes
 * Should be run sparringly as it will take a long time.
 */
function checkAllArticles() {
    echo "Checking all articles for spelling mistakes at " . microtime(true) . "\n";
    $articles = array();

    $dbr = wfGetDB(DB_SLAVE);
    $dbw = wfGetDB(DB_MASTER);
    $res = $dbr->select('page', 'page_id', array('page_namespace' => 0, 'page_is_redirect' => 0 ), __FUNCTION__);
	
	echo "SQL query done at " . microtime(true) . "\n";
	
    while ($row = $dbr->fetchObject($res)) {
        $articles[] = $row->page_id;
    }

    echo count($articles) . " IDs in array at " . microtime(true) . "\n";

    $pspell = wikiHowDictionary::getLibrary();
    $caps = wikiHowDictionary::getCaps();
	
	$i = 0;
    foreach ($articles as $articleId) {
        spellCheckArticle($dbw, $articleId, $pspell, $caps);
		$i++;
		if($i % 1000 == 0) {
			echo $i . " articles processed at " . microtime(true) . "\n";
		}
    }

    echo "Done importing all articles at " . microtime(true) . "\n";
}

/***
 * Checks all articles that have been marked as dirty (have been
 * edited). 
 */
function checkDirtyArticles() {
	echo "Checking dirty articles for spelling mistakes at " . microtime(true) . "\n";
	
	$dbr = wfGetDB(DB_SLAVE);
	$dbw = wfGetDB(DB_MASTER);
	$res = $dbr->select('spellchecker', 'sc_page', array('sc_dirty' => 1, 'sc_exempt' => 0), __FUNCTION__);
	
	$articles = array();
	
	while ($row = $dbr->fetchObject($res)) {
		$articles[] = $row->sc_page;
	}
	
	$pspell = wikiHowDictionary::getLibrary();
	$capsString = wikiHowDictionary::getCaps();
	
	$i = 0;
	foreach ($articles as $articleId) {
		spellCheckArticle($dbw, $articleId, $pspell, $capsString);
		$i++;
		if($i % 1000 == 0) {
			echo $i . " articles processed at " . microtime(true) . "\n";
		}
	}

    echo "Done checking dirty articles at " . microtime(true) . "\n";

}

/**
 *
 * Checks a specific article for spelling mistakes.
 * 
 */
function spellCheckArticle (&$dbw, $articleId, &$pspell, &$capsString) {
	
	//first remove all mistakes from the mapping table
	$dbw->delete('spellchecker_page', array('sp_page' => $articleId), __FUNCTION__);
	
	$title = Title::newFromID($articleId);
	
	if ($title) {
		$revision = Revision::newFromTitle($title);
		if(!$revision)
			continue;

		$text = $revision->getText();
		
		//now need to remove the sections we're not going to check
		$wikiArticle = WikiHow::newFromText($text);
		
		$sourceText = $wikiArticle->getSection(wfMsg('sources'));//WikiHow::textify($wikiArticle->getSection(wfMsg('sources'), array('remove_ext_links'=>1)));
		$newtext = str_replace($sourceText, "", $text);
		$relatedText = $wikiArticle->getSection(wfMsg('related'));//WikiHow::textify($wikiArticle->getSection(wfMsg('sources'), array('remove_ext_links'=>1)));
		$newtext = str_replace($relatedText, "", $newtext);
		
		//remove reference tags
		$newtext = preg_replace('@<ref>[^<].*</ref>@', "", $newtext);
		
		//remove links
		$newtext = preg_replace('@\[\[[^\]].*\]\]@', "", $newtext);
		
		//replace wierd apostrophes
		$newtext = str_replace('’', "'", $newtext);
		
		$newtext = WikiHow::textify($newtext);
		preg_match_all('/\b(\w|\')+\b/u', $newtext, $matches); //u modified allows for international characters
		
		$foundErrors = false;
		
		foreach ($matches[0] as $match) {
			$word_id = wikiHowDictionary::spellCheckWord($dbw, $match, $pspell, $capsString);
			if ($word_id > 0) {
				//insert into the mapping table
				$dbw->insert('spellchecker_page', array('sp_page' => $articleId, 'sp_word' => $word_id), __FUNCTION__, array('IGNORE'));
				$foundErrors = true;
			}
		}
		if ($foundErrors) {
			$sql = "INSERT INTO spellchecker (sc_page, sc_timestamp, sc_dirty, sc_errors, sc_exempt) VALUES (" . 
					$articleId . ", " . wfTimestampNow() . ", 0, 1, 0) ON DUPLICATE KEY UPDATE sc_dirty = '0', sc_errors = '1', sc_timestamp = " . wfTimestampNow();
			$dbw->query($sql, __FUNCTION__);
		}
		else {
			$dbw->update('spellchecker', array('sc_errors' => 0, 'sc_dirty' => 0), array('sc_page' => $articleId), __FUNCTION__);
		}

	}
}

/**
 *
 * Takes all of the words out of the custom dictionary and adds them
 * to the whitelist table.
 * 
 */
function populateWhitelistTable() {
	global $IP;
	
	$filecontents = file_get_contents($IP . wikiHowDictionary::DICTIONARY_LOC);
	$words = explode("\n", $filecontents);
	asort($words);
	
	$dbw = wfGetDB(DB_MASTER);
	
	foreach($words as $word) {
		$word = trim($word);
		if($word != "" && stripos($word, "personal_ws-1.1") === false)
			$dbw->insert(wikiHowDictionary::WHITELIST_TABLE, array(wikiHowDictionary::WORD_FIELD => $word, wikiHowDictionary::ACTIVE_FIELD => 1), __METHOD__, "IGNORE");
	}
}

function addWordFile($fileName) {
	echo "getting file " . $fileName . "\n";
	$fileContents = file_get_contents($fileName);
	$words = explode("\n", $fileContents);
	
	$dbw = wfGetDB(DB_MASTER);
	
	foreach($words as $word) {
		$word = trim($word);
		if($word != "" && stripos($word, "personal_ws-1.1") === false)
			$dbw->insert(wikiHowDictionary::WHITELIST_TABLE, array(wikiHowDictionary::WORD_FIELD => $word, wikiHowDictionary::ACTIVE_FIELD => 0), __METHOD__, "IGNORE");
	}
}


/**

CREATE TABLE IF NOT EXISTS `spellchecker` (
  `sc_page` int(10) unsigned NOT NULL,
  `sc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_errors` tinyint(3) unsigned NOT NULL,
  `sc_dirty` tinyint(4) NOT NULL,
  `sc_firstedit` varchar(14) collate utf8_unicode_ci default NULL,
  `sc_checkout` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_checkout_user` int(5) NOT NULL,
  UNIQUE KEY `sc_page` (`sc_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_caps` (
  `sc_word` varchar(20) collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `sc_word` (`sc_word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `spellchecker_caps` ADD `sc_user` MEDIUMINT( 8 ) NOT NULL;

CREATE TABLE IF NOT EXISTS `spellchecker_page` (
  `sp_id` int(10) unsigned NOT NULL auto_increment,
  `sp_page` int(10) unsigned NOT NULL,
  `sp_word` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sp_id`),
  UNIQUE KEY `sp_id` (`sp_id`),
  UNIQUE KEY `sp_page` (`sp_page`,`sp_word`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_temp` (
  `st_word` varchar(20) collate utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `spellchecker_word` (
  `sw_id` int(10) unsigned NOT NULL auto_increment,
  `sw_word` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `sw_corrections` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`sw_id`),
  UNIQUE KEY `sw_id` (`sw_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wikidb_112`.`spellchecker_whitelist` (
`sw_word` VARCHAR( 20 ) NOT NULL ,
`sw_active` TINYINT NOT NULL ,
`sw_user` MEDIUMINT( 8 ) NOT NULL, 
UNIQUE (
`sw_word`
)
) ENGINE = InnoDB ;
ALTER TABLE `spellchecker_whitelist` CHANGE `sw_word` `sw_word` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL 
ALTER TABLE `spellchecker` ADD `sc_exempt` TINYINT( 3 ) NOT NULL DEFAULT '0'

**/

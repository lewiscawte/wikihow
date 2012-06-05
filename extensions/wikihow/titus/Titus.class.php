<?
/*
* Titus is a meta db of stats pertaining to our articles.  This file includes the classes 
* that store and retreive data from the db
*/
class TitusDB {
	var $dbw;
	var $dbr;
	var $debugOutput;
	var $dataBatch = array();

	function __construct($debugOutput = false) {
		$this->dbw = wfGetDB(DB_MASTER);
		$this->dbr = wfGetDB(DB_SLAVE);
		$this->debugOutput = $debugOutput;
	}

	/*
	* This function calcs Titus stats for pages that have been most recently edited on wikiHow. 
	* See DailyEdits.class.php for more details
	*/
	public function calcLatestEdits(&$statsToCalc, $lookBack = 1) {
		$dbr = $this->dbr;		

		$lowDate = wfTimestamp(TS_MW, strtotime("-$lookBack day", strtotime(date('Ymd', time()))));
		$highDate = wfTimestamp(TS_MW, strtotime(date('Ymd', time())));
		$res = $dbr->select('daily_edits', 'de_page_id', array("de_timestamp >= '$lowDate'", "de_timestamp < '$highDate'"), __METHOD__);
		$pageIds = array();
		while ($row = $dbr->fetchObject($res)) {
			$pageIds[] = $row->de_page_id;
		}
		$pageChunks = array_chunk($pageIds, 1000);
		foreach ($pageChunks as $chunk) {
			$this->calcStatsForPageIds($statsToCalc, $chunk);
		}
	}

	/*
	* Calc Titus stats for an array of $pageIds
	*/
	public function calcStatsForPageIds(&$statsToCalc, &$pageIds) {
		if (sizeof($pageIds) > 1000) {
			throw new Exception("\$pageIds must be an array of 1000 or fewer page ids");
		}

		$dbr = $this->dbr;
		$pageIds = implode(",", $pageIds);


		$res = $dbr->select('page', 
			array('page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'), 
			array('page_namespace' => 0, 'page_is_redirect' => 0, 
				"page_id IN ($pageIds)"), 
			__METHOD__);

		while ($row = $dbr->fetchObject($res)) {
			$fields = $this->calcPageStats($statsToCalc, $row);

			if (!empty($fields)) {
				//$this->storeRecord($fields);
				$this->batchStoreRecord($fields);
			}
		}
		// flush out current batch
		$this->flushDataBatch();
	}

	/*
	* Calc Titus stats for all pages in the page table that are NS_MAIN and non-redirect.
	* WARNING:  Use this with caution as calculating all Titus stats takes many hours
	*/
	public function calcStatsForAllPages(&$statsToCalc, $limit = array()) {
		$dbr = $this->dbr;		

		$res = $dbr->select('page', 
			array('page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'), 
			array('page_namespace' => 0, 'page_is_redirect' => 0), 
			__METHOD__, 
			$limit);

		while ($row = $dbr->fetchObject($res)) {
			$fields = $this->calcPageStats($statsToCalc, $row);

			if (!empty($fields)) {
				//$this->storeRecord($fields);
				$this->batchStoreRecord($fields);
			}
		}

		// flush out current batch
		$this->flushDataBatch();
	}

	/*
	* Calc stats for a given article.  An article is represented by a subset of its page data from the page table,
	* but this should probably be abstracted in the future to something like TitusArticle with the appropriate fields
	*/
	public function calcPageStats(&$statsToCalc, &$row) {
		$dbw = $this->dbw;
		$dbr = $this->dbr;

		$t = Title::newFromId($row->page_id); 
		$goodRevision = GoodRevision::newFromTitle($t, $row->page_id);
		$revId = $goodRevision->latestGood();
		$r = $revId > 0 ? Revision::loadFromId($dbr, $revId) : Revision::loadFromPageId($dbr, $row->page_id);

		$fields = array();
		if ($r && $t && $t->exists()) {
			foreach ($statsToCalc as $stat => $isOn) {
				if ($isOn) {
					$stat = "TS" . $stat;
					$statCalculator = new $stat();
					$fields = array_merge($fields, $statCalculator->calc($dbr, $r, $t, $row));
				}
			}
		}
		return $fields;
	}

	/*
	* Stores a Titus record
	*/
	public function storeRecord(&$data) {
		$dbw = $this->dbw;
		$fields = join(",", array_keys($data));
		$values = "'" . join("','", array_values($data)) . "'";
		$set = array();
		foreach ($data as $col => $val) {
			$set[] = "$col = '$val'";
		}
		$set = join(",", $set);

		if ($this->debugOutput) {
			var_dump($data);
		}
		$sql = "INSERT INTO titus ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $set";
		$dbw->query($sql);
	}

	/*
	* Stores records in batches sized as specified by the $batchSize parameter
	* NOTE:  This method buffers the data and only stores data once $batchSize threshold has been 
	* met.  To immediately store the bufffered data call flushDataBatch()
	*/
	public function batchStoreRecord($data, $batchSize = 1000) {
		$this->dataBatch[] = $data;
		if (sizeof($this->dataBatch) == $batchSize) {
			$this->flushDataBatch();
		}
	}

	/*
	* Stores multiple records of data.  IMPORTANT:  All data records must constain identical fields of data to  data to insert
	*/
	public function storeRecords(&$dataBatch) {
		if (!sizeof($dataBatch)) {
			return;
		}

		$dbw = $this->dbw;
		$fields = join(",", array_keys($dataBatch[0]));
		$set = array();
		foreach ($dataBatch[0] as $col => $val) {
			$set[] = "$col = VALUES($col)";
		}
		$set = join(",", $set);

		$values = array();
		foreach ($this->dataBatch as $data) {
			$values[] = "('" . join("','", array_values($data)) . "')";
		}
		$values = implode(",", $values);

		$sql = "INSERT INTO titus ($fields) VALUES $values ON DUPLICATE KEY UPDATE $set";
		if ($this->debugOutput) {
			var_dump($this->dataBatch);
		}
		$dbw->query($sql);
	}

	/*
	* Store records currently queued in $this->dataBatch
	*/
	private function flushDataBatch() {
		// Flush out remaining records to database
		if (sizeof($this->dataBatch)) {
			$this->storeRecords($this->dataBatch);
			$this->dataBatch = array();
		}
	}
}

/*
* Returns configuration for TitusController represened by an associative array.   of stats available in the TitusDB that can be calculated
* The key of each row represents a TitusStat that can be calculated and the value represents whether to calculate (1 for calc, 0 for don't calc)
*/
class TitusConfig {

	public static function getSocialStats() {
	}

	/*
	*  Get config to calc stu stats
	*/
	public static function getStuStats() {
		$stats = array(
			"PageId" => 1,
			"Timestamp" => 1,
			"Stu" => 1,
			"PageViews" => 1,
		);
		return $stats;
	}

	public static function getNightlyStats() {
		return self::getStuStats();
	}

	/*
	* Get config for stats that we want to calculate on a nightly basis
	*/
	public static function getDailyEditStats() {
		$stats = self::getAllStats();
		// Social stats are slow to calc, so remove them from the calcs
		$stats['Social'] = 0;

		// Stu stats don't make sense to calculate on a page edit.  This should be done nightly via
		// across all pages
		$stats['Stu'] = 0;
		$stats['PageViews'] = 0;


		// RobotPolicy is also a bit slow, but we should probably leave it on because it's so important
		// to make sure everything is indexing properly
		//unset($stats['RobotPolicy']);

		return $stats;
	}

	public static function getAllStats() {
		$stats = array (
			"PageId" => 1,
			"Timestamp" => 1,
			"Title" => 1,
			"Views" => 1,
			"NumEdits" => 1,
			"AltMethods" => 1,
			"ByteSize" => 1,
			"Accuracy" => 1,
			"Stu" => 1,
			"PageViews" => 1,
			"Intl" => 1,	
			"Video" => 1,
			"FirstEdit" => 1,
			"LastEdit" => 1,
			"TopLevelCat" => 1,
			"ParentCat" => 1,
			"NumSteps" => 1,
			"NumTips" => 1,
			"NumWarnings" => 1,
			"FellowEdit" => 1, 
			"Photos" => 1,
			"Featured" => 1,
			"RobotPolicy" => 1,
			"RisingStar" => 1,
			"Templates" => 1,
			"RushData" => 1,
			"Social" => 1,
			);

		return $stats;
	}

	public static function getBasicStats() {
		$stats = array (
			"PageId" => 1,
			"Timestamp" => 1,
			"Title" => 1,
			);

		return $stats;
	}
}

/*
* Abstract class representing a stat to be calculated by TitusDB
*/
abstract class TitusStat {
	// Abstract function that returns calculated stats.  IMPORTANT: All status must be returned with a 
	// default value or batch insertion of records will break
	abstract function calc(&$dbr, &$r, &$t, &$pageRow);
}

/*
* Provides stats on whether es, pt or de articles have been created for this article
*/
class TSIntl extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$txt = $r->getText();
		$stats = array("ti_langs" => "");
		$langs = implode("|", explode("\n", trim(wfMsg('titus_langs'))));
		if (preg_match_all("@\[\[($langs):@", $txt, $matches)) {
			$matches = $matches[1];
			$stats["ti_langs"] = implode(",", $matches);
		}

		return $stats;
	}
}

/*
* Provides top level category for Article
*/
class TSTopLevelCat extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		global $wgCategoryNames;
		$topCat = "";
		$catMask = $pageRow->page_catinfo; 
		if ($catMask) {
			foreach ($wgCategoryNames as $bit => $cat) {
				if ($bit & $catMask) {
					$topCat = $dbr->strencode($cat);
					break;
				}
			}
		}
		return array('ti_top_cat' => $topCat);
	}
}

/*
* Provides parent category for article
*/
class TSParentCat extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		global $wgContLang; 
		$text = $r->getText();
		$parentCat = "";
		if(preg_match("/\[\[" . $wgContLang->getNSText(NS_CATEGORY) . ":([^\]]*)\]\]/im", $text, $matches)) {
			$parentCat = $dbr->strencode(trim($matches[1]));
		}
		return array('ti_cat' => $parentCat);
	}
}

/*
* Number of views for an article
*/
class TSViews extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_views" => $pageRow->page_counter);
	}
}

/*
* Title of an article
*/
class TSTitle extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_page_title" => $dbr->strencode($pageRow->page_title));
	}
}

/*
* Page id of an article
*/
class TSPageId extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_page_id" => $dbr->strencode($pageRow->page_id));
	}
}


/*
* Number of bytes in in an article
*/
class TSByteSize extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_bytes" => $pageRow->page_len);
	}
}

/*
* Date of first edit
*/
class TSFirstEdit extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$stats = array("ti_first_edit_timestamp" => "", "ti_first_edit_author" => "");
		$res = $dbr->select('firstedit', array('fe_timestamp', 'fe_user_text'), array('fe_page' => $pageRow->page_id), __METHOD__);
		if ($row = $dbr->fetchObject($res)) {
			$stats['ti_first_edit_timestamp'] = $row->fe_timestamp;
			$stats['ti_first_edit_author'] = $dbr->strencode($row->fe_user_text);
		}
		return $stats;
	}
}

/*
* Total number of edits to an article
*/
class TSNumEdits extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_num_edits" => 
			$dbr->selectField('revision', array('count(*)'), array('rev_page' => $pageRow->page_id)));
	}
}

/*
* Determines whether a 'wikifellow' (as defined by mw message 'wikifellows') user account 
* has edited this article
*/
class TSFellowEdit extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$fellows = explode("\n", trim(wfMsg('wikifellows')));
		$fellows = "'" . implode("','", $fellows) . "'";

		$lastEdit = $dbr->selectField(
			'revision', 
			array('rev_timestamp'), 
			array('rev_page' => $pageRow->page_id, "rev_user_text IN ($fellows)"),
			__METHOD__,
			array('ORDER BY rev_id DESC', "LIMIT" => 1));
		if ($lastEdit === false) {
			$lastEdit = 0;
		}
		return array("ti_last_fellow_edit" => $lastEdit);
	}
}

/*
* Date of last edit to this article
*/
class TSLastEdit extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_last_edit_timestamp" => 
			$dbr->selectField('revision', 
				array('rev_timestamp'), 
				array('rev_page' => $pageRow->page_id), 
				__METHOD__, 
				array('ORDER BY' => 'rev_id DESC', 'LIMIT' => '1'))
		);
	}
}

/*
* Number of alternate methods in the article
*/
class TSAltMethods extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$altMethods = intVal(preg_match_all("@^===@m", $r->getText(), $matches));
		return array("ti_alt_methods" => $altMethods);
	}
}

/*
* Whether the article has a video 
*/
class TSVideo extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$video = strpos($r->getText(), "{{Video") ? 1 : 0;
		return array("ti_video" => $video);
	}
}

/*
* Whether the article has been featured
*/
class TSFeatured extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_featured" => $pageRow->page_is_featured);
	}
}

/*
*  Whether the article has a bad template
*/
class TSTemplates extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$txt = $r->getText();

		$badTemplates = array('Speedy', 'Stub', 'Copyvio','Copyviobot','Copyedit','Cleanup');
		$badTemplates = implode("|", $badTemplates); 
		$hasBadTemp = preg_match("@{{($badTemplates{0,1000})@mi", $txt) == 1 ? 1 : 0;

		$templates = array();
		$articleTemplates = implode("|", explode("\n", trim(wfMsg('titus_templates'))));
		if (preg_match_all("@{{($articleTemplates{0,1000})@mi", $txt, $matches)) {
			$templates = $matches[1];
		}
		
		$templates = sizeof($templates) ? $dbr->strencode(implode(",", $templates)) : '';

		return array("ti_bad_template" => intVal($hasBadTemp), 'ti_templates' => $templates);
	}
}

/*
* Number of steps (including alt methods) in the article
*/
class TSNumSteps extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$text = Wikitext::getStepsSection($r->getText(), true);
		$text = $text[0];
		$num_steps = 0;
		if ($text) {
			$num_steps = preg_match_all('/^#[^*]/im', $text, $matches);
		}
		return array("ti_num_steps" => intVal($num_steps));
	}
}

/*
*  Number of tips in the article
*/
class TSNumTips extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$text = Wikitext::getSection($r->getText(), wfMsg('tips'), true);
		$text = $text[0];
		if ($text) {
			$num_tips = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		return array("ti_num_tips" => intVal($num_tips));
	}
}

/*
* Number of warnings in the article
*/
class TSNumWarnings extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$text = Wikitext::getSection($r->getText(), wfMsg('warnings'), true);
		$text = $text[0];
		$num_warnings = 0;
		if ($text) {
			$num_warnings = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		return array("ti_num_warnings" => intVal($num_warnings));
	}
}

/*
*  Accuracy percentage, number of votes, and last reset date to accuracy
*/
class TSAccuracy extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$stats = array();
		$pageId = $pageRow->page_id; 
		$sql = "
			select count(*) as C from rating where rat_page = $pageId and rat_rating = 1 
			UNION 
			select count(*) as C from rating  where rat_page  = $pageId and (rat_rating = 0 OR rat_rating=1)
			UNION
			select max(rat_deleted_when) as C from rating where rat_page = $pageId";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		$accurate = intVal($row->C);
		$row = $dbr->fetchObject($res);
		$total = intVal($row->C);
		$stats['ti_accuracy_percentage'] = $this->percent($accurate, $total); 
		$stats['ti_accuracy_total'] = $total; 

		$row = $dbr->fetchObject($res);
		$lastReset = $row->C;
		$stats['ti_accuracy_last_reset'] = wfTimestamp(TS_MS, 0);
		if(!is_null($lastReset) && '0000-00-00 00:00:00' != $lastReset) { 
			$stats['ti_accuracy_last_reset'] = wfTimestamp(TS_MW, strtotime($row->C));
		}

		return $stats;
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0);
	}
}

/*
*  Date of last update to Titus record
*/
class TSTimestamp extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_timestamp" => wfTimestamp(TS_MW));
	}
}

/*
* Whether the article is a rising star
*/
class TSRisingStar extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		return array("ti_risingstar" => 
			$dbr->selectField('pagelist', array('count(*)'), array('pl_page' => $pageRow->page_id, 'pl_list' => 'risingstar')));
	}
}

/*
* Number of wikiphotos, community photos and if the article has enlarged (> 499 px photos)
*/
class TSPhotos extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {

		$numPhotos = preg_match_all('/\[\[Image:/im', $r->getText(), $matches);
		$numWikiPhotos = intVal($dbr->selectField('wikiphoto_article_status', array('images'), array('article_id' => $pageRow->page_id)));
		$stats = $this->getIntroPhotoStats($r);
		$stats['ti_num_wikiphotos'] = $numWikiPhotos;
		$stats['ti_enlarged_wikiphoto'] = intVal($this->hasEnlargedWikiPhotos($r));
		$stats['ti_num_community_photos'] = $numPhotos - $numWikiPhotos;
		return $stats;
	}

	private function hasEnlargedWikiPhotos(&$r) {
		$enlargedWikiPhoto = 0;
		$text = Wikitext::getStepsSection($r->getText(), true);
		$text = $text[0];
		if ($text) {
			// Photo is enlarged if it is great than 500px (and less than 9999px)
			$enlargedWikiPhoto = preg_match('/\|[5-9][\d]{2,3}px\]\]/im', $text);
		}
		return $enlargedWikiPhoto;
	}

	private function getIntroPhotoStats(&$r) {
		$text = Wikitext::getIntro($r->getText());
		$stats['ti_intro_photo'] = intVal(preg_match('/\[\[Image:/im', $text));
		// Photo is enlarged if it is great than 500px (and less than 9999px)
		$stats['ti_enlarged_intro_photo'] = intVal(preg_match('/\|[5-9][\d]{2,3}px\]\]/im', $text));
		return $stats;
	}

}

/*
* Stu data (www and mobile) for article
*/
class TSStu extends TitusStat {
    public function calc(&$dbr, &$r, &$t, &$pageRow) {
        $stats = array('ti_stu_10s_percentage_mobile' => 0, 'ti_stu_views_mobile' => 0, 
			'ti_stu_10s_percentage_www' => 0, 'ti_stu_3min_percentage_www' => 0, 'ti_stu_views_www' => 0);
		$domains = array('bt' => 'www', 'mb' => 'mobile');
		foreach ($domains as $domain => $label) {
			$query = $this->makeQuery(&$t, $domain);
			$ret = AdminBounceTests::doBounceQuery($query);
			if (!$ret['err'] && $ret['results']) {
				AdminBounceTests::cleanBounceData($ret['results']);
				$stats = array_merge($stats, $this->extractStats($ret['results'], $label));
			}
		}
        return $stats;
    }

    protected function makeQuery(&$t, $domain = 'bt') {
        return array(
            'select' => '*',
            'from' => $domain,
            'pages' => array($t->getDBkey()),
        );
    }

    private function extractStats(&$data, $label) {
        $headers = array('0-10s', '3+m');
        $stats = array();
        foreach ($data as $page => $datum) {
            AdminBounceTests::computePercentagesForCSV($datum, '');
            if (isset($datum['0-10s'])) {
                $stats['ti_stu_10s_percentage_' . $label] = $datum['0-10s'];
            }

            if ($label != 'mobile' && isset($datum['3+m'])) {
                $stats['ti_stu_3min_percentage_' . $label] = $datum['3+m'];
            }

            if (isset($datum['__'])) {
                $stats['ti_stu_views_' . $label] = $datum['__'];
            }
            break; // should only be one record
        }
        return $stats;
    }
}

/*
* Stu data (pv) for article
*/
class TSPageViews extends TSStu {
    public function calc(&$dbr, &$r, &$t, &$pageRow) {
        $stats = array('ti_daily_views' => 0, 'ti_30day_views' => 0);
		
		$query = $this->makeQuery(&$t, 'pv');
		$ret = AdminBounceTests::doBounceQuery($query);
		if (!$ret['err'] && $ret['results']) {
			AdminBounceTests::cleanBounceData($ret['results']);
			$stats = array_merge($stats, $this->extractStats($ret['results']));
		}
		
		$deleteQuery = $this->makeResetQuery($t, 'pv');
		AdminBounceTests::doBounceQuery($deleteQuery);
		
		$stats['ti_30day_views'] = intVal($dbr->selectField('pageview', array('pv_30day'), array('pv_page' => $pageRow->page_id)));
		
        return $stats;
    }
	
	private function makeResetQuery(&$t, $domain = 'pv') {
		return array(
            'delete' => '*',
            'from' => $domain,
            'pages' => array($t->getDBkey()),
        );
	}

    private function extractStats(&$data) {
        $stats = array();
        foreach ($data as $page => $datum) {
            if (isset($datum['__'])) {
                $stats['ti_daily_views'] = $datum['__'];
            }
            break; // should only be one record
        }
        return $stats;
    }
}

/*
* Meta robot policy for article
*/
class TSRobotPolicy extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$stats = array('ti_robot_policy' => '');
		// Request the html  a few times just in case the request is bad
		$i = 0;
		do {
			$html = $this->curlUrl('http://www.wikihow.com' . $t->getLocalUrl());
			if(preg_match('@<meta name="robots" content="([^"]+)"@', $html, $matches)) {
				$stats['ti_robot_policy'] = $matches[1];
			}
		} while ($html == '' && ++$i < 5);
		return $stats;
	}
	
	function curlUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        $contents = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "curl error {$url}: " . curl_error($ch) . "\n";
        }

        curl_close($ch);
		return $contents;
    }
}

/*
* SEM Rush data for article
*/
class TSRushData extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$aid = $pageRow->page_id;
		$stats = array('ti_rush_topic_rank' => 0, 'ti_rush_cpc' => 0, 'ti_rush_query' => '');

		$sql = "select * from rush_data ra inner join 
			(select rush_page_id, max(rush_volume) as max_vol from rush_data where rush_page_id = $aid group by rush_page_id) rb on  
			ra.rush_page_id = rb.rush_page_id and ra.rush_volume = rb.max_vol and ra.rush_page_id = $aid LIMIT 1";
		$res = $dbr->query($sql);
		if ($row = $dbr->fetchObject($res)) {
			$stats['ti_rush_topic_rank'] = $row->rush_position;
			$stats['ti_rush_cpc'] = $row->rush_cpc;
			$stats['ti_rush_query'] = $row->rush_query;
		}
		return $stats;
	}
}

/*
* Number of likes, plus ones and tweets
*/
class TSSocial extends TitusStat {
	public function calc(&$dbr, &$r, &$t, &$pageRow) {
		$url = 'http://www.wikihow.com' . $t->getLocalUrl();
		$stats = array();
		$stats['ti_tweets'] = $this->getTweets($url);
		$stats['ti_likes'] = $this->getLikes($url);
		$stats['ti_plusones'] = $this->getPlusOnes($url);
		return $stats;
	}
	
	function getTweets($url) {
		$json_string = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);
		$json = json_decode($json_string, true);
	 
		return intval($json['count']);
	}
	 
	function getLikes($url) {
	 
		$json_string = file_get_contents('http://graph.facebook.com/?ids=' . $url);
		$json = json_decode($json_string, true);
	 
		return intval($json[$url]['shares']);
	}
	 
	function getPlusOnes($url) {
	 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 
			'[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . 
			'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		$curl_results = curl_exec ($curl);
		curl_close ($curl);
	 
		$json = json_decode($curl_results, true);
	 
		return intval($json[0]['result']['metadata']['globalCounts']['count']);
	}
}


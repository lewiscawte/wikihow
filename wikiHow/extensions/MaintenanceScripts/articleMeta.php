<?

require_once('commandLine.inc');

$stats = array (
	"Title" => 1,
	"PageId" => 1,
	"Views" => 1,
	"NumEdits" => 1,
	"AltMethods" => 1,
	"ByteSize" => 1,
	"Accuracy" => 1,
	"Stu" => 1,
	"Intl" => 1,	
	"Video" => 1,
	"FirstEdit" => 1,
	"LastEdit" => 1,
	"TopLevelCat" => 1,
	"ParentCat" => 1,
	"NumSteps" => 1,
	"NumTips" => 1,
	"NumWarnings" => 1,
	"Timestamp" => 1,
	"FellowEdit" => 1, 
	"Photos" => 1,
	"Featured" => 1,
	"RisingStar" => 1,
	"BadTemplate" => 1,
	"StepPhotos" => 0,
	);

$ams = new ArticleMetaStats();
$ams->calcStats($stats, array('LIMIT' => 10));
//$ams->calcStats($stats);

class ArticleMetaStats {
	var $dbw;
	var $dbr;

	function __construct() {
		$this->dbw = wfGetDB(DB_MASTER);
		$this->dbr = wfGetDB(DB_SLAVE);
	}

	public function calcStats(&$stats, $limit = array()) {
		$dbr = $this->dbr;		
		$res = $dbr->select('page', 
			array('page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'), 
			array('page_id' => 92036, 'page_namespace' => 0, 'page_is_redirect' => 0), 
			//array('page_namespace' => 0, 'page_is_redirect' => 0), 
			__METHOD__, 
			$limit);

		while ($row = $dbr->fetchObject($res)) {
			$this->calcPageStats($stats, $row);
		}
	}

	public function calcPageStats(&$stats, &$row) {
		$dbw = $this->dbw;
		$fields = array();
		$t = Title::newFromId($row->page_id); 
		$r = Revision::loadFromPageId($dbw, $row->page_id);
		if ($r && $t && $t->exists()) {
			foreach ($stats as $stat => $isOn) {
				if ($isOn) {
					$stat = "AMS" . $stat;
					//$statCalculator = $r && $t ? new $stat() : new AMSError();
					$statCalculator = new $stat();
					$fields = array_merge($fields, $statCalculator->process($dbw, $r, $t, $row));
				}
			}

			if (!empty($fields)) {
				$this->storeRecord($fields);
			}
		}
	}

	public function storeRecord(&$data) {
		$dbw = $this->dbw;
		$fields = join(",", array_keys($data));
		$values = "'" . join("','", array_values($data)) . "'";
		$set = array();
		foreach ($data as $col => $val) {
			$set[] = "$col = '$val'";
		}
		$set = join(",", $set);

		var_dump($data);
		$sql = "INSERT INTO ams_page ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $set";
		$dbw->query($sql);
	}
}


abstract class AMSStat {
	abstract function process(&$dbr, &$r, &$t, &$pageRow);
}

class AMSError extends AMSStat {
	public function process(&$dbr, &$r, &$t, &$pageRow) {
		return array('ams_error' => 1);
	}
}
class AMSIntl extends AMSStat {
	public function process(&$dbr, &$r, &$t, &$pageRow) {
		$txt = $r->getText();
		$fields = array();
		if (false !== stripos($txt, "[[es:")) {
			$fields["ams_lang_es"] = 1;
		}
		if (false !== stripos($txt, "[[de:")) {
			$fields["ams_lang_de"] = 1;
		}
		if (false !== stripos($txt, "[[pt:")) {
			$fields["ams_lang_pt"] = 1;
		}
		return $fields;
	}
}

class AMSTopLevelCat extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		global $wgCategoryNames;
		$fields = array();
		$catMask = $pageRow->page_catinfo; 
		if ($catMask) {
			foreach ($wgCategoryNames as $bit => $cat) {
				if ($bit & $catMask) {
					$fields["ams_top_cat"] = $dbw->strencode($cat);
					break;
				}
			}
		}
		return $fields;
	}
}

/*
class AMSTopLevelCat extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$fields = array();
		$topCats = $t->getTopLevelCategories();
		if (is_array($topCats) && sizeof($topCats) > 0) {
			$topCat = $topCats[0];
			$fields["ams_top_cat"] = $dbw->strencode($topCat->getText());
		}
		return $fields;
	}
}
*/

class AMSParentCat extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		global $wgContLang; 
		$text = $r->getText();
		$fields = array();
		if(preg_match("/\[\[" . $wgContLang->getNSText(NS_CATEGORY) . ":([^\]]*)\]\]/im", $text, $matches)) {
			$fields["ams_parent_cat"] = $dbw->strencode(trim($matches[1]));
		}
		return $fields;
	}
}

/*
class AMSParentCat extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$fields = array();
		$parentCats = array_keys($t->getParentCategories());
		if (is_array($parentCats) && sizeof($parentCats) > 0) {
			$parentCat = str_ireplace("Category:", "", $parentCats[0]);
			$fields["ams_parent_cat"] = $dbw->strencode($parentCat);
		}
		return $fields;
	}
}
*/

class AMSViews extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_views" => $pageRow->page_counter);
	}
}

class AMSTitle extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_page_title" => $dbw->strencode($pageRow->page_title));
	}
}

class AMSPageId extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_page_id" => $dbw->strencode($pageRow->page_id));
	}
}


class AMSByteSize extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_bytes" => $pageRow->page_len);
	}
}

class AMSFirstEdit extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_first_edit" => 
			$dbw->selectField('firstedit', array('fe_timestamp'), array('fe_page' => $pageRow->page_id)));
	}
}

class AMSNumEdits extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_num_edits" => 
			$dbw->selectField('revision', array('count(*)'), array('rev_page' => $pageRow->page_id)));
	}
}

class AMSFellowEdit extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$fellows = explode("\n", trim(wfMsg('wikifellows')));
		$fellows = "'" . implode("','", $fellows) . "'";

		$lastEdit = $dbw->selectField(
			'revision', 
			array('rev_timestamp'), 
			array('rev_page' => $pageRow->page_id, "rev_user_text IN ($fellows)"),
			__METHOD__,
			array('ORDER BY rev_id DESC', "LIMIT" => 1));
		if ($lastEdit === false) {
			$lastEdit = 0;
		}
		return array("ams_last_fellow_edit" => $lastEdit);
	}
}

class AMSLastEdit extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_last_edit" => 
			$dbw->selectField('revision', 
				array('rev_timestamp'), 
				array('rev_page' => $pageRow->page_id), 
				__METHOD__, 
				array('ORDER BY' => 'rev_id DESC', 'LIMIT' => '1'))
		);
	}
}

class AMSAltMethods extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$altMethods = preg_match_all("@^===@m", $r->getText(), $matches);
		if ($altMethods === false) {
			$altMethods = 0;
		}
		return array("ams_alt_methods" => $altMethods);
	}
}

class AMSVideo  extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$video =  strpos($r->getText(), "{{Video") ? 1 : 0;
		return array("ams_video" => $video);
	}
}

class AMSFeatured extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$isFeatured = $pageRow->page_is_featured; 
		return array("ams_featured" => $isFeatured);
	}
}

class AMSBadTemplate extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$count = $dbw->selectField('templatelinks', 
			array('count(*)'), 
			array('tl_from' => $pageRow->page_id, "tl_title IN ('Speedy', 'Stub', 'Copyvio','Copyviobot','Copyedit','Cleanup')"), 
			__METHOD__);
		$badTemp = $count > 0 ? 1 : 0;
		return array("ams_bad_template" => $badTemp);
	}
}

class AMSNumSteps extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$text = Wikitext::getStepsSection($r->getText(), true);
		$text = $text[0];
		$num_steps = 0;
		if ($text) {
			$num_steps = preg_match_all('/^#[^*]/im', $text, $matches);
		}
		return array("ams_num_steps" => $num_steps);
	}
}

class AMSNumTips extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$text = Wikitext::getSection($r->getText(), wfMsg('tips'), true);
		$text = $text[0];
		if ($text) {
			$num_tips = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		return array("ams_num_tips" => $num_tips);
	}
}

class AMSNumWarnings extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$text = Wikitext::getSection($r->getText(), wfMsg('warnings'), true);
		$text = $text[0];
		$num_warnings = 0;
		if ($text) {
			$num_warnings = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		return array("ams_num_warnings" => $num_warnings);
	}
}

class AMSStepPhotos extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$text = Wikitext::getStepsSection($r->getText(), true);
		$text = $text[0];
		if ($text) {
			$num_steps = preg_match_all('/^#[^*]/im', $text, $matches);
			$num_step_photos = preg_match_all('/\[\[Image:/im', $text, $matches);
			$stepPhotos = $num_step_photos > ($num_steps / 2) ? 1 : 0;
		} else {
			$stepPhotos = 0;
		}
		return array("ams_photos" => $stepPhotos);
	}
}

class AMSAccuracy extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$pageId = $pageRow->page_id; 
		$sql = "
			select count(*) as C from rating where rat_page = $pageId and rat_rating = 1 
			UNION 
			select count(*) as C from rating  where rat_page  = $pageId and (rat_rating = 0 OR rat_rating=1)";
		$res = $dbw->query($sql);
		$row = $dbw->fetchObject($res);
		$accurate = $row->C;
		$row = $dbw->fetchObject($res);
		$total = $row->C;
		$accurate = $this->percent($accurate, $total); 
		return array("ams_accuracy_percentage" => $accurate, "ams_accuracy_total" => $total);
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0);
	}
}

class AMSTimestamp extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_timestamp" => wfTimestamp(TS_MW));
	}
}

class AMSRisingStar extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		return array("ams_risingstar" => 
			$dbw->selectField('pagelist', array('count(*)'), array('pl_page' => $pageRow->page_id, 'pl_list' => 'risingstar')));
	}
}

class AMSPhotos extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {

		$numPhotos = preg_match_all('/\[\[Image:/im', $r->getText(), $matches);
		$numWikiPhotos = intVal($dbw->selectField('wikiphoto_article_status', array('images'), array('article_id' => $pageRow->page_id)));
		$stats = $this->getIntroPhotoStats($r);
		$stats['ams_num_wikiphotos'] = $numWikiPhotos;
		$stats['ams_enlarged_wikiphoto'] = intVal($this->hasEnlargedWikiPhotos($r));
		$stats['ams_num_community_photos'] = $numPhotos - $numWikiPhotos;
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
		$stats['ams_intro_photo'] = intVal(preg_match('/\[\[Image:/im', $text));
		// Photo is enlarged if it is great than 500px (and less than 9999px)
		$stats['ams_enlarged_intro_photo'] = intVal(preg_match('/\|[5-9][\d]{2,3}px\]\]/im', $text));
		return $stats;
	}

}

class AMSStu extends AMSStat {
    public function process(&$dbw, &$r, &$t, &$pageRow) {
        $stats = array();
		$domains = array('bt', 'mobile');
		foreach ($domains as $domain) {
			$query = $this->makeQuery(&$t, $domain);
			$ret = AdminBounceTests::doBounceQuery($query);
			if (!$ret['err'] && $ret['results']) {
				AdminBounceTests::cleanBounceData($ret['results']);
				$stats = array_merge($stats, $this->extractStats($ret['results'], $domain));
			}
		}
        return $stats;
    }

    private function makeQuery(&$t, $domain = 'bt') {
        return array(
            'select' => '*',
            'from' => $domain,
            'pages' => array($t->getDBkey()),
        );
    }

    private function extractStats(&$data, $domain = 'bt') {
        $headers = array('0-10s', '3+m');
        $stats = array();
        foreach ($data as $page => $datum) {
            AdminBounceTests::computePercentagesForCSV($datum, '');
			$suffix = $domain == 'bt' ? 'www' : $domain;
            if (isset($datum['0-10s'])) {
                $stats['ams_stu_10s_percentage_' . $suffix] = $datum['0-10s'];
            }

            if ($domain != 'mobile' && isset($datum['3+m'])) {
                $stats['ams_stu_3min_percentage_' . $suffix] = $datum['3+m'];
            }

            if ($domain != 'mobile' && isset($datum['__'])) {
                $stats['ams_stu_views_' . $suffix] = $datum['__'];
            }
            break; // should only be one record
        }
        return $stats;
    }
}


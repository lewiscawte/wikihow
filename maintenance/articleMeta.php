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
	"Stu10sec" => 0,
	"Stu3min" => 0,
	"Intl" => 1,	
	"StepPhotos" => 1,
	"Video" => 1,
	"BadTemplate" => 1,
	"FirstEdit" => 1,
	"TopLevelCat" => 1,
	"Featured" => 1,
	"ParentCat" => 1
	);
$stats = array (
	"Accuracy" => 1,
	);
$ams = new ArticleMetaStats();
$ams->updateStats($stats);

class ArticleMetaStats {

	public function updateStats($stats) {
		$dbw = wfGetDB(DB_MASTER);		
		$res = $dbw->select('page', 
			array('page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'), 
			array('page_namespace' => 0, 'page_is_redirect' => 0), 
			''/*, 
			array('LIMIT' => 10)*/);

		while ($row = $dbw->fetchObject($res)) {
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
					$this->storeRecord($dbw, $row, $fields);
				}
			}
		}
	}

	public function storeRecord(&$dbw, &$row, &$data) {
		$fields = join(",", array_keys($data));
		$values = "'" . join("','", array_values($data)) . "'";
		$pageId = $row->page_id;
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

class AMSAltMethods extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$altMethods = preg_match("@^===@m", $r->getText()) ? 1 : 0;
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

class AMSStepPhotos extends AMSStat {
	public function process(&$dbw, &$r, &$t, &$pageRow) {
		$text = Wikitext::getStepsSection($r->getText());
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

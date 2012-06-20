<?php

$wgExtensionMessagesFiles['Pagestats'] = dirname(__FILE__) . '/Pagestats.i18n.php';

class Pagestats {
	
	public static function get30day($pageId, &$dbr) {
		global $wgMemc;
		
		//$key = "ps-30day-" . $pageId;
		//$val = $wgMemc->get($key);
		
		//if(!$val) {
			$val =  $dbr->selectField('pageview', 'pv_30day', array('pv_page' => $pageId));
			//$wgMemc->set($key, $val);
		//}
		
		return $val;
		
	}
	
	public static function get1day($pageId, &$dbr) {
		$val = $dbr->selectField('pageview', 'pv_1day', array('pv_page' => $pageId));
		
		return $val;
	}
	
	public static function update30day($pageId, $val) {
		global $wgMemc;
		
		$key = "ps-30day-" . $pageId;
		
		$wgMemc->set($key, $val);
	}
	
	public static function getStuData($pageId, &$dbr) {
		global $wgMemc;
		
		//$key = "ps-stu-" . $pageId;
		//$val = $wgMemc->get($key);
		
		//if(!$val) {
			$res = $dbr->select('titus', array('ti_stu_views_www', 'ti_stu_10s_percentage_www', 'ti_stu_3min_percentage_www', 'ti_stu_views_mobile', 'ti_stu_10s_percentage_mobile'), array('ti_page_id' => $pageId), __METHOD__);
			
			$val = $dbr->fetchObject($res);
			
			//$wgMemc->set($key, $val);
		//}
		
		return $val;
	}
	
	public static function getRatingData($pageId, &$dbr) {
		global $wgMemc;
		
		//$key = "ps-rating-" . $pageId;
		//$val = $wgMemc->get($key);
		
		//if(!$val) {
			$val->total = 0;
			$yes = 0;
		
			$res = $dbr->select('rating', '*', array('rat_page' => $pageId, 'rat_isdeleted' => 0), __METHOD__);
			while($row = $dbr->fetchObject($res)) {
				$val->total++;
				if($row->rat_rating == 1)
					$yes++;
			}
			
			if($val->total > 0)
				$val->percentage = round($yes*1000/$val->total)/10;
			else
				$val->percentage = 0;
			
			//$wgMemc->set($key, $val);
			
		//}
			
		return $val;
	}
	
	public static function getPagestatData($pageId) {
		$dbr = wfGetDB(DB_SLAVE);
		
		wfLoadExtensionMessages('Pagestats');
		
		$html = "<h3 style='margin-bottom:5px'>Staff-only data</h3>";
		
		$day30 = self::get30day($pageId, $dbr);
		$day1 = self::get1day($pageId, $dbr);
		$html .= "<p>{$day30} " . wfMsg('ps-pv-30day') . "</p>";
		$html .= "<p>{$day1} " . wfMsg('ps-pv-1day') . "</p>";
		
		$data = self::getStuData($pageId, $dbr);
		$html .= "<hr style='margin:5px 0; '/>";
		$html .= "<p>" . wfMsg('ps-stu') . " {$data->ti_stu_10s_percentage_www}%&nbsp;&nbsp;{$data->ti_stu_3min_percentage_www}%&nbsp;&nbsp;{$data->ti_stu_10s_percentage_mobile}%</p>";
		$html .= "<p>" . wfMsg('ps-stu-views') . " {$data->ti_stu_views_www}&nbsp;&nbsp;{$data->ti_stu_views_mobile}</p>";
		
		$data = self::getRatingData($pageId, $dbr);
		$html .= "<hr style='margin:5px 0;' />";
		$html .= "<p>Accuracy: {$data->percentage}% of {$data->total} votes</p>";
		
		$t = Title::newFromID($pageId);
		if($t) {
			$cl = SpecialPage::getTitleFor( 'Clearratings', $t->getText());
			$html .= "<p><a href='" . $cl->getFullUrl() . "'>Clear ratings</a></p>";
		}
		
		return $html;
	}
	
	private static function addData(&$data) {
		$html = "";
		foreach($data as $key => $value) {
			$html .= "<tr><td style='font-weight:bold; padding-right:5px;'>" . $value . "</td><td>" . wfMsg("ps-" . $key) . "</td></tr>";
		}
		return $html;
	}
}

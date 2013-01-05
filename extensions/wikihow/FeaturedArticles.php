<?php

class FeaturedArticles {
	
	
	function getNumberOfDays ($default, $title_article="RSS-feed") {
		$header = "==Number of Days==";
		$header_len = strlen($header);
		$t = Title::newFromText($title_article, NS_PROJECT);
		$r = Revision::newFromTitle($t);
		$text = $r->getText();
		$x = strpos($text, $header);
		if ($x === false) return $default;
		$y = strpos($text, "==", $x+$header_len);
		if ($y === false) { $y = strlen($text); }
		$days = substr($text, $x+ $header_len, $y - $x - $header_len);
		return trim($days);
	}
	
	function getDatesForFeed ($numdays) {
		global $wgRSSOffsetHours;
		$result = array();
		$tstamp=mktime() - $wgRSSOffsetHours * 3600;
		$last_tz = date('Z', $tstamp);
		for ($i = 0; $i < $numdays; $i++) {
			$xx = getdate($tstamp);
			$d = $xx['mday'];
			$m = $xx['mon'];
			$y = $xx['year'];   
			if ($d < 10)  
				$d = "0".$d;
			if ($m < 10) 
				$m = "0".$m; 
			$result[] = "$y-$m-$d";
			// set the time stamp back a day 86400 seconds in 1 day
			$tstamp -= 86400;   
			$tz = date('Z', $tstamp);
			if ($tz != $last_tz) {
				$tstamp -= ($tz - $last_tz);
				$last_tz = $tz;
			}
		}   
		return $result; 
	}
	
	
	function getFeaturedArticles($numdays, $title_article="RSS-feed") { 
		global $wgStylePath, $wgUser, $wgScriptPath, $wgTitle, $wgRSSOffsetHours;
		$sk = $wgUser->getSkin();
		$feeds = array();
		$t = Title::newFromText($title_article, NS_PROJECT);
		$r = Revision::newFromTitle($t); 
		if (!$r) {
			return $feeds;
		}
		$text = $r->getText(); 
		$dates = FeaturedArticles::getDatesForFeed($numdays);
		$d_count = array();
		$feeds = array();
		foreach ($dates as $d) {
			preg_match_all("@^==[ ]*{$d}[ ]*==\n.*@m", $text, $matches); 
			foreach ($matches[0] as $entry) {
				// now entry is 
				// ==2011-03-18==
				// http://www.wikihow.com/Article How to Alternative Title 
				$lines = split("\n", $entry); 
				$parts = split(" ", $lines[1]); 
				$item = array(); 
				$item[] = $parts[0]; // the url
				$item[] = $d; // the date
				if (sizeof($parts) > 1) {
					array_shift($parts);
					$item[] = implode(" ", $parts); // the alt title
				}
				$feeds[] = $item;
				if (!isset($d_count[$d])) {
					$d_count[$d] = 0;
				}
				$d_count[$d] += 1;
			}
		}
		
		// convert dates to timestamps based
		// on the number of feeds that day
		$d_index = array();
		$new_feeds = array();
		$t_array = array();
		$t_url_map = array();
		foreach ($feeds as $item) {
			$d = $item[1];
			$index = 0;
			$count = $d_count[$d];
			if (isset($d_index[$d]))
				$index = $d_index[$d];
			$hour = floor( $index  * (24 / ($count) ) ) + $wgRSSOffsetHours;
			$d_array = split("-", $d);
			$ts = mktime($hour, 0, 0, $d_array[1], $d_array[2], $d_array[0]);
			$t_array[] = $ts;
			
			// inner array
			$xx = array();
			$xx[0] = $item[0];
			if (isset($item[2]))
				$xx[1] = $item[2];
			
			$t_url_map[$ts] = $xx; // assign the url / override title array
			$item[1] = $ts;	
			$d_index[$d] = $index+1;
			$new_feeds[] = $item;
		}	
		
		// sort by timestamp descending
		sort($t_array);
		$feeds = array();
		for ($i = sizeof($t_array) - 1; $i >= 0; $i--) {
			$item = array();
			$ts = $t_array[$i];
			$item[1] = $ts;
			$xx = $t_url_map[$ts];
			$item[0] = $xx[0];
			if(isset($xx[1])) $item[2] = $xx[1];
			$feeds[] = $item;
		}
		
		return $feeds;
	}
	
}
	
?>

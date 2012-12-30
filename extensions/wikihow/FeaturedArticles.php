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
            $t = Title::newFromText($title_article, NS_PROJECT);
                $a = new Article($t);
                $text = $a->getContent(true, false);
                $dates = FeaturedArticles::getDatesForFeed($numdays);
                $feeds = array();
				$d_count = array();
                foreach ($dates as $d) {
                        $x = 0;
                        while ($x >= 0) {
                                $x = @strpos($text, "==$d==", $x);
                                if ($x === false) break;
                                $y = @strpos($text, "==", $x+15);
                                if ($y === false) { $y = strlen($text); }
                                $url = substr($text, $x+15, $y - $x - 15);
                               	$lines = split("\n", $url);
								
				foreach ($lines as $line) {
					$url = trim($line);
					if ($url == "") continue;
					$item = array();
					//find the override title
					$index = strpos($url, " ");
					$override = null;
					if ($index !== false) {
						$override = substr($url, $index+1);
						$url = substr($url, 0, $index);
					}
					$item[0] = $url; 
					if (isset($d_count[$d])) $d_count[$d] += 1;
					else $d_count [$d] =1;
					$item[1] = $d;
					if ($override != null) $item[2] = $override; // additional title
					$feeds[] = $item;
				}
	
				$x += 15;
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

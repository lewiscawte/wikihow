<?

class Videoadder extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'Videoadder' );
    }

	// returns the HTML for the category drop down
	function getCategoryDropDown() {
		global $wgRequest;
		$cats = Categoryhelper::getTopLevelCategoriesForDropDown();
		$selected = $wgRequest->getVal('cat');
		$html = '<select id="va_category" onchange="chooseCat();"><OPTION value="">All</OPTION>';
		foreach ($cats as $c) {
			$c = trim($c);
			if ($c == "" || $c == "WikiHow" || $c == "Other")
				continue;
			if ($c == $selected)
				$html .= '<OPTION value="' . $c . '" SELECTED>' . $c . '</OPTION>\n';
			else
				$html .= '<OPTION value="' . $c . '">' . $c . '</OPTION>\n';
		}
		$html .= '</select>';
		return $html;
	}

	// to be used when we allow a user to skip a video more than once
	// basically filters out previously skipped videos
	// NOT CURRENTLY USED - FOR FUTURE
	function getSkipVal($title, $src = 'youtube') {
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('videoadder', array('va_vid_id'), array('va_page'=>$title->getArticleID()));
		while ($row = $dbr->fetchObject($res)) {
			$ids[] = "-" . $row->va_vid_id;
		}
		return implode("", $ids);
	}

	// handles the coookie settings for skipping a video
    function skipArticle($id) {
        global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
        // skip the article for now
        $cookiename = "VAskip";
        $cookie = $id;
        if (isset($_COOKIE[$wgCookiePrefix.$cookiename]))
            $cookie .= "," . $_COOKIE[$wgCookiePrefix.$cookiename];
        $exp = time() + 86400; // expire after 1 week
        setcookie( $wgCookiePrefix.$cookiename, $cookie, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
        $_COOKIE[$wgCookiePrefix.$cookiename] = $cookie;
    }

	/**
	 *
	 * Returns the total number of articles waiting to
	 * have images added to the Intro
	 */
	function getArticleCount(&$dbr){
		$ts = wfTimestamp(TS_MW, time() - 10 * 60);
		
		$res = $dbr->select('videoadder', array('count(*) as C'), array ("va_inuse IS NULL or va_inuse < '{$ts}'", "va_skipped_accepted IS NULL", "va_page NOT In (5, 5791)", "va_template_ns IS NULL"), 'VideoAdder::getArticleCount');
		$row = $dbr->fetchObject($res);

		return $row->C;
	}

	/**
	 *
	 * Returns the id/date of the last VAdder.
	 */
	function getLastVA(&$dbr){
		$sql = "";
		$bots = User::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = "va_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if($sql != "")
			$res = $dbr->select('videoadder', array('va_user', 'va_timestamp'), array('va_skipped_accepted' => 0, $sql), 'Videoadder::getLastVA', array("ORDER BY"=>"va_timestamp DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('videoadder', array('va_user', 'va_timestamp'), array('va_skipped_accepted' => 0), 'Videoadder::getLastVA', array("ORDER BY"=>"va_timestamp DESC", "LIMIT"=>1));

		$row = $dbr->fetchObject($res);
		$vauser = array();
		$vauser['id'] = $row->va_user;
		$vauser['date'] = wfTimeAgo($row->va_timestamp);

		return $vauser;
	}

	/**
	 *
	 * Returns the id/date of the highest VAdder
	 */
	function getHighestVA(&$dbr, $period='7 days ago'){
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "";
		$bots = User::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = " AND va_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$sql = "SELECT *, count(va_user) as va_count, MAX(va_timestamp) as va_recent FROM `videoadder` WHERE va_timestamp >= '" . $starttimestamp . "'" . $sql . " AND va_skipped_accepted IN ('0','1') GROUP BY va_user ORDER BY va_count DESC";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		$vauser = array();
		$vauser['id'] = $row->va_user;
		$vauser['date'] = wfTimeAgo($row->va_recent);

		return $vauser;
	}

	// performs all of the logic of getting the next video, returns an array
	// array ( title object of the article to work on, video array the video returned from the api )
	function getNext() {
		global $wgRequest, $wgCookiePrefix, $wgCategoryNames, $wgUser;
		$iv = new ImportvideoYoutube();
		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);
		$cat = $wgRequest->getVal('va_cat') ? Title::makeTitle(NS_CATEGORY, $wgRequest->getVal('va_cat')) : null;

		// get a list
        $cookiename = $wgCookiePrefix."VAskip";
        $skipids = "";
        if (isset($_COOKIE[$cookiename])) {
            $ids = array_unique(split(",", $_COOKIE[$cookiename]));
            $good = array(); //safety first
            foreach ($ids as $id) {
                if (preg_match("@[^0-9]@", $id))
                    continue;
                $good[] = $id;
            }
            $skipids = " AND va_page NOT IN (" . implode(",", $good) . ") ";
        }
		for ($i = 0; $i < 10; $i++) {
			$r = rand(0, 2);
			// if it's been in use for more than x minutes, forget 'em
			$ts = wfTimestamp(TS_MW, time() - 10 * 60);
			$catfilter = $wgUser->getCatFilter();
			$sql = "SELECT va_page, va_id
					FROM videoadder ";
			if ($cat || $catfilter) {
					// TODO: to avoid join should we just put catinfo in the page table? 
					$sql .= " LEFT JOIN page ON va_page = page_id ";
			}
			$sql .= "
					WHERE va_page NOT IN (5, 5791) AND
					va_template_ns is NULL and va_skipped_accepted is NULL
					AND (va_inuse is NULL or va_inuse < '{$ts}')
					{$catfilter}
			";
			if ($cat) {
				$cats = array_flip($wgCategoryNames); 
				$mask = $cats[$cat->getText()];
				$sql .= " AND page_catinfo & {$mask} = {$mask} ";
			}

			$sql .= " $skipids ";
			if ($r < 2) {
				// get the most popular page that has no video
				$sql .= " ORDER BY va_page_counter DESC LIMIT 1";
			} else {
				// get the mostly recently edited page that has no video
				$sql .= " ORDER BY va_page_touched DESC LIMIT 1";
			}
			$res = $dbr->query($sql);
			if ($row = $dbr->fetchObject($res)) {
				$title = Title::newFromID($row->va_page);
				if ($title) {
					$iv->getTopResults($title, 1, wfMsg("howto", $title->getText()));
				}
			}
			// get the next title to deal with
			if (sizeof($iv->mResults) > 0) {
				// mark it as in use, so we don't get multiple people processing the same page
				$dbw->update("videoadder", array("va_inuse"=>wfTimestampNow()), array("va_page"=>$row->va_page));
				return array($title, $iv->mResults[0]);
			}
			// set va_skipped_accepted to 2 because we have no results, so we skip it again
			$dbw->update("videoadder", array("va_skipped_accepted"=>2), array("va_page"=>$row->va_page));
		}
		return null;
	}

	// widget settings for getting the weekly rankings
	// caches the rankings for 1 hour, no sense in thrashing the DB for 1 value here
	function getWeekRankings() {
		global $wgMemc, $wgRequest;
		$rankings = null;
		$key = wfMemcKey("videoadder_rankings");
		if ($wgMemc->get($key) && !$wgRequest->getVal('flushrankings')) {
			$rankings = $wgMemc->get($key);
		}
		if (!$rankings) {
			$dbr = wfGetDB(DB_SLAVE);
			$ts 	= substr(wfTimestamp(TS_MW, time() - 7 * 24 * 3600), 0, 8) . "000000";
			$res = $dbr->query("SELECT va_user, count(*) as C from videoadder where va_timestamp >= '{$ts}' AND (va_skipped_accepted = '0' OR va_skipped_accepted = '1') group by va_user ORDER BY C desc;");
			while ($row = $dbr->fetchObject($res))  {
				$rankings[$row->va_user] = $row->C;
			}
			$wgMemc->set($key, $rankings, 3600);
		}
		return $rankings;
	}

	// gets the ranking  of the user for this week, returns "N/A" if they haven't added videos
	function getRankThisWeek() {
		global $wgUser;
		$rankings = self::getWeekRankings();
		$i = 0;
		if(isset($rankings) && is_array($rankings)){
			foreach ($rankings as $u=>$c) {
				if ($u == $wgUser->getID()) {
					return $i + 1;
				}
				$i++;
			}
		}
		return "N/A";
	}

	// sets all of the side widgets for the page
	function setSideWidgets() {
		$indi = new VideoStandingsIndividual();
		$indi->addStatsWidget();

		$standings = new VideoStandingsGroup();
		$standings->addStandingsWidget();

	}

	// gets the top 5 reviewers for the side widget
	//NOT USED ANYMORE
	function getReviewersTable(){
		$rankings = self::getWeekRankings();
		$table = "<table>";
		$index = 0;
		if(isset($rankings) && is_array($rankings)){
			foreach ($rankings as $u=>$c) {
				$u = User::newFromID($u);
				$u->load();
				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}
				$table .= "<tr><td class='va_image'>{$img}</td><td class='va_reviewer'><a href='{$u->getUserPage()->getFullURL()}' target='new'>{$u->getName()}</a></td><td class='va_stat'>{$c}</td></tr>";
				$index++;
				if ($index == 5) break;
			}
		}
		$table .= "</table>";
		return $table;
	}
	
    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}	

		if (class_exists('WikihowCSSDisplay'))
			WikihowCSSDisplay::setSpecialBackground(true);

		if ($wgRequest->getVal( 'fetchReviewersTable' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->getReviewersTable();
			return;
		}

		wfLoadExtensionMessages('Importvideo');
		wfLoadExtensionMessages("Videoadder");

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		// get just the HTML for the Ajax call for the next video
		// used even on the initial page load
		if ($target == 'getnext') {
			$wgOut->setArticleBodyOnly(true);

			// process any skipped videos
			if ($wgRequest->getVal('va_page_id') && !preg_match("@[^0-9]@",$wgRequest->getVal('va_page_id'))) {
				$dbw = wfGetDB(DB_MASTER);

				$vals = array(
					'va_vid_id'		=>$wgRequest->getVal('va_vid_id'),
					'va_user'		=>$wgUser->getID(),
					'va_user_text'	=> $wgUser->getName(),
					'va_timestamp'	=>wfTimestampNow(),
					'va_inuse'		=> null,
					'va_src'		=> 'youtube',
				);

				$va_skip = $wgRequest->getVal('va_skip');
			 	if ($va_skip < 2)
					$vals['va_skipped_accepted']	= $va_skip;

				$dbw->update('videoadder', $vals,
					array(
						'va_page'		=>$wgRequest->getVal('va_page_id'),
					)
				);

				if ($wgRequest->getVal('va_skip') == 0 ) {
					// import the video
					$tx = Title::newFromID($wgRequest->getVal('va_page_id'));
					$ipv = new ImportvideoYoutube();
					$text = $ipv->loadVideoText($wgRequest->getVal('va_vid_id'));
					$vid = Title::makeTitle(NS_VIDEO, $tx->getText());
					Importvideo::updateVideoArticle($vid, $text, 'va_addingvideo');
					Importvideo::updateMainArticle($tx, 'va_addingvideo');
					wfRunHooks("VAdone", array());
					$wgOut->redirect('');
				} else if ($wgRequest->getVal('va_skip') == 2) {
					// the user has skipped it and not rejected this one, don't show it to them again
					self::skipArticle($wgRequest->getVal('va_page_id'));
					wfRunHooks("VAskipped", array());
				}
			}
			$results = self::getNext();
 			$title = $results[0];
			$vid	= $results[1];
			$id 	= str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $vid['ID']);

			$who = WikiHow::newFromTitle($title);
			$intro = $who->getSection("summary");
			$intro = WikiHow::removeWikitext($intro);

			$wgOut->addHTML("<div id='va_title'><a href='#' onclick='va_skip(); return false;' class='button white_button_100 ' style='float:right;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'>Skip</a><span>Article: </span><a href='{$title->getFullURL()}' target='new'>" .  wfMsg("howto", $title->getText()) . "</a></div>
							<div id='va_more' class='off'></div><a href='#' id='va_introlink'>View article introduction</a> | <a href='{$title->getFullURL()}' target='_blank'>Open article in new window</a>
							<div id='va_articleintro'>{$intro}</div>
					<div id='va_video'>
<center>
<div id='va_video_title'><span>Video</span>: {$vid['TITLE']}</div>
<object width='480' height='385'><param name='movie' value='http://www.youtube.com/v/{$id}&amp;hl=en_US&amp;fs=1'></param><param name='allowFullScreen' value='true'></param><param name='allowscriptaccess' value='always'></param><embed src='http://www.youtube.com/v/{$id}&amp;hl=en_US&amp;fs=1' type='application/x-shockwave-flash' allowscriptaccess='always' allowfullscreen='true' width='480' height='385'></embed></object>
					<input type='hidden' id='va_vid_id' value='{$id}'/>
					<input type='hidden' id='va_page_id' value='{$title->getArticleID()}'/>
					<input type='hidden' id='va_page_title' value='" . htmlspecialchars($title->getText()) . "'/>
					<input type='hidden' id='va_page_url' value='" . htmlspecialchars($title->getFullURL()) . "'/>
					<input type='hidden' id='va_skip' value='0'/>
					<input type='hidden' id='va_src' value='youtube'/>
<div id='va_yes_no'>
	<div id='va_notice'><p>" . wfMsg('va_notice') . "</p></div>
						<div id='va_skip'><a href='#' onclick='va_skip(); return false;'>Skip</a></div>
						<div style='width:240px; margin:0 auto;'>
<a id='va_yes' href='#' class='button white_button_100 disabled' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'>Yes</a>
<a id='va_no' href='#' class='button white_button_100 ' style='float:right;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'>No</a>
						<div class='clearall'></div>
</div></div>
</center>
					</div>
					
			</div>
			");
			return;
			//<div id='va_help'><a href='#'>Help me decide</a>
		}


		// add the layer of the page
		$this->setHeaders();
		$this->setSideWidgets();
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/cookie.js"></script>');
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/videoadder.js"></script>');
		$wgOut->addScript('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/videoadder.css"; /*]]>*/</style>');
		$wgOut->addHTML("<div id='va_question'>" . wfMsg('va_question') . "</div>");
		$wgOut->addHTML("<div id='va_instructions'>" . wfMsgExt('va_instructions', 'parse') . "</div>");

		$dropdown = wfMsg('va_browsemsg')  . " " . self::getCategoryDropDown();

		$wgOut->addHTML("<div id='va_browsecat'>" . $dropdown . "</div>"
			. "<div id='va_guts'>
					<center><img src='/extensions/wikihow/rotate.gif'/></center>
				</div>
				");

		$langKeys = array('va_congrats', 'va_check');
		$js = WikiHow_i18n::genJSMsgs($langKeys);
		$wgOut->addHTML($js);

		return;
	}
}

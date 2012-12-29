<?

class RequestTopic extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'RequestTopic' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;

		wfLoadExtensionMessages('RequestTopic');


		$pass_captcha = true;
		if ($wgRequest->wasPosted()) {
        	$fc = new FancyCaptcha();
        	$pass_captcha   = $fc->passCaptcha();
		}

		if ($wgRequest->wasPosted() && $pass_captcha) {
			$dbr = wfGetDB(DB_SLAVE);
			require_once('EditPageWrapper.php');
			$title = EditPageWrapper::formatTitle($wgRequest->getVal('suggest_topic'));
			$s = Title::newFromText($title);
			if (!$s) {
				$wgOut->addHTML("There was an error creating this title.");
				return;
			}
			// does the request exist as an article?
			if ($s->getArticleiD()) {
				$wgOut->addHTML(wfMsg('suggested_article_exists_title'));
				$wgOut->addHTML(wfMsg('suggested_article_exists_info', $s->getText(), $s->getFullURL()));
				return;
			}
			// does the request exist in the list of suggested titles?
			$email = $wgRequest->getVal('suggest_email');
			if (!$wgRequest->getVal('suggest_email_me_check'))
				$email ='';

			$count = $dbr->selectField('suggested_titles', array('count(*)'), array('st_title' => $s->getDBKey()));
			$dbw = wfGetDB(DB_MASTER);
			if ($count == 0) {
			    $dbw->insert('suggested_titles',
				array('st_title'	=> $s->getDBKey(),
					'st_user'	=> $wgUser->getID(),
					'st_user_text'	=> $wgUser->getName(),
					'st_isrequest'	=> 1,
					'st_category'	=> $wgRequest->getVal('suggest_category'),
					'st_suggested'	=> wfTimestampNow(),
					'st_notify'		=> $email,
					'st_source'		=> 'req',
					'st_key'		=> generateSearchKey($title),
					'st_group'		=> rand(0, 4)
				)
			    );
			} else if ($email != ''){
				//request exists lets add the user's email to the list of notifications
				$existing = $dbr->selectField('suggested_titles', array('st_notify'), array('st_title' => $s->getDBKey()));
				if ($existing != '')
					$email = "$existing, $email";
				$dbw->update('suggested_titles',
					array('st_notify'     => $email),
					array('st_title'    => $s->getDBKey())
					);
			}
			$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
			$wgOut->addHTML(wfMsg("suggest_confirmation", $s->getFullURL(), $s->getText()));
			return;
		}
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
		$wgOut->addHTML(wfMsg('suggest_header'));
		$wgOut->addHTML(wfMsg('suggest_sub_header'));

		$wgOut->addHTML("<form action='/Special:RequestTopic' method='POST' onSubmit='return checkSTForm();' name='suggest_topic_form'>");
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');
		$wgOut->addScript("<script type='text/javascript'/>var gSelectCat = '" . wfMsg('suggest_please_select_cat') . "';
		var gEnterTitle = '" . wfMsg('suggest_please_enter_title') . "';
		var gEnterEmail  = '" . wfMsg('suggest_please_enter_email') . "';
	</script>");

		$fc = new FancyCaptcha();
		$cats = $this->getCategoryOptions();
		$wgOut->addHTML(wfMsg('suggest_input_form', $cats, $fc->getForm(),  $pass_captcha ? "" : wfMsg('suggest_captcha_failed'), $wgUser->getEmail()));
		//$wgOut->addHTML(wfMsg('suggest_notifications_form', $wgUser->getEmail()));
		//$wgOut->addHTML(wfMsg('suggest_submit_buttons'));
		$wgOut->addHTML("</form>");
	}

    function getCategoryOptions($default = "") {
            global $wgUser;

            // only do this for logged in users
            $t = Title::newFromDBKey("WikiHow:" . wfMsg('requestcategories') );
            $r = Revision::newFromTitle($t);
            if (!$r)
                return '';
            $cat_array = split("\n", $r->getText());
            $s = "";
            foreach($cat_array as $line) {
                $line = trim($line);
                if ($line == "" || strpos($line, "[[") === 0) continue;
                $tokens = split(":", $line);
                $val = "";
                $val = trim($tokens[sizeof($tokens) - 1]);
                $s .= "<OPTION VALUE=\"" . $val . "\">" . $line . "</OPTION>\n";
            }
            $s = str_replace("\"$default\"", "\"$default\" SELECTED", $s);

            return $s;
    }

}

class ListRequestedTopics extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'ListRequestedTopics' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;

	require_once('Leaderboard.body.php');

	$this->setActiveWidget();
	$this->setTopAuthorWidget();
	$this->getNewArticlesWidget();

	wfLoadExtensionMessages('RequestTopic');
	list( $limit, $offset ) = wfCheckLimits();
	$dbr = wfGetDB(DB_SLAVE);

	$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
	$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');

	ListRequestedTopics::addTabs('Topic');

	$category = $wgRequest->getVal('category', '');
	$wgOut->addHTML(wfMsg('suggsted_list_topics_title'));
	//$wgOut->addHTML(wfMsg('suggested_show_by_category', RequestTopic::getCategoryOptions($category)));
	//$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));

	if ($wgRequest->getVal('st_search')) {
		$key = generateSearchKey($wgRequest->getVal('st_search'));
		$sql = "select st_title, st_user_text, st_user from suggested_titles where  st_isrequest = 1 and st_used = 0
			and st_patrolled=1 and st_key like " . $dbr->addQuotes("%" . str_replace(" ", "%", $key) . "%") . ";";
	} else {
		$sql = "select st_title, st_user_text, st_user from suggested_titles where  st_isrequest = 1  and st_used= 0"
			. ($category == '' ? '' : " AND st_category = " . $dbr->addQuotes($category))
			. " and st_patrolled=1 ORDER BY st_suggested desc LIMIT $offset, $limit";
	}
	$res = $dbr->query($sql);
	$wgOut->addHTML($this->getSearchBox($key));

	if($key){
	    $wgOut->addHTML($this->getCategoryBox());
	    $wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
	    $wgOut->addHTML("<tr class='st_top_row'><td></td><td class='st_title'>Topics for <strong>\"{$key}\"</strong></td><td>Requested By</td></tr>");
	}
	else if($category != ""){
	    $catString = str_replace(" and ", " &amp; ", $category);

	    $wgOut->addHTML($this->getCategoryBox($catString));
	    $wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
	    $wgOut->addHTML("<tr class='st_top_row'><td class='st_icon'><img src='" . $this->getCategoryImage($category) . "' alt='{$catString}' /></td><td class='st_title'><strong>{$catString}</strong> Topics</td><td>Requested By</td></tr>");
	}
	else{
	    $wgOut->addHTML($this->getCategoryBox());
	    $wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
	    $wgOut->addHTML("<tr class='st_top_row'><td class='st_icon'></td><td class='st_title'>" . wfMsg('suggested_list_all') . "</td><td>Requested By</td></tr>");
	}

	$count = 0;
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::newFromDBKey($row->st_title);
		if (!$t) continue;
		$c = "";
		if ($count % 2 == 1) $c = "class='st_on'";
		if ($row->st_user == 0) {
			$wgOut->addHTML("<tr><td class='st_write'><a href='{$t->getEditURL()}'>Write</td><td class='st_title'>{$t->getText()}</td><td class='st_requestor'>Anonymous</td>
				</tr>");
		} else {
			$u = User::newFromName($row->st_user_text);
			$wgOut->addHTML("<tr><td class='st_write'><a href='{$t->getEditURL()}'>Write</td><td class='st_title'>{$t->getText()}</td><td class='st_requestor'><a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>
				</tr>");
		}
		$count++;
	}
	$wgOut->addHTML("<tr class='footer'><td colspan='3' style='border:none'><img src='/skins/WikiHow/images/sttable_bottom.png' alt='' /></td></tr>");
	$wgOut->addHTML("</table>");
	if ($offset != 0) {
		$wgOut->addHTML("<a style='float: left;' href='/Special:ListRequestedTopics?offset=" . (max($offset - $limit, 0)) . "'>Previous {$limit}</a>");
	}
	if ($count == $limit) {
		$wgOut->addHTML("<a class='pagination' style='float: right;' href='/Special:ListRequestedTopics?offset=" . ($offset + $limit) . "'>Next {$limit}</a>");
	}
	//$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));
    }

    function addTabs($section){
	global $wgOut;

	$tabs_main = ListRequestedTopics::getTabs($section);
	$wgOut->addHTML("  <script type='text/javascript'>
				var menutop_elem = document.createElement('div');
				menutop_elem.setAttribute('id','article_tabs');
				menutop_elem.innerHTML = '" . $tabs_main . "';

				var beforeMe = document.getElementById('article_tabs_line');
				beforeMe.parentNode.insertBefore(menutop_elem, beforeMe);


			</script>\n");
    }

    function getSearchBox($searchTerm = ""){
	$search = '<div class="st_search">
		<form action="/Special:ListRequestedTopics">
		<input type="text" value="' . $searchTerm . '" name="st_search" style="width:217px; float:left; margin-top:4px;" />
		<input type="submit" value="Search" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" class="button white_button_100 submit_button" id="st_search_btn" />
		</form>';
		if($searchTerm != "")
		    $search .= '<img src="/skins/WikiHow/images/st_arrow.png" alt="" class="st_arrow" />';
	$search .= '</div>';
	return $search;
    }

	function getCategoryBox($categoryName = ""){
	    if($categoryName != ""){
		$catBox = '<div class="st_box2">';
		 $catBox .= '<p>' . $categoryName . '<br /><a href="/Special:ListTopicCategories">&larr; Show all categories</a></p>
		    <img src="/skins/WikiHow/images/st_arrow.png" alt="" class="st_arrow" />
		</div>';
	    }
	    else{
		$catBox = '<div class="st_box2 singleline">';
		$catBox .= '<p><a href="/Special:ListTopicCategories">&larr; Show categories</a></p>
		    </div>';
	    }
	    return $catBox;
	}

	function getCategoryImage($category){
	    switch(strtolower($category)){
		case "arts and entertainment":
		    return "/skins/WikiHow/images/category_Icon_arts.png";
		case "cars & other vehicles":
		    return "/skins/WikiHow/images/category_icon_cars.png";
		case "computers and electronics":
		    return "/skins/WikiHow/images/category_icon_computers.png";
		case "education and communications":
		    return "/skins/WikiHow/images/category_icon_education.png";
		case "family life":
		    return "/skins/WikiHow/images/category_icon_family.png";
		case "finance and business":
		    return "/skins/WikiHow/images/category_icon_finance.png";
		case "food and entertaining":
		    return "/skins/WikiHow/images/category_icon_food.png";
		case "health":
		    return "/skins/WikiHow/images/category_icon_health.png";
		case "hobbies and crafts":
		    return "/skins/WikiHow/images/category_icon_hobbies.png";
		case "holidays and traditions":
		    return "/skins/WikiHow/images/category_icon_holidays.png";
		case "home and garden":
		    return "/skins/WikiHow/images/category_icon_home.png";
		case "personal care and style":
		    return "/skins/WikiHow/images/category_icon_personal.png";
		case "pets":
		    return "/skins/WikiHow/images/category_icon_pets.png";
		case "philosophy and religion":
		    return "/skins/WikiHow/images/category_icon_pholosophy.png";
		case "relationships":
		    return "/skins/WikiHow/images/category_icon_relationships.png";
		case "sports and fitness":
		    return "/skins/WikiHow/images/category_icon_sports.png";
		case "travel":
		    return "/skins/WikiHow/images/category_icon_travel.png";
		case "wikihow":
		    return "/skins/WikiHow/images/category_icon_wikihow.png";
		case "work world":
		    return "/skins/WikiHow/images/category_icon_work.png";
		case "youth":
		    return "/skins/WikiHow/images/category_icon_youth.png";
		default:
		    return "";
	    }
	}

	function setActiveWidget() {
	    global $wgUser;
	    $html = "<div id='stactivewidget'>" . ListRequestedTopics::getActiveWidget() . "</div>";
	    $skin = $wgUser->getSkin();
	    $skin->addWidget($html);
	}

	function setTopAuthorWidget(){
	    global $wgUser;
	    $html = "<div id=''>" . ListRequestedTopics::getTopAuthorWidget() . "</div>";
	    $skin = $wgUser->getSkin();
	    $skin->addWidget($html);
	}

	function getTabs($section) {
		global $wgUser, $wgOut;
		$sk = $wgUser->getSkin();

	    $tabs = '';
		$articles = 'wide';
		$topic = 'wide';
		$recommended = 'wide';
	    if ($section == 'Topic') {
			$topic .= ' on';
	    } else if ($section == 'Recommended') {
			$recommended .= ' on';
	    } else if ($section == 'Articles') {
			$articles .= ' on';
	    }
	    $tabs .= '<a href="#" onmousedown="button_click(this);" class="' . $topic . '">Find a Topic</a>';
	    $tabs .= '<a href="#" onmousedown="button_click(this);" class="' . $recommended . '">Recommended</a>';
	    $tabs .= '<a href="#" onmousedown="button_click(this);" class="' . $articles . '">Your Articles</a>';
		$request = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'RequestTopic'), wfMsg('requesttopic'));
		$request = preg_replace('@^<a@', '<a class="notab" style="float:right; font-size:1em; width:auto;"', $request);
		$tabs .= $request;


	    return $tabs;
	}

	function getNewArticlesWidget(){
	    global $wgUser;

	    $skin = $wgUser->getSkin();
	    $html = $skin->getNewArticlesBox();
	    $skin->addWidget($html);
	}

	function getTopAuthorWidget(){
	    global $wgUser;
	    $startdate = strtotime('7 days ago');
	    $starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
	    $data = LeaderBoard::getArticlesWritten($starttimestamp);

	    $html = "<h3>Top Authors - Last 7 Days</h3><table class='stleaders'>";

	    $index = 1;

	    $sk = $wgUser->getSkin();

	    foreach($data as $key => $value) {
			$u = new User();
			$value = number_format($value, 0, "", ',');
			$u->setName($key);
			if (($value > 0) && ($key != '')) {
				$class = "";
				if ($index % 2 == 1)
					$class = 'class="odd"';

				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}

				$html .= "<tr $class>
					<td class='leader_image'>" . $img . "</td>
					<td class='leader_user'>" . $sk->makeLinkObj($u->getUserPage(), $u->getName()) . "</td>
					<td class='leader_count'><a href='/Special:Leaderboard/$target?action=articlelist&period=$period&lb_name=".$u->getName() ."' >$value</a> </td>
				</tr> ";
				$data[$key] = $value * -1;
				$index++;
			}
			if ($index > 20) break;
		}
		$html .= "</table>";

		return $html;
	}

	function getActiveWidget() {
	    global $wgUser;

	    $html = "<h3>" . wfMsg('st_currentstats') . "</h3><table class='st_stats'>";

	    $unw = ListRequestedTopics::getUnwrittenTopics();
	    $today = ListRequestedTopics::getArticlesWritten(false);
	    $topicsToday = ListRequestedTopics::getTopicsSuggested(false);
	    $alltime = ListRequestedTopics::getArticlesWritten(true);
	    $topicsAlltime = ListRequestedTopics::getTopicsSuggested(true);

	    $html .= "<tr class='dashed'><td>" . wfMsg('st_numunwritten') . "</td><td class='stcount'>{$unw}</tr>";
	    $html .= "<tr><td>" . wfMsg('st_articleswrittentoday') . "</td><td class='stcount' id='patrolledcount'>{$today}</td></tr>";
	    $html .= "<tr class='dashed'><td>" . wfMsg('st_articlessuggestedtoday') . "</td><td class='stcount' id='quickedits'>{$topicsToday}</td></tr>";
	    $html .= "<tr><td>" . wfMsg('st_alltimewritten'). "</td><td class='stcount' id='alltime'>{$alltime}</td></tr>";
	    $html .= "<tr class='dashed'><td>" . wfMsg('st_alltimesuggested'). "</td><td class='stcount'>{$topicsAlltime}</td></tr>";
	    $html .= "</table><center>" . wfMsg('rcpatrolstats_activeupdate') . "</center>";
	    return $html;
	}

	function getUnwrittenTopics(){
	    $dbr = wfGetDB(DB_SLAVE);
	    $count = $dbr->selectField('suggested_titles',
		array('count(*)'),
		array('st_used' => 0)
            );
	    return number_format($count, 0, ".", ", ");
	}

	function getArticlesWritten($alltime) {
	    global $wgUser;
	    $dbr = wfGetDB(DB_SLAVE);
	    $options = array('rc_user' => $wgUser->getID(), 'rc_new'=>'1', 'rc_namespace'=>'0');
	    if (!$alltime) {
		// just today
		$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
		$options[] = "rc_timestamp > '{$cutoff}'";
	    }
	    $count = $dbr->selectField('recentchanges',
		array('count(*)'),
		$options
	    );
	    return number_format($count, 0, ".", ", ");
	}

	function getTopicsSuggested($alltime) {
	    global $wgUser;
	    $dbr = wfGetDB(DB_SLAVE);
	    $options = array('st_user' => $wgUser->getID());
	    if (!$alltime) {
		// just today
		$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
		$options[] = "st_suggested > '{$cutoff}'";
	    }
	    $count = $dbr->selectField('suggested_titles',
		array('count(*)'),
		$options
	    );
	    return number_format($count, 0, ".", ", ");
	}
}

class ListTopicCategories extends SpecialPage{

    function __construct() {
        SpecialPage::SpecialPage( 'ListTopicCategories' );
    }

    function execute ($par) {
	global $wgOut;

	wfLoadExtensionMessages('RequestTopic');

	$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
	$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');

	ListRequestedTopics::addTabs('Topic');

	$wgOut->addHTML(wfMsg('suggsted_list_topics_title'));

	$link = '/Special:ListRequestedTopics';
	$wgOut->addHTML(
		ListRequestedTopics::getSearchBox() . ' <a href="' . $link . '" class="categorybutton">All</a> ' . ' <a href="' . $link . '?category=Other" class="categorybutton">Other</a>
		<table class="categorytopics" cellspacing="0" cellpadding="0">
		    <tr>
			<td><a href="' . $link . '?category=Arts and Entertainment" class="categorylink"><img src="/skins/WikiHow/images/category_Icon_arts.png" /><br />Arts &amp; Entertainment</a></td>
			<td><a href="' . $link . '?category=' . urlencode('Cars & Other Vehicles') .'" class="categorylink"><img src="/skins/WikiHow/images/category_icon_cars.png" /><br />Cars &amp; Other Vehicles</a></td>
			<td><a href="' . $link . '?category=Computers and Electronics" class="categorylink"><img src="/skins/WikiHow/images/category_icon_computers.png" /><br />Computers &amp; Electronics</a></td>
			<td><a href="' . $link . '?category=Education and Communications" class="categorylink"><img src="/skins/WikiHow/images/category_icon_education.png" /><br />Education &amp; Communications</a></td>
			<td class="last"><a href="' . $link . '?category=Family Life" class="categorylink"><img src="/skins/WikiHow/images/category_icon_family.png" /><br />Family Life</a></td>
		    </tr>
		    <tr>
			<td><a href="' . $link . '?category=Finance and Business" class="categorylink"><img src="/skins/WikiHow/images/category_icon_finance.png" /><br />Finance, Business &amp; Legal</a></td>
			<td><a href="' . $link . '?category=Food and Entertaining" class="categorylink"><img src="/skins/WikiHow/images/category_icon_food.png" /><br />Food &amp; Entertaining</a></td>
			<td><a href="' . $link . '?category=Health" class="categorylink"><img src="/skins/WikiHow/images/category_icon_health.png" /><br />Health</a></td>
			<td><a href="' . $link . '?category=Hobbies and Crafts" class="categorylink"><img src="/skins/WikiHow/images/category_icon_hobbies.png" /><br />Hobbies<br /> &amp; Crafts</a></td>
			<td class="last"><a href="' . $link . '?category=Home and Garden" class="categorylink"><img src="/skins/WikiHow/images/category_icon_home.png" /><br />Home &amp;<br /> Garden</a></td>
		    </tr>
		    <tr>
			<td><a href="' . $link . '?category=Holidays and Traditions" class="categorylink"><img src="/skins/WikiHow/images/category_icon_holidays.png" /><br />Holidays &amp; Traditions</a></td>
			<td><a href="' . $link . '?category=Personal Care and Style" class="categorylink"><img src="/skins/WikiHow/images/category_icon_personal.png" /><br />Personal<br /> Care &amp; Style</a></td>
			<td><a href="' . $link . '?category=Pets" class="categorylink"><img src="/skins/WikiHow/images/category_icon_pets.png" /><br />Pets &amp;<br /> Animals</a></td>
			<td><a href="' . $link . '?category=Philosophy and Religion" class="categorylink"><img src="/skins/WikiHow/images/category_icon_pholosophy.png" /><br />Philosophy<br /> &amp; Religion</a></td>
			<td class="last"><a href="' . $link . '?category=Relationships" class="categorylink"><img src="/skins/WikiHow/images/category_icon_relationships.png" /><br />Relationships</a></td>
		    </tr>
		    <tr>
			<td><a href="' . $link . '?category=Sports and Fitness" class="categorylink"><img src="/skins/WikiHow/images/category_icon_sports.png" /><br />Sports &amp;<br /> Fitness</a></td>
			<td><a href="' . $link . '?category=Travel" class="categorylink"><img src="/skins/WikiHow/images/category_icon_travel.png" /><br />Travel</a></td>
			<td><a href="' . $link . '?category=wikiHow" class="categorylink"><img src="/skins/WikiHow/images/category_icon_wikihow.png" /><br />wikiHow</a></td>
			<td><a href="' . $link . '?category=Work World" class="categorylink"><img src="/skins/WikiHow/images/category_icon_work.png" /><br />Work World</a></td>
			<td class="last"><a href="' . $link . '?category=Youth" class="categorylink"><img src="/skins/WikiHow/images/category_icon_youth.png" /><br />Youth</a></td>
		    </tr>
		</table>'

	);
    }

}


class ManageSuggestedTopics extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'ManageSuggestedTopics' );
    }

    function execute ($par) {
        global $wgRequest, $wgUser, $wgOut;

        if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
            $wgOut->setArticleRelated( false );
            $wgOut->setRobotpolicy( 'noindex,nofollow' );
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }

		wfLoadExtensionMessages('RequestTopic');
		list( $limit, $offset ) = wfCheckLimits();

		$wgOut->addHTML('<script type="text/javascript" language="javascript" src="/extensions/wikihow/winpop.js"></script>
   	<link rel="stylesheet" href="/extensions/wikihow/winpop.css" type="text/css" />');


		$dbr = wfGetDB(DB_SLAVE);
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/suggestedtopics.css"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="/extensions/wikihow/suggestedtopics.js"></script>');

		if ($wgRequest->wasPosted()) {
			$accept = array();
			$reject = array();
			$updates = array();
			$newnames = array();
			foreach ($wgRequest->getValues() as $key=>$value) {
				$id = str_replace("ar_", "", $key);
				if ($value== 'accept') {
					$accept[] = $id;
				} else if ($value == 'reject'){
					$reject[] = $id;
				} else if (strpos($key, 'st_newname_') !== false) {
					$updates[str_replace('st_newname_', '', $key)] = $value;
					$newnames[str_replace('st_newname_', '', $key)] = $value;
				}
			}
			$dbw = wfGetDB(DB_MASTER);
			if (sizeof($accept) > 0) {
				$dbw->query("update suggested_titles set st_patrolled=1 where st_id in (" . implode(",", $accept) . ")");
			}
			if (sizeof($reject) > 0) {
				$dbw->query("delete from suggested_titles where st_id in (" . implode(",", $reject) . ")");
			}

			foreach ($updates as $u=>$v) {
				$t = Title::newFromText($v);
				if (!$t) continue;

				// renames occassionally cause conflicts with existing requests, that's a bummer
				if (isset($newnames[$u])) {
					$page = $dbr->selectField('page', array('page_id'), array('page_title'=>$t->getDBKey()));
					if ($page) {
						// wait, this article is already written, doh
						$notify = $dbr->selectField('suggested_titles', array('st_notify'), array('st_id'=>$u));
						if ($notify) {
                			$dbw->insert('suggested_notify', array('sn_page' => $page,'sn_notify' => $notify, 'sn_timestamp' => wfTimestampNow(TS_MW)));
						}
						$dbw->delete('suggested_titles', array('st_id' => $u));
					}
					$id = $dbr->selectField('suggested_titles', array('st_id'), array('st_title'=>$t->getDBKey()));
					if ($id) {
						// well, it already exists... like the Highlander, there can be only one
						$notify = $dbr->selectField('suggested_titles', array('st_notify'), array('st_id'=>$u));
						if ($notify != '') {
							// append the notify to the existing
							$dbw->update('suggested_titles', array('st_notify = concat(st_notify, ' . $dbr->addQuotes("\n" . $notify) . ")"), array('st_id' => $id));
						}
						// delete the old one
						$dbw->delete('suggested_titles', array('st_id' => $u));
					}
				}
				$dbw->update('suggested_titles',
					array('st_title' => $t->getDBKey()),
					array('st_id' => $u));
			}
			$wgOut->addHTML(sizeof($accept) . " suggestions accepted, " . sizeof($reject) . " suggestions rejected.");
		}
		$sql = "select st_title, st_user_text, st_category, st_id
				from suggested_titles where  st_isrequest = 1  and st_used= 0
				and st_patrolled=0 ORDER BY st_suggested desc LIMIT $offset, $limit";
		$res = $dbr->query($sql);
		$wgOut->addHTML("<br/><br/>
				<form action='/Special:ManageSuggestedTopics' method='POST' name='suggested_topics_manage'>
				<table width='100%' class='suggested_titles_list'>
				<tr class='st_top_row'>
				<td class='st_title'>Article request</td>
				<td>Category</td>
				<td>Edit Title</td>
				<td>Requestor</td>
				<td>Accept</td>
				<td>Reject</td>
			</tr>
			");
		$count = 0;
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromDBKey($row->st_title);
			if (!$t) continue;
			$c = "";
			if ($count % 2 == 1) $c = "class='st_on'";
			$u = User::newFromName($row->st_user_text);

			$wgOut->addHTML("<tr $c>
					<input type='hidden' name='st_newname_{$row->st_id}' value=''/>
					<td class='st_title_m' id='st_display_id_{$row->st_id}'>{$t->getText()}</td>
					<td>{$row->st_category}</td>
					<td><a href='' onclick='javascript:editSuggestion({$row->st_id}); return false;'>Edit</a></td>
					" .  ($u ? "<td><a href='{$u->getUserPage()->getFullURL()}' target='new'>{$u->getName()}</a></td>"
							: "<td>{$row->st_user_text}</td>" ) .
					"<td class='st_radio'><input type='radio' name='ar_{$row->st_id}' value='accept'></td>
					<td class='st_radio'><input type='radio' name='ar_{$row->st_id}' value='reject'></td>
				</tr>");
			$count++;
		}
		$wgOut->addHTML("</table>
			<br/><br/>
			<table width='100%'><tr><td style='text-align:right;'><input type='submit' value='Submit' class='button white_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'/></td></tr></table>
			</form>
			");
	}
}

class RenameSuggestion extends UnlistedSpecialPage {
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'RenameSuggestion' );
    }

    function execute ($par) {
	global $wgOut;
	wfLoadExtensionMessages('RequestTopic');
	$wgOut->setArticleBodyOnly(true);
	$wgOut->addHTML(wfMsg('suggested_edit_title'));
	return;
    }

}

class YourArticles extends SpecialPage{

    function __construct() {
        SpecialPage::SpecialPage( 'YourArticles' );
    }

    function execute($par){

	require_once('Leaderboard.body.php');

	ListRequestedTopics::setActiveWidget();
	ListRequestedTopics::setTopAuthorWidget();
	ListRequestedTopics::getNewArticlesWidget();

	ListRequestedTopics::addTabs('Articles');

	$this->fetchCreated('snailpet', 5);
    }

    function fetchCreated($pagename, $limit = '') {
     	$dbr = wfGetDB(DB_SLAVE);
	$t = Title::newFromText($pagename);


	// GET FEATURED ARTICLES
	require_once('FeaturedArticles.php');
	$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
	$fares = $dbr->query($fasql);
	while ($row=$dbr->fetchObject($fares)) {
		$fa[ $row->page_title ] = 1;
	}

	// DB CALL
	$order = array();
	$order['ORDER BY'] = 'fe_timestamp DESC';
	if ($limit) {
		$order['LIMIT'] = $limit;
	}
	$res = $dbr->select(
		array('firstedit','page'),
		array ('page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'),
		array ('fe_page=page_id', 'fe_user_text' => $t->getText(), "page_title not like 'Youtube%'"),
		"",
		$order
		);
	$display .= $dbr->lastQuery();
	$display .= "<table class='pbTable' cellspacing='0'>\n";
	$display .= "
<tr class='pbTableHeader'>
<th class='pbTableTitle'>Article Title</th>
<th class='pbTableViews'>Views</th>
<th  class='pbTableRS'>Rising Star</th>
<th class='pbTableFA'>Featured</th>
</tr>\n";
	while ($row=$dbr->fetchObject($res)) {

	    $t = Title::makeTitle($row->page_namespace, $row->page_title);
	    $rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$t->getArticleID(), 'pl_list'=>'risingstar')) > 0;
	    $risingstar = "";
	    if ($rs) {
		    $risingstar = "<img src='/extensions/wikihow/star-green.png' height='20px' width='20px'>";
	    }

	    if ($fa[ $t->getPartialURL() ]) {
		    //$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
		    $featured = "<img src='/extensions/wikihow/star-blue.png' height='20px' width='20px'>";
	    } else {
		    $featured = "";
	    }


	    if ($t->getArticleID() > 0)  {
		    $display .= "  <tr>\n";
		    $display .= "    <td><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
		    $display .= "    <td align='center'>".number_format($row->page_counter, 0, '',',') ."</td>\n";
		    $display .= "    <td align='center'>$risingstar</td>\n";
		    $display .= "    <td align='center'>$featured</td>\n";
		    $display .= "  </tr>\n";
	    }
	}
	$display .= "</table>\n";
	$dbr->freeResult($res);

	echo $display;
	return;
    }
}

<?

class RequestTopic extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'RequestTopic' );
	}

	function execute($par) {
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
			$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
			$wgOut->addHTML(wfMsg("suggest_confirmation", $s->getFullURL(), $s->getText()));
			return;
		}

		$wgOut->setHTMLTitle('Requested Topics - wikiHow');

		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addHTML(wfMsg('suggest_header'));
		$wgOut->addHTML(wfMsg('suggest_sub_header'));

		$wgOut->addHTML("<form action='/Special:RequestTopic' method='POST' onSubmit='return checkSTForm();' name='suggest_topic_form'>");
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.js?rev=') . WH_SITEREV . '"></script>');
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

	function execute($par) {
		global $wgRequest, $wgUser, $wgOut;

		require_once('Leaderboard.body.php');

		$wgOut->setHTMLTitle('List Requested Topics - wikiHow');

		$this->setActiveWidget();
		$this->setTopAuthorWidget();
		$this->getNewArticlesWidget();

		wfLoadExtensionMessages('RequestTopic');
		list( $limit, $offset ) = wfCheckLimits();
		$dbr = wfGetDB(DB_SLAVE);

		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.js?rev=') . WH_SITEREV . '"></script>');

		ListRequestedTopics::addTabs('Topic');

		$category = $wgRequest->getVal('category', '');
		$wgOut->addHTML(wfMsg('suggsted_list_topics_title'));
		//$wgOut->addHTML(wfMsg('suggested_show_by_category', RequestTopic::getCategoryOptions($category)));
		//$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));

		$wgOut->addHTML('<div class="clear"></div>');

		if(!$wgRequest->getVal('st_search') && !$wgRequest->getVal('category')){

			$link = '/Special:ListRequestedTopics';
			$wgOut->addHTML(
				ListRequestedTopics::getSearchBox() . ' <a href="' . $link . '?st_search=all" class="categorybutton">All</a> ' . ' <a href="' . $link . '?category=Other" class="categorybutton">Other</a>
				<table class="categorytopics" cellspacing="0" cellpadding="0">
					<tr>
					<td class="categorylink"><a href="' . $link . '?category=Arts and Entertainment"><img src="' . wfGetPad('/skins/WikiHow/images/category_Icon_arts.png') . '" /><br />Arts &amp; Entertainment</a></td>
					<td class="categorylink"><a href="' . $link . '?category=' . urlencode('Cars & Other Vehicles') .'"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_cars.png') . '" /><br />Cars &amp; Other Vehicles</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Computers and Electronics"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_computers.png') . '" /><br />Computers &amp; Electronics</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Education and Communications"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_education.png') . '" /><br />Education &amp; Communications</a></td>
					<td class="categorylink last"><a href="' . $link . '?category=Family Life"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_family.png') . '" /><br />Family Life</a></td>
					</tr>
					<tr>
					<td class="categorylink"><a href="' . $link . '?category=Finance and Business"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_finance.png') . '" /><br />Finance, Business &amp; Legal</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Food and Entertaining"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_food.png') . '" /><br />Food &amp; Entertaining</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Health"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_health.png') . '" /><br />Health</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Hobbies and Crafts"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_hobbies.png') . '" /><br />Hobbies<br /> &amp; Crafts</a></td>
					<td class="categorylink last"><a href="' . $link . '?category=Home and Garden"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_home.png') . '" /><br />Home &amp;<br /> Garden</a></td>
					</tr>
					<tr>
					<td class="categorylink"><a href="' . $link . '?category=Holidays and Traditions"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_holidays.png') . '" /><br />Holidays &amp; Traditions</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Personal Care and Style"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_personal.png') . '" /><br />Personal<br /> Care &amp; Style</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Pets"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_pets.png') . '" /><br />Pets &amp;<br /> Animals</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Philosophy and Religion"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_pholosophy.png') . '" /><br />Philosophy<br /> &amp; Religion</a></td>
					<td class="categorylink last"><a href="' . $link . '?category=Relationships"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_relationships.png') . '" /><br />Relationships</a></td>
					</tr>
					<tr>
					<td class="categorylink"><a href="' . $link . '?category=Sports and Fitness"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_sports.png') . '" /><br />Sports &amp;<br /> Fitness</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Travel"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_travel.png') . '" /><br />Travel</a></td>
					<td class="categorylink"><a href="' . $link . '?category=wikiHow"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_wikihow.png') . '" /><br />wikiHow</a></td>
					<td class="categorylink"><a href="' . $link . '?category=Work World"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_work.png') . '" /><br />Work World</a></td>
					<td class="categorylink last"><a href="' . $link . '?category=Youth"><img src="' . wfGetPad('/skins/WikiHow/images/category_icon_youth.png') . '" /><br />Youth</a></td>
					</tr>
				</table>'

			);

		}
		else{
			if ($wgRequest->getVal('st_search') && $wgRequest->getVal('st_search') != "all") {
				$key = generateSearchKey($wgRequest->getVal('st_search'));
				$sql = "select st_title, st_user_text, st_user from suggested_titles where st_used = 0
				and st_patrolled=1 and st_key like " . $dbr->addQuotes("%" . str_replace(" ", "%", $key) . "%") . "LIMIT $offset, $limit;";
			} else {
				$sql = "select st_title, st_user_text, st_user from suggested_titles where st_used= 0"
				. ($category == '' ? '' : " AND st_category = " . $dbr->addQuotes($category))
				. " and st_patrolled=1 ORDER BY st_suggested desc LIMIT $offset, $limit";
			}
			$res = $dbr->query($sql);
			$wgOut->addHTML($this->getSearchBox($key));

			if($key){
				$wgOut->addHTML($this->getCategoryBox());
			}
			else if($category != ""){
				$catString = str_replace(" and ", " &amp; ", $category);
				$wgOut->addHTML($this->getCategoryBox($catString));
			}
			else{
				$wgOut->addHTML($this->getCategoryBox());
			}

			if($dbr->numRows($res) > 0){
				if($key){
					$wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
					$wgOut->addHTML("<tr class='st_top_row'><td class='first'></td><td class='st_title second'>Requests for <strong>\"{$key}\"</strong></td><td class='third'>Requested By</td></tr>");
				}
				else if($category != ""){
					$catString = str_replace(" and ", " &amp; ", $category);
					$wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
					$wgOut->addHTML("<tr class='st_top_row'><td class='st_icon first'>" . $this->getCategoryImage($category) . "</td><td class='st_title second'><strong>{$catString}</strong></td><td class='third'>Requested By</td></tr>");
				}
				else{
					$wgOut->addHTML("<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>");
					$wgOut->addHTML("<tr class='st_top_row'><td class='st_icon first'></td><td class='st_title second'>" . wfMsg('suggested_list_all') . "</td><td class='third'>Requested By</td></tr>");
				}

				$count = 0;
				while ($row = $dbr->fetchObject($res)) {
					$t = Title::newFromDBKey($row->st_title);
					if (!$t) continue;
					$c = "";
					if ($count % 2 == 1) $c = "class='st_on'";
					if ($row->st_user == 0) {
						$wgOut->addHTML("<tr><td class='st_write'><a href='/Special:CreatePage/{$t->getPartialURL()}'>Write</td><td class='st_title'>{$t->getText()}</td><td class='st_requestor'>Anonymous</td>
							</tr>");
					} else {
						$u = User::newFromName($row->st_user_text);
						$wgOut->addHTML("<tr><td class='st_write'><a href='/Special:CreatePage/{$t->getPartialURL()}'>Write</td><td class='st_title'>{$t->getText()}</td><td class='st_requestor'><a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>
							</tr>");
					}
					$count++;
				}
				$wgOut->addHTML("<tr class='footer'><td colspan='3' style='border:none'><img src='" . wfGetPad('/skins/WikiHow/images/sttable_bottom.png') . "' alt='' /></td></tr>");
				$wgOut->addHTML("</table>");
				$key = $wgRequest->getVal('st_search');
				if ($offset != 0) {
					$url = $_SERVER['SCRIPT_URI'];
					if($key)
						$url .= "?st_search=" . $key;
					else if($category)
						$url .= "?category=" . $category;

					$wgOut->addHTML("<a style='float: left;' href='" . $url . "&offset=" . (max($offset - $limit, 0)) . "'>Previous {$limit}</a>");
				}
				if ($count == $limit) {
					$url = $_SERVER['SCRIPT_URI'];
					if($key)
						$url .= "?st_search=" . $key;
					else if($category)
						$url .= "?category=" . $category;

					$wgOut->addHTML("<a class='pagination' style='float: right;' href='" . $url . "&offset=" . ($offset + $limit) . "'>Next {$limit}</a>");
				}
				//$wgOut->addHTML(wfMsg('suggested_topic_search_form', "ListRequestedTopics", $wgRequest->getVal('st_search')));
			}
			else{
				if($key != "")
					$wgOut->addHTML(wfMsg('suggest_noresults', $key));
				else
					$wgOut->addHTML(wfMsg('suggest_noresults', $category));
			}
		}
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
				$search .= '<img src="' . wfGetPad('/skins/WikiHow/images/st_arrow.png') . '" alt="" class="st_arrow" />';
		$search .= '</div>';
		return $search;
		}

	function getCategoryBox($categoryName = ""){
		if($categoryName != ""){
		$catBox = '<div class="st_box2">';
		 $catBox .= '<p>You\'re searching in<br />' . $categoryName . '<br /><a href="/Special:ListRequestedTopics">&larr; Show all categories</a></p>
			<img src="' . wfGetPad('/skins/WikiHow/images/st_arrow.png') . '" alt="" class="st_arrow" />
		</div>';
		}
		else{
		$catBox = '<div class="st_box2 singleline">';
		$catBox .= '<p><a href="/Special:ListRequestedTopics">&larr; Show categories</a></p>
			</div>';
		}
		return $catBox;
	}

	function getCategoryImage($category){
		$path = "";
		$image = "";
		switch(strtolower($category)){
			case "arts and entertainment":
				$path = "/skins/WikiHow/images/category_Icon_arts.png";
				break;
			case "cars & other vehicles":
				$path = "/skins/WikiHow/images/category_icon_cars.png";
				break;
			case "computers and electronics":
				$path = "/skins/WikiHow/images/category_icon_computers.png";
				break;
			case "education and communications":
				$path = "/skins/WikiHow/images/category_icon_education.png";
				break;
			case "family life":
				$path = "/skins/WikiHow/images/category_icon_family.png";
				break;
			case "finance and business":
				$path = "/skins/WikiHow/images/category_icon_finance.png";
				break;
			case "food and entertaining":
				$path = "/skins/WikiHow/images/category_icon_food.png";
				break;
			case "health":
				$path = "/skins/WikiHow/images/category_icon_health.png";
				break;
			case "hobbies and crafts":
				$path = "/skins/WikiHow/images/category_icon_hobbies.png";
				break;
			case "holidays and traditions":
				$path = "/skins/WikiHow/images/category_icon_holidays.png";
				break;
			case "home and garden":
				$path = "/skins/WikiHow/images/category_icon_home.png";
				break;
			case "personal care and style":
				$path = "/skins/WikiHow/images/category_icon_personal.png";
				break;
			case "pets and animals":
				$path = "/skins/WikiHow/images/category_icon_pets.png";
				break;
			case "philosophy and religion":
				$path = "/skins/WikiHow/images/category_icon_pholosophy.png";
				break;
			case "relationships":
				$path = "/skins/WikiHow/images/category_icon_relationships.png";
				break;
			case "sports and fitness":
				$path = "/skins/WikiHow/images/category_icon_sports.png";
				break;
			case "travel":
				$path = "/skins/WikiHow/images/category_icon_travel.png";
				break;
			case "wikihow":
				$path = "/skins/WikiHow/images/category_icon_wikihow.png";
				break;
			case "work world":
				$path = "/skins/WikiHow/images/category_icon_work.png";
				break;
			case "youth":
				$path = "/skins/WikiHow/images/category_icon_youth.png";
				break;
			default:
				$path = "";
		}
		if($path != ""){
			$path = wfGetPad($path);
			$image = "<img src='{$path}' alt='{$category}' />";
		}
		return $image;
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
		$articles = $topic = $cats = $recommended = 'wide';
		if ($section == 'Topic') {
			$topic .= ' on';
		} else if ($section == 'Recommended') {
			$recommended .= ' on';
		} else if ($section == 'Articles') {
			$articles .= ' on';
		} else if ($section == 'SuggestCategories') {
			$cats .= " on";
		}
		$tabs .= '<a href="/Special:ListRequestedTopics" onmousedown="button_click(this);" class="' . $topic . '">Find a Topic</a>';
		$tabs .= '<a href="/Special:RecommendedArticles" onmousedown="button_click(this);" class="' . $recommended . '">Recommended</a>';
		$tabs .= '<a href="/Special:YourArticles" onmousedown="button_click(this);" class="' . $articles . '">Your Articles</a>';
		$request = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'RequestTopic'), wfMsg('requesttopic'));
		$request = preg_replace('@^<a@', '<a class="notab" style="float:right; margin-right:10px; font-size:1em; width:auto; font-weight:normal;"', $request);
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
		$starttimestamp = date('Ymd-G',$startdate) . '!' . floor(date('i',$startdate)/10) . '00000';
		$data = LeaderBoard::getArticlesWritten($starttimestamp);
		arsort($data);
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
			if ($index > 6) break;
		}
		$html .= "</table>";

		return $html;
	}

	function getActiveWidget() {
		global $wgUser;

		$sk = $wgUser->getSkin();

		$html = "<h3>" . wfMsg('st_currentstats') . "</h3><table class='st_stats'>";

		$unw = number_format(ListRequestedTopics::getUnwrittenTopics(), 0, ".", ", ");

		if($wgUser->getID() != 0){
			$today = ListRequestedTopics::getArticlesWritten(false);
			$topicsToday = ListRequestedTopics::getTopicsSuggested(false);
			$alltime = ListRequestedTopics::getArticlesWritten(true);
			$topicsAlltime = ListRequestedTopics::getTopicsSuggested(true);
		}
		else{
			$today = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Userlogin"), "Login");
			$topicsToday = "N/A";
			$alltime = "N/A";
			$topicsAlltime = "N/A";
		}


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
		return $count;
	}

	function getArticlesWritten($alltime) {
		global $wgUser;
		$dbr = wfGetDB(DB_SLAVE);
		$options = array('fe_user' => $wgUser->getID(), 'page_id = fe_page', 'page_namespace=0');
		if (!$alltime) {
			// just today
			$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
			$options[] = "fe_timestamp > '{$cutoff}'";
		}
		$count = $dbr->selectField( array('firstedit', 'page'),
			array('count(*)'),
			$options
		);

		return number_format($count, 0, ".", ", ");
	}

	function getTopicsSuggested($alltime) {
		global $wgUser;
		$dbr = wfGetDB(DB_SLAVE);
		$options = array('fe_user' => $wgUser->getID(), 'fe_page=page_id', 'page_title=st_title', 'page_namespace= 0');
		if (!$alltime) {
			// just today
			$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
			$options[] = "fe_timestamp > '{$cutoff}'";
		}
		$count = $dbr->selectField(array('firstedit', 'page' ,'suggested_titles'),
			array('count(*)'),
			$options
		);

		return number_format($count, 0, ".", ", ");
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

		$wgOut->addHTML('<script type="text/javascript" language="javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.js?rev=') . WH_SITEREV . '"></script>
   	<link rel="stylesheet" href="' . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.css?rev=') . WH_SITEREV . '" type="text/css" />');


		$dbr = wfGetDB(DB_SLAVE);
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.js?rev=') . WH_SITEREV . '"></script>');

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
				from suggested_titles where st_used= 0
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

class RecommendedArticles extends SpecialPage{

	function __construct() {
        SpecialPage::SpecialPage( 'RecommendedArticles' );
    }

	// for the two little boxes at the top.
	function getTopLevelSuggestions($map, $cats) {
		$dbr = wfGetDB(DB_SLAVE);
		$cat1 = $cats[0];
		$cat2 = sizeof($cats) > 1 ? $cats[1] : $cats[0];
		$top = array($cat1, $cat2);
		$suggests = array();
		$users = array();
		$catresults = array();

		$catarray = "(";
		for($i = 0; $i < count($cats); $i++){
			if($i > 0)
				$catarray .= ",";
			$catarray .= "'{$map[$cats[$i]]}'";
		}
		$catarray .= ")";

		$randstr = wfRandom();
		$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr");

		if(count($cats) > 0)
			$opts[] = "st_category IN $catarray";
		$rows = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text', 'st_category'), $opts,
			"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random', 'GROUP BY' => 'st_category'));

		//print_r($dbr->lastQuery());
		//print_r($dbr->numRows($rows));

		if($dbr->numRows($rows) == 0){
			$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr");
			$rows = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text', 'st_category'), $opts,
			"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random', 'GROUP BY' => 'st_category'));
			for ($i = 0; $i < 2; $i++) {
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}
		}
		else if($dbr->numRows($rows) == 1){
			$row = $dbr->fetchRow($rows);
			$t = Title::makeTitle(NS_MAIN, $row['st_title']);
			$suggests[] = $t;
			$users[] = $row['st_user_text'];
			$userids[] = $row['st_user'];
			$catresults[] = $row['st_category'];

			$randstr = wfRandom();
			$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr", "st_category IN $catarray", "st_title != '" . $row['st_title'] . "'");
			$rows2 = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text', 'st_category'), $opts,
				"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random', 'GROUP BY' => 'st_category'));
			if($dbr->numRows($rows2) >= 1){
				$row = $dbr->fetchRow($rows2);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}
			else{
				$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr");
				$rows = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text', 'st_category'), $opts,
				"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random', 'GROUP BY' => 'st_category'));
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}

		}
		else{
			for ($i = 0; $i < 2; $i++) {
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}
		}
		/*$suggests = array();
		$users = array();
		for ($i = 0; $i < 2; $i++) {
			$randstr = wfRandom();
			$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr");
			if ($top[$i] != "") $opts['st_category'] = $map[$top[$i]];
			$row = $dbr->selectRow('suggested_titles', array('st_title', 'st_user', 'st_user_text'), $opts,
				"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random'));
			$t = Title::makeTitle(NS_MAIN, $row->st_title);
			$suggests[] = $t;
			$users[] = $row->st_user_text;
			$userids[] = $row->st_user;
		}*/
		//$randstr = wfRandom();
		//$opts =array('st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr");
		//if ($top[$i] != "") $opts['st_category'] = $map[$top[$i]];
		//$row = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text'), $opts,
		//	"YourArticles::getTopLevelSuggestions", array('ORDER BY'=>'st_random'));


		$s = "
		<table width='100%' id='top_suggestions' cellspacing='0' cellpadding='0'>
		<tr><td style='width:260px' class='category'>{$catresults[0]}</td>
		<td rowspan='3' class='middle'>OR</td>
		<td style='width:260px' class='category'>{$catresults[1]}</td></tr>

		<tr><td class='title'>{$suggests[0]->getText()}</td><td class='title'>{$suggests[1]->getText()}</td></tr>
		<tr><td class='requestor'>";
		$av = Avatar::getPicture($users[0]);
		if ($av == '')
			$av = '<img src="' . wfGetPad('/skins/WikiHow/images/default_profile.png') . '"/>';
		$s .= $av;
		$s .= "
		<p>Requested By<br />";
		if ($userids[0] > 0) {
			$u = User::newFromName($users[0]);
			$s .= "<a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
		} else {
			$s .= wfMsg('anonymous');
		}
		$s .= "</p><a href='/Special:CreatePage/{$suggests[0]->getPartialURL()}' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class='button button52' style='font-weight:normal'>Write</a>
		<td class='requestor'>";
		$av = Avatar::getPicture($users[1]);
		if ($av == '')
			$av = '<img src="' . wfGetPad('/skins/WikiHow/images/default_profile.png') . '"/>';
		$s .= $av;
		$s .= "<p>Requested By<br />";
		if ($userids[1] > 0) {
			$u = User::newFromName($users[1]);
			$s .= "<a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
		} else {
			$s .= wfMsg('anonymous');
		}
		$s .= "</p><a href='/Special:CreatePage/{$suggests[1]->getPartialURL()}' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class='button button52' style='font-weight:normal'>Write</a>
		</td></tr>
		</tr></table>
	";
		return $s;
	}

    function execute($par){
		global $wgOut, $wgRequest, $wgUser, $wgTitle;
		require_once('Leaderboard.body.php');

		$map = SuggestCategories::getCatMap(true);
		$cats = SuggestCategories::getSubscribedCats();
		$dbr = wfGetDB(DB_SLAVE);
		wfLoadExtensionMessages('RecommendedArticles');

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if ($target == 'TopRow') {
			$wgOut->setArticleBodyOnly(true);
			$wgOut->addHTML($this->getTopLevelSuggestions($map, $cats));
			return;
		}
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.js?rev=') . WH_SITEREV . '"></script>');

		ListRequestedTopics::setActiveWidget();
		ListRequestedTopics::setTopAuthorWidget();
		ListRequestedTopics::getNewArticlesWidget();

		ListRequestedTopics::addTabs('Recommended');

		$wgOut->addHTML(wfMsg('suggestedarticles_header'));


		$suggestions = "";

		if(count($cats) > 0){
			foreach ($cats as $key) {
				$cat = $map[$key];
				$suggestionsArray = array();

				// grab some suggestions
				$randstr = wfRandom();
				$headerDone = false;
				$suggCount = 0;
				//grab 2 suggested articles that are NOT by ANON
				$resUser = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text'),
					array('st_category' => $cat, 'st_used=0', "st_user > 0"),
					"RecommendedArticles::execute",
					array("ORDER BY" => "st_random", "LIMIT"=>2)
				);
				while ($userRow = $dbr->fetchObject($resUser)) {
					$randSpot = mt_rand(0, 4);
					while(!empty($suggestionsArray[$randSpot]))
						$randSpot = mt_rand(0, 4);
					$suggestionsArray[$randSpot]->title = $userRow->st_title;
					$suggestionsArray[$randSpot]->user = $userRow->st_user;
					$suggCount++;
				}

				$res = $dbr->select('suggested_titles', array('st_title', 'st_user', 'st_user_text'),
					array('st_category' => $cat, 'st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr"),
					"RecommendedArticles::execute",
					array("ORDER BY" => "st_random", "LIMIT"=>5)
				);
				if($dbr->numRows($res) > 0){
					while ($row = $dbr->fetchObject($res)) {
						if($suggCount >= 5)
							break;
						$randSpot = mt_rand(0, 4);
						while(!empty($suggestionsArray[$randSpot]))
							$randSpot = mt_rand(0, 4);
						$suggestionsArray[$randSpot]->title = $row->st_title;
						$suggestionsArray[$randSpot]->user = $row->st_user;
						$suggCount++;
					}
				}

				if($suggCount > 0){
					$suggestions .= "<table width='100%' class='suggested_titles_list' cellspacing='0' cellpadding='0'>";
					$suggestions .= "<tr class='st_top_row'><td class='st_icon first'>" . ListRequestedTopics::getCategoryImage($cat) . "</td><td class='st_title second'><strong>{$cat}</strong></td><td class='third'>Requested By</td></tr>";

					for($i = 0; $i < count($suggestionsArray); $i++){
						if(!empty($suggestionsArray)){
							$t = Title::makeTitle(NS_MAIN, $suggestionsArray[$i]->title);
							if ($suggestionsArray[$i]->user > 0) {
								$u = User::newFromName($suggestionsArray[$i]->user);
								$u = "<a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
							}
							else
								$u = "Anonymous";
							$suggestions .= "<tr><td class='st_write'><a href='/Special:CreatePage/{$t->getPartialURL()}'>Write</td><td class='st_title'>{$t->getText()}</td><td class='st_requestor'>{$u}</td></tr>";
					
						}
					}

					$suggestions .= "<tr class='footer'><td colspan='3' style='border:none'><img src='" . wfGetPad('/skins/WikiHow/images/sttable_bottom.png') . "' alt='' /></td></tr>";
					$suggestions .= "</table>";
				}
			}
		}

		if($wgRequest->getVal('surprise') == 1 || $suggestions == "")
			$wgOut->addHTML("<div id='top_suggestions_top'>" . $this->getTopLevelSuggestions($map, $cats) . "</div>");

		if (sizeof($cats) == 0) {
			$wgOut->addHTML(wfMsg('suggested_nocats'));
			$wgOut->addHTML("<a href='#' id='choose_cats'>Choose which categories to display</a>");
			return;
		}

		if($wgRequest->getVal('surprise') == 1 && $suggestions != "")
			$wgOut->addHTML("<div id='article_tabs_line' style='padding:0;'></div>");
			//$wgOut->addHTML("<a onclick='reloadTopRow();' id='tryagain'>Nope, Try Again</a>");
		if($wgUser->getID() > 0){
			$wgOut->addHTML($suggestions);
			$wgOut->addHTML("<a href='#' id='choose_cats'>Choose which categories to display</a>");
		}
		else{
			$rt = $wgTitle->getPrefixedURL();
			$q = "returnto={$rt}";
			$wgOut->addHTML(wfMsg('recommend_anon', $q));
		}
    }

}

class YourArticles extends SpecialPage{

    function __construct() {
        SpecialPage::SpecialPage( 'YourArticles' );
    }

	function getAuthors($t) {
		$dbr = wfGetDB(DB_SLAVE);
		$authors = array();
        $res = $dbr->select('revision',
            array('rev_user', 'rev_user_text'),
            array('rev_page'=> $t->getArticleID()),
            "YourArticles::getAuthors",
            array('ORDER BY' => 'rev_timestamp')
        );
        while ($row = $dbr->fetchObject($res)) {
            if ($row->rev_user == 0) {
               $authors['anonymous'] = 1;
            } else if (!isset($this->mAuthors[$row->user_text]))  {
               $authors[$row->rev_user_text] = 1;
            }
        }
		return array_reverse($authors);
	}

    function execute($par){
		global $wgOut, $wgUser, $wgTitle;

       /* if ( $wgUser->getID() == 0 ) {
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }*/

		require_once('Leaderboard.body.php');

		wfLoadExtensionMessages('RequestTopic');
		$wgOut->addScript('  <style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.css?rev=') . WH_SITEREV . '"; /*]]>*/</style>');
		$wgOut->addScript('<script type="text/javascript" src="' . wfGetPad('/extensions/min/f/extensions/wikihow/suggestedtopics.js?rev=') . WH_SITEREV . '"></script>');

		ListRequestedTopics::setActiveWidget();
		ListRequestedTopics::setTopAuthorWidget();
		ListRequestedTopics::getNewArticlesWidget();

		ListRequestedTopics::addTabs('Articles');

		$wgOut->addHTML(wfMsg('Yourarticles_header'));

		if( $wgUser->getID() > 0){

			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->query("select * from firstedit left join page on fe_page=page_id
					left join suggested_titles on page_title=st_title and page_namespace= 0 where fe_user={$wgUser->getID()} and page_id is not NULL order by st_category");

			if ($dbr->numRows($res) == 0) {
				$wgOut->addHTML(wfMsg("yourarticles_none"));
				return;
			}
			$wgOut->addHTML("<table width='100%' class='suggested_titles_list' id='your_articles_list' cellspacing='0' cellpadding='0'>");

			$last_cat = "-";

			// group it by categories
			// sometimes st_category is not set, so we have to grab the top category
			// from the title object of the target article
			$articles = array();
			while ($row = $dbr->fetchObject($res)) {
				$t = Title::makeTitle(NS_MAIN, $row->page_title);
				$cat = $row->st_category;
				if ($cat == '') {
					$str  = SkinWikihowskin::getTopCategory($t);
					if ($str != '')  {
						$title = Title::makeTitle(NS_CATEGORY, $str);
						$cat = $title->getText();
					} else {
						$cat = "Other";
					}
				}
				if (!isset($articles[$cat]))
					$articles[$cat] = array();
				$articles[$cat][] = $row;
			}
			$top = true;
			foreach ($articles as $cat=>$article_array) {
				$image = ListRequestedTopics::getCategoryImage($cat);
				$style = "";
				if($image == ""){
					$style = "style='padding-left:67px;'";
				}
				if ($top) {
					$wgOut->addHTML("<tr class='st_top_row'><td colspan='2' class='your_cat_top first' {$style}>" . $image . "{$cat}</td><td class='header_views third'>Number of Views</td></tr>");
					$top = false;
				} else {
					$wgOut->addHTML("<tr class='st_middle_row'><td colspan='2' class='your_cat_top' {$style}>" . $image . "{$cat}</td><td class='header_views'>Number of Views</td></tr>");
				}
				foreach ($article_array as $row) {
					$t = Title::makeTitle(NS_MAIN, $row->page_title);
					$ago = wfTimeAgo($row->page_touched);
					$authors = array_keys($this->getAuthors($t));
					$a_out = array();
					for ($i = 0; $i < 2 && sizeof($authors) > 0; $i++) {
						$a = array_shift($authors);
						if ($a == 'anonymous')  {
							$a_out[] = "Anonymous"; // duh
						} else {
							$u = User::newFromName($a);
							if (!$u) {
								echo "{$a} broke";
								exit;
							}
							$a_out[] = "<a href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
						}
					}
					$skin = $wgUser->getSkin();
					$img = $skin->getGalleryImage($t, 46, 35);
					$wgOut->addHTML("<tr><td class='article_image'><img src='{$img}' alt='' width='46' height='35' /></td>"
						 . "<td><h4><a href='{$t->getFullURL()}' class='title'>" . wfMsg('howto', $t->getFullText()) . "</a></h4>"
						. "<p class='meta_info'>Authored by: <a href='{$wgUser->getUserPage()->getFullURL()}'>You</a></p>"
						. "<p class='meta_info'>Edits by: " . implode(", ", $a_out) . " (<a href='{$t->getFullURL()}?action=credits'>see all</a>)</p>"
						. "<p class='meta_info'>Last updated {$ago}</p>"
						. "</td>"
						. "<td class='view_count'>" . number_format($row->page_counter, 0, "", ",") . "</td></tr>"
					);
				}
			}
			$wgOut->addHTML("<tr class='footer'><td colspan='3' style='border:none'><img src='" . wfGetPad('/skins/WikiHow/images/sttable_bottom.png') . "' alt='' /></td></tr>");
			$wgOut->addHTML("</table>");
		}
		else{
			$rt = $wgTitle->getPrefixedURL();
			$q = "returnto={$rt}";
			$wgOut->addHTML( wfMsg('yourarticles_anon', $q) );
		}


		//$this->fetchCreated('snailpet', 5);
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
		    $risingstar = "<img src='" . wfGetPad('/extensions/wikihow/star-green.png') . "' height='20px' width='20px'>";
	    }

	    if ($fa[ $t->getPartialURL() ]) {
		    //$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
		    $featured = "<img src='" . wfGetPad('/extensions/wikihow/star-blue.png') . "' height='20px' width='20px'>";
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

class SuggestCategories extends UnlistedSpecialPage {

	function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'SuggestCategories' );
    }

	// returns a set of keys for the top level categories
	function getCatMap($associative=false) {
		// get it? cat-map? instead of cat-nap? hahah.
        $cat_title = Title::makeTitle(NS_PROJECT, "Categories");
        $rev = Revision::newFromTitle($cat_title);
        $text = preg_replace("@\*\*.*@im", "", $rev->getText());
        $text = preg_replace("@\n[\n]*@im", "\n", $text);
		$lines = split("\n", $text);
		$map = array();
        foreach ($lines as $l) {
            if (strpos($l, "*") === false) continue;
			$cat = trim(preg_replace("@\*@", "", $l));
			if ($associative) {
				$key = strtolower(str_replace(" ", "-", $cat));
				$map[$key] = $cat;
			} else {
				$map[] = $cat;
			}
		}
		return $map;
	}

	function getSubscribedCats() {
		global $wgUser;
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow('suggest_cats', array('*'), array('sc_user'=>$wgUser->getID()));
		if ($row) {
			$field = $row->sc_cats;
			if($field == '')
				return array();
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);
			return $cats;
		}
		$catmap = self::getCatMap();
		
		foreach ($catmap as $cat) {
			$cats[] = strtolower(str_replace(" ", "-", $cat));			
		}
		
		//meow!
		return $cats;
		
		// no cats yet?
		// can we grab some suggested categories from cat_views? Let's find out.
/*		$cats = array();
		$res = $dbr->select('cat_views', array('cv_cat'), array('cv_user'=>$wgUser->getID()), "SuggestCategories::execute", 				array("ORDER BY" => "cv_views DESC"));
		$count = 0;
		while ($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle(NS_CATEGORY, $row->cv_cat);
			if (in_array($t->getText(), $catmap))  {
				$key = strtolower(str_replace(" ", "-", $t->getText()));
				$cats[] = $key;
				$count++;
				if ($count >= 3) break;
			}
		}
		// meow!
		return $cats;
*/
	}

    function execute($par){
		global $wgOut, $wgRequest, $wgUser;

		$dbr = wfGetDB(DB_SLAVE);

		//just getting cats?
		if ($wgRequest->getVal('getusercats')) {			
			$catmap = self::getCatMap();
			$cats = self::getSubscribedCats();
			
			$wgOut->setArticleBodyOnly(true);
			
			if ((count($catmap) == count($cats)) or (empty($cats))) {
				$wgOut->addHTML('All');
				return;
			}
			
			foreach ($catmap  as $cat) {
				$key = strtolower(str_replace(" ", "-", $cat));
				$safekey = str_replace("&", "and", $key);
				
				//hack for the ampersand in our db
				($safekey == 'cars-and-other-vehicles') ? $checkkey = 'cars-&-other-vehicles' :	$checkkey = $safekey;
				
				//are we selecting it?
				if ($cats && in_array($checkkey, $cats)) {
					$usercats[] = $cat; 
				}
			}
			$wgOut->addHTML(implode($usercats, ", "));
			
			return;
		}
		
		// process any postings, saving the categories
		if ($wgRequest->wasPosted()) {
			$field = preg_replace("@ @", "", $wgRequest->getVal('cats'));
			//hack for ampersand
			$field = str_replace('cars-and-other-vehicles','cars-&-other-vehicles',$field);
			
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);
			$cats = array_unique($cats);
			sort($cats);
			$dbw = wfGetDB(DB_MASTER);
			$sql = "INSERT INTO suggest_cats VALUES(" .$wgUser->getID() . ", " . $dbw->addQuotes(implode($cats, ","))
				. ") ON DUPLICATE KEY UPDATE sc_cats = " . $dbw->addQuotes(implode($cats, ","));
			$dbw->query($sql);
			//print_r($dbw->lastQuery());
			$wgOut->addHTML("<br/><br/>Categories updated.<br/><br/>");
			
			//we doing the teen filter too?
			if ($wgRequest->getVal('wpContentFilter') != '') {
				$wgUser->setOption('contentfilter',$wgRequest->getVal('wpContentFilter'));
				$wgUser->saveSettings();
			}
			
			if ($wgRequest->getVal('type') == '') {
				header('location:/Special:RecommendedArticles');
			} else {
				header('location:/Special:EditFinder/'.$wgRequest->getVal('type'));
			}
		}

		$wgOut->setArticleBodyOnly(true);

		$catmap = self::getCatMap();
		$cats = self::getSubscribedCats();

		$hiddencats = implode($cats, ",");
		$hiddencats = str_replace("&","and",$hiddencats);
		
		// get top categories
		$theHTML .= "<form method='post' action='/Special:SuggestCategories' id='suggest_cats' name='suggest_cats'><input type='hidden' name='cats' value='" . $hiddencats . "'/>";
		$theHTML .= "<table width='100%' class='categorytopics selecttopics'><tr>";
		$index = 0;
		$select_count = 0;
		foreach ($catmap  as $cat) {
			$key = strtolower(str_replace(" ", "-", $cat));
			$safekey = str_replace("&", "and", $key);
			//hack for the ampersand in our db
			($safekey == 'cars-and-other-vehicles') ? $checkkey = 'cars-&-other-vehicles' :	$checkkey = $safekey;

			//are we selecting it?
			if ($cats && in_array($checkkey, $cats)) {
				$c = "chosen";
				$s = "checked='checked'";
				$select_count++;
			}
			else {
				$c = "not_chosen";
				$s = "";
			}
			
			$theHTML .= "<td id='{$safekey}' class='{$c} categorylink'><a class=''><input type='checkbox' id='check_{$safekey}' {$s} />" .  ListRequestedTopics::getCategoryImage($cat) . "<br />{$cat}</a></td>";
			$index++;
			if ($index % 6 == 0)
	    		$theHTML .= "</tr><tr>";
			
		}
		$actual_count = $index;
		if($index % 6 <= 5){
			while($index % 6 != 0){
					$theHTML .= "<td></td>";
					$index++;
			}
		}
		
		$theHTML .= '</tr></table> '
					.'<a onmouseout="button_unswap(this);" onmouseover="button_swap(this);" style="float: right; background-position: 0% 0pt;" class="button button100" onclick="document.suggest_cats.submit();" id="the_save_button">' . wfMsg('save') . '</a>';
		
		if ($wgRequest->getVal('type')) {
			$filter = $wgUser->getOption('contentfilter');
			// TEEN FILTER
			$options = wfMsg('pref_content_preferences_info') . " "
				. Xml::radioLabel(wfMsg('pref_content_preferences_all'),'wpContentFilter', 0 , 'wpContentFilter_0', ($filter == 0), array('content'=>$filter)) . " "
				. Xml::radioLabel(wfMsg('pref_content_preferences_young'),'wpContentFilter', 1, 'wpContentFilter_1', ($filter == 1), array('content'=>$filter)) . " "
				. Xml::radioLabel(wfMsg('pref_content_preferences_adult'),'wpContentFilter', 2, 'wpContentFilter_2', ($filter == 2), array('content'=>$filter)); 
		
			$theHTML .= '<div class="cats_age_filter">'.$options.'</div>';
		}
		
		//selected all?
		$s = $select_count == $actual_count ? "checked='checked'" : "";
		//add checkbox at the top
		$theHTML = "<input type='checkbox' id='check_all_cats' ".$s." /> <label for='check_all_cats'>All categories</label>".$theHTML."</form>";
		
		$wgOut->addHTML($theHTML);
	}
}

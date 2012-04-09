<?php

class GoogSearch extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'GoogSearch' );
	}

	function getSearchBox($formid, $q = '', $size = 30) {
		global $wgServer, $wgLanguageCode;
		$search_box = wfMsg('cse_search_box_new', "", $formid, $size, htmlspecialchars($q), $wgLanguageCode);
		$search_box = preg_replace('/\<[\/]?pre\>/', '', $search_box);
		return $search_box;
	}

	function execute($par = '') {
		global $wgUser, $wgOut, $wgScriptPath, $wgRequest, $wgServer;
		global $wgLanguageCode, $wgUseLucene;
		global $gCurrent, $gResults, $gEn, $IP;

		if (! $wgUseLucene) {
			require_once("$IP/includes/Search.php");
			GoogSearch::execute();
			return;
		}
		$me = Title::makeTitle(NS_SPECIAL, "GoogSearch");

		$wgOut->setArticleBodyOnly(true);

		$q = $wgRequest->getVal('q');
		$q = strip_tags($q); // clean of html to avoid XSS attacks
		$wgRequest->setVal('q', $q);

		$start = $wgRequest->getInt('start', 0);

		$wgOut->setHTMLTitle(wfMsg('lsearch_title_q', $q));

		$wgOut->addHTML("<html><head><title>" . wfMsg('lsearch_title_q', $q) . "</title>
 <style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/skins/WikiHow/new.css&rev=') . WH_SITEREV . "'; /*]]>*/</style>
 <script type='text/javascript' src='" . wfGetPad('/extensions/min/f/skins/WikiHow/google_cse_search_box.js?') . WH_SITEREV . "'></script>
</head>

<body>
<div id='header'>
	<div id='logo'>
		<a href='" . wfMsgForContent('mainpage') . "'  class='imglink'>
		<img src='" . wfGetPad('/skins/WikiHow/images/wikihow.png') . "' id='wikiHow' alt='wikiHow - The How-to Manual That You Can Edit' width='216' height='37'/><p>the how to manual that you can edit</p></a>
	</div>
</div>
<div id='main'>
<div id='search_page'>

");
		$fname = "GoogSearch::execute";
		$search_page_results = wfMsg('cse_search_page_results');
		$search_page_results = preg_replace('/\<[\/]?pre\>/', '', $search_page_results);

		$search_box = GoogSearch::getSearchBox("cse-search-box", $q, 63);

		$wgOut->addHTML($search_box."\n");

		$wgOut->addHTML("<div style='clear:both;margin-bottom:10px;'></div>");

		$wgOut->addHTML("
			<!-- GOOGLE SEARCH RESULTS HERE -->
			<img src='" . wfGetPad('/skins/WikiHow/images/actions_top.png') . "' width='989' height='14' alt='' style='display:block;' />
			<div class='actions' >

			<div class='search_results'><br/>
			<div id='results' style='width: 900px;'>{$search_page_results}</div>
			</div>

			</div>
			<img src='" . wfGetPad('/skins/WikiHow/images/actions_bottom.png') . "' width='989' height='19' alt='' />

			");
		$wgOut->addHTML("<div style='clear:all; margin-top:10px;'></div>");

		$wgOut->addHTML($search_box."\n");

		$wgOut->addHTML('<br/></div></div><br/><br/>

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-2375655-1");
pageTracker._setDomainName(".wikihow.com");
pageTracker._trackPageview();} catch(err) {}
</script>
');

		$wgOut->addHTML("</body>
</html>");

	}

}



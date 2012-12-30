<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 class NewArticles extends UnlistedSpecialPage{

	var $risingStars;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'NewArticles' );
	}

	function getNewArticlesBox(){
		global $wgUser;
		$sk = $wgUser->getSkin();
		$titles = $this->getRisingStars();

		$html = "<div id='category_shell'><img src='" . wfGetPad('/skins/WikiHow/images/article_top.png') . "' width='679' height='10' alt='' class='module_cap' /><div id='newarticles'>";
		$html .= "<div id='newarticles_header' class='newarticles_header'><h1>" . wfMsg('newarticles') . "</h1></div>";
		$html .= "<div class='newarticles_inner'><table class='featuredArticle_Table'>";
		foreach($titles as $title){
			$html .= $sk->featuredArticlesLineWide($title, $title->getText());
		}
		$html .=  "</table>";

		$html .= "<div id='newarticles_divider'>" . wfMsg('morenewarticles') . "</div>";

		$html .= "<div id='newarticles_list'>" . $this->getArticleList() . "<div class='clearall'></div></div>";

		$html .= "</div></div><img src='" . wfGetPad('/skins/WikiHow/images/article_bottom.png') . "' width='679' height='12' alt='' class='module_cap' /></div>";

		return $html;
	}

	function getArticleList(){
		global $wgMemc, $wgUser;
		$key = "newarticleslist";
		//$cached = $wgMemc->get($key);
		//if ($cached)  {
			//return $cached;
		//}

		$skin = $wgUser->getSkin();

		$dbr = wfGetDB(DB_SLAVE);
		$sql = self::getSql() . " ORDER BY value DESC LIMIT 50";
		$res = $dbr->query( $sql );

		$html = "";
		$num = 18;

		for( $i = 0; $i < $num && $row = $dbr->fetchObject( $res ); ) {
			$title = Title::makeTitle($row->namespace, $row->title);
			//check to see if its in the list at the top
			if($this->checkTitle($title))
				continue;
			$link = $skin->makeKnownLinkObj($title);
			if($i % 6 == 0)
				$html .= "<ul>";
			$html .= "<li>" . $link . "</li>";
			if($i % 6 == 5)
				$html .= "</ul>";

			$i++;
		}

		$title = Title::makeTitle( NS_SPECIAL, 'NewArticles' );
		$link = $skin->makeKnownLinkObj( $title, wfMsg('more_newarticles') );

		$html .= "<p id='more_newarticles'>" . $link . "<img alt='' src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' ></p>";

		$wgMemc->set($key, $html, 3600);
		return $html;
	}

	function checkTitle($title){
		foreach($this->risingStars as $t){
			if($title->getArticleID() == $t->getArticleID())
				return true;
		}
		return false;
	}

	function getSql(){
		return "SELECT tl_from, page_namespace as namespace, nap_patrolled, page_counter as counter, fe_timestamp as value, page_title AS title, page_id as cur_id  FROM `newarticlepatrol`  LEFT JOIN `page` ON page_id = nap_page LEFT JOIN `firstedit` ON fe_page = nap_page LEFT JOIN `templatelinks` ON tl_from = page_id WHERE nap_patrolled = 1 AND tl_from IS NULL";
	}

	function getRisingStarsBox() {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$titles = $this->getRisingStars();

		$html = "<h3>" . wfMsg('newarticles') . "</h3>\n<table>";
		foreach($titles as $title){
			$html .= $sk->featuredArticlesLine($title, $title->getText());
		}
		$html .=  "</table>";

		return $html;
	}

	function getRisingStars(){
		global $wgMemc;
		$key = "newarticlesbox";
		//$cached = $wgMemc->get($key);
		//if ($cached)  {
			//return $cached;
		//}
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('pagelist', 'pl_page', array('pl_list'=>'risingstar'),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'pl_page desc', 'LIMIT'=>5)
			);
		while($row = $dbr->fetchObject($res)) {
			$ids[] = $row->pl_page;
		}
		$res = $dbr->select(array('page'),
			array('page_namespace', 'page_title'),
			array('page_id IN (' . implode(",", $ids) . ")"),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'page_id desc', 'LIMIT'=>5)
			);
		$titles = array();
		$this->risingStars = array();
		while($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle(NS_MAIN, $row->page_title);
			if (!$t)
				continue;
			$titles[] = $t;
			$this->risingStars[] = $t;
		}

		$wgMemc->set($key, $titles, 3600);
		return $titles;
	}

	function showModule(){

	}

	function execute($par){
		list( $limit, $offset ) = wfCheckLimits();
		$llr = new NewArticlesPage();
    	return $llr->doQuery( $offset, $limit );
	}

 }


 class NewArticlesPage extends QueryPage {

	function NewArticlesPage(){
		global $wgHooks, $wgOut;
		list( $limit, $offset ) = wfCheckLimits();
		$wgOut->setPageTitle(wfMsg('newarticles_range', $offset+1, $offset+$limit));
	}

	function getName() {
		return "NewArticles";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return NewArticles::getSql();
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::makeTitle( $result->namespace, $result->title );
		$link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		$nv = wfMsgExt( 'nviews', array( 'parsemag', 'escape'),
			$wgLang->formatNum( $result->counter ) );
		return wfSpecialList($link, $nv);
	}
}

?>

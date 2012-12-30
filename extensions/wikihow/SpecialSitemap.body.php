<?php
class Sitemap extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'Sitemap' );
    }

	function getTopLevelCategories() {
			global $wgCategoriesArticle;
			$results = array (); 
			$revision = Revision::newFromTitle( Title::newFromText( $wgCategoriesArticle ) );
			if (!$revision) return $results;
			$lines = split("\n", $revision->getText() );
			foreach ($lines as $line) {
				if (preg_match ('/^\*[^\*]/', $line)) {
					$line = trim(substr($line, 1)) ;
					switch ($line) {
						case "Other":
						case "wikiHow":
							break;
						default:
							$results [] = $line;
					}
				}
			}
			return $results;
	}
	
	function getSubcategories($t) {
	 	$dbr =& wfGetDB( DB_SLAVE );
		$subcats = array();
		$res = $dbr->select ( array ('categorylinks', 'page'),
				array('page_title'),
				array('page_id=cl_from',
						'cl_to' => $t->getDBKey(),
						'page_namespace=' .NS_CATEGORY
					),
				"Sitemap:wfGetSubcategories"
		);
		while ($row = $dbr->fetchObject($res)) {
			if (strpos($row->page_title, 'Requests') !== false
				)
				continue;
			$subcats[] = $row->page_title;
		}
		return $subcats;
	}
	
	function execute ($par) {
			global $wgOut, $wgUser;
			$wgOut->setRobotPolicy("index,follow");
			$sk = $wgUser->getSkin();
			$topcats = $this->getTopLevelCategories();
			$count = 0;
			$wgOut->addHTML("
				<style>
					#catentry li {
						margin-bottom: 0;
					}
					.cats td {
						vertical-align: top;
						border: 1px solid #C3D9FF;
						padding: 5px;
					}
				</style>
				<table align='center' class='cats' width='90%' cellspacing=10px>");
			foreach ($topcats as $cat) {
					$t = Title::newFromText($cat, NS_CATEGORY);
					$subcats = $this->getSubcategories($t);
					if ($count % 2 == 0)
						$wgOut->addHTML("<tr>");
					$wgOut->addHTML ( "<td><h3>" . $sk->makeLinkObj($t, $t->getText()) . "</h3><ul id='catentry'>");
					foreach ($subcats as $sub) {
						$t = Title::newFromText($sub, NS_CATEGORY);
						$wgOut->addHTML ( "<li>" . $sk->makeLinkObj($t, $t->getText()) . "</li>\n");
					}
					$wgOut->addHTML("</ul></td>\n");
					if ($count % 2 == 1)
						$wgOut->addHTML("</tr>");
					$count++;
			}	
	
			$wgOut->addHTML("</table>");
	}
}

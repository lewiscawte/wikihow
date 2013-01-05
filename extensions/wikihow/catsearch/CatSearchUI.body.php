<?
class CatSearchUI extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'CatSearchUI' );
	}
	
	function execute($par) {
		global $wgOut, $wgRequest;

		$fname = 'CatSearchUI::execute';
		wfProfileIn( $fname );

		$js = HtmlSnips::makeUrlTags('js', array('catsearchui.js'), '/extensions/wikihow/catsearch', !CATSEARCH_DEBUG);
		$css = HtmlSnips::makeUrlTags('css', array('catsearchui.css'), '/extensions/wikihow/catsearch', CATSEARCH_DEBUG);
		$vars = array('js' => $js, 'css' => $css, 'cats' => $this->getUserCategoriesHtml());
		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$html = EasyTemplate::html('CatSearchUI', $vars);

		$embedded = intval($wgRequest->getVal('embed'));
		$wgOut->setArticleBodyOnly($embedded);
		$wgOut->addHtml($html);

		wfProfileOut( $fname );
	}

	function getUserCategoriesHtml() {
		$cats = CategoryInterests::getCategoryInterests();
		$html = "";
		foreach ($cats as $cat) {
			$catName = str_replace("-", " ", $cat);
			$html .= "<div class='csui_category'><span class='csui_close'>X</span>$catName<div class='csui_hidden'>$cat</div></div>\n";
		}
		return $html;
	}
}

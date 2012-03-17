<?

class ArticleWidgets extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ArticleWidgets' );
	}

	function execute($par) {
		global $wgOut, $wgRequest;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		
		$target = strtoupper($target);
		$wgOut->setArticleBodyOnly(true);
		
		if ($target == 'BMI') {
			$html = self::getBMICalculator();
		}

		$wgOut->addHTML($html);
	}
	
	//BMI Calculator Widget
	function getBMICalculator() {
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$html = $tmpl->execute('BMI/bmi.tmpl.php');
		return $html;
	}
	
	function GrabWidget($widget_name) {
		$html = '';
		
		if ($widget_name == 'BMI') {
			$html = '<iframe src="'.wfGetPad('/Special:ArticleWidgets/BMI').'" scrolling="no" frameborder="0" class="article_widget" allowTransparency="true"></iframe>';
			$html = '<div class="widget_br"></div>'.$html.'<div class="widget_br"></div>';
		}
		
		return $html;
	}
}
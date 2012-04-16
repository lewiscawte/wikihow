<?
if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class Slider extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Slider' );
	}
	
	public function getBox() {
		return self::getBox_02();
	}
	
	public function getBox_01() {
		global $wgOut, $wgLanguageCode, $wgServer, $wgTitle;
		wfLoadExtensionMessages('Slider');

        // Remove background for non-english sites. Unfortunate, but bg image has English in it.
        $slider_thanks_intl = "";
        if ($wgLanguageCode != 'en') {
            $slider_thanks_intl = "class='slider_thanks_intl'";
        }

		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		
		$theBox = "<div id='sliderbox'>
						<div id='slider_thanks' $slider_thanks_intl>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text'>
								<p class='tta_first'>".wfMsg('slider-text')."</p>
								<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone')."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	public function getBox_02() {
		wfLoadExtensionMessages('Slider');
		
		$theBox = "<div id='sliderbox'>
						<div id='slider_thanks_02'>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1_02'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text_02'>
								<p class='tta_first'>".wfMsg('slider-text')."</p>
								<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone')."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	
	/**
	 * EXECUTE
	 **/
	function execute ($par = '') {	
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		
		//log it to the database
		if ($wgRequest->getVal('action')) {
			$wgOut->addHTML($res);
			return;
		}
	}
	
}

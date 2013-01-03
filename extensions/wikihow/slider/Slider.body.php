<?
if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class Slider extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Slider' );
	}
	
	public function getBox() {
		global $wgOut;		
		wfLoadExtensionMessages('Slider');

		//MIXPANEL
		$wgOut->addScript("<script type='text/javascript'> try {  var mpmetrics = new MixpanelLib('56772aa11cf306f711166fd445f8d7cb'); } catch(err) { null_fn = function () {}; var mpmetrics = {  track: null_fn,  track_funnel: null_fn,  register: null_fn,  register_once: null_fn, register_funnel: null_fn }; } </script>");

		//engage!
		$wgOut->addScript('<script type="text/javascript">var slider = new Slider(); if (!getCookie("sliderbox")) { slider.init(); }</script>');
							
		$theBox = "<div id='sliderbox'>
						<h2>".wfMsg('slider-head')."<a href='#' id='slider_close_button'>x</a></h2>
						<div id='slider_contents'>
							<p>".wfMsg('slider-intro')."</p>
							<a href='#' class='slider_strong' id='slider_start_link'>".wfMsg('slider-start-link')."</a>
							<p>".wfMsg('slider-paragraph')."</p>
							<div id='slider_choices'>
								<div id='slider_no'><a href='#'>".wfMsg('slider-no-link')."</a></div>
								<a href='#' class='button button52' id='slider_start_button'>".wfMsg('slider-start-button')."</a>
							</div>
						</div>
					</div>";

		return $theBox;
	}

	//(using mixpanel instead)
	//(this is commented out of the code)
	public function SliderLog($action) {
		global $wgUser, $dbm;

		//touch db
		$dbm = wfGetDB(DB_MASTER);
		$dbm->insert('sliderbox', array('sb_action'=>$action,'sb_user'=>$wgUser->getName(),'sb_timestamp'=>wfTimestampNow()));
		
		return;
	}
	
	
	/**
	 * EXECUTE
	 **/
	function execute ($par = '') {	
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		
		//log it to the database
		if ($wgRequest->getVal('action')) {
			$res = self::SliderLog($wgRequest->getVal('action'));
			$wgOut->addHTML($res);
			return;
		}
	}
	
}

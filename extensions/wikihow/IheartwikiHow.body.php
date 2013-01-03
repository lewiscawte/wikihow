<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 class IheartwikiHow extends UnlistedSpecialPage{

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'IheartwikiHow' );
	}

	function execute($par){
		global $wgOut, $wgMemc, $wgRequest;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		if(isset($target)){

			$wgOut->redirect(wfGetPad("/skins/WikiHow/images/wikiHow_badge_" . $target . ".png"));

			//not used right now
			/*$wgOut->setArticleBodyOnly(true);

			$cacheValue = $wgMemc->get(wfMemcKey("iheartwikihow_" . $target));

			if($cacheValue){
				$wgOut->addHTML($cacheValue);
				return;
			}

			$image = file_get_contents(wfGetPad("/skins/WikiHow/images/wikiHow_badge_" . $target . ".png"));

			$wgOut->addHTML($image);

			$wgMemc->set(wfMemcKey(wfMemcKey("iheartwikihow_" . $target)), $image);*/
		}
		else{
			$wgOut->setPageTitle( wfMsg('iheartwikihow_title') );

			if (class_exists('WikihowCSSDisplay'))
				WikihowCSSDisplay::setSpecialBackground(true);
			$wgOut->addHTML("<div style='background-color:#F5F5F5;margin: -13px -27px -50px -23px;padding: 18px 27px 15px 23px;'>");
			$this->showBadges();
			$wgOut->addHTML("</div>");
		}
	}

	function showBadges(){
		global $wgOut;

		$html = "<p>Select the badge size you want, then copy and paste the cody below into your page.</p>";

		$html .= $this->getBadge(180);
		$html .= $this->getBadge(120);
		$html .= $this->getBadge(150);
		$html .= $this->getBadge(240);

		$wgOut->addHtml($html);
	}

	function getBadge($badgeSize){
		$html = "<table style='vertical-align:top; margin-bottom:15px;' cellspacing='0' cellpading='0' >";
		$html .= "<tr><td colspan='2'><img src='" . wfGetPad("/skins/WikiHow/images/sttable_top_white.png") . "'</td></tr>";
		$html .= "<tr><td style='border-left:1px solid #DEDACF; padding-left:15px; padding-right:35px; vertical-align:top;background-color:#ffffff;'><h4 style='font-size:18px;'>Badge:</h4></td>";
		$html .= "<td style='border-right:1px solid #DEDACF; padding-right:15px; padding-bottom:15px; vertical-align:top;background-color:#ffffff;'><img src='" . wfGetPad("/skins/WikiHow/images/wikiHow_badge_" . $badgeSize . ".png") . "' /></td></tr>";
		$html .= "<tr><td style='border-left:1px solid #DEDACF; padding-left:15px; vertical-align:top; padding-right:35px;background-color:#ffffff;'><h4 style='font-size:18px;'>Code:</h4></td>";
		$html .= "<td style='border-right:1px solid #DEDACF; padding-right:15px; vertical-align:top;background-color:#ffffff;'>" . htmlspecialchars("<a href='http://www.wikihow.com'><img src='http://www.wikihow.com/Special:IheartwikiHow/" . $badgeSize . "' alt='I &lt;3 wikiHow' style='border:none' /></a>") . "</td>";
		$html .= "</tr>";
		$html .= "<tr><td colspan='2'><img src='" . wfGetPad("/skins/WikiHow/images/sttable_bottom.png") . "' /></td></tr></table>";

		return $html;
	}

	function addIheartwikiHowWidget(){
		$html = "<h3><a href='/Special:IheartwikiHow' style='float:right; font-size:0.9em; font-weight:normal;'>see all</a><span>". wfMsg('iheart-title') . "<span></h3>";
		$html .= "<a href='/Special:IheartwikiHow' style='margin: 14px 0pt 0pt 14px; display: block;'><img src='" . wfGetPad("/skins/WikiHow/images/wikiHow_badge_240.png") . "' /></a>";

		return $html;
	}

 }

?>

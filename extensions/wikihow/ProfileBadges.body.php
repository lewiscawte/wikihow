<?php

class ProfileBadges extends SpecialPage {

	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		SpecialPage::SpecialPage( 'ProfileBadges' );
	}

	function execute($par){
		global $wgOut;

		wfLoadExtensionMessages('ProfileBadges');

		if (class_exists('WikihowCSSDisplay'))
				WikihowCSSDisplay::setSpecialBackground(true);
		$wgOut->addScript('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/ProfileBadges.css"; /*]]>*/</style>');

		$wgOut->setPageTitle(wfMsg('ab-title'));

		$wgOut->addHTML("<div class='undoArticleInner'>");
		$wgOut->addHTML(ProfileBadges::getBadge('admin'));
		$wgOut->addHTML(ProfileBadges::getBadge('nab'));
		$wgOut->addHTML(ProfileBadges::getBadge('fa'));
		$wgOut->addHTML("</div>");
	}

	function getBadge($badgeName){
		$html = "<div class='ab-box'>";
		$html .= "<div class='ab-badge ab-" . $badgeName . "'></div>";
		$html .= "<h4>" . wfMsg("ab-" . $badgeName . "-title") . "</h4>";
		$html .= wfMsgWikiHtml("ab-" . $badgeName . "-description");
		$html .= "</div>";

		return $html;
	}

}


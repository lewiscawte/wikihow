<?php

class AuthorBadges extends SpecialPage {

	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		SpecialPage::SpecialPage( 'AuthorBadges' );
	}

	function execute($par){
		global $wgOut;

		wfLoadExtensionMessages('AuthorBadges');

		if (class_exists('WikihowCSSDisplay'))
				WikihowCSSDisplay::setSpecialBackground(true);
		$wgOut->addScript('<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/AuthorBadges.css"; /*]]>*/</style>');

		$wgOut->setPageTitle(wfMsg('ab-title'));

		$wgOut->addHTML("<div class='undoArticleInner'>");
		$wgOut->addHTML(AuthorBadges::getBadge('admin'));
		$wgOut->addHTML(AuthorBadges::getBadge('nab'));
		$wgOut->addHTML(AuthorBadges::getBadge('fa'));
		$wgOut->addHTML("</div>");
	}

	function getBadge($badgeName){
		$html = "<div class='ab-box'>";
		$html .= "<div class='ab-badge ab-" . $badgeName . "'></div>";
		$html .= "<h4>" . self::getUserTitle($badgeName) . "</h4>";
		$html .= "<p>" . wfMsg("ab-" . $badgeName . "-description");
		$html .= "</div>";

		return $html;
	}

	function getUserTitle($badgeName){
		switch($badgeName){
			case 'admin':
				return "Administrator";
			case 'nab':
				return "Nabber";
			case 'fa':
				return "Writer";
		}
	}

}


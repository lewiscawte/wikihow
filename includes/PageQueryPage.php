<?php

/**
 * Variant of QueryPage which formats the result as a simple link to the page
 *
 * @package MediaWiki
 * @addtogroup SpecialPage
 */
class PageQueryPage extends QueryPage {

	/**
	 * Format the result as a simple link to the page
	 *
	 * @param Skin $skin
	 * @param object $row Result row
	 * @return string
	 */
	public function formatResult( $skin, $row ) {
		global $wgContLang;
		if ($title = Title::makeTitleSafe( $row->namespace, $row->title )) {
			return $skin->makeKnownLinkObj( $title,
				htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		} else {
			wfDebug("Vooo title NOT safe fix in DB: ".$row->title."\n");
			return "";
		}
	}
}



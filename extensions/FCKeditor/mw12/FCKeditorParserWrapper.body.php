<?php

/**
 * Used by MW 1.12+
 */
class FCKeditorParserWrapper extends Parser_OldPP
{
	function __construct() {
		global $wgParser;

		parent::__construct();

		$wgParser->firstCallInit();
		foreach ($wgParser->getTags() as $h) {
			if (!in_array($h, array("pre"))) {
				$this->setHook($h, array($this, "fck_genericTagHook"));
			}
		}
	}

	function replaceInternalLinks( $s ) {
		return parent::replaceInternalLinks( $s );
	}

	function makeImage( $title, $options, $holders = false ) {
		return parent::makeImage( $title, $options, $holders );
	}

	function internalParse( $text ) {
		return parent::internalParse( $text );
	}

	function parse( $text, Title $title, ParserOptions $options, $linestart = true, $clearState = true, $revid = null ) {
		return parent::parse( $text, $title, $options, $linestart, $clearState, $revid );
	}
}

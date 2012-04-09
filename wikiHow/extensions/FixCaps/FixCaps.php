<?php
/**
 * An extension that adds a new button, which allows to change the
 * capitalization of the page text, to the edit toolbar.
 *
 * Functionality is provided here, styling is done elsewhere.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgHooks['EditPageBeforeEditToolbar'][] = 'wfFixCaps';

function wfFixCaps( &$toolbar ) {
	global $wgExtensionAssetsPath;

	// Inline JS, moved from skins/common/clientscript.js, because I'm too lazy
	// to figure out how to make ResourceLoader work properly in an environment
	// like this.
	$toolbar .= <<<JS
function fixcaps( e ) {
	var text = e.value.toLowerCase().replace( /(^\s*\w|[\.\!\?]\s*\w)/g, function( c ) { return c.toUpperCase(); } );
	text = text.replace( /(^(#|\*)[ ]*[^ ])/gim, function( c ) { return c.toUpperCase(); } );
	text = text.replace( /(^==[ ]*[^ ])/gim, function( c ) { return c.toUpperCase(); } );
	e.value = text;
}
JS;
	$toolbar .= '<div style="float: left;"><input id="fixcaps_button" type="image" src="' .
		$wgExtensionAssetsPath . '/FixCaps/fixcaps.png" accesskey="o" onclick="javascript:fixcaps(document.editform.wpTextbox1)" /></div>' .
		"\n";

	return true;
}
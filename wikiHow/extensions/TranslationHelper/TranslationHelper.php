<?php
/**
 * TranslationHelper -- provides a special page for keeping system messages up
 * to date
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link http://www.mediawiki.org/wiki/Extension:TranslationHelper Documentantion
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is not a valid entry point.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'TranslationHelper',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides [[Special:TranslationHelper|a basic tool]] for keeping system messages up to date',
	'url' => 'http://www.mediawiki.org/wiki/Extension:TranslationHelper',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['TranslationHelper'] = $dir . 'TranslationHelper.i18n.php';
$wgAutoloadClasses['TranslationHelper'] = $dir . 'TranslationHelper.body.php';
$wgSpecialPages['TranslationHelper'] = 'TranslationHelper';

// Configuration settings
// @todo FIXME: rethink these...
$wgTranslationHelper = array(
	'sourceDBName' => 'wikidb_16', // this replaces $wgTH_SourceDBName
	'localDomain' => 'de.wikihow.com', // this replaces $wgTH_LocalDomain
	'sourceDomain' => 'www.wikihow.com', // this replaces $wgTH_SourceDomain
	'sourceLang' => 'en' // this replaces $wgTH_SourceLang
);
<?php
/**
 * Customized search backend for Google Mini
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'LSearch',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Customized search backend for Google Mini',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.LSearch'] = array(
	'styles' => 'searchresults.css',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'LSearch',
	'position' => 'top'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['LSearch'] = $dir . 'LSearch.i18n.php';
$wgAutoloadClasses['LSearch'] = $dir . 'LSearch.body.php';
$wgSpecialPages['LSearch'] = 'LSearch';

// Use this extension? If set to false, Special:LSearch will redirect to
// Special:Search, the default MediaWiki search page.
$wgUseLSearch = true;

// An array of queries that will be ignored by the search engine
$wgBogusQueries = array(
	'_vti_bin/owssvr.dll',
	'msoffice/cltreq.asp',
	'crossdomain.xml',
	'type in here',
	'ehow_feed.rss',
	'__utm.gif',
	'null',
	'_vpi.xml',
	'wikihow.gif',
	'',
	'sharetab_email.gif',
	'main page/favicon.ico',
	'sharetab_delicious.gif',
	'sharetab_digg.gif',
	'sharetab_facebook.png',
	'sharetab_blogger.gif',
	'sharetab_google.png',
	'cnw_logowikihow1_133.png',
	'acticon_create.gif',
	'acticon_edit.gif',
	'$1',
	'http:/amyru.h18.ru/images/cs.txt',
	'logo_creative_commons.gif',
	'acticon_discuss.gif',
	'sharetab_yahoo.png',
	'logo_mediawiki.png',
	'2547 1_3 0 20.xml',
	'extreme.xml',
	'_vti_inf.html',
	'_vti_bin/shtml.exe/_vti_rpc',
	'acticon_email.gif',
	'acticon_printable.gif',
	'opera6fixes.css',
	'opera7fixes.css',
	'khtmlfixes.css',
);
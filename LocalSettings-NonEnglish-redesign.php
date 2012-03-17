<?php

# This file was automatically generated by the MediaWiki installer.
# If you make manual changes, please keep track in case you need to
# recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.

# If you customize your file layout, set $IP to the directory that contains
# the other MediaWiki files. It will be used as a base to locate files.
if (defined('MW_INSTALL_PATH')) {
	$IP = MW_INSTALL_PATH;
} else {
	$IP = dirname(__FILE__);
}

$version = phpversion();
if (preg_match("@5.3@", $version)) {
	$path = array($IP, "$IP/includes", "$IP/languages", "$IP/extensions/wikihow", '/usr/local/lib/php/openid-php-openid-782224d');
} else {
	$path = array($IP, "$IP/includes", "$IP/languages", "$IP/extensions/wikihow", '/usr/local/lib/php/php-openid-2.1.0');
}

set_include_path( implode(PATH_SEPARATOR, $path) . PATH_SEPARATOR . get_include_path() );

set_include_path( implode(PATH_SEPARATOR, $path) . PATH_SEPARATOR . get_include_path() );

require_once("$IP/includes/DefaultSettings.php");

# Include site revision if it exists, so that URLs get updated on changes
@include_once("$IP/siterev.php");
if (!defined('WH_SITEREV')) define('WH_SITEREV', '100');

define('IS_PROD_EN_SITE', false);
define('IS_PROD_INTL_SITE', true);
require_once("$IP/LocalKeys.php");

# If PHP's memory limit is very low, some operations may fail.
# ini_set( 'memory_limit', '20M' );

if ($wgCommandLineMode) {
	if ( isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER) ) {
		die("This script must be run from the command line\n");
	}
} elseif ( empty($wgNoOutputBuffer) ) {
	## Compress output if the browser supports it
	if( !ini_get('zlib.output_compression') ) @ob_start('ob_gzhandler');
}

$wgSitename = 'wikiHow';
$wgCookieDomain = 'wikihow.com';
$isDevServer = strpos(@$_SERVER['SERVER_NAME'], 'wikidiy.com') !== false;
if (@$_SERVER['SERVER_NAME'] == 'm.wikihow.com' ||
	@$_SERVER['SERVER_NAME'] == 'carrot.wikihow.com' ||
	//preg_match('@^apache\d+@', @$_SERVER['SERVER_NAME']) ||
	$isDevServer)
{
	$portStr = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '');
	$wgServer = 'http://' . $_SERVER['SERVER_NAME'] . $portStr;
	if ($isDevServer) $wgCookieDomain = 'wikidiy.com';
} else {
	$wgServer = 'http://www.wikihow.com';
}

// INTL Uncomment the appropriate language
#$wgLanguageCode = 'de';
#$wgLanguageCode = 'es';
#$wgLanguageCode = 'pt';


if ($wgLanguageCode != 'de' && $wgLanguageCode != 'es' && $wgLanguageCode != 'pt') {
	die ('language not set');
}

if ($wgLanguageCode == 'de') {
	$wgServer = 'http://de.intl.wikihow.com';
	$wgProdServer = 'http://de.wikihow.com/';
} elseif ($wgLanguageCode == 'pt') {
	$wgServer = 'http://pt.intl.wikihow.com';
	$wgProdServer = 'http://pt.wikihow.com/';
} elseif ($wgLanguageCode == 'es') {
	$wgServer = 'http://es.intl.wikihow.com';
	$wgProdServer = 'http://es.wikihow.com/';
}

$wgLogo = '/skins/WikiHow/wikiHow.gif';

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
$wgScriptPath = '';
$wgArticlePath = "$wgScriptPath/$1";

## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL

$wgEnableEmail = true;
$wgEnableUserEmail = true;

$wgEmergencyContact = 'wiki@wikihow.com';
$wgPasswordSender = 'wiki@wikihow.com';

## For a detailed description of the following switches see
## http://meta.wikimedia.org/Enotif and http://meta.wikimedia.org/Eauthent
## There are many more options for fine tuning available see
## /includes/DefaultSettings.php
## UPO means: this is also a user preference option
$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = true;
$wgEmailAuthentication = true;

if ($wgLanguageCode == 'de') {
	$dbName = 'wikidb_de';
} elseif ($wgLanguageCode == 'es') {
	$dbName = 'wikidb_es';
} elseif ($wgLanguageCode == 'pt') {
	$dbName = 'wikidb_pt';
}
else {
	die("no database");
}

$wgDBservers = array(
	array(
		'host'     => WH_DATABASE_MASTER,
		'dbname'   => $dbName, 
		'user'     => WH_DATABASE_USER,
		'password' => WH_DATABASE_PASSWORD,
		'load'     => 1
	),
/*
	array(
		'host'     => WH_DATABASE_SLAVE1,
		'dbname'   => WH_DATABASE_NAME,
		'user'     => WH_DATABASE_USER,
		'password' => WH_DATABASE_PASSWORD,
		'load'     => 1
	),
*/
);

# Schemas for Postgres
$wgDBmwschema = 'mediawiki';
$wgDBts2schema = 'public';

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType = CACHE_MEMCACHED;
define('WH_MEMCACHED_SERVER_INTL', '127.0.0.1:11212');
$wgMemCachedServers = array(WH_MEMCACHED_SERVER_INTL);

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX = false;

$wgLocalInterwiki = $wgSitename;

$wgProxyKey = WH_PROXY_KEY;

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
$wgDefaultSkin = 'wikihowskin';

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
#$wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ''; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = '';
$wgRightsText = '';
$wgRightsIcon = '';
#$wgRightsCode = ''; # Not yet used

$wgDiff3 = '/usr/bin/diff3';

# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$configdate = gmdate('YmdHis', @filemtime(__FILE__));
$wgCacheEpoch = max($wgCacheEpoch, $configdate);
#$wgReadOnly = "<br/><br/><b><font color=red size='+1'>We are currently doing some maintenance, should be back up and running by 12am EST, sorry for the inconvenience.</font>";


$wgHooks = array();

$wgUseSquid = true;
$wgSquidServers = array(WH_SQUID1);
if (defined('WH_SQUID2')) $wgSquidServers[] = WH_SQUID2;
$wgSquidMaxage = 86400; # 24 hours

# Import wikiHow Extensions
require_once("$IP/imports.php");

$wgOpenIDServerStoreType = 'memcached';
$wgOpenIDConsumerStoreType = 'memcached';

# This needs to be redesigned
# POSSIBLY REMOVING THIS

$wgBlockOpenProxies = true;
$wgProxyScriptPath = "$IP/includes/proxy_check.php";
$wgProxyDefaultBlockLength = '3 month';

# TODO: move this to WikiHowHooks.php
$wgHooks['ArticleSaveComplete'][] = array('wfArticleSaveComplete', $data);
$wgHooks['TitleMoveComplete'][] = array('wfTitleMoveComplete', $data);

$wgAntiLockFlags = ALF_NO_LINK_LOCK | ALF_NO_BLOCK_LOCK;
$wgJobRunRate = 0; # see php maintenance/runJobs.php

$wgSessionsInMemcached = true;

#$wgExternalDiffEngine = 'wikidiff2';
$wgRateLimits['mailpassword'] = array('ip' => array(3, 3600));

$wgForumLink = 'http://www.wikihow.com/forum/';
if ($wgLanguageCode == 'de') {
	$wgForumLink = 'http://groups.google.com/group/deutsches-wikihow';
} elseif ($wgLanguageCode == 'es') {
	$wgForumLink = 'http://groups.google.com/group/wikihow-en-espanol';
}

$wgCompressRevisions  = true;
$wgAllowExternalImages = false;

$wgShowEditSectionLink = true;

$wgSharedDB = WH_DATABASE_NAME_SHARED;

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageResize = true;

$wgShowSQLErrors = true;

# DEBUGGING
#$wgDebugLogFile = '/tmp/wiki112-debug.log';
#$wgProfileLimit = 2.0;
#$wgProfileCallTree = true;

$wgShowExceptionDetails = true;
$wgUseCategoryBrowser = true;

# Tidy
$wgUseTidy = true;
$wgAlwaysUseTidy = false;
$wgTidyOpts = '--show-body-only yes';
$wgTidyBin = '/usr/local/bin/tidy';
#$wgTidyConf = $IP.'/extensions/tidy/tidy.conf';

## See list of skins and their symbolic names in languages/Language.php
$wgExternalDiffEngine = 'wikidiff2';
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = '/usr/local/bin/convert';
$wgUploadSizeWarning = 10485760; # 10mb

$wgLocaltimezone = 'GMT';
$wgRSSOffsetHours = 8;

#$wgAllowCopyUploads = true;
$wgEnableAPI = true;
$wgGoogleAPIKey = WH_GOOGLE_API_KEY;
$wgAllowDisplayTitle = true;

$wgMinimalPasswordLength = 4;
$wgUseLucene = true;
$wgFileExtensions = array('png', 'gif', 'jpg', 'jpeg', 'pdf');
$wgSVGConverter = 'rsvg';

# Google Mini
$wgUseGoogleMini = true;
$wgGoogleMiniHost = array(
	array(1, 'http://' . WH_GOOGLE_MINI_HOST),
);
$wgCacheGoogleMiniResults = true;

$wgGroupPermissions['user']['patrol'] = true;
$wgGroupPermissions['user']['move'] = false;
$wgGroupPermissions['user']['rollback'] = true;
$wgGroupPermissions['sysop']['move'] = true;

$wgCaptchaTriggers['addurl'] = true;
$wgGroupPermissions['user']['skipcaptcha'] = true;

$wgShowHostnames = true;

define('KALTURA_NAMESPACE_STRING', 'Kaltura');
define('KALTURA_NAMESPACE_ID', 320);
define('KALTURA_DISCUSSION_NAMESPACE_ID', 321);
$wgExtraNamespaces[KALTURA_NAMESPACE_ID] = KALTURA_NAMESPACE_STRING ;
$wgExtraNamespaces[KALTURA_NAMESPACE_ID + 1] = KALTURA_NAMESPACE_STRING . '_Talk';

$wgDraftsLifeSpan = 365;
$egDraftsLifeSpan = $wgDraftsLifeSpan; # tmp

$wgFavicon = 'http://pad1.whstatic.com/favicon.ico';
$wgUseAjax = true;
$wgRCMaxAge = 7776000; # 90 days

# We needed to raise this limit (default MediaWiki limit is about 100k) because
# it was cause the ImageMagick convert to fail on some hosts (ulimit is
# applied to the convert command).
$wgMaxShellFileSize = 1*1024*1024; # 1 mb

$wgWikiHowSections = array('ingredients', 'steps', 'video', 'tips', 'warnings', 'relatedwikihows', 'thingsyoullneed', 'sources');

# Facebook connect settings
$wgFBConnectAPIkey = WH_FACEBOOK_CONNECT_API_KEY;
$wgFBConnectSecret = WH_FACEBOOK_CONNECT_SECRET;

# Legacy for old article requests
define('NS_ARTICLE_REQUEST', 16);
define('NS_ARTICLE_REQUEST_TALK', 17);
$wgExtraNamespaces[NS_ARTICLE_REQUEST] = 'Request';
$wgExtraNamespaces[NS_ARTICLE_REQUEST_TALK] = 'Request_talk';

$wgGoogleAjaxKey = WH_GOOGLE_AJAX_SEARCH_API_KEY;
$wgGoogleAjaxSig = WH_GOOGLE_AJAX_SEARCH_SIG;

$wgIgnoreNamespacesForEditCount = array(2, 3, 18);

$wgEnableLateLoadingAds = @$_GET['pl'] == 'true';

$wgCapitalLinks = false;

$wgForeignFileRepos[] = array(
    'class' => 'ForeignDBRepo',
    'name' => 'otherwiki',
    'url' => "http://www.wikihow.com/images",
    'directory' => '/var/www/images_en',
    'hashLevels' => 2, // This must be the same for the other family member
    'dbType' => $wgDBtype,
    'dbServer' => WH_DATABASE_MASTER,
    'dbUser' => WH_DATABASE_USER,
    'dbPassword' => WH_DATABASE_PASSWORD,
    'dbFlags' => DBO_DEFAULT,
    'dbName' => 'wikidb_112',
    'hasSharedCache' => false,
    'descBaseUrl' => 'http://www.wikihow.com/Image:',
    'fetchDescription' => false
);

$wgDBname = $wgDBservers[0]['dbname']; 

date_default_timezone_set("UTC");

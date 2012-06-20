<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WikiText Downloader',
	'author' => 'Jordan Small',
	'description' => 'Download the wikitext of an article given an article id',
);

$wgSpecialPages['WikiTextDownloader'] = 'WikiTextDownloader';
$wgAutoloadClasses['WikiTextDownloader'] = dirname(__FILE__) . '/WikiTextDownloader.body.php';
$wgExtensionMessagesFiles['WikiTextDownloader'] = dirname(__FILE__) . '/WikiTextDownloader.i18n.php';

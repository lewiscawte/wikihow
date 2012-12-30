<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Video',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Provides a way to search and \'import\' YouTube videos into the wiki',
);

$wgSpecialPages['Video'] = 'Video';
$wgSpecialPages['VideoTest'] = 'VideoTest';
$wgSpecialPages['Flagvideo'] = 'Flagvideo';
$wgSpecialPages['ManageFlaggedVideos'] = 'ManageFlaggedVideos';
$wgSpecialPages['ManageVideo'] = 'ManageVideo';

# Internationalisation file
$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Video'] = $dir . 'Video.i18n.php';

$wgAutoloadClasses['Video'] = $dir . 'Video.body.php';
$wgAutoloadClasses['RelatedVideoApi'] = $dir . 'Video.body.php';
$wgAutoloadClasses['FiveMinApi'] = $dir . 'Video.body.php';
$wgAutoloadClasses['FiveMinApiVideo'] = $dir . 'Video.body.php';
$wgAutoloadClasses['YoutubeApi'] = $dir . 'Video.body.php';
$wgAutoloadClasses['HowcastApi'] = $dir . 'Video.body.php';
$wgAutoloadClasses['WonderhowtoApi'] = $dir . 'Video.Wonderhowto.class.php';
$wgAutoloadClasses['WonderhowtoVideo'] = $dir . 'Video.Wonderhowto.class.php';
$wgAutoloadClasses['VideoTest'] = $dir . 'Video.Wonderhowto.class.php';
$wgAutoloadClasses['RelatedVideos'] = $dir . 'Video.body.php';
$wgAutoloadClasses['Flagvideo'] = $dir . 'Video.body.php';
$wgAutoloadClasses['ManageVideo'] = $dir . 'ManageVideo.body.php';
$wgAutoloadClasses['ManageFlaggedVideos'] = $dir . 'Video.body.php';

$wgFiveMinCategoryMap= array(
	"Arts and Entertaiment"			=>	"1,41,10",
	"Cars & Other Vehicles"			=> "18",
	"Computers and Electronics"		=> "16,223",
	"Education and Communications"	=> "11,13,193",
	"Family Life"					=> "11,13,166,189",
	"Finance and Business"			=> "2",
	"Food and Entertaining"			=> "6,166",
	"Health"						=> "8,107",
	"Hobbies and Crafts"			=> "1,219,82,83,190,112",
	"Home and Garden"				=> "9,19",
	"Personal Care and Style"		=> "4,179,161,215,213,156,22",
	"Pets and Animals"				=> "12",
	"Philosophy and Religion"		=> "14",
	"Relationships"					=> "13",
	"Sports and Fitness"			=> "3,5,15,120",
	"Travel"						=> "17",
	"Work World"					=> "2,13",
	"Youth"							=> "11,13,205,44,166",
	);

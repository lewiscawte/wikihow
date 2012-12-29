<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Generatefeed',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates the RSS feed for the featured articles', 
);


$wgSpecialPages['Generatefeed'] = 'Generatefeed'; 
$wgAutoloadClasses['Generatefeed'] = dirname( __FILE__ ) . '/SpecialGeneratefeed.body.php';

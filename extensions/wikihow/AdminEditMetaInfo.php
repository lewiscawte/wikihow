<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminEditMetaInfo',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to hand-edit meta descriptions of articles, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminEditMetaInfo'] = 'AdminEditMetaInfo';
$wgAutoloadClasses['AdminEditMetaInfo'] = dirname( __FILE__ ) . '/AdminEditMetaInfo.body.php';


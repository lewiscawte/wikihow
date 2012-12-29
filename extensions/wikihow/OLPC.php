<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}
$wgOLPCCategoryMap = null;
$wgOLPCUrls = null;
$wgExtensionCredits['other'][] = array(
	'name' => 'OLPC',
	'author' => 'Travis Derouin',
	'description' => 'Provides a means of capturing OLPC snapshots.',
);


$wgSpecialPages['OLPC'] = 'OLPC';
$wgAutoloadClasses['OLPC'] = dirname( __FILE__ ) . '/OLPC.body.php';


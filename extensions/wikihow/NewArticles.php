<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'NewArticles',
    'author' => 'Bebeth Steudel',
    'description' => 'Tool to show new articles',
);

$wgSpecialPages['NewArticles'] = 'NewArticles';
$wgAutoloadClasses['NewArticles'] = dirname( __FILE__ ) . '/NewArticles.body.php';

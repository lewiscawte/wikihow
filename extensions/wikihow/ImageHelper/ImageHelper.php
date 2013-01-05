<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ImageHelper',
    'author' => 'Bebeth Steudel',
    'description' => 'New functionality for the Image pages',
);

$wgSpecialPages['ImageHelper'] = 'IheartwikiHow';
$wgAutoloadClasses['ImageHelper'] = dirname( __FILE__ ) . '/ImageHelper.body.php';
$wgExtensionMessagesFiles['ImageHelper'] = dirname( __FILE__ ) . "/ImageHelper.i18n.php";

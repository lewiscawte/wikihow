<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'LoginReminder',
    'author' => 'Bebeth Steudel',
    'description' => 'Tool to retrieve username/password',
);

$wgSpecialPages['LoginReminder'] = 'LoginReminder';
$wgAutoloadClasses['LoginReminder'] = dirname( __FILE__ ) . '/LoginReminder.body.php';

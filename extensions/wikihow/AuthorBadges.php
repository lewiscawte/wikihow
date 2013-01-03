<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'AuthorBadges',
    'author' => 'Bebeth Steudel',
    'description' => 'Page which shows the current Author Badges available',
);
$wgExtensionMessagesFiles['AuthorBadges'] = dirname(__FILE__) . '/AuthorBadges.i18n.php';

$wgSpecialPages['AuthorBadges'] = 'AuthorBadges';
$wgAutoloadClasses['AuthorBadges'] = dirname( __FILE__ ) . '/AuthorBadges.body.php';

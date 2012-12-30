<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QuickNoteEdit',
    'author' => 'Vu <vu@wikihow.com>',
    'description' => 'quick popups for notes and edit',
);


$wgSpecialPages['QuickNoteEdit'] = 'QuickNoteEdit'; 
$wgAutoloadClasses['QuickNoteEdit'] = dirname( __FILE__ ) . '/QuickNoteEdit.body.php';

<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EditFinder',
	'author' => 'Scott Cushman',
	'description' => 'Tool for experienced users to edit articles that need it.',
);

$wgSpecialPages['EditFinder'] = 'EditFinder';
$wgAutoloadClasses['EditFinder'] = dirname( __FILE__ ) . '/EditFinder.body.php';
$wgExtensionMessagesFiles['EditFinder'] = dirname(__FILE__) . '/EditFinder.i18n.php';

$wgLogTypes[]               = 'editfinder';
$wgLogNames['editfinder']   = 'editfinder';
$wgLogHeaders['editfinder'] = 'editfindertext';

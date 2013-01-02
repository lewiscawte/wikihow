<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
require_once("$IP/extensions/wikihow/WikiHow_i18n.class.php");
require_once("$IP/includes/EasyTemplate.php");

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
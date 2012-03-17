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

$wgLogTypes[] = 'ef_format';
$wgLogNames['ef_format'] = 'editfinder_format';
$wgLogHeaders['ef_format'] = 'editfindertext_format';

$wgLogTypes[] = 'ef_stub';
$wgLogNames['ef_stub'] = 'editfinder_stub';
$wgLogHeaders['ef_stub'] = 'editfindertext_stub';

$wgLogTypes[] = 'ef_topic';
$wgLogNames['ef_topic'] = 'editfinder_topic';
$wgLogHeaders['ef_topic'] = 'editfindertext_topic';

<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Starter Tool',
	'author' => 'Scott Cushman',
	'description' => 'Intro tool for new users.',
);

require_once("$IP/extensions/wikihow/WikiHow_i18n.class.php");
require_once("$IP/includes/EasyTemplate.php");

$wgSpecialPages['StarterTool'] = 'StarterTool';
$wgAutoloadClasses['StarterTool'] = dirname( __FILE__ ) . '/StarterTool.body.php';
$wgExtensionMessagesFiles['StarterTool'] = dirname(__FILE__) . '/StarterTool.i18n.php';
	
$wgLogTypes[]            = 'starter';
$wgLogNames['starter']   = 'starter';
$wgLogHeaders['starter'] = 'startertext';

$wgStarterPages = array('wikiHow:StarterTool001','wikiHow:StarterTool002','wikiHow:StarterTool003',
						'wikiHow:StarterTool004','wikiHow:StarterTool005','wikiHow:StarterTool006',
						'wikiHow:StarterTool007','wikiHow:StarterTool008','wikiHow:StarterTool009',
						'wikiHow:StarterTool010','wikiHow:StarterTool011','wikiHow:StarterTool012');
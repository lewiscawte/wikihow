<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsUp'] = 'ThumbsUp';
$wgAutoloadClasses['ThumbsUp'] = dirname( __FILE__ ) . '/ThumbsUp.body.php';
$wgExtensionMessagesFiles['ThumbsUp'] = dirname(__FILE__) . '/ThumbsUp.i18n.php';


$wgLogTypes[]             = 'thumbsup';
$wgLogNames['thumbsup']   = 'thumbslogpage';
$wgLogHeaders['thumbsup'] = 'thumbspagetext';

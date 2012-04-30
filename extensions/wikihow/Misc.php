<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionMessagesFiles['Misc'] = dirname(__FILE__) . '/Misc.i18n.php';
$wgAutoloadClasses['Misc'] = dirname(__FILE__) . '/Misc.body.php';


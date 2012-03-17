<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Article Widgets',
	'author' => 'Scott Cushman',
	'description' => 'Extension for adding widgets into articles',
);

$wgSpecialPages['ArticleWidgets'] = 'ArticleWidgets';
$wgAutoloadClasses['ArticleWidgets'] = dirname( __FILE__ ) . '/ArticleWidgets.body.php';


$wgHooks["ParserGetArticleWidget"][] = array("wfGrabWidget"); 


function wfGrabWidget(&$parser, &$nt, &$ret) {
	$nt = strtoupper(preg_replace('@:@','',$nt));
	$ret = ArticleWidgets::GrabWidget($nt);
	return $ret;
}


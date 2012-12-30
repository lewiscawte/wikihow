<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QC',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Provides a way of reviewing a set of edits separate from RC Patrol, such as removal of stub templates.',
);

$wgSpecialPages['QC'] = 'QC';
$wgAutoloadClasses['QC'] = $dir . 'QC.body.php';


$wgQCRules = array(
	"QCRuleTemplateChange" => "ArticleSaveComplete"
);

foreach ($wgQCRules as $rule=>$hook) {
	$wgAutoloadClasses[$rule] = $dir . 'QC.body.php';
}


# Internationalisation file
$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['QC'] = $dir . 'QC.i18n.php';

$wgChangedTemplatesToQC = array("stub", "format", "cleanup", "copyedit");

$wgHooks["ArticleSaveComplete"][] = "wfCheckQC";

function wfCheckQC(&$article, &$user, $text, $summary, $minoredit, &$watchthis, $sectionanchor, &$flags, $revision) {
	global $wgChangedTemplatesToQC;
	foreach ($wgChangedTemplatesToQC as $t) {
		wfDebug("QC: About to process template change $t\n");
		$l = new QCRuleTemplateChange($t, $revision, $article); 
		$l->process();	
	}
	return true;
}

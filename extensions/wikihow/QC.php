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
$wgTemplateChangedVotesRequired = array(
	"removed" => array("yes"=>1, "no"=>2), 
	"added" => array("yes"=>1, "no"=>2)
);

$wgQCIntroImageVotesRequired = array ("yes"=>1, "no"=>2); 
$wgQCVideoChangeVotesRequired = array ("yes"=>1, "no"=>2); 


$wgHooks["ArticleSaveComplete"][] = "wfCheckQC";

function wfCheckQC(&$article, &$user, $text, $summary, $minoredit, &$watchthis, $sectionanchor, &$flags, $revision) {
	global $wgChangedTemplatesToQC;
	
	// do the templates
	foreach ($wgChangedTemplatesToQC as $t) {
		wfDebug("QC: About to process template change $t\n");
		$l = new QCRuleTemplateChange($t, $revision, $article); 
		$l->process();	
	}

	// check for intro image change
	$l = new QCRuleIntroImage($revision, $article); 
	$l->process();	

	// check for video changes 
	$l = new QCRuleVideoChange($revision, $article); 
	$l->process();	

	return true;
}

$wgQCRulesToCheck = array("ChangedTemplate/Stub", "ChangedTemplate/Format", "ChangedTemplate/Cleanup", "ChangedTemplate/Copyedit", "ChangedIntroImage", "ChangedVideo"); 


$wgAvailableRights[] = 'qc';
$wgGroupPermissions['qccheckers']['qc'] = true;
$wgGroupPermissions['staff' ]['qc']   = true;

$wgHooks['ArticleDelete'][] = array("wfClearQCOnDelete"); 

function wfClearQCOnDelete($article, $user, $reason) {
	try {	
		$dbw = wfGetDB(DB_MASTER); 
		$id = $article->getTitle()->getArticleID();
		$dbw->delete("qc", array("qc_page"=>$id));
	} catch (Exception $e) {

	}
	return true;
}


// Log page definitions
$wgLogTypes[]              = 'qc';
$wgLogNames['qc']          = 'qclogpage';
$wgLogHeaders['qc']        = 'qclogtext';


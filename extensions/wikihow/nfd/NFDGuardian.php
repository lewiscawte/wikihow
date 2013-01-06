<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'NFDGuardian',
    'author' => 'Bebeth <bebeth.com>',
    'description' => 'Provides a way of reviewing NFD templates',
);

$wgSpecialPages['NFDGuardian'] = 'NFDGuardian';
$wgSpecialPages['NFDAdvanced'] = 'NFDAdvanced';

$wgAutoloadClasses['NFDGuardian'] = dirname(__FILE__) . '/NFDGuardian.body.php';
$wgAutoloadClasses["NFDRuleTemplateChange"] = dirname(__FILE__) . '/NFDGuardian.body.php';
$wgAutoloadClasses["NFDAdvanced"] = dirname(__FILE__) . '/NFDGuardian.body.php';
$wgExtensionMessagesFiles['NFDGuardian'] = dirname(__FILE__) .'/NFDGuardian.i18n.php';

$wgLogTypes[] = 'nfd';
$wgLogNames['nfd'] = 'nfd';
$wgLogHeaders['nfd'] = 'nfd_log';

$wgNfdVotesRequired = array("delete"=>3, "keep"=>3, "admin_delete" => 1, "admin_keep" => 0, "advanced_delete" => 6);
//advanced_delete is number required to delete once a keep vote has been logged

$wgHooks["ArticleSaveComplete"][] = "wfCheckNFD";
$wgHooks["ArticleDelete"][] = "wfRemoveNFD";

function wfCheckNFD(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	// if an article becomes a redirect, vanquish all previous nfd entries
	if($article->getTitle()->getNamespace() != NS_MAIN)
		return true;
	
	if (preg_match("@^#REDIRECT@", $text)) {
		NFDRuleTemplateChange::markPreviousAsPatrolled($article->getID());
		return true;
	}

	// check for bots
	$bots = User::getBotIDs();
	if (in_array($user->getID(),$bots)) {
		return true;
	}

	// do the templates
	wfDebug("NFD: Looking for NFD templates\n");
	$l = new NFDRuleTemplateChange($revision, $article);
	$l->process();

	return true;
}

function wfRemoveNFD( Article &$article, User &$user, $reason) {
	NFDRuleTemplateChange::markPreviousAsPatrolled($article->getID());
	return true;
}

/************************
--
-- Table structure for table `nfd`
--

CREATE TABLE IF NOT EXISTS `nfd` (
  `nfd_id` int(8) unsigned NOT NULL auto_increment,
  `nfd_action` varchar(16) NOT NULL default '',
  `nfd_template` varchar(100) NOT NULL default '',
  `nfd_reason` varchar(14) NOT NULL default '',
  `nfd_page` int(8) unsigned NOT NULL default '0',
  `nfd_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nfd_fe_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nfd_user` int(5) unsigned NOT NULL default '0',
  `nfd_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `nfd_rev_id` int(8) unsigned NOT NULL default '0',
  `nfd_old_rev_id` int(8) unsigned NOT NULL default '0',
  `nfd_patrolled` tinyint(3) unsigned default '0',
  `nfd_delete_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_admin_delete_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_keep_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_admin_keep_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_checkout_time` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nfd_checkout_user` int(5) unsigned NOT NULL default '0',
  `nfd_extra` varchar(32) default '',
  PRIMARY KEY  (`nfd_id`),
  KEY `nfd_page` (`nfd_page`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=122 ;

--
-- Table structure for table `nfd_vote`
--

CREATE TABLE IF NOT EXISTS `nfd_vote` (
  `nfdv_nfdid` int(8) unsigned NOT NULL,
  `nfdv_user` int(5) unsigned NOT NULL,
  `nfdv_vote` tinyint(3) unsigned NOT NULL default '0',
  `nfdv_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `nfdv_nfdid` (`nfdv_nfdid`),
  KEY `nfdv_user` (`nfdv_user`,`nfdv_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `nfd` DROP `nfd_extra`;
ALTER TABLE `nfd` ADD `nfd_status` TINYINT( 3 ) NOT NULL DEFAULT '0' AFTER `nfd_patrolled`

 *********************************/

-- MySQL dump 10.11
--
-- Host: localhost    Database: wikidb_112
-- ------------------------------------------------------
-- Server version	5.0.77-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abuse_filter`
--

DROP TABLE IF EXISTS `abuse_filter`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `abuse_filter` (
  `af_id` bigint(20) unsigned NOT NULL auto_increment,
  `af_pattern` blob NOT NULL,
  `af_user` bigint(20) unsigned NOT NULL,
  `af_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `af_timestamp` binary(14) NOT NULL,
  `af_enabled` tinyint(1) NOT NULL default '1',
  `af_comments` blob,
  `af_public_comments` tinyblob,
  `af_hidden` tinyint(1) NOT NULL default '0',
  `af_hit_count` bigint(20) NOT NULL default '0',
  `af_throttled` tinyint(1) NOT NULL default '0',
  `af_deleted` tinyint(1) NOT NULL default '0',
  `af_actions` varchar(255) NOT NULL default '',
  `af_global` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`af_id`),
  KEY `af_user` (`af_user`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `abuse_filter_action`
--

DROP TABLE IF EXISTS `abuse_filter_action`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `abuse_filter_action` (
  `afa_filter` bigint(20) unsigned NOT NULL,
  `afa_consequence` varchar(255) NOT NULL,
  `afa_parameters` tinyblob NOT NULL,
  PRIMARY KEY  (`afa_filter`,`afa_consequence`),
  KEY `afa_consequence` (`afa_consequence`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `abuse_filter_history`
--

DROP TABLE IF EXISTS `abuse_filter_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `abuse_filter_history` (
  `afh_id` bigint(20) unsigned NOT NULL auto_increment,
  `afh_filter` bigint(20) unsigned NOT NULL,
  `afh_user` bigint(20) unsigned NOT NULL,
  `afh_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `afh_timestamp` binary(14) NOT NULL,
  `afh_pattern` blob NOT NULL,
  `afh_comments` blob NOT NULL,
  `afh_flags` tinyblob NOT NULL,
  `afh_public_comments` tinyblob,
  `afh_actions` blob,
  `afh_deleted` tinyint(1) NOT NULL default '0',
  `afh_changed_fields` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`afh_id`),
  KEY `afh_filter` (`afh_filter`),
  KEY `afh_user` (`afh_user`),
  KEY `afh_user_text` (`afh_user_text`),
  KEY `afh_timestamp` (`afh_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `abuse_filter_log`
--

DROP TABLE IF EXISTS `abuse_filter_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `abuse_filter_log` (
  `afl_id` bigint(20) unsigned NOT NULL auto_increment,
  `afl_filter` varchar(64) character set latin1 collate latin1_bin NOT NULL,
  `afl_user` bigint(20) unsigned NOT NULL,
  `afl_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `afl_ip` varchar(255) NOT NULL,
  `afl_action` varbinary(255) NOT NULL,
  `afl_actions` varbinary(255) NOT NULL,
  `afl_var_dump` blob NOT NULL,
  `afl_timestamp` binary(14) NOT NULL,
  `afl_namespace` tinyint(4) NOT NULL,
  `afl_title` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `afl_wiki` varchar(64) character set latin1 collate latin1_bin default NULL,
  `afl_deleted` tinyint(1) default NULL,
  `afl_patrolled_by` int(10) unsigned default NULL,
  PRIMARY KEY  (`afl_id`),
  KEY `afl_filter` (`afl_filter`),
  KEY `afl_user` (`afl_user`),
  KEY `afl_timestamp` (`afl_timestamp`),
  KEY `afl_namespace` (`afl_namespace`,`afl_title`),
  KEY `afl_ip` (`afl_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `archive`
--

DROP TABLE IF EXISTS `archive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `archive` (
  `ar_namespace` int(11) NOT NULL default '0',
  `ar_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ar_text` mediumtext NOT NULL,
  `ar_comment` tinyblob NOT NULL,
  `ar_user` int(5) unsigned NOT NULL default '0',
  `ar_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ar_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `ar_minor_edit` tinyint(1) NOT NULL default '0',
  `ar_flags` tinyblob NOT NULL,
  `ar_rev_id` int(8) unsigned default NULL,
  `ar_text_id` int(8) unsigned default NULL,
  `ar_deleted` tinyint(3) unsigned NOT NULL default '0',
  `ar_len` int(10) unsigned default NULL,
  `ar_page_id` int(10) unsigned default NULL,
  KEY `name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `usertext_timestamp` (`ar_user_text`,`ar_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `article_meta_info`
--

DROP TABLE IF EXISTS `article_meta_info`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `article_meta_info` (
  `ami_id` int(10) unsigned NOT NULL,
  `ami_namespace` int(10) unsigned NOT NULL default '0',
  `ami_title` varchar(255) NOT NULL default '',
  `ami_updated` varchar(14) NOT NULL default '',
  `ami_desc_style` tinyint(1) NOT NULL default '1',
  `ami_desc` varchar(255) NOT NULL default '',
  `ami_facebook_desc` varchar(255) NOT NULL default '',
  `ami_img` varchar(255) default NULL,
  PRIMARY KEY  (`ami_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `avatar`
--

DROP TABLE IF EXISTS `avatar`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `avatar` (
  `av_user` mediumint(8) unsigned NOT NULL default '0',
  `av_image` varchar(255) NOT NULL default '',
  `av_patrol` tinyint(2) NOT NULL default '0',
  `av_rejectReason` varchar(255) NOT NULL default '',
  `av_patrolledBy` mediumint(8) unsigned NOT NULL default '0',
  `av_patrolledDate` varchar(14) character set latin1 collate latin1_bin default NULL,
  `av_dateAdded` varchar(14) character set latin1 collate latin1_bin default NULL,
  PRIMARY KEY  (`av_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `badge_views`
--

DROP TABLE IF EXISTS `badge_views`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `badge_views` (
  `bv_id` int(10) unsigned NOT NULL auto_increment,
  `bv_referrer` text NOT NULL,
  `bv_size` int(10) unsigned NOT NULL,
  `bv_count` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`bv_id`),
  UNIQUE KEY `bv_referrer` (`bv_referrer`(100))
) ENGINE=InnoDB AUTO_INCREMENT=24702 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `blobs`
--

DROP TABLE IF EXISTS `blobs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `blobs` (
  `blob_index` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `blob_data` longblob NOT NULL,
  UNIQUE KEY `blob_index` (`blob_index`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `brokenlinks`
--

DROP TABLE IF EXISTS `brokenlinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `brokenlinks` (
  `bl_from` int(8) unsigned NOT NULL default '0',
  `bl_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `bl_from` (`bl_from`,`bl_to`),
  KEY `bl_to` (`bl_to`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `bugreport`
--

DROP TABLE IF EXISTS `bugreport`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `bugreport` (
  `br_id` int(10) unsigned NOT NULL auto_increment,
  `br_user` int(5) unsigned NOT NULL default '0',
  `br_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `br_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `br_summary` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `br_details` text NOT NULL,
  `br_history` text NOT NULL,
  PRIMARY KEY  (`br_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cat_views`
--

DROP TABLE IF EXISTS `cat_views`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cat_views` (
  `cv_user` int(5) unsigned NOT NULL default '0',
  `cv_cat` varchar(64) default '',
  `cv_views` int(10) unsigned default '0',
  UNIQUE KEY `user_cat` (`cv_user`,`cv_cat`),
  KEY `user` (`cv_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `category_interests`
--

DROP TABLE IF EXISTS `category_interests`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `category_interests` (
  `ci_user_id` mediumint(8) unsigned NOT NULL default '0',
  `ci_category` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ci_user_id`,`ci_category`),
  KEY `ci_category` (`ci_category`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `categorylinks`
--

DROP TABLE IF EXISTS `categorylinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `categorylinks` (
  `cl_from` int(8) unsigned NOT NULL default '0',
  `cl_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cl_sortkey` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cl_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `cl_from` (`cl_from`,`cl_to`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_sortkey` (`cl_to`,`cl_sortkey`,`cl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `categorylinkstop`
--

DROP TABLE IF EXISTS `categorylinkstop`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `categorylinkstop` (
  `cl_from` int(8) unsigned NOT NULL default '0',
  `cl_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cl_sortkey` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cl_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `cl_from` (`cl_from`,`cl_to`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_sortkey` (`cl_to`,`cl_sortkey`,`cl_from`),
  KEY `cl_from_1` (`cl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `checkjs`
--

DROP TABLE IF EXISTS `checkjs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `checkjs` (
  `hasjs` tinyint(3) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `community_dashboard_opts`
--

DROP TABLE IF EXISTS `community_dashboard_opts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `community_dashboard_opts` (
  `cdo_priorities_json` text,
  `cdo_thresholds_json` text,
  `cdo_baselines_json` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `community_dashboard_users`
--

DROP TABLE IF EXISTS `community_dashboard_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `community_dashboard_users` (
  `cdu_userid` int(10) unsigned NOT NULL,
  `cdu_prefs_json` text NOT NULL,
  `cdu_completion_json` text NOT NULL,
  PRIMARY KEY  (`cdu_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `config_storage`
--

DROP TABLE IF EXISTS `config_storage`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `config_storage` (
  `cs_key` varchar(64) NOT NULL,
  `cs_config` longtext NOT NULL,
  PRIMARY KEY  (`cs_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `copyviocheck`
--

DROP TABLE IF EXISTS `copyviocheck`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `copyviocheck` (
  `cv_page` int(8) unsigned NOT NULL,
  `cv_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `cv_checks` int(10) unsigned default '0',
  `cv_copyvio` tinyint(3) unsigned default '0',
  UNIQUE KEY `cv_page` (`cv_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cu_changes`
--

DROP TABLE IF EXISTS `cu_changes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cu_changes` (
  `cuc_id` int(11) NOT NULL auto_increment,
  `cuc_namespace` int(11) NOT NULL default '0',
  `cuc_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cuc_user` int(11) NOT NULL default '0',
  `cuc_user_text` varchar(255) NOT NULL default '',
  `cuc_actiontext` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cuc_comment` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cuc_minor` tinyint(1) NOT NULL default '0',
  `cuc_page_id` int(10) unsigned NOT NULL default '0',
  `cuc_this_oldid` int(10) unsigned NOT NULL default '0',
  `cuc_last_oldid` int(10) unsigned NOT NULL default '0',
  `cuc_type` tinyint(3) unsigned NOT NULL default '0',
  `cuc_timestamp` varchar(14) NOT NULL default '',
  `cuc_ip` varchar(255) default '',
  `cuc_ip_hex` varchar(255) default NULL,
  `cuc_xff` varchar(255) character set latin1 collate latin1_bin default '',
  `cuc_xff_hex` varchar(255) default NULL,
  `cuc_agent` varchar(255) character set latin1 collate latin1_bin default NULL,
  PRIMARY KEY  (`cuc_id`),
  KEY `cuc_ip_hex_time` (`cuc_ip_hex`,`cuc_timestamp`),
  KEY `cuc_user_ip_time` (`cuc_user`,`cuc_ip`,`cuc_timestamp`),
  KEY `cuc_xff_hex_time` (`cuc_xff_hex`,`cuc_timestamp`),
  KEY `cuc_timestamp` (`cuc_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=5993392 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cu_log`
--

DROP TABLE IF EXISTS `cu_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cu_log` (
  `cul_id` int(10) unsigned NOT NULL auto_increment,
  `cul_timestamp` varbinary(14) NOT NULL default '',
  `cul_user` int(10) unsigned NOT NULL default '0',
  `cul_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cul_reason` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `cul_type` varbinary(30) NOT NULL default '',
  `cul_target_id` int(10) unsigned NOT NULL default '0',
  `cul_target_text` blob NOT NULL,
  `cul_target_hex` varbinary(255) NOT NULL default '',
  `cul_range_start` varbinary(255) NOT NULL default '',
  `cul_range_end` varbinary(255) NOT NULL default '',
  PRIMARY KEY  (`cul_id`),
  KEY `cul_timestamp` (`cul_timestamp`),
  KEY `cul_user` (`cul_user`,`cul_timestamp`),
  KEY `cul_type_target` (`cul_type`,`cul_target_id`,`cul_timestamp`),
  KEY `cul_target_hex` (`cul_target_hex`,`cul_timestamp`),
  KEY `cul_range_start` (`cul_range_start`,`cul_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2294 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `daily_edits`
--

DROP TABLE IF EXISTS `daily_edits`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `daily_edits` (
  `de_page_id` int(8) unsigned NOT NULL,
  `de_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`de_page_id`),
  KEY `de_timestamp` (`de_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `docentcategories`
--

DROP TABLE IF EXISTS `docentcategories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `docentcategories` (
  `dc_user` mediumint(8) unsigned NOT NULL default '0',
  `dc_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `dc_user` (`dc_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `docentwarnings`
--

DROP TABLE IF EXISTS `docentwarnings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `docentwarnings` (
  `dw_user` mediumint(8) unsigned NOT NULL default '0',
  `dw_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `drafts`
--

DROP TABLE IF EXISTS `drafts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drafts` (
  `draft_id` int(11) NOT NULL auto_increment,
  `draft_token` int(11) default NULL,
  `draft_user` int(11) NOT NULL default '0',
  `draft_page` int(11) NOT NULL default '0',
  `draft_namespace` int(11) NOT NULL default '0',
  `draft_title` varbinary(255) NOT NULL default '',
  `draft_section` int(11) default NULL,
  `draft_starttime` varbinary(14) default NULL,
  `draft_edittime` varbinary(14) default NULL,
  `draft_savetime` varbinary(14) default NULL,
  `draft_scrolltop` int(11) default NULL,
  `draft_text` mediumblob NOT NULL,
  `draft_summary` tinyblob,
  `draft_minoredit` tinyint(1) default NULL,
  `draft_htmlfive` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`draft_id`),
  KEY `draft_user_savetime` (`draft_user`,`draft_savetime`),
  KEY `draft_user_page_savetime` (`draft_user`,`draft_page`,`draft_namespace`,`draft_title`,`draft_savetime`),
  KEY `draft_savetime` (`draft_savetime`)
) ENGINE=InnoDB AUTO_INCREMENT=234008 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `editfinder`
--

DROP TABLE IF EXISTS `editfinder`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `editfinder` (
  `ef_page` int(8) default NULL,
  `ef_title` varchar(255) default NULL,
  `ef_edittype` varchar(255) default NULL,
  `ef_skip` mediumint(8) default NULL,
  `ef_skip_ts` varchar(14) default NULL,
  `ef_last_viewed` varchar(14) default NULL,
  UNIQUE KEY `idx_ef` (`ef_page`,`ef_edittype`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `editfinder_skip`
--

DROP TABLE IF EXISTS `editfinder_skip`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `editfinder_skip` (
  `efs_page` int(8) default NULL,
  `efs_user` mediumint(8) default NULL,
  `efs_timestamp` varchar(14) default NULL,
  KEY `efs_user` (`efs_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `editor_stats`
--

DROP TABLE IF EXISTS `editor_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `editor_stats` (
  `es_user` int(10) NOT NULL,
  `es_edits` int(11) NOT NULL default '0',
  `es_created` int(11) NOT NULL default '0',
  `es_nab` int(11) NOT NULL default '0',
  `es_patrol` int(11) NOT NULL default '0',
  `es_timestamp` varchar(14) NOT NULL,
  UNIQUE KEY `es_user` (`es_user`),
  KEY `es_timestamp` (`es_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `email_notifications`
--

DROP TABLE IF EXISTS `email_notifications`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `email_notifications` (
  `en_user` mediumint(8) unsigned NOT NULL default '0',
  `en_page` int(8) unsigned NOT NULL default '0',
  `en_watch` tinyint(2) unsigned NOT NULL default '0',
  `en_viewership` int(5) unsigned NOT NULL default '0',
  `en_viewership_email` varchar(14) character set latin1 collate latin1_bin default NULL,
  `en_watch_email` varchar(14) character set latin1 collate latin1_bin default NULL,
  `en_featured_email` varchar(14) character set latin1 collate latin1_bin default NULL,
  `en_share_email` varchar(14) character set latin1 collate latin1_bin default NULL,
  `en_risingstar_email` varchar(14) character set latin1 collate latin1_bin default NULL,
  `en_last_emailsent` varchar(14) character set latin1 collate latin1_bin default NULL,
  PRIMARY KEY  (`en_user`,`en_page`),
  KEY `en_user` (`en_user`),
  KEY `en_page` (`en_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `emailfeed`
--

DROP TABLE IF EXISTS `emailfeed`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `emailfeed` (
  `email` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `event_logger`
--

DROP TABLE IF EXISTS `event_logger`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `event_logger` (
  `el_key` varchar(255) default NULL,
  `el_type` varchar(255) default NULL,
  `el_value` varchar(255) default NULL,
  `el_timestamp` varchar(14) default NULL,
  KEY `el_timestamp` (`el_timestamp`),
  KEY `el_key` (`el_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `externallinks`
--

DROP TABLE IF EXISTS `externallinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `externallinks` (
  `el_from` int(8) unsigned NOT NULL default '0',
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `facebook_sessions`
--

DROP TABLE IF EXISTS `facebook_sessions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `facebook_sessions` (
  `wiki_user` int(10) unsigned NOT NULL default '0',
  `facebook_user` int(10) unsigned NOT NULL default '0',
  `session_key` varchar(255) default NULL,
  `last_update` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `filearchive`
--

DROP TABLE IF EXISTS `filearchive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `filearchive` (
  `fa_id` int(11) NOT NULL auto_increment,
  `fa_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `fa_archive_name` varchar(255) character set latin1 collate latin1_bin default '',
  `fa_storage_group` varchar(16) default NULL,
  `fa_storage_key` varchar(64) character set latin1 collate latin1_bin default '',
  `fa_deleted_user` int(11) default NULL,
  `fa_deleted_timestamp` varchar(14) character set latin1 collate latin1_bin default '',
  `fa_deleted_reason` text,
  `fa_size` int(8) unsigned default '0',
  `fa_width` int(5) default '0',
  `fa_height` int(5) default '0',
  `fa_metadata` mediumblob,
  `fa_bits` int(3) default '0',
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') default NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart') default 'unknown',
  `fa_minor_mime` varchar(32) default 'unknown',
  `fa_description` tinyblob,
  `fa_user` int(5) unsigned default '0',
  `fa_user_text` varchar(255) character set latin1 collate latin1_bin default '',
  `fa_timestamp` varchar(14) character set latin1 collate latin1_bin default '',
  `fa_deleted` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_deleted_user` (`fa_deleted_user`)
) ENGINE=InnoDB AUTO_INCREMENT=32117 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `firstedit`
--

DROP TABLE IF EXISTS `firstedit`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `firstedit` (
  `fe_page` int(10) unsigned NOT NULL default '0',
  `fe_user` int(10) unsigned NOT NULL default '0',
  `fe_user_text` varchar(255) default NULL,
  `fe_timestamp` varchar(14) default NULL,
  PRIMARY KEY  (`fe_page`,`fe_user`),
  KEY `fe_user` (`fe_user`),
  KEY `fe_user_text` (`fe_user_text`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `follow`
--

DROP TABLE IF EXISTS `follow`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `follow` (
  `fo_id` int(8) unsigned NOT NULL auto_increment,
  `fo_user` int(5) unsigned NOT NULL default '0',
  `fo_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `fo_type` varchar(16) default '',
  `fo_target_id` int(8) unsigned NOT NULL,
  `fo_target_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `fo_weight` smallint(5) unsigned NOT NULL default '0',
  `fo_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`fo_id`),
  UNIQUE KEY `user_type_id_name` (`fo_user`,`fo_type`,`fo_target_id`,`fo_target_name`)
) ENGINE=InnoDB AUTO_INCREMENT=365178 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `good_revision`
--

DROP TABLE IF EXISTS `good_revision`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `good_revision` (
  `gr_page` int(8) unsigned NOT NULL,
  `gr_rev` int(8) unsigned NOT NULL,
  `gr_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`gr_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `google_indexed`
--

DROP TABLE IF EXISTS `google_indexed`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `google_indexed` (
  `gi_page` int(8) unsigned NOT NULL,
  `gi_indexed` tinyint(3) unsigned NOT NULL default '0',
  `gi_lastcheck` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `gi_page_created` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `gi_times_checked` int(10) unsigned default '0',
  UNIQUE KEY `gi_page1` (`gi_page`),
  KEY `gi_page` (`gi_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `google_indexed_log`
--

DROP TABLE IF EXISTS `google_indexed_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `google_indexed_log` (
  `gl_page` int(8) unsigned NOT NULL,
  `gl_pos` tinyint(3) unsigned default '0',
  `gl_err` tinyint(3) unsigned default '0',
  `gl_err_str` varchar(255) default NULL,
  `gl_checked` varchar(14) character set latin1 collate latin1_bin NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `google_monitor`
--

DROP TABLE IF EXISTS `google_monitor`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `google_monitor` (
  `gm_page` int(8) unsigned NOT NULL default '0',
  `gm_active` tinyint(1) unsigned NOT NULL default '1',
  UNIQUE KEY `gm_page` (`gm_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `google_monitor_results`
--

DROP TABLE IF EXISTS `google_monitor_results`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `google_monitor_results` (
  `gmr_page` int(8) unsigned NOT NULL default '0',
  `gmr_position` tinyint(3) unsigned NOT NULL default '0',
  `gmr_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `google_spell_suggest_cache`
--

DROP TABLE IF EXISTS `google_spell_suggest_cache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `google_spell_suggest_cache` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `query` varchar(255) NOT NULL default '',
  `suggest` varchar(255) default NULL,
  `hits` int(10) unsigned NOT NULL default '1',
  KEY `sugg_id` (`id`),
  KEY `sugg_query` (`query`)
) ENGINE=InnoDB AUTO_INCREMENT=1520042 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `googlebot`
--

DROP TABLE IF EXISTS `googlebot`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `googlebot` (
  `gb_batch` varchar(8) default NULL,
  `gb_total` int(10) unsigned default '0',
  `gb_404` int(10) unsigned default '0',
  `gb_301` int(10) unsigned default '0',
  `gb_main` int(10) unsigned default '0',
  `gb_bad` int(10) unsigned default '0',
  `gb_user` int(10) unsigned default '0',
  `gb_usertalk` int(10) unsigned default '0',
  `gb_discuss` int(10) unsigned default '0',
  `gb_special` int(10) unsigned default '0',
  `gb_uniquemain` int(10) unsigned default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `image` (
  `img_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `img_size` int(8) unsigned NOT NULL default '0',
  `img_description` tinyblob NOT NULL,
  `img_user` int(5) unsigned NOT NULL default '0',
  `img_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `img_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `img_width` int(5) NOT NULL default '0',
  `img_height` int(5) NOT NULL default '0',
  `img_bits` int(5) NOT NULL default '0',
  `img_metadata` mediumblob NOT NULL,
  `img_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') default NULL,
  `img_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart') NOT NULL default 'unknown',
  `img_minor_mime` varchar(32) NOT NULL default 'unknown',
  `img_sha1` varbinary(32) NOT NULL default '',
  PRIMARY KEY  (`img_name`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_usertext_timestamp` (`img_user_text`,`img_timestamp`),
  KEY `img_sha1` (`img_sha1`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `imageadder`
--

DROP TABLE IF EXISTS `imageadder`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `imageadder` (
  `imageadder_id` int(8) unsigned NOT NULL auto_increment,
  `imageadder_page` mediumint(8) unsigned NOT NULL,
  `imageadder_page_counter` bigint(20) unsigned NOT NULL default '0',
  `imageadder_page_touched` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `imageadder_intro` mediumint(8) unsigned NOT NULL default '0',
  `imageadder_total` mediumint(8) unsigned NOT NULL default '0',
  `imageadder_skip` mediumint(8) unsigned NOT NULL default '0',
  `imageadder_skip_ts` varchar(14) default NULL,
  `imageadder_inuse` tinyint(1) unsigned NOT NULL default '0',
  `imageadder_terms` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `imageadder_last_viewed` datetime NOT NULL default '0000-00-00 00:00:00',
  `imageadder_hasimage` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`imageadder_id`),
  UNIQUE KEY `imageadder_page` (`imageadder_page`),
  KEY `imageadder_last_viewed` (`imageadder_last_viewed`)
) ENGINE=InnoDB AUTO_INCREMENT=177855 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `imageadder_standings`
--

DROP TABLE IF EXISTS `imageadder_standings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `imageadder_standings` (
  `ias_id` mediumint(8) unsigned NOT NULL auto_increment,
  `ias_user` mediumint(8) unsigned NOT NULL,
  `ias_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ias_images` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`ias_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10339 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `imagelinks`
--

DROP TABLE IF EXISTS `imagelinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `imagelinks` (
  `il_from` int(8) unsigned NOT NULL default '0',
  `il_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `il_from` (`il_from`,`il_to`),
  KEY `il_to` (`il_to`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `import_articles`
--

DROP TABLE IF EXISTS `import_articles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `import_articles` (
  `ia_id` int(8) unsigned NOT NULL auto_increment,
  `ia_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ia_text` text,
  `ia_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `ia_published` tinyint(3) unsigned default '0',
  `ia_published_timestamp` varchar(14) default '',
  `ia_publish_err` tinyint(3) unsigned default '0',
  UNIQUE KEY `ia_title` (`ia_title`),
  KEY `ia_id` (`ia_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14446 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `improve_links`
--

DROP TABLE IF EXISTS `improve_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `improve_links` (
  `il_from` int(8) unsigned NOT NULL default '0',
  `il_namespace` int(11) NOT NULL default '0',
  `il_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `il_from` (`il_from`,`il_namespace`,`il_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `interwiki`
--

DROP TABLE IF EXISTS `interwiki`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `interwiki` (
  `iw_prefix` char(32) NOT NULL default '',
  `iw_url` char(127) NOT NULL default '',
  `iw_local` tinyint(1) NOT NULL default '0',
  `iw_trans` tinyint(1) NOT NULL default '0',
  UNIQUE KEY `iw_prefix` (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ipblocks`
--

DROP TABLE IF EXISTS `ipblocks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ipblocks` (
  `ipb_id` int(8) NOT NULL auto_increment,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(8) unsigned NOT NULL default '0',
  `ipb_by` int(8) unsigned NOT NULL default '0',
  `ipb_reason` tinyblob NOT NULL,
  `ipb_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `ipb_auto` tinyint(1) NOT NULL default '0',
  `ipb_anon_only` tinyint(1) NOT NULL default '0',
  `ipb_create_account` tinyint(1) NOT NULL default '1',
  `ipb_expiry` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_enable_autoblock` tinyint(1) NOT NULL default '1',
  `ipb_deleted` tinyint(1) NOT NULL default '0',
  `ipb_block_email` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ipb_id`),
  UNIQUE KEY `ipb_address_unique` (`ipb_address`(255),`ipb_user`,`ipb_auto`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`)
) ENGINE=InnoDB AUTO_INCREMENT=75437 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `job`
--

DROP TABLE IF EXISTS `job`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `job` (
  `job_id` int(9) unsigned NOT NULL auto_increment,
  `job_cmd` varchar(255) NOT NULL default '',
  `job_namespace` int(11) NOT NULL default '0',
  `job_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `job_params` blob NOT NULL,
  PRIMARY KEY  (`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`)
) ENGINE=InnoDB AUTO_INCREMENT=10943647 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `langlinks`
--

DROP TABLE IF EXISTS `langlinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `langlinks` (
  `ll_from` int(8) unsigned NOT NULL default '0',
  `ll_lang` varchar(10) character set latin1 collate latin1_bin NOT NULL default '',
  `ll_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `ll_from` (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `logging`
--

DROP TABLE IF EXISTS `logging`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `logging` (
  `log_type` varchar(10) NOT NULL default '',
  `log_action` varchar(10) NOT NULL default '',
  `log_timestamp` varchar(14) NOT NULL default '19700101000000',
  `log_user` int(10) unsigned NOT NULL default '0',
  `log_namespace` int(11) NOT NULL default '0',
  `log_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `log_comment` varchar(255) NOT NULL default '',
  `log_params` blob NOT NULL,
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_deleted` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`log_id`),
  KEY `type_time` (`log_type`,`log_timestamp`),
  KEY `user_time` (`log_user`,`log_timestamp`),
  KEY `page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `times` (`log_timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=8018007 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mailman_subscribe`
--

DROP TABLE IF EXISTS `mailman_subscribe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mailman_subscribe` (
  `mm_sid` mediumint(8) unsigned NOT NULL auto_increment,
  `mm_user` mediumint(8) unsigned NOT NULL default '0',
  `mm_list` varchar(255) NOT NULL default '',
  `mm_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `mm_done` tinyint(4) default '0',
  UNIQUE KEY `mm_sid` (`mm_sid`)
) ENGINE=InnoDB AUTO_INCREMENT=11663 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mailman_unsubscribe`
--

DROP TABLE IF EXISTS `mailman_unsubscribe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mailman_unsubscribe` (
  `mm_usid` mediumint(8) unsigned NOT NULL auto_increment,
  `mm_user` mediumint(8) unsigned NOT NULL default '0',
  `mm_list` varchar(255) NOT NULL default '',
  `mm_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `mm_done` tinyint(4) default '0',
  UNIQUE KEY `mm_usid` (`mm_usid`)
) ENGINE=InnoDB AUTO_INCREMENT=3280 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `math`
--

DROP TABLE IF EXISTS `math`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `math` (
  `math_inputhash` varchar(16) NOT NULL default '',
  `math_outputhash` varchar(16) NOT NULL default '',
  `math_html_conservativeness` tinyint(1) NOT NULL default '0',
  `math_html` text,
  `math_mathml` text,
  UNIQUE KEY `math_inputhash` (`math_inputhash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `mqg_emails`
--

DROP TABLE IF EXISTS `mqg_emails`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `mqg_emails` (
  `mqg_email` varchar(255) default NULL,
  `mqg_timestamp` varchar(14) default NULL,
  UNIQUE KEY `mqg_email` (`mqg_email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `newarticlepatrol`
--

DROP TABLE IF EXISTS `newarticlepatrol`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `newarticlepatrol` (
  `nap_page` int(8) unsigned NOT NULL default '0',
  `nap_patrolled` tinyint(3) unsigned NOT NULL default '0',
  `nap_user_co` mediumint(8) unsigned NOT NULL default '0',
  `nap_timestamp_co` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nap_user_ci` mediumint(8) unsigned NOT NULL default '0',
  `nap_timestamp_ci` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nap_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nap_newbie` tinyint(3) unsigned default '0',
  KEY `nap_page` (`nap_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `newarticles`
--

DROP TABLE IF EXISTS `newarticles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `newarticles` (
  `na_page` int(8) unsigned NOT NULL,
  `na_timestamp` varchar(14) NOT NULL,
  `na_valid` tinyint(3) unsigned NOT NULL,
  `na_user_text` varchar(14) NOT NULL,
  PRIMARY KEY  (`na_page`),
  KEY `na_timestamp` (`na_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `nfd`
--

DROP TABLE IF EXISTS `nfd`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nfd` (
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
  `nfd_status` tinyint(3) NOT NULL default '0',
  `nfd_delete_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_admin_delete_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_keep_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_admin_keep_votes` tinyint(3) unsigned NOT NULL default '0',
  `nfd_checkout_time` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `nfd_checkout_user` int(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`nfd_id`),
  KEY `nfd_page` (`nfd_page`)
) ENGINE=InnoDB AUTO_INCREMENT=26693 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `nfd_vote`
--

DROP TABLE IF EXISTS `nfd_vote`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `nfd_vote` (
  `nfdv_nfdid` int(8) unsigned NOT NULL,
  `nfdv_user` int(5) unsigned NOT NULL,
  `nfdv_vote` tinyint(3) unsigned NOT NULL default '0',
  `nfdv_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `nfdv_nfdid` (`nfdv_nfdid`),
  KEY `nfdv_user` (`nfdv_user`,`nfdv_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `objectcache`
--

DROP TABLE IF EXISTS `objectcache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `objectcache` (
  `keyname` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `value` mediumblob,
  `exptime` datetime default NULL,
  UNIQUE KEY `keyname` (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `oldimage`
--

DROP TABLE IF EXISTS `oldimage`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `oldimage` (
  `oi_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `oi_archive_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `oi_size` int(8) unsigned NOT NULL default '0',
  `oi_description` tinyblob NOT NULL,
  `oi_user` int(5) unsigned NOT NULL default '0',
  `oi_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `oi_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `oi_width` int(5) NOT NULL default '0',
  `oi_height` int(5) NOT NULL default '0',
  `oi_bits` int(3) NOT NULL default '0',
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE') default NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart') NOT NULL default 'unknown',
  `oi_minor_mime` varbinary(32) NOT NULL default 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL default '0',
  `oi_sha1` varbinary(32) NOT NULL default '',
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_usertext_timestamp` (`oi_user_text`,`oi_timestamp`),
  KEY `oi_sha1` (`oi_sha1`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `page` (
  `page_id` int(8) unsigned NOT NULL auto_increment,
  `page_namespace` int(11) NOT NULL default '0',
  `page_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `page_restrictions` tinyblob NOT NULL,
  `page_counter` bigint(20) unsigned NOT NULL default '0',
  `page_is_redirect` tinyint(1) unsigned NOT NULL default '0',
  `page_is_new` tinyint(1) unsigned NOT NULL default '0',
  `page_random` double unsigned NOT NULL default '0',
  `page_touched` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `page_latest` int(8) unsigned NOT NULL default '0',
  `page_len` int(8) unsigned NOT NULL default '0',
  `page_is_featured` tinyint(1) unsigned NOT NULL default '0',
  `page_counter2` bigint(20) unsigned NOT NULL default '0',
  `page_lastchecked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `page_randomizer` tinyint(4) NOT NULL default '0',
  `page_catinfo` int(10) unsigned default '0',
  `page_further_editing` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`page_id`),
  UNIQUE KEY `name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`)
) ENGINE=InnoDB AUTO_INCREMENT=2183662 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `page_randomizer`
--

DROP TABLE IF EXISTS `page_randomizer`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `page_randomizer` (
  `pr_id` int(10) unsigned NOT NULL,
  `pr_namespace` int(10) unsigned NOT NULL default '0',
  `pr_title` varchar(255) NOT NULL,
  `pr_random` double unsigned NOT NULL,
  `pr_catinfo` int(10) unsigned NOT NULL default '0',
  `pr_updated` varchar(14) default '',
  PRIMARY KEY  (`pr_id`),
  KEY `pr_random` (`pr_random`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `page_restrictions`
--

DROP TABLE IF EXISTS `page_restrictions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `page_restrictions` (
  `pr_page` int(11) NOT NULL default '0',
  `pr_type` varbinary(60) NOT NULL default '',
  `pr_level` varbinary(60) NOT NULL default '',
  `pr_cascade` tinyint(4) NOT NULL default '0',
  `pr_user` int(11) default NULL,
  `pr_expiry` varbinary(14) default NULL,
  `pr_id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`pr_page`,`pr_type`),
  UNIQUE KEY `pr_id` (`pr_id`),
  KEY `pr_typelevel` (`pr_type`,`pr_level`),
  KEY `pr_level` (`pr_level`),
  KEY `pr_cascade` (`pr_cascade`)
) ENGINE=InnoDB AUTO_INCREMENT=6771 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pagelinks`
--

DROP TABLE IF EXISTS `pagelinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pagelinks` (
  `pl_from` int(8) unsigned NOT NULL default '0',
  `pl_namespace` int(11) NOT NULL default '0',
  `pl_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `pl_from` (`pl_from`,`pl_namespace`,`pl_title`),
  KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pagelist`
--

DROP TABLE IF EXISTS `pagelist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pagelist` (
  `pl_page` int(8) unsigned NOT NULL,
  `pl_list` varchar(14) default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pageswithbrokenlinks`
--

DROP TABLE IF EXISTS `pageswithbrokenlinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pageswithbrokenlinks` (
  `pbl_page` int(8) unsigned NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pageview`
--

DROP TABLE IF EXISTS `pageview`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pageview` (
  `pv_page` int(8) unsigned NOT NULL,
  `pv_30day` bigint(20) unsigned NOT NULL default '0',
  `pv_1day` int(8) unsigned NOT NULL default '0',
  `pv_timestamp` varchar(14) NOT NULL,
  UNIQUE KEY `pv_page` (`pv_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pageview_tmp`
--

DROP TABLE IF EXISTS `pageview_tmp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `pageview_tmp` (
  `pv_page` int(8) NOT NULL,
  `pv_30day` bigint(20) unsigned NOT NULL default '0',
  `pv_1day` int(8) unsigned NOT NULL default '0',
  `pv_timestamp` varchar(14) NOT NULL,
  PRIMARY KEY  (`pv_page`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `passcaptcha`
--

DROP TABLE IF EXISTS `passcaptcha`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `passcaptcha` (
  `pc_id` int(8) unsigned NOT NULL auto_increment,
  `pc_pass` tinyint(3) unsigned NOT NULL default '0',
  `pc_user` int(5) unsigned NOT NULL default '0',
  `pc_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `pc_fullip` varchar(32) character set latin1 collate latin1_bin NOT NULL default '',
  `pc_caller` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `pc_caller_linenum` int(11) default '0',
  `pc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`pc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1293857 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `profilebox`
--

DROP TABLE IF EXISTS `profilebox`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `profilebox` (
  `pb_user` mediumint(8) unsigned NOT NULL default '0',
  `pb_started` mediumint(11) unsigned NOT NULL default '0',
  `pb_edits` mediumint(11) unsigned NOT NULL default '0',
  `pb_patrolled` mediumint(11) unsigned NOT NULL default '0',
  `pb_viewership` int(10) unsigned NOT NULL default '0',
  `pb_lastUpdated` varchar(14) character set latin1 collate latin1_bin default NULL,
  `pb_thumbs_given` int(11) NOT NULL default '0',
  `pb_thumbs_received` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pb_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `proposedredirects`
--

DROP TABLE IF EXISTS `proposedredirects`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `proposedredirects` (
  `pr_user` mediumint(8) unsigned NOT NULL,
  `pr_user_text` varchar(255) NOT NULL default '',
  `pr_from` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `pr_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `pr_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `protected_titles`
--

DROP TABLE IF EXISTS `protected_titles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `protected_titles` (
  `pt_namespace` int(11) NOT NULL default '0',
  `pt_title` varchar(255) NOT NULL default '',
  `pt_user` int(10) unsigned NOT NULL default '0',
  `pt_reason` tinyblob,
  `pt_timestamp` varbinary(14) NOT NULL default '',
  `pt_expiry` varbinary(14) NOT NULL default '',
  `pt_create_perm` varbinary(60) NOT NULL default '',
  PRIMARY KEY  (`pt_namespace`,`pt_title`),
  KEY `pt_timestamp` (`pt_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `qc`
--

DROP TABLE IF EXISTS `qc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `qc` (
  `qc_id` int(8) unsigned NOT NULL auto_increment,
  `qc_key` varchar(32) NOT NULL default '',
  `qc_action` varchar(16) NOT NULL default '',
  `qc_page` int(8) unsigned NOT NULL default '0',
  `qc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `qc_user` int(5) unsigned NOT NULL default '0',
  `qc_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `qc_rev_id` int(8) unsigned NOT NULL default '0',
  `qc_old_rev_id` int(8) unsigned NOT NULL default '0',
  `qc_patrolled` tinyint(3) unsigned default '0',
  `qc_yes_votes_req` tinyint(3) unsigned NOT NULL default '0',
  `qc_no_votes_req` tinyint(3) unsigned NOT NULL default '0',
  `qc_yes_votes` tinyint(3) unsigned NOT NULL default '0',
  `qc_no_votes` tinyint(3) unsigned NOT NULL default '0',
  `qc_checkout_time` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `qc_checkout_user` int(5) unsigned NOT NULL default '0',
  `qc_extra` varchar(32) default '',
  PRIMARY KEY  (`qc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=427160 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `qc_archive`
--

DROP TABLE IF EXISTS `qc_archive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `qc_archive` (
  `qc_id` int(8) unsigned NOT NULL,
  `qc_key` varchar(32) NOT NULL default '',
  `qc_action` varchar(16) NOT NULL default '',
  `qc_page` int(8) unsigned NOT NULL default '0',
  `qc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `qc_user` int(5) unsigned NOT NULL default '0',
  `qc_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `qc_rev_id` int(8) unsigned NOT NULL default '0',
  `qc_old_rev_id` int(8) unsigned NOT NULL default '0',
  `qc_patrolled` tinyint(3) unsigned default '0',
  `qc_yes_votes_req` tinyint(3) unsigned NOT NULL default '0',
  `qc_no_votes_req` tinyint(3) unsigned NOT NULL default '0',
  `qc_yes_votes` tinyint(3) unsigned NOT NULL default '0',
  `qc_no_votes` tinyint(3) unsigned NOT NULL default '0',
  `qc_checkout_time` varchar(14) default NULL,
  `qc_checkout_user` int(5) unsigned NOT NULL default '0',
  `qc_extra` varchar(32) default '',
  PRIMARY KEY  (`qc_id`),
  KEY `qc_timestamp` (`qc_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `qc_vote`
--

DROP TABLE IF EXISTS `qc_vote`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `qc_vote` (
  `qcv_qcid` int(8) unsigned NOT NULL,
  `qcv_user` int(5) unsigned NOT NULL,
  `qcv_vote` tinyint(3) unsigned NOT NULL default '0',
  `qc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `qcv_qcid` (`qcv_qcid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `qc_vote_archive`
--

DROP TABLE IF EXISTS `qc_vote_archive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `qc_vote_archive` (
  `qcv_qcid` int(8) unsigned NOT NULL,
  `qcv_user` int(5) unsigned NOT NULL,
  `qcv_vote` tinyint(3) unsigned NOT NULL default '0',
  `qc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `qcv_qcid` (`qcv_qcid`),
  KEY `qcv_user` (`qcv_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `querycache`
--

DROP TABLE IF EXISTS `querycache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `querycache` (
  `qc_type` char(32) NOT NULL default '',
  `qc_value` int(5) unsigned NOT NULL default '0',
  `qc_namespace` int(11) NOT NULL default '0',
  `qc_title` char(255) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `qc_type` (`qc_type`,`qc_value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `querycache_info`
--

DROP TABLE IF EXISTS `querycache_info`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `querycache_info` (
  `qci_type` varchar(32) NOT NULL default '',
  `qci_timestamp` varchar(14) NOT NULL default '19700101000000',
  UNIQUE KEY `qci_type` (`qci_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `querycachetwo`
--

DROP TABLE IF EXISTS `querycachetwo`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `querycachetwo` (
  `qcc_type` char(32) NOT NULL default '',
  `qcc_value` int(5) unsigned NOT NULL default '0',
  `qcc_namespace` int(11) NOT NULL default '0',
  `qcc_title` char(255) character set latin1 collate latin1_bin NOT NULL default '',
  `qcc_namespacetwo` int(11) NOT NULL default '0',
  `qcc_titletwo` char(255) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `qcc_type` (`qcc_type`,`qcc_value`),
  KEY `qcc_title` (`qcc_type`,`qcc_namespace`,`qcc_title`),
  KEY `qcc_titletwo` (`qcc_type`,`qcc_namespacetwo`,`qcc_titletwo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rating` (
  `rat_id` int(8) unsigned NOT NULL auto_increment,
  `rat_page` int(8) unsigned NOT NULL default '0',
  `rat_user` int(5) unsigned NOT NULL default '0',
  `rat_user_text` varchar(255) NOT NULL default '',
  `rat_month` varchar(7) NOT NULL default '',
  `rat_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `rat_rating` tinyint(1) unsigned NOT NULL default '0',
  `rat_isdeleted` tinyint(3) unsigned NOT NULL default '0',
  `rat_user_deleted` int(10) unsigned default NULL,
  `rat_deleted_when` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`rat_page`,`rat_id`),
  UNIQUE KEY `rat_id` (`rat_id`),
  UNIQUE KEY `user_month_id` (`rat_page`,`rat_user_text`,`rat_month`),
  KEY `rat_timestamp` (`rat_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=3462491 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rating_low`
--

DROP TABLE IF EXISTS `rating_low`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rating_low` (
  `rl_page` int(8) unsigned NOT NULL default '0',
  `rl_avg` double NOT NULL default '0',
  `rl_count` tinyint(4) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rctest_quizzes`
--

DROP TABLE IF EXISTS `rctest_quizzes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rctest_quizzes` (
  `rq_id` int(11) NOT NULL auto_increment,
  `rq_page_id` int(8) unsigned NOT NULL,
  `rq_rev_old` int(8) unsigned NOT NULL,
  `rq_rev_new` int(8) unsigned NOT NULL,
  `rq_ideal_responses` varchar(50) default NULL,
  `rq_acceptable_responses` varchar(50) default NULL,
  `rq_incorrect_responses` varchar(50) default NULL,
  `rq_explanation` text NOT NULL,
  `rq_coaching` text NOT NULL,
  `rq_difficulty` int(10) unsigned NOT NULL,
  `rq_author` varchar(255) default NULL,
  `rq_deleted` int(1) NOT NULL default '0',
  PRIMARY KEY  (`rq_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rctest_responses`
--

DROP TABLE IF EXISTS `rctest_responses`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rctest_responses` (
  `rr_id` tinyint(4) default NULL,
  `rr_response_button` varchar(50) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rctest_scores`
--

DROP TABLE IF EXISTS `rctest_scores`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rctest_scores` (
  `rs_user_id` mediumint(8) unsigned NOT NULL,
  `rs_user_name` varchar(255) NOT NULL,
  `rs_quiz_id` int(11) NOT NULL,
  `rs_correct` int(1) NOT NULL,
  `rs_response` tinyint(4) NOT NULL,
  `rs_timestamp` varchar(14) NOT NULL,
  PRIMARY KEY  (`rs_user_id`,`rs_quiz_id`),
  KEY `rs_timestamp` (`rs_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rctest_users`
--

DROP TABLE IF EXISTS `rctest_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rctest_users` (
  `ru_user_id` mediumint(8) unsigned NOT NULL,
  `ru_user_name` varchar(255) NOT NULL,
  `ru_base_patrol_count` mediumint(8) unsigned NOT NULL,
  `ru_quiz_ids` text,
  `ru_next_test_patrol_count` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ru_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `recentchanges`
--

DROP TABLE IF EXISTS `recentchanges`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `recentchanges` (
  `rc_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_cur_time` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_user` int(10) unsigned NOT NULL default '0',
  `rc_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_namespace` int(11) NOT NULL default '0',
  `rc_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_comment` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_minor` tinyint(3) unsigned NOT NULL default '0',
  `rc_bot` tinyint(3) unsigned NOT NULL default '0',
  `rc_new` tinyint(3) unsigned NOT NULL default '0',
  `rc_cur_id` int(10) unsigned NOT NULL default '0',
  `rc_this_oldid` int(10) unsigned NOT NULL default '0',
  `rc_last_oldid` int(10) unsigned NOT NULL default '0',
  `rc_type` tinyint(3) unsigned NOT NULL default '0',
  `rc_moved_to_ns` tinyint(3) unsigned NOT NULL default '0',
  `rc_moved_to_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `rc_ip` varchar(15) NOT NULL default '',
  `rc_id` int(8) NOT NULL auto_increment,
  `rc_patrolled` tinyint(3) unsigned NOT NULL default '0',
  `rc_indexed` tinyint(3) unsigned NOT NULL default '0',
  `rc_old_len` int(10) default NULL,
  `rc_new_len` int(10) default NULL,
  `rc_deleted` tinyint(3) unsigned NOT NULL default '0',
  `rc_logid` int(10) unsigned NOT NULL default '0',
  `rc_log_type` varbinary(255) default NULL,
  `rc_log_action` varbinary(255) default NULL,
  `rc_params` blob,
  PRIMARY KEY  (`rc_id`),
  KEY `rc_timestamp` (`rc_timestamp`),
  KEY `rc_namespace_title` (`rc_namespace`,`rc_title`),
  KEY `rc_cur_id` (`rc_cur_id`),
  KEY `new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_this_oldid` (`rc_this_oldid`),
  KEY `rc_ns_usertext` (`rc_namespace`,`rc_user_text`),
  KEY `rc_user_text` (`rc_user_text`,`rc_timestamp`),
  KEY `rc_patrolled_user` (`rc_patrolled`,`rc_user`)
) ENGINE=InnoDB AUTO_INCREMENT=8655607 DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `redirect`
--

DROP TABLE IF EXISTS `redirect`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `redirect` (
  `rd_from` int(8) unsigned NOT NULL default '0',
  `rd_namespace` int(11) NOT NULL default '0',
  `rd_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`rd_from`),
  KEY `rd_ns_title` (`rd_namespace`,`rd_title`,`rd_from`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rejected_email_links`
--

DROP TABLE IF EXISTS `rejected_email_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rejected_email_links` (
  `rel_id` int(8) unsigned NOT NULL auto_increment,
  `rel_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `rel_text` text,
  PRIMARY KEY  (`rel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=99249 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `revision`
--

DROP TABLE IF EXISTS `revision`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `revision` (
  `rev_id` int(8) unsigned NOT NULL auto_increment,
  `rev_page` int(8) unsigned NOT NULL default '0',
  `rev_comment` tinyblob NOT NULL,
  `rev_user` int(5) unsigned NOT NULL default '0',
  `rev_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `rev_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `rev_minor_edit` tinyint(1) unsigned NOT NULL default '0',
  `rev_deleted` tinyint(1) unsigned NOT NULL default '0',
  `rev_text_id` int(8) unsigned NOT NULL default '0',
  `rev_parent_id` int(10) unsigned default NULL,
  `rev_len` int(10) unsigned default NULL,
  PRIMARY KEY  (`rev_page`,`rev_id`),
  UNIQUE KEY `rev_id` (`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
  KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=8094749 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rssfeed`
--

DROP TABLE IF EXISTS `rssfeed`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rssfeed` (
  `rss_page` int(10) unsigned default NULL,
  `rss_timestamp` varchar(14) default NULL,
  `rss_approved` tinyint(3) unsigned NOT NULL default '0',
  `rss_alt_title` varchar(255) default NULL,
  UNIQUE KEY `rss_page` (`rss_page`,`rss_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ru_stcl`
--

DROP TABLE IF EXISTS `ru_stcl`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ru_stcl` (
  `raw_title` varchar(255) default NULL,
  `ru_rest` varchar(255) default NULL,
  `clean` varchar(255) default '',
  `tskey` varchar(255) default '',
  `excluded` tinyint(3) unsigned default '0',
  `id` mediumint(9) NOT NULL auto_increment,
  `lastupdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=959616 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `rush_data`
--

DROP TABLE IF EXISTS `rush_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rush_data` (
  `rush_query` text,
  `rush_volume` int(11) default NULL,
  `rush_cpc` decimal(5,2) default NULL,
  `rush_competition` decimal(5,2) default NULL,
  `rush_position` int(10) unsigned default NULL,
  `rush_page_id` int(11) default NULL,
  KEY `rush_page_id` (`rush_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `search_log_archive`
--

DROP TABLE IF EXISTS `search_log_archive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `search_log_archive` (
  `id` int(8) unsigned NOT NULL auto_increment,
  `query` varchar(255) NOT NULL default '',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `user` varchar(255) default NULL,
  KEY `slog_id` (`id`),
  KEY `slog_timestamp` (`timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=19619457 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `search_results`
--

DROP TABLE IF EXISTS `search_results`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `search_results` (
  `sr_id` int(10) unsigned NOT NULL,
  `sr_namespace` int(10) unsigned NOT NULL,
  `sr_title` varchar(255) NOT NULL,
  `sr_timestamp` varchar(14) NOT NULL,
  `sr_is_featured` tinyint(1) unsigned NOT NULL,
  `sr_has_video` tinyint(1) unsigned NOT NULL,
  `sr_steps` tinyint(1) unsigned NOT NULL,
  `sr_popularity` int(10) unsigned NOT NULL,
  `sr_num_editors` int(10) unsigned NOT NULL,
  `sr_first_editor` varchar(255) NOT NULL,
  `sr_last_editor` varchar(255) NOT NULL,
  `sr_img` varchar(255) NOT NULL,
  `sr_img_thumb_100` varchar(255) NOT NULL,
  `sr_processed` varchar(14) NOT NULL default '',
  PRIMARY KEY  (`sr_id`),
  KEY `sr_title` (`sr_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `searchindex`
--

DROP TABLE IF EXISTS `searchindex`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `searchindex` (
  `si_page` int(8) unsigned NOT NULL default '0',
  `si_title` varchar(255) NOT NULL default '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `sent_email`
--

DROP TABLE IF EXISTS `sent_email`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `sent_email` (
  `se_id` int(8) unsigned NOT NULL auto_increment,
  `se_user` int(5) unsigned NOT NULL default '0',
  `se_user_text` varchar(255) character set utf8 collate utf8_bin NOT NULL default '',
  `se_fullip` varchar(32) character set latin1 collate latin1_bin NOT NULL default '',
  `se_baseip` varchar(32) character set latin1 collate latin1_bin NOT NULL default '',
  `se_subject` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `se_from` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `se_to` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `se_caller` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `se_caller_linenum` int(11) default '0',
  `se_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`se_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1291045 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `share_id`
--

DROP TABLE IF EXISTS `share_id`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `share_id` (
  `s_id` int(10) unsigned NOT NULL,
  `s_desc` varchar(128) default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `share_track`
--

DROP TABLE IF EXISTS `share_track`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `share_track` (
  `selection` tinyint(3) unsigned NOT NULL default '0',
  `tstamp` timestamp NOT NULL default CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `site_search_log`
--

DROP TABLE IF EXISTS `site_search_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `site_search_log` (
  `ssl_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ssl_query` varchar(255) default NULL,
  `ssl_ts_count` double default NULL,
  `ssl_tm_count` double NOT NULL default '0',
  `ssl_error` tinyint(4) NOT NULL default '0',
  `ssl_user` int(5) unsigned NOT NULL default '0',
  `ssl_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ssl_cache` tinyint(4) NOT NULL default '0',
  `ssl_curl_error` tinyint(3) unsigned NOT NULL default '0',
  `ssl_host_id` tinyint(3) unsigned NOT NULL default '0',
  `ssl_num_results` smallint(5) unsigned NOT NULL default '0',
  `ssl_rank` tinyint(4) NOT NULL default '-1',
  `ssl_type` tinyint(4) default '0',
  UNIQUE KEY `gm_user_query_ts` (`ssl_user_text`,`ssl_query`,`ssl_ts`),
  KEY `gm_ts` (`ssl_ts`),
  KEY `gm_query` (`ssl_query`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `site_stats`
--

DROP TABLE IF EXISTS `site_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `site_stats` (
  `ss_row_id` int(8) unsigned NOT NULL default '0',
  `ss_total_views` bigint(20) unsigned default '0',
  `ss_total_edits` bigint(20) unsigned default '0',
  `ss_good_articles` bigint(20) unsigned default '0',
  `ss_links_emailed` bigint(20) unsigned default NULL,
  `ss_total_pages` bigint(20) default '-1',
  `ss_users` bigint(20) default '-1',
  `ss_admins` int(10) default '-1',
  `ss_images` int(10) default '0',
  UNIQUE KEY `ss_row_id` (`ss_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `sitesnap`
--

DROP TABLE IF EXISTS `sitesnap`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `sitesnap` (
  `ss_day` varchar(8) default '',
  `ss_total_views` bigint(20) unsigned default '0',
  `ss_total_edits` bigint(20) unsigned default '0',
  `ss_good_articles` bigint(20) unsigned default '0',
  `ss_links_emailed` bigint(20) unsigned default NULL,
  `ss_total_pages` bigint(20) default '-1',
  `ss_users` bigint(20) default '-1',
  `ss_admins` int(10) default '-1',
  `ss_images` int(10) default '0',
  UNIQUE KEY `ss_day` (`ss_day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `slideshow_todo`
--

DROP TABLE IF EXISTS `slideshow_todo`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `slideshow_todo` (
  `ss2d_page_id` int(8) unsigned NOT NULL,
  `ss2d_done` tinyint(1) NOT NULL default '0',
  `ss2d_error` tinyint(1) NOT NULL default '0',
  `ss2d_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ss2d_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `snap`
--

DROP TABLE IF EXISTS `snap`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `snap` (
  `snap_id` int(10) unsigned NOT NULL auto_increment,
  `snap_page` int(10) unsigned NOT NULL default '0',
  `snap_counter1` bigint(20) unsigned NOT NULL default '0',
  `snap_counter2` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`snap_id`),
  UNIQUE KEY `snap_page` (`snap_page`)
) ENGINE=InnoDB AUTO_INCREMENT=110467 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellcheck_articles`
--

DROP TABLE IF EXISTS `spellcheck_articles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellcheck_articles` (
  `sa_page_id` int(8) unsigned NOT NULL,
  `sa_rev_id` int(8) unsigned NOT NULL default '0',
  `sa_misspelled_count` smallint(5) unsigned NOT NULL default '0',
  `sa_misspellings` text NOT NULL,
  PRIMARY KEY  (`sa_page_id`),
  UNIQUE KEY `sa_page_rev` (`sa_rev_id`),
  FULLTEXT KEY `misspellings` (`sa_misspellings`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellcheck_misspellings`
--

DROP TABLE IF EXISTS `spellcheck_misspellings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellcheck_misspellings` (
  `sm_word` varchar(255) NOT NULL,
  `sm_count` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`sm_word`),
  UNIQUE KEY `sm_word` (`sm_word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker`
--

DROP TABLE IF EXISTS `spellchecker`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker` (
  `sc_page` int(10) unsigned NOT NULL,
  `sc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_errors` tinyint(3) unsigned NOT NULL,
  `sc_dirty` tinyint(4) NOT NULL,
  `sc_firstedit` varchar(14) collate utf8_unicode_ci default NULL,
  `sc_checkout` varchar(14) collate utf8_unicode_ci NOT NULL,
  `sc_checkout_user` int(5) NOT NULL,
  `sc_exempt` tinyint(3) NOT NULL default '0',
  UNIQUE KEY `sc_page` (`sc_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker_caps`
--

DROP TABLE IF EXISTS `spellchecker_caps`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker_caps` (
  `sc_word` varchar(20) collate utf8_unicode_ci NOT NULL,
  `sc_user` mediumint(8) NOT NULL,
  UNIQUE KEY `sc_word` (`sc_word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker_page`
--

DROP TABLE IF EXISTS `spellchecker_page`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker_page` (
  `sp_id` int(10) unsigned NOT NULL auto_increment,
  `sp_page` int(10) unsigned NOT NULL,
  `sp_word` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sp_id`),
  UNIQUE KEY `sp_id` (`sp_id`),
  UNIQUE KEY `sp_page` (`sp_page`,`sp_word`)
) ENGINE=InnoDB AUTO_INCREMENT=9364545 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker_temp`
--

DROP TABLE IF EXISTS `spellchecker_temp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker_temp` (
  `st_word` varchar(20) collate utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker_whitelist`
--

DROP TABLE IF EXISTS `spellchecker_whitelist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker_whitelist` (
  `sw_word` varchar(20) character set latin1 collate latin1_general_cs NOT NULL,
  `sw_active` tinyint(4) NOT NULL,
  `sw_user` mediumint(8) NOT NULL,
  UNIQUE KEY `sw_word` (`sw_word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spellchecker_word`
--

DROP TABLE IF EXISTS `spellchecker_word`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spellchecker_word` (
  `sw_id` int(10) unsigned NOT NULL auto_increment,
  `sw_word` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `sw_corrections` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`sw_id`),
  UNIQUE KEY `sw_id` (`sw_id`)
) ENGINE=InnoDB AUTO_INCREMENT=373978 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `spoofuser`
--

DROP TABLE IF EXISTS `spoofuser`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `spoofuser` (
  `su_name` varchar(255) NOT NULL default '',
  `su_normalized` varchar(255) default NULL,
  `su_legal` tinyint(1) default NULL,
  `su_error` text,
  PRIMARY KEY  (`su_name`),
  KEY `su_normalized` (`su_normalized`,`su_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `startertool`
--

DROP TABLE IF EXISTS `startertool`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `startertool` (
  `st_user` int(10) NOT NULL,
  `st_username` varchar(255) NOT NULL,
  `st_date` varchar(14) NOT NULL,
  `st_action` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `stcl`
--

DROP TABLE IF EXISTS `stcl`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `stcl` (
  `id` int(10) unsigned NOT NULL default '0',
  `source` varchar(32) default '',
  `kw_vrank` int(10) unsigned default '0',
  `raw_title` varchar(255) default NULL,
  `sshare_rank` int(10) unsigned default '0',
  `keyword_engage` int(10) unsigned default '0',
  `clean` varchar(255) default '',
  `excluded` tinyint(3) unsigned default '0',
  `keyword_effect` int(10) unsigned default '0',
  `tskey` varchar(255) default '',
  `lastupdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `article_exists` tinyint(4) default '0',
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `stop_words`
--

DROP TABLE IF EXISTS `stop_words`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `stop_words` (
  `stop_words` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `suggest_cats`
--

DROP TABLE IF EXISTS `suggest_cats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `suggest_cats` (
  `sc_user` int(5) unsigned NOT NULL default '0',
  `sc_cats` varchar(512) default NULL,
  UNIQUE KEY `sc_user` (`sc_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `suggested_links`
--

DROP TABLE IF EXISTS `suggested_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `suggested_links` (
  `sl_sugg` int(8) unsigned NOT NULL,
  `sl_page` int(8) unsigned NOT NULL,
  `sl_sort` double(5,4) unsigned default '0.0000',
  KEY `sl_sugg` (`sl_sugg`),
  KEY `sl_sugg_2` (`sl_sugg`,`sl_page`),
  KEY `sl_page` (`sl_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `suggested_notify`
--

DROP TABLE IF EXISTS `suggested_notify`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `suggested_notify` (
  `sn_page` int(8) unsigned NOT NULL,
  `sn_notify` varchar(255) default '',
  `sn_timestamp` varchar(14) default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `suggested_titles`
--

DROP TABLE IF EXISTS `suggested_titles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `suggested_titles` (
  `st_id` int(10) unsigned NOT NULL auto_increment,
  `st_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `st_key` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `st_used` tinyint(4) default '0',
  `st_hastraffic_v` varchar(32) default '',
  `st_sv` tinyint(4) default '-1',
  `st_created` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `st_source` varchar(4) default '',
  `st_group` tinyint(3) unsigned default '0',
  `st_patrolled` tinyint(3) unsigned default '0',
  `st_category` varchar(255) default '',
  `st_isrequest` tinyint(3) unsigned default '0',
  `st_suggested` varchar(14) default '',
  `st_notify` varchar(255) default '',
  `st_user` mediumint(8) unsigned NOT NULL default '0',
  `st_user_text` varchar(255) NOT NULL default '',
  `st_random` double unsigned NOT NULL default '0',
  `st_traffic_volume` tinyint(4) default '-1',
  PRIMARY KEY  (`st_id`),
  UNIQUE KEY `st_title` (`st_title`),
  KEY `st_key` (`st_key`),
  KEY `st_id` (`st_id`),
  KEY `st_group` (`st_group`),
  KEY `st_used` (`st_used`,`st_patrolled`,`st_group`,`st_category`),
  KEY `st_random` (`st_random`),
  KEY `suggested_recommendations` (`st_category`,`st_used`,`st_traffic_volume`,`st_random`)
) ENGINE=InnoDB AUTO_INCREMENT=548746 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `templatelinks`
--

DROP TABLE IF EXISTS `templatelinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `templatelinks` (
  `tl_from` int(8) unsigned NOT NULL default '0',
  `tl_namespace` int(11) NOT NULL default '0',
  `tl_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `tl_from` (`tl_from`,`tl_namespace`,`tl_title`),
  KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_title` (`tl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `text` (
  `old_id` int(8) unsigned NOT NULL auto_increment,
  `old_namespace` tinyint(2) unsigned NOT NULL default '0',
  `old_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `old_text` mediumtext NOT NULL,
  `old_comment` tinyblob NOT NULL,
  `old_user` int(5) unsigned NOT NULL default '0',
  `old_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `old_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `old_minor_edit` tinyint(1) NOT NULL default '0',
  `old_flags` tinyblob NOT NULL,
  `inverse_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `old_id` (`old_id`),
  KEY `old_namespace` (`old_namespace`,`old_title`(20)),
  KEY `old_timestamp` (`old_timestamp`),
  KEY `name_title_timestamp` (`old_namespace`,`old_title`,`inverse_timestamp`),
  KEY `user_timestamp` (`old_user`,`inverse_timestamp`),
  KEY `usertext_timestamp` (`old_user_text`,`inverse_timestamp`),
  KEY `old_user_teimetsamp` (`old_user`,`old_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=8021594 DEFAULT CHARSET=latin1 MAX_ROWS=100000000 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `thumbs`
--

DROP TABLE IF EXISTS `thumbs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `thumbs` (
  `thumb_giver_id` mediumint(8) unsigned NOT NULL,
  `thumb_giver_text` varchar(255) NOT NULL,
  `thumb_recipient_id` mediumint(8) unsigned NOT NULL,
  `thumb_recipient_text` varchar(255) NOT NULL,
  `thumb_rev_id` int(8) unsigned NOT NULL,
  `thumb_page_id` int(8) unsigned NOT NULL default '0',
  `thumb_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `thumb_notified` int(1) default '0',
  `thumb_exclude` int(1) default '0',
  PRIMARY KEY  (`thumb_rev_id`,`thumb_giver_id`),
  KEY `thumb_page_id` (`thumb_page_id`),
  KEY `thumb_timestamp` (`thumb_timestamp`),
  KEY `thumb_recipient_id` (`thumb_recipient_id`),
  KEY `thumb_recipient_text` (`thumb_recipient_text`,`thumb_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `thumbs_notifications`
--

DROP TABLE IF EXISTS `thumbs_notifications`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `thumbs_notifications` (
  `tn_rev_id` int(8) unsigned NOT NULL,
  `tn_last_thumbed` timestamp NOT NULL default '0000-00-00 00:00:00',
  `tn_last_notified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tn_rev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `title_search_key`
--

DROP TABLE IF EXISTS `title_search_key`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `title_search_key` (
  `tsk_id` int(8) unsigned NOT NULL auto_increment,
  `tsk_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `tsk_key` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `tsk_namespace` tinyint(2) unsigned NOT NULL default '0',
  `tsk_wasfeatured` tinyint(4) NOT NULL default '0',
  UNIQUE KEY `skey_id` (`tsk_id`),
  UNIQUE KEY `name_title` (`tsk_namespace`,`tsk_title`),
  KEY `skey_title` (`tsk_title`(20))
) ENGINE=InnoDB AUTO_INCREMENT=109412292 DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `title_tests`
--

DROP TABLE IF EXISTS `title_tests`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `title_tests` (
  `tt_pageid` int(10) unsigned NOT NULL,
  `tt_page` varchar(255) NOT NULL,
  `tt_test` int(2) unsigned NOT NULL,
  `tt_custom` text,
  PRIMARY KEY  (`tt_pageid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `trackbacks`
--

DROP TABLE IF EXISTS `trackbacks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `trackbacks` (
  `tb_id` int(11) NOT NULL auto_increment,
  `tb_page` int(11) default NULL,
  `tb_title` varchar(255) NOT NULL default '',
  `tb_url` varchar(255) NOT NULL default '',
  `tb_ex` text,
  `tb_name` varchar(255) default NULL,
  PRIMARY KEY  (`tb_id`),
  KEY `tb_page` (`tb_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `transcache`
--

DROP TABLE IF EXISTS `transcache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `transcache` (
  `tc_url` varchar(255) NOT NULL default '',
  `tc_contents` text,
  `tc_time` int(11) NOT NULL default '0',
  UNIQUE KEY `tc_url_idx` (`tc_url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterfeedaccounts`
--

DROP TABLE IF EXISTS `twitterfeedaccounts`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterfeedaccounts` (
  `tws_username` varchar(16) default NULL,
  `tws_token` varchar(255) default NULL,
  `tws_verifier` varchar(255) default NULL,
  `tws_secret` varchar(255) default NULL,
  `tws_password` varchar(255) default NULL,
  UNIQUE KEY `tws_username` (`tws_username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterfeedcatgories`
--

DROP TABLE IF EXISTS `twitterfeedcatgories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterfeedcatgories` (
  `tfc_username` varchar(255) default NULL,
  `tfc_category` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `name_cat` (`tfc_username`,`tfc_category`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterfeedlog`
--

DROP TABLE IF EXISTS `twitterfeedlog`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterfeedlog` (
  `tfl_user` int(5) unsigned NOT NULL default '0',
  `tfl_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `tfl_message` varchar(140) default NULL,
  `tfl_twitteraccount` varchar(16) default NULL,
  `tfl_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterfeedusers`
--

DROP TABLE IF EXISTS `twitterfeedusers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterfeedusers` (
  `tfu_user` int(5) unsigned NOT NULL default '0',
  `tfu_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `tfu_token` varchar(255) default NULL,
  `tfu_secret` varchar(255) default NULL,
  `tfu_settings` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_cookie`
--

DROP TABLE IF EXISTS `twitterreplier_cookie`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_cookie` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `hash` varchar(255) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `twitter_user_id` (`twitter_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_oauth`
--

DROP TABLE IF EXISTS `twitterreplier_oauth`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_oauth` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `wikihow_user_id` int(10) unsigned NOT NULL,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `twitter_user_id` USING BTREE (`twitter_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_reply_log`
--

DROP TABLE IF EXISTS `twitterreplier_reply_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_reply_log` (
  `trrl_added` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `trrl_reply_id` varchar(255) NOT NULL,
  `trrl_reply_handle` varchar(255) NOT NULL,
  `trrl_wh_user` varchar(255) NOT NULL default '0',
  `trrl_orig_id` varchar(255) NOT NULL,
  `trrl_orig_handle` varchar(255) NOT NULL,
  `trrl_reply_tweet` text,
  `trrl_url` varchar(255) NOT NULL,
  `trrl_orig_tweet` text,
  KEY `trrl_added` (`trrl_added`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_reply_tweets`
--

DROP TABLE IF EXISTS `twitterreplier_reply_tweets`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_reply_tweets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `in_reply_to_tweet_id` bigint(12) unsigned NOT NULL,
  `reply_tweet_id` bigint(12) NOT NULL,
  `reply_tweet` varchar(160) NOT NULL,
  `wikihow_user_id` int(10) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `twitter_user_id` (`twitter_user_id`,`in_reply_to_tweet_id`),
  KEY `in_reply_to_tweet_id` (`in_reply_to_tweet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1381 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_search_categories`
--

DROP TABLE IF EXISTS `twitterreplier_search_categories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_search_categories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `keywords` varchar(255) NOT NULL,
  `type` enum('twitter','inboxq') NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `twitterreplier_tweets`
--

DROP TABLE IF EXISTS `twitterreplier_tweets`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterreplier_tweets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tweet_id` bigint(12) unsigned NOT NULL,
  `tweet` varchar(255) NOT NULL,
  `twitter_user_id` bigint(12) NOT NULL,
  `search_category_id` int(10) unsigned NOT NULL,
  `reply_status` int(1) unsigned NOT NULL,
  `response_object` text NOT NULL,
  `twitter_created_on` datetime NOT NULL,
  `locked_by` varchar(255) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `tweet_id` (`tweet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39132 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `urls`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `urls` (
  `url` varchar(255) default NULL,
  `count` int(10) unsigned default '0',
  `batch` tinyint(3) unsigned default NULL,
  `host` varchar(32) default '',
  KEY `batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user` (
  `user_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_name` varchar(255) NOT NULL default '',
  `user_real_name` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `user_password` tinyblob NOT NULL,
  `user_newpassword` tinyblob NOT NULL,
  `user_email` tinytext NOT NULL,
  `user_options` blob NOT NULL,
  `user_touched` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `user_token` varchar(32) character set latin1 collate latin1_bin NOT NULL default '',
  `user_email_authenticated` varchar(14) character set latin1 collate latin1_bin default NULL,
  `user_email_token` varchar(32) character set latin1 collate latin1_bin default NULL,
  `user_email_token_expires` varchar(14) character set latin1 collate latin1_bin default NULL,
  `user_registration` varchar(14) character set latin1 collate latin1_bin default NULL,
  `user_wiki_password` tinyblob,
  `ehow_password` tinyblob,
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `user_email_token` (`user_email_token`)
) ENGINE=InnoDB AUTO_INCREMENT=1286783 DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_groups` (
  `ug_user` int(5) unsigned NOT NULL default '0',
  `ug_group` char(16) NOT NULL default '',
  PRIMARY KEY  (`ug_user`,`ug_group`),
  KEY `ug_group` (`ug_group`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_newkudos`
--

DROP TABLE IF EXISTS `user_newkudos`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_newkudos` (
  `user_id` int(5) NOT NULL default '0',
  `user_ip` varchar(40) NOT NULL default '',
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_newtalk`
--

DROP TABLE IF EXISTS `user_newtalk`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_newtalk` (
  `user_id` int(5) NOT NULL default '0',
  `user_ip` varchar(40) NOT NULL default '',
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_rights`
--

DROP TABLE IF EXISTS `user_rights`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_rights` (
  `ur_user` int(5) unsigned NOT NULL default '0',
  `ur_rights` tinyblob NOT NULL,
  UNIQUE KEY `ur_user` (`ur_user`),
  KEY `ur_rights` (`ur_rights`(128))
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_blacklist`
--

DROP TABLE IF EXISTS `video_blacklist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_blacklist` (
  `vb_id` varchar(16) NOT NULL default '',
  `vb_user` int(5) unsigned NOT NULL default '0',
  `vb_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `vb_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `vb_id` (`vb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_data`
--

DROP TABLE IF EXISTS `video_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_data` (
  `vd_id` varchar(16) NOT NULL default '',
  `vd_data` text,
  `vd_source` varchar(16) NOT NULL default '',
  UNIQUE KEY `vd_id_source` (`vd_id`,`vd_source`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_flag`
--

DROP TABLE IF EXISTS `video_flag`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_flag` (
  `vf_id` varchar(16) NOT NULL default '',
  `vf_user` int(5) unsigned NOT NULL default '0',
  `vf_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `vf_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `id_user_text` (`vf_id`,`vf_user_text`),
  KEY `vf_id` (`vf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_links`
--

DROP TABLE IF EXISTS `video_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_links` (
  `vl_page` int(8) unsigned NOT NULL default '0',
  `vl_id` varchar(16) NOT NULL default '',
  `vl_result` tinyint(3) unsigned default '0',
  `vl_source` varchar(16) NOT NULL default '',
  `vl_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `vl_thumb` varchar(255) default '',
  UNIQUE KEY `id_page` (`vl_id`,`vl_page`),
  KEY `vl_id` (`vl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_removedlinks`
--

DROP TABLE IF EXISTS `video_removedlinks`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_removedlinks` (
  `vr_page` int(8) unsigned NOT NULL default '0',
  `vr_id` varchar(16) NOT NULL default '',
  `vr_user` int(5) unsigned NOT NULL default '0',
  `vr_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `vr_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  KEY `vr_id` (`vr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `video_titles`
--

DROP TABLE IF EXISTS `video_titles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video_titles` (
  `vt_id` varchar(16) NOT NULL default '',
  `vt_source` varchar(16) NOT NULL default '',
  `vt_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `vt_thumb` varchar(255) default '',
  UNIQUE KEY `vt_id` (`vt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `videoadder`
--

DROP TABLE IF EXISTS `videoadder`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `videoadder` (
  `va_id` int(8) unsigned NOT NULL auto_increment,
  `va_page` mediumint(8) unsigned NOT NULL,
  `va_page_touched` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `va_inuse` varchar(14) default NULL,
  `va_skipped_accepted` tinyint(3) unsigned default NULL,
  `va_template_ns` tinyint(3) unsigned default NULL,
  `va_src` varchar(16) NOT NULL default '',
  `va_vid_id` varchar(32) NOT NULL default '',
  `va_user` int(8) unsigned default NULL,
  `va_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `va_timestamp` varchar(14) default '',
  `va_page_counter` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`va_id`),
  UNIQUE KEY `va_page` (`va_page`)
) ENGINE=InnoDB AUTO_INCREMENT=179453 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `watchlist` (
  `wl_user` int(5) unsigned NOT NULL default '0',
  `wl_namespace` int(11) NOT NULL default '0',
  `wl_title` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `wl_notificationtimestamp` varchar(14) character set latin1 collate latin1_bin default NULL,
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `namespace_title` (`wl_namespace`,`wl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wh_db_ping`
--

DROP TABLE IF EXISTS `wh_db_ping`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `wh_db_ping` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `i` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1608272 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wikiphoto_article_status`
--

DROP TABLE IF EXISTS `wikiphoto_article_status`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `wikiphoto_article_status` (
  `article_id` int(10) unsigned NOT NULL,
  `creator` varchar(32) NOT NULL default '',
  `processed` varchar(14) NOT NULL default '',
  `reviewed` tinyint(3) unsigned NOT NULL default '0',
  `retry` tinyint(3) unsigned NOT NULL default '0',
  `needs_retry` tinyint(3) unsigned NOT NULL default '0',
  `error` text NOT NULL,
  `url` varchar(255) NOT NULL default '',
  `images` int(10) unsigned NOT NULL default '0',
  `steps` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wikiphoto_image_names`
--

DROP TABLE IF EXISTS `wikiphoto_image_names`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `wikiphoto_image_names` (
  `filename` varchar(255) NOT NULL,
  `wikiname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ytas_meta`
--

DROP TABLE IF EXISTS `ytas_meta`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ytas_meta` (
  `ytas_id` mediumint(8) unsigned NOT NULL auto_increment,
  `ytas_title` varchar(255) NOT NULL default '',
  `ytas_description` text,
  `ytas_keywords` varchar(255) NOT NULL default '',
  `ytas_category` varchar(255) default '',
  `ytas_user` mediumint(8) unsigned NOT NULL default '0',
  `ytas_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `ytas_id` (`ytas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ythasthumbs`
--

DROP TABLE IF EXISTS `ythasthumbs`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ythasthumbs` (
  `yth_page` int(8) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ytnotify`
--

DROP TABLE IF EXISTS `ytnotify`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ytnotify` (
  `ytn_user` int(5) unsigned NOT NULL default '0',
  `ytn_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `ytn_page` int(8) unsigned NOT NULL default '0',
  `ytn_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `ytn_published` tinyint(3) unsigned default '0',
  `ytn_published_time` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  UNIQUE KEY `user_page` (`ytn_user`,`ytn_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-07-16 11:05:20

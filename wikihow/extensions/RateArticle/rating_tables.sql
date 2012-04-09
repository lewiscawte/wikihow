--
-- Table structure for table `rating`
--
CREATE TABLE /*_*/rating (
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
) /*$wgDBTableOptions*/;

--
-- Table structure for table `rating_low`
--
CREATE TABLE /*_*/rating_low (
  `rl_page` int(8) unsigned NOT NULL default '0',
  `rl_avg` double NOT NULL default '0',
  `rl_count` tinyint(4) NOT NULL default '0'
) /*$wgDBTableOptions*/;
--
-- Table structure for table `email_notifications`
--
CREATE TABLE /*_*/email_notifications (
  `en_user` mediumint(8) unsigned NOT NULL default '0',
  `en_page` int(8) unsigned NOT NULL default '0',
  `en_watch` tinyint(2) unsigned NOT NULL default '0',
  `en_viewership` int(5) unsigned NOT NULL default '0',
  `en_viewership_email` varchar(14) default NULL,
  `en_watch_email` varchar(14) default NULL,
  `en_featured_email` varchar(14) default NULL,
  `en_share_email` varchar(14) default NULL,
  `en_risingstar_email` varchar(14) default NULL,
  `en_last_emailsent` varchar(14) default NULL,
  PRIMARY KEY  (`en_user`,`en_page`),
  KEY `en_user` (`en_user`),
  KEY `en_page` (`en_page`)
) /*$wgDBTableOptions*/;
--
-- Table structure for table `editfinder`
--
CREATE TABLE /*_*/editfinder (
  `ef_page` int(8) default NULL,
  `ef_title` varchar(255) default NULL,
  `ef_edittype` varchar(255) default NULL,
  `ef_skip` mediumint(8) default NULL,
  `ef_skip_ts` varchar(14) default NULL,
  `ef_last_viewed` varchar(14) default NULL,
  UNIQUE KEY `idx_ef` (`ef_page`,`ef_edittype`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `editfinder_skip`
--
CREATE TABLE /*_*/editfinder_skip (
  `efs_page` int(8) default NULL,
  `efs_user` mediumint(8) default NULL,
  `efs_timestamp` varchar(14) default NULL,
  KEY `efs_user` (`efs_user`)
) /*$wgDBTableOptions*/;
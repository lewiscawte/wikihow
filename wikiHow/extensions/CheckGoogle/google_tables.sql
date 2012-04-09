--
-- Table structure for table `google_indexed`
--
CREATE TABLE /*_*/google_indexed (
  `gi_page` int(8) unsigned NOT NULL,
  `gi_indexed` tinyint(3) unsigned NOT NULL default '0',
  `gi_lastcheck` varchar(14) NOT NULL default '',
  `gi_page_created` varchar(14) NOT NULL default '',
  `gi_times_checked` int(10) unsigned default '0',
  UNIQUE KEY `gi_page1` (`gi_page`),
  KEY `gi_page` (`gi_page`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `google_indexed_log`
--
CREATE TABLE /*_*/google_indexed_log (
  `gl_page` int(8) unsigned NOT NULL,
  `gl_pos` tinyint(3) unsigned default '0',
  `gl_err` tinyint(3) unsigned default '0',
  `gl_err_str` varchar(255) default NULL,
  `gl_checked` varchar(14) NOT NULL default ''
) /*$wgDBTableOptions*/;
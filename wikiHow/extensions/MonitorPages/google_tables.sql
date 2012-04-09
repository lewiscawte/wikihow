--
-- Table structure for table `google_monitor`
--
CREATE TABLE /*_*/google_monitor (
  `gm_page` int(8) unsigned NOT NULL default '0',
  `gm_active` tinyint(1) unsigned NOT NULL default '1',
  UNIQUE KEY `gm_page` (`gm_page`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `google_monitor_results`
--
CREATE TABLE /*_*/google_monitor_results (
  `gmr_page` int(8) unsigned NOT NULL default '0',
  `gmr_position` tinyint(3) unsigned NOT NULL default '0',
  `gmr_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP
) /*$wgDBTableOptions*/;
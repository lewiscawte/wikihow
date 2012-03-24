--
-- Table structure for table `docentcategories`
--
CREATE TABLE /*_*/docentcategories (
	`dc_user` mediumint(8) unsigned NOT NULL default '0',
	`dc_to` varchar(255) NOT NULL default '',
	KEY `dc_user` (`dc_user`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `docentwarnings`
--
CREATE TABLE /*_*/docentwarnings (
	`dw_user` mediumint(8) unsigned NOT NULL default '0',
	`dw_timestamp` varchar(14) NOT NULL default ''
) /*$wgDBTableOptions*/;

--
-- Table structure for table `mailman_subscribe`
--
CREATE TABLE /*_*/mailman_subscribe (
	`mm_sid` mediumint(8) unsigned NOT NULL auto_increment,
	`mm_user` mediumint(8) unsigned NOT NULL default '0',
	`mm_list` varchar(255) NOT NULL default '',
	`mm_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`mm_done` tinyint(4) default '0',
	UNIQUE KEY `mm_sid` (`mm_sid`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `mailman_unsubscribe`
--
CREATE TABLE /*_*/mailman_unsubscribe (
	`mm_usid` mediumint(8) unsigned NOT NULL auto_increment,
	`mm_user` mediumint(8) unsigned NOT NULL default '0',
	`mm_list` varchar(255) NOT NULL default '',
	`mm_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`mm_done` tinyint(4) default '0',
	UNIQUE KEY `mm_usid` (`mm_usid`)
) /*$wgDBTableOptions*/;
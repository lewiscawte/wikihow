--
-- Table structure for table `categorylinkstop`
--
CREATE TABLE /*_*/categorylinkstop (
	`cl_from` int(8) unsigned NOT NULL default '0',
	`cl_to` varchar(255) NOT NULL default '',
	`cl_sortkey` varchar(255) NOT NULL default '',
	`cl_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	UNIQUE KEY `cl_from` (`cl_from`,`cl_to`),
	KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
	KEY `cl_sortkey` (`cl_to`,`cl_sortkey`,`cl_from`),
	KEY `cl_from_1` (`cl_from`)
) /*$wgDBTableOptions*/;
--
-- Table structure for table `gmini`
--
CREATE TABLE /*_*/gmini (
  `gm_ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `gm_query` varchar(255) default NULL,
  `gm_ts_count` double default NULL,
  `gm_tm_count` double default NULL,
  `gm_error` tinyint(4) default '0',
  `gm_user` int(5) unsigned NOT NULL default '0',
  `gm_user_text` varchar(255) NOT NULL default '',
  `gm_cache` tinyint(4) default '0',
  `gm_curl_error` tinyint(3) unsigned default '0',
  `gm_host_id` tinyint(3) unsigned default '0',
  `gm_num_results` smallint(5) unsigned NOT NULL default '0',
  `gm_rank` tinyint(4) NOT NULL default '-1',
  `gm_type` tinyint(4) default '0',
  UNIQUE KEY `gm_user_query_ts` (`gm_user_text`,`gm_query`,`gm_ts`),
  KEY `gm_ts` (`gm_ts`),
  KEY `gm_query` (`gm_query`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `search_results`
--
CREATE TABLE /*_*/search_results (
  `sr_id` int(10) unsigned NOT NULL PRIMARY KEY,
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
  KEY `sr_title` (`sr_title`)
) /*$wgDBTableOptions*/;
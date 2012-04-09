--
-- Table structure for table `twitterreplier_cookie`
--
CREATE TABLE `twitterreplier_cookie (
  `id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `hash` varchar(255) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  UNIQUE KEY `twitter_user_id` (`twitter_user_id`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `twitterreplier_oauth`
--
CREATE TABLE /*_*/twitterreplier_oauth (
  `id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
  `wikihow_user_id` int(10) unsigned NOT NULL,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  UNIQUE KEY `twitter_user_id` USING BTREE (`twitter_user_id`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `twitterreplier_reply_log`
--
CREATE TABLE /*_*/twitterreplier_reply_log (
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
) /*$wgDBTableOptions*/;

--
-- Table structure for table `twitterreplier_reply_tweets`
--
CREATE TABLE /*_*/twitterreplier_reply_tweets (
  `id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
  `twitter_user_id` bigint(12) unsigned NOT NULL,
  `in_reply_to_tweet_id` bigint(12) unsigned NOT NULL,
  `reply_tweet_id` bigint(12) NOT NULL,
  `reply_tweet` varchar(160) NOT NULL,
  `wikihow_user_id` int(10) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  KEY `twitter_user_id` (`twitter_user_id`,`in_reply_to_tweet_id`),
  KEY `in_reply_to_tweet_id` (`in_reply_to_tweet_id`)
) /*$wgDBTableOptions*/;

--
-- Table structure for table `twitterreplier_search_categories`
--
CREATE TABLE /*_*/twitterreplier_search_categories (
  `id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
  `keywords` varchar(255) NOT NULL,
  `type` enum('twitter','inboxq') NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL
) /*$wgDBTableOptions*/;

--
-- Table structure for table `twitterreplier_tweets`
--
CREATE TABLE /*_*/twitterreplier_tweets (
  `id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
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
  UNIQUE KEY `tweet_id` (`tweet_id`)
) /*$wgDBTableOptions*/;
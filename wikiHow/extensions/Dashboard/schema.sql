--
-- Table structure for table `community_dashboard_opts`
--
CREATE TABLE /*_*/community_dashboard_opts (
  `cdo_priorities_json` text,
  `cdo_thresholds_json` text,
  `cdo_baselines_json` text
) /*$wgDBTableOptions*/;

-- initial values:
INSERT INTO /*_*/community_dashboard_opts SET
	cdo_priorities_json = '["RecentChangesAppWidget"]',
	cdo_thresholds_json = '{"RecentChangesAppWidget":{"mid":250,"high":500}}';

--
-- Table structure for table `community_dashboard_users`
--
-- Note: in community_dashboard_users, I wanted to separate
-- cdu_completion_json from cdu_prefs_json because there's a query in
-- DashboardData::resetDailyCompletionAllUsers() that would have been very slow
-- if the data was one column
--
CREATE TABLE /*_*/community_dashboard_users (
  `cdu_userid` int(10) unsigned NOT NULL PRIMARY KEY,
  `cdu_prefs_json` text NOT NULL,
  `cdu_completion_json` text NOT NULL
) /*$wgDBTableOptions*/;

-- initial values:
INSERT INTO /*_*/community_dashboard_users SET
	cdu_userid = '',
	cdu_prefs_json = '{"ordering":["RecentChangesAppWidget"]}',
	cdu_completion_json = '{"RecentChangesAppWidget":0}';
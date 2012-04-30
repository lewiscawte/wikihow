<?
/** db schema:
CREATE TABLE `daily_edits` (
  `de_page_id` int(8) unsigned NOT NULL,
  `de_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (`de_page_id`),
  KEY `de_timestamp` (`de_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
*/

$wgHooks['MarkPatrolledDB'][] = array('DailyEdits::onMarkPatrolledDB');

/*
* Contains a static method to store pages that have been edited for a given day. 
* See maintenance/trimDailyEditsTable.php for maintenance of the table
*
* IMPORTANT:  This class should be included after GoodRevision.class.php to ensure a last good 
* revision id is present when consumers of this table attempt to use it
*/

class DailyEdits {

	public static function onMarkPatrolledDB(&$rcid, &$article) {
		if ($article) {
			$t = $article->getTitle();
			if ($t && $t->exists() && $t->getNamespace() == NS_MAIN) {
				$aid = $t->getArticleId();
				$ts = wfTimestampNow();
				$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp) VALUES ($aid, '$ts') 
					ON DUPLICATE KEY UPDATE de_timestamp = '$ts'";
				$dbw = wfGetDB(DB_MASTER);
					$dbw->query($sql, __METHOD__);
				try {
				} catch(Exception $e) {}
			}
		}
		return true;
	}
}

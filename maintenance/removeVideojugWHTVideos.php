<?
	require_once('commandLine.inc');
	$dbw = wfGetDB(DB_MASTER);
	$sql = "select vt_id, vd_data from video_titles left join video_data on vt_id=vd_id where vt_source='wonderhowto'";
	$res = $dbw->query($sql);
	$ids = array();
	while ($row = $dbw->fetchObject($res)) {
		if (preg_match("@<media:credit role=\"producer\".*videojug@im", $row->vd_data)) {
		#if (preg_match("@<media:text.*videojug@im", $row->vd_data)) {
		 	$ids[] = $row->vt_id;
		}
	}
	$xx = implode(", ", $ids);
	$dbw->query("delete from video_titles where vt_id in ($xx) and vt_source='wonderhowto'");
	$dbw->query("delete from video_data where vd_id in ($xx) and vd_source='wonderhowto'");
	$dbw->query("delete from video_links where vl_id in ($xx) and vl_source='wonderhowto'");
	echo "deleted the following ids from wonderhowto:" . print_r($ids, true) . "\n";

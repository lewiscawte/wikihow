<?
	require_once('commandLine.inc');
	$dbw = wfGetDB(DB_MASTER);
	$res = $dbw->query(
		"SELECT rc_cur_id,rc_title, rc_timestamp from recentchanges 
		WHERE rc_new = 1 AND rc_namespace=0 ");
	while ($row = $dbw->fetchObject($res)) {
		$count = $dbw->selectField('newarticlepatrol', 'count(*)', array('nap_page'=>$row->rc_cur_id));
		if ($count == 0) {
			echo "{$row->rc_title}\n";
			$dbw->insert("newarticlepatrol", array('nap_page'=>$row->rc_cur_id, 'nap_timestamp'=>$row->rc_timestamp, 'nap_patrolled'=>0));	
		}
	}
	


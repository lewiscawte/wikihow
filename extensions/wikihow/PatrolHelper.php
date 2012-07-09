<?

	function wfGetRCPatrols ($rcid, $hi, $low, $curid) {
		$dbr = &wfGetDB(DB_SLAVE);
		$res = $dbr->select( 'recentchanges',
			array('rc_id'),
			array('rc_id <= ' . $hi,
					'rc_id >= ' . $low,
				  'rc_cur_id = ' . $curid,
					'rc_patrolled <> 1'
				),
			"wfGetRCPatrols"
		);
		$result = array();
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$result[] = $row->rc_id;
		}
		$dbr->freeResult($res);
		return $result;
	
	}
?>

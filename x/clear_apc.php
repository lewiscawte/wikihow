<?

$action = @$_REQUEST['action'];
if ($action == 'info-only') {
	# get cache stats
	$info = apc_cache_info();
	print "total hits = {$info['num_hits']}<br>\n";
	print "total misses = {$info['num_misses']}<br>\n";
	print "total entries = {$info['num_entries']}<br>\n";
	print "<br>\n";

	# sort data
	$data = array();
	$hits_col = array();
	$filename_col = array();
	foreach ($info['cache_list'] as $i => $file) {
		$hits = intval($file['num_hits']);
		$filename = $file['filename'];
		$hits_col[] = $hits;
		$filename_col[] = $filename;
		$data[] = array('hits' => $hits, 'filename' => $filename);
	}
	array_multisort($hits_col, SORT_DESC, $filename_col, SORT_ASC, $data);

	# print data
	print "<pre>\n";
	print "  hits       php file\n";
	print "-----------------------------\n";
	foreach ($data as $row) {
		print sprintf('%10s', strval($row['hits'])) . "  {$row['filename']}\n";
	}
	print "</pre>\n";
} else {
	# clear APC cache
	apc_clear_cache();
	print "cache cleared\n";
}


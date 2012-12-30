<?

	require_once('commandLine.inc');
	$ts = wfTimestamp(TS_MW, time() - 60*60);
	$dbr = wfGetDB(DB_MASTER);
	$count = $dbr->selectField("me", array('count(*)'), array('me_timestamp > "' . $ts . '"'));
	echo $count;

<?
//
// Re-run serps reports for either one day of the current data week (specify
// the day starting as a number, where Sunday is 0), or for the whole week
// up to the current hour (but not including it).
//

chdir( dirname( __FILE__ ) );

$opts = getopt('hd:m', array('help', 'day:', 'no-mediawiki'));
if (isset($opts['h']) || isset($opts['help'])) {
	print "usage: php rerunGetSerpsData.php [--day <0-6>] [--no-mediawiki]\n";
	print "  note: day is a number 0 to 6 where 0 is Sunday\n";
	print "    if a day is specified, only that day will be rerun.\n";
	exit;
}

$use_mw = !isset($opts['m']) && !isset($opts['no-mediawiki']);
define('USE_MEDIAWIKI', $use_mw);

if (USE_MEDIAWIKI) {
	require_once("commandLine.inc");
}

$currentHour = date("w") * 24 + date("G");
$sleepMinutes = 20;
$sleepSecs = $sleepMinutes*60;
$dataScript = dirname(__FILE__) . '/getSerpsData.php';

if (isset($opts['d'])) $opts['day'] = $opts['d'];
if (isset($opts['day'])) {
	$start = intval($opts['day']) * 24;
	$stop = min($currentHour, $start + 24);
} else {
	$start = 0;
	$stop = $currentHour;
}

//$start = 51;
//$stop = 60;
// DON'T RUN THE CURRENT HOUR
for ($i = 0; $i < ($stop - $start); $i++) {
	$hour = $start + $i;
	print "++hour start: $hour\n";
	$mw_opt = !$use_mw ? '-m' : '';
	system("php $dataScript -b $hour $mw_opt");
	print "++hour done: $hour\n";
	print "++sleep {$sleepMinutes} minutes\n";
	sleep($sleepSecs);
}

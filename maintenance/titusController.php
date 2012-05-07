<?
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$statsToCalc = TitusConfig::getNightlyStats();
$titus = new TitusDB(true);
$titus->calcLatestEdits($statsToCalc);

//$titus->calcStatsForAllPages($statsToCalc, array('LIMIT' => 10));
//$ids = array('265069');
//$titus->calcStatsForPageIds($statsToCalc, $ids);


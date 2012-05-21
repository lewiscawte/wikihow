<?
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/dynamo/dynamo.class.php");

$dynamo = new dynamo();
$dynamo->insertDaysData();



<?php
/*
 * Opens a CSV file and adds page IDs to the file.  Part of the Phodesk project.
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/maintenance/Phodesk.class.php");

if (count($argv) < 2) {
	print "usage: php phodeskAddPageIDs.php infile.csv outfile.csv\n";
	exit;
}

$infile = $argv[0];
$outfile = $argv[1];

$in = fopen($infile, 'r');
$out = fopen($outfile, 'w');

if (!$in || !$out) {
	print "error: opening a file\n";
	exit;
}

$dbr = wfGetDB(DB_SLAVE);

while (($data = fgetcsv($in)) !== false) {
	$id = Phodesk::getArticleID($data[0]);
	$data[1] = $id;

	$data[2] = '';
	if (!empty($id)) {
		$hasNoImages = Phodesk::articleBodyHasNoImages($dbr, $id);
		$images = intval(!$hasNoImages);
		$data[2] = $images;
	}

	fputcsv($out, $data);
}


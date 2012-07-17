<?php
	$doc = $_GET[doc];

	if ($doc) {
		header('Content-disposition: attachment; filename='.$doc.'.pdf');
		header('Content-type: application/pdf');
		readfile(wfGetPad('/extensions/wikihow/docviewer/pdf/'.$doc.'.pdf'));
	}
?>
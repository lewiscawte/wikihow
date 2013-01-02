<?php

require_once( 'commandLine.inc' );

$dbw = wfGetDB(DB_SLAVE); 

$titles = array();

$res = $dbw->select('suggested_titles', array('st_id', 'st_key') );

while ( $row = $dbw->fetchObject( $res ) ) {
	$titles[$row->st_id] = $row->st_key;
}

$dbw->freeResult($res);

echo "checking the keys\n";
$check = 0;
foreach($titles as $id=>$k) {
	$count = $dbw->selectField('skey', array('count(*)'), array('skey_key' => $k));
	if ($count > 0) {
		echo "found $k \n";
		$dbw->update('suggested_titles', array('st_used'=>1), array ('st_id' => $id));
	}
	$check++;
	if ($check % 100 == 0) 
		echo "looking good at $check\n";
}

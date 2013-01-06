<?php
/*
 * Test script for Bebeth
 */

global $IP, $wgTitle;
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/nfd/NFDGuardian.body.php");

$key = wfMemcKey("nfduserlog");

$var = $wgMemc->get($key);

var_dump($var);
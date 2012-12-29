<?php

// phpBB 2.x auto-generated config file
// Do not change anything in this file!

$dbms = 'mysql4';

#$dbhost = 'localhost';
#$dbhost = '10.234.169.201';
#$dbname = 'wikidb';
#$dbuser = 'wiki';
#$dbpasswd = 'freakshow';

define('IS_PROD_EN_SITE', true);
require_once('../LocalKeys.php');

$dbhost = WH_DATABASE_MASTER;
$dbname = WH_DATABASE_NAME_SHARED;
$dbuser = WH_DATABASE_USER;
$dbpasswd = WH_DATABASE_PASSWORD;

$table_prefix = 'phpbb_';

$wikiBase = '';

define('PHPBB_INSTALLED', true);

?>

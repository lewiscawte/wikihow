<?php
/*
This file is part of the Kaltura Collaborative Media Suite which allows users 
to do with audio, video, and animation what Wiki platfroms allow them to do with 
text.

Copyright (C) 2006-2008  Kaltura Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

$version = "0.5";

$secret = "9a3db4bd437c3bfb73f5baf0106f620b";
$partner_id = "328";
$subp_id = "32800";
 
$SERVER_HOST = "http://www.kaltura.com";
$SERVICE_URL = $SERVER_HOST . "/index.php/partnerservices2/";
$WIDGET_HOST = $SERVER_HOST;

$wiki_root = "/index.php";

// define if to log every hit to the server
$log_kaltura_services = true;

$send_raw_text = true;

// texts
$partner_name = "wikiHow";
$btn_txt_back = "BACK";
$btn_txt_publish = "PUBLISH" ;
$logo_url = "http://www.wikihow.com/skins/WikiHow/wikiHow.gif";

$kg_open_editor_as_special = false;//true;
$kg_allow_anonymous_users = false;
?>

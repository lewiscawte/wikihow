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
define ( 'KALTURA_NAMESPACE_STRING' , "Video" );

define ( 'KALTURA_SIMPLE_EDITOR' , 1 );
define ( 'KALTURA_ADVANCED_EDITOR' , 2 );

$kg_version = "1.0";

$partner_id = "";
$subp_id = "";
$secret = "";
$admin_secret = "";
 
$wiki_root = "/wiki/index.php";

// defines if to log every hit to the server
$log_kaltura_services = true; //true;

// texts
$partner_name = "Wiki";
$btn_txt_back = "Back";
$btn_txt_publish = "Publish" ;
$logo_url = "";

$kg_open_editor_as_special = true; //true;
$kg_open_editor_as_special_body_only = true ; //true;
$kg_allow_anonymous_users = false; //false;

$kg_installation_complete = false;

$kg_widget_id_default = 201;
$kg_widget_id_preview = 203;
$kg_widget_id_no_edit = 204;
$kg_widget_id_medium  = 205;

$kg_cw_conf_id_wiki_default = 210; 

$kg_editor_types = KALTURA_SIMPLE_EDITOR ; //| KALTURA_ADVANCED_EDITOR ;
?>

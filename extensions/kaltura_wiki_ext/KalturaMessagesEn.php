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



$kaltura_messages = array (
	'kalturainstall' => 'Video extension installation' , //- set up process for the Kaltura video extension',
	
	'kalturahelp' => ' ',
	'kalturahelpinfo' => " ",
	
	'collaborativevideo' => 'Add Video',

// KalturaCollaborativeVideoInfo
	'kalturacollaborativevideoinfo' => 'Add Video' ,
    'kalturacollaborativevideoinfologin' => 'Not logged in' ,
    'kalturacollaborativevideoinfologintext' => 'You have to be logged in to add/update Video' ,

	"err_title_taken" => "This title is already taken. Please choose another." ,
	"title_add_collaborative" => "Add Video" ,
	"title_update_collaborative" => "Update Video" ,
	"lbl_help" => "\"Add Video\" Help" ,
	"body_text_add_collaborative" =>
			"This form enables you to add a video to any Wiki page.  
			Anyone with editing permissions can add images, videos and sounds to the video, or edit it using the online video editor.<br><br>" .
			"<big><b>To add a video:</b></big><br/>" .
			"<ul>" .
			"<li>Enter a title and summary</li>" .
			"<li>Select size and position of video player  (in relation to surrounding text)</li>" .
			"<li>Click on \"Next\"</li>" .
			"<li>You will be prompted to add videos, images or sounds to it.  (You can either add media now, or skip this step and generate an \"empty\" video player.) </li>" .
			"<li>If you are in edit mode, a highlighted tag will appear at the bottom of the text editor." .
				"If you are not in edit mode, copy the tag that will appear, then go to the article page where you want to place the video player." .
				"Click on edit in the article page and paste the code anywhere on the page.</li>" .
			"</ul>" .
			"<br/>" .
			"Once the video player appears in the article page, you can upload and import video/image/audio files to the video and edit them.<br/><br/>" .
			"<br/>" ,


	"body_text_update_collaborative" => "To update the video info, modify the title or summary and click \"Update\".",


	"lbl_new_kaltura_warning" => "Note:  You can change the title of the video itself later, however you will not be able to change the name of the video article page that will be created now." ,
	"lbl_video_title" => "Video Title:" ,
	"lbl_summary" => "Summary:"  ,
	"lbl_size" => "Player Size:"  ,
	"lbl_align" => "Player Alignment:"  ,
	"btn_txt_generate" => 'Next' , //"Generate Tag" ,
	"lbl_widget_tag" => "Widget Tag:" ,
	"btn_txt_cancel" => "Cancel" ,
	"btn_txt_back" => "Back" ,
	"btn_txt_update" => "Update" ,
	"err_no_title" => "You need to specify a title for this Video" ,
	"btn_txt_help" => "What's this?" ,


// KalturaAjaxCollaborativeVideoInfo
	'kalturaajaxcollaborativevideoinfo' => ' ' ,
    'kalturaajaxcollaborativevideoinfologin' => 'Not logged in' ,
    'kalturacajaxollaborativevideoinfologintext' => 'You have to be logged in to add/update Video' ,

    'btn_txt_insert_widget_code' => 'Insert in page',
    'btn_txt_generate_tag' => 'Upload Video' , //'Generate Tag',
    "title_add_to_collaborative" => "Add to Video" ,
    "body_text_contribution_wizard" => "This is where you can add media (images, videos, audio files) to the video from various sources.<br>" .
			"Start by selecting the media type you want to add." .
			"<br><br>" ,

// KalturaAjaxCollaborativeVideoFinalStep
	'kalturaajaxcollaborativevideofinalstep' => ' ' ,
	'text_tag_inserted' => 'The video player tag below has been inserted at the bottom of the article page (highlighted):' ,
	'text_tag_should_copy' => 'Copy the video player tag below, then go to the article page where you want to place the video player. Click on the wiki edit tab and paste the tag anywhere.' ,
	'lbl_close_window' =>  'Close Window' ,
	
// KalturaContributionWizard
	'kalturacontributionwizard' => ' ' ,
	'kalturacontributionwizardlogin' => 'Not logged in' ,
	'kalturacontributionwizardlogintext' => 'You have to be logged in to add to this Video' ,



// KalturaVideoEditor
	'kalturavideoeditor' => " "  ,
	'kalturavideoeditorlogin' => 'Not logged in' ,
	'kalturavideoeditorlogintext' => 'You have to be logged in to edit this Video' ,

	"title_editor" => "Video Editor" ,


// KalturaTestPage
	'kalturatestpage' => " " ,
	'kalturavideoeditorlogin' => 'Not logged in' ,
	'kalturavideoeditorlogintext' => 'You have to be logged in to use the test page' ,

// KalturaDispatcher
	'kalturadispatcher' => " " ,

// KalturaSearch
	'kalturasearch' => "Video Search" ,

// globals
	"btn_txt_edit_page" => 'Video' ,

	"invalid_kshow_id_in_article_page" => "You have reached a page of a deleted or a  non existing Video.<br>" ,

// history list
	"alert_txt_revert" => 'Do you want to revert to this version?' ,
	'revert_to_version' => 'revert to this version' ,
	"lbl_revision" => "Revision as of" ,
	"lbl_tag_code" => "Video Player Code" ,
	"txt_tag_code" => "Copy the video player code below and paste it in any article page to display this video<br>" ,
	"lbl_search" => "Search" ,
	"lbl_history" => "History" ,
	"lbl_info" => "Info" ,
	"lbl_version" => "Version" ,
	"lbl_gallery" => "Clips in this version" ,
	"lbl_asset_list" => "Asset list" ,
	"lbl_empty_gallery" => "No clips in gallery" ,
	"confirm_delete_entry" => "Careful!\\nDeleting is irreversible.\\nDeleting this clip will also delete all references to it in this and previous versions.\\nA placeholder clip will appear during playback. Are you sure?",
	"lbl_links_to_article" => "Links to article" ,
	"lbl_empty_links" => "No links to this article" ,
	"lbl_seconds" => "sec",
	'lbl_btn_delete' => "Delete" ,
	'lbl_already_deleted' => "Deleted" ,

	"new_version" => "*NEW*" ,

	"update_article_new" => "New " ,
	"update_article_revert" => "Reverted to version" ,
	"update_article_contrib" => "Contributor" ,
	"update_article_info_change" => "Info change" ,
	"update_article_editor" => "Editor" ,

);

$kaltura_objects = array (
	"list_widget_size" => array ( 'L' => 'Large' , 'M' => 'Medium' ) ,
	"list_widget_align" => array ( 'R' => 'Right' , 'C' => 'Center' , 'L' => 'Left' , '_' => 'None' )  ,

);

?>

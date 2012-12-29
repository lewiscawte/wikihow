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



require_once ( "wiki_helper_functions.php" );
require_once ( "kalturaapi_php5_lib.php" );

class KalturaCollaborativeVideoInfo extends SpecialPage
{
	const DISPLAY = 0;
	const CREATE_KALTURA = 1;
	const GET_KALTURA = 2;
	const UPDATE_KALTURA = 3;

	private $extra_params = null;

	function KalturaCollaborativeVideoInfo( $call_impl_only = false ) {
		if ( ! $call_impl_only )
		{
			SpecialPage::SpecialPage("KalturaCollaborativeVideoInfo");
			kloadMessages();
		}
	}

	// this is the regular interface of specialPages
	function execute( $par ) {
		return $this->executeImpl( $par );
	}


	// This method is run in 2 modes - one as a special page and the other as the edit version of the kaltura article.
	// TODO - split into 2 pages:
	// 1 - special page for generating the widgets
	// 2 - edit mode of kaltura article
	// they are different flows anyway !
	function executeImpl( $par , $extra_params = null )
	{
		global $wgRequest, $wgOut , $wgUser;
		global $wgEnableUploads;
		global $kg_allow_anonymous_users;
		global $kg_inplace_cw;
		global $kg_widget_id_default;
		
		$this->extra_params = $extra_params;

		if ( !verifyUserRights() )
		{
			ksetcookie( "kshow_id" , $this->kgetText( 'kshow_id' ) ) ;
			ksetcookie( "kwid" , $this->kgetText( 'kwid' ) ) ;
			$wgOut->loginToUse( );//'kalturakshowidrequestnologin', 'kalturakshowidrequestnologintext' , array( $this->mDesiredDestName ) );
			return;
		}

		# Check blocks
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		$kaltura_user = getKalturaUserFromWgUser ( );

		$this->setHeaders();

		if ( $extra_params )
		{
			// in case of edit mode
			$page_title = ktoken ( 'title_update_collaborative' );
		}
		else
		{
			$page_title = ktoken ( "title_add_collaborative" );
		}

		$wgOut->setPagetitle( $page_title );//" (" . time() .")" );

		// TODO - get rif of this code after the new/edit split !
		// 2 ways to indicate the new kaltrau:
		// ?nk=t - for normal links
		// /nk at the end of the URL - for links from the upper menu in the sidebar
		$url = @$_SERVER["PATH_INFO"];
		$new_kshow = ( $this->kgetText( 'nk' ) != null ) || ( strpos ( $url , "nk" ) > 0 );
		$widget_id = null;

		if ( ! $new_kshow  )
		{
			$kshow_id = $this->kgetText( 'kshow_id' );
			kresetcookie ( 'kshow_id' );
			$kwid_str = $this->kgetText( 'kwid' );
			$kwid = kwid::fromString( $kwid_str );
		}
		else
		{
			// if newkshow -> remove the cookie and start from scratch
			$kshow_id = null;
			kdeletecookie( 'kshow_id' ); // delete the cookie so it won't drag along the session
			$kwid_str = null;
			$kwid = new kwid();
			kdeletecookie( 'kwid' ); // delete the cookie so it won't drag along the session

		}

		$kshow_name = $this->kgetText( 'kshow_name' );
		$kshow_description = $this->kgetText( 'kshow_description' );

		$embed_str = "";
		$submitted = $this->kgetText( 'kaltura_submitted' );
		$res = "";

		$back_url =   $this->kgetText( "back_url" );
		$back_count = $this->kgetText( "back_count" , 0 );
		$back_count++;

		$mode = self::DISPLAY;
		// if arrived with kshow_id - assume submitted
		if ( $kshow_id )
		{
			if ( $submitted )	$mode = self::UPDATE_KALTURA;
			else $mode = self::GET_KALTURA;
		}
		else
		{
			// no kshow - this should be the first time -> create
			if ( $submitted ) $mode = self::CREATE_KALTURA;
		}

		// in case of edit mode
		if ( $this->kgetText( "form_action" ) )
		{
			// this means we are now under the "edit" action of a kaltura article
			$form_action = $this->kgetText( "form_action" );
		}
		else
		{
			// this means we are in the special page
			$titleObj = SpecialPage::getTitleFor( get_class() ) ;//'Userlogin' );
			$form_action = $titleObj->getLocalUrl( "" ) ;//"#form1" );
		}

		$already_exists = false;
		$result_info = "";

		$widget_size = $this->kgetText ( 'widget_size' , 'L' );
		$widget_align = $this->kgetText ( 'widget_align' , 'R' );

		$createCollaborativeVideoLink = "";
		
		// can be both - update & submitted =
		if ( $mode != self::DISPLAY )
		{
			$kaltura_services = kalturaService::getInstance ( $kaltura_user );

			// TODO - handle errors !!!
			if ( $mode == self::GET_KALTURA )
			{
				// getkshow
				$params = array ( "kshow_id" => $kshow_id ,
					 /*"metadata" => "true" */);
				$res = $kaltura_services->getkshow( $kaltura_user , $params );

				$already_exists = true;
			}
			elseif ( $mode == self::UPDATE_KALTURA )
			{
				// updatekshow
				$params = array ( "kshow_id" => $kshow_id ,
//					"kwid" => $kwid_str,
					 "kshow_name" => $kshow_name ,
					 "kshow_description" => $kshow_description ,
					 /*"metadata" => "true" */);

				$res = $kaltura_services->updatekshow( $kaltura_user , $params );

				$prev_kshow_name = @$res["result"]["old_kshow"]["name"];
				$prev_kshow_description = @$res["result"]["old_kshow"]["description"];
				$prev_kshow_indexedCustomData3 = @$res["result"]["old_kshow"]["indexedCustomData3"];
				if ( $prev_kshow_name != $kshow_name || $prev_kshow_description != $kshow_description )
				{
/*					
					// get the widget_id from the 
					$temp_kwid = new kwid();
					$temp_kwid->kshow_id = $kshow_id;
					$temp_kwid->article_name = $prev_kshow_indexedCustomData3;
					$widget_id = KalturaNamespace::getWidgetIdFromArticle( $temp_kwid );
					// 	update the article only if name or description changed
					// create a kwid with the original name ($prev_kshow_indexedCustomData3) NOT the current name !!!!
					$kwid = kwid::generateKwid( $kshow_id , $prev_kshow_indexedCustomData3 , $widget_id );
*/
					// was set by the caller or by post data
					//$kwid = kgetText( "kwid" ) ; 
					$watch_this = true;
					KalturaNamespace::updateThisArticle( false , $kwid , ktoken ( "update_article_info_change")  , false , $watch_this , $kshow_name );
				}

				// now - if there is a return_url, redirect there
  				if ( $back_url )
  				{
  					// now - delete the cookies
  					kdeletecookie( 'kshow_id' ); // delete the cookie so it won't drag along the session
					$wgOut->redirect ( $back_url );
					return;
  				}

				$result_info = "<b>Updated</b>";

				$already_exists = true;
			}
			elseif ( $mode == self::CREATE_KALTURA )
			{
				// addkshow
				$params = array ( "kshow_name" => $kshow_name ,
					 "kshow_description" => $kshow_description ,
					 "kshow_indexedCustomData3" => $kshow_name, // use indexedCustomData3 for the first name od the kshow
					 "allow_duplicate_names" => "false" ,
					 /*"metadata" => "true" */);
				$res = $kaltura_services->addkshow( $kaltura_user , $params );

				$error = @$res["error"][0];

				if ( $error )
				{
					$already_exists = ( $error['code'] == "DUPLICATE_KSHOW_BY_NAME" ); // already exists
					if ( $already_exists )
					$result_info = "<b style='color:red;'>" . ktoken ( "err_title_taken" ) . "</b>";
				}
				else
				{
					// TODO - PERFORMACE: make multi-request together with addkshow
					$kshow_id = @$res["result"]["kshow"]["id"];
					// use this for the partner_data to be passed on to the js from the player	
					$url_title = Title::newFromText( $kshow_name );
					$original_url = $url_title->getFullURL();
					$partner_data = "<xml><pd_article_name>$kshow_name</pd_article_name><pd_original_url>$original_url</pd_original_url></xml>"; 	
					
					$params = array ( "widget_kshowId" => $kshow_id , 
						"widget_sourceWidgetId" => $kg_widget_id_default ,					
						"widget_partnerData" => $partner_data ,
						"widget_securityType" => 1 ); // security type = none					
					$widget_res = $kaltura_services->addwidget( $kaltura_user , $params );
				}
			}
			else
			{
				// ERROR !
			}

			$kshow_id = @$res["result"]["kshow"]["id"];
			$kshow_name = @$res["result"]["kshow"]["name"];
			$kshow_description = @$res["result"]["kshow"]["description"];
			$kshow_version = @$res["result"]["kshow"]["version"];
			$widget_id  = @$widget_res["result"]["widget"]["id"];
			
			$kwid = kwid::generateKwid( $kshow_id , $kshow_name , $widget_id );
			$kwid_str = ( $kwid != null ? $kwid->toString() : "" );

			if ( $res )
			{
				// dont' use the version for the embed code - it will fix the altura on a specific version rathern than
				// leave it up-to-date
				$kshow_version = null;
				$embed_str = createWidgetTag( $kwid , $widget_size , $widget_align , $kshow_version );
			}

			$wgOut->setPagetitle( $page_title . " [" .
				 ( $kshow_name ? "$kshow_name" : "" ) .
				"]");

			$should_update_kaltura_article = !$already_exists && $kshow_id!= null;

			kalturaLog( "Submitted kshow_id: $kshow_id, kshow_name: $kshow_name");

			if ( $should_update_kaltura_article )
			{
				$version = @$res["result"]["kshow"]["version"];
				$watch_this = true;
				KalturaNamespace::updateThisArticle( true , $kwid ,  ktoken ( "update_article_new")  , false , $watch_this , $kshow_name );
			}
		}

		if ( $wgUser != null )
		{
			$html = "";//"$form_action<br/>";

			if ( $mode == self::DISPLAY || $mode == self::CREATE_KALTURA )
			{
				// in this case - ignore the kshow_id - this is for now the indicator for GET or UPDATE
				$kshow_id= "";

/*				
$back_to_previous_page = "<a href='javascript:history.go(-{$back_count})'>" . ktoken ( "btn_txt_back" ) . "</a>";
$a = $back_to_previous_page . "<br/>" .
	"<div style='border:1px solid #999; padding: 8px; background-color: lightyellow; width:60%'>" .
		ktoken ( "body_text_add_collaborative" ).
	"</div>" .
	"<br/>" .
		ktoken ('body_text_add_collaborative_instructions') .
	"<br/>" ;

				$html .= $a ;
*/
$createCollaborativeVideoLink = createCollaborativeVideoLink();
$html =<<< HTML
This functionality enables you to add a video to any Wiki page. 
<br />
Anyone with editing permissions can add images, videos and sounds to the video, or edit it using the online video editor.
<br />
<br />
To add a video:
<ul>
<li>Click <a href='#' onclick='{$createCollaborativeVideoLink}'>Add Video</a> here or in the wiki toolbox</li> 
<li>Enter a title and summary </li>
<li>Select size and position of video player (in relation to surrounding text)</li> 
<li>Click on "Next" </li>
<li>You will be prompted to add videos, images or sounds to it. (You can either add media now, or skip this step and generate an "empty" video player.)</li> 
<li>If you are in edit mode, a highlighted tag will appear at the bottom of the text editor.  
	<br />If you are not in edit mode, copy the tag that will appear, then go to the article page where you want to place the video player.
	<br />Click on edit in the article page and paste the code anywhere on the page.</li> 
</ul>
Once the video player appears in the article page, you can upload and import video/image/audio files to the video and edit them.</li>

HTML;
			$html .= "<script>hookEvent('load', pageLoaded);\nfunction pageLoaded() {" . createCollaborativeVideoLink() . "}</script>";
			
			}
			elseif ( $mode == self::UPDATE_KALTURA || $mode== self::GET_KALTURA )
			{
				$html .=
	"<br/>" . ktoken ( "body_text_update_collaborative" ) . " <br/>" ;
		

			$html .= "<a name='form1'></a>" .
				"<form method='post' action='" . $form_action . "'>" .
					"<input type='hidden' name='kaltura_submitted' value='true'>" .
					"<input type='hidden' name='user_name' value='{$kaltura_user->puser_name}'>" .
					"<input type='hidden' name='user_id' value='{$kaltura_user->puser_id}'>" .
					"<input type='hidden' name='kshow_id' value='$kshow_id'>" .
					"<input type='hidden' name='kwid' value='{$kwid_str}'>" .
					"<input type='hidden' name='back_url' value='$back_url'>" .
					"<input type='hidden' name='back_count' value='$back_count'>" .
					"<table>" .
					"<tr><td></td><td id='result_info'>$result_info<td></tr>" .
					"<tr><td></td><td>" . ktoken ( 'lbl_new_kaltura_warning' ) .  "</td>" .
					"<tr><td>" . ktoken ( 'lbl_video_title' ) .  "</td>" .
					"<td><input type='text' size='50' name='kshow_name' id='kshow_name' value='$kshow_name'></td></tr>" .
//					"<span id='generating'></span>" .
					"<tr><td>" . ktoken ( 'lbl_summary' ) . "</td>" .
					"<td><textarea cols='60' rows='3' name='kshow_description' id='kshow_description'>$kshow_description</textarea></td></tr>" ;
			}
			
			if ( $mode == self::DISPLAY || $mode == self::CREATE_KALTURA )
			{
/*				
				$html .=
					"<tr height='60'><td></td> " .
					"<td>" .
					ktoken ( 'lbl_size' ) .  " " . createSelectHtml ( "widget_size" , kobject ( "list_widget_size") , $widget_size) . " " .
					ktoken ( 'lbl_align' ) . " " . createSelectHtml ( "widget_align" , kobject ( "list_widget_align") , $widget_align ) . " " .
					"<input type='submit' name='submit' value='" . ktoken ( 'btn_txt_generate' ) . "' onclick='return validateForm()' style='margin-left:40px'>" .
					"</td></tr>".
					"<tr><td>" .ktoken ( 'lbl_widget_tag' ) . "</td>" .
					"<td><textarea style='	' cols='60' rows='2' readonly='readonly' name='dummy'>$embed_str</textarea></td></tr>" ;
				if ( $mode == self::CREATE_KALTURA )
				{
					if ( $kg_inplace_cw )
					{
					   	$extra_params = array( "inflow" => kgetText ( "inflow" ) , "kwid" =>  $kwid_str , "kshow_id" => $kwid->kshow_id );
						$extra_params_str = http_build_query( $extra_params , "" , "&" )		;
						
						$url = Skin::makeSpecialUrl ( "KalturaContributionWizard" , $extra_params_str );
						$html .= "<script>kalturaInitModalBox ( '$url' ) ;</script>";
					}
				}
*/
			}
			elseif ( $mode == self::UPDATE_KALTURA || $mode== self::GET_KALTURA )
			{
				$html .= "<tr><td></td> " .
					"<td>" .
					"<input type='submit' name='submit' value='" . ktoken ( 'btn_txt_update' ) . "'  onclick='return validateForm()'>" .
					" <a href='{$this->kgetText("back_url")}'>" . ktoken ( 'btn_txt_cancel' ) . "</a>" .
					"</td></tr>";
			}

				$html .= "</table>" .
					"</form>" ;
			$wgOut->addHTML( $html );
		}
		else
		{
			$wgOut->addHTML( "User is not logged in" );
		}

		// javascript to make sure the form is valid
		$javascript = "<script type='text/javascript'>\n" .
			"function validateForm () { \n" .
			" var kshow_name = document.getElementById ( 'kshow_name' );\n" .
			" var trimmed = (kshow_name.value).replace(/^\s+|\s+$/g, '') ;" .
			" if ( trimmed == '' ) {\n".
			" alert ( '" . ktoken ( 'err_no_title' ) . "' );\n" .
			" return false;" .
			" }\n" .
			" var result_info = document.getElementById ( 'result_info' );\n" .
			" result_info.style.visibility='hidden';\n" .
			" return true;" .
			"}\n" .
			"</script>";

		$wgOut->addScript ( $javascript );

	}


	private function kgetText ( $param_name , $default_value = null )
	{
		if ( isset ( $this->extra_params[$param_name] ))
			return $this->extra_params[$param_name];
		return kgetText ( $param_name , $default_value );
	}


}

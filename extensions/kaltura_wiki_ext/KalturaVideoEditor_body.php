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



global $IP;
require_once($IP.'/includes/Wiki.php');
require_once ( "wiki_helper_functions.php" );
require_once ( "kalturaapi_php5_lib.php" );

class KalturaVideoEditor extends SpecialPage
{
	function KalturaVideoEditor() {
		SpecialPage::SpecialPage("KalturaVideoEditor");
		kloadMessages();
	}

	function execute( $par ) {
		
		global $wgRequest, $wgOut , $wgUser;
		global $wgEnableUploads;
		global $partner_id, $subp_id, $partner_name;
		global $btn_txt_back, $btn_txt_publish, $logo_url;
		global $kg_allow_anonymous_users ,	$kg_open_editor_as_special, $kg_open_editor_as_special_body_only ;
		global $kg_editor_types;
		 
		// according to the parnters preferences, there are 3 options -
		// 1. only simple
		// 2. only advanced
		// 3. both -> prefer the simple but if the roughcut was ever edited by advanced - stick to it
		if ( $kg_editor_types == KALTURA_SIMPLE_EDITOR )
		{
			// only simple editor - 
			return $this->easyedit ( $par );
		}
		elseif ( $kg_editor_types & KALTURA_SIMPLE_EDITOR )  
		{
			// simple is supporeted - check runtime parameter to figure out which to open
			// store the cookie incase lost in  submition
			$kaltura_editor = kgetText( 'kaltura_editor' );
			ksetcookie( "kaltura_editor" ,  $kaltura_editor ) ;
			if ( $kaltura_editor !=  KALTURA_ADVANCED_EDITOR ) 
			{
				return $this->easyedit ( $par );
			}
			
		}
		// else ... must be advanced only  - continue with code
		
		if ( !verifyUserRights() )
		{
			ksetcookie( "kshow_id" , kgetText( 'kshow_id' ) ) ;
			ksetcookie( "kwid" , kgetText( 'kwid' ) ) ;
			ksetcookie( "back_url" , kgetText( 'back_url' ) ) ;

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

//		$this->setHeaders();
 		$kaltura_user = getKalturaUserFromWgUser ( );

		$wgOut->setPagetitle( ktoken ( "title_editor" ) );

		$kshow_id = kgetText( 'kshow_id' );
		$kwid_str = kgetText( 'kwid' );
		$kwid = kwid::fromString( $kwid_str );
		$original_page_url = kgetText ( 'back_url' );
		kresetcookie ( 'kshow_id' );
		kresetcookie ( 'kwid' );
		kresetcookie ( 'back_url' );
//		kdeletecookies ( array ('kshow_id', 'back_url'  ) ); // cookie will be deleted so it won't be dragged as part of the session

		$domain = WIDGET_HOST; //"http://www.kaltura.com";
		
		$user_name = $kaltura_user->puser_name; // $wgUser->getName();
		$user_id = $kaltura_user->puser_id; //$wgUser->getId();


		// this page has 2 purposes:
		// a launch page for the editor for a specific kshow_id
		// the return page from the editor -> will update the kshow_id's article
		$from_editor = kgetText ( "keditor" );
//		kresetcookie ( 'keditor' );
		kdeletecookie( "keditor" );

		$res = "";
		if ( ! empty ( $from_editor) )
		{
			//  kaltura_update is what the editor returns after the editor actually modifies the kshow
			$kshow_id_to_update = kgetText ( "kaltura_modified"  );
			if ( $kshow_id_to_update )
			{
				// see if the same as what we stored in the cookie
				$edited_kshow_id = kgetText( "edited_kshow_id" , 3 ) ; // only from cookie
				if ( $edited_kshow_id != $kshow_id_to_update )
				{
					// Strange !! - we'll update some other kshow's article
				}

				$kwid_str = kgetText( "edited_kwid" , 3 ); // fetch the kwid from the cookie
				$kwid = kwid::fromString( $kwid_str );
				// TODO !!!
				// update the article !!
				// get the articel in hand - it will be of type KalturaNamespace
				// TODO - maybe not believe the return value - verify with the cookie
				$watch_this = true ; // does user want to watch the changes ?
				KalturaNamespace::updateThisArticle( false , $kwid ,  ktoken ( "update_article_editor")  , false , $watch_this );
			}

			$original_page_url = kgetText( "original_page_url" );
			$wgOut->redirect ( $original_page_url );
		}
		elseif ( ! empty ( $kshow_id  ) )
		{
			// 	start a session
			$kaltura_services = kalturaService::getInstance( $kaltura_user );
			$ks = $kaltura_services->getKs();

			if ( !$ks )
			{
				// ERROR - starting a session to kaltura failed !!
				$error = "FATAL - cannot connect to kaltura";
				$wgOut->addHTML( $error );
			}

			// set the back_url for this page to return to once back from the editor
			ksetcookie( "original_page_url" , $original_page_url );
			ksetcookie( "edited_kshow_id" , kgetText( 'kshow_id' ) ) ;
			ksetcookie( "edited_kwid" , kgetText( 'kwid' ) ) ;

			// tell the editor to return to this page, handle wiki-update and then redirect back to $original_page_url

			$titleObj = SpecialPage::getTitleFor( get_class() ) ;
			$back_url = $titleObj->getFullUrl( "keditor=true" ); // This will be used as an indicator when the editor returns to this page - go back to original u

			$first_visit = kgetText( "visit$kshow_id" );

    //		$var_names = array( "partner_id" , "subp_id" , "logo_url" , "btn_txt_back" , "btn_txt_publish" ,/* "back_url"*/ );
		    $editor_params = array( "partner_id" => $partner_id ,
		    						"subp_id" => $subp_id ,
		    						"uid" => $kaltura_user->puser_id ,
		    						"ks" => $ks ,
		    						"kshow_id" => $kshow_id ,
		    						"partner_data" => $kwid->toString() ,
		    						"logo_url" => $logo_url ,
		    						"btn_txt_back" => $btn_txt_back ,
		    						"btn_txt_publish" => $btn_txt_publish ,
									"back_url" => $back_url ,
									"partner_name" => $partner_name );

			if ( $first_visit )
			{	$editor_params ["first_visit"] ="1";
			}
			else
			{
				// remember that the editor was visited for this kshow_id - skip the message box
				ksetcookie( "visit$kshow_id" , 1 , 30 );
			}

			$editor_params_str = http_build_query( $editor_params , '' , "&" )		;

			$editor_url = $domain . "/index.php/edit?$editor_params_str";

			// instead of redirecting - open editro in current special page
			if ( $kg_open_editor_as_special )
			{
				$wgOut->setPagetitle("" );//" (" . time() .")" );

				$iframe_html = "<iframe src='$editor_url' width='100%' height='800px'></iframe>";
				$wgOut->addHtml ( $iframe_html );
				// to allow full-screen:

				if ( $kg_open_editor_as_special_body_only )
					$wgOut->setArticleBodyOnly ( true );
			}
			else
			{
				TRACE ( $editor_params );
				$wgOut->redirect ( $editor_url );
			}
		}
		else
		{
			$wgOut->addHTML( createInternalErrorPage ( "Error creating Video.<br>Please try again later" ) );
		}
	}
	
	private function easyedit (  $par )
	{
		global $wgRequest, $wgOut , $wgUser;
		global $wgEnableUploads;
		global $partner_id, $subp_id, $partner_name;
		global $btn_txt_back, $btn_txt_publish, $logo_url;
		global $kg_allow_anonymous_users ,	$kg_open_editor_as_special, $kg_open_editor_as_special_body_only ;
		global $kg_editor_types, $kg_se_conf_id_wiki_default;
		
		$kshow_id = kgetText( 'kshow_id' );
		$kwid_str = kgetText( 'kwid' );
		$kwid = kwid::fromString( $kwid_str );
				
		$wgOut->setArticleBodyOnly ( true );
		
		$domain = WIDGET_HOST;
		$host = getHostId();
		
		$ui_conf_id = $kg_se_conf_id_wiki_default ;
		//$swf_url = "/swf/simpleeditor.swf";
		$swf_url = "/kse/ui_conf_id/{$ui_conf_id}";
			
		$lang = "en" ;
		$height = 546;
		$width = 890;

		$start = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">' . "\n" .
			'<head>' . "\n";
		$end = "\n</head>";
		
		// see if this is the second time - after the editor is supposed to close
		$from_editor = kgetText ( "fromeditor" );
		// this will happen when the easy editor closes
		if ( ! empty ( $from_editor ) )
		{
			$watch_this = true ; // does user want to watch the changes ?
			KalturaNamespace::updateThisArticle( false , $kwid ,  ktoken ( "update_article_editor")  , false , $watch_this );
			$js_for_easyedit =  "<script type='text/javascript'>window.top.kalturaRefreshTop(); /* kalturaCloseModalBox();*/</script>";
			$wgOut->addHTML( $start . $js_for_easyedit . $end );
			return;
		}

		// create javascript for simple editor to call - if enabled the advnaced - add the advancedEditor method
		$js_for_easyedit = "<script type='text/javascript'>" .
			( $kg_editor_types & KALTURA_ADVANCED_EDITOR ? "function advancedEditor() { parent.kalturaOpenEditor ( window.location ,2,$kg_editor_types );}\n " : "" ) . // js function to switch from the simple editor to the advanced one
			"function editorSave( modified ){}\n" .
			"function editorBack ( modified ){\n" .
//			"	modified=1;\n" .
			"	if ( modified == 1 ) {window.location=window.location + '&fromeditor=true'; }\n" .
			" 	else { kalturaCloseModalBox(); }\n" .
			"}" .
			"</script>";
			
		$js_for_easyedit .= getKalturaScriptAndCss();
		
$css = <<< CSS_FOR_HEAD
	<style type='text/css'>
		body{ margin: 0; padding:0; font-family:arial; font-size:100.2%; background-color:#262626; }
		h1{ color:#cbdb8d; font-size:1.5em; font-weight:normal; display:inline; margin-right:20px; }
		fieldset{ border:0 none; padding:6px 20px; font-size:0.9em; font-weight:bold; margin:4px 0; border:1px solid #383838; background-color:#303030; color:#ddd; }
		div.item{ margin-bottom:15px; }
		div.item label{ float:left; width:150px; margin-right:20px; line-height:1.6em }
		div.item p{ font-weight:normal; font-size:0.75em; padding:5px 0 5px 170px; }
		div.radio input, .innerWrap form label.radio input{ margin:0 6px -3px 0; border:none; width:auto; }
		div.radio label{ float:none; }
		#mbCloseBtn{ right:10px; width:14px; height:14px; background:url(images/btn_close.gif) 0 0 no-repeat; }
		#mbCloseBtn.type1{ color:#ccc; position:absolute; top:4px; }
		a.top{ width:14px; height:14px; overflow:hidden; position:absolute; top: 10px; z-index:101; cursor: pointer; }
		button{ clear:both; font-weight:bold; font-size:1.5em; margin-right:8px; margin-top:25px; padding:3px 12px; color:#444; }
	</style>
CSS_FOR_HEAD;
		

		

		
		$wgOut->addHTML( $start . $css . $js_for_easyedit . $end );
		
		if ( !verifyUserRights() )
		{
			$title = ktoken ( "title_editor" );			
$error = <<< TXT
	<a id="mbCloseBtn" class="top" title="Close" href="#" onclick="kalturaCloseModalBox(); return false;"></a>
		<div id="content_main" style="margin: 0; padding:24px 24px;">
			<h1>$title</h1> 
					<fieldset>
						<div class="item">

			<br />
			To edit a video, you need to be logged in.<br /><br />
			<center><button onclick="kalturaCloseModalBox(); return false;">Close</button></center>

						</div>
					</feildset>
		</div>
	</form>
TXT;
			// display text so and user is supposed to close window
			$wgOut->addHTML( $error );
			return;
		}
		
		

		
		$kaltura_user = getKalturaUserFromWgUser ( );
		$kaltura_services = kalturaService::getInstance( $kaltura_user );
		$ks = $kaltura_services->getKs();
		
		$user_name = $kaltura_user->puser_name;
		$user_id = $kaltura_user->puser_id ;
		
		if ( !$ks )
		{
			
			// ERROR - starting a session to kaltura failed !!
			$error = "FATAL - cannot connect to kaltura";
			$wgOut->addHTML( $error );
		}
		
		
		$flashvars =	'entry_id=' . kgetText ( "entry_id" , "-1" ) .
						'&kshow_id='. kgetText ( "kshow_id" )  .
						'&partner_id='. $partner_id.
						'&subp_id='. $subp_id.
						'&uid='. $user_id.
						'&ks=' . urlencode( $ks ).
						'&host=' .$host.
						'&backF=editorBack'.
						'&saveF=editorSave'.
						'&lconid='. $lang;

		   $widget = '<object id="kaltura_easyedit_wizard" type="application/x-shockwave-flash" allowScriptAccess="always" allowNetworking="all" height="' . $height . '" width="' . $width . '" data="'.$domain. $swf_url . '">'.
				'<param name="allowScriptAccess" value="always" />'.
				'<param name="allowNetworking" value="all" />'.
				'<param name="bgcolor" value=#000000 />'.
				'<param name="movie" value="'.$domain. $swf_url . '"/>'.
		   		'<param name="flashVars" value="' . $flashvars . '" />' .
				'</object>';
				
		$html = "<body>$widget</body>";
		$html .= "</html>";
		$wgOut->addHTML( $html ); 
		
	}
}

?>

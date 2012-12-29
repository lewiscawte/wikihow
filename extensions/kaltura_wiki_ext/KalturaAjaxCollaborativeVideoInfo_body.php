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

// TODO - change the name of the class & reuse code with other CollborativeVideoInfo
// this part of code can be reused in the other CollborativeVideoInfo class
// it will hold the frame that submits the kshow parameter
class KalturaAjaxCollaborativeVideoInfo extends SpecialPage
{
	const DISPLAY = 0;
	const CREATE_KALTURA = 1;
	const GET_KALTURA = 2;
	const UPDATE_KALTURA = 3;

	private $extra_params = null;

	function KalturaAjaxCollaborativeVideoInfo( $call_impl_only = false ) {
		if ( ! $call_impl_only )
		{
			SpecialPage::SpecialPage("KalturaAjaxCollaborativeVideoInfo");
			kloadMessages();
		}
	}

	// this is the regular interface of specialPages
	function execute( $par ) {
		return $this->executeImpl( $par );
	}


	function executeImpl( $par , $extra_params = null )
	{
		global $partner_id , $subp_id;
		global $wgRequest, $wgOut , $wgUser;
		global $wgJsMimeType, $wgScriptPath, $wgStyleVersion, $wgStylePath;
		global $wgUseAjax;
		global $kg_inplace_cw;
		global $kg_widget_id_default;

//		$wgUseAjax = true;

		$wgOut->setArticleBodyOnly ( true );

		$this->extra_params = $extra_params;

		// ASSUME: We assume for the inframe version of this page that the user is already logged in
		$kaltura_user = getKalturaUserFromWgUser ( );

		$this->setHeaders();

		$embed_str = "";
		$submitted = $this->kgetText( 'kaltura_submitted' );
		$res = "";

		$inflow = kgetText ( "inflow" );
		
		$mode = self::DISPLAY;
		if ( $submitted ) $mode = self::CREATE_KALTURA;

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
			$form_action = $titleObj->getLocalUrl( "" );
		}
		//$titleObj->getFullURL( 'wpCookieCheck='.$type );

		$already_exists = false;
		$result_info = "";

		$widget_size = $this->kgetText ( 'widget_size' , 'L' );
		$widget_align = $this->kgetText ( 'widget_align' , 'L' );

		$kshow_id = null;
		$back_url = null;
		$kshow_name = kgetText ( "kshow_name" );
		$kshow_description = kgetText ( "kshow_description" );
		$widget_id = null;
		
		$url = kgetText ( "url" );
		
		// can be both - update & submitted =
		if ( $mode != self::DISPLAY )
		{
			$kaltura_services = kalturaService::getInstance ( $kaltura_user );

			// TODO - handle errors !!!

			if ( $mode == self::CREATE_KALTURA )
			{
				// addkshow
				$params = array ( "kshow_name" => $kshow_name ,
					 "kshow_description" => $kshow_description ,
					 "kshow_indexedCustomData3" => $kshow_name, // use indexedCustomData3 for the first name od the kshow
					 "allow_duplicate_names" => "false" , // wiki env does not allow duplicate names 
					 /*"metadata" => "true" */);
				$res = $kaltura_services->addkshow( $kaltura_user , $params );

				$error = @$res["error"][0];
//$wgOut->addHTML ( "<pre>result:<br/>" . print_r ( $res , true ) . "</pre>" );
				if ( $error )
				{
					$already_exists = ( $error['code'] == "DUPLICATE_KSHOW_BY_NAME" ); // already exists
					if ( $already_exists )
					{
						$result_info = "<b style='color:red;'>" . ktoken ( "err_title_taken" ) . "</b>";
						// in case of an error - prefer the description the user submitted
						$res["result"]["kshow"]["description"] = kgetText ( "kshow_description" );
					} 
				}
				else
				{
					// create the widget 
					// TODO - PERFORMACE: make multi-request together with addkshow
					$kshow_id = @$res["result"]["kshow"]["id"];
					// use this for the partner_data to be passed on to the js from the player
					$url = kgetText ( "url" );
					$partner_data = "<xml><pd_article_name>$kshow_name</pd_article_name><pd_original_url>$url</pd_original_url></xml>"; 	

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


			if ( $res )
			{
				// dont' use the version for the embed code - it will fix the altura on a specific version rathern than
				// leave it up-to-date
				$kshow_version = null;
				$embed_str = createWidgetTag( $kwid , $widget_size , $widget_align , $kshow_version );
			}

			$should_update_kaltura_article = !$already_exists && $kshow_id!= null;

			kalturaLog( "Submitted kshow_id: $kshow_id, kshow_name: $kshow_name");

			if ( $should_update_kaltura_article )
			{
				$version = @$res["result"]["kshow"]["version"];
				$text_to_submit = empty ( $version  ) ? $kshow_id : $version;
				$watch_this = true;
				KalturaNamespace::updateThisArticle( true , $kwid ,  ktoken ( "update_article_new")  , false , $watch_this , $kshow_name );
			}
		}

		if ( $wgUser != null )
		{
		// javascript to make sure the form is valid

// TODO - localize the js alert
$err_no_title = ktoken ("err_no_title");
$localscript = <<< LOCAL_SCRIPT
<script type='text/javascript'>
function submitForm () {

	var kshow_name = document.getElementById ( 'kshow_name' );
	if ( kshow_name == null ) return true;
	var trimmed = (kshow_name.value).replace(/^\s+|\s+$/g, '') ;
	var err_msg = document.getElementById ( 'err_msg' );
	if ( trimmed == '' ) {
		err_msg.style.visibility='visible';
		err_msg.innerHTML = '{$err_no_title}';
		return false;
	}

	err_msg.style.visibility='hidden';
	frm = document.getElementById ( 'kalturaForm' )
	frm.submit();
	return true;
}

function kalturaInit()
{
	var kshow_name = document.getElementById ( 'kshow_name' );
	if ( kshow_name != null ) kshow_name.focus();
}
</script>
LOCAL_SCRIPT;

		$javascript = getKalturaScriptAndCss() . $localscript;

		$kaltura_path = getKalturaPath();

$css = <<< CSS_FOR_HEAD
	<style type='text/css'>
		*{ padding:0; margin:0; }
		body{ margin: 0; padding:0; font-family:arial; font-size:100.2%; background-color:#262626; }
		:focus { -moz-outline-style: none; outline:none; }
		a{ color:#cbdb8d; }
		a:hover{ color:#fff; }
		#content_help fieldset{ font-weight:normal; font-size:0.7em; }
		#content_help fieldset h2{ font-weight:normal; font-size:1.5em; }

		a.top{ width:14px; height:14px; overflow:hidden; position:absolute; top: 10px; z-index:101; cursor: pointer; }

		form{ overflow:hidden; padding:24px 20px; }
		form h1{ color:#cbdb8d; font-size:1.5em; font-weight:normal; display:inline; margin-right:20px; }
		form fieldset{ border:0 none; padding:6px 20px; font-size:0.9em; font-weight:bold; margin:4px 0; border:1px solid #383838; background-color:#303030; color:#ddd; }
		form div.item{ margin-bottom:15px; }
		form div.item label{ float:left; width:150px; margin-right:20px; line-height:1.6em }
		form div.item p{ font-weight:normal; font-size:0.75em; padding:5px 0 5px 170px; }
		form div.radio input, .innerWrap form label.radio input{ margin:0 6px -3px 0; border:none; width:auto; }
		form div.radio label{ float:none; }
		/* for radio buttons */
		form div.item label.radio{ float:none; line-height:1.7em; width:auto; margin:0 20px 0 0; cursor:pointer; }
		 form div.item label.radio input{ width:auto; margin:0 5px 3px 0; vertical-align:middle; border:none; background:none; cursor:pointer; }
					 fieldset legend{ padding:0 5px; margin:0 10px; font-size:1.2em; color:#6A804A; }
		 fieldset legend span{ font-size:0.7em; font-weight:normal; }
		 form label.radio, #page_new_app form label.chkbx{ cursor:pointer; }
		 form label.radio{ margin-right:2em; }
			 form label.radio input{ margin:0 6px -2px 0; border:0 none; }
			 form label.chkbx input{ width:auto; margin:-1px 6px 0 0; vertical-align:middle; border:0 none; background-color:transparent; }
			 form label.chkbx{ cursor:pointer; }

		 label.chklabel{ cursor:pointer; }
		 input, textarea{ color:#444; width:400px; font-size:12px; font-family:arial; font-weight:bold; padding:3px; border:1px solid #AAA; background-color:#EEE; }
		 textarea{ overflow:auto; height:45px; }
		 select{ font-size:100%; font-family:arial; }
		 select option{ margin-right:10px; }
		 input:focus, textarea:focus{ color:#FF3333; background-color:#FFF; }

		 a.btn{ display:block; width:120px; height:26px; line-height:26px; font-size:1em; text-decoration:none; text-align:center; color:#333; background:url({$kaltura_path}images/btn_template.gif) 0 0 no-repeat; cursor:pointer; }
		 a.btn:hover{ background-position:0 -26px; }
		#err_msg{ width:450px; float:right; line-height:1.3em; color:red; }
	</style>
CSS_FOR_HEAD;

		$start = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">' . "\n" .
			'<head>' . "\n";
		$end = "\n</head>";
$wgOut->addHTML( $start . $css . $javascript . $end );
			$html = "<body  onload='kalturaInit()'>";

$title = ktoken('title_add_collaborative');

$lbl_new_kaltura_warning = ktoken ( 'lbl_new_kaltura_warning' );
$lbl_video_title = ktoken ( 'lbl_video_title' );
$lbl_summary = ktoken ( 'lbl_summary' );
$lbl_size = ktoken ( 'lbl_size' );
$size_radios = createRadioHtml ( "widget_size" , kobject ( "list_widget_size") , $widget_size );
$lbl_align = ktoken ( 'lbl_align' );
$align_radios = createRadioHtml ( "widget_align" , kobject ( "list_widget_align") , $widget_align );
$btn_txt_generate = ktoken ( 'btn_txt_generate' );

//$root_url = getRootUrl();
$btn_url_help = 	"javascript:window.top.location='" . Skin::makeSpecialUrl ( "KalturaCollaborativeVideoInfo" ) . "'";
$btn_txt_help = ktoken ( 'btn_txt_help' );

$help_txt = ktoken ( "body_text_add_collaborative" );

$title_help = ktoken ( "lbl_help");
if ( !verifyUserRights() )
{
$content = <<< TXT
	<a id="mbCloseBtn" class="top" title="Close" href="#" onclick="kalturaCloseModalBox(); return false;"></a>
	<a id="mbHelpBtn" class="top" title="Help" href="#" onclick="toggleHelp(); return false;" title='{$btn_txt_help}'></a>
	<form id="kalturaForm" class="addVideo">
		<div id="content_main">
			<h1>$title</h1> <a href="#" onclick="toggleHelp(); return false;">({$btn_txt_help})</a>
					<fieldset>
						<div class="item">

			<br />
			To add a video, you need to be logged in.<br /><br />
			If you have made changes to this article, save this edit, log in and then return to add the video.

						</div>
					</feildset>
		</div>
				<div id="content_help" style="display:none;">
					<h1>{$title_help}</h1>
					<fieldset>
						{$help_txt}
						<br />
						<a href="#" onclick="toggleHelp(); return false;">Go Back</a>
					</fieldset>
			</div>
	</form>

TXT;
}
else
{
$content = <<< HTML_CONTENT
	<a id="mbCloseBtn" class="top" href="#" title="Close" onclick="kalturaCloseModalBox();  return false;"></a>
	<a id="mbHelpBtn" class="top" href="#" title="Help" onclick="toggleHelp(); return false;" title='{$btn_txt_help}'></a>
			<form id="kalturaForm" class="addVideo" method='post'>
				<div id="content_main">
					<h1>$title</h1> <a href="#" onclick="toggleHelp(); return false;">({$btn_txt_help})</a>
					<fieldset>
						<div class="item">
							<label>{$lbl_video_title}</label>
							<input id="kshow_name" name="kshow_name" value="$kshow_name" type="text" />
							<p>{$lbl_new_kaltura_warning}</p>
						</div>
						<div class="item">
							<label>{$lbl_summary}</label>
							<textarea id="kshow_description" name="kshow_description" cols="30" rows="2">$kshow_description</textarea>
						</div>
						<div class="item">
							<label>{$lbl_size}</label>
							<div class="opts">
								{$size_radios}
							</div>
						</div>
						<div class="item">
							<label>{$lbl_align}</label>
							<div class="opts">
								{$align_radios}
							</div>
						</div>
						<div id="err_msg">{$result_info}</div>
						<a class="btn" href="#" onclick='return submitForm()' style="float:right; margin-top:15px; clear: both;">{$btn_txt_generate}</a>
					</fieldset>
					<input type='hidden' name='kaltura_submitted' value='true'>
					<input type='hidden' name='user_name' value='{$kaltura_user->puser_name}'>
					<input type='hidden' name='user_id' value='{$kaltura_user->puser_id}'>
					<input type='hidden' name='kshow_id' value='{$kshow_id}'>
					<input type='hidden' name='back_url' value='{$back_url}'>
					<input type='hidden' name='inflow' value='{$inflow}'>	
					<input type='hidden' name='url' value='{$url}'>
				</div>
				<div id="content_help" style="display:none;">
					<h1>{$title_help}</h1>
					<fieldset>
						{$help_txt}
						<br />
						<a href="#" onclick="toggleHelp(); return false;">Go Back</a>
					</fieldset>
				</div>
			</form>
HTML_CONTENT;
}
			$html .= $content;

			// create code to fill the value and
			if ( !$already_exists && $mode == self::CREATE_KALTURA )
			{
				// TODO - decide if we want an immediate CW
				// open the CW immediatly after the server returns with a successful result
				$kwid_str = $kwid->toString();
				$html .= "<script type='{$wgJsMimeType}'>\n";
				if ( $inflow == 1  ) // flow = edit 
				{
					$html .= "kalturaInsertWidget(\"{$embed_str}\");\n" ;
				}
					
				if ( $kg_inplace_cw )
				{
					$tag = urlencode( $embed_str );
					
					$html .= "window.location='" . Skin::makeSpecialUrl( "KalturaContributionWizard" , 
						"inframe=true&inflow={$inflow}&kshow_id={$kshow_id}&kwid={$kwid_str}&tag=" . $tag ) . "';" ;
				}
				else
				{
					$html .= " kalturaCloseModalBox();" ;
				}

				$html .= "</script>";
			$html .= "</body></html>";
				
			}


			$wgOut->addHTML( $html );
		}
		else
		{
			$wgOut->addHTML( "User is not logged in" );
		}

	}


	private function kgetText ( $param_name , $default_value = null )
	{
		if ( isset ( $this->extra_params[$param_name] ))
			return $this->extra_params[$param_name];
		return kgetText ( $param_name , $default_value );
	}


}

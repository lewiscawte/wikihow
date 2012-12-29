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

class KalturaInstall extends SpecialPage
{
	const PARTNER_SETTINGS = "partner_settings.php";
	const PARTNER_SETTINGS_TEMPLATE = "partner_settings.php.template";
	
	// this is a temp variable - it should be tur in production !
	// TODO - remove !
	const KALTURA_TEMP_REPLACE_PARTNER_SETTINGS = true;
	
	function KalturaInstall(  ) {
		SpecialPage::SpecialPage("KalturaInstall");
		kloadMessages();
	}

	/**
	 * Will help install the extension:
	 * 1. display a form will relevant data for creating a kaltura partner & local settings
	 * 2. hit kaltura with the data and receive partner data:
	 * 		partner_id
	 * 		subp_id
	 * 		secret
	 * 		admin_secret
	 * 3. copy aside the current partner_settings.php
	 * 4. create a partner_settings.php file reflecting the data from the form
	 * 5. change mode to 'alreay installed'
	 */
	function execute ( $par )
	{
		global $wgRequest, $wgOut , $wgUser;
		global $partner_id , $subp_id , $secret ;
		global $wgLanguageCode, $wgVersion, $wgSitename;
		global $wgVersion;
		
		
		if ( !verifyUserRights() )
		{
			$wgOut->loginToUse( );//'kalturakshowidrequestnologin', 'kalturakshowidrequestnologintext' , array( $this->mDesiredDestName ) );
			return;
		}

		# Check blocks
		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		// make sure only admins see this page
		if ( ! isAdmin() )
		{
			$wgOut->addHtml ( "Admins only!" );
			return;
		}

//		$wgOut->addHtml ( "<pre>" . print_r ( $wgUser , true ) . "</pre>" );

		$cms_password = "";
		$error_str = "";
		$result_txt = "";

		$helper = new installationHelper();
		
		$partner_existing_html = "";
		$step = kgetText( "step" , 0 );

		$html = getKalturaScriptAndCss();

		$correct_version = version_compare($wgVersion, '1.10.0' ) >= 0 ;
		
		// make sure that partner_setting.php can be moved before continuing
		list ( $directory_ready , $file_to_be_written ) = $this->kalturaInstallReplaceValues ( null ,true ) ;
		if ( ! $directory_ready ) 
		{
			$error_str = "Error: cannot write to file [$file_to_be_written]. Make sure you have writting permissions to this directory and file then retry.";
			$step = 0; 
$html .= <<< HTML

	<div style="margin: 10px 0pt 0pt; text-align: center;"><img src="http://www.kaltura.com/images/cms/klogo.png" alt="kaltura creating together"/></div>
		<div class="scheme1 clearfix">
			<h1>Video Extension Installation</h1>
			<div class="error">{$error_str}</div>
		</div>
	</div>
HTML;

			$wgOut->addHTML( $html );
			return;			
		}
		
		//$step = 2;
		$html_message_from_server = "";
		
		$existing_partner = ( kgetText ( "existing" , "false" ) == "true" );
		$toggle_existing_partner = $existing_partner == "true" ? "false" : "true" ;
		
		$toggle_existing_url = Skin::makeSpecialUrl( get_class ( $this) , "existing=$toggle_existing_partner" );
		
		
		
		$partner_existing_html = $helper->input( "existing" , "hidden" ,0 ) ;
		
		if( $step == 1 )
		{
			// form submitted 
			// 1. make sure all data was received properly 
			
			// 2. hit kaltura service
	 		$kaltura_user = getKalturaUserFromWgUser ();
	 		$kaltura_services = new kalturaService( $partner_id );

	 		$description = kgetText ( "partner_description" );
	 		$description .= "||" . "Wiki|$wgVersion|$wgSitename|$wgLanguageCode"; // add some data we know about this wiki - don't pass sensitive info !!
	 		
	 		$params = array (  "partner_name" => kgetText ( "partner_name" ) ,
				"partner_adminName" => kgetText ( "partner_adminName" ),
				"partner_adminEmail" => kgetText ( "partner_adminEmail" ),
				"partner_url1" => kgetText ( "partner_url1" ),
				"partner_description" => $description,
				"partner_appearInSearch" => kgetText ( "partner_appearInSearch" ),
				"cms_password" => kgetText ( "cms_password" ),
				"partner_id" => kgetText ( "partner_id" ),
				"partner_productType" => KALTURA_PARTNER_PRODUCT_TYPE_WIKI ,
				"extra_data_1" => kgetText ( "extra_data_1" ),
				"extra_data_2" => kgetText ( "extra_data_2" ),
				);
				
	 		if ( ! $existing_partner )
	 		{
				$res = $kaltura_services->registerpartner( $kaltura_user , $params );
	 		}
	 		else
	 		{
	 			$res = $kaltura_services->getpartner( $kaltura_user , $params );
	 		}
		
			$error = @$res["error"];
			
			if ( $error != null )
			{

				$code = @$error[0]["code"];
				$error_str = @$error[0]["desc"];			

				if ($code == "PARTNER_REGISTRATION_ERROR" )
				{
				  $error_str .= '.<br>If you already have a Kaltura Partner ID <a href="#" onclick="existing( true )">click here</a>';
				}
				elseif ($code == "ADMIN_KUSER_NOT_FOUND" )
				{
					$error_str .= '.<br>For a new Kaltura Partner ID <a href="#" onclick="existing( false )">click here</a>'; 
				}
				else
				{
					$error_str .= "Unknown Error";
				}
				$error_str .= "<br /><br />";
			}
			else
			{
				// the password created by the system for logging into kaltura's cms
				// 3. replace data in partner_settings.php
				$result = @$res["result"];
				
				$pid = @$result["partner"]["id"];
				
				
				list ( $source , $target , $new_settings ) = $this->kalturaInstallReplaceValues ( $result );
//	$wgOut->addHTML( "New settings:<br><pre>: " . $new_settings . "</pre><br>" );	
				$step = 2;			
				// the previous form will be displayed with disabled fields
			}
		}
		
		if ( $step == 2 )
		{
			$result_txt = "The file '$source' was modified to properly use the extension. <br />A copy was written to '$target'<br /><br />";
					
			$test_page_url = Skin::makeSpecialUrl ( "KalturaTestPage" , "addentry=true&pp=" . @$result["partner"]["secret"] ) ;//'Userlogin' );
//			$test_page_url = $test_page_title->getLocalUrl( "addentry=true&pp=" . @$result["partner"]["secret"] ) ;				
			$result_txt .= "Click here to <a href='$test_page_url'>test</a> the new extension.<br />Contact <a href='mailto:wikisupport@kaltura.com'>wikisupport@kaltura.com</a> with any questions"; 
			
			$partner_id_info = $existing_partner ? 
				"Note that your Partner ID and secret remain the same, and you will receive a confirmation email with the details.  Please save the information for future reference." :
				"Note that a Partner ID and secret have been created for you, and an email has been sent to you with this information.  Please save the information for future reference.
					The email you received also includes a link and Administrator Password to the Kaltura Content Management System, where you can track and manage all information related to the Kaltura video system." ; 
			
			$add_video_url = createCollaborativeVideoLink();
		}


	// after all is done - disable the form and display the results
		$button = '<button onclick="return submitForm()">Complete installation</button>';		
		if ( $step == 2 ) 
		{
			$helper->enable = false;
			$button = "";
		}
		$step_id = 1;
		
	$html = getKalturaScriptAndCss();

// first time or with error 
if ( $step == 1 || $step == 0  )
{
	
if ( ! $correct_version  )
{
	$link = '<p class="right"></p>';
}
else
{
if ( !$existing_partner )
{
	$link = '<p class="right">If you already have a Kaltura Partner ID <a href="#" onclick="existing( true )">click here</a></p>';	
}
else
{
	$link = '<p class="right">For a new Kaltura Partner ID <a href="#" onclick="existing( false )">click here</a></p>';
}
}


$html .= <<< HTML

	<div style="margin: 10px 0pt 0pt; text-align: center;"><img src="http://www.kaltura.com/images/cms/klogo.png" alt="kaltura creating together"></div>
		<div class="scheme1 clearfix">
		<h1>
			{$link}
			<span>Video Extension Installation</span>
		</h1>
		<form method="post" action="" id="signupForm" >
			<input name="step" value="1" type="hidden">
HTML;

if ( ! $correct_version )
{
	$html .=  <<< HTML
		</form>
		<p>
			The Kaltura Video Extension works only for Mediawiki of version 1.10 and later.<br/>You seem to have {$wgVersion} 
		</p>
HTML;
}
else
{
if ( !$existing_partner )
{
$html .=  <<< HTML
		<p>
			Fill in the form below and click "Complete installation" in order to fully install the video extension. <br>
			For additional support, please contact <a href="mailto:wikisupport@kaltura.com">wikisupport@kaltura.com</a>
		</p>
<br />
		<div class="error">{$error_str}</div>
		<div class="result">{$result_txt}</div>
			<div>{$partner_existing_html}</div>
			<div class="item">
				<label>Wiki Name</label>
				{$helper->input( "partner_name" , "text" ,50 )}
			</div>
			<div class="item">
				<label>Wiki URL</label>
				{$helper->input( "partner_url1" , "text" ,50 )}
			</div>
			<div class="item">
				<label>Administrator Name</label>
				{$helper->input( "partner_adminName" , "text" ,50 )}
			</div>
			<div class="item">
				<label>Administrator Email Address</label>
				{$helper->input( "partner_adminEmail" , "text" ,50 )}
			</div>
			<br />
			<div class="item2">
				<label class="chkbx">
					{$helper->input( "kg_allow_anonymous_users" , "checkbox" , 50 )}Allow users that are not logged in to add and edit videos
				</label>
			</div>
			<br /><br />
			<div class="item2">
				<label class="chkbx">
					<input id="SDK_terms_agreement" name="SDK_terms_agreement" value="yes" type="checkbox"> I agree to comply with the Kaltura SDK
					<a href="http://www.kaltura.com/index.php/corp/tandc" target="_blank">Terms of Use</a>
				</label>
			</div>
HTML;
}
else
{
$html .=  <<< HTML
			<p>
				Enter your existing Kaltura Partner information.  For additional support, please contact  <a href="mailto:wikisupport@kaltura.com">wikisupport@kaltura.com</a>
			</p>
			<br />
			<div class="error">{$error_str}</div>
			<div>{$partner_existing_html}</div>
			<div class="item">
				<label>Wiki Name</label>
				{$helper->input( "partner_name" , "text" ,50 )}
			</div>
			<div class="item">
				<label>Wiki URL</label>
				{$helper->input( "partner_url1" , "text" ,50 )}
			</div>
			<div class="item">
				<label>Kaltura Partner ID<br></label>
				{$helper->input( "partner_id" , "text" ,20 , "" )}
			</div>

			<div class="item">
				<label>Administrator Email Address</label>
				{$helper->input( "partner_adminEmail" , "text" ,50 )}
			</div>

			<div class="item">
				<label>Administrator Password <br></label>
				{$helper->input( "cms_password" , "text" ,20 , "" )}
			</div>
			<br />
			<div class="item2">
				<label class="chkbx">
					{$helper->input( "kg_allow_anonymous_users" , "checkbox" , 50 )}Allow users that are not logged in to add and edit videos
				</label>
			</div>
			<br /><br />
			<div class="item2">
				<label class="chkbx">
					<input id="SDK_terms_agreement" name="SDK_terms_agreement" value="yes" type="checkbox"> I agree to comply with the Kaltura SDK
					<a href="http://www.kaltura.com/index.php/corp/tandc" target="_blank">Terms of Use</a>
				</label>
			</div>

			<div class="item">
				{$html_message_from_server}
			</div>
HTML;
}

$html .= <<< HTML

	
			<br />

		<center>{$button}</center>

	</div><!-- signup -->
	</form>




	<script type="text/javascript">
		function toggleOpenEditorAsSpecial ( open_editor_as_special )
		{
			var other_elem = document.getElementById ( 'kg_open_editor_as_special_body_only_div' );
			if ( open_editor_as_special.checked = 'checked' ) other_elem.style.display = "block";
			else  other_elem.style.display = "none";
		}

		function existing ( use_existing )
		{
			document.location = '{$toggle_existing_url}';   
		}

		function submitForm () 
		{
			var form = document.getElementById ("signupForm");
			// validate 
			var SDK_terms_agreement = document.getElementById ( "SDK_terms_agreement");
			if (  SDK_terms_agreement != null )
			{
				if ( !SDK_terms_agreement.checked )
				{
					alert ( " Please agree to comply with the Terms of Use" );
					return false;
				}
			}

			form.submit();
		}
	</script>
	</div>
HTML;
}
}

if ( $step == 2 )
{
	$one_pixel = "<img src='http://www.kaltura.com/images/campaign.gif?type=wiki&pid={$pid}' alt=''>";
	$html .= <<< HTML

	<div style="margin: 10px 0pt 0pt; text-align: center;"><img src="http://www.kaltura.com/images/cms/klogo.png" alt="kaltura creating together">{$one_pixel}</div>
		<div class="scheme1 clearfix">
			<div class="container">
				<h1><span>Congratulations!</span></h1>
				<p>
					You have successfully installed the video extension.<br>
					{$partner_id_info}
					<br /><br />
					{$result_txt}
					<br /><br />
					You are welcome to visit our <a href="http://kaltura.org/community">Kaltura Communities</a>.
				</p>
				<center><button onclick='{$add_video_url}'>Create first video now</button></center>
			</div><!-- container -->
		</div><!-- scheme1 -->
	</div>

HTML;

}

		$wgOut->addHTML( $html );
	}
	
	
	// the service result holds the new partner's details, subp_id and the cms's password to login to the kaltura's cms 
	function kalturaInstallReplaceValues ( $service_result , $test = false )
	{
		$path = dirname( __FILE__ ) . "/";
		// copy aside the partner_settings.php file
		$source = $path . self::PARTNER_SETTINGS;
		$target = $source . "." . date("Y-m-d_H-i-s", time() );
		if ( self::KALTURA_TEMP_REPLACE_PARTNER_SETTINGS )
		{
			$test_file = $source;
			if ( ! $test ) @rename (  $source , $target );
		}
		else
		{
			$test_file = $target;
		}

		if ( $test ) 
		{
			$fd = @fopen( $test_file , "a+" );
			if ( $fd === FALSE ) return array ( false , $test_file );
			fclose( $fd );
			unlink($test_file);
			return  array ( true , $test_file );
		}
	
		// replace all the parameters from the template with values from the form
		// there are 3 resources we canfind the values from: 
		// 1 - the $service_results
		// 2 - the form (kgetText)
		// 3- values from GLOBALS
		$dictionary = array (
			"partner_id" => $service_result["partner"]["id"] ,
			"partner_name" => $service_result["partner"]["name"] ,
			"partner_secret" => $service_result["partner"]["secret"] ,
			"partner_admin_secret" => $service_result["partner"]["adminSecret"] ,
			"subp_id" => $service_result["subp_id"] ,
			"wiki_root" => kgetText ( "wiki_root" , $GLOBALS["wiki_root" ] ),
			"log_kaltura_services" => kgetText ( "log_kaltura_services" , $GLOBALS["log_kaltura_services" ]),
			"btn_txt_back" => kgetText ( "btn_txt_back" ),
			"btn_txt_publish" => kgetText ( 'btn_txt_publish' ),
			"logo_url" => kgetText ( "logo_url" ),
			"kg_open_editor_as_special" => kgetText ( "kg_open_editor_as_special" , "false" ),
			"kg_open_editor_as_special_body_only" => kgetText ( "kg_open_editor_as_special_body_only" , "false" ),
			"kg_allow_anonymous_users" => kgetText ( "kg_allow_anonymous_users" , "false" ) ,
			"kg_installation_complete" => "true" ,
		);
		
		$template_content = file_get_contents( $path .self::PARTNER_SETTINGS_TEMPLATE ); 
		foreach ( $dictionary as $place_holder => $new_value )
		{
			if ( $new_value === false )
				$value_to_use = "false";
			elseif ( $new_value === true )
				$value_to_use = "true";
			else
				$value_to_use = $new_value;
			$template_content = str_replace ( '{' . $place_holder . '}' , $value_to_use , $template_content );
		}
		
		// make sure all place-holders where replaced 
		if ( strpos ( $template_content , '{' ) !== FALSE )
		{
			// error while replacing template
			throw new Exception ( "Placeholder no replaced in\n$template_content\n" . print_r ( $dictionary , true ) );
		}
		// TODO - for now write the results to a new file not PARTNER_SETTINGS
		if ( self::KALTURA_TEMP_REPLACE_PARTNER_SETTINGS )
		{
			$result = @file_put_contents( $source , $template_content );
		}
		else
		{
			$result = @file_put_contents( $target , $template_content );

		}
		
		return array ( $source , $target , $template_content );
	}
}

class installationHelper
{
	public $enable = true; 
	public function input ( $name , $type , $size = 50 , $default_value = null , $onclick = null )
	{
		if( $default_value != null ) $value = $default_value;
		else
		{
			$value = kgetText ( $name );
			if( $value == null ) $value = @$GLOBALS[strtolower( $name )];
		}

		$disable = $this->enable ? "" : " disabled='disabled' readonly='true' ";
		
		if ( $type == "raw" ) return $value;
		if ( $type == "text" )
		{
			return "<input $disable name='$name' id='$name' type='$type' size='$size' value='$value' >\n";
		}
		elseif ( $type == "checkbox" )
		{
			return "<input $disable name='$name' id='$name' value='true' " . ( $onclick != null ? " onclick='$onclick' " : "" ) . " type='$type' " . ($value ? "checked='checked'" : "" ) . ">\n";
		}
		elseif ( $type == "textarea" )
		{
			@list ( $rows,$cols ) = explode ( "," , $size ) ;
			return "<textarea $disable id='$name' name='{$name}' rows='$rows' cols='$cols' >{$value}</textarea>\n" ;
		}
		
	}
}
?>

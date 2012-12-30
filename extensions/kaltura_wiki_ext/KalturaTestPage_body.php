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
class KalturaTestPage extends SpecialPage
{

	function KalturaTestPage(  ) {
		SpecialPage::SpecialPage("KalturaTestPage");
		kloadMessages();
	}

	// this is the regular interface of specialPages
	function execute( $par ) {
		return $this->executeImpl( $par );
	}

	/**
	 * TODO - remove this page on production
	 */
	function executeImpl( $par )
	{
		global $wgRequest, $wgOut , $wgUser;
		global $wgJsMimeType, $wgScriptPath, $wgStyleVersion, $wgStylePath;
		global $wgUseAjax;
		global $partner_id , $log_kaltura_services, $kg_allow_anonymous_users, $kg_widget_id_default;


		if ( ! isAdmin() )
		{
			$wgOut->addHtml ( "Admins only!" );
			return;
		}
		
		// check if alloed:
		global $secret ;
		$allow = kgetText("pp" ) == $secret ;
		if ( !$allow )
		{
			$wgOut->addHtml ( "Enter by adding 'pp=' followed by your secret" );
			return;
		}

		$wgOut->addHtml ( "<div style='font-size:12px; font-family: arial;'>" );

		if (  kgetText("log" ) != null )
		{
			$log_file_name =  getKalturaLogName();
			$maxlen = kgetText("maxlen" , 10000 );
			$def_offset = filesize( $log_file_name ) - $maxlen ;
			$offset = kgetText("offset" , $def_offset );

			$this->actionDesc ( "kaltura log: starting at: $offset, bytes : $maxlen <br>" );

			$log_content = @file_get_contents( $log_file_name , false ,null , $offset  , $maxlen  ) ;
			$wgOut->addWikiText ( "<pre>$log_content</pre>");
			return;
		}

		$wgOut->addHtml ( "This test page will help ensure that the video extension was installed successfully on your wiki.<br>Please review the following items to check the installation<br><br>" );
		
		$wgOut->addHtml ( "Note:  contact wikisupport@kaltura.com should you have any issues or questions regarding the installation and if possible, specify which of the items on the test page you are referring to.<br><br>" );
		
		$this->actionDesc ( "kaltura.css: " .
			"<span id='kaltura_test'>This should be blue. If not - the kaltura.css was not loaded properly</span><br />"
		);


		$this->actionDesc ( "kaltura.js: " .
			"<button onclick='kalturaTest(\"" . microtime(true ) . "\")'>Refresh</button>" .
			" Press the button and you should see a javascript alert pop-up � if the alert does not pop-up, the kaltura.js was not loaded properly<br />"
		);

		$this->actionDesc ( "Kaltura images: " .
			"<image src='" . getKalturaPath() . "/images/btn_help.gif" . "'>   You should see a small help image - the Kaltura images directory was installed properly<br />"
		);

		try
		{


			// check the log and tried to create it
			$log_file_name =  getKalturaLogName();
			$file_exists = file_exists( $log_file_name );
			if ( $file_exists )
			{
				$h = @fopen ( $log_file_name , "a" );
				$can_write = ( $h != null );
				if ( $h ) fclose ( $h );
			}
			$this->actionDesc ( "Kaltura log:<br>" .
				'$log_kaltura_services is set to ' . ( $log_kaltura_services ? "'true'" : 'false' ) . "<br>" .
				"When the logging is turned on, will write to file '$log_file_name'<br>" .
				"This file " . ($file_exists ?
					"already exists " .	( $can_write ? "and can be writen to" : "<b style='color:red'>BUT CANNOT BE WRITTEN TO! Please change the file and directory privileges for the log to work.</b>")
					: "does not yet exist. <b style='color:red'>Please create its directory with write-permissions</b>." ) .
				".<br>"
				);

			$this->actionDesc ( "kalturaUser:<br>" .
				'$kg_allow_anonymous_users is set to ' . ( $kg_allow_anonymous_users ? "'true'" : 'false' ) . ".<br>" .
				"Users who are not logged-in " .
					($kg_allow_anonymous_users ? "will be considered anounymous and will be allowed to use the system." : "will be forced to do so before creating or modifying a collaborative video." )
				 );

			$kaltura_user = getKalturaUserFromWgUser();
			$this->printObj( $kaltura_user );

			if ( $kaltura_user->puser_id == "" )
			{
				// user is not logged in and partner does not allow anonymous users
				$this->actionDesc ( "User is not logged-in and partner does not allow anonymous users<br>" .
					'Either login as a wiki user or, if your policy is to allow anonymous users to modify wiki pages, change <b>\'$kg_allow_anonymous_users\'</b> to <b>\'true\'</b> in the partner_settings.php file.<br>' );
				$this->actionDesc ( "<span style='color:red; font-weight:bold;'>All further tests should fail!</span><br>" );
			}

			$this->actionDesc ( "kalturaService::getInstance. Will initialize a session with Kaltura" );
			$kaltura_services = kalturaService::getInstance ( $kaltura_user );
			$this->printObj( $kaltura_services );

			$this->actionDesc ( "create a sample video (will indicate an existing kshow from the second version and onwards)" );
			$params = array ( "kshow_name" => "collvideo{$partner_id}" ,
				"kshow_description" => "Some text to be used as the summary" ,
				"allow_duplicate_names" => "false" , );
			$this->printObj( $params );
			$result = $kaltura_services->addkshow ($kaltura_user , $params );
			$this->printObj( $result );

			$kshow_id = @$result["result"]["kshow"]["id"];
			$this->actionDesc ( "Additional details about the video above:" );
			$params = array ( "kshow_id" => $kshow_id , "detailed" => "true" );
			$this->printObj( $params );
			$kshow = $kaltura_services->getkshow ($kaltura_user , $params );
			$this->printObj( $kshow );

			$should_add_entry = kgetText( "addentry" );
			if ( $should_add_entry )
			{
				$this->actionDesc ( "add an image entry to the collaborative video" );
				$params = array (	"kshow_id" => $kshow_id ,
									"entry1_mediaType" => "2" ,
									"entry1_source" => "20" ,
									"entry1_name" => "kaltura logo" ,
									"entry1_tags" => "kaltura, logo" ,
									"media1_id" => "10" );
				$this->printObj( $params );
				$entry = $kaltura_services->addentry ($kaltura_user , $params );
				$this->printObj( $entry );
			}
			else
			{
				// TODO - make the feature work !
				//$this->actionDesc ( "<b>To add an entry to the collaborative video, add '?addentry=t' to the URL.</b>" );
			}

			$kwid = kwid::generateKwid( $kshow_id , "Test" , $kg_widget_id_default );
			$this->actionDesc ( "The end result:  an interactive video player" );
			$widget = createWidgetHtml( $kwid , 'm' , 'l' , null , null , null , false , false );
//			fixJavascriptForWidget ( $widget );
			$wgOut->addHtml ( $widget );

			$wgOut->addHtml ( "</div>" );
		}
		catch ( Exception $ex )
		{

		}

	}

	function loadMessages() {

		static $messagesLoaded = false;
		global $wgMessageCache;
		if ( !$messagesLoaded ) {
			$messagesLoaded = true;

			require( dirname( __FILE__ ) . '/KalturaAjaxCollaborativeVideoInfo.i18n.php' );
			foreach ( $allMessages as $lang => $langMessages ) {
				$wgMessageCache->addMessages( $langMessages, $lang );
			}
		}
		return true;
	}

	private function printObj ( $obj )
	{
		global $wgRequest, $wgOut;
		$wgOut->addHtml ( "<pre>" . print_r ( $obj , true ) . "</pre>" );
	}

	private function actionDesc ( $str )
	{
		static $desc_count = 1;
		global $wgRequest, $wgOut;
		$wgOut->addHtml ( "<span style='background-color:lightyellow'><big><b>Test [$desc_count]</b></big> " . $this->t() . $str . "<br/></span>" );
		$desc_count++;
	}

	private function t()
	{
		$time = ( microtime(true) );
		$milliseconds = (int)(($time - (int)$time) * 1000);
		$formatted = strftime( "%d/%m %H:%M:%S." , time() ) . $milliseconds;
		return "[" . $formatted . "] ";
	}

}

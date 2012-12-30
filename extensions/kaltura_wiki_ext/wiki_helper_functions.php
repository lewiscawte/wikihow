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

define ( 'PLACEHOLDER_FOR_WIDGET_JAVASCRIPT' , "__PLACEHOLDER_FOR_WIDGET_JAVASCRIPT__");

class kalturaUser
{
	var $puser_name;
	var $puser_id;
	var $kuser_name;
	var $kuser_id;
}

// kaltura-wiki id
// class to handle the conversion of the string->params
class kwid
{
	const KWID_SEPARATOR = "|";
	var $kshow_id;
	var $partner_id;
	var $subp_id;
	var $article_name;
	var $widget_id;
	var $hash;

	public static function generateKwid ( $kshow_id, $article_name , $widget_id , $explicit_partner_id = null , $explicit_subp_id = null)
	{
		global $partner_id , $subp_id;

		$kwid = new kwid();
		$kwid->kshow_id= $kshow_id;
		$kwid->partner_id= $explicit_partner_id ? $explicit_partner_id : $partner_id;
		$kwid->subp_id= $explicit_partner_id ? $explicit_partner_id : $subp_id;
		$kwid->widget_id = $widget_id;
		// if the name includes the prefix - remove it
		if ( strpos ( strtolower( $article_name ) , strtolower ( KALTURA_NAMESPACE ) ) === 0 )
			$article_name = substr ( $article_name , strlen( KALTURA_NAMESPACE ));
		$kwid->article_name= $article_name;
		$kwid->hash = $kwid->createHash();
		return $kwid;
	}

	public function toString()
	{
		$res = 	$this->toStringNoBase64();
		return base64_encode( $res );
	}

	public function toStringNoBase64 ()
	{
		$res = 	$this->kshow_id . self::KWID_SEPARATOR .
				$this->partner_id . self::KWID_SEPARATOR .
				$this->subp_id . self::KWID_SEPARATOR .
				$this->article_name . self::KWID_SEPARATOR .
				$this->widget_id . self::KWID_SEPARATOR .
				$this->hash;
		return $res;
	}

	public static function fromString ( $base64_str )
	{
		if ( ! $base64_str)
		{
			// invalid string
			return null;
		}
		$str = @base64_decode( $base64_str );
		if ( ! $str)
		{
			// invalid string
			return null;
		}

		$kwid = new kwid();
		list ( $kwid->kshow_id , $kwid->partner_id , $kwid->subp_id ,$kwid->article_name  ,$kwid->widget_id , $kwid->hash  ) =
			 @explode ( self::KWID_SEPARATOR , $str );

		if ( $kwid->hash != $kwid->createHash() )
		{
			// invalid tamperred string
//			return null;
			return $kwid;
		}

		return $kwid;
//		return print_r ( $kwid , true );
	}

	/**
	 * will make sure the partner_id and subp_id of the current context are the same as of the kwid
	 */
	public function verifyContext ($explicit_partner_id = null , $explicit_subp_id = null )
	{
		global $partner_id , $subp_id;
		if ( $explicit_partner_id == null ) $explicit_partner_id = $partner_id;
		if ( $explicit_subp_id == null ) $explicit_subp_id = $subp_id;

		$res = ( $this->partner_id == $explicit_partner_id && $this->subp_id == $explicit_subp_id );
		return $res;
	}

	// need only 10 first characters - good enough for hashing
	public function createHash ()
	{
		global $secret;
		$res = md5 ( $this->kshow_id . $this->partner_id . $this->subp_id . $this->article_name . $this->widget_id .  $secret );
		return substr ( $res , 1 , 10 );
	}
}


function escapeString ( $str )
{
	return 	str_replace ( array ( "'" , "\n\r" , "\n" , "\r" , ), array ( "\\'" , " " , " " , " " ) , $str );
}


function formatDate ( $time )
{
	return strftime( "%d/%m/%y %H:%M:%S" , $time ) ;
}


function TRACE ( $str , $pre = false)
{
	//return ;

	global  $wgOut;

	if ( is_array ( $str ) )
	{
		$str = print_r ( $str , true );
		$pre = true;
	}

	$html = "";
	if ( $pre )
	{
		$html .= "<pre>" . "[" . time() . "] " . $str . "</pre>" ;
	}
	else
	{
		$html .= "[" . time() . "] " . $str . "<br>";
	}

	$wgOut->addHTML ( $html );
}



$log_file_name = dirname(__FILE__) . "/log/kaltura_extension_log";
$log_fh = null;
function kalturaLog ( $content )
{
	global $log_file_name;
	global $log_fh;
	global $log_kaltura_services;
	if ( ! $log_kaltura_services ) return;

	if ( $log_fh == null )	$log_fh = @fopen($log_file_name, 'a');

	if ( $log_fh != FALSE )	
	{
		$time = ( microtime(true) );
		$milliseconds = (int)(($time - (int)$time) * 1000);  
		$time_str = strftime( "%d/%m %H:%M:%S." , time() ) . $milliseconds;
		fwrite($log_fh, "(" . $time_str . ")" . $content . "\n"); // if the directory of file don't exuist - continue ...
	}
}

function closeKalturaLog ( )
{
	if ( $log_fh != null )	$log_fh = fclose($log_fh );
}

function getKalturaLogName ( )
{
	global $log_file_name;
	return $log_file_name;
}

function getKalturaUserFromWgUser ( $w_user = null )
{
	global $wgUser;
	global $kg_allow_anonymous_users;

	if ( $w_user == null )
	{
		$w_user = $wgUser;
	}

	$kaltura_user = new kalturaUser();
	if ( $w_user->isLoggedIn() )
	{
		$kaltura_user->puser_id = $w_user->getId();
		$kaltura_user->puser_name = $w_user->getName();
	}
	elseif ( $kg_allow_anonymous_users )
	{
		return getAnonymous ( $kaltura_user );
/*		
		// create an anonymous user only if allowed by partner
		// 	if Anonymous - set some default values
		$kaltura_user->puser_id = "";//"_" . $w_user->getId();
		$kaltura_user->puser_name = "__ANONYMOUS__";
*/
	}
	else
	{
		$kaltura_user->puser_id = "";
		$kaltura_user->puser_name = "";
	}

	return $kaltura_user;
}

function isAnonymous ( $name )
{
	return $name == "__ANONYMOUS__";
}

function getAnonymous ( &$kaltura_user = null )
{
	global $wgUser;
	if ( $kaltura_user == null ) $kaltura_user = new kalturaUser();
	$kaltura_user->puser_id = "0"; //"_" . $wgUser->getId();
	$kaltura_user->puser_name = "__ANONYMOUS__";
	return 	$kaltura_user;
}

function verifyUserRights($w_user = null )
{
	global $wgUser;
	global $kg_allow_anonymous_users;

//	echo "<pre>" . print_r ( $w_user , true ) . "</pre>";
	if ( $w_user == null )	{		$w_user = $wgUser;	}

	$rights = $w_user->getRights();
	// is allow anonymous users - check the user's rights
	if ( $kg_allow_anonymous_users )
	{
		return in_array ( "edit" , $rights );
	}
	else
	{
		return $w_user->isLoggedIn();
	}

	return false;
}


function canUserDeleteEntry ($w_user = null )
{
	global $wgUser;
	if ( $w_user == null )	{		$w_user = $wgUser;	}
	return ( $w_user->isLoggedIn()&& $w_user->isAllowed ("delete" ) );
}

//
function kshowIdFromArticle( $article )
{
	global $kg_widget_id_default;
	$widget_id_default = $kg_widget_id_default;

	// ----- backward compatible -----
	$title_obj = $article->getTitle();
	$res =  kshowIdFromTitle ($title_obj);
	if ( $res > 0 ) return array ( $res, $widget_id_default ); // the widget will be the default widget
		
	$kshow_id = $article->getKshowId();
	$widget_id = $article->getWidgetId();
	return array ( $kshow_id , $widget_id );
}

function kshowIdFromTitle ( $title_obj )
{
	if ( ! $title_obj ) return -1;
	$namespace = $title_obj->mNamespace	;
	$title = $title_obj->mTextform	;

	if ( $namespace == KALTURA_NAMESPACE_ID )
	{
		$title = strtolower( $title );
		$match = preg_match( "/video[^\d]*([\d]*)/" , $title , $kshow_arr );
		if ( $match )
		{
			$kshow_id = $kshow_arr[1];
		}
		else
		{
			// maybe of form:  'article-name (id)'
			$kshow_arr = array();
			$match = preg_match( "/.*\(([0-9]*)\)$/" , $title , $kshow_arr );
			if ( $match )
			{
				$kshow_id = $kshow_arr[1];
			}
			else
			{
				return -1;
			}
		}
		return $kshow_id;
	}
	return -1;
}

function searchableText ( $kshow_id )
{
	return " video_$kshow_id ";
}

// TODO - remove function and all calls to it
// there is no way we can trace the title from the kshow_id
function titleFromKshowId ( $kshow_id )
{
	$str = KALTURA_NAMESPACE . "video_" . $kshow_id;
	return $str;
}

function titleFromKwid ( $kwid )
{
	if ( $kwid == null )
		return null;
	return titleFromTitle ( $kwid->article_name );
}

function kshowIdFromKwid ( $kwid )
{
	if ( $kwid == null )
		return null;
	return $kwid->kshow_id;
}

function kwidFromArticle ( $article )
{
	list ( $kshow_id , $widget_id ) = kshowIdFromArticle( $article );
	$kwid = kwid::generateKwid ( $kshow_id , $article->getTitle() , $widget_id );
	return $kwid;
}

//adds the namespace if needed
function titleFromTitle ( $title  )
{
	if ( strpos ( $title , KALTURA_NAMESPACE ) !== 0 ) $title = KALTURA_NAMESPACE . $title;

	return $title;
}


function createSelectHtml ( $name , $option_values , $current_value = null )
{
	$str = "<select name='$name' id='$name'>" ;
	foreach ( $option_values as $value => $option )
	{
		$str .= "<option value='$value' " . ( $current_value == $value ? "selected='selected'" : "" ) . ">$option</option>" ;
	}
	$str .= "</select>";
	return $str;
}

function createRadioHtml ( $name , $option_values , $current_value = null )
{
//<label class="radio"><input type="radio" name="widget_align" value="L" checked="checked" />Left</label>
	$str = "";
	foreach ( $option_values as $value => $option )
	{
		$str .= "<label class='radio'><input type='radio' name='{$name}' value='{$value}' " . ( $current_value == $value ? "checked='checked'" : "" ) . "/>{$option}</label>\n";
	}

	return $str;
}


function createWidgetTag ( $kwid ,  $size = "l" , $align = "" , $version=null , $name=null , $description=null )
{
	//return "<kaltura-widget kalturaid='$kshow_id' size='$size' align='$align'" .
	return "<kaltura-widget size='$size' align='$align' kwid='{$kwid->toString()}' " . 
		//__kwid='{$kwid->toStringNoBase64()}'" .
		( $version ? " version='$version'" : "" ) .
		( $name ? " name='$name'" : "" ) .
		( $description ? " summary='$description'" : "" ) .
		"/>";
}

$added_js_for_widget = false;
$javascript_for_widget = "";

//
function createWidgetHtml ( /*kwid*/ $kwid , $size , $align , $version=null , $version_kshow_name=null , $version_kshow_description=null,
	$use_object_creation = true ,  
	$prepare_vars_only = false  )
{
	if ( !$kwid )
	{
		return "empty kwid";
	}

	global $wgTitle , $wgUser , $wgOut;
	global $partner_id, $subp_id, $partner_name;
	global $ks; // assume there is a ks for the user- there as a successful startSession
	global $current_widget_kshow_id_list;
	global $added_js_for_widget, $javascript_for_widget;
	global $kg_widget_id_default , $kg_widget_id_preview , $kg_widget_id_no_edit ;
	global $partner_id , $subp_id;

	$entry_id = null;
    $media_type = 2;
    $widget_type = 3;

    $kshow_id = $kwid->kshow_id;
    $widget_id = $kwid->widget_id;
	$kwid_str = $kwid->toString();
	$widget_type = 3; // old version 

	$append_kshow = false;
	
	if ( $widget_id == $kg_widget_id_default ) $append_kshow = true;
	
	// when out of the primary context - the widget is not updatable and is of a special size
	if ( ! $kwid->verifyContext() )
	{
		$widget_type = $kg_widget_id_no_edit;
		$size = "noedit";
		$append_kshow = true;
	}

	// if explicitly called with size special -> widget_type must be 0
	if ( $size == "noedit" )
	{
		$widget_id = $kg_widget_id_no_edit; 
		$append_kshow = true;
	}
	elseif ( $size == "preview" )
	{
		$widget_id = $kg_widget_id_preview; 
		$append_kshow = true;
	}
	elseif ( $size == "preview-nokshow" )
	{
		$widget_id = $kg_widget_id_preview; 
		$append_kshow = false;
	}

     // add the version as an additional parameter
	$domain = WIDGET_HOST; //"http://www.kaltura.com";

	$swf_url = "/index.php/extwidget/kwidget/wid/$widget_id" . ( $version ? "/v/$version" : "" );

	// ----- backward compatible -----
	// add the kshow_id in case the widget is the default one - this will let us recover on the server side 
	if ( $append_kshow )	$swf_url .= "/kid/" .  $kshow_id;


	$current_widget_kshow_id_list[] = $kshow_id;

	$widget_layout = ""; // use the default 
    if ( $size == "m")
    {
    	//$widget_id = $kg_widget_id_medium;
    	$widget_layout = "Medium";
    	 
    	// medium size
    	$height = 198 + 105;
    	$width = 267;
    }
    elseif ( $size == 'noedit' || $size == 'preview' || $size == "preview-nokshow" )
    {
//    	$height = 325;
    	$height = 396;
    	$width = 400;
    }
    else
    {
    	// large size
    	$height = 300 + 105 + 20;
    	$width = 400;
    }

	$titleObj = $wgTitle;
	$current_paget_url = $titleObj->getFullURL( "" );
	$root_url = getRootUrl();

    $str = "";//$extra_links ; //"";

    $external_url = "http://" . @$_SERVER["HTTP_HOST"] ."$root_url";

	$user_id = $wgUser->getId();
	$share = $titleObj->getFullUrl ();

	// this is a shorthand version of the kdata
    $links_arr = array (
    		"base" => "$external_url/" ,
    		"add" =>  Skin::makeSpecialUrl ( "KalturaContributionWizard" , "kshow_id=$kshow_id" ) ,
    		"edit" => Skin::makeSpecialUrl ( "KalturaVideoEditor" , "kshow_id=$kshow_id" ) ,
    		"share" => $share ,
    	);

    $links_str = str_replace ( array ( "|" , "/") , array ( "|01" , "|02" ) , base64_encode ( serialize ( $links_arr ) ) ) ;

	$kaltura_link = "<a href='http://www.kaltura.com' style='color:#bcff63; text-decoration:none; '>video, player, editor, open-source</a>";
	$kaltura_link_str = "A $partner_name collaborative video powered by  "  . $kaltura_link;

	// pass on the kwid as ExtraData so the functions can use it rather than the kshow_id
	$flash_vars = array (  
							"WidgetSize" => $size ,
							"pd_extraData" => $kwid_str );

	// add only if not null
	if ( $version_kshow_name ) $flash_vars["Title"] = $version_kshow_name;
	if ( $version_kshow_description ) $flash_vars["Description"] = $version_kshow_description;
	if ( $append_kshow )   $flash_vars["kshowID"] = $kshow_id;
	$flash_vars["partner_id"] = $partner_id;
	$flash_vars["subp_id"] = $subp_id;
	if ( $ks ) $flash_vars["ks"] = $ks;
	$kaltura_user = getKalturaUserFromWgUser ();
	$flash_vars["uid"] = $kaltura_user->puser_id;
	
	if ( $widget_layout ) $flash_vars["layoutId"] = $widget_layout;

	static $name_count = 0;
	$kplayer_obj_name = 'kaltura_player_' . (int)microtime(true) . "_" . $name_count;
	$name_count++;

  $flash_vars_str = "";
	$count = count($flash_vars); 
	$i = 0; 
	foreach($flash_vars as $key => $value)
	{
		$i++;
		$flash_vars_str .= $key . ": '$value'" ;
		if ($count != $i) $flash_vars_str .= ","; 
	}

	$use_object_creation = false;
	
	$flash_vars_obj_name = "flashVars_$kplayer_obj_name";
	
	if ( $prepare_vars_only  )
	{
		$js_for_player =  		' <script type="text/javascript">' .
				"function kalturaCreateDynamicSWFObject ( div_name_to_replace , flash_obj_name ) {" .
				"var so = new SWFObject('{$domain}{$swf_url}' , flash_obj_name , '{$width}','{$height}', '9.0.0', '#000000');" .
				'so.addParam("allowscriptaccess", "always");'. 
				'so.addParam("allownetworking", "all");' . 
				'so.addParam("allowfullscreen", "true");' . 
				'so.addParam("bgcolor", "#000000");' . 
				'so.addParam("wmode", "opaque");'  ;
		foreach($flash_vars as $key => $value)
		{
			$js_for_player .= "so.addVariable(\"$key\",\"$value\"); " ;
		}				
			
		$js_for_player .= "so.write( div_name_to_replace );";
					
		$js_for_player .=  '}</script>' ;
		return $js_for_player;
	}
	else
	{

		$widget = '<div id="' . $kplayer_obj_name . '"><a href=\"http://www.kaltura.com\" style="text-decoration:none; \">video, player, editor, open-source</a></div>' .
			' <script type="text/javascript">'.
			"var so = new SWFObject('{$domain}{$swf_url}' , '{$kplayer_obj_name}_flash_obj', '{$width}','{$height}', '9.0.0', '#000000');" .
			'so.addParam("allowscriptaccess", "always");'. 
			'so.addParam("allownetworking", "all");' . 
			'so.addParam("allowfullscreen", "true");' . 
			'so.addParam("bgcolor", "#000000");' . 
			'so.addParam("wmode", "opaque");'  ;
		foreach($flash_vars as $key => $value)
		{
			$widget .= "so.addVariable(\"$key\",\"$value\"); " ;
		}				
		$widget .= "so.write(\"$kplayer_obj_name\");";
		
		$widget .= 	'</script>' ;		

	}
		
	if ( $align == 'r' )
	{
		$str .= '<div class="floatright"><span>' . $widget . '</span></div>';
	}
	elseif ( $align == 'l' )
	{
		$str .= '<div class="floatleft"><span>' . $widget . '</span></div>';
	}
	elseif ( $align == 'c' )
	{
		$str .= '<div class="center"><div class="floatnone"><span>' . $widget . '</span></div></div>';
	}
	else
	{
		$str .= $widget;
	}

	return $str ;
}

function fixJavascriptForWidget ( &$text )
{
	global $added_js_for_widget, $javascript_for_widget ;
	kalturaLog( "fixJavascriptForWidget [$added_js_for_widget] [$javascript_for_widget]" );
	if ( $added_js_for_widget )
	{
		// if this is turned on - we need to replace the javascrip placeholder
		// first time replace with the relevant values
		$text = str_replace (  PLACEHOLDER_FOR_WIDGET_JAVASCRIPT , $javascript_for_widget , $text );
		// for all the rest - remove
		//$text = str_replace (  PLACEHOLDER_FOR_WIDGET_JAVASCRIPT , "" , $text );
	}
}



// creates the js according to the current user
function createJsForDeleteEntry ( $should_create , $refresh_url )
{
	$js = "<script type='text/javascript'>\n" .
			"function deleteEntry ( entry_id, kshow_id , hash ) {\n" ;
	if ( $should_create )
	{
		$js .= "res = confirm ( '" . ktoken( "confirm_delete_entry") .  "' );\n";
 		$js .= "if ( res ) deleteEntryImpl ( entry_id, kshow_id , hash , '$refresh_url' );\n";
	}
	else
	{
		$js .= "alert ( 'not deleting ' + entry_id );\n" ;
	}
	$js .= "}\n";
	$js .= "</script>";
	return $js;
}


function createInternalErrorPage ( $text )
{
	$start = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">' . "\n" .
		'<head>' . "\n";
	$js_for_easyedit = getKalturaScriptAndCss();			
	$end = "\n</head>";

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
	

	$result = $start . $js_for_easyedit . $css . $end . "<body><span style='color:white'><center>$text</center></span><br><center><button onclick='kalturaCloseModalBox(); return false;'>Close</button></center></body></html>";
	return $result;
}



function getRootUrl ()
{
//	$url = Skin::makeUrl( "" );// makeSpecialUrl ( "KalturaCollaborativeVideoInfo" );
//	return $url;
	global $wiki_root;
	return $wiki_root;//"/wiki/index.php";
}



function hashEntryKshow ( $entry_id , $kshow_id )
{
	global $secret;
	return base64_encode ( md5 ( $entry_id . $kshow_id , $secret ) );
}

function wikiSection ( $name , $add_as_wiki_text = true )
{
	global $wgOut;
	
//	$text = "\n== $name ==";
	$text = "<h2><span class='mw-headline'>$name</span></h2>";
	if ( $add_as_wiki_text ) $wgOut->addHtml ( $text ); //		$wgOut->addWikiText( $text );
	else	return $text;
}

function getHostId ( )
{
	$domain = WIDGET_HOST;
	if ( strpos ( $domain , "localhost"  ) !== false )		$host = 2;
	elseif ( strpos ( $domain , "kaldev" ) !== false ) 		$host = 0;
	elseif ( strpos ( $domain , "sandbox" ) !== false ) 	$host = 3;		
	else													$host = 1;
	return $host;	
}



function kresetcookie ( $name , $expiry_in_seconds = 180 )
{
	$value = kgetText ( $name );
	ksetcookie ( $name , $value , $expiry_in_seconds );
}

function ksetcookie ( $name , $value , $expiry_in_seconds = 3600 )
{
	global $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix;
	setcookie( $wgCookiePrefix.$name, $value , time() + $expiry_in_seconds, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
}

function kdeletecookie ( $name )
{
	global $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix;
	setcookie( $wgCookiePrefix.$name, "" , 1000 , $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
}

function kdeletecookies ( $arr )
{
	if ( is_array ( $arr ) )
	{
		foreach ( $arr as $name )
			kdeletecookie ( $name );
	}
}




// have a local mechanism for oobjects - we won't have tah many !
$kaltura_objects = null;
function kobject ( $lable )
{
	global $kaltura_objects;
	kloadMessages();
	//return wfMsgHtml ( $lable );
	return @$kaltura_objects[$lable];
}


?>

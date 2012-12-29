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


/**
 * 1.02 - 2008-06-10 include kalturaapi_php5_lib.php only when needed 
 * 	changes in KalturaNamespace.php, KalturaAjaxCollaborativeVideoInfo_body.php, 
 * 		KalturaCollaborativeVideoInfo_body.php, KalturaContributionWizard_body.php, KalturaInstall_body.php, KalturaTestPage_body.php, 
 * 		KalturaVideoEditor_body.php
 *  optimization using MW_SUPPORTS_PARSERFIRSTCALLINIT
 *  optimization using lazy-loading according to the URI
 *  moved kalturaUser class to wiki_helper_functions.php to be always included 
 * 1.01 - 2008-06-10 allow lazy loading for special pages
 * 	changes in kaltura_extension.php, KalturaSearch_body.php, wiki_helper_functions.php 
 * 
 */

// should be included before the definitions bellow for the KALTURA_NAMESPACE_STRING definition
// << -------------------------- partner_settings_default.php -------------------------- >>
#define ( 'KALTURA_NAMESPACE_STRING' , "Video" );
define ( 'KALTURA_NAMESPACE_STRING' , "Kaltura" );
define ( 'KALTURA_SIMPLE_EDITOR' , 1 );
define ( 'KALTURA_ADVANCED_EDITOR' , 2 );

define ('SERVER_HOST' ,"http://www.kaltura.com" );
define ('SERVICE_URL' ,  SERVER_HOST . "/index.php/partnerservices2/" );
define ('WIDGET_HOST' , SERVER_HOST );

$kg_version = "1.0.9";
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
$kg_se_conf_id_wiki_default = 220;
$kg_editor_types = KALTURA_SIMPLE_EDITOR ; //| KALTURA_ADVANCED_EDITOR ;

//require_once ( "partner_settings_default.php" );
// >> -------------------------- partner_settings_default.php -------------------------- <<


$inc = @include_once ( "partner_settings.php" ); // if the partner settings exists - it will overide the default values

#if ( ! defined ( 'KALTURA_NAMESPACE_STRING' ) ) define ( 'KALTURA_NAMESPACE_STRING' , "Video" );

define ( 'KALTURA_NAMESPACE' ,KALTURA_NAMESPACE_STRING . ":" );
define ( 'KALTURA_NAMESPACE_ID' , 320);
define ( 'KALTURA_DISCUSSION_NAMESPACE_ID' , 321);

// a const value for the product type
define ('KALTURA_PARTNER_PRODUCT_TYPE_WIKI' , 10 );

if ( isInstalled() )
{
	$uri = strtolower( @$_SERVER ['REQUEST_URI'] );
	if (strpos ( $uri , "kaltura" ) !== FALSE || strpos ( $uri , strtolower (KALTURA_NAMESPACE_STRING  ) ) !== FALSE )
	{
		require_once ( "kalturaapi_php5_lib.php" );

		// a nasty hack to know if should include the files or not.
		// I don't want to be within a scope of a function so hooks cannot be used 
		// kaltura - all special page use kaltura,  KALTURA_NAMESPACE_STRING - for the namespace
		require_once ( "KalturaNamespace.php" );
		require_once ( "NamespaceManager.php" );
		require_once ( "KalturaNamespacePermissions.php" );
			
		NamespaceManagers::register ( KALTURA_NAMESPACE_ID  , "KalturaNamespace" , pathinfo( __FILE__ , PATHINFO_DIRNAME ) . "/KalturaNamespace.php" );
		$wgHooks['userCan'][] = 'fnKalturaPermissionsCheckNamespace';
	}
} else{
}

$wgExtensionCredits['other'][] = array(
'name'              => "CollaborativeVideo",
'version'           => $kg_version ,
'author'            => "Kaltura" ,
'description'  		=> "Enables to create collaborative videos, add to them and edit them.",
'url'               => "http://www.mediawiki.org/wiki/Extension:KalturaCollaborativeVideo" ,
);




if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {

    $wgHooks['ParserFirstCallInit'][] = 'efKalturaSetup';

} else {
    $wgExtensionFunctions[] = 'efKalturaSetup';

}

$wgExtraNamespaces[KALTURA_NAMESPACE_ID]  = KALTURA_NAMESPACE_STRING ;
$wgExtraNamespaces[KALTURA_NAMESPACE_ID + 1]  = KALTURA_NAMESPACE_STRING . "_Talk";

if ( isInstalled() )
{
	//$wgHooks['OutputPageParserOutput'][] = 'fnKalturaOutputPageParserOutput';
	$wgHooks['SkinAfterBottomScripts'][] = 'fnKalturaSkinAfterBottomScripts';
	$wgHooks['BeforePageDisplay'][] = 'fnKalturaBeforePageDisplay';

	// hook as a special search page
	$wgHooks['LangugeGetSpecialPageAliases'][] = 'fnKalturaSearchLangugeGetSpecialPageAliases';

	/*
	 * KalturaBeforePageDisplay is a proprietary hook for those who don't want to use the standard 'BeforePageDisplay' one but still
	 * need our js & css additions. This hook somehow must be called in all pages.
	 * originally called from the skin class
	 */
	$wgHooks['KalturaBeforePageDisplay'][] = 'fnKalturaBeforePageDisplay';
	$wgHooks['MonoBookTemplateToolboxEnd'][] = 'fnKalturaMonoBookTemplateToolboxEnd';

	// add some ajax hooks
	$wgAjaxExportList[] = 'ajaxKalturaUpdateArticle';
	$wgAjaxExportList[] = 'ajaxKalturaDeleteEntry';
}
// TODO - decide if we want this or not and remove it. it shouldn't really be a paramter
// some terminology should change too (such as labels on buttons)
$kg_inplace_cw = true;

$kaltura_namespace_mgr = null;

function efKalturaSetup()
{
	if ( displayInstallation() )
	{
		if ( isAdmin() )		{			
			kloadSpecial ( "KalturaInstall" );
//			return;		
		}
		if ( ! isInstalled() )		{			
			// 	not installed and not admin - out !			
			return true;
		}
	}
	global $wgParser;
	global $wgUseAjax;
	$wgUseAjax = true;

	$wgParser->setHook( 'kaltura-widget', 'efKalturaWidgetRender' );

	kloadSpecial ( "KalturaCollaborativeVideoInfo" );
	kloadSpecial ( "KalturaAjaxCollaborativeVideoFinalStep" );
	kloadSpecial ( "KalturaAjaxCollaborativeVideoInfo" );
	kloadSpecial ( "KalturaContributionWizard" );
	kloadSpecial ( "KalturaVideoEditor" );
	kloadSpecial ( "KalturaDispatcher" );
	kloadSpecial ( "KalturaSearch" );
	kloadSpecial ( "KalturaTestPage" );
	return true;
}


// this is a nasty way to replace the Special:Search page
function fnKalturaSearchLangugeGetSpecialPageAliases ( &$special_page, $code )
{
	global $wgTitle;
	if ( $wgTitle != null && strtolower ( $wgTitle->getText() ) == "kalturasearch" )
	{
		$special_page["Search"][0] = "KalturaSearch";
	}

	return true;
}


// TODO -  should add to toolbar ??
// hook to bottom of toolbar
function fnKalturaMonoBookTemplateToolboxEnd ( $monobook )
{
	if ( ! isInstalled() ) return;
	
	echo createCollaborativeVideoLinkForToolbox();
	return true;
}


/**
 * Add JS to be called at the end of the regular edit page
 * See SkinAfterBottomScripts hook for the skin & text paramters.
 * All we need is to add a script element that calls the 'kalturaAddButtonsToEdit' js function with the
 * 	$root_url - to create the iframe/ajax call
 * 	$kaltura_path - to find the button's image in kaltura extension path
 */
function fnKalturaSkinAfterBottomScripts ($skin, &$text)
{
	$text .= createJsForAddButtonForEdit ();
	return true;
}

/**
 * Will add JS and CSS depending on the context of the page.
 * kaltura.js & kaltura.css will always be added.
 * a group of js for widgets will always be added.
 * a function to add a button to the edit page will be added only when in NON KALTURA NAMESPACE
 * 	and when in edit mode.
 */
function fnKalturaBeforePageDisplay ( &$out  , $add_script=true )
{
krequire_once ( "wiki_helper_functions.php" );
	
	global $wgTitle , $wgVersion;

	$script_list = array();
	// always add kaltura css & js - they should be harmless for other pages due to the kaltura prefix
	$script_list[] = getKalturaScriptAndCss() ;

	// add js for widget - for now in all pages
	$current_paget_url = $wgTitle->getFullURL( "" );
	$script_list[] =  createJsForWidget ( $current_paget_url ) ;

	// if of early versions - add the script here -
	// this will cause the button to be added earlt and will appear at the beginning of the button list
	if (version_compare($wgVersion, '1.10', '<' ) )
	{
		$js_to_add = createJsForAddButtonForEdit ();
		if ( ! empty ( $js_to_add ) )
		{
			$script_list[] = $js_to_add;
		}
	}

	$str = "";

	foreach ( $script_list as $script )
	{
		if ( $add_script ) $out->addScript ( $script );
		else $str .= $script . "\n";

	}

	if  ( $add_script ) return true;
	return $str;
}


/*
// add some ajax hooks
$wgAjaxExportList[] = 'ajaxKalturaUpdateArticle';
$wgAjaxExportList[] = 'ajaxKalturaDeleteEntry';
*/
function ajaxKalturaDeleteEntry ( $entry_id , $kshow_id , $hash )
{
krequire_once ( "wiki_helper_functions.php" );	
	kalturaLog( "ajaxKalturaDeleteEntry: [$entry_id , $kshow_id , $hash]" );
	$res = KalturaNamespace::deleteThisArticle (  $entry_id , $kshow_id , $hash );
	return "" + $res;
}

function ajaxKalturaUpdateArticle ( $kwid_str )
{
krequire_once ( "wiki_helper_functions.php" );
	$kwid = kwid::fromString( $kwid_str );
	// TODO - verify the user has delete privileges
	kalturaLog( "ajaxKalturaUpdateArticle: [{$kwid->toStringNoBase64()}]" );
	KalturaNamespace::updateThisArticle( false , $kwid , ktoken ( "update_article_contrib" ) , false , true );
	return "1";
}


$current_widget_kshow_id_list = array();
$set_hooks_after_widget = false;


/**
 Add us as a listner to the page save - only in this case
 */
function efKalturaWidgetRender( $input, $args, $parser )
{
krequire_once ( "wiki_helper_functions.php" );
	
	global $wgHooks;
	global $wgOuts ;
	global $kg_widget_id_default;
	global $set_hooks_after_widget;

	// this will prevent adding several hooks when more than one widget is used in a page
	if ( ! $set_hooks_after_widget)
	{
		// set a hook
		//    	$wgHooks['ParserAfterTidy'][] = 'kalturaWidgetParserAfterTidy';
		$wgHooks['SearchUpdate'][] = 'kalturaWidgetSearchUpdate';
		$set_hooks_after_widget = true;
	}

	$attr = array();

	$entry_id = null;
	$kshow_id = @$args["kalturaid"];
	//$entry_id = @$args["entryid"];
	$size = strtolower( @$args["size"] );
	$align = strtolower( @$args["align"] );
	$version = strtolower( @$args["version"] );
	$name = strtolower( @$args["name"] );
	$description = strtolower( @$args["summary"] );
	// TODO - replace the kalturaid
	$kwid_str = @$args["kwid"];
	$kwid = kwid::fromString( $kwid_str );

	if ( $kwid == null && empty ( $kshow_id ) )
	{
		return "<b>kaltura-widget: Invalid tag. Must have a valid 'kwid' attribute or a 'kalturaid' attribute. Make sure the case of the attribute is LOWER-CASE.</b>";
	}

	if ( $kwid == null && $kshow_id )
	{
		// $kg_support_old_tags
		// this is for backward compatibility
		$title_str = titleFromKshowId ( $kshow_id );
		$title = Title::newFromText ($title_str);
		$kwid = kwid::generateKwid ( $kshow_id , $title , $kg_widget_id_default);
	}

	kalturaLog ( "efKalturaWidgetRender" );

	// add a link to this kaltura
	//$title_str = titleFromKshowId( $kshow_id );
	$title_str = titleFromKwid ( $kwid );

	if ( !$title_str )
	{
		// to be backward compatible
		$title_str = titleFromKshowId ( $kshow_id );
		// construct a kwid assuming the old version of tag
		$kwid = kwid::generateKwid( $kshow_id , $title_str , $kg_widget_id_default );
	}

	if ( $title_str )
	{
		$title = Title::newFromText ($title_str);
		if ( $title ) //&& $title->getNamespace() != KALTURA_NAMESPACE_ID )
		{
			// add the link only for real articles
			$parser->mOutput->addLink ( $title ); // add a link to the article from this page
		}
	}

	// here we don't expect to insert the version - if we do set it, it will no be up-to-date
	if ( $parser->getTitle()->getNamespace() != KALTURA_NAMESPACE_ID )
	{
		// for all widgets that are not in the kaltura namespace - ignore the version so they will be up-to-date
		$version = null;
	}
	return createWidgetHtml ( $kwid , $size , $align , $version , $name, $description , true );
}


function kalturaWidgetSearchUpdate( $id, $namespace, $title, &$text)
{
krequire_once ( "wiki_helper_functions.php" );
	
	global $current_widget_kshow_id_list;

	foreach ( $current_widget_kshow_id_list as $kshow_id )
	{
		$text .= searchableText ( $kshow_id );
	}

	return true;
}

// ----------------------- moved from wiki_helper_functions -----------------------------
// display installation if either the extension is not yet installed or $kg_force_display_installation 
// was explicitly set to be true
function displayInstallation()
{
	global $kg_force_display_installation;
	return $kg_force_display_installation || ( ! isInstalled() ) ;
}

function isAdmin ($w_user = null )
{
	global $wgUser;
	if ( $w_user == null )	{		$w_user = $wgUser;	}
	return ( $w_user->isLoggedIn()&& $w_user->isAllowed ("userrights" ) );
}

function isInstalled ()
{
	global $kg_installation_complete ;
	return $kg_installation_complete ;
}

function kloadSpecial ( $obj_name , $obj_file = null )
{
	global $wgAutoloadClasses,$wgSpecialPages;
	
	if ( ! $obj_file ) $obj_file =  $obj_name . "_body.php";
//	require_once ( dirname(__FILE__) . "/" . $obj_file );
//	SpecialPage::addPage( new $obj_name() );

	// use lazy-loading mechanism
$wgAutoloadClasses[$obj_name] = dirname(__FILE__) . "/" . $obj_file; # Tell MediaWiki to load the extension body.
$wgSpecialPages[$obj_name] = $obj_name; # Let MediaWiki know about your new special page.
}

function krequire_once ( $file )
{
	$prof = "kaltura-debug-[$file]";
	wfProfileIn ($prof);
	require_once ( $file );
	wfProfileOut ($prof);		
}

// Should NOT assume tny specific directory under extensions
function getKalturaPath()
{
	global $wgScriptPath;
//	$this_file_path = basename ( dirname ( __FILE__ ) );
//	return "{$wgScriptPath}/extensions/$this_file_path/";
	$this_file_path =  realpath ( dirname ( __FILE__ ) ) ;
	$fixed_path = str_replace ( "\\" , "/" , preg_replace ( "/^.*extensions/" , "{$wgScriptPath}/extensions" , $this_file_path ) );
	 
	// remove duplicate slashs from the beginning of the path
	$fixed_path = preg_replace ( "|^\/\/|" , "/" , $fixed_path );
	return  $fixed_path . "/" ;
}

function getKalturaScriptAndCss ()
{
	global $wgJsMimeType, $wgScriptPath, $wgStyleVersion ;
	
	$kaltura_path = getKalturaPath();

$s = <<< JS_AND_CSS
<script type='{$wgJsMimeType}' src='{$kaltura_path}kaltura.js'><!-- kaltura js --></script>
<style type="text/css" media="screen, projection">/*<![CDATA[*/
	@import "{$kaltura_path}kaltura.css?{$wgStyleVersion}";
/*]]>*/</style>

JS_AND_CSS;
	return $s;
}


function createCollaborativeVideoLink()
{
	$url = Skin::makeSpecialUrl ( "KalturaAjaxCollaborativeVideoInfo" , "inflow=2" ); 

	$javascript = "kalturaOpenModalBoxBeginKaltura( \"$url\" );";
	return $javascript;
}


function createJsForWidget ( $current_paget_url )
{
	global $wgLink ;
	global $kg_editor_types;
	
		// don't add the kshow_id to the list - it can change from one widget to another within the page
   	$editor_launch_params = array( "back_url" => $current_paget_url );

	$editor_launch_params_str = http_build_query( $editor_launch_params , "" , "&" )		;

	// for advanced editor
	//$editor_js_func = "function gotoEditor ( kshow_id,kwid ) { document.location='". Skin::makeSpecialUrl ( "KalturaVideoEditor" , "$editor_launch_params_str' + '&kshow_id=' + kshow_id + '&kwid=' + kwid" ) . ";}\n" ;
	// for simple editor
	//$editor_js_func = "function gotoEditor ( kshow_id,kwid ) { kalturaOpenEditor ( '" . Skin::makeSpecialUrl ( "KalturaVideoEditor" , $editor_launch_params_str ) . "' + '&kshow_id=' + kshow_id + '&kwid=' + kwid ) ;}\n" ;
	// added the runtime parameter type to distinguish between the editors
	$editor_js_func = "function gotoEditor ( kshow_id,kwid,type) { kalturaOpenEditor ( '" . Skin::makeSpecialUrl ( "KalturaVideoEditor" , $editor_launch_params_str ) . "' + '&kshow_id=' + kshow_id + '&kwid=' + kwid , type,{$kg_editor_types}) ;}\n" ;
	
    $javascript_for_widget = "<script type='text/javascript'>\n" .
    	"function gotoCW ( kshow_id,kwid ) { kalturaInitModalBox ( '" . Skin::makeSpecialUrl ( "KalturaContributionWizard" , $editor_launch_params_str ) . "' + '&kshow_id=' + kshow_id + '&kwid=' + kwid ) ;}\n" .
    	$editor_js_func . 
    	"function gotoKalturaArticle ( kshow_id,kwid ) { document.location='" . Skin::makeSpecialUrl ( "KalturaDispatcher" , "kwid=' + kwid + '&kshow_id=' + kshow_id" ) . ";}\n" .
    	"function createNewVideo () { " . createCollaborativeVideoLink() . "; }" .
    	"</script>\n";
    	

	return 	    $javascript_for_widget;
}


$kg_added_buttons_to_edit = false;
// adds a script section that calls kalturaAddButtonsToEdit.
// depending on when called on the server - a js hook is added in the page.

// if called using the 'BeforePageDisplay' hook, it is placed early in the html -> the button will be added at the beggining of the button list.
// if called using the 'SkinAfterBottomScripts' hook, it is placed late in the html -> the button will be added at the end of the button list.
function createJsForAddButtonForEdit ( )
{
	global $wgJsMimeType, $wgScriptPath, $wgStyleVersion ;
	global $wgTitle ;

	global $kg_added_buttons_to_edit;

	$kaltura_path = getKalturaPath();

	$text = "";
	if ( ! $kg_added_buttons_to_edit )
	{
		// add a button to the wysiwyg editor when not in KNS
		if ( $wgTitle->getNamespace() != KALTURA_NAMESPACE_ID )
		{
			// try to add some script for adding buttons for the editor
			$action = kgetText ( "action" );
			if ( $action == "edit" )
			{
				$edit_url = Skin::makeSpecialUrl ( "KalturaAjaxCollaborativeVideoInfo" , "inflow=1") ;

				$javascript_all_add_buttons = "kalturaAddButtonsToEdit( '$edit_url' , '$kaltura_path/images/' , '" . ktoken ( "btn_txt_edit_page") . "' );";
				$text = "<script type='{$wgJsMimeType}'>{$javascript_all_add_buttons}</script>";
			}
		}
		$kg_added_buttons_to_edit = true;
	}

	return $text;
}

function createCollaborativeVideoLinkForToolbox ( )
{
	$javascript = createCollaborativeVideoLink();
	return "<li><a href='#' onclick='$javascript' >" . ktoken( "collaborativevideo" ) . "</a></li>";
	//return "<li><a href='" . Skin::makeSpecialUrl ( "KalturaCollaborativeVideoInfo" , "nk=true" ) . "' >" . ktoken( "collaborativevideo" ) . "</a></li>";
}


// search_order
// 0 - first getText then cookie
// 1 - first cookie then getText
// 2 - only getText
// 3 - only cookie
function kgetText ( $param , $default_val = null , $search_order = 0 )
{
	$val = kgetTextImpl ( $param , $search_order );
	if ( $val == null )
		return $default_val;
	return $val ;
}

function kgetTextImpl ( $param , $search_order = 0 )
{
	global $wgRequest;

	global $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix;

	// prefer post/get data
	if ( $search_order == 2 )
		return $wgRequest->getText ( $param );
	if ( $search_order == 0 )
	{
		$val = $wgRequest->getText ( $param  );
		if ( ! $val )
			$val = @$_COOKIE[$wgCookiePrefix.$param];
		return $val;
	}

	// prefer cookie
	if ( $search_order == 1 )
		return @$_COOKIE[$wgCookiePrefix.$param];
	if ( $search_order == 3 )
	{
		if ( ! $val )
			$val = $wgRequest->getText ( $param  );
		return $val;
	}
}

function kloadMessages() {
	static $messagesLoaded = false;
	global $wgMessageCache, $wgLanguageCode;
	global $kaltura_objects;

	if ( !$messagesLoaded ) {
		$messagesLoaded = true;

		// get the current lang
		$lang = $wgLanguageCode ;
		$lang_file_name = ucwords ( $lang );
		$file_to_load =  dirname( __FILE__ ) . "/KalturaMessages{$lang_file_name}.php";
		if ( ! file_exists($file_to_load ))			$lang_file_name  ="En" ; //the default-always existing lang

		require( dirname( __FILE__ ) . "/KalturaMessages{$lang_file_name}.php" );

		$wgMessageCache->addMessages( $kaltura_messages, $lang );
	}
	//return true;
}

function ktoken ( $lable )
{
	$args = func_get_args();
	array_shift( $args );
	kloadMessages();
	//return wfMsgHtml ( $lable );
	return wfMsg ( $lable , $args );
}

$pages = array("KalturaCollaborativeVideoInfo", 
		"KalturaAjaxCollaborativeVideoFinalStep",
		"KalturaAjaxCollaborativeVideoInfo",   
		"KalturaContributionWizard" ,   
		"KalturaVideoEditor" ,   
		"KalturaDispatcher" ,   
		"KalturaSearch" ,   
		"KalturaTestPage");

foreach ($pages as $page) {
	$wgAutoloadClasses[$page] = dirname(__FILE__) . "/" . $page . "_body.php"; 
	$wgSpecialPages[$page] = $page; 
}

?>

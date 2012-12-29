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
class KalturaAjaxCollaborativeVideoFinalStep extends SpecialPage
{
	const DISPLAY = 0;
	const CREATE_KALTURA = 1;
	const GET_KALTURA = 2;
	const UPDATE_KALTURA = 3;

	private $extra_params = null;

	function KalturaAjaxCollaborativeVideoFinalStep( ) {
		SpecialPage::SpecialPage("KalturaAjaxCollaborativeVideoFinalStep");
		kloadMessages();
	}

	function execute( $par  )
	{
		global $wgRequest, $wgOut , $wgUser;
		global $wgJsMimeType, $wgScriptPath, $wgStyleVersion, $wgStylePath;

		$wgOut->setArticleBodyOnly ( true );

		$kaltura_user = getKalturaUserFromWgUser ( );

		$this->setHeaders();


$localscript = <<< LOCAL_SCRIPT
<script type='text/javascript'>

function kalturaInit()
{
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
		a.top{ width:14px; height:14px; overflow:hidden; position:absolute; top: 10px; z-index:101; cursor: pointer; }

		form{ overflow:hidden; padding:24px 20px; }
		form h1{ color:#cbdb8d; font-size:1.5em; font-weight:normal; display:inline; margin-right:20px; }
		form fieldset{ height:300px; text-align:center; border:0 none; padding:8px 20px 0; font-size:0.9em; font-weight:bold; margin:4px 0; border:1px solid #383838; background-color:#303030; color:#ddd; }
		form div.item{ margin-bottom:15px; }
		form div.item label{ float:left; width:8em; margin-right:20px; }
		form div.radio input, .innerWrap form label.radio input{ margin:0 6px -3px 0; border:none; width:auto; }
		form div.radio label{ float:none; }

		 input, textarea{ color:#444; width:400px; font-size:12px; font-family:arial; font-weight:bold; padding:3px; border:1px solid #AAA; background-color:#EEE; }
		 textarea{ overflow:auto; }
		 input:focus, textarea:focus{ color:#FF3333; background-color:#FFF; }

		 a.btn{ display:block; width:120px; height:26px; line-height:26px; font-size:1em; text-decoration:none; text-align:center; color:#333; background:url({$kaltura_path}images/btn_template.gif) 0 0 no-repeat; cursor:pointer; }
		 a.btn:hover{ background-position:0 -26px; }
	</style>
CSS_FOR_HEAD;

		$start = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">' . "\n" .
			'<head>' . "\n";
		$end = "\n</head>";
$wgOut->addHTML( $start . $css . $javascript . $end );
			$html = "<body  onload='kalturaInit()'>";

// flow 1 - from an the eidt page
// flow 2 - from the sidebar		
$inflow = kgetText ( "inflow" ) ;
$tag = kgetText ( "tag" );

if ( $inflow == 1 )
	$text = ktoken ( "text_tag_inserted" );
elseif ( $inflow == 2 )
	$text = ktoken ( "text_tag_should_copy" );
	
$close_window = ktoken ( "lbl_close_window" );

$content = <<< HTML_CONTENT
	<a id="mbCloseBtn" class="top" title="Close" href="#" onclick="kalturaCloseModalBox();  return false;"></a>
			<div id="content_main">
				<form>
					<fieldset>
						<p style="margin-top:40px;">{$text}</p>
						<br />
						<div class="item">
							<td><textarea cols='65' rows='4' readonly='readonly' name='dummy'>$tag</textarea></td></tr>
						</div>
						<br />
						<center><a class="btn" href="#" onclick='return kalturaCloseModalBox()'>{$close_window}</a></center>
					</fieldset>
				</form>
			</div>
HTML_CONTENT;

			$html .= $content;

			$html .= "</body></html>";

			$wgOut->addHTML( $html );
	}
}

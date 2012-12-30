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
require_once ( "NamespaceManager.php" );
require_once ( "kalturaapi_php5_lib.php" );

function LOGME ( $method , $article )
{
	return;
	global  $wgOut;

	$text = $article->getLatest() . "|" . $article->getOldID();

	$text = "<pre>$method\n" . $text . "</pre>";
	$wgOut->addHtml ( $text );


	kaltura_log( $method . " " . $text );
}

class KalturaNamespace extends NamespaceManager
{
	public $update_version = false;
	private $rollback_version = null;

	private $displayed_lines_of_history = false;
	public  $ignore_redirect = false;

	private $kshow_version = null;
	private $kshow = null;
	static $best_revision = -1;

	private $kshow_id;
	private $widget_id;
	private $kaltura_history_data;
	
	public static function deleteThisArticle ( $entry_id ,$kshow_id , $hash  )
	{
		// validate the request using $entry_id , $kshow_id & $hash
		$valid = ( hashEntryKshow ( $entry_id , $kshow_id ) ==  $hash) ;
		if ( $valid )
		{
			kalturaLog ( "deleteThisArticle ( $entry_id ,$kshow_id , $hash ) - valid" );
			KalturaNamespace::deleteEntry ( $kshow_id , $entry_id );
		}
		else
		{
			kalturaLog ( "deleteThisArticle ( $entry_id ,$kshow_id , $hash ) - invalid !!" );
		}
	}

	public static function getWidgetIdFromArticle ( $kwid )
	{
		$title_str = titleFromKwid( $kwid );
		$title = Title::newFromText ($title_str);

		$kaltura_article = MediaWiki::articleFromTitle ( $title );
		return $kaltura_article->getWidgetId();
	}
	
	public static function updateThisArticle ( $first_time , $kwid , $summary  , $minor  ,  $watch_this , $new_title_str = null )
	{
		//$title_str = titleFromKshowId( $kshow_id );
		$title_str = titleFromKwid( $kwid );
		$title = Title::newFromText ($title_str);

		$kaltura_article = MediaWiki::articleFromTitle ( $title );

		$kaltura_article->update_version = true; // marks this article for update - not rollback
		$kaltura_article->ignore_redirect = true;

		if ( $first_time )
		{
			// make sure these params exist in as members - they will be embeded in the page
			$kshow_id = $kwid->kshow_id;
			$widget_id = $kwid->widget_id;
			// for the first time must set the $kshow_id so for next revisions will be embeded in the historyData
			$kaltura_article->setKshowId ( $kshow_id );
			$kaltura_article->setWidgetId ( $widget_id );
			$kaltura_article->insertNewArticle( "Will be modified anyway"  , $summary , $minor , $watch_this );
		}
		else
		{
		 	$kaltura_article->updateArticle( "Will be modified anyway"  , $summary , $minor , $watch_this );
		}

//		$new_title_str = titleFromTitle ( $new_title_str , $kshow_id );

/*
		if ( $new_title_str != null && $new_title_str != $title_str )
		{
			$new_title = Title::newFromText ($new_title_str);
//			kalturaLog ( "updateThisArticle: moveTo [{$title_str}->{$new_title_str}] " ) ;
			$res = $title->moveTo ( $new_title , false );
//			kalturaLog ( "updateThisArticle: moveTo [{$title_str}->{$new_title_str}] " . print_r ( $res , true ) ) ;
		}
*/
	}


	// override the function  Article:doRedirect  - to be able to skip this action sometimes,
	// depending on if the insert/update where behind the scenes or not
	function doRedirect( $noRedir = false, $sectionAnchor = '', $extraq = '' ) {
		if ( $this->ignore_redirect )
			return;
		else
			parent::doRedirect( $noRedir , $sectionAnchor , $extraq );
	}

	// will control the view of all Kaltura: pages
/**
 	Some data is fetched from the content (set at save time) - see hArticleSave
	This data will be part of "diff" - it's the real content.
	At view time, the rest can be created
	Here we should NOT call any external services !!
 */
	public function view()
	{
		global $wgRequest, $wgOut , $wgUser, $wgLang;
		global $wgMaxTocLevel;
		global $wgVersion, $wgUseRCPatrol;
		global $ks;
		global $wgUrl;

		LOGME ( __METHOD__ , $this );

		// start a session - it will be used many time during the view 
	 	$kaltura_user = getKalturaUserFromWgUser ();
		$kaltura_services = kalturaService::getInstance( $kaltura_user );
		$ks = $kaltura_services->getKs();
			
		$kshow_id = $this->getKshowForArticle();
		if( is_numeric( $kshow_id ) && $kshow_id < 1 )
		{
			// a problem - migth be a nonexisting kshow or a deleted one
			$html = ktoken ( "invalid_kshow_id_in_article_page" );
			$wgOut->addHtml( $html );
			return;
		}

		$kwid = kwidFromArticle( $this );
		
		$article_name= $this->mTitle->mTextform;
		$refresh_url = Skin::makeUrl ( titleFromTitle ( $article_name ) , "r=" . time() );

		// add some javascript that will behave according to the logged in user - will refresh the page if needed		
		$wgOut->addScript ( createJsForDeleteEntry ( canUserDeleteEntry()  , $refresh_url ) );

		$sk = $wgUser->getSkin();
		$rcid =  kgetText( 'rcid' );

		$diff = kgetText ( "diff" );
		if ( $diff )
		{
			// disable the table of content so it won't appear in the diff table
			$wgMaxTocLevel = 0;
			// show diff between diff and oldid
			$oldid = kgetText ( "oldid" );

			// set the title after calling fetchContent - or else it will be overriden
			$wgOut->setPageTitle( $this->mTitle->getPrefixedText() . " $diff | $oldid");

			// because fetchContent works on the state of this object - create another object to compare this one too
			$kaltura_article_1 = MediaWiki::articleFromTitle ( $this->mTitle );
			$content_1  = $kaltura_article_1->fetchContent ( $diff );
			$revision_data_1 = kalturaHistoryData::fromText( $content_1 );
			$revision_1 = Revision::newFromId( $diff );

			$kaltura_article_2 = MediaWiki::articleFromTitle ( $this->mTitle );
			$content_2  = $kaltura_article_2->fetchContent ( $oldid );
			$revision_data_2 = kalturaHistoryData::fromText( $content_2 );
//			$content_2  = $this->fetchContent ( $oldid );
			$revision_2 = Revision::newFromId( $oldid );

			$timestamp_1 =  $wgLang->timeanddate( $revision_1->getTimestamp() , true );
			$timestamp_2 =  $wgLang->timeanddate( $revision_2->getTimestamp() , true );
	//return wfTimestamp(TS_MW, $this->mTimestamp);
	//$visible_date = $wgLang->timeanddate( wfTimestampNow (TS_MW ), true );

			$lbl_revision = ktoken ( "lbl_revision" );
			$html = "<table width='80%' cellpadding='10'>" .
				"<thead>" .
				"<tr><td width='40%' >{$lbl_revision} {$timestamp_2}</td>" .
					"<td width='40%' >{$lbl_revision} {$timestamp_1}</td></tr>" .
				"</thead>" .
				"<tbody>" ;
			// 	---- DIFF-PATROL ----
			// Build the patrol link (taken from DifferenceEngine)
//			if( $rcid ) {
			if ( $wgUseRCPatrol && !is_null( $rcid ) && $rcid != 0 && $wgUser->isAllowed( 'patrol' ) ) {				
					$patrol = ' [' . $sk->makeKnownLinkObj(
						$this->mTitle,
						wfMsgHtml( 'markaspatrolleddiff' ),
						"action=markpatrolled&rcid={$rcid}"
						) . ']';

				$html .= "<tr style='vertical-align:top;'>" .
						"<td></td>" .
						"<td>$patrol</td>" .
						"</tr>";
			} // patrol
			$html .= "<tr style='vertical-align:top;'>" .
				"<td>" ;
			$wgOut->addHtml( $html );

			$wgOut->addWikiText( self::createWidgetCodePreview ( $content_2 ) );
			wikiSection ( ktoken ( "lbl_gallery" ) );
			$wgOut->addHtml( $kaltura_article_2->renderGallery ( $revision_data_2 )  );
			$wgOut->addHtml ( "</td><td>" );

			$wgOut->addWikiText( self::createWidgetCodePreview ( $content_1 ) );
			wikiSection ( ktoken ( "lbl_gallery" ) );
			$wgOut->addHtml( $kaltura_article_1->renderGallery ( $revision_data_1 )  );

			$html = "</td>".
				"</tr>" .
				"</tbody>" .
				"</table><br><br>" ;

			$wgOut->addHtml( $html );

		}
		else
		{
//			$kwid = kwidFromArticle( $this );
/*
			$wgOut->addHtml( $kwid->toString() . "<br>" );
			$wgOut->addHtml( $kwid->toStringNoBase64() . "<br>" );
*/
			$oldid = kgetText ( "oldid" );

			if ( $oldid )
			{
				// older versions of this article - called from the history list
				$older_version_of_this_article = MediaWiki::articleFromTitle ( $this->mTitle );
				$rev_id = $this->getRevIdFetched();
				
				$content  = $older_version_of_this_article->fetchContent ( $oldid );
				// for old versions - make the widget a NoEdit one
				if ( $rev_id != $oldid ) 
					$content = self::createWidgetCodePreview( $content );
			}
			else
			{
				// this is for the standard view
				$content = $this->getContent();
			}

			$wgOut->addWikiText( $content );


			$revision_data = kalturaHistoryData::fromText( $content );
			wikiSection ( ktoken ( "lbl_gallery" ) );

			$wgOut->addHtml( $this->renderGallery ( $revision_data )  );

			// ---- PATROL ---- code taken from the Article object
			# If we have been passed an &rcid= parameter, we want to give the user a
			# chance to mark this new article as patrolled.
			if ( $wgUseRCPatrol && !is_null( $rcid ) && $rcid != 0 && $wgUser->isAllowed( 'patrol' ) ) {
				$wgOut->addHTML(
					"<div class='patrollink'>" .
						wfMsgHtml( 'markaspatrolledlink',
						$sk->makeKnownLinkObj( $this->mTitle, wfMsgHtml('markaspatrolledtext'),
							"action=markpatrolled&rcid=$rcid" )
				 		) .
					'</div>'
				 );
			}
		}

		// hidden player - will appear first time an entry was viewed
		$this->renderHiddenPlayer ( $kwid );
		$wgOut->addHtml( "<br>" );


		// ---- TAG CODE ----
		wikiSection ( ktoken ( "lbl_tag_code" ) );

		$embed_code = ktoken ( "txt_tag_code" ) .
		   "<input type='text' style='font-size:11px;' size='150' value='" .
			htmlspecialchars( $this->createWidgetCode( "L" , "L" , null ) , 1 )."'/> <br>";

		$wgOut->addHtml( $embed_code );

		// ---- LINKS TO ARTICLE ----
		wikiSection ( ktoken ( "lbl_links_to_article" ) );
		$links = $this->mTitle->getLinksTo( true );
		$wgOut->addHtml ( $this->renderLinks( $links ) );

		// ---- ASSET LIST ----
		$can_delete = canUserDeleteEntry();
		if ( $can_delete )
		{
			// display this sub section only for those who can delete
			wikiSection ( ktoken ( "lbl_asset_list" ) );
			$wgOut->addHtml( $this->renderGallery ( null , false )  ); // null for the latest version - not roughcut gallery
		}

		// ---- HISTORY ----
		wikiSection ( ktoken ( "lbl_history" ) );
		// continue with the dynamic stuff (wich is modified between views)
		$history = new PageHistory( $this );
		$history->history();

	}

	// if we return true - the 'edit' chain in the CustomEditor hook will continue
	// this funciton will actually be used only for undo/revert
	// no reason for a user to edit the kalturaArticle
	public function edit()
	{
		global $wgRequest, $wgOut , $wgUser;
		global $kg_allow_anonymous_users;

		LOGME ( __METHOD__ , $this );

		# Get variables from query string :P
		$undo = kgetText( 'undo' );

		kalturaLog( "User Wants to undo to [$undo]");

		// action=edit & undo -> rollback
		if ( $undo )
		{
			// This should actually not happen - users will not see the revern link if not logged in
			if ( !verifyUserRights() )
			{
				// store the kshow_id so it will be used wen user IS logged in
				ksetcookie( "undo" , $undo ) ;
				ksetcookie( "back_url" , kgetText( 'back_url' ) ) ;

				kalturaLog( "User should log in. Will undo [$undo]");
				$wgOut->loginToUse( );//'kalturakshowidrequestnologin', 'kalturakshowidrequestnologintext' , array( $this->mDesiredDestName ) );
				return false;
			}

			// make sure user can revert
			// TODO - retrun condition !!
			if ( true ) //$wgUser->isAllowed( 'rollback' ) )
			{
				$this->rollback_version = $undo; // set the rollback_version so will be fetched at save time
				$this->updateArticle( "Will be modified anyway"  , ktoken ( "update_article_revert") . " $undo" , false , true );
			}
			else
			{
				$this->rollback_version =  null;
			}

			return true;
		}
		else
		{
			// here we'll actually display our edit page
			// for now - use the executeImpl function of the special page
			$edit_page = new KalturaCollaborativeVideoInfo( true );
			$kwid = kwidFromArticle( $this );
			$extra_params = array(
				"kwid" =>$kwid->toString() ,
				"kshow_id" => $kwid->kshow_id ,
				//"form_action" => $this->mTitle->getLocalURL ( "action=edit" ),
				"form_action" => $this->mTitle->getLocalURL ( "action=edit" ), // should point to the index-name so will be redirected to the updated one
				"back_url" => $this->mTitle->getLocalURL ( "action=view" )
			);
			$edit_page->executeImpl( null , $extra_params );

			return false;
		}
		return true;
	}


	// handle the delete only once it's actually done
	public function hArticleDeleteComplete ( &$article, &$wgUser, $reason)
	{
		$res = $this->deleteKshow ();
		return ( $res != null );
	}


	// IMPORTANT - will override the article's default save and will store relevant data for future use
 	public function hArticleSave        ( &$article, &$user, &$text, $summary, $minor, $dontcare1, $dontcare2, &$flags )
 	{
 		global $wgOut , $wgUser, $wgRequest, $wgLang;

		LOGME ( __METHOD__ , $article );

		$current_version = $this->getLatest();
 		if ( ! $current_version )
		{
			$current_version = ktoken ( "new_version");
		}
		if ( ! $this->update_version && $this->rollback_version  )
		{
			// time to rollback -
			// fetch the content of the desired version.
			// there we'll find the name,summary and the kaltura_desired_version to update the kshow with
			$kaltura_article = MediaWiki::articleFromTitle ( $this->mTitle );
			$content  = $kaltura_article->fetchContent ( $this->rollback_version );

			$revision_data = kalturaHistoryData::fromText( $content );

			// this cannot be done at save time because the delete happens dynamically
			// and should affect the gallery without the article really being saved
//			$gellery = $this->renderGallery ( $revision_data );

			kalturaLog ( "content: $content\nrevision_data: " . print_r ( $revision_data , true ) );

			$desired_name = $revision_data->data["name"]; // from the content
			$desired_description = $revision_data->data["description"];// from the content
			$desired_kaltura_version = $revision_data->data["version"]; // from the content

			// this is where we revert the version rather than get it from kaltura
			// get the desired version from the text
			$kshow = $this->kshowRollback ( $desired_name , $desired_description , $desired_kaltura_version );
			// if there was a problem - use the previous string
			if ( ! $kshow )
			{
				kalturaLog ( "Error while rolling back to $desired_name , $desired_description , $desired_kaltura_version" );

				// TODO - a problem !!
				$kshow_version = $desired_kaltura_version;
			}
			$kshow_version = $kshow["version"];
			// update the version to be the new one from kaltura
			$revision_data->data["version"] = $kshow_version;
			// because the version on kaltura will change to be a new one - not necessarily the current one -
			// we'll override the text all together
		}
		else
		{
			// get the current version from kaltura
			$kshow  = $this->getKshow ( );
			$revision_data =  new kalturaHistoryData ( null );
			$revision_data->data["version"] = $kshow["version"];
		}

		$kshow = $this->getKshow();
		// for the this special page of the show - use the version so it will be part of the page and will help
		// while looking through the history
		$text  = $this->createWidgetCode( "l" , "" , $kshow["version"]	 , $kshow["name"] , @$kshow["description"] );

		$info_data = "<br>" . wikiSection ( ktoken ( "lbl_info" ) , false ) ."\n";
		$text .= $info_data . "Name: " . $kshow["name"] . "<br>Summary: " . @$kshow["description"] ;

		$version_data = "<br>" . wikiSection ( ktoken ( "lbl_version" ) , false ) ."\n";
		// mark the
		$visible_date = $wgLang->timeanddate( wfTimestampNow (TS_MW ), true ) ;
		// TOD - remove !
		$visible_date .= " (" . $kshow["version"] . ")";
		$text .= $version_data . $visible_date ; // . " [{$kshow["version"]}]";

		// make the revision_data invisible

		$revision_data->data["id"] = @$kshow["id"];
		$revision_data->data["name"] = @$kshow["name"];
		$revision_data->data["description"] = @$kshow["description"];
		$revision_data->data["wgUserName"] = $wgUser->mName;
		$revision_data->data["timestamp"] = time();
		$revision_data->data["articleCurrentVersion"] = $current_version ;
		$revision_data->data["widget_id"] = $this->getWidgetId() ;

kalturaLog ( "revision_data\n" . print_r ( $revision_data , true ) );

		
		$revision_data_str = "\n<span style='display:none;'>" . $revision_data->toText() .	"</span>"	;
		$text .= $revision_data_str;

		return true;
 	}


 	// This is the best way to manipulate the history line
	// we would like to enable/disable the 'rollback' option according to user's rights - INSTEAD of 'undo'
	// this is a strange hook because it manipulates the string of each row rathern than the raw data
	// TODO - is there any other better way
 	public function hPageHistoryLineEnding ( &$row , &$s )
 	{
 		global $wgUser;

 		// keep count - if this is the first time, don't display the link
 		$rev_to_rollback_to = $row->rev_id;

 		$user_allowed_to_rollback = true;//$wgUser->isAllowed( 'rollback' ) ;
 		if( $user_allowed_to_rollback && $this->displayed_lines_of_history )
 		{
 			$url  = $this->mTitle->getLocalURL ( "action=edit" );
 			$link_for_rollback  = "<a href=\"#\" onclick=\"return kalturaRevert ( '$url' , '$rev_to_rollback_to' , '" . ktoken ( "alert_txt_revert" ) . "' );\">" .
 				"(" . ktoken ( "revert_to_version" ) . ")</a>";
		}
		else
		{
 			$link_for_rollback  = "";
		}
/*
 * <span class="mw-history-undo">
<a title="Kaltura:Video 10230" href="/wiki/index.php?title=Kaltura:Video_10230&action=edit&undoafter=179&undo=180">undo</a>
</span>
 */
 		// replace the link to display 'rollback' rathern than 'undo' and
		// call javascript before rollbacking
		$pattern = "/\([ ]*(<span[^>]*history\-undo[^>]*\>)(.*)<\/a>(<\/span>)[ ]*\)/";
		if ( strpos ( $s  , "kalturaRevert" ) === false  )
		{
 			// add text only if the origianal text doesn't include the javascript we want to add
			// this happens because our code is called several times for each history line when in 'diff' mode
	 		$res = preg_replace ( $pattern , "\\1 $link_for_rollback \\3" , $s );

	 		if ( $res == $s )
	 		{
	 			// might be that there was never the history-undo tag- ususally the last row in the list (first revision)
				if ( ! preg_match ( $pattern , $s ) )
				{
					// tidy the last line
					 $s .= " " . $link_for_rollback ;
				}
	 		}
	 		else
	 		{
	 			$s = $res ;
	 		}
		}

 		$this->displayed_lines_of_history = true;

 		return true;
 	}

/*
 * 	// a small hack to get rid of the [Edit] in the article page
 	public function hEditSectionLink ( &$linker, $nt, $section, $hint, &$url, &$result )
 	{
 		$result = "";
 		return false;
 	}
 */	
	private function getHistoryData()
	{
		if ( $this->kaltura_history_data ) return  	$this->kaltura_history_data;
		$kaltura_article_1 = MediaWiki::articleFromTitle ( $this->mTitle );
		$content_1  = $kaltura_article_1->fetchContent (  );
		$this->kaltura_history_data = kalturaHistoryData::fromText( $content_1 );

		return $this->kaltura_history_data;		
	}
	
	public function setKshowId ( $kshow_id ) 	{		$this->kshow_id  = $kshow_id; 	}
 	public function getKshowId () 	
 	{
 		if( $this->kshow_id != null )	return $this->kshow_id ;
 		$history_data = $this->getHistoryData();
 		$this->kshow_id = @$history_data->data["id"];
 		return $this->kshow_id;
 	}
 		

	public function setWidgetId ( $widget_id ) 	{		$this->widget_id  = $widget_id; 	}
 	public function getWidgetId () 	
 	{
 		if ( $this->widget_id != null ) return $this->widget_id ;
		$history_data = $this->getHistoryData();
		$this->widget_id  = 		@$history_data->data["widget_id"];	
		if ( $this->widget_id == null )
		{
			// this is for backward compatibility - for when there was no widget_id
			$kshow = $this->getKshow();
			$this->widget_id = $kshow["indexedCustomData3"];
		}
		return $this->widget_id;
 	}
 	
 	public function getKshowForArticle()
 	{
 		if ( !$this->kshow_id )
 		{
 			list ( $this->kshow_id , $this->widget_id ) = kshowIdFromArticle ( $this  );
 		}
 		return $this->kshow_id;
 	}


	private function getKshow ()
 	{
 		if ( $this->kshow == null )
 		{
	 		$kshow_id = $this->getKshowForArticle();

	 		$kaltura_user = getKalturaUserFromWgUser ();
			$kaltura_services = kalturaService::getInstance( $kaltura_user );
			$params = array (  "kshow_id" => $kshow_id );
			$res = $kaltura_services->getkshow( $kaltura_user , $params );
			$kshow = @$res["result"]["kshow"];
			// cache $kshow for the current requset
			$this->kshow  = $kshow;
 		}
		return $this->kshow;
 	}

 	private function getKshowVersion ()
 	{
 		if ( $this->kshow_version == null )
 		{
 			$kshow = $this->getKshow();
 			$version = @$kshow["version"];

			$this->kshow_version = $version;
 		}

		return $this->kshow_version;
 	}

 	private function kshowRollback ( $desired_name, $desired_description , $desired_version )
 	{
 		list ( $kshow_id , $widget_id ) = kshowIdFromArticle ( $this );
	 	$kaltura_user = getKalturaUserFromWgUser ();
		$kaltura_services = kalturaService::getInstance( $kaltura_user );

		$params = array (  "kshow_id" => $kshow_id ,
							"kshow_name" => $desired_name ,
							"kshow_description" => $desired_description ,
							"kshow_version" => $desired_version );
		$res = $kaltura_services->rollbackkshow( $kaltura_user , $params );

		$this->kshow  =  @$res["result"]["kshow"];
		$version = @$res["result"]["kshow"]["version"];

		$this->kshow_version = $version;

		return $this->kshow;
 	}

 	// $list_type: 1= LIST_TYPE_KSHOW  ,4 = LIST_TYPE_ROUGHCUT
	private function getAllEntries ( $desired_version , $list_type )
	{
		list ( $kshow_id , $widget_id ) = kshowIdFromArticle( $this );
	 	$kaltura_user = getKalturaUserFromWgUser ();
	 	// even if the user is anonymous - still fetch the gallery
		if ( 	$kaltura_user->puser_id == "" )			 $kaltura_user = getAnonymous();

		$kaltura_services = kalturaService::getInstance( $kaltura_user );

		$params = array (  "kshow_id" => $kshow_id ,
							"list_type" => $list_type ,
							"version" => $desired_version );
		$res = $kaltura_services->getallentries( $kaltura_user , $params );

		$entries = @$res["result"]["show"];
		$enties_data = @$res["result"]["roughcut_entry_data"];
		return array ( $entries ,$enties_data) ;
	}

	private function deleteKshow ()
 	{
 		$kshow_id = $this->getKshowForArticle();
		$kaltura_user = getKalturaUserFromWgUser ();
		$kaltura_services = kalturaService::getInstance( $kaltura_user , true );
		$params = array (  "kshow_id" => $kshow_id );

		$res = $kaltura_services->deletekshow( $kaltura_user , $params );
		$deleted_kshow = @$res["result"]["deleted_kshow"];

		return ( $deleted_kshow );
 	}

 	// this happens from an ajax call, so there is no real sense on making it a member function of the KalturaNamespace
	// ...therefore static
 	private static function deleteEntry ( $kshow_id , $entry_id )
 	{
		$kaltura_user = getKalturaUserFromWgUser ();
		$kaltura_services = kalturaService::getInstance( $kaltura_user , true );
		$params = array (  "kshow_id" => $kshow_id , "entry_id" => $entry_id );

		$res = $kaltura_services->deleteentry ( $kaltura_user , $params );
		$deleted_kshow = @$res["result"]["deleted_entry"];

		return ( $deleted_kshow );
 	}


 	private static function createWidgetCodePreview ( $content )
 	{
 		// noedit hides the add/edit
		return preg_replace ( "/size=\'.*?\'/is" , "size='preview'" , $content );
 	}

 	private function createWidgetCode ( $size = "l" , $align = "" , $version=null , $name=null , $description=null)
 	{
 		$kwid = kwidFromArticle( $this );
		return createWidgetTag ( $kwid , $size , $align , $version , $name , $description);
 	}

 	// TODO - move to a wiki generic funciton
 	private function renderGallery ( /*kalturaHistoryData*/ $history_data , $roughcut = true )
 	{
 		global $wgOut ;
		global $secret;

 		$version = $history_data ? @$history_data->data["version"] : null;

		// we'll fetch the gallery also if the user is not logged in
		$list_type = $roughcut ? 4 : 1; // const from the server
		list ( $entries , $entries_data ) = $this->getAllEntries ( $version , $list_type );

		// if in roughcut mode - even if there are no entries -
		$count = 0;
		if ( $entries || $roughcut )
		{
			$html = "<ul style='list-style:none'>" ;
			list ( $kshow_id , $widget_id ) = kshowIdFromArticle ( $this );
			// iterate all the entries in the roughcut - display duplicates too

			if ( $roughcut )
			{
				$can_delete = false ; // don't display delete for the roughcut gallery
//$can_delete = canUserDeleteEntry();
				if ( $entries_data )
				{
					foreach ( $entries_data as $entry_data )
					{
						$entry_id = $entry_data["id"];
						$entry = self::getEntryFromList ( $entries , $entry_id );

						$html .= self::renderEntry( $entry , $kshow_id , $can_delete , $entry_data );
						$count++;
					}
				}
			}
			else
			{
				$can_delete = canUserDeleteEntry();
				foreach ( $entries as $entry  )
				{
					$html .= self::renderEntry( $entry , $kshow_id , $can_delete , null );
					$count++;
				}
			}
			$html .= "</ul>";
			$html .= "<br clear='all'/>";
		}

		if( $count == 0 )
		{
			$html = ktoken ( "lbl_empty_gallery" );
		}

		return $html;
 	}

 	private static function renderEntry ( $entry , $kshow_id , $can_delete , $entry_data )
 	{
 		$entry_id = $entry["id"];
		$hash = hashEntryKshow ( $entry_id , $kshow_id );

		if ( $entry )
		{
			$alt = @$entry['name'];
			$src = @$entry['thumbnailUrl'];

			if ( isset ( $GLOBALS['kg_replace_local_host'] ) ) $src = str_replace ( "localhost" , "www.kaltura.com" , $src );
		}
		else
		{
			$alt = ktoken ( "lbl_already_deleted" );
			$src = "";
		}

		$len_time = @$entry_data["len_time"] ;
		if ( $len_time ) $len_time = number_format( $len_time , 1 );
		$start_time = @$entry_data["start_time"] ;

		$html =
			"<li style='float:left; width:80px; margin:5px; text-align:center;'>" .
			( $entry ?
				"<a href='#' class='kGalLink' onclick='return kalturaOpenPlayer ( \"$kshow_id\" , \"$entry_id\" , \"$start_time\" , \"$len_time\" );'><img style='width:100%; border:none; display:block;' title='$alt' alt='$alt' src='$src'' /></a>" :
				"<div style='width:80px; height:60px; background-color:#eee; line-height:60px;'>" . ktoken ( "lbl_already_deleted" ). "</div>" ) .
				"<div style='font-weight:bold; padding:0 4px 2px 4px; color:#fff; background-color:#333;'>" .
					( $entry_data != null  ? $len_time . " " . ktoken ( "lbl_seconds" ) : "" ) .
				"</div>" .
			(  $can_delete && $entry ?
				"<button onclick='deleteEntry ( \"$entry_id\" , \"$kshow_id\" , \"$hash\");'>" . ktoken ( "lbl_btn_delete" ) . "</button>" :
				"" ) .
			"</li>";

		return $html;
 	}

	private function renderHiddenPlayer ( $kwid )
	{
		global $wgOut;
		// ----- hidden widget------
		$html = "<div id='kplayer_hidden_div' style='display:none;'>";
//$html = "<div id='kplayer_div' style='display:block;'>";
		$wgOut->addHtml ( $html );
		// use size=preview for insertEntry
		$hidden_widget = createWidgetHtml ( $kwid , "preview-nokshow" , "" , null , null , null , false , true ); // prepare_vars_only = true
		$wgOut->addHtml ( $hidden_widget );
		$html = "</div>";
		$wgOut->addHtml ( $html );
	}

 	// the links is an array of titles
 	function renderLinks ( $links )
 	{
 		global $wgUser;

 		$number_of_links = 0;
 		$this_name = $this->getKshowForArticle();
		$html = "<ul>" ;
		$sk = $wgUser->getSkin();
		foreach ( $links as $title  )
		{
			// skip all the links of the redirecting pages which are from the kaltura namespace
			if ( $title->getNamespace() != KALTURA_NAMESPACE_ID )
			{
				$name = $title;
				$link = $sk->makeKnownLinkObj( $name, "" );
				$html .= "<li>{$link}</li>\n" ;
				$number_of_links++;
			}
		}
		$html .= "</ul>\n" ;

		if ( $number_of_links == 0 )
		{
			$html = ktoken ( "lbl_empty_links" );
		}
		return $html;
 	}

 	private function getEntryFromList ( $entry_list , $id )
 	{
 		if ( $entry_list == null ) return null;
		foreach ( $entry_list as $entry )
		{
			if ( $id == $entry["id"] )
				return $entry;
		}

		return null;
 	}
}

class kalturaHistoryData
{
	const SYMBOL = "kalturaHistoryData";
	// name
	// summary
	// kaltura_version
	public $data ;

	public function kalturaHistoryData ( $arr )
	{
		if ( $arr == null )
			$this->data = array();
		$this->data = $arr;
	}

	public static function fromText ( $text )
	{
		$pat = "/(" . self::SYMBOL . ")\|(\d+?)\|(.*)/s";
		preg_match ( $pat , $text , $matchs );
		$len = @$matchs[2];

		if ( is_numeric( $len ))
		{
			$data_str = substr ( $matchs[3] , 0 , $len );
			return new kalturaHistoryData ( unserialize( $data_str ) );
		}

		return new kalturaHistoryData ( null );
	}

	public function toText ( )
	{
		$ser = serialize($this->data);
		return self::SYMBOL . "|" . strlen ( $ser ) . "|" . $ser ;
	}

	// TODO - compare between 2 history_data so wil lbe easy to display in the diff mode
	public function compare ( kalturaHistoryData $other)
	{

	}
}


?>

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

class KalturaDispatcher extends SpecialPage
{
	function KalturaDispatcher(  ) {
		SpecialPage::SpecialPage("KalturaDispatcher");
		kloadMessages();
	}


	// This method is run in 2 modes - one as a special page and the other as the edit version of the kaltura article.
	// TODO - split into 2 pages:
	// 1 - special page for generating the widgets
	// 2 - edit mode of kaltura article
	// they are different flows anyway !
	function execute ( $par )
	{
		global $wgRequest, $wgOut , $wgUser;

		$kwid_str = kgetText( "kwid" );
		$kwid = kwid::fromString( $kwid_str );
		$kshow_id = kgetText( "kshow_id" );
		if ( $kwid )
		{
			$article_name = $kwid->article_name;
		}
		elseif ( $kshow_id )
		{
			// fetch the kshow from kaltura using the kshow_id
			// TODO 
	// TODO - remove ! this is only until the player passes the kwid string to all the javascript methods
			$kwid_str = kgetText( "kshow_$kshow_id"  );
			$kwid = kwid::fromString( $kwid_str );
			$article_name = $kwid->article_name;
		}
		else
		{
			// maybe a literal name was usd -
			$article_name = kgetText( "name" );
		}

		$wgOut->redirect ( Skin::makeUrl( titleFromTitle ( $article_name ) ) );
		return;
	}
}

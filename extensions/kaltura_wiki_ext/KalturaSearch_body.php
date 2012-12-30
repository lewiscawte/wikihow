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


//require_once ( "SpecialSearch.php" );
require_once ( "wiki_helper_functions.php" );
require_once ( "kalturaapi_php5_lib.php" );

class KalturaSearch extends SpecialPage
{
	// TODO - need to hook into SpecialSearchNogomatch

	function KalturaSearch (  ) {
		SpecialPage::SpecialPage("KalturaSearch");
		kloadMessages();
	}

	// this is the regular interface of specialPages
	function execute( $par ) {
		global $wgRequest, $wgUser , $wgOut;
		global $wgContLang;

		$this->setHeaders();

		$user = new KalturaSearchUser ( $wgUser );
		$search = $wgRequest->getText( 'search', $par );
		$searchPage = new SpecialSearch( $wgRequest, $user );

		SearchEngine::setNamespaces( array ( KALTURA_NAMESPACE_ID ));

		if( $wgRequest->getVal( 'fulltext' ) ||
			!is_null( $wgRequest->getVal( 'offset' ) ) ||
			!is_null ($wgRequest->getVal( 'searchx' ) ) ) {
			$searchPage->showResults( $search );
		} else {
			$searchPage->goResult( $search );
		}

		$wgOut->setPageTitle( ktoken ( 'kalturasearch') );
	}

}


class KalturaSearchUser extends User
{
	private $usr;
	public function KalturaSearchUser ( $usr )
	{
		$this->usr = $usr;
	}

	// TODO - look for a better way to enable the kaltura nmaspace only
	public function getOption( $opt  )
	{
		if ( $opt == 'searchNs' . KALTURA_NAMESPACE_ID ||  $opt == 'searchNs' . KALTURA_DISCUSSION_NAMESPACE_ID )
		{
			return true;
		}
		elseif ( strpos ( $opt , 'searchNs' ) === FALSE )
		{
			return $user->getOption ( $opt );
		}
		return false;
	}

	public function __call($function, $args)
	{

//		die();
	    $args = implode(', ', $args);
	    $usr->function ( $args);
//	    print "Call to $function() with args '$args' failed!\n";
	}
}

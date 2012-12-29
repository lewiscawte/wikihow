<?php

class  Newcontributors extends Specialpage {

    function __construct() {
        SpecialPage::SpecialPage( 'Newcontributors' );
    }

function execute( $par )
{
	global $wgUser, $wgOut, $wgLang, $wgTitle, $wgMemc, $wgDBname;
	global $wgRequest, $wgSitename, $wgLanguageCode;
	global $wgFeedClasses;
	
	require_once( 'SpecialRecentchanges.php' );

	if ($wgUser->getID() == 0) {
		$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
		return;
	}

	$this->setHeaders();
	$sk = $wgUser->getSkin();
	$wgOut->setRobotpolicy( "index,follow,noarchive" );
	$fname = "wfSpecialNewcontributors";

	# Get query parameters
	$feedFormat = $wgRequest->getVal( "feed" );

	$defaultDays = $wgUser->getOption( 'rcdays' );
	if ( !$defaultDays ) {
		$defaultDays = 7;
	}

    $limit = $wgUser->getOption( 'rclimit' );
    if ( !$limit ) { $limit = $defaults['limit']; }

    #   list( $limit, $offset ) = wfCheckLimits( 100, 'rclimit' );
    $limit = $wgRequest->getInt( 'limit', $limit );
		$dbr =& wfGetDB( DB_SLAVE );
		$userTable = $dbr->tableName( 'user' );
	
	
	$sql = "select user_id, user_name, user_real_name, count(*) as numedits from $userTable, revision where user_id = rev_user and user_registration is not null group by user_id order by user_registration desc limit $limit;";	
	
		
	$res = $dbr->query( $sql,$fname );
	$rows = array();
	while( $row = $dbr->fetchObject( $res ) ){ 
		$rows[] = $row; 
	}

	if(isset($from)) {
		$note = wfMsg( "rcnotefrom", $wgLang->formatNum( $limit ),
			$wgLang->timeanddate( $from, true ) );
	} else {
		$note = wfMsg( "newcontributorsnote", $wgLang->formatNum( $limit ), $wgLang->formatNum( $days ) );
	}
	$wgOut->addHTML( "\n<hr />\n{$note}\n<br />" );

	$note = rcDayLimitLinks( $days, $limit, "newcontributors", $hideparams, false, $minorLink, $botLink, $liuLink );


	$wgOut->addHTML( "{$note}\n" );

		$wgOut->setSyndicated( true );
		$list =& new ChangesList( $sk );
		$s = $list->beginRecentChangesList();
		$counter = 1;
		$s .= "<br/><br/>";
		foreach( $rows as $obj ){
			$s .= $this->newContributorsLine( $obj, $counter ); 
			$counter++;
			--$limit;
		}
		$s .= $list->endRecentChangesList();
		$wgOut->addHTML( $s );
	
	$dbr->freeResult( $res );
}

    function newContributorsLine($obj, $count) {
        global $wgScriptPath, $wgLang, $wgUser;

        $display = $obj->user_name;
        if ($obj->user_real_name != "")
            $display = $obj->user_real_name;
        $flag = "";
        $sk = $wgUser->getSkin();
        $t = Title::makeTitleSafe( NS_USER_TALK, $obj->user_name );
                $UTLink = $sk->makeLinkObj( $t, "Talk" );
        if ($t->getArticleID() == 0 && $obj->numedits > 2) {
            $flag = '<span class="unpatrolled">!</span>';
        }
                 $Contribs = $sk->makeKnownLinkObj( Title::makeTitle( NS_SPECIAL, 'Contributions' ), "Contributions",
                        'target=' . urlencode($obj->user_name) );
	$Block = '';
	if ( in_array( 'sysop', $wgUser->getGroups() ) ) {
                $Block = "| " . $sk->makeKnownLinkObj( Title::makeTitle( NS_SPECIAL, 'Blockip' ), "Block", 'ip=' . urlencode($obj->user_name) );
	}     

        $timestamp = $wgLang->timeanddate( $obj->min, true);
        return "$count. $flag <a href=\"$wgScriptPath/User:" . $obj->user_name . "\">$display</a> ( $UTLink | $Contribs $Block) {$obj->numedits} edits <br/>";
    }
}

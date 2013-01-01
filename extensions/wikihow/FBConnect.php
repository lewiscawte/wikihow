<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'FBConnect',
    'author' => 'Vu <vu@wikihow.com>, Travis <travis@wikihow.com>',
    'description' => 'Facebook Connect integration to wikihow',
);


$wgSpecialPages['FBConnect'] = 'FBConnect'; 
$wgAutoloadClasses['FBConnect'] = dirname( __FILE__ ) . '/FBConnect.body.php';

#$wgHooks['BeforePageDisplay'][] = array("wfAddFBConnectScript");
$wgHooks['UserLoginForm'][] = array("wfAddFBConnectScript");
$wgHooks['FBLoginForm'][] = array("wfAddFBConnectScript");

function wfAddFBConnectScript () {
	global $wgOut, $wgFBConnectAPIkey, $wgLanguageCode;

	// INTL: Facebook requires a locale code to display the appropriate language.  Since we're just updating spanish and german, create quick and dirty locale strings here
	$localeCode = "en_US";
	if ($wgLanguageCode == 'es' || $wgLanguageCode == 'de') {
		$localeCode = $wgLanguageCode . "_" . strtoupper($wgLanguageCode);
	}

	$wgOut->addScript('<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/' . $localeCode . '" type="text/javascript"></script>');
	$wgOut->addScript('<script type="text/javascript">FB.init("' . $wgFBConnectAPIkey. '", "/extensions/wikihow/xd_receiver.htm");</script>');
	return true;

}

# magic words for user profile pages
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
    $wgHooks['ParserFirstCallInit'][] = 'wfFBConnectParserFunction_Setup';
} else {
    $wgExtensionFunctions[] = 'wfWikiHowParserFunction_Setup';
}


$wgHooks['MagicWordMagicWords'][] 			= 'wfFBConnectMagicWords';
$wgHooks['MagicWordwgVariableIDs'][] 		= 'wfFBConnectwgVariableIDs';
$wgHooks['LanguageGetMagic'][] 				= 'wfFBConnectLanguageGetMagic';

function wfFBConnectParserFunction_Setup() {
	global $wgParser;
	$wgParser->setFunctionHook('FBPROFILE', 'facebookprofile');
	return true;
}

function wfFBConnectMagicWords(&$magicWords) {
	$magicWords[] = 'facebookprofile';
	return true;
}

function wfFBConnectwgVariableIDs(&$wgVariableIDs)
{
        //$wgVariableIDs[] = 'facebookprofile';
        return true;
}

function wfFBConnectLanguageGetMagic(&$magicWords, $langCode)
{
        switch($langCode)
        {
            default :
                $magicWords['FBPROFILE']      = array( 0, 'FBPROFILE' );
        }
        return true;
}

function facebookprofile($parser, $vars) {
	global $wgFBConnectAPIkey, $wgFBConnectSecret, $wgTitle;

	if ($wgTitle->getNamespace() != NS_USER) {
		return '';
	}
	$u= User::newFromName($wgTitle->getText());
	$u->load();
	$ret = "";
	$params = split("/[ ]*,[ ]*/", $vars);
	require_once('extensions/fbconnect/facebook-platform/php/facebook.php');
	// get user id from DB
	$dbr = wfGetDB(DB_SLAVE);

    $fb_userid = $dbr->selectField( 'facebook_connect',
            array('fb_user'),
            array('wh_user' => $u->getID())
            );

	if (!$fb_userid) {
		return "No user id associated with {$wgTitle->getText()}, has this user connected through facebook before?";
	}
	$facebook = new Facebook($wgFBConnectAPIkey, $wgFBConnectSecret);
	$result= $facebook->api_client->users_getInfo($fb_userid, $vars);

	$facebook = new Facebook($wgFBConnectAPIkey, $wgFBConnectSecret);
	foreach ($result[0] as $k => $v) {
		if ($k == 'uid') continue;
		if ($k == 'pic') 
			$ret .= "<img src='{$v}'/><br/><br/>";
		else if ($k == 'status') 
			$ret .= "$k: {$v['message']}<br/><br/>";
		else
			$ret .= "$k: $v<br/><br/>";
	}	
	return array($ret, 'noparse' => true, 'isHTML' => true);
}


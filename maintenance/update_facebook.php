<?

require_once( "commandLine.inc" );
require_once('extensions/wikihow/facebook/client/facebook.php');

date_default_timezone_set($wgLocaltimezone);
$appapikey = 'd9750a5976aa776dbdc1b48dc31920ee';
$appsecret = '6219a0b85473cff9dcbe154eb3d3b64e';
$facebook = new FacebookAPI($appapikey, $appsecret);
#$facebook->set_user('624756097', '178e56f5de603674a846da69-624756097');
/*
$html = '<fb:title>The FBML Test Metaconsole</fb:title>
<fb:subtitle>from wikiHow</fb:subtitle>
<form>
<b>meta preview</b>
<div id="preview" style="border-style: solid; border-color: black; 
  border-width: 1px; padding: 5px;">
</div>
<br />
<textarea name="fbml">
</textarea><br />
<input type="submit"
  clickrewriteurl="http://wikidiy.com/facebooktest.php"
  clickrewriteid="preview" value=" click to update "/>
</form>';

$facebook->api_client->profile_setFBML($html, 624756097);
*/
#$html = wfGetFacebookHTML();
require_once("extensions/wikihow/Facebook.body.php");
$html_with_images = Facebook::getFacebookHTML(true);
#echo $html_with_images; exit;
$facebook->api_client->session_key = '713c8c1ad20765b1afdb656f-624756097';

echo $html . "\n\n"; 
$dbr = &wfGetDB(DB_SLAVE);
$res = $dbr->query('select distinct(facebook_user) from facebook_sessions group by facebook_user order by last_update desc');
while ($row = $dbr->fetchObject($res)) {
	try {
	echo "Updating {$row->facebook_user} \n";
	#$facebook->set_user($row->facebook_user, $row->session_key);
	//if ($row->facebook_user == '705845533' || $row->facebook_user == '629097252' || $row->facebook_user == '876505499')	
	$facebook->api_client->profile_setFBML($html_with_images, $row->facebook_user);
//	$facebook->api_client->profile_setFBML($html, $row->facebook_user);
	#$facebook->api_client->profile_setFBML($html, '705845533');
	} catch (Exception $e) {
		echo "Error updating  {$row->facebook_user} Message: {$e->getMessage()}\n";
	}
	#break;
}
$dbr->freeResult($res);

?>

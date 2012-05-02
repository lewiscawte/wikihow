<?
  $username="Reuben"; // fill this in!
  $password="xwhx"; // fill this in!

require_once('commandLine.inc');
require('SxWiki.php');

//* GET THE LOCAL MESSAGES
$days = !empty($argv[0]) ? $argv[0] : 7;
$msgs = array();
$dbr = wfGetDB(DB_SLAVE);
$ts = wfTimestamp(TS_MW, time() - ($days * 24 * 60 * 60));
echo "getting mediawiki messages that have been updated since $ts\n";
$res = $dbr->query("select rc_title, max(rc_timestamp) as ts from recentchanges where rc_namespace=" . NS_MEDIAWIKI . " and rc_timestamp > '{$ts}' and rc_user_text ='{$username}' group by rc_title");
while ($row= $dbr->fetchObject($res)) {
	$msgs[$row->rc_title] = $row->ts;
}

//* CHECK THE REMOTE MESSAGES
$sx=new SxWiki;
$sx->username=$username;
$sx->password=$password;
$sx->url = "http://www.wikihow.com/";

foreach ($msgs as $title=>$update) {
	$page = "MediaWiki:{$title}";
	$info = $sx->getPageInfo($page);
	if (!isset($info['touched'])) {
		echo "$page doesn't exist on production";
	} else {
		$remote = wfTimestamp(TS_UNIX, $info['touched']);
		$local =  wfTimestamp(TS_UNIX, $update);
		if ($local > $remote) {
			echo "$page Local copy newer local $update remote $ts";
		} else {
			continue;
			//echo "$page Remote copy newer local $update remote $ts";
		}
	}
	echo ": Update remote copy? [y/n] ";
	$line = trim(fgets(STDIN));
	if ($line == "y") {
		$title = Title::makeTitle(NS_MEDIAWIKI, $title);
		$rev = Revision::newFromTitle($title);
		if ($sx->putPage($page,"Updating production message",$rev->getText())){
			echo "$page has been updated\n";
		} else {
			print_r($sx);
			echo "error updating $page\n";
		}
	}  else {
		echo "Not updating $page\n";
	}
}
?>

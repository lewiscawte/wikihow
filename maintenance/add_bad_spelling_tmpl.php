<?
require_once( "commandLine.inc" );

$wgUser->setId(1236205);

	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->query(
		'select sa_page_id from spellcheck_articles where sa_misspelled_count > 0;'
			);
	while ( $row = $dbr->fetchObject($res) ) {
		$title = Title::newFromID($row->sa_page_id);
		$revision = Revision::newFromTitle($title);
		$text = $revision->getText();
		if (strpos($text, "{{copyedit") == false) {
			$text = '{{copyeditbot}}'.$text;
			//echo $text; break;
			$a = new Article(&$title);
			$a->updateArticle($text, "Adding internal copyedit template.", true, false);
			echo "updating {$title->getFullURL()}\n";
			//break;
		} else {
			echo "NOT UPDATING {$title->getFullURL()}\n";
		}
	}	
	$dbr->freeResult($res);
?>

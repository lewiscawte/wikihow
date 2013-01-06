<?
require_once( "commandLine.inc" );

$wgUser = new User();
$wgUser->setName('KudosArchiver');
	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->select(
			array('page', 'user'),
			array( 'page_title', 'page_namespace', 'user_name'),
			array ('page_namespace' => NS_USER_KUDOS,
				'page_len>80000',
				'page_title=user_name',	
				),
			"archiveUserKuods"
			);
	while ( $row = $dbr->fetchObject($res) ) {
		try {

			$num_titles = 0;
			$ot = Title::makeTitle( $row->page_namespace, $row->page_title );
			$links = array();
			for ($x = 1; ; $x++) {
				$t  = Title::makeTitle(NS_USER_KUDOS,wfMsg('user_kudos_archive_url', $row->user_name, $x));
				if ($t->getArticleID() == 0)
					break;
				$num_titles++;
				$links[] .= "[[{$t->getPrefixedText()}|$x]]";
			} 
			$links[] .= "[[{$t->getPrefixedText()}|" . ($num_titles+1) . "]]";
			$nt  = Title::makeTitle(NS_USER_KUDOS,wfMsg('user_kudos_archive_url', $row->user_name, $num_titles+1));
print ("Moving {$ot->getFullText()} to {$nt->getFullText()}\n");
			$ot->moveNoAuth($nt);
			
			$text = wfMsg('user_kudos_archive_title') . implode(", ", $links);
			$a = new Article(&$ot);
			$a->updateArticle(&$text, wfMsg('user_kudos_archive_summary'), false, false);
print ("Setting new text $text\n");
		} catch (Exception $e) {

		}
	}	
	$dbr->freeResult($res);
?>

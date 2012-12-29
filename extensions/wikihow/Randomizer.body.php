<?
class Randomizer extends SpecialPage {

    // youtube, 5min, etc.
    public $mSource;

    function __construct() {
		SpecialPage::SpecialPage( 'Randomizer' );
	}

	function getRandomTitle() {
		$t = null;
        $dbr = wfGetDB( DB_SLAVE );
        $page = $dbr->tableName( 'page' );
		$randstr = wfRandom();
        $sql = "SELECT page_title
            FROM $page , randompage
            WHERE page_namespace = 0
			AND page_id = rp_page	
            AND page_is_redirect = 0 
            AND page_random >= $randstr
            ORDER BY page_random";
        $sql = $dbr->limitResult( $sql, 1, 0 );
		for ($i = 0; $i < 3; $i++) {
        	$res = $dbr->query( $sql, $fname );
        	$row = $dbr->fetchObject( $res );
			$t = Title::makeTitleSafe( $this->namespace, $row->page_title );
			if ($t) break;
		}
		return $t;
	}

	function execute() {
		global $wgOut;
        $fname = 'Randomizer::execute';
		global $wgLanguageCode;
		if ($wgLanguageCode != 'en') {
			$rp = new RandomPage();
			$title = $rp->getRandomTitle();
		} else {
			$title = $this->getRandomTitle();
		}

		$wgOut->redirect( $title->getFullUrl() );
	}
}




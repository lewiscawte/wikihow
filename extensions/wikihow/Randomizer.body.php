<?
class Randomizer extends SpecialPage {

    // youtube, 5min, etc.
    public $mSource;

    function __construct() {
		SpecialPage::SpecialPage( 'Randomizer' );
	}

	function getRandomTitle() {
		global $wgUser; 

        $fname = 'Randomizer::getRandomTitle';
        wfProfileIn( $fname );

		$cat = $wgUser->getCatFilter();

		$t = null;
        $dbr = wfGetDB( DB_SLAVE );
        $page = $dbr->tableName( 'page' );
		$randstr = wfRandom();
        $sql = "SELECT page_title
            FROM $page
            WHERE page_randomizer = 1
            AND page_random >= $randstr
			{$cat}
            ORDER BY page_random";
        $sql = $dbr->limitResult( $sql, 1, 0 );
		for ($i = 0; $i < 3; $i++) {
        	$res = $dbr->query( $sql, $fname );
        	$row = $dbr->fetchObject( $res );
			$t = Title::makeTitleSafe( $this->namespace, $row->page_title );
			if ($t) break;
		}

		wfProfileOut($fname); 
		return $t;
	}

	function execute() {
		global $wgOut, $wgLanguageCode;
        $fname = 'Randomizer::execute';
        wfProfileIn( $fname );
		if ($wgLanguageCode != 'en') {
			$rp = new RandomPage();
			$title = $rp->getRandomTitle();
		} else {
			$title = $this->getRandomTitle();
		}
		$wgOut->redirect( $title->getFullUrl() );
		wfProfileOut($fname); 
	}
}




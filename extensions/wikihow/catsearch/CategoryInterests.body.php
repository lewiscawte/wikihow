<?

/*
* A utility class that assists in storing and retreiving category interests for users.
* See the CategorySearchUI for the accompanying interface that uses this class.
* DB Schema:
*
*	CREATE TABLE `category_interests` (
*	  `ci_user_id` mediumint(8) unsigned NOT NULL default '0',
*	  `ci_category` varchar(255) NOT NULL default '',
*	  PRIMARY KEY  (`ci_user_id`,`ci_category`),
*	  KEY `ci_category` (`ci_category`)
*	) ENGINE=InnoDB DEFAULT CHARSET=latin1
*/
class CategoryInterests extends UnlistedSpecialPage {

	function __construct() { 
		UnlistedSpecialPage::UnlistedSpecialPage( 'CategoryInterests' );
	}
	

	function execute($par) {
		global $wgOut, $wgRequest;

		$fname = 'CategoryInterests::execute';
		wfProfileIn( $fname );

		$retVal = false;
		$action = $wgRequest->getVal('a');
		$category = $wgRequest->getVal('cat');
		switch ($action) {
			case 'get':
				$retVal = $this->getCategoryInterests();
				break;
			case 'add':
				$retVal = $this->addCategoryInterest($category);
				break;
			case 'remove':
				$retVal = $this->removeCategoryInterest($category);
				break;
			default: 
				// Oops. Didn't understad the action
				$retVal = false;
		}

		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHtml(json_encode($retVal));
		return;

		wfProfileOut( $fname );
	}

	/*
	*	Returns a list of categories in the form of the title name
	*/
	public static function getCategoryInterests() {
		global $wgUser;
		
		$catInterests = array();

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('category_interests', array('ci_category'), array('ci_user_id' => $wgUser->getId()));
		while ($row = $dbr->fetchObject($res)) {
			$catInterests[] = $row->ci_category;
		}

		return $catInterests;
	}

	/*
	* Insert a category into the category_interest table for the logged in user. Categories should be in the form of the category url title.
	* Ex: Arts-and-Entertainment or Actor-Appreciation
	*/
	public static function addCategoryInterest($category) {
		global $wgUser;

		// Don't add a category if it isn't a valid category title
		$t = Title::newFromText($category, NS_CATEGORY);
		if (!$t->exists()) {
			return false;
		}

		if ($wgUser->getId() == 0) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);
		$category = $dbw->strencode($category);
		return $dbw->insert('category_interests', array('ci_user_id' => $wgUser->getId(), 'ci_category' => $category), 'CategoryInterests::addCategoryInterest', array('IGNORE'));
	}

	/*
	* Removes a category from the category_interest table for the logged in user. Categories should be in the form of the category url title.
	* Ex: Arts-and-Entertainment or Actor-Appreciation
	*/
	public static function removeCategoryInterest($category) {
		global $wgUser;

		if ($wgUser->getId() == 0) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);
		$category = $dbw->strencode($category);
		return $dbw->delete('category_interests', array('ci_user_id' => $wgUser->getId(), 'ci_category' => $category));
	}
}

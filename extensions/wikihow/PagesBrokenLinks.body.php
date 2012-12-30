<?
class PagesBrokenLinks extends SpecialPage {

    function __construct() {
        SpecialPage::SpecialPage( 'PagesBrokenLinks' );
    }


    function execute ($par) {
		list( $limit, $offset ) = wfCheckLimits();
		$lpp = new BrokenLinksPage();
		return $lpp->doQuery( $offset, $limit );
	}

}	
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */
class BrokenLinksPage extends PageQueryPage{
	
		function getName() {
			return "Pageswithbrokenlinks";
		}
	
		function sortDescending() {
			return false;
		}
	
		function isExpensive() {
			return false;
		}

		function getPageHeader() {
			return "This a list of pages that have broken links on them. This list only updates Monday morning.<br/><br/>";
		}
		function isSyndicated() { return false; }
	
		function getSQL() {
			$dbr =& wfGetDB( DB_SLAVE );
			extract( $dbr->tableNames( 'page', 'pagelinks' ) );

			$sql = "SELECT 'Pageswithbrokenlinks'  AS type,
	                  p1.page_namespace AS namespace,
	                  p1.page_title     AS title,
	                  p1.page_title     AS value
	             FROM pageswithbrokenlinks 
	        LEFT JOIN page p1
	               ON p1.page_id=pbl_page";
			return $sql;
    
    }
		function getOrder() {
			return " GROUP by value ORDER BY value ";
		}
}

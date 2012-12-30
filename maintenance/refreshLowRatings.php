<?
require_once( "commandLine.inc" );

$dbw =& wfGetDB( DB_MASTER );
$avg = wfMsg('list_bottom_rated_pages_avg');
$minvotes = wfMsg('list_bottom_rated_pages_min_votes');
$cleardays = wfMsg('list_bottom_rated_pages_clear_limit_days');

$dateDiff = strtotime('now - 1 month');
$res = $dbw->select(
	array('rating'),
	array('distinct rat_page'),
	array('rat_deleted_when > FROM_UNIXTIME('.$dbw->addQuotes($dateDiff).')'),
	'refreshLowRating.php:newlyDeleted');

$newlyDeletedPages = array();
while ($row = $dbw->fetchRow($res)) {
	$newlyDeletedPages[] = $row['rat_page'];
}

$dbw->query("delete from rating_low;", 'refreshLowRatings.php:delete');
$dbw->query("insert into rating_low select rat_page, avg(rat_rating) as R, count(*) as C from rating, page where rat_page=page_id and rat_page NOT IN (".join(',', $newlyDeletedPages).") and rat_isdeleted = 0 and page_is_redirect = 0 group by rat_page having R <= $avg and C >= $minvotes ; ",
	'refreshLowRatings.php:refresh');
?>

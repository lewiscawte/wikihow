<?
	$me = Title::makeTitle(NS_SPECIAL, "LSearch");
	$enc_q = htmlspecialchars($q);
?>
<form id='search_site' action='<?= $me->getFullURL() ?>' method='get' >
	<p>
	<input type='hidden' name='fulltext' value='Search'/>
	<input type='text' id='keywords' name='search' size='40' maxlength='75' value="<?= $enc_q ?>" />
	<input type='submit' class='SearchMe' value='<?= wfMsg('search') ?>' />
	</p></form>

<?
	// refactor: set vars if $q == empty
	if ($q == null):
		return;
	endif;
?>

<? if ($suggestionLink): ?>
	<?= wfMsg('lsearch_suggestion', $suggestionLink) ?>
<? endif; ?>

<? if (sizeof($results) == 0): ?>
	<?= wfMsg('lsearch_noresults', htmlspecialchars($q)) ?>
	<? return; ?>
<? endif; ?>

<div id='searchresults_list'>
Search for '<?= $q ?>', showing results <?= $first ?> through <?= $last ?><br/><br/>
<? foreach($results as $i => $result): ?>
	<div class='searchresult_<?= intval($i % 2 == 0) ?>'><a href="<?= $result['url'] ?>"><?= $result['title_match'] ?></a></div>
<? endforeach; ?>
</div>

<?
if (($total > $start + $max_results
	  && $last == $start + $max_results)
	|| $start >= $max_results): ?>

<div style='width: 600px; padding: 10px; margin: 10px; font-size:120%; font-weight: bold; border: 1px solid #eee;'>

<div style='float:left; width: 300px;'>
<? // "< Prev" link ?>
<? if ($start - $max_results >= 0): ?>
	<?= $sk->makeLinkObj($me, wfMsg("lsearch_previous"), "search=" . urlencode($q) . ($start - $max_results !== 0 ? "&start=" . ($start - $max_results) : '')) ?>
<? endif; ?>
&nbsp;
</div>

<div style='float:right; width: 250px; text-align: right;'>
<? // "Next >" link ?>
<? if ($total > $start + $max_results && $last == $start + $max_results): ?>
	<?= $sk->makeLinkObj($me, wfMsg("lsearch_next"), "search=" . urlencode($q) . "&start=" . ($start + $max_results)) ?>
<? endif; ?>
</div>

<br/>
</div>

<? endif; ?>

<?= wfMsg('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?>


<form id='search_site' action='<?= $me->getFullURL() ?>' method='get' >
	<p>
	<input type='hidden' name='fulltext' value='Search'/>
	<input type='text' id='keywords' name='search' size='40' maxlength='75' value="<?= $enc_q ?>" />
	<input type='submit' class='SearchMe' value='<?= wfMsg('search') ?>' />
	<? if (count($results) > 0): ?>
		<span><?= wfMsg('lsearch_num_results', $total) ?></span>
	<? endif; ?>
	</p></form>

<?
	// refactor: set vars if $q == empty
	if ($q == null):
		return;
	endif;
?>

<? if (count($results) > 0): ?>
	<div>
		<?= wfMsgForContent('lsearch_results_for', $enc_q) ?>
	</div>
	<br/>
<? endif; ?>

<? if ($suggestionLink): ?>
	<?= wfMsg('lsearch_suggestion', $suggestionLink) ?>
<? endif; ?>

<? if (count($results) == 0): ?>
	<?= wfMsg('lsearch_noresults', $enc_q) ?>
	<? return; ?>
<? endif; ?>

<div id='searchresults_list'>
<? foreach($results as $i => $result): ?>
	<div>
		<? if (!empty($result['img_thumb_100'])): ?>
			<div style="float:right;">
				<img src="<?= $result['img_thumb_100'] ?>" />
			</div>
		<? endif; ?>

		<a href="<?= $result['url'] ?>"><?= $result['title_match'] ?></a>
		<? if (!empty($result['first_editor'])): ?>
			<div>
				<?
					$editorLink = $sk->makeLinkObj(Title::makeTitle(NS_USER, $result['first_editor']), $result['first_editor']);
				?>
				<? if ($result['num_editors'] <= 1): ?>
					<?= wfMsg('lsearch_edited_by', $editorLink) ?>
				<? elseif ($result['num_editors'] == 2): ?>
					<?= wfMsg('lsearch_edited_by_other', $editorLink, $result['num_editors'] - 1) ?>
				<? else: ?>
					<?= wfMsg('lsearch_edited_by_others', $editorLink, $result['num_editors'] - 1) ?>
				<? endif; ?>
			</div>

			<? if (!empty($result['last_editor']) && $result['num_editors'] > 1): ?>
				<div>
					<?= wfMsg( 'lsearch_last_updated', wfTimeAgo($result['timestamp'], true), $sk->makeLinkObj(Title::makeTitle(NS_USER, $result['last_editor']), $result['last_editor']) ) ?>
				</div>
			<? endif; ?>
		<? endif; ?>

		<div>
			<? if ($result['is_featured']): ?>
				<span><?= wfMsg('lsearch_featured') ?></span>
			<? endif; ?>
			<? if ($result['has_video']): ?>
				<span><?= wfMsg('lsearch_has_video') ?></span>
			<? endif; ?>
			<span><?= wfMsg('lsearch_steps', $result['steps']) ?></span>
			<span>
			<? if ($result['popularity'] < 100): ?>
				<?= wfMsg('lsearch_views_tier0') ?>
			<? elseif ($result['popularity'] < 1000): ?>
				<?= wfMsg('lsearch_views_tier1') ?>
			<? elseif ($result['popularity'] < 10000): ?>
				<?= wfMsg('lsearch_views_tier2') ?>
			<? elseif ($result['popularity'] < 100000): ?>
				<?= wfMsg('lsearch_views_tier3') ?>
			<? else: ?>
				<?= wfMsg('lsearch_views_tier4') ?>
			<? endif; ?>
		</div>

		<div class="clearall"></div>
	</div>
	<? if ($i != count($results) - 1): ?>
		<hr>
	<? endif; ?>
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
<? else: ?>
	<?= wfMsg("lsearch_previous") ?>
<? endif; ?>
&nbsp;
</div>

<?= wfMsg('lsearch_results_range', $first, $last, $total) ?>

<div style='float:right; width: 250px; text-align: right;'>
<? // "Next >" link ?>
<? if ($total > $start + $max_results && $last == $start + $max_results): ?>
	<?= $sk->makeLinkObj($me, wfMsg("lsearch_next"), "search=" . urlencode($q) . "&start=" . ($start + $max_results)) ?>
<? else: ?>
	<?= wfMsg("lsearch_next") ?>
<? endif; ?>
</div>

<br/>
</div>

<? endif; ?>

<?= wfMsg('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?>


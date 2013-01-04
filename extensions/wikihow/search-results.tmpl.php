<form id='search_site' action='<?= $me->getFullURL() ?>' method='get' >
	<div id='search_head'>
	<input type='hidden' name='fulltext' value='Search'/>
	<input type='text' id='keywords' name='search' size='40' maxlength='75' value="<?= $enc_q ?>" />
	<? if (count($results) > 0): ?>
		<span class='result_count'><?= wfMsg('lsearch_num_results', $total) ?></span>
	<? endif; ?>
	<input type='submit' class='button button136 input_button' value='<?= wfMsg('search') ?>' />
	</div></form>

<?
	// refactor: set vars if $q == empty
	if ($q == null):
		return;
	endif;
?>

<? if (count($results) > 0): ?>
	<div class="sr_for">
		<?= wfMsgForContent('lsearch_results_for', $enc_q) ?>
	</div>
<? endif; ?>

<? if ($suggestionLink): ?>
	<div class="sr_suggest"><?= wfMsg('lsearch_suggestion', $suggestionLink) ?></div>
<? endif; ?>

<? if (count($results) == 0): ?>
	<div class="sr_noresults"><?= wfMsg('lsearch_noresults', $enc_q) ?></div>
	<div id='searchresults_footer'><br /></div>
	<? return; ?>
<? endif; ?>

<div id='searchresults_list'>
<? foreach($results as $i => $result): ?>
	<div class="result">
		<? if (!$result['is_category']): ?>
			<? if (!empty($result['img_thumb_100'])): ?>
				<div class='result_thumb'><img src="<?= $result['img_thumb_100'] ?>" /></div>
			<? endif; ?>
		<? else: ?>
			<div class='result_thumb cat_thumb'><img src="<?= $result['img_thumb_100'] ? $result['img_thumb_100'] : '/skins/WikiHow/images/Book_75.png' ?>" /></div>
		<? endif; ?>

<?
	$url = $result['url'];
	if (!preg_match('@^http:@', $url)) {
		$url = $BASE_URL . '/' . $url;
	}
?>

		<? if ($result['has_supplement']): ?>
			<? if (!$result['is_category']): ?>
				<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
			<? else: ?>
				<a href="<?= $url ?>" class="result_link"><?= wfMsg('lsearch_article_category', $result['title_match']) ?></a>
			<? endif; ?>

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
						<?= wfMsg( 'lsearch_last_updated', wfTimeAgo(wfTimestamp(TS_UNIX, $result['timestamp']), true), $sk->makeLinkObj(Title::makeTitle(NS_USER, $result['last_editor']), $result['last_editor']) ) ?>
					</div>
				<? endif; ?>
			<? endif; ?>

			<ul class="search_results_stats">
				<? if ($result['is_featured']): ?>
					<li class="sr_featured"><?= wfMsg('lsearch_featured') ?></li>
				<? endif; ?>
				<? if ($result['has_video']): ?>
					<li class="sr_video"><?= wfMsg('lsearch_has_video') ?></li>
				<? endif; ?>
				<? if ($result['steps'] > 0): ?>
					<li class="sr_steps"><?= wfMsg('lsearch_steps', $result['steps']) ?></li>
				<? endif; ?>

				<li class="sr_view">
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
				<? endif; ?></li>
			</ul>
		<? else: ?>
			<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
		<? endif; // has_supplement ?>

		<div class="clearall"></div>
	</div>
<? endforeach; ?>
</div>

<?
if (($total > $start + $max_results
	  && $last == $start + $max_results)
	|| $start >= $max_results): ?>

<div id='searchresults_footer'>

<div class="sr_next">
<? // "Next >" link ?>
<? if ($total > $start + $max_results && $last == $start + $max_results): ?>
	<?= $sk->makeLinkObj($me, wfMsg("lsearch_next"), "search=" . urlencode($q) . "&start=" . ($start + $max_results)) ?>
<? else: ?>
	<?= wfMsg("lsearch_next") ?>
<? endif; ?>
</div>

<div class='sr_prev'>
<? // "< Prev" link ?>
<? if ($start - $max_results >= 0): ?>
	<?= $sk->makeLinkObj($me, wfMsg("lsearch_previous"), "search=" . urlencode($q) . ($start - $max_results !== 0 ? "&start=" . ($start - $max_results) : '')) ?>
<? else: ?>
	<?= wfMsg("lsearch_previous") ?>
<? endif; ?>
&nbsp;
</div>

<?= wfMsg('lsearch_results_range', $first, $last, $total) ?>

<div class="sr_text"><?= wfMsg('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?></div>

</div>

<? endif; ?>

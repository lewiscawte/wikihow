
<div id='nfd_options'></div>
<div id='nfd_head'>
	<h1 id='nfd_articletitle'><a href='<?= $titleUrl ?>' target='new'><?= wfMsg('howto', $title) ?></a></h1>
	<div id='nfd_skip_div'>
		<div id='nfd_skip_arrow'></div>
		<a href='#' id='nfd_skip'><?= wfMsg('nfd_skip_article') ?></a>
	</div>
	<a href='#' class='button white_button_100' id='nfd_delete' onmouseout='button_unswap(this);' onmouseover='button_swap(this);'><?= wfMsg("nfd_button_delete"); ?></a>
	<a href='#' class='button white_button_100' id='nfd_keep' onmouseout='button_unswap(this);' onmouseover='button_swap(this);'><?= wfMsg("nfd_button_keep"); ?></a>
	<a href='#' class='button button100' id='nfd_save' onmouseout='button_unswap(this);' onmouseover='button_swap(this);'><?= wfMsg("nfd_button_save"); ?></a>
</div>
<?= $articleInfo ?>
<input type='hidden' id='qcrule_choices' value='' />
<div id="article_tabs">
	<a href="#" id="tab_article" title="Article" class="on" onmousedown="button_click(this);">Article</a>
    <span id="gatEdit"><a href="#" title="Edit" id="tab_edit"><div class="tab_pencil edit_pencil"></div><?= wfMsg('edit'); ?></a></span>
	<span id="gatDiscussionTab"><a href="#" id="tab_discuss" title="<?= wfMsg('discuss') ?>" onmousedown="button_click(this);"><?= wfMsg('discuss') ?></a></span>
    <a href="#" id="tab_history"  title="<?= wfMsg('history') ?>" onmousedown="button_click(this);"><?= wfMsg('history') ?></a>
</div><!--end article_tabs-->
<div id="article_tabs_line"></div>
<div id="articleBody" class="nfd_tabs_content">
	<?= $articleHtml ?>
</div>
<div id="articleEdit" class="article_inner nfd_tabs_content"></div>
<div id="articleDiscussion" class="article_inner nfd_tabs_content"></div>
<div id="articleHistory" class="article_inner nfd_tabs_content"></div>
<input type='hidden' name='nfd_id' value='<?= $nfdId ?>'/>
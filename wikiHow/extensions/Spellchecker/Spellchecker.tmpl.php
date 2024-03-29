<div id="spch-container">
	<div id="spch-head">
		<div id="spch-options">
			<div id="spch-skip"><a href="#"><?= wfMsg("spch-no"); ?></a><div id="spch-skip-arrow"></div></div>
			<a href="#" class="button spch-button-yes" id="spch-yes"><?= wfMsg('spch-yes'); ?></a>
		</div>
		<h1><?= wfMsg('spch-question'); ?></h1>
		<p id="spch-help"><?=wfMsg('spch-instructions') ?></p>
		<a href="#" id="spch-add-words"> Add all words to whitelist</a>
	</div>
	<div id='spch-error'>
		An error occurred while trying to get another article.
		Please try again later.
	</div>
	<div id='spch-title'><h1></h1></div>
	<div id='spch-preview'></div>
	<div id='spch-edit'>
		<div id='spch-content'></div>
		<div id='spch-summary'></div>
		<div id='spch-buttons'></div>
		<div id="spch-words">
			<h3 style="color: #4A3C31">Add word to whitelist</h3>
			<p>Select the words you want to add to the whitelist.</p>
			<div class="spch-word-list">
				
			</div>
			<a href="#" class="button white_button_100 spch-add">Add words</a>
			<div class="spch-message"></div>
		</div>
	</div>
	<div id='spch-id'></div>
	<div class='spch-waiting'><img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' alt='' /></div>
</div>
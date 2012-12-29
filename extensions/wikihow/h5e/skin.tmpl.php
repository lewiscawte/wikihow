<?php
	define('H5E_URL_REV', 1);
?>
<link rel="stylesheet" href="/extensions/min/?f=/extensions/wikihow/h5e/skin.css,/extensions/wikihow/eiu/easyimageupload.css&<?= H5E_URL_REV ?>" />
<script src="http://www.google.com/jsapi?key=<?= $GOOGLE_SEARCH_API_KEY ?>" type="text/javascript"></script>
<script src="/extensions/wikihow/h5e/html5editor.js"></script>
<script src="/extensions/min/?f=/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery.ui.sortable.min.js,/skins/common/ac.js"></script>
<!--<script type="text/javascript" src="/extensions/min/?f=/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.1.custom.min.js,/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/h5e/html5editor.js&<?= H5E_URL_REV ?>"></script>-->
<?php
	$langKeys = array('new-link','howto','h5e-ref','h5e-new-section','h5e-new-alternate-method','h5e-new-method','h5e-references-removed','h5e-references-removed','h5e-loading','h5e-more-results','Ingredients','Steps','Video','Tips','Warnings','relatedwikihows','sourcescitations','thingsyoullneed', 'h5e-edit-description-examples', 'h5e-changes-to-be-discarded', 'import-content', 'import-content-url', 'h5e-add-reference', 'h5e-edit-reference', 'h5e-add');
?>
<script>
	if (typeof WH == 'undefined') WH = {};
	if (typeof WH.lang == 'undefined') WH.lang = {};
	jQuery.extend(WH.lang, {
<?
		$len = count($langKeys); 
		foreach ($langKeys as $i => $key):
			$msg = preg_replace('@([\'\\\\])@', '\\\\$1', wfMsg($key));
?>
			'<?= $key ?>': '<?= $msg ?>'<?= ($i == $len - 1 ? '' : ',') ?>
		<? endforeach; ?>
	});
</script>

<div class="h5e-edit-link-options-over">
	<span class="h5e-edit-link-inner">
		<?= wfMSg('h5e-goto-link') ?>
		<a id="h5e-editlink-display" href="#"></a> - 
		<a id="h5e-editlink-change" href="#"><?= wfMsg('h5e-change') ?></a> - 
		<a id="h5e-editlink-remove" href="#"><?= wfMsg('h5e-remove') ?></a> - 
		<a id="h5e-editlink-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
	</span>
</div>

<div id="h5e-link-dialog" class="h5e-dialog" title="<?= wfMsg('change-link-dialog') ?>">
	<?= wfMsg('h5e-text') ?> <input id="h5e-link-text"/><br/>
	<?= wfMsg('h5e-article') ?> <input id="h5e-link-article" disabled="disabled"/> (<a id="h5e-link-preview" target="_blank">view</a>)<br/>
	<?= wfMsg('h5e-search') ?> <input id="h5e-link-query"/>&nbsp;<input id="h5e-link-search-button" type="button" value="<?= wfMsg('search') ?>"/><br/>
	<br/>
	<div id="h5e-link-query-results"> </div>
	<input id="h5e-link-change" type="button" value="<?= wfMsg('h5e-change') ?>"/>
	<input id="h5e-link-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
</div>

<div id="h5e-sections-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-sections') ?>">
	<div id="h5e-sections">
	</div>
	<br/>
	<input id="h5e-sections-change" type="button" value="<?= wfMsg('h5e-change') ?>"/>
	<input id="h5e-sections-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
</div>

<div id="h5e-am-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-new-method') ?>">
	<input id="h5e-am-name" type="text" size="25"/><br/>
	<br/>
	<input id="h5e-am-add" type="button" value="<?= wfMsg('h5e-add') ?>"/>
	<input id="h5e-am-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
</div>

<div id="h5e-editing-toolbar">
	<div class="h5e-tb-function-wrapper">
		<div class="h5e-tb-left-wrapper">
			<input id="h5e-toolbar-img" type="button" value="<?= wfMsg('h5e-add-image') ?>"/> |
			<input id="h5e-toolbar-a" type="button" value="<?= wfMsg('h5e-add-link') ?>"/> |
			<input id="h5e-toolbar-italics" type="button" value="<?= wfMsg('h5e-italics') ?>"/> |
			<input id="h5e-toolbar-outdent" type="button" value="<?= wfMsg('h5e-outdent') ?>"/> | 
			<input id="h5e-toolbar-indent" type="button" value="<?= wfMsg('h5e-indent') ?>"/> | 
			<input id="h5e-toolbar-ref" type="button" value="<?= wfMsg('h5e-add-reference') ?>"/> |
			<input id="h5e-toolbar-section" type="button" value="<?= wfMsg('h5e-add-section') ?>"/> |
			<input id="h5e-toolbar-related" type="button" value="<?= wfMsg('relatedwikihows') ?>"/> |
			<input id="h5e-discard-changes" class="h5e-toolbar-cancel" type="button" value="<?= wfMsg('h5e-discard-changes') ?>"/> |
			<input id="h5e-toolbar-publish" type="button" value="<?= wfMsg('h5e-publish') ?>"/>
		</div>
		<div class="h5e-tb-right-wrapper">
			<input class="h5e-toolbar-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
		</div>
	</div>
	<div class="h5e-tb-save-wrapper">
		<div class="h5e-tb-left-wrapper">
			<table>
				<tr><td>
					<?= wfMsg('h5e-describe-edits') ?>
				</td><td>
				</td></tr>
				<tr><td>
					<input id="h5e-edit-description" type="text" size="70" />
				</td><td>
					<button id="h5e-edit-description-save"><?= wfMsg('h5e-save') ?></button>
				</td></tr>
			</table>
		</div>
		<div class="h5e-tb-right-wrapper">
			<input class="h5e-toolbar-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
		</div>
	</div>
</div>

<div class="h5e-saving-notice">
	saving...<br/>
	<br/>
	<img src="/extensions/wikihow/rotate.gif"/>
</div>

<div id="h5e-mwimg-mouseover">
	<a href="#"><?= wfMsg('h5e-remove-image') ?></a>
</div>

<div id="edit-ref-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-edit-reference') ?>">
	<input id="ref-edit" type="text" size="50"/><br/>
	<br/>
	<input id="ref-edit-remove" type="button" value="<?= wfMsg('h5e-remove') ?>"/>
	<input id="ref-edit-change" type="button" value="<?= wfMsg('h5e-change') ?>"/>
	<input id="ref-edit-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
</div>

<div id="related-wh-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-edit-related') ?>">
	<p><?= wfMsg('h5e-add-related-text') ?></p><br/>
	<form id="h5e-ac" name="h5e-ac">
		<input id="h5e-related-new" name="h5e-related-new" autocomplete="off" maxLength="256" size="60%" value=""/> <button id="h5e-related-add"><?= wfMsg('h5e-add') ?></button><br/>
	</form>
	<br/>
	<div class="h5e-related-list">
		<ul class="h5e-related-sortable">
			<?php // list items go here ?>
		</ul>
	</div>
	<br/>
	<button id="h5e-related-done"><?= wfMsg('h5e-done') ?></button> <button id="h5e-related-cancel"><?= wfMsg('h5e-cancel') ?></button><br/>
</div>

<div class="related-wh-overlay">
</div>

<div class="related-wh-overlay-edit">
	<button id='related-wh-button'><?= wfMsg('h5e-change') ?></button>
</div>


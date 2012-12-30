<?php
	define('H5E_URL_REV', 1);
?>
<script>
	var wgArticleExists = <?= $articleExists ? 'true' : 'false' ?>;
</script>
<link rel="stylesheet" href="/extensions/min/?f=/extensions/wikihow/h5e/skin.css,/extensions/wikihow/eiu/easyimageupload.css&<?= H5E_URL_REV ?>" />
<script src="http://www.google.com/jsapi?key=<?= $GOOGLE_SEARCH_API_KEY ?>" type="text/javascript"></script>
<script src="/extensions/wikihow/h5e/html5editor.js"></script>
<script src="/extensions/min/?f=/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery.ui.sortable.min.js,/skins/common/ac.js"></script>
<!--<script type="text/javascript" src="/extensions/min/?f=/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.1.custom.min.js,/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/h5e/html5editor.js&<?= H5E_URL_REV ?>"></script>-->
<?php
	$langKeys = array('new-link','howto','h5e-ref','h5e-new-section','h5e-new-alternate-method','h5e-new-method','h5e-references-removed','h5e-references-removed','h5e-loading','h5e-more-results','Ingredients','Steps','Video','Tips','Warnings','relatedwikihows','sourcescitations','thingsyoullneed', 'h5e-edit-summary-examples', 'h5e-changes-to-be-discarded', 'import-content', 'import-content-url', 'h5e-add-reference', 'h5e-edit-reference', 'h5e-add', 'h5e-edit-link', 'h5e-edit-link-external', 'h5e-add-link', 'h5e-done', 'h5e-change', 'h5e-edit-ref', 'h5e-hidden-template', 'h5e-hidden-video', 'h5e-rel-wh-add', 'h5e-rel-wh-edit', 'h5e-enter-edit-summary', 'h5e-first-step', 'h5e-external-link-editing-disabled', 'h5e-external-links-warning', 'h5e-ok', 'h5e-remove-section', 'warning', 'congrats-article-published', 'h5e-switch-advanced', 'h5e-publish-timeout', 'h5e-error', 'h5e-editing-title', 'h5e-creating-title', 'h5e-create-new-article');
	echo WikiHow_i18n::genJSMsgs($langKeys);
?>
<div class="h5e-edit-link-options-over rounded_corners">
	<a href="#" id="h5e-editlink-cancel" title="<?= wfMsg('h5e-cancel') ?>" class="h5e-x"></a>
	<span class="h5e-edit-link-inner">
		<?= wfMSg('h5e-goto-link') ?>
		<a id="h5e-editlink-display" href="#"></a><br />
		<a id="h5e-editlink-change" href="#"><?= wfMsg('h5e-change') ?></a> - 
		<a id="h5e-editlink-remove" href="#"><?= wfMsg('h5e-remove') ?></a>
	</span>
</div>

<div id="h5e-link-dialog" class="h5e-dialog" title="">
	<p><?= wfMsg('h5e-text') ?><br /><input id="h5e-link-text" autocomplete="off" /></p>
	<p style="float:right;"><a id="h5e-link-preview" target="_blank" href="#"><?= wfMsg('view-article') ?></a></p>
	<form id="h5e-ac-link" name="h5e-ac-link">
	<p><?= wfMsg('h5e-article') ?></p>
	<p class="h5e-howto-input">
		<span class="h5e-link-how-to-link"><?= wfMsg('howto', '') ?></span>
		<input id="h5e-link-article" autocomplete="off" />
		<div class="h5e-external-link-editing-disabled"><span><?= wfMsg('h5e-external-link-editing-disabled') ?></span></div>
	</p>
	</form>
	<div class="h5e-bottom-buttons">
		<div class="h5e-link-external-help">
			<a href="#"><img src="/skins/WikiHow/images/icon_help.jpg"/></a>
			<a class="h5e-external-link-why" href="#"><?= wfMsg('h5e-how-to-add-external-link') ?></a>
		</div>
		<a id="h5e-link-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-link-change" value="<?= wfMsg('h5e-done') ?>" contenteditable="false" />
	</div>
</div>

<div id="h5e-external-url-msg-dialog" title="<?= wfMsg('h5e-external-links') ?>">
	<div>
		<?= wfMsg('h5e-external-link-disallowed') ?>
	</div>
	<div class="h5e-bottom-buttons">
		<input class="h5e-button button64 h5e-input-button" id="h5e-link-change" value="<?= wfMsg('h5e-ok') ?>" contenteditable="false" />
	</div>
</div>

<div id="h5e-sections-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-sections') ?>">
	<div id="h5e-sections">
	</div>
	<br/>
	<div class="h5e-bottom-buttons">
		<a id="h5e-sections-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-sections-change" value="<?= wfMsg('h5e-change') ?>" contenteditable="false" />
	</div>
</div>

<div id="h5e-am-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-new-method') ?>">
	<p><input id="h5e-am-name" class="h5e-input" type="text" autocomplete="off" size="25" /><br /></p>
	<br/>
	<div class="h5e-bottom-buttons">
		<a id="h5e-am-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-am-add" value="<?= wfMsg('h5e-add') ?>" contenteditable="false" />
	</div>
</div>

<div id="h5e-editing-toolbar">
	<div class="h5e-tb-function-wrapper">
		<div class="h5e-tb-left-edge"></div>
		<div class="h5e-tb-left-wrapper">
			<a id="h5e-toolbar-img" class="h5e-button h5e-button-img" title="<?= wfMsg('h5e-add-image') ?>" href=""><?= wfMsg('h5e-image') ?></a>
			<a id="h5e-toolbar-a" class="h5e-button h5e-button-a" title="<?= wfMsg('h5e-add-link') ?>" href=""><?= wfMsg('h5e-link') ?></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />
			
			<a id="h5e-toolbar-italics" class="h5e-button h5e-button-italics" title="<?= wfMsg('h5e-italics') ?>" href=""></a>
			<a id="h5e-toolbar-indent" class="h5e-button h5e-button-indent" title="<?= wfMsg('h5e-indent') ?>" href=""></a>
			<a id="h5e-toolbar-outdent" class="h5e-button h5e-button-outdent h5e-disabled" title="<?= wfMsg('h5e-outdent') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />
			
			<a id="h5e-toolbar-ref" class="h5e-button h5e-button-ref" title="<?= wfMsg('h5e-ref') ?>" href=""></a>
			<a id="h5e-toolbar-section" class="h5e-button h5e-button-section" title="<?= wfMsg('h5e-sections') ?>" href=""></a>
			<a id="h5e-toolbar-related" class="h5e-button h5e-button-related" title="<?= wfMsg('h5e-edit-related') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />

			<a id="h5e-discard-changes" class="h5e-button h5e-discard-changes h5e-toolbar-cancel" title="<?= wfMsg('h5e-discard-changes') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />

			<input type="text" id="h5e-edit-summary-pre" class="h5e-input h5e-example-text" value="" />
			<a id="h5e-toolbar-publish" class="h5e-button button106" href=""><?= wfMsg('h5e-publish') ?></a>
		</div>
		<div class="h5e-tb-right-edge"></div>
		<div class="h5e-tb-right-wrapper">
			<a href=""><?= wfMsg('h5e-cancel') ?></a>
			<a href="" title="<?= wfMsg('h5e-cancel') ?>" class="h5e-x h5e-toolbar-cancel"></a>
		</div>
	</div>
	<div class="h5e-tb-save-wrapper">
		<div class="h5e-tb-left-edge"></div>
		<div class="h5e-tb-left-wrapper">
			<div class="h5e-describe-edits"><?= wfMsg('h5e-describe-edits') ?></div>
			<div>
				<input type="text" id="h5e-edit-summary-post" class="h5e-input h5e-example-text" size="70" />
				<a id="h5e-edit-summary-save" style="float: left;" class="h5e-button button106" href=""><?= wfMsg('h5e-save') ?></a>
			</div>
		</div>
		<div class="h5e-tb-right-edge"></div>
		<div class="h5e-tb-right-wrapper">
			<a href=""><?= wfMsg('h5e-close') ?></a>
			<a href="" title="<?= wfMsg('h5e-close') ?>" class="h5e-x h5e-toolbar-cancel"></a>
		</div>
	</div>
</div>

<div class="h5e-saving-notice">
	<img src="/extensions/wikihow/rotate_white.gif"/><br/>
	<br/>
	<?= wfMsg('h5e-saving') ?>
</div>

<div id="h5e-mwimg-mouseover">
	<div id="h5e-mwimg-mouseover-bg"></div>
	<ul id="h5e-mwimg-mouseover-confirm">
		<li class="h5e-mwimg-mouseover-confirm_top"></li>
		<li class="h5e-mwimg-mouseover-confirm-main"><?= wfMsg('h5e-confirm-delete-image') ?></li>
		<li class="h5e-mwimg-mouseover-confirm-main"><span class="h5e-mwimg-confirm-yes"><?= wfMsg('h5e-yes') ?></span> | <span class="h5e-mwimg-confirm-no"><?= wfMsg('h5e-no') ?></span></li>
		<li class="h5e-mwimg-mouseover-confirm_bottom"></li>
	</ul>
	<a class="edit-remove-image" title="<?= wfMsg('h5e-remove-image') ?>" href=""><?= wfMsg('h5e-remove') ?></a>
</div>

<div id="edit-ref-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-edit-reference') ?>">
	<p><?= wfMsg('h5e-add-reference-text') ?><br />
	<input class="h5e-input" id="ref-edit" type="text" size="50" autocomplete="off" /></p>
	<div class="h5e-bottom-buttons">
		<a id="ref-edit-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="ref-edit-change" value="<?= wfMsg('h5e-change') ?>" contenteditable="false" />
	</div>
</div>

<div id="related-wh-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-edit-related') ?>">
	<p class="h5e-related-p"><?= wfMsg('h5e-add-related-text') ?>:</p><br />
	<form id="h5e-ac" name="h5e-ac">
		<p class="h5e-link-how-to-related"><?= wfMsg('howto', '') ?></p>
		<input class="h5e-input" id="h5e-related-new" name="h5e-related-new" autocomplete="off" maxLength="256" value=""/> 
		<input class="h5e-button button-gray-73 h5e-input-button" id="h5e-related-add" value="<?= wfMsg('h5e-add') ?>" contenteditable="false" /><br/>
	</form>
	<br/>
	<div class="h5e-related-list">
		<ul class="h5e-related-sortable">
			<?php // list items go here ?>
		</ul>
	</div>
	<br/>
	<div class="h5e-bottom-buttons">
		<a id="h5e-related-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-related-done" value="<?= wfMsg('h5e-done') ?>" contenteditable="false" />
	</div>
	<br/>
</div>

<div class="related-wh-overlay">
</div>

<div class="related-wh-overlay-edit">
	<button id='related-wh-button'><?= wfMsg('h5e-change') ?></button>
</div>

<div id="h5e-sections-confirm" class="h5e-dialog" title="<?= wfMsg('h5e-remove-section-confirm') ?>">
	<p><?= wfMsg('h5e-remove-confirm-desc') ?></p>
	<p>&nbsp;</p>
	<p class="h5e-remove-confirm-help"><span class="h5e-button h5e-button-section"></span><?= wfMsg('h5e-remove-confirm-help') ?></p>
	<br/>
	<div class="h5e-bottom-buttons">
		<input class="h5e-button button64 h5e-input-button" id="h5e-sections-confirm-remove" value="<?= wfMsg('h5e-remove') ?>" contenteditable="false" />
		<a id="h5e-sections-confirm-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
	</div>
</div>


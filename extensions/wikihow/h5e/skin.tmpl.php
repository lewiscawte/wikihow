<?php
	define('H5E_URL_REV', 1);
?>
<link rel="stylesheet" href="/extensions/min/?f=/extensions/wikihow/h5e/skin.css,/extensions/wikihow/eiu/easyimageupload.css&<?= H5E_URL_REV ?>" />
<script src="http://www.google.com/jsapi?key=<?= $GOOGLE_SEARCH_API_KEY ?>" type="text/javascript"></script>
<script src="/extensions/wikihow/h5e/html5editor.js"></script>
<script src="/extensions/min/?f=/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery.ui.sortable.min.js,/skins/common/ac.js"></script>
<!--<script type="text/javascript" src="/extensions/min/?f=/extensions/wikihow/common/jquery-ui-slider-dialog-custom/jquery-ui-1.8.1.custom.min.js,/extensions/wikihow/h5e/jquery.textNodes.js,/extensions/wikihow/h5e/html5editor.js&<?= H5E_URL_REV ?>"></script>-->
<?php
	$langKeys = array('new-link','howto','h5e-ref','h5e-new-section','h5e-new-alternate-method','h5e-new-method','h5e-references-removed','h5e-references-removed','h5e-loading','h5e-more-results','Ingredients','Steps','Video','Tips','Warnings','relatedwikihows','sourcescitations','thingsyoullneed', 'h5e-edit-summary-examples', 'h5e-changes-to-be-discarded', 'import-content', 'import-content-url', 'h5e-add-reference', 'h5e-edit-reference', 'h5e-add', 'edit-link', 'add-link', 'h5e-done', 'h5e-change', 'h5e-edit-ref', 'h5e-hidden-template', 'h5e-hidden-video', 'h5e-rel-wh-edit', 'h5e-enter-edit-summary', 'h5e-first-step');
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
	<p><?= wfMsg('h5e-text') ?><br /><input id="h5e-link-text"/></p>
	<p style="float:right;"><a id="h5e-link-preview" target="_blank" href="#"><?= wfMsg('view-article') ?></a></p>
	<form id="h5e-ac-link" name="h5e-ac-link">
	<p>
		<?= wfMsg('h5e-article') ?><br />
		<input id="h5e-link-article" autocomplete="off" />
	</p>
	</form>
	<div class="h5e-bottom-buttons">
		<a id="h5e-link-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-link-change" value="<?= wfMsg('h5e-done') ?>" contenteditable="false" />
	</div>
</div>

<div id="h5e-sections-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-sections') ?>">
	<div id="h5e-sections">
	</div>
	<br/>
	<div class="h5e-bottom-buttons">
		<a id="h5e-sections-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="h5e-sections-change" value="<?= wfMsg('h5e-change') ?>" />
	</div>
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
			<a id="h5e-toolbar-img" class="h5e-button h5e-button-img" title="<?= wfMsg('h5e-add-image') ?>" href=""><?= wfMsg('h5e-image') ?></a>
			<a id="h5e-toolbar-a" class="h5e-button h5e-button-a" title="<?= wfMsg('h5e-add-link') ?>" href=""><?= wfMsg('h5e-link') ?></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />
			
			<a id="h5e-toolbar-italics" class="h5e-button h5e-button-italics" title="<?= wfMsg('h5e-italics') ?>" href=""></a>
			<a id="h5e-toolbar-indent" class="h5e-button h5e-button-indent" title="<?= wfMsg('h5e-indent') ?>" href=""></a>
			<a id="h5e-toolbar-outdent" class="h5e-button h5e-button-outdent" title="<?= wfMsg('h5e-outdent') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />
			
			<a id="h5e-toolbar-ref" class="h5e-button h5e-button-ref" title="<?= wfMsg('h5e-ref') ?>" href=""></a>
			<a id="h5e-toolbar-section" class="h5e-button h5e-button-section" title="<?= wfMsg('h5e-sections') ?>" href=""></a>
			<a id="h5e-toolbar-related" class="h5e-button h5e-button-related" title="<?= wfMsg('h5e-edit-related') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />

			<a id="h5e-discard-changes" class="h5e-button h5e-discard-changes h5e-toolbar-cancel" title="<?= wfMsg('h5e-discard-changes') ?>" href=""></a>

			<img src="/skins/WikiHow/images/separator.gif" class="h5e-separator" />

			<input type="text" id="h5e-edit-summary-pre" class="h5e-input h5e-example-text" value="" />
			<a id="h5e-toolbar-publish" style="float: left; background-position: -341px 0pt;" class="h5e-button button106" href=""><?= wfMsg('h5e-publish') ?></a>
		</div>
		<div class="h5e-tb-right-wrapper">
			<a href="" title="<?= wfMsg('h5e-cancel') ?>" class="h5e-x h5e-toolbar-cancel"></a>
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
					<input type="text" id="h5e-edit-summary-post" class="h5e-input h5e-example-text" size="70" />
				</td><td>
					<button id="h5e-edit-summary-save"><?= wfMsg('h5e-save') ?></button>
				</td></tr>
			</table>
		</div>
		<div class="h5e-tb-right-wrapper">
			<input class="h5e-toolbar-cancel" type="button" value="<?= wfMsg('h5e-cancel') ?>"/>
		</div>
	</div>
</div>

<div class="h5e-saving-notice">
	<?= wfMsg('h5e-saving') ?><br/>
	<br/>
	<img src="/extensions/wikihow/rotate.gif"/>
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
	<input class="h5e-input" id="ref-edit" type="text" size="50" />
	<div class="h5e-bottom-buttons">
		<a id="ref-edit-cancel" href="#"><?= wfMsg('h5e-cancel') ?></a>
		<input class="h5e-button button64 h5e-input-button" id="ref-edit-change" value="<?= wfMsg('h5e-change') ?>" />
	</div>
</div>

<div id="related-wh-dialog" class="h5e-dialog" title="<?= wfMsg('h5e-edit-related') ?>">
	<p class="h5e-related-p"><?= wfMsg('h5e-add-related-text') ?>:</p>
	<form id="h5e-ac" name="h5e-ac">
		<input class="h5e-input" id="h5e-related-new" name="h5e-related-new" autocomplete="off" maxLength="256" value=""/> 
		<input class="h5e-button button-gray-73 h5e-input-button" id="h5e-related-add" value="<?= wfMsg('h5e-add') ?>" /><br/>
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
		<input class="h5e-button button64 h5e-input-button" id="h5e-related-done" value="<?= wfMsg('h5e-done') ?>" />
	</div>
	<br/>
</div>

<div class="related-wh-overlay">
</div>

<div class="related-wh-overlay-edit">
	<button id='related-wh-button'><?= wfMsg('h5e-change') ?></button>
</div>


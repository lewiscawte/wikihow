<? // Easy image upload process: 2nd page form allowing user to customize just uploaded image ?>
<div id="eiu-image-details-page">
<div id="ImageUploadSource" style="display: none;"><?= $src ?></div>
<form id="eiu-insert-image" name="eiu-insert-image" method="post" onsubmit="easyImageUpload.storeImageDetails('eiu-insert-image'); return false;">
<input id="ImageUploadMWname" type="hidden" name="mwname" value="<?= htmlspecialchars($mwname) ?>" />
<input id="ImageUploadType" type="hidden" name='type' value="<?= isset($file) ? htmlspecialchars($file->media_type) : '' ?>" />
<input id="ImageAttribution" type="hidden" name="ImageAttribution" value="<?= htmlspecialchars($image_comment) ?>" />

<? if (isset($name)): ?>
<div id="ImageUploadSection" style="display: none;">
	<?= wfMsg('eiu-details-inf') ?>
	<table class='eiu-upload-table'>
		<tr> 
			<th><?= wfMsg('eiu-name') ?></th>
			<td>
				<input id="ImageUploadName" type="text" size="30" value="<?= htmlspecialchars($name) ?>" name='name' />
				<? 
					$title = Title::makeTitle(NS_IMAGE, $name);
				?>
				<? if ($title && $title->getArticleID() > 0): ?>
					<span style='font-size: .8em; margin-left: 15px;'><img src='/extensions/wikihow/eiu/dialog-warning.png'> 
						<?= wfMsg('eiu-warning-image-exists', '<a href="'.$title->getFullURL().'" target="new">', '</a>') ?>
					</span>
				<? endif; ?>
			</td>
		</tr>
		<tr>
			<th><?= wfMsg('eiu-license') ?></th>
			<td>
			<? if ($src == 'upload'): ?>
				<?
					// used to indent inside the select box
					$indent = "\xc2\xa0\xc2\xa0";
				?>
				<select name='wpLicense' id='wpLicense' tabindex='4' onchange='easyImageUpload.cssShowHideAttribution();'>
<option value="" disabled="disabled" style="color: GrayText"><?= wfMsg('eiu-your-own-work') ?></option>
	<option value="cc-by-sa-nc-2.5-self" title="{{cc-by-sa-nc-2.5-self}}" selected="selected"><?= $indent . wfMsg('eiu-made-myself-wikihow') ?></option>
	<option value="Self" title="{{Self}}"><?= $indent . wfMsg('eiu-made-myself-cc') ?></option>
	<option value="PD self" title="{{PD self}}"><?= $indent . wfMsg('eiu-made-myself-public') ?></option>
<option value="" disabled="disabled" style="color: GrayText"><?= wfMsg('eiu-not-self-made') ?></option>
	<option value="GFDL" title="{{GFDL}}"><?= $indent . wfMsg('eiu-public-gfdl') ?></option>
	<option value="cc-by-2.5" title="{{cc-by-2.5}}"><?= $indent . wfMsg('eiu-public-cc-by') ?></option>
	<option value="cc-by-sa-2.5" title="{{cc-by-sa-2.5}}"><?= $indent . wfMsg('eiu-public-cc-sa') ?></option>
	<option value="PD" title="{{PD}}"><?= $indent . wfMsg('eiu-public-domain') ?></option>
<option value="" disabled="disabled" style="color: GrayText"><?= wfMsg('eiu-cr') ?></option>
	<option value="copyrighted-rerelease" title="{{copyrighted-rerelease}}"><?= $indent . wfMsg('eiu-cr-cc') ?></option>
	<option value="Fair Use" title="{{Fair Use}}"><?= $indent . wfMsg('eiu-cr-fu') ?></option>
	<option value="Screenshot" title="{{Screenshot}}"><?= $indent . wfMsg('eiu-cr-ss') ?></option>
<option value="" disabled="disabled" style="color: GrayText"><?= wfMsg('eiu-unknown-deleted') ?></option>
	<option value="No License" title="{{No License}}"><?= $indent . wfMsg('eiu-unknown-found') ?></option>
				</select>

				<?
					// disabled this code for now -- elizabeth wants
					// enough changes to the licenses that I need to
					// just copy the output rather than hack it
					/*
					$licenses = new Licenses();
					$licenseshtml = $licenses->getHtml();
					// pass as a silent global, since php 5.2 doesn't have
					// closures yet
					function replaceFunc($match) {
						$insertOptionAfter = 13;
						static $n = 1;
						if ($n++ == $insertOptionAfter) {
							$defaultLicense = '<option value="cc-by-sa-nc-2.5-self" title="{{cc-by-sa-nc-2.5-self}}" selected="selected">' . "\xc2\xa0" . wfMsg('eiu-made-this-myself') . '</option>';
							return $match[1] . $defaultLicense . $match[3];
						} else {
							return $match[0];
						}
					}
					$licenseshtml = preg_replace_callback('@(</option>)(\s|\n)*(<option)@', replaceFunc, $licenseshtml);
					*/
				?>
			 <? endif; ?>
			</td>
		</tr>
		<? if ($src == 'upload'): ?>
			<tr>
				<th><span id="eiu-attribution-header" style="visibility:hidden;"><?= wfMsg('eiu-provide-attribution') ?></span></th>
				<td>
					<span id="eiu-attribution-input" style="visibility:hidden;"><input type="text" name="attribution" value="" size="30" id="wpAttribution" /></span>
				</td>
			</tr>
		<? endif; ?>
	</table>
</div>
<? endif; ?>
<div id="options_div" >
<?= wfMsg('eiu-details-inf2') ?>
<table class="ImageUploadOptionsTable">
<? if($is_image): ?>
	<tr>
		<th><?= wfMsg('eiu-size') ?></th>
		<td>
			<input type="radio" name="sizing" value="automatic" id="ImageUploadAutomaticOption" checked="checked" onclick="easyImageUpload.cssResizeImagePreview();" /> 
			<label for="ImageUploadAutomaticOption" onclick="easyImageUpload.cssResizeImagePreview();"><?= wfMsg('eiu-automatic') ?></label> &nbsp;
			<input type="radio" name="sizing" value="manual" id="ImageUploadManualOption" onclick="easyImageUpload.cssResizeImagePreview();" /> 
			<label for="ImageUploadManualOption" onclick="easyImageUpload.cssResizeImagePreview();">
				<?= wfMsg('eiu-manual') ?></label>
		</td>
	</tr>
	<tr id="ImageWidthRow" style="display: none;">
		<th><?= wfMsg('eiu-width') ?></th>
		<td>
			<? // SLIDER CODE ?>
			<table style="width: 230px;"> 
   				<tr>
					<td colspan="2" style="border: 0px none black;">
						<div id="eiu-slider-track" style="position: relative; width: 209px;"></div>
<!--
						<div id="eiu-slider-track" style="background:url(/extensions/wikihow/eiu/slider-bg-fader.gif) 5px 0 no-repeat; outline:none; position: relative; width: 209px; height: 28px; cursor: pointer; z-index: 0;">
							<div id="eiu-slider-handle" style="position: absolute; top: 3px; cursor: move; z-index: 2; width: 17px; height: 21px;"><img src="/extensions/wikihow/eiu/slider-thumb-n.gif" /></div>
						</div>
-->
					</td>
				</tr>
				</table>
				<?= wfMsg('eiu-width-in-percentage') ?>
				<input type="text" id="slider-converted-value" name="width" value="50" size="4" maxlength="4" autocomplete="off" />% (<span id="chosen-width-display"><?= $width ?></span>px/<span id="chosen-height-display"><?= $height ?></span>px)
				<input type="hidden" id="chosen-width" name="chosen-width" value="<?= $width ?>" />
				<input type="hidden" id="chosen-height" name="chosen-height" value="<?= $height ?>" />
			</p>
		</td>
	</tr>
	<tr id="ImageLayoutRow">
		<th><?= wfMsg('eiu-layout') ?></th>
		<td>
			<table id="ImageLayoutRowInner"><tr>
			<td><input type="radio" id="ImageUploadLayoutRight" name="layout" value="right" checked="checked" onclick="easyImageUpload.cssResizeImagePreview();" /></td>
			<td style="padding-left: 5px;"><label for="ImageUploadLayoutRight"><img src="/extensions/wikihow/eiu/image_upload_right.png" onclick="easyImageUpload.cssResizeImagePreview();" /></label></td>
			<td style="padding-left: 10px;"><input type="radio" id="ImageUploadLayoutCenter" name="layout" value="center" onclick="easyImageUpload.cssResizeImagePreview();" /></td>
			<td style="padding-left: 5px;"><label for="ImageUploadLayoutCenter"><img src="/extensions/wikihow/eiu/image_upload_center.png" onclick="easyImageUpload.cssResizeImagePreview();" /></label></td>
			</tr></table>
		</td>
	</tr>
<? endif; ?>
	<tr>
		<th><?= wfMsg('eiu-caption') ?></th>
		<td><input type="text" id="ImageUploadCaption" name="caption" /><?= wfMsg('eiu-optional') ?></td>
	</tr>
	<tr class="ImageUploadNoBorder">
		<td>&nbsp;</td>
		<td>
			<input type='hidden' name='uploadform2' value='1' />
			<input type='hidden' name='image-details' id='eiu-image-details' value='' />
			<input type="submit" value="<?= wfMsg('eiu-insert2') ?>" onclick="easyImageUpload.imageDetailsSave();" class="button button100 input_button" style="margin:0;" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" id="eiu_insert" />
            <img src="/extensions/wikihow/rotate.gif" alt="" class="eiu-wheel" id="eiu-wheel-details" />
		</td>
	</tr>
</table>
</div>
<?
	$thumbMaxWidth = isset($name) ? '255' : '370';
?>
<div id="eiu-image-orig-width" style="display: none;"><?= $width ?></div>
<div id="eiu-image-orig-height" style="display: none;"><?= $height ?></div>
<div id="ImageUploadThumb" style="height: <?= $thumbMaxWidth ?>px;">
	<?
		$width = min($upload_file->getWidth(), 340);
		$thumbnail = $upload_file->getThumbnail($width, 400);
		$html = $thumbnail->toHtml();
		$html = preg_replace('@<img@', '<img id="eiu-thumb-img"', $html);
		print $html;
	?>
</div>
</form>
</div>

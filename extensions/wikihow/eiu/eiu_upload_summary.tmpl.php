<? // Final screen / text of easy image upload process ?>
<?
	$thumbHtml = $file->getThumbnail($width, $height)->toHtml();
	$details['html'] = $thumbHtml;
	$details['filename'] = $imageFilename;
	$details['tag'] = $tag;
?>
<h3 style="text-align: center;"><?= wfMsg('eiu-success') ?></h3>
<div style="text-align: center;">
	<p id="imagethumb"><?= $thumbHtml ?></p>
	<p><?= wfMsg('eiu-wiki-text-placed') ?></p>
	<div id="ImageUploadDisplayedTag"><?= $tag ?></div>
	<input onclick="easyImageUpload.closeUploadDialog();" type="button" value="<?= wfMsg('eiu-return') ?>" class="button button100 input_button" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" style="display:inline;" />
	<input type="hidden" id="ImageUploadTag" value="<?= htmlspecialchars( $tag ) ?>" />
	<input type="hidden" id="ImageUploadFilename" value="<?= htmlspecialchars($imageFilename) ?>" />
	<input type="hidden" id="ImageUploadImageDetails" value="<?= htmlspecialchars( json_encode($details) ) ?>" />
</div>

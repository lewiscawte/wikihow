<div id="docviewer_side">
	<p><a href="<?=wfGetPad('/extensions/wikihow/docviewer/pdf/'.$pdf_name.'.pdf')?>" class="button button136"><?=$download_text?></a></p>
	<p>or</p>
	<p><a href="<?=$docracy_link?>"><img src="<?=wfGetPad('/extensions/wikihow/docviewer/images/customize_button.png')?>" /></a></p>
	<p class="back_link"><?=$click_back?></p>
</div>

<object id="pdfobject" type="application/pdf" data="/extensions/wikihow/docviewer/pdf/<?=$pdf_name?>.pdf">
	<!--fallback for IE and other non-PDF-embeddable browsers-->
	<img src="<?=wfGetPad('/extensions/wikihow/docviewer/images/'.$pdf_name.'.png')?>" />
</object>

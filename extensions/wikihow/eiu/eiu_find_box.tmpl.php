<form id="eiu-image-search" action="#" onsubmit="easyImageUpload.loadImages('current', jQuery('#search_image_query').val(), 1); return false;">
<table width='100%' class='findtable'>
<tr>
	<td class='findtitle'><?= wfMsg('eiu-find') ?></td>
	<td class='findinput' ><input type='text' id='search_image_query' value='<?= htmlspecialchars($title, ENT_QUOTES) ?>' size='30'> <input type='submit' value='<?= wfMsg('eiu-find') ?>' class='button white_button input_button' style='display:inline' onmouseout='button_unswap(this);' onmouseover='button_swap(this);'>
		<a id="eiu-flickr-link" style="font-weight: bold;" href="#" onclick="easyImageUpload.cssSetFindLinksWeight(false); easyImageUpload.loadImages('flickr', jQuery('#search_image_query').val(), 1); return false;"><?= wfMsg('eiu-flickr') ?></a> | 
		<a id="eiu-this-wiki-link" style="font-weight: normal;" href="#" onclick="easyImageUpload.cssSetFindLinksWeight(true); easyImageUpload.loadImages('wiki', jQuery('#search_image_query').val(), 1); return false;"><?= wfMsg('eiu-wikimedia') ?></a>
	</td>
</tr>
</table>
</form>
<div id='eiu_recently_uploaded' style='text-align: center;'>

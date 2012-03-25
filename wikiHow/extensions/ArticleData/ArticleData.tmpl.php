<style>
.ct_cat {
	width: 400px;
	height: 17px;
}

.ct_urls {
	width: 600px;
	height: 300px;
}

#ct_a {
	display: none;
}

.ct_row {
	margin: 5px 0 5px 0;
}

</style>
<script type='text/javascript'>
$('#ct_button').live('click', function(e) {
	var url = '/' + wgPageName;
	$.download(url, {a: $('#ct_a').text(), alts: $('#ct_slow').is(':checked'), intonly: $('#ct_introonly').is(':checked'), data: $('#ct_data').val()});
});
</script>
<div id='ct_a'><?=$ct_a?></div>

<? if ($ct_a == 'cats') { ?>
<label for="ct_cat"><b>Enter Category URL</b> </label><input type="text" class="ct_cat" name="ct_cat" id="ct_data"/>
<? } else { ?>
<label for="ct_urls"><b>Enter Article  URLs</b> </label>
<div>
<textarea class="ct_urls" name="ct_urls" id="ct_data"/></textarea>
</div>
<? } ?>
<div class='ct_row'>
<label for="ct_slow"><b>Include Slower Data (alt methods, images and article size)</b> </label><input type="checkbox" name="ct_slow" id="ct_slow" />
</div>
<div class='ct_row'>
<label for="ct_introonly"><b>Intro Image only</b> </label><input type="checkbox" name="ct_introonly" id="ct_introonly" />
</div>
<div class='ct_row'>
<input type='button' id='ct_button' value='Get File'></input>
</div>

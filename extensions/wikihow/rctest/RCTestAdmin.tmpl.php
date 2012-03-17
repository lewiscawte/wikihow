<?=$css?>
<script type='text/javascript'>
$('a.rct_detail').live('click', function(e) {
	var id = $(this).attr('id');
	id = id.split('_');
	var title = "ALL Tests Taken by " + $(this).html();
	$('#dialog-box').html('');
	$('#dialog-box').load('/Special:RCTestAdmin?a=detail&uid=' + id[1], function() {
		jQuery('#dialog-box').dialog({
			width: 670,
			modal: true,
			title: title
		});
	});
});
</script>
<form class="rct_ts" method="GET"> 
<label for="days">Test scores from the past </label><input type="text" name="days" value="<?=$days?>"/> days
<input type="submit" maxlength="2" name="submit" value="Go"/>
</form>
<table class="rct_scores">
	<tr>
		<th>User</th>
		<th>% Easy </th>
		<th>% Other </th>
		<th>% Total </th>
		<th>% Incorrect</th>
		<th>Total Tests</th>
	</tr>
	<? 
	$i = 0;
	foreach ($results as $result): 
		$class = $i % 2 == 0 ? 'even' : 'odd';
		$i++
	?>
		<tr class="<?=$class?>">
			<td><a href='#' class='rct_detail' id='rct_<?=$result['rs_user_id']?>'><?=$result['rs_user_name']?></a></td>
			<td><?=$result['correct_easy']?></td>
			<td><?=$result['correct_other']?></td>
			<td><?=$result['correct']?></td>
			<td><?=$result['incorrect']?></td>
			<td><?=$result['total']?></td>
		</tr>
	<? endforeach; ?>
</table>

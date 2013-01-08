<style>
table.rct_scores {
	width: 500px;	
	font-variant: normal;
	font-style: normal;
	border-spacing: 0px;
}

table.rct_scores th {
	text-align: left;
	border-bottom: 1px black solid;
}

table.rct_scores tr {
	margin-top: 5px;
	margin-bottom: 5px;
}

table.rct_scores tr.odd{
	background: #EEEEEE;
}

table.rct_scores td {
	padding: 5px;
	line-height: 15px;
}

form.rct_ts {
	margin-bottom: 25px;
}

form.rct_ts input[type=text] {
	padding: 2px;
	width: 15px;
}
</style>
<form class="rct_ts" method="GET"> 
<label for="days">Test scores from the past </label><input type="text" name="days" value="<?=$days?>"/> days
<input type="submit" maxlength="2" name="submit" value="Go"/>
</form>
<table class="rct_scores">
	<tr>
		<th>User</th>
		<th>% Correct</th>
		<th>% Incorrect</th>
		<th>Total Tests</th>
	</tr>
	<? 
	foreach ($results as $i => $result): 
		$class = $i % 2 == 0 ? 'even' : 'odd';
	?>
		<tr class="<?=$class?>">
			<td><?=$result['rs_user_name']?></td>
			<td><?=$result['correct']?></td>
			<td><?=$result['incorrect']?></td>
			<td><?=$result['total']?></td>
		</tr>
	<? endforeach; ?>
</table>

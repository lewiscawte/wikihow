<table cellpadding="0" cellspacing="0" id="nfd_article_info">
	<tr>
		<td class="first">Age:</td><td><?= $age ?></td>
	</tr>
	<tr>
		<td class="first">Popularity:</td><td><?= $views ?> views</td>
	</tr>
	<tr>
		<td class="first">Edits:</td><td><?= $edits ?> Edits</td>
	</tr>
	<tr>
		<td class="first">Author:</td><td><a href='<?= $authorUrl ?>'><?= $authorName ?></a>, <?= $userEdits ?> edits</td>
	</tr>
	<tr>
		<td class="first">NFD Reason:</td><td><?= $nfd ?></td>
	</tr>
	<tr>
		<td class="first">NFD Votes:</td><td><?= $nfdVotes ?></td>
	</tr>
</table>

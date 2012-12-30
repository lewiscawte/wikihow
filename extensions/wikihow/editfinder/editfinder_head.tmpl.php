<div id="editfinder_head">
	<h1>Article: <a href="<?= $titlelink ?>" id="article_title"><?=$title?></a></h1>
	<p class="editfinder_issue">Issue: <?=$edittype?></p>
	<div id="editfinder_nav">
		<a href="#" class="button button52" id="editfinder_yes" style="float:left;">Edit</a>
		<p style="padding-top:4px;"><a href="#" onclick="editFinder.getArticle();">Skip</a></p>
	</div>
</div>
<div id='editfinder_spinner'><img src='/extensions/wikihow/rotate.gif' alt='' /></div>
<div id="cat_head_outer">
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
		<div id="cat_aid"><?=$pageId?></div>
		<h1 id="cat_title">Article: <a href="<?=$titleUrl?>" target="_blank"><?=$title?></a></h1>
		<? if (!empty($intro)) { ?>
		<a href="#" id="cat_introlink"><div id="cat_more" class="off"></div>View article introduction</a> | <a href="<?=$titleUrl?>" target="_blank">Open article in new window</a>
		<div id="cat_article_intro">
			<p><?=$intro?></p>
		</div> 
		<? } ?>
		<div id="cat_list_header">
			<b>Categories:</b>
			<span id="cat_list">
				<? 
				$nodisplay = "";
				if (!empty($cats)) { 
					echo $cats;
					$nodisplay = "style = 'display: none'";
				} 
				?>
				<span id='cat_none' <?=$nodisplay?>>Search below to add categories.</span>
			</span>
		</div>
	</div>
</div>
<div id="cat_notify">A maximum of two categories can be assigned.</div>

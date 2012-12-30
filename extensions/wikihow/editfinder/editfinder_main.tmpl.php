<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/editfinder/editfinder.css?2'; /*]]>*/</style>
<style type='text/css' media='all'>/*<![CDATA[*/ @import '/extensions/wikihow/suggestedtopics.css'; /*]]>*/</style>
<script type='text/javascript' src='/extensions/wikihow/editfinder/editfinder.js?4'></script>
<script type='text/javascript' src='/extensions/min/f/skins/common/preview.js?rev=100'></script>

<div id="editfinder_upper">
	<h1><?=$pagetitle?></h1>
</div>
<div id="editfinder_head">
	<div id="editfinder_options">
		<p id="editfinder_skip"><a href="#" onclick="editFinder.getArticle();"><?=$nope?></a></p>
		<a href="#" class="button editfinder_button_yes" id="editfinder_yes"><?=$yep?></a>
	</div>
	<h1><?=$question?></h1>
</div>
<div id="editfinder_title">
	<h1>Title: <span id="editfinder_article_inner"></span></h1>
</div>
<div id='editfinder_spinner'><img src='/extensions/wikihow/rotate.gif' alt='' /></div>
<div id='editfinder_preview_updated'></div>
<div id='editfinder_preview'></div>
<div id='article_contents'></div>
<div id="editfinder_category">
	Not finding an article you like?  <a href="" id="editfinder_choose_cats">Choose a category</a>
</div>

<?=$css?>
<?=$js?>
<div id="cat_upper">
	<h1>Categorizer App</h1>
	<p id="cat_help">Need Help? <a href="<?=$cat_help_url?>" target="_blank">Learn How to Categorize</a>.</p>
</div>
<?=$cat_head?>
<div id="cat_tree"><?=$tree?></div>
<div id="cat_ui">
	<div id="cat_search_outer">
		<label for="cat_search"><b class="whb"><?=$cat_search_label?></b></label>
		<div id="cat_search_box">
			<input id="cat_search"></input>
			<input type="button"  id="cat_search_button" class="cat_search_button" value="Search"></input>
		</div>
	</div>
	<div id='cat_breadcrumbs_outer'>
		<ul id='cat_breadcrumbs' class='ui-corner-all'></ul>
		<a class="cat_breadcrumb_add" href="#">Add</a>
		<div class ="cat_divider"></div>
		<div class="cat_subcats_outer">
			<div><b class="whb"><?=$cat_subcats_label?></b></div>
			<div class="cat_subcats cat_multicolumn">
				<ul id="cat_subcats_list"></ul>
			</div>
		</div>
	</div>
	<div id="cat_options">
		<div id="cat_skip"><a href="#">Skip</a><div id="cat_skip_arrow"></div></div>
		<a href="#" class="button cat_button_save_disabled" id="cat_save">Save</a>
	</div>
</div>

<?=$css?>
<?echo $js;?>
<div id="cat_head_outer">
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
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
		<div id="cat_cancel"><a href="#">Cancel</a></div>
		<a href="#" class="button cat_button_save_disabled" id="cat_save_editpage">Update Categories</a>
	</div>
</div>

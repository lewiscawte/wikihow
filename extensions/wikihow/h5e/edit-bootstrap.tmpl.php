<script>

// This global is used in the WH.h5e module.  During html5 editing 
// initialization, the previously clicked button is retrieved (and clicked),
// then the live handler below is removed.
var whH5EClickedEditButton = null;

(function ($) {
	var handler = function() {
		whH5EClickedEditButton = this;
		return false;
	};

	$('.editsectionbutton')
		.live('click', handler);
	$('.edit_article_button')
		.live('click', handler);
	$('#tab_edit')
		.live('click', handler);
})(jQuery);

</script>

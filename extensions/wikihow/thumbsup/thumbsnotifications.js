jQuery('.th_close').click(function() {
	var revId = jQuery(this).attr('id');

	jQuery.get('/Special:ThumbsNotifications/' + revId, function(data) {});
	jQuery('#th_msg_' + revId).hide();	
	}
);

jQuery('.th_avimg').hover(
	function() {
		var txt = jQuery(this).parent().find('span').html();
		if (txt) {
			var pos = jQuery(this).offset();
			var posTop = pos.top - 55;
			jQuery('<div class="th_tooltip_text"><div>' + txt + '</div></div>').appendTo('body');
			var imgWidth = pos.left + (jQuery('.th_tooltip').width() / 2);
			var posLeft = imgWidth - 23;
			jQuery('.th_tooltip_text').css('top', posTop).css('left', posLeft);
		}
	}, 
	function() {
		jQuery('.th_tooltip_text').remove();
	}
);

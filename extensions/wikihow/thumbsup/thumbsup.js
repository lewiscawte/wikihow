var isThumbedUp = false;
$('.thumbbutton').bind('click', function (c) {
	if (!isThumbedUp) {
		c.preventDefault();
		// only allow one click
		$(this).unbind('click');
		isThumbedUp = true;

		var thumbsurl = $('#thumbUp').html().replace(/\&amp;/g,'&');
		$('#thumbsup-status').html(wfMsg('rcpatrol_thumb_msg_pending')).fadeIn('slow', function(n){});
		$.get(thumbsurl, {}, function(html){
			$('#thumbsup-status').css('background-color','#CFC').html(wfMsg('rcpatrol_thumb_msg_complete'));
			$('.thumbbutton').css('background-position', '0 0');
		});
	}
});


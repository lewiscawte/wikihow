$('.thumbbutton').bind('click', function (c) {
	c.preventDefault();
	// only allow one click
	$(this).unbind('click');

	var thumbsurl = $('#thumbUp').html().replace(/\&amp;/g,'&');
	$('#thumbsup-status').html(wfMsg('rcpatrol_thumb_msg_pending')).fadeIn('slow', function(n){});
	$.get(thumbsurl, {}, function(html){
		$('#thumbsup-status').css('background-color','#CFC').html(wfMsg('rcpatrol_thumb_msg_complete'));
		$('.thumbbutton').css('background-position', '0 0');
	});
});

/*
function thumbsup() {
	var thumbsurl = $('#thumbUp').html().replace(/\&amp;/g,'&');
	$('.thumbbutton').attr('onclick', '');
	$('#thumbsup-status').html('Giving the below revision(s) a thumbs up...').fadeIn('slow', function(n){});
	$.get(thumbsurl, {}, function(html){
		$('#thumbsup-status').css('background-color','#CFC').html('Thanks for thumbing up the below revision(s)');
		$('.thumbbutton').css('background-position', '0 0');
	});
*/

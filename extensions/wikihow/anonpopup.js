
try {  var mpmetrics = new MixpanelLib('56772aa11cf306f711166fd445f8d7cb'); } catch(err) { null_fn = function () {}; var mpmetrics = {  track: null_fn,  track_funnel: null_fn,  register: null_fn,  register_once: null_fn, register_funnel: null_fn }; } 

var html = '<p><b>Thanks!  We really appreciate your edit.</b></p>' +
			'<p><br />We would love to have your help fixing some spelling errors!</p>' +
			'<p style=\'line-height:38px;text-align:right;padding:0 80px 0 60px;\'><br /><a href=\'#\' id=\'anonpop-start\' class=\'button button136\' style=\'float:left;\'>Yes, I\'ll help</a>' +
			'<a href=\'#\' id=\'anonpop-no\' style=\'font-size:.85em;font-weight:bold;\'>No, thanks.</a></p>';
$('#img-box').css('font-size', '1.2em');

$('#img-box').html(html);
$('#img-box').dialog({
	modal: true,
	title: 'Thank you!',
	show: 'slide',
	closeOnEscape: true,
	position: 'center',
	height: 220,
	width: 450,
	open: function () {
		mpmetrics.track('Anon Post Edit OPEN 03');
	},
	close: function() {
		mpmetrics.track('Anon Post Edit CLOSE 03');
	}
});
$('#anonpop-no').click(function() {
	mpmetrics.track('Anon Post Edit No 03');
	$('#img-box').dialog('close');
});
$('#anonpop-start').click(function() {
	mpmetrics.track('Anon Post Edit Start 03','',function() {
		window.location = '/Special:StarterTool';
	});
});
